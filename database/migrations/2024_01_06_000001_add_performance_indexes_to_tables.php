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
        // Users table indexes
        Schema::table('users', function (Blueprint $table) {
            $table->index('email');
            $table->index('role');
            $table->index('department_id');
            $table->index(['role', 'department_id']);
            $table->index('created_at');
        });

        // Courses table indexes
        Schema::table('courses', function (Blueprint $table) {
            $table->index('category_id');
            $table->index('is_published');
            $table->index('is_mandatory');
            $table->index('is_featured');
            $table->index('difficulty_level');
            $table->index(['is_published', 'category_id']);
            $table->index(['is_published', 'is_featured']);
            $table->index('created_by');
            $table->index('created_at');
        });

        // Course enrollments table indexes
        Schema::table('course_enrollments', function (Blueprint $table) {
            $table->index('user_id');
            $table->index('course_id');
            $table->index('status');
            $table->index(['user_id', 'status']);
            $table->index(['course_id', 'status']);
            $table->index('deadline');
            $table->index('completion_date');
            $table->index('enrollment_date');
        });

        // Lesson progress table indexes
        Schema::table('lesson_progress', function (Blueprint $table) {
            $table->index('enrollment_id');
            $table->index('lesson_id');
            $table->index('status');
            $table->index(['enrollment_id', 'status']);
            $table->index('last_accessed_at');
            $table->index('completed_at');
        });

        // Assessments table indexes
        Schema::table('assessments', function (Blueprint $table) {
            $table->index('course_id');
            $table->index('is_published');
            $table->index('created_by');
            $table->index(['course_id', 'is_published']);
        });

        // Assessment attempts table indexes
        Schema::table('assessment_attempts', function (Blueprint $table) {
            $table->index('assessment_id');
            $table->index('user_id');
            $table->index('status');
            $table->index(['user_id', 'assessment_id']);
            $table->index(['assessment_id', 'status']);
            $table->index('started_at');
            $table->index('completed_at');
        });

        // Questions table indexes
        Schema::table('questions', function (Blueprint $table) {
            $table->index('assessment_id');
            $table->index('question_type');
            $table->index(['assessment_id', 'question_type']);
        });

        // AI recommendations table indexes
        Schema::table('ai_recommendations', function (Blueprint $table) {
            $table->index('user_id');
            $table->index('course_id');
            $table->index('status');
            $table->index(['user_id', 'status']);
            $table->index('expires_at');
            $table->index('created_at');
        });

        // AI question jobs table indexes
        Schema::table('ai_question_jobs', function (Blueprint $table) {
            $table->index('lesson_id');
            $table->index('status');
            $table->index('created_by');
            $table->index(['lesson_id', 'status']);
        });

        // Generated questions table indexes
        Schema::table('generated_questions', function (Blueprint $table) {
            $table->index('job_id');
            $table->index('status');
            $table->index(['job_id', 'status']);
        });

        // AI learning paths table indexes
        Schema::table('ai_learning_paths', function (Blueprint $table) {
            $table->index('user_id');
            $table->index('status');
            $table->index(['user_id', 'status']);
            $table->index('created_at');
        });

        // Content analysis table indexes
        Schema::table('content_analysis', function (Blueprint $table) {
            $table->index('course_id');
            $table->index('analyzed_by');
            $table->index('created_at');
        });

        // AI grading results table indexes
        Schema::table('ai_grading_results', function (Blueprint $table) {
            $table->index('attempt_response_id');
            $table->index('needs_review');
            $table->index('reviewed_by');
            $table->index(['needs_review', 'reviewed_by']);
        });

        // Chatbot conversations table indexes
        Schema::table('chatbot_conversations', function (Blueprint $table) {
            $table->index('user_id');
            $table->index('status');
            $table->index(['user_id', 'status']);
            $table->index('created_at');
        });

        // Chatbot messages table indexes
        Schema::table('chatbot_messages', function (Blueprint $table) {
            $table->index('conversation_id');
            $table->index('sender_type');
            $table->index(['conversation_id', 'sender_type']);
            $table->index('created_at');
        });

        // Certificates table indexes
        Schema::table('certificates', function (Blueprint $table) {
            $table->index('enrollment_id');
            $table->index('user_id');
            $table->index('certificate_number');
            $table->index('issued_at');
            $table->index('expires_at');
        });

        // Analytics events table indexes
        Schema::table('analytics_events', function (Blueprint $table) {
            $table->index('user_id');
            $table->index('event_type');
            $table->index(['user_id', 'event_type']);
            $table->index('created_at');
        });

        // Reports table indexes
        Schema::table('reports', function (Blueprint $table) {
            $table->index('report_type');
            $table->index('status');
            $table->index('created_by');
            $table->index(['report_type', 'status']);
            $table->index('created_at');
        });

        // Course modules table indexes
        Schema::table('course_modules', function (Blueprint $table) {
            $table->index('course_id');
            $table->index('order_index');
            $table->index(['course_id', 'order_index']);
        });

        // Course lessons table indexes
        Schema::table('course_lessons', function (Blueprint $table) {
            $table->index('module_id');
            $table->index('content_type');
            $table->index('order_index');
            $table->index(['module_id', 'order_index']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop indexes from users table
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['email']);
            $table->dropIndex(['role']);
            $table->dropIndex(['department_id']);
            $table->dropIndex(['role', 'department_id']);
            $table->dropIndex(['created_at']);
        });

        // Drop indexes from courses table
        Schema::table('courses', function (Blueprint $table) {
            $table->dropIndex(['category_id']);
            $table->dropIndex(['is_published']);
            $table->dropIndex(['is_mandatory']);
            $table->dropIndex(['is_featured']);
            $table->dropIndex(['difficulty_level']);
            $table->dropIndex(['is_published', 'category_id']);
            $table->dropIndex(['is_published', 'is_featured']);
            $table->dropIndex(['created_by']);
            $table->dropIndex(['created_at']);
        });

        // Drop indexes from course_enrollments table
        Schema::table('course_enrollments', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['course_id']);
            $table->dropIndex(['status']);
            $table->dropIndex(['user_id', 'status']);
            $table->dropIndex(['course_id', 'status']);
            $table->dropIndex(['deadline']);
            $table->dropIndex(['completion_date']);
            $table->dropIndex(['enrollment_date']);
        });

        // Drop indexes from lesson_progress table
        Schema::table('lesson_progress', function (Blueprint $table) {
            $table->dropIndex(['enrollment_id']);
            $table->dropIndex(['lesson_id']);
            $table->dropIndex(['status']);
            $table->dropIndex(['enrollment_id', 'status']);
            $table->dropIndex(['last_accessed_at']);
            $table->dropIndex(['completed_at']);
        });

        // Drop indexes from assessments table
        Schema::table('assessments', function (Blueprint $table) {
            $table->dropIndex(['course_id']);
            $table->dropIndex(['is_published']);
            $table->dropIndex(['created_by']);
            $table->dropIndex(['course_id', 'is_published']);
        });

        // Drop indexes from assessment_attempts table
        Schema::table('assessment_attempts', function (Blueprint $table) {
            $table->dropIndex(['assessment_id']);
            $table->dropIndex(['user_id']);
            $table->dropIndex(['status']);
            $table->dropIndex(['user_id', 'assessment_id']);
            $table->dropIndex(['assessment_id', 'status']);
            $table->dropIndex(['started_at']);
            $table->dropIndex(['completed_at']);
        });

        // Drop indexes from questions table
        Schema::table('questions', function (Blueprint $table) {
            $table->dropIndex(['assessment_id']);
            $table->dropIndex(['question_type']);
            $table->dropIndex(['assessment_id', 'question_type']);
        });

        // Drop indexes from ai_recommendations table
        Schema::table('ai_recommendations', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['course_id']);
            $table->dropIndex(['status']);
            $table->dropIndex(['user_id', 'status']);
            $table->dropIndex(['expires_at']);
            $table->dropIndex(['created_at']);
        });

        // Drop indexes from ai_question_jobs table
        Schema::table('ai_question_jobs', function (Blueprint $table) {
            $table->dropIndex(['lesson_id']);
            $table->dropIndex(['status']);
            $table->dropIndex(['created_by']);
            $table->dropIndex(['lesson_id', 'status']);
        });

        // Drop indexes from generated_questions table
        Schema::table('generated_questions', function (Blueprint $table) {
            $table->dropIndex(['job_id']);
            $table->dropIndex(['status']);
            $table->dropIndex(['job_id', 'status']);
        });

        // Drop indexes from ai_learning_paths table
        Schema::table('ai_learning_paths', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['status']);
            $table->dropIndex(['user_id', 'status']);
            $table->dropIndex(['created_at']);
        });

        // Drop indexes from content_analysis table
        Schema::table('content_analysis', function (Blueprint $table) {
            $table->dropIndex(['course_id']);
            $table->dropIndex(['analyzed_by']);
            $table->dropIndex(['created_at']);
        });

        // Drop indexes from ai_grading_results table
        Schema::table('ai_grading_results', function (Blueprint $table) {
            $table->dropIndex(['attempt_response_id']);
            $table->dropIndex(['needs_review']);
            $table->dropIndex(['reviewed_by']);
            $table->dropIndex(['needs_review', 'reviewed_by']);
        });

        // Drop indexes from chatbot_conversations table
        Schema::table('chatbot_conversations', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['status']);
            $table->dropIndex(['user_id', 'status']);
            $table->dropIndex(['created_at']);
        });

        // Drop indexes from chatbot_messages table
        Schema::table('chatbot_messages', function (Blueprint $table) {
            $table->dropIndex(['conversation_id']);
            $table->dropIndex(['sender_type']);
            $table->dropIndex(['conversation_id', 'sender_type']);
            $table->dropIndex(['created_at']);
        });

        // Drop indexes from certificates table
        Schema::table('certificates', function (Blueprint $table) {
            $table->dropIndex(['enrollment_id']);
            $table->dropIndex(['user_id']);
            $table->dropIndex(['certificate_number']);
            $table->dropIndex(['issued_at']);
            $table->dropIndex(['expires_at']);
        });

        // Drop indexes from analytics_events table
        Schema::table('analytics_events', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['event_type']);
            $table->dropIndex(['user_id', 'event_type']);
            $table->dropIndex(['created_at']);
        });

        // Drop indexes from reports table
        Schema::table('reports', function (Blueprint $table) {
            $table->dropIndex(['report_type']);
            $table->dropIndex(['status']);
            $table->dropIndex(['created_by']);
            $table->dropIndex(['report_type', 'status']);
            $table->dropIndex(['created_at']);
        });

        // Drop indexes from course_modules table
        Schema::table('course_modules', function (Blueprint $table) {
            $table->dropIndex(['course_id']);
            $table->dropIndex(['order_index']);
            $table->dropIndex(['course_id', 'order_index']);
        });

        // Drop indexes from course_lessons table
        Schema::table('course_lessons', function (Blueprint $table) {
            $table->dropIndex(['module_id']);
            $table->dropIndex(['content_type']);
            $table->dropIndex(['order_index']);
            $table->dropIndex(['module_id', 'order_index']);
        });
    }
};
