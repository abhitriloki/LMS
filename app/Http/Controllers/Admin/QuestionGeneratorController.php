<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourseLesson;
use App\Models\AIQuestionJob;
use App\Models\GeneratedQuestion;
use App\Models\Assessment;
use App\Services\AI\AIQuestionGeneratorService;
use App\Services\QuestionService;
use App\Jobs\GenerateQuestionsJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class QuestionGeneratorController extends Controller
{
    protected AIQuestionGeneratorService $generatorService;
    protected QuestionService $questionService;

    public function __construct(
        AIQuestionGeneratorService $generatorService,
        QuestionService $questionService
    ) {
        $this->generatorService = $generatorService;
        $this->questionService = $questionService;
    }

    /**
     * Show question generation form
     */
    public function create(Request $request)
    {
        $lessonId = $request->query('lesson_id');
        $lesson = null;

        if ($lessonId) {
            $lesson = CourseLesson::with('module.course')->findOrFail($lessonId);
            
            // Check authorization
            Gate::authorize('update', $lesson->module->course);
        }

        return view('admin.questions.generate', [
            'lesson' => $lesson,
            'supportedTypes' => $this->generatorService->getSupportedTypes(),
            'difficultyLevels' => $this->generatorService->getDifficultyLevels(),
        ]);
    }

    /**
     * Trigger question generation
     */
    public function generate(Request $request)
    {
        $validated = $request->validate([
            'lesson_id' => 'required|exists:course_lessons,id',
            'count' => 'required|integer|min:1|max:20',
            'types' => 'required|array|min:1',
            'types.*' => 'string|in:multiple_choice,true_false,fill_in_blank,essay',
            'difficulty' => 'required|string|in:easy,medium,hard',
            'include_explanation' => 'boolean',
            'points_per_question' => 'required|numeric|min:0.5|max:10',
        ]);

        $lesson = CourseLesson::with('module.course')->findOrFail($validated['lesson_id']);
        
        // Check authorization
        Gate::authorize('update', $lesson->module->course);

        // Create job record
        $job = AIQuestionJob::create([
            'lesson_id' => $lesson->id,
            'requested_by' => auth()->id(),
            'status' => 'pending',
            'parameters' => [
                'count' => $validated['count'],
                'types' => $validated['types'],
                'difficulty' => $validated['difficulty'],
                'include_explanation' => $validated['include_explanation'] ?? true,
                'points_per_question' => $validated['points_per_question'],
            ],
        ]);

        // Dispatch job for async processing
        GenerateQuestionsJob::dispatch($job);

        return redirect()
            ->route('admin.questions.generator.status', $job)
            ->with('success', 'Question generation started. You will be notified when complete.');
    }

    /**
     * Show generation status
     */
    public function status(AIQuestionJob $job)
    {
        $job->load(['lesson.module.course', 'requestedBy', 'generatedQuestions']);

        // Check authorization
        Gate::authorize('update', $job->lesson->module->course);

        return view('admin.questions.generation-status', [
            'job' => $job,
        ]);
    }

    /**
     * Show generated questions for review
     */
    public function review(AIQuestionJob $job)
    {
        $job->load(['lesson.module.course', 'generatedQuestions' => function ($query) {
            $query->where('status', 'pending')->orderBy('id');
        }]);

        // Check authorization
        Gate::authorize('update', $job->lesson->module->course);

        if (!$job->isCompleted()) {
            return redirect()
                ->route('admin.questions.generator.status', $job)
                ->with('warning', 'Question generation is not yet complete.');
        }

        return view('admin.questions.review', [
            'job' => $job,
            'questions' => $job->generatedQuestions,
        ]);
    }

    /**
     * Show single generated question for editing
     */
    public function edit(GeneratedQuestion $generatedQuestion)
    {
        $generatedQuestion->load('job.lesson.module.course');

        // Check authorization
        Gate::authorize('update', $generatedQuestion->job->lesson->module->course);

        return view('admin.questions.edit-generated', [
            'generatedQuestion' => $generatedQuestion,
        ]);
    }

    /**
     * Update generated question
     */
    public function update(Request $request, GeneratedQuestion $generatedQuestion)
    {
        $generatedQuestion->load('job.lesson.module.course');

        // Check authorization
        Gate::authorize('update', $generatedQuestion->job->lesson->module->course);

        $validated = $request->validate([
            'question_text' => 'required|string',
            'question_type' => 'required|string|in:multiple_choice,true_false,fill_in_blank,essay',
            'options' => 'nullable|array',
            'correct_answer' => 'nullable',
            'explanation' => 'nullable|string',
            'points' => 'required|numeric|min:0.5|max:10',
        ]);

        $generatedQuestion->update($validated);

        return redirect()
            ->route('admin.questions.generator.review', $generatedQuestion->job)
            ->with('success', 'Question updated successfully.');
    }

    /**
     * Approve a generated question
     */
    public function approve(Request $request, GeneratedQuestion $generatedQuestion)
    {
        $generatedQuestion->load('job.lesson.module.course');

        // Check authorization
        Gate::authorize('update', $generatedQuestion->job->lesson->module->course);

        $validated = $request->validate([
            'assessment_id' => 'required|exists:assessments,id',
            'review_notes' => 'nullable|string',
        ]);

        $assessment = Assessment::findOrFail($validated['assessment_id']);

        // Check authorization for assessment
        Gate::authorize('update', $assessment);

        try {
            // Create actual question from generated question
            $questionData = [
                'question_text' => $generatedQuestion->question_text,
                'question_type' => $generatedQuestion->question_type,
                'points' => $generatedQuestion->points,
                'explanation' => $generatedQuestion->explanation,
            ];

            $question = $this->questionService->createQuestion($assessment, $questionData);

            // Add options if applicable
            if ($generatedQuestion->options && is_array($generatedQuestion->options)) {
                foreach ($generatedQuestion->options as $index => $optionText) {
                    $isCorrect = in_array($index, $generatedQuestion->correct_answer ?? []);
                    
                    $this->questionService->addOption($question, [
                        'option_text' => $optionText,
                        'is_correct' => $isCorrect,
                        'order_index' => $index,
                    ]);
                }
            }

            // Mark as approved and link to question
            $generatedQuestion->approve(auth()->user(), $validated['review_notes'] ?? null);
            $generatedQuestion->linkToQuestion($question);

            return response()->json([
                'success' => true,
                'message' => 'Question approved and added to assessment.',
                'question_id' => $question->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to approve generated question', [
                'generated_question_id' => $generatedQuestion->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to approve question: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reject a generated question
     */
    public function reject(Request $request, GeneratedQuestion $generatedQuestion)
    {
        $generatedQuestion->load('job.lesson.module.course');

        // Check authorization
        Gate::authorize('update', $generatedQuestion->job->lesson->module->course);

        $validated = $request->validate([
            'review_notes' => 'required|string',
        ]);

        $generatedQuestion->reject(auth()->user(), $validated['review_notes']);

        return response()->json([
            'success' => true,
            'message' => 'Question rejected.',
        ]);
    }

    /**
     * Bulk approve questions
     */
    public function bulkApprove(Request $request)
    {
        $validated = $request->validate([
            'question_ids' => 'required|array|min:1',
            'question_ids.*' => 'exists:generated_questions,id',
            'assessment_id' => 'required|exists:assessments,id',
        ]);

        $assessment = Assessment::findOrFail($validated['assessment_id']);
        Gate::authorize('update', $assessment);

        $approved = 0;
        $failed = 0;

        foreach ($validated['question_ids'] as $questionId) {
            try {
                $generatedQuestion = GeneratedQuestion::with('job.lesson.module.course')
                    ->findOrFail($questionId);

                // Check authorization
                Gate::authorize('update', $generatedQuestion->job->lesson->module->course);

                // Create actual question
                $questionData = [
                    'question_text' => $generatedQuestion->question_text,
                    'question_type' => $generatedQuestion->question_type,
                    'points' => $generatedQuestion->points,
                    'explanation' => $generatedQuestion->explanation,
                ];

                $question = $this->questionService->createQuestion($assessment, $questionData);

                // Add options if applicable
                if ($generatedQuestion->options && is_array($generatedQuestion->options)) {
                    foreach ($generatedQuestion->options as $index => $optionText) {
                        $isCorrect = in_array($index, $generatedQuestion->correct_answer ?? []);
                        
                        $this->questionService->addOption($question, [
                            'option_text' => $optionText,
                            'is_correct' => $isCorrect,
                            'order_index' => $index,
                        ]);
                    }
                }

                // Mark as approved
                $generatedQuestion->approve(auth()->user(), 'Bulk approved');
                $generatedQuestion->linkToQuestion($question);

                $approved++;
            } catch (\Exception $e) {
                Log::error('Failed to bulk approve question', [
                    'question_id' => $questionId,
                    'error' => $e->getMessage()
                ]);
                $failed++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "{$approved} questions approved successfully." . ($failed > 0 ? " {$failed} failed." : ''),
            'approved' => $approved,
            'failed' => $failed,
        ]);
    }

    /**
     * Get available assessments for a course
     */
    public function getAssessments(Request $request)
    {
        $validated = $request->validate([
            'lesson_id' => 'required|exists:course_lessons,id',
        ]);

        $lesson = CourseLesson::with('module.course.assessments')->findOrFail($validated['lesson_id']);
        
        // Check authorization
        Gate::authorize('view', $lesson->module->course);

        $assessments = $lesson->module->course->assessments()
            ->select('id', 'title', 'is_published')
            ->get();

        return response()->json([
            'success' => true,
            'assessments' => $assessments,
        ]);
    }
}
