<?php

namespace App\Services;

use App\Models\Business;
use App\Models\BusinessTrainingArtifact;
use App\Models\Option;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Question;
use App\Models\Rule;
use App\Models\TrainingArtifact;
use Illuminate\Support\Collection;

/**
 * Copies a template business's entire catalog (products, questions,
 * options, rules, images) into another business — as brand new rows
 * with their own IDs, not references to the template's rows. The
 * template itself is only ever read here, never written to. Used both
 * at registration time (a brand new, empty business) and by Super Admin
 * to push a template's catalog into an existing business — in the
 * latter case this is purely additive, alongside whatever that business
 * already has; nothing existing is ever touched or removed.
 *
 * The tricky part: a rule's condition_logic / action_logic JSON, and a
 * question's own display_conditions JSON, reference specific
 * question_id / option_id values (e.g. "if question 4 = option 9").
 * Copied verbatim, those numbers would point at the TEMPLATE's rows, not
 * the new business's — so as each question/option is cloned, we
 * remember old ID -> new ID, then rewrite that JSON through the map
 * before saving it. display_conditions can only ever reference an
 * earlier question in the same product (enforced at publish time by
 * QuestionVisibility::assertPublishable()), so questions are cloned in
 * sort_order so that earlier questions are always already in the map by
 * the time a later one needs to remap its own condition against them.
 */
class TemplateCloner
{
    /**
     * $onlyProductIds narrows the clone to specific products from the
     * template rather than its whole catalog — used when a registering
     * customer hand-picks products instead of taking the whole template.
     * Left null (the default), every caller that wants the full catalog
     * — Super Admin's "Copy Template" action chief among them — is
     * unaffected.
     */
    public function clone(Business $template, Business $target, ?array $onlyProductIds = null): void
    {
        // Every nested relation below needs withoutGlobalScopes() of its
        // own — Product::withoutGlobalScopes() only lifts the tenant scope
        // on Product itself, not on eager-loaded Question/Option/Rule/
        // ProductImage rows, which still filter to whichever business is
        // currently logged in (see BelongsToBusiness). Skipping this
        // silently clones an empty shell (no questions/options/rules)
        // whenever this runs under a real logged-in business session —
        // it only ever looked fine before because every prior caller ran
        // outside that session (an unauthenticated webhook, or Super
        // Admin's separate guard, where the scope never activates).
        $products = Product::withoutGlobalScopes()
            ->where('business_id', $template->id)
            ->when($onlyProductIds !== null, fn ($query) => $query->whereIn('id', $onlyProductIds))
            ->with([
                'questions' => fn ($query) => $query->withoutGlobalScopes()->orderBy('sort_order'),
                'questions.options' => fn ($query) => $query->withoutGlobalScopes(),
                'rules' => fn ($query) => $query->withoutGlobalScopes(),
                'images' => fn ($query) => $query->withoutGlobalScopes(),
            ])
            ->get();

        foreach ($products as $sourceProduct) {
            $newProduct = Product::create([
                'business_id' => $target->id,
                'source_template_product_id' => $sourceProduct->id,
                'name' => $sourceProduct->name,
                'description' => $sourceProduct->description,
                'base_price' => $sourceProduct->base_price,
                'is_active' => $sourceProduct->is_active,
                'is_published' => false,
            ]);

            $questionIdMap = [];
            $optionIdMap = [];
            $questionTypeById = [];

            foreach ($sourceProduct->questions as $sourceQuestion) {
                $newQuestion = Question::create([
                    'business_id' => $target->id,
                    'product_id' => $newProduct->id,
                    'question_text' => $sourceQuestion->question_text,
                    'type' => $sourceQuestion->type,
                    'sort_order' => $sourceQuestion->sort_order,
                    'display_conditions' => $this->remapDisplayConditions(
                        $sourceQuestion->display_conditions, $questionIdMap, $optionIdMap, $questionTypeById
                    ),
                    'is_published' => false,
                ]);

                $questionIdMap[$sourceQuestion->id] = $newQuestion->id;
                $questionTypeById[$sourceQuestion->id] = $sourceQuestion->type;

                foreach ($sourceQuestion->options as $sourceOption) {
                    $newOption = Option::create([
                        'business_id' => $target->id,
                        'question_id' => $newQuestion->id,
                        'label' => $sourceOption->label,
                        'price_modifier' => $sourceOption->price_modifier,
                        'is_published' => false,
                    ]);

                    $optionIdMap[$sourceOption->id] = $newOption->id;
                }
            }

            foreach ($sourceProduct->rules as $sourceRule) {
                Rule::create([
                    'business_id' => $target->id,
                    'product_id' => $newProduct->id,
                    'name' => $sourceRule->name,
                    'condition_logic' => $this->remapConditionLogic(
                        $sourceRule->condition_logic, $questionIdMap, $optionIdMap, $questionTypeById
                    ),
                    'action_logic' => $this->remapActionLogic($sourceRule->action_logic, $optionIdMap),
                    'is_published' => false,
                ]);
            }

            // Images are shared, read-only illustrative assets on the
            // public disk — cloning just points a new row at the same
            // file rather than duplicating it on disk.
            foreach ($sourceProduct->images as $sourceImage) {
                ProductImage::create([
                    'business_id' => $target->id,
                    'product_id' => $newProduct->id,
                    'path' => $sourceImage->path,
                    'sort_order' => $sourceImage->sort_order,
                ]);
            }

            // If Super Admin has authored a build sheet for this template
            // product, install the business's own private copy alongside
            // it — see BusinessTrainingArtifact's docblock for why it's a
            // copy, not a live link.
            $artifact = TrainingArtifact::where('product_id', $sourceProduct->id)->first();

            if ($artifact) {
                BusinessTrainingArtifact::create([
                    'business_id' => $target->id,
                    'product_id' => $newProduct->id,
                    'source_artifact_id' => $artifact->id,
                    'title' => $artifact->title,
                    'html' => $artifact->html,
                ]);
            }
        }
    }

    private function remapDisplayConditions(mixed $displayConditions, array $questionIdMap, array $optionIdMap, array $questionTypeById): mixed
    {
        if (empty($displayConditions['conditions'])) {
            return $displayConditions;
        }

        $displayConditions['conditions'] = array_map(
            fn ($condition) => $this->remapSingleCondition($condition, $questionIdMap, $optionIdMap, $questionTypeById),
            $displayConditions['conditions']
        );

        return $displayConditions;
    }

    private function remapConditionLogic(mixed $conditionLogic, array $questionIdMap, array $optionIdMap, array $questionTypeById): mixed
    {
        if (empty($conditionLogic)) {
            return $conditionLogic;
        }

        if (array_is_list($conditionLogic)) {
            return array_map(
                fn ($condition) => $this->remapSingleCondition($condition, $questionIdMap, $optionIdMap, $questionTypeById),
                $conditionLogic
            );
        }

        return $this->remapSingleCondition($conditionLogic, $questionIdMap, $optionIdMap, $questionTypeById);
    }

    private function remapSingleCondition(array $condition, array $questionIdMap, array $optionIdMap, array $questionTypeById): array
    {
        $oldQuestionId = $condition['question_id'] ?? null;
        $condition['question_id'] = $questionIdMap[$oldQuestionId] ?? $oldQuestionId;

        // The condition's "value" is only an option_id when the question
        // it's answering is single_choice — for number/text questions
        // it's a raw number or string and must be left alone.
        if (($questionTypeById[$oldQuestionId] ?? null) === 'single_choice') {
            $oldValue = $condition['value'] ?? null;
            $condition['value'] = $optionIdMap[$oldValue] ?? $oldValue;
        }

        return $condition;
    }

    private function remapActionLogic(mixed $actionLogic, array $optionIdMap): mixed
    {
        if (empty($actionLogic)) {
            return $actionLogic;
        }

        if (($actionLogic['type'] ?? null) === 'set_option_price') {
            $oldOptionId = $actionLogic['option_id'] ?? null;
            $actionLogic['option_id'] = $optionIdMap[$oldOptionId] ?? $oldOptionId;
        }

        return $actionLogic;
    }

    /**
     * Only ever resolves products that actually belong to a template
     * business and are active — the same tamper guard the old
     * whole-template picker had, just scoped down to individual products
     * now that registration lets a customer hand-pick them.
     *
     * @param  array<int, int|string>  $ids
     * @return Collection<int, Product>
     */
    public function resolveProducts(array $ids): Collection
    {
        $ids = collect($ids)->map(fn ($id) => (int) $id)->filter()->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        return Product::withoutGlobalScopes()
            ->whereIn('id', $ids)
            ->where('is_active', true)
            ->whereHas('business', fn ($query) => $query->where('is_template', true))
            ->get();
    }

    /**
     * Groups the given products by the template business they came from
     * and clones each group in — so picking products from more than one
     * template in the same industry still works in one pass. Returns the
     * IDs of the template businesses actually used, for recording via
     * Business::templatesUsed().
     *
     * @param  Collection<int, Product>  $products
     * @return array<int, int>
     */
    public function cloneProductsInto(Business $target, Collection $products): array
    {
        $templateIds = [];

        foreach ($products->groupBy('business_id') as $templateBusinessId => $templateProducts) {
            $template = Business::withoutGlobalScopes()->find($templateBusinessId);

            if (! $template) {
                continue;
            }

            $this->clone($template, $target, $templateProducts->pluck('id')->all());
            $templateIds[] = $template->id;
        }

        return $templateIds;
    }
}
