<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('generated_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id')->constrained('ai_question_jobs')->cascadeOnDelete();
            $table->foreignId('question_id')->nullable()->constrained('questions')->nullOnDelete();
            $table->enum('question_type', ['multiple_choice', 'true_false', 'fill_blank', 'essay', 'matching', 'drag_drop']);
            $table->text('question_text');
            $table->text('explanation')->nullable();
            $table->json('options')->nullable();
            $table->json('correct_answer')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'modified'])->default('pending');
            $table->text('review_notes')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generated_questions');
    }
};
