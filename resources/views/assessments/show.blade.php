<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ $assessment->title }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <!-- Assessment Info -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">About This Assessment</h3>
                    
                    @if($assessment->description)
                        <p class="text-gray-700 dark:text-gray-300 mb-4">{{ $assessment->description }}</p>
                    @endif

                    <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Course</dt>
                            <dd class="text-sm text-gray-900 dark:text-gray-100">{{ $assessment->course->title }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Questions</dt>
                            <dd class="text-sm text-gray-900 dark:text-gray-100">{{ $assessment->getTotalQuestions() }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Passing Score</dt>
                            <dd class="text-sm text-gray-900 dark:text-gray-100">{{ $assessment->passing_score }}%</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Time Limit</dt>
                            <dd class="text-sm text-gray-900 dark:text-gray-100">
                                {{ $assessment->time_limit > 0 ? $assessment->time_limit . ' minutes' : 'Unlimited' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Attempts Allowed</dt>
                            <dd class="text-sm text-gray-900 dark:text-gray-100">
                                {{ $assessment->max_attempts > 0 ? $assessment->max_attempts : 'Unlimited' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Remaining Attempts</dt>
                            <dd class="text-sm text-gray-900 dark:text-gray-100">
                                {{ $statistics['remaining_attempts'] ?? 'Unlimited' }}
                            </dd>
                        </div>
                    </dl>

                    @if($canAttempt)
                        <div class="mt-6">
                            <a href="{{ route('attempts.start', $assessment) }}" 
                                class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg inline-block">
                                Start Assessment
                            </a>
                        </div>
                    @else
                        <div class="mt-6 p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
                            <p class="text-yellow-800 dark:text-yellow-200">
                                You have reached the maximum number of attempts for this assessment.
                            </p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Previous Attempts -->
            @if($attempts->count() > 0)
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Your Attempts</h3>
                        
                        <div class="space-y-3">
                            @foreach($attempts as $attempt)
                                <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                                    <div class="flex justify-between items-center">
                                        <div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                                {{ $attempt->started_at->format('M d, Y H:i') }}
                                            </div>
                                            @if($attempt->status === 'graded')
                                                <div class="text-lg font-semibold {{ $attempt->passed ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                                    {{ number_format($attempt->percentage, 1) }}%
                                                    @if($attempt->passed)
                                                        (Passed)
                                                    @else
                                                        (Failed)
                                                    @endif
                                                </div>
                                            @elseif($attempt->status === 'in_progress')
                                                <div class="text-sm text-yellow-600 dark:text-yellow-400">In Progress</div>
                                            @else
                                                <div class="text-sm text-blue-600 dark:text-blue-400">Awaiting Grading</div>
                                            @endif
                                        </div>
                                        <div>
                                            @if($attempt->status === 'in_progress')
                                                <a href="{{ route('attempts.take', $attempt) }}" 
                                                    class="text-blue-600 hover:text-blue-900 dark:text-blue-400">
                                                    Continue
                                                </a>
                                            @elseif($attempt->status === 'graded' && $assessment->allowsReview())
                                                <a href="{{ route('attempts.review', $attempt) }}" 
                                                    class="text-blue-600 hover:text-blue-900 dark:text-blue-400">
                                                    Review
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
