<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\Question;
use App\Services\QuestionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class QuestionController extends Controller
{
    public function __construct(
        protected QuestionService $questionService
    ) {}

    /**
     * Show the form for creating a new question
     */
    public function create(Assessment $assessment)
    {
        Gate::authorize('update', $assessment);

        return view('admin.questions.create', compact('assessment'));
    }

    /**
     * Store a newly created question
     */
    public function store(Request $request, Assessment $assessment)
    {
        Gate::authorize('update', $assessment);

        $validated = $request->validate([
            'question_text' => 'required|string',
            'question_type' => 'required|in:multiple_choice,true_false,fill_in_blank,essay,matching,drag_drop',
            'points' => 'required|numeric|min:0',
            'explanation' => 'nullable|string',
            'options' => 'nullable|array',
            'options.*.option_text' => 'required|string',
            'options.*.is_correct' => 'boolean',
            'options.*.explanation' => 'nullable|string',
        ]);

        $question = $this->questionService->createQuestion($assessment, $validated);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'question' => $question->load('options'),
            ]);
        }

        return redirect()
            ->route('admin.assessments.show', $assessment)
            ->with('success', 'Question created successfully');
    }

    /**
     * Show the form for editing a question
     */
    public function edit(Assessment $assessment, Question $question)
    {
        Gate::authorize('update', $assessment);

        $question->load('options');

        return view('admin.questions.edit', compact('assessment', 'question'));
    }

    /**
     * Update the specified question
     */
    public function update(Request $request, Assessment $assessment, Question $question)
    {
        Gate::authorize('update', $assessment);

        $validated = $request->validate([
            'question_text' => 'required|string',
            'question_type' => 'required|in:multiple_choice,true_false,fill_in_blank,essay,matching,drag_drop',
            'points' => 'required|numeric|min:0',
            'explanation' => 'nullable|string',
            'options' => 'nullable|array',
            'options.*.option_text' => 'required|string',
            'options.*.is_correct' => 'boolean',
            'options.*.explanation' => 'nullable|string',
        ]);

        $question = $this->questionService->updateQuestion($question, $validated);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'question' => $question->load('options'),
            ]);
        }

        return redirect()
            ->route('admin.assessments.show', $assessment)
            ->with('success', 'Question updated successfully');
    }

    /**
     * Remove the specified question
     */
    public function destroy(Assessment $assessment, Question $question)
    {
        Gate::authorize('update', $assessment);

        $this->questionService->deleteQuestion($question);

        if (request()->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Question deleted successfully');
    }

    /**
     * Reorder questions
     */
    public function reorder(Request $request, Assessment $assessment)
    {
        Gate::authorize('update', $assessment);

        $validated = $request->validate([
            'question_ids' => 'required|array',
            'question_ids.*' => 'exists:questions,id',
        ]);

        $this->questionService->reorderQuestions($assessment, $validated['question_ids']);

        return response()->json(['success' => true]);
    }

    /**
     * Clone a question
     */
    public function clone(Assessment $assessment, Question $question)
    {
        Gate::authorize('update', $assessment);

        $newQuestion = $this->questionService->cloneQuestion($question, $assessment);

        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'question' => $newQuestion->load('options'),
            ]);
        }

        return back()->with('success', 'Question cloned successfully');
    }

    /**
     * Bulk import questions
     */
    public function bulkImport(Request $request, Assessment $assessment)
    {
        Gate::authorize('update', $assessment);

        $validated = $request->validate([
            'questions' => 'required|array',
            'questions.*.question_text' => 'required|string',
            'questions.*.question_type' => 'required|in:multiple_choice,true_false,fill_in_blank,essay,matching,drag_drop',
            'questions.*.points' => 'required|numeric|min:0',
            'questions.*.options' => 'nullable|array',
        ]);

        $questions = $this->questionService->bulkImportQuestions($assessment, $validated['questions']);

        return response()->json([
            'success' => true,
            'count' => $questions->count(),
        ]);
    }

    /**
     * Get question bank for a course
     */
    public function questionBank(Request $request)
    {
        $courseId = $request->input('course_id');
        
        if (!$courseId) {
            return response()->json(['error' => 'Course ID required'], 400);
        }

        $questions = $this->questionService->getQuestionBank($courseId);

        return response()->json([
            'success' => true,
            'questions' => $questions,
        ]);
    }

    /**
     * Get question statistics
     */
    public function statistics(Assessment $assessment, Question $question)
    {
        Gate::authorize('view', $assessment);

        $statistics = $this->questionService->getQuestionStatistics($question);

        return response()->json([
            'success' => true,
            'statistics' => $statistics,
        ]);
    }
}
