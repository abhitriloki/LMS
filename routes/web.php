<?php

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CourseController;
use App\Http\Controllers\Admin\ModuleController;
use App\Http\Controllers\Admin\LessonController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\FileUploadController;
use App\Http\Controllers\Admin\EnrollmentController as AdminEnrollmentController;
use App\Http\Controllers\Admin\AssessmentController as AdminAssessmentController;
use App\Http\Controllers\Admin\QuestionController;
use App\Http\Controllers\Admin\CertificateController as AdminCertificateController;
use App\Http\Controllers\AssessmentController;
use App\Http\Controllers\AttemptController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\HealthCheckController;
use App\Http\Controllers\LessonViewController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RecommendationController;
use Illuminate\Support\Facades\Route;

// Health check endpoints (no authentication required)
Route::get('/health', [HealthCheckController::class, 'index'])->name('health.check');
Route::get('/ping', [HealthCheckController::class, 'ping'])->name('health.ping');

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// Notification routes
Route::middleware(['auth'])->group(function () {
    Route::get('notifications', [\App\Http\Controllers\NotificationController::class, 'index'])->name('notifications.index');
    Route::get('notifications/unread', [\App\Http\Controllers\NotificationController::class, 'unread'])->name('notifications.unread');
    Route::patch('notifications/{notification}/mark-read', [\App\Http\Controllers\NotificationController::class, 'markAsRead'])->name('notifications.mark-read');
    Route::post('notifications/mark-all-read', [\App\Http\Controllers\NotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-read');
    Route::delete('notifications/{notification}', [\App\Http\Controllers\NotificationController::class, 'destroy'])->name('notifications.destroy');
});

// Course catalog routes (public)
Route::get('/catalog', [CatalogController::class, 'index'])->name('catalog.index');
Route::get('/catalog/{course:slug}', [CatalogController::class, 'show'])->name('catalog.show');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    // Enrollment routes
    Route::get('/my-courses', [EnrollmentController::class, 'index'])->name('enrollments.index');
    Route::get('/courses/{course}/enroll', [EnrollmentController::class, 'show'])->name('enrollments.show');
    Route::post('/courses/{course}/enroll', [EnrollmentController::class, 'store'])->name('enrollments.store');
    Route::delete('/courses/{course}/unenroll', [EnrollmentController::class, 'destroy'])->name('enrollments.destroy');
    
    // Lesson viewer routes
    Route::get('/lessons/{lesson}', [LessonViewController::class, 'show'])->name('lessons.show');
    Route::post('/lessons/{lesson}/progress', [LessonViewController::class, 'updateProgress'])->name('lessons.progress.update');
    Route::post('/lessons/{lesson}/bookmark', [LessonViewController::class, 'saveBookmark'])->name('lessons.bookmark.save');
    Route::get('/lessons/{lesson}/bookmark', [LessonViewController::class, 'getBookmark'])->name('lessons.bookmark.get');
    Route::post('/lessons/{lesson}/complete', [LessonViewController::class, 'markComplete'])->name('lessons.complete');
    Route::get('/lessons/{lesson}/download', [LessonViewController::class, 'download'])->name('lessons.download');
    
    // Assessment routes
    Route::get('/assessments/{assessment}', [AssessmentController::class, 'show'])->name('assessments.show');
    Route::get('/my-attempts', [AssessmentController::class, 'myAttempts'])->name('assessments.my-attempts');
    
    // Recommendation routes
    Route::get('/recommendations', [RecommendationController::class, 'index'])->name('recommendations.index');
    Route::post('/recommendations/generate', [RecommendationController::class, 'generate'])->name('recommendations.generate');
    Route::post('/recommendations/{recommendation}/accept', [RecommendationController::class, 'accept'])->name('recommendations.accept');
    Route::post('/recommendations/{recommendation}/reject', [RecommendationController::class, 'reject'])->name('recommendations.reject');
    Route::post('/recommendations/{recommendation}/feedback', [RecommendationController::class, 'feedback'])->name('recommendations.feedback');
    
    // Learning Path routes
    Route::get('/learning-paths', [App\Http\Controllers\LearningPathController::class, 'index'])->name('learning-paths.index');
    Route::get('/learning-paths/create', [App\Http\Controllers\LearningPathController::class, 'create'])->name('learning-paths.create');
    Route::post('/learning-paths', [App\Http\Controllers\LearningPathController::class, 'store'])->name('learning-paths.store');
    Route::get('/learning-paths/{learningPath}', [App\Http\Controllers\LearningPathController::class, 'show'])->name('learning-paths.show');
    Route::post('/learning-paths/{learningPath}/start', [App\Http\Controllers\LearningPathController::class, 'start'])->name('learning-paths.start');
    Route::post('/learning-paths/{learningPath}/pause', [App\Http\Controllers\LearningPathController::class, 'pause'])->name('learning-paths.pause');
    Route::post('/learning-paths/{learningPath}/resume', [App\Http\Controllers\LearningPathController::class, 'resume'])->name('learning-paths.resume');
    Route::post('/learning-paths/{learningPath}/optimize', [App\Http\Controllers\LearningPathController::class, 'optimize'])->name('learning-paths.optimize');
    Route::post('/learning-paths/{learningPath}/adjust', [App\Http\Controllers\LearningPathController::class, 'adjust'])->name('learning-paths.adjust');
    Route::post('/learning-paths/{learningPath}/update-progress', [App\Http\Controllers\LearningPathController::class, 'updateProgress'])->name('learning-paths.update-progress');
    Route::delete('/learning-paths/{learningPath}', [App\Http\Controllers\LearningPathController::class, 'destroy'])->name('learning-paths.destroy');
    
    // Attempt routes
    Route::get('/assessments/{assessment}/start', [AttemptController::class, 'start'])->name('attempts.start');
    Route::post('/assessments/{assessment}/begin', [AttemptController::class, 'begin'])->name('attempts.begin');
    Route::get('/attempts/{attempt}/take', [AttemptController::class, 'take'])->name('attempts.take');
    Route::post('/attempts/{attempt}/questions/{question}/response', [AttemptController::class, 'saveResponse'])->name('attempts.save-response');
    Route::post('/attempts/{attempt}/submit', [AttemptController::class, 'submit'])->name('attempts.submit');
    Route::get('/attempts/{attempt}/results', [AttemptController::class, 'results'])->name('attempts.results');
    Route::get('/attempts/{attempt}/review', [AttemptController::class, 'review'])->name('attempts.review');
    Route::get('/attempts/{attempt}/remaining-time', [AttemptController::class, 'remainingTime'])->name('attempts.remaining-time');
    
    // Chatbot routes
    Route::post('/chatbot/message', [App\Http\Controllers\ChatbotController::class, 'sendMessage'])->name('chatbot.send-message');
    Route::get('/chatbot/conversations', [App\Http\Controllers\ChatbotController::class, 'getConversations'])->name('chatbot.conversations');
    Route::post('/chatbot/conversations/start', [App\Http\Controllers\ChatbotController::class, 'startConversation'])->name('chatbot.start-conversation');
    Route::get('/chatbot/conversations/{conversation}', [App\Http\Controllers\ChatbotController::class, 'getConversation'])->name('chatbot.get-conversation');
    Route::post('/chatbot/conversations/{conversation}/end', [App\Http\Controllers\ChatbotController::class, 'endConversation'])->name('chatbot.end-conversation');
    Route::post('/chatbot/conversations/{conversation}/rate', [App\Http\Controllers\ChatbotController::class, 'rateConversation'])->name('chatbot.rate-conversation');
    Route::delete('/chatbot/conversations/{conversation}', [App\Http\Controllers\ChatbotController::class, 'deleteConversation'])->name('chatbot.delete-conversation');
    
    // Certificate routes
    Route::get('/certificates', [CertificateController::class, 'index'])->name('certificates.index');
    Route::get('/certificates/{certificate}', [CertificateController::class, 'show'])->name('certificates.show');
    Route::post('/enrollments/{enrollment}/certificate', [CertificateController::class, 'generate'])->name('certificates.generate');
    Route::get('/certificates/{certificate}/download', [CertificateController::class, 'download'])->name('certificates.download');
    Route::post('/certificates/{certificate}/email', [CertificateController::class, 'email'])->name('certificates.email');
});

// Certificate verification routes (public)
Route::get('/verify-certificate', [CertificateController::class, 'verify'])->name('certificates.verify');
Route::post('/verify-certificate', [CertificateController::class, 'verifyPost'])->name('certificates.verify.post');
Route::get('/verify-certificate/{certificateNumber}', [CertificateController::class, 'verify'])->name('certificates.verify.number');

// Admin routes (accessible by admin, super_admin, and instructor)
Route::middleware(['auth', 'role:admin,super_admin,instructor'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('users', UserController::class);
    Route::resource('categories', CategoryController::class);
    Route::post('categories/reorder', [CategoryController::class, 'reorder'])->name('categories.reorder');
    
    // Course routes
    Route::resource('courses', CourseController::class);
    Route::get('courses/{course}/builder', [CourseController::class, 'builder'])->name('courses.builder');
    Route::put('courses/{course}/publish', [CourseController::class, 'publish'])->name('courses.publish');
    Route::put('courses/{course}/unpublish', [CourseController::class, 'unpublish'])->name('courses.unpublish');
    Route::post('courses/{course}/clone', [CourseController::class, 'clone'])->name('courses.clone');
    
    // Module routes
    Route::post('courses/{course}/modules', [ModuleController::class, 'store'])->name('modules.store');
    Route::get('modules/{module}/edit', [ModuleController::class, 'edit'])->name('modules.edit');
    Route::put('modules/{module}', [ModuleController::class, 'update'])->name('modules.update');
    Route::delete('modules/{module}', [ModuleController::class, 'destroy'])->name('modules.destroy');
    Route::post('courses/{course}/modules/reorder', [ModuleController::class, 'reorder'])->name('modules.reorder');
    
    // Lesson routes
    Route::post('modules/{module}/lessons', [LessonController::class, 'store'])->name('lessons.store');
    Route::get('lessons/{lesson}/edit', [LessonController::class, 'edit'])->name('lessons.edit');
    Route::put('lessons/{lesson}', [LessonController::class, 'update'])->name('lessons.update');
    Route::delete('lessons/{lesson}', [LessonController::class, 'destroy'])->name('lessons.destroy');
    Route::post('modules/{module}/lessons/reorder', [LessonController::class, 'reorder'])->name('lessons.reorder');
    Route::post('lessons/{lesson}/upload-content', [LessonController::class, 'uploadContent'])->name('lessons.upload-content');
    
    // File upload routes
    Route::post('upload/video', [FileUploadController::class, 'uploadVideo'])->name('upload.video');
    Route::post('upload/pdf', [FileUploadController::class, 'uploadPdf'])->name('upload.pdf');
    Route::post('upload/presentation', [FileUploadController::class, 'uploadPresentation'])->name('upload.presentation');
    Route::post('upload/scorm', [FileUploadController::class, 'uploadScorm'])->name('upload.scorm');
    Route::delete('upload/file', [FileUploadController::class, 'deleteFile'])->name('upload.delete');
    Route::get('upload/progress', [FileUploadController::class, 'getUploadProgress'])->name('upload.progress');
    
    // Admin enrollment routes
    Route::resource('enrollments', AdminEnrollmentController::class);
    Route::get('courses/{course}/enrollments', [AdminEnrollmentController::class, 'courseStats'])->name('enrollments.course-stats');
    
    // Admin assessment routes
    Route::resource('assessments', AdminAssessmentController::class);
    Route::put('assessments/{assessment}/publish', [AdminAssessmentController::class, 'publish'])->name('assessments.publish');
    Route::put('assessments/{assessment}/unpublish', [AdminAssessmentController::class, 'unpublish'])->name('assessments.unpublish');
    Route::post('assessments/{assessment}/clone', [AdminAssessmentController::class, 'clone'])->name('assessments.clone');
    Route::get('assessments/{assessment}/attempts', [AdminAssessmentController::class, 'attempts'])->name('assessments.attempts');
    
    // Admin question routes
    Route::get('assessments/{assessment}/questions/create', [QuestionController::class, 'create'])->name('questions.create');
    Route::post('assessments/{assessment}/questions', [QuestionController::class, 'store'])->name('questions.store');
    Route::get('assessments/{assessment}/questions/{question}/edit', [QuestionController::class, 'edit'])->name('questions.edit');
    Route::put('assessments/{assessment}/questions/{question}', [QuestionController::class, 'update'])->name('questions.update');
    Route::delete('assessments/{assessment}/questions/{question}', [QuestionController::class, 'destroy'])->name('questions.destroy');
    Route::post('assessments/{assessment}/questions/reorder', [QuestionController::class, 'reorder'])->name('questions.reorder');
    Route::post('assessments/{assessment}/questions/{question}/clone', [QuestionController::class, 'clone'])->name('questions.clone');
    Route::post('assessments/{assessment}/questions/bulk-import', [QuestionController::class, 'bulkImport'])->name('questions.bulk-import');
    Route::get('questions/bank', [QuestionController::class, 'questionBank'])->name('questions.bank');
    Route::get('assessments/{assessment}/questions/{question}/statistics', [QuestionController::class, 'statistics'])->name('questions.statistics');
    
    // Admin grading routes
    Route::get('attempts/{attempt}/grade', [AttemptController::class, 'grade'])->name('attempts.grade');
    Route::post('attempts/{attempt}/save-grade', [AttemptController::class, 'saveGrade'])->name('attempts.save-grade');
    
    // AI Grading Review routes
    Route::get('grading/review', [\App\Http\Controllers\Admin\GradingReviewController::class, 'index'])->name('grading.review.index');
    Route::get('grading/review/{gradingResult}', [\App\Http\Controllers\Admin\GradingReviewController::class, 'show'])->name('grading.review.show');
    Route::put('grading/review/{gradingResult}', [\App\Http\Controllers\Admin\GradingReviewController::class, 'update'])->name('grading.review.update');
    Route::post('grading/review/{gradingResult}/accept', [\App\Http\Controllers\Admin\GradingReviewController::class, 'accept'])->name('grading.review.accept');
    Route::post('grading/review/batch', [\App\Http\Controllers\Admin\GradingReviewController::class, 'batchReview'])->name('grading.review.batch');
    Route::get('grading/statistics', [\App\Http\Controllers\Admin\GradingReviewController::class, 'statistics'])->name('grading.statistics');
    
    // AI Question Generator routes
    Route::get('questions/generator/create', [\App\Http\Controllers\Admin\QuestionGeneratorController::class, 'create'])->name('questions.generator.create');
    Route::post('questions/generator/generate', [\App\Http\Controllers\Admin\QuestionGeneratorController::class, 'generate'])->name('questions.generator.generate');
    Route::get('questions/generator/{job}/status', [\App\Http\Controllers\Admin\QuestionGeneratorController::class, 'status'])->name('questions.generator.status');
    Route::get('questions/generator/{job}/review', [\App\Http\Controllers\Admin\QuestionGeneratorController::class, 'review'])->name('questions.generator.review');
    Route::get('questions/generator/{generatedQuestion}/edit', [\App\Http\Controllers\Admin\QuestionGeneratorController::class, 'edit'])->name('questions.generator.edit');
    Route::put('questions/generator/{generatedQuestion}', [\App\Http\Controllers\Admin\QuestionGeneratorController::class, 'update'])->name('questions.generator.update');
    Route::post('questions/generator/{generatedQuestion}/approve', [\App\Http\Controllers\Admin\QuestionGeneratorController::class, 'approve'])->name('questions.generator.approve');
    Route::post('questions/generator/{generatedQuestion}/reject', [\App\Http\Controllers\Admin\QuestionGeneratorController::class, 'reject'])->name('questions.generator.reject');
    Route::post('questions/generator/bulk-approve', [\App\Http\Controllers\Admin\QuestionGeneratorController::class, 'bulkApprove'])->name('questions.generator.bulk-approve');
    Route::get('questions/generator/assessments', [\App\Http\Controllers\Admin\QuestionGeneratorController::class, 'getAssessments'])->name('questions.generator.assessments');
    
    // Content Analyzer routes
    Route::get('courses/{course}/analyze', [\App\Http\Controllers\Admin\ContentAnalyzerController::class, 'show'])->name('content-analyzer.show');
    Route::post('courses/{course}/analyze', [\App\Http\Controllers\Admin\ContentAnalyzerController::class, 'analyze'])->name('content-analyzer.analyze');
    Route::post('courses/{course}/re-analyze', [\App\Http\Controllers\Admin\ContentAnalyzerController::class, 'reAnalyze'])->name('content-analyzer.re-analyze');
    Route::get('courses/{course}/analyze/history', [\App\Http\Controllers\Admin\ContentAnalyzerController::class, 'history'])->name('content-analyzer.history');
    
    // Admin Certificate routes
    Route::get('certificates', [AdminCertificateController::class, 'index'])->name('certificates.index');
    Route::get('certificates/{certificate}', [AdminCertificateController::class, 'show'])->name('certificates.show');
    Route::post('enrollments/{enrollment}/certificate', [AdminCertificateController::class, 'generate'])->name('certificates.generate');
    Route::post('certificates/bulk-generate', [AdminCertificateController::class, 'bulkGenerate'])->name('certificates.bulk-generate');
    Route::post('certificates/{certificate}/regenerate', [AdminCertificateController::class, 'regenerate'])->name('certificates.regenerate');
    Route::post('certificates/{certificate}/revoke', [AdminCertificateController::class, 'revoke'])->name('certificates.revoke');
    Route::post('certificates/{certificate}/restore', [AdminCertificateController::class, 'restore'])->name('certificates.restore');
    Route::post('certificates/{certificate}/email', [AdminCertificateController::class, 'sendEmail'])->name('certificates.send-email');
    Route::get('certificates/expiring/list', [AdminCertificateController::class, 'expiring'])->name('certificates.expiring');
    
    // Admin Certificate Template routes
    Route::resource('certificate-templates', \App\Http\Controllers\Admin\CertificateTemplateController::class);
    Route::post('certificate-templates/{certificateTemplate}/set-default', [\App\Http\Controllers\Admin\CertificateTemplateController::class, 'setDefault'])->name('certificate-templates.set-default');
    Route::get('certificate-templates/{certificateTemplate}/preview', [\App\Http\Controllers\Admin\CertificateTemplateController::class, 'preview'])->name('certificate-templates.preview');
    Route::post('certificate-templates/{certificateTemplate}/clone', [\App\Http\Controllers\Admin\CertificateTemplateController::class, 'clone'])->name('certificate-templates.clone');
    
    // Analytics routes
    Route::get('analytics', [\App\Http\Controllers\Admin\AnalyticsController::class, 'index'])->name('analytics.index');
    Route::get('analytics/dashboard-data', [\App\Http\Controllers\Admin\AnalyticsController::class, 'getDashboardData'])->name('analytics.dashboard-data');
    Route::get('analytics/users/{user}', [\App\Http\Controllers\Admin\AnalyticsController::class, 'getUserAnalytics'])->name('analytics.user');
    Route::get('analytics/courses/{course}', [\App\Http\Controllers\Admin\AnalyticsController::class, 'getCourseAnalytics'])->name('analytics.course');
    Route::get('analytics/departments/{department}', [\App\Http\Controllers\Admin\AnalyticsController::class, 'getDepartmentAnalytics'])->name('analytics.department');
    Route::post('analytics/track-event', [\App\Http\Controllers\Admin\AnalyticsController::class, 'trackEvent'])->name('analytics.track-event');
    Route::get('analytics/enrollment-trends', [\App\Http\Controllers\Admin\AnalyticsController::class, 'getEnrollmentTrends'])->name('analytics.enrollment-trends');
    Route::get('analytics/completion-trends', [\App\Http\Controllers\Admin\AnalyticsController::class, 'getCompletionTrends'])->name('analytics.completion-trends');
    Route::get('analytics/activity-heatmap', [\App\Http\Controllers\Admin\AnalyticsController::class, 'getActivityHeatmap'])->name('analytics.activity-heatmap');
    Route::post('analytics/clear-cache', [\App\Http\Controllers\Admin\AnalyticsController::class, 'clearCache'])->name('analytics.clear-cache');
    
    // Report routes
    Route::get('reports', [\App\Http\Controllers\Admin\ReportController::class, 'index'])->name('reports.index');
    Route::get('reports/create', [\App\Http\Controllers\Admin\ReportController::class, 'create'])->name('reports.create');
    Route::post('reports', [\App\Http\Controllers\Admin\ReportController::class, 'store'])->name('reports.store');
    Route::get('reports/{report}', [\App\Http\Controllers\Admin\ReportController::class, 'show'])->name('reports.show');
    Route::delete('reports/{report}', [\App\Http\Controllers\Admin\ReportController::class, 'destroy'])->name('reports.destroy');
    Route::get('reports/{report}/download', [\App\Http\Controllers\Admin\ReportController::class, 'download'])->name('reports.download');
    Route::post('reports/schedule', [\App\Http\Controllers\Admin\ReportController::class, 'schedule'])->name('reports.schedule');
    Route::get('reports/scheduled/list', [\App\Http\Controllers\Admin\ReportController::class, 'scheduled'])->name('reports.scheduled');
    Route::put('reports/{report}/schedule', [\App\Http\Controllers\Admin\ReportController::class, 'updateSchedule'])->name('reports.update-schedule');
    Route::post('reports/{report}/disable-schedule', [\App\Http\Controllers\Admin\ReportController::class, 'disableSchedule'])->name('reports.disable-schedule');
    Route::post('reports/{report}/enable-schedule', [\App\Http\Controllers\Admin\ReportController::class, 'enableSchedule'])->name('reports.enable-schedule');
    Route::post('reports/preview', [\App\Http\Controllers\Admin\ReportController::class, 'preview'])->name('reports.preview');
    
    // Audit Log routes
    Route::get('audit-logs', [\App\Http\Controllers\Admin\AuditLogController::class, 'index'])->name('audit-logs.index');
    Route::get('audit-logs/{id}', [\App\Http\Controllers\Admin\AuditLogController::class, 'show'])->name('audit-logs.show');
    Route::get('audit-logs/export/csv', [\App\Http\Controllers\Admin\AuditLogController::class, 'export'])->name('audit-logs.export');
    Route::get('audit-logs/user/{userId}', [\App\Http\Controllers\Admin\AuditLogController::class, 'userLogs'])->name('audit-logs.user');
    Route::get('audit-logs/model/logs', [\App\Http\Controllers\Admin\AuditLogController::class, 'modelLogs'])->name('audit-logs.model');
});

require __DIR__.'/auth.php';
