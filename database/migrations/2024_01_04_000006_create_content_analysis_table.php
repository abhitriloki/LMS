<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_analysis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->decimal('readability_score', 5, 2)->nullable();
            $table->decimal('complexity_score', 5, 2)->nullable();
            $table->decimal('engagement_score', 5, 2)->nullable();
            $table->json('identified_gaps')->nullable();
            $table->json('suggestions')->nullable();
            $table->json('accessibility_issues')->nullable();
            $table->text('summary')->nullable();
            $table->timestamp('analyzed_at')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_analysis');
    }
};
