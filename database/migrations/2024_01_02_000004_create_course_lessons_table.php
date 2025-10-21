<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')->constrained('course_modules')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('content_type', ['video', 'pdf', 'scorm', 'presentation', 'text', 'quiz'])->default('text');
            $table->string('content_path')->nullable();
            $table->text('content_text')->nullable();
            $table->integer('duration')->nullable()->comment('Duration in minutes');
            $table->integer('order_index')->default(0);
            $table->boolean('is_downloadable')->default(false);
            $table->boolean('is_published')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_lessons');
    }
};
