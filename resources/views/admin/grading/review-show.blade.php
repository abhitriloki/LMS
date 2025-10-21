<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Review AI Grading') }}
            </h2>
            <a href="{{ route('admin.grading.review.index') }}" class="btn btn-secondary">
                Back to Queue
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <!-- Student & Assessment Info -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Student Information</h3>
                            <dl class="space-y-2">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Name</dt>
                                    <dd class="text-sm text-gray-900 dark:text-gray-100">{{ $attempt->user->name }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Email</dt>
                                    <dd class="text-sm text-gray-900 dark:text-gray-100">{{ $attempt->user->email }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Submitted</dt>
                                    <dd class="text-sm text-gray-900 dark:text-gray-100">{{ $attempt->submitted_at->format('M d, Y H:i') }}</dd>
                                </div>
                            </dl>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Assessment Information</h3>
                            <dl class="space-y-2">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Assessment</dt>
                                    <dd class="text-sm text-gray-900 dark:text-gray-100">{{ $attempt->assessment->title }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Question Points</dt>
                                    <dd class="text-sm text-gray-900 dark:text-gray-100">{{ $question->points }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Current Attempt Score</dt>
                                    <dd class="text-sm text-gray-900 dark:text-gray-100">{{ $attempt->score }}/{{ $attempt->assessment->getTotalPoints() }} ({{ number_format($attempt->percentage, 1) }}%)</dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Question -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Question</h3>
                    <div class="prose dark:prose-invert max-w-none">
                        {!! nl2br(e($question->question_text)) !!}
                    </div>
                    @if($question->explanation)
                        <div class="mt-4 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                            <h4 class="text-sm font-semibold text-blue-900 dark:text-blue-100 mb-2">Expected Answer/Key Points:</h4>
                            <div class="text-sm text-blue-800 dark:text-blue-200">
                                {!! nl2br(e($question->explanation)) !!}
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Student Response -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Student Response</h3>
                    <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <div class="prose dark:prose-invert max-w-none">
                            {!! nl2br(e($response->response_data)) !!}
                        </div>
                    </div>
                </div>
            </div>

            <!-- AI Grading Results -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex justify-between items-start mb-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">AI Grading Results</h3>
                        <div class="flex items-center gap-4">
                            <span class="px-3 py-1 rounded-full text-sm font-medium
                                {{ $gradingResult->confidence_score < 0.5 ? 'bg-red-100 text-red-800' : 
                                   ($gradingResult->confidence_score < 0.7 ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800') }}">
                                Confidence: {{ number_format($gradingResult->confidence_score * 100, 0) }}%
                            </span>
                            <span class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                                {{ $gradingResult->ai_score }}/{{ $question->points }}
                            </span>
                        </div>
                    </div>

                    @if($gradingResult->review_reason)
                        <div class="mb-4 p-4 bg-orange-50 dark:bg-orange-900/20 rounded-lg">
                            <p class="text-sm font-medium text-orange-900 dark:text-orange-100">
                                Flagged Reason: {{ $gradingResult->review_reason }}
                            </p>
                        </div>
                    @endif

                    <div class="space-y-4">
                        <div>
                            <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">AI Feedback:</h4>
                            <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <div class="prose dark:prose-invert max-w-none text-sm">
                                    {!! nl2br(e($gradingResult->ai_feedback)) !!}
                                </div>
                            </div>
                        </div>

                        @if(!empty($gradingResult->rubric_scores))
                            <div>
                                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Rubric Scores:</h4>
                                <div class="grid grid-cols-2 gap-4">
                                    @foreach($gradingResult->rubric_scores as $criterion => $score)
                                        <div class="p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                            <div class="flex justify-between items-center">
                                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $criterion }}</span>
                                                <span class="text-sm font-bold text-gray-900 dark:text-gray-100">{{ $score }}</span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Override Form -->
            @if(!$gradingResult->hasBeenReviewed())
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Instructor Review</h3>
                        
                        <form method="POST" action="{{ route('admin.grading.review.update', $gradingResult) }}" class="space-y-4">
                            @csrf
                            @method('PUT')

                            <div>
                                <label for="human_score" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Override Score (Max: {{ $question->points }})
                                </label>
                                <input type="number" 
                                       name="human_score" 
                                       id="human_score" 
                                       step="0.01"
                                       min="0" 
                                       max="{{ $question->points }}"
                                       value="{{ old('human_score', $gradingResult->ai_score) }}"
                                       class="form-input w-full"
                                       required>
                                @error('human_score')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="human_feedback" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Instructor Feedback (Optional)
                                </label>
                                <textarea name="human_feedback" 
                                          id="human_feedback" 
                                          rows="6"
                                          class="form-textarea w-full"
                                          placeholder="Provide additional feedback or override AI feedback...">{{ old('human_feedback') }}</textarea>
                                @error('human_feedback')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="flex gap-4">
                                <button type="submit" class="btn btn-primary">
                                    Submit Review
                                </button>
                                <button type="button" 
                                        onclick="document.getElementById('human_score').value = '{{ $gradingResult->ai_score }}'; document.getElementById('human_feedback').value = '';"
                                        class="btn btn-secondary">
                                    Reset to AI Score
                                </button>
                            </div>
                        </form>

                        <div class="mt-4 pt-4 border-t dark:border-gray-700">
                            <form method="POST" action="{{ route('admin.grading.review.accept', $gradingResult) }}" class="inline">
                                @csrf
                                <button type="submit" 
                                        class="btn btn-success"
                                        onclick="return confirm('Accept AI grading without any changes?')">
                                    Accept AI Grading As-Is
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @else
                <div class="bg-green-50 dark:bg-green-900/20 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-green-900 dark:text-green-100 mb-4">
                            ✓ Already Reviewed
                        </h3>
                        <dl class="space-y-2">
                            <div>
                                <dt class="text-sm font-medium text-green-700 dark:text-green-300">Reviewed By</dt>
                                <dd class="text-sm text-green-900 dark:text-green-100">{{ $gradingResult->reviewedBy->name }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-green-700 dark:text-green-300">Reviewed At</dt>
                                <dd class="text-sm text-green-900 dark:text-green-100">{{ $gradingResult->reviewed_at->format('M d, Y H:i') }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-green-700 dark:text-green-300">Final Score</dt>
                                <dd class="text-sm text-green-900 dark:text-green-100">{{ $gradingResult->human_score }}/{{ $question->points }}</dd>
                            </div>
                            @if($gradingResult->human_feedback)
                                <div>
                                    <dt class="text-sm font-medium text-green-700 dark:text-green-300">Instructor Feedback</dt>
                                    <dd class="text-sm text-green-900 dark:text-green-100">{!! nl2br(e($gradingResult->human_feedback)) !!}</dd>
                                </div>
                            @endif
                        </dl>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
