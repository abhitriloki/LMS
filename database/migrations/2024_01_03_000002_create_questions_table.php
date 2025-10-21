<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_id')->nullable()->constrained('assessments')->cascadeOnDelete();
            $table->enum('question_type', ['multiple_choice', 'true_false', 'fill_blank', 'essay', 'matching', 'drag_drop'])->default('multiple_choice');
            $table->text('question_text');
            $table->text('explanation')->nullable();
            $table->decimal('points', 5, 2)->default(1);
            $table->integer('order_index')->default(0);
            $table->enum('difficulty_level', ['easy', 'medium', 'hard'])->default('medium');
            $table->json('metadata')->nullable()->comment('Additional data for complex question types');
            $table->json('tags')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
