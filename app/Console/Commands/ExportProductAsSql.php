<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\TrainingArtifact;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Turns a product built locally (via Super Admin, or one of the
 * "build a product" prompts) into a single, portable .sql file that can
 * be pasted straight into phpMyAdmin's SQL tab on a live server — no
 * server-side Claude Code access, no SSH, no artisan access required on
 * the live side at all.
 *
 * Why not just copy the database rows directly? Because the live
 * database hands out its own auto-increment IDs independently of this
 * one — the local "product #104" might land as #12 or #400 live, and
 * every question/option/rule reference has to follow along correctly.
 * This resolves the target business/industry by NAME (not ID) via a
 * subquery, and chains every other reference through MySQL session
 * variables (@product_id, @q1, @q1_opt1, ...) captured via
 * LAST_INSERT_ID() as each row is inserted — so the whole script is
 * self-contained and correct regardless of what IDs the target database
 * already has in use.
 *
 * Deliberately leaves is_published = 0 on everything it inserts, rather
 * than trying to hand-build a matching published_snapshot in raw SQL —
 * that snapshot's shape is real PHP logic (Product::
 * buildPublishableSnapshot()) that could change over time, and getting
 * it wrong in SQL would silently break quoting. Clicking the real
 * Publish button once on the live site after running this script reuses
 * that logic correctly instead of trying to reimplement it here.
 */
class ExportProductAsSql extends Command
{
    protected $signature = 'product:export-sql {product : The local product ID to export} {--output= : Path to write the .sql file to}';

    protected $description = 'Export a product (questions, options, rules, and its training artifact) as a portable SQL script for pasting into phpMyAdmin on a live server';

    public function handle(): int
    {
        $product = Product::withoutGlobalScopes()
            ->with(['business', 'questions' => fn ($q) => $q->withoutGlobalScopes()->orderBy('sort_order'), 'questions.options' => fn ($q) => $q->withoutGlobalScopes(), 'rules' => fn ($q) => $q->withoutGlobalScopes()])
            ->find($this->argument('product'));

        if (! $product) {
            $this->error("No product found with ID {$this->argument('product')}.");

            return self::FAILURE;
        }

        if (! $product->business->is_template) {
            $this->warn("Note: \"{$product->business->name}\" isn't a template business — this will still export fine, but the generated script looks up the target by that exact business name, which only makes sense if a business with that same name already exists on the live server.");
        }

        $artifact = TrainingArtifact::where('product_id', $product->id)->first();

        $sql = $this->buildSql($product, $artifact);

        $outputPath = $this->option('output')
            ?? storage_path('app/exports/product-'.$product->id.'-'.now()->format('Y-m-d-His').'.sql');

        if (! is_dir(dirname($outputPath))) {
            mkdir(dirname($outputPath), 0755, true);
        }

        file_put_contents($outputPath, $sql);

        $this->info("Exported \"{$product->name}\" to:");
        $this->line($outputPath);

        return self::SUCCESS;
    }

    private function buildSql(Product $product, ?TrainingArtifact $artifact): string
    {
        $lines = [];

        $lines[] = '-- '.str_repeat('=', 70);
        $lines[] = "-- Product export: \"{$product->name}\" (local id #{$product->id})";
        $lines[] = '-- Generated '.now()->toDateTimeString();
        $lines[] = '--';
        $lines[] = '-- HOW TO USE THIS:';
        $lines[] = '--   1. Log into phpMyAdmin on the live server, open the right database.';
        $lines[] = '--   2. Click the "SQL" tab, paste this whole file in, click Go.';
        $lines[] = '--   3. Log into the live Super Admin, find this product, click Publish.';
        $lines[] = '--      (That last click is required — this script deliberately leaves';
        $lines[] = '--       the product unpublished so the real Publish button, not this';
        $lines[] = '--       script, is what finalizes it.)';
        $lines[] = '-- '.str_repeat('=', 70);
        $lines[] = '';

        $lines[] = '-- Finds the target business by name — must already exist on the live';
        $lines[] = "-- server (e.g. the \"{$product->business->name}\" template).";
        $lines[] = 'SET @business_id = (SELECT id FROM businesses WHERE name = '.$this->quote($product->business->name).' AND is_template = 1 LIMIT 1);';
        $lines[] = '';

        $lines[] = 'INSERT INTO products (business_id, name, description, base_price, is_active, is_published, created_at, updated_at)';
        $lines[] = 'VALUES (@business_id, '.$this->quote($product->name).', '.$this->quote($product->description).', '.$this->num($product->base_price).', '.($product->is_active ? 1 : 0).', 0, NOW(), NOW());';
        $lines[] = 'SET @product_id = LAST_INSERT_ID();';
        $lines[] = '';

        // First pass: emit every question + its options, capturing each
        // row's ID into a @q{n} / @q{n}_opt{m} variable as it's created —
        // needed below so later questions/rules can reference earlier ones
        // by variable instead of a hardcoded ID that won't exist yet.
        $questionVar = [];
        $optionVar = [];

        foreach ($product->questions as $i => $question) {
            $qv = '@q'.($i + 1);
            $questionVar[$question->id] = $qv;

            $displayConditionsSql = $this->displayConditionsToSql($question->display_conditions, $questionVar, $optionVar);

            $lines[] = "-- Question {$question->sort_order}: {$question->question_text}";
            $lines[] = 'INSERT INTO questions (business_id, product_id, question_text, type, sort_order, display_conditions, is_published, created_at, updated_at)';
            $lines[] = 'VALUES (@business_id, @product_id, '.$this->quote($question->question_text).', '.$this->quote($question->type).', '.$question->sort_order.', '.$displayConditionsSql.', 0, NOW(), NOW());';
            $lines[] = "SET {$qv} = LAST_INSERT_ID();";

            foreach ($question->options as $j => $option) {
                $ov = $qv.'_opt'.($j + 1);
                $optionVar[$option->id] = $ov;

                $lines[] = 'INSERT INTO options (business_id, question_id, label, price_modifier, is_published, created_at, updated_at)';
                $lines[] = "VALUES (@business_id, {$qv}, ".$this->quote($option->label).', '.$this->num($option->price_modifier).", 0, NOW(), NOW());";
                $lines[] = "SET {$ov} = LAST_INSERT_ID();";
            }

            $lines[] = '';
        }

        foreach ($product->rules as $rule) {
            $conditionSql = $this->conditionLogicToSql($rule->condition_logic, $questionVar, $optionVar);
            $actionSql = $this->quote(json_encode($rule->action_logic));

            $lines[] = "-- Rule: {$rule->name}";
            $lines[] = 'INSERT INTO rules (business_id, product_id, name, condition_logic, action_logic, is_published, created_at, updated_at)';
            // No CAST(... AS JSON) here — MariaDB (common on shared hosting)
            // doesn't support that syntax at all, unlike real MySQL. A
            // plain quoted JSON string is valid on both: the column is
            // already typed JSON, and inserting a well-formed JSON string
            // into it needs no explicit cast either way.
            $lines[] = 'VALUES (@business_id, @product_id, '.$this->quote($rule->name).", {$conditionSql}, {$actionSql}, 0, NOW(), NOW());";
            $lines[] = '';
        }

        if ($artifact) {
            $lines[] = '-- Training artifact (build sheet)';

            if ($product->business->industry_id && $product->business->industry) {
                $lines[] = 'SET @industry_id = (SELECT id FROM industries WHERE name = '.$this->quote($product->business->industry->name).' LIMIT 1);';
            } else {
                $lines[] = 'SET @industry_id = NULL;';
            }

            $lines[] = 'INSERT INTO training_artifacts (title, industry_id, product_id, html, created_by, created_at, updated_at)';
            $lines[] = 'VALUES ('.$this->quote($artifact->title).', @industry_id, @product_id, '.$this->quote($artifact->html).', NULL, NOW(), NOW());';
            $lines[] = '';
        }

        return implode("\n", $lines);
    }

    /**
     * @param  array<int|string, string>  $questionVar
     * @param  array<int|string, string>  $optionVar
     */
    private function displayConditionsToSql(mixed $displayConditions, array $questionVar, array $optionVar): string
    {
        if (empty($displayConditions['conditions'])) {
            return 'NULL';
        }

        $conditionExprs = array_map(
            fn ($condition) => $this->singleConditionToJsonObjectSql($condition, $questionVar, $optionVar),
            $displayConditions['conditions']
        );

        $logic = $this->quote($displayConditions['logic'] ?? 'and');

        return "JSON_OBJECT('logic', {$logic}, 'conditions', JSON_ARRAY(".implode(', ', $conditionExprs).'))';
    }

    /**
     * @param  array<int|string, string>  $questionVar
     * @param  array<int|string, string>  $optionVar
     */
    private function conditionLogicToSql(mixed $conditionLogic, array $questionVar, array $optionVar): string
    {
        if (empty($conditionLogic)) {
            return 'NULL';
        }

        return $this->singleConditionToJsonObjectSql($conditionLogic, $questionVar, $optionVar);
    }

    /**
     * @param  array<int|string, string>  $questionVar
     * @param  array<int|string, string>  $optionVar
     */
    private function singleConditionToJsonObjectSql(array $condition, array $questionVar, array $optionVar): string
    {
        $questionRef = $questionVar[$condition['question_id']] ?? null;

        // Falls back to the literal old ID only if it somehow isn't one of
        // this product's own questions (shouldn't happen in practice —
        // conditions only ever reference an earlier question in the same
        // product) rather than silently producing broken SQL.
        $questionExpr = $questionRef ?? (int) $condition['question_id'];

        $value = $condition['value'] ?? null;
        $valueRef = $optionVar[$value] ?? null;

        // The value is an option_id reference (for single_choice questions)
        // when it matches a known option; otherwise it's a raw number/text
        // answer (number/text question types) and gets passed through as-is.
        $valueExpr = $valueRef
            ? "CAST({$valueRef} AS CHAR)"
            : $this->quote((string) $value);

        return "JSON_OBJECT('question_id', {$questionExpr}, 'operator', ".$this->quote($condition['operator'] ?? 'equals').", 'value', {$valueExpr})";
    }

    private function quote(?string $value): string
    {
        if ($value === null) {
            return 'NULL';
        }

        return DB::connection()->getPdo()->quote($value);
    }

    private function num(mixed $value): string
    {
        return (string) (float) $value;
    }
}
