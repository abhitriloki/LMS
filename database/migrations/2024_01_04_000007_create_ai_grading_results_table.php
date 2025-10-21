<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_grading_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attempt_response_id')->constrained('attempt_responses')->cascadeOnDelete();
            $table->decimal('ai_score', 5, 2);
            $table->decimal('confidence_score', 5, 2);
            $table->text('ai_feedback');
            $table->json('rubric_scores')->nullable();
            $table->boolean('flagged_for_review')->default(false);
            $table->decimal('final_score', 5, 2)->nullable();
            $table->text('instructor_feedback')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_grading_results');
    }
};
