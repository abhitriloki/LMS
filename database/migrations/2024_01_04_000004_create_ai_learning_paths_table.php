<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_learning_paths', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('target_role')->nullable();
            $table->json('current_skills')->nullable();
            $table->json('target_skills')->nullable();
            $table->json('course_sequence')->comment('Ordered array of course IDs');
            $table->integer('estimated_duration')->nullable()->comment('Total duration in hours');
            $table->decimal('progress_percentage', 5, 2)->default(0);
            $table->enum('status', ['active', 'completed', 'paused', 'abandoned'])->default('active');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->json('milestones')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_learning_paths');
    }
};
