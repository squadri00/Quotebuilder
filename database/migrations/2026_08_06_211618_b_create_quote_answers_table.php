<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('quote_answers', function (Blueprint $table) {
            $table->id();
            // Copied from the parent quote at write time (not automatically
            // scoped via BelongsToBusiness — see app/Models/QuoteAnswer.php)
            // purely so this table can be filtered/indexed without a join.
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('quote_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            // Nullable: only set when the question was answered by picking
            // an option (single_choice). Number/text answers use answer_value.
            $table->foreignId('option_id')->nullable()->constrained()->nullOnDelete();
            $table->string('answer_value')->nullable();
            $table->timestamps();

            // Expected to grow large — see docs/conventions.md.
            $table->index(['business_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quote_answers');
    }
};
