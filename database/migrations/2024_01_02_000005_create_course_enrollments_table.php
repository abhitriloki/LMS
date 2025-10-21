<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('enrollment_type', ['self', 'mandatory', 'assigned'])->default('self');
            $table->foreignId('enrolled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('enrollment_date')->useCurrent();
            $table->timestamp('deadline')->nullable();
            $table->enum('status', ['active', 'completed', 'dropped', 'expired'])->default('active');
            $table->timestamp('completion_date')->nullable();
            $table->decimal('final_score', 5, 2)->nullable();
            $table->unsignedBigInteger('certificate_id')->nullable();
            $table->decimal('progress_percentage', 5, 2)->default(0);
            $table->timestamp('last_accessed_at')->nullable();
            $table->timestamps();
            
            $table->unique(['course_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_enrollments');
    }
};
