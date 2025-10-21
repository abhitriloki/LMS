<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Assessment Results
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <!-- Results Summary -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="text-center mb-6">
                        @if($attempt->status === 'submitted')
                            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-blue-100 dark:bg-blue-900 mb-4">
                                <svg class="w-8 h-8 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-2">
                                Awaiting Grading
                            </h3>
                            <p class="text-gray-600 dark:text-gray-400">
                                Your assessment has been submitted and is awaiting manual grading.
                            </p>
                        @elseif($attempt->passed)
                            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-green-100 dark:bg-green-900 mb-4">
                                <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                            <h3 class="text-2xl font-bold text-green-600 dark:text-green-400 mb-2">
                                Congratulations! You Passed
                            </h3>
                            <p class="text-4xl font-bold text-gray-900 dark:text-gray-100 mb-2">
                                {{ number_format($results['percentage'], 1) }}%
                            </p>
                            <p class="text-gray-600 dark:text-gray-400">
                                {{ $results['score'] }} out of {{ $attempt->assessment->getTotalPoints() }} points
                            </p>
                        @else
                            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-red-100 dark:bg-red-900 mb-4">
                                <svg class="w-8 h-8 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </div>
                            <h3 class="text-2xl font-bold text-red-600 dark:text-red-400 mb-2">
                                Not Passed
                            </h3>
                            <p class="text-4xl font-bold text-gray-900 dark:text-gray-100 mb-2">
                                {{ number_format($results['percentage'], 1) }}%
                            </p>
                            <p class="text-gray-600 dark:text-gray-400">
                                {{ $results['score'] }} out of {{ $attempt->assessment->getTotalPoints() }} points
                            </p>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                                Passing score: {{ $attempt->assessment->passing_score }}%
                            </p>
                        @endif
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6">
                        <div class="text-center p-4 bg-gray-50 dark:bg-gray-900/50 rounded-lg">
                            <div class="text-sm text-gray-500 dark:text-gray-400">Time Taken</div>
                            <div class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                {{ gmdate('H:i:s', $results['time_taken'] ?? 0) }}
                            </div>
                        </div>
                        <div class="text-center p-4 bg-gray-50 dark:bg-gray-900/50 rounded-lg">
                            <div class="text-sm text-gray-500 dark:text-gray-400">Submitted</div>
                            <div class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                {{ $results['submitted_at']->format('M d, Y H:i') }}
                            </div>
                        </div>
                        <div class="text-center p-4 bg-gray-50 dark:bg-gray-900/50 rounded-lg">
                            <div class="text-sm text-gray-500 dark:text-gray-400">Status</div>
                            <div class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                {{ ucfirst($results['status']) }}
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-center space-x-3">
                        <a href="{{ route('assessments.show', $attempt->assessment) }}" 
                            class="bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 px-6 py-2 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600">
                            Back to Assessment
                        </a>
                        @if($results['allow_review'] && $attempt->status === 'graded')
                            <a href="{{ route('attempts.review', $attempt) }}" 
                                class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg">
                                Review Answers
                            </a>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Quick Summary (if graded) -->
            @if(isset($results['responses']) && $attempt->status === 'graded')
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Summary</h3>
                        
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div class="text-center p-4 bg-green-50 dark:bg-green-900/20 rounded-lg">
                                <div class="text-2xl font-bold text-green-600 dark:text-green-400">
                                    {{ collect($results['responses'])->where('is_correct', true)->count() }}
                                </div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">Correct</div>
                            </div>
                            <div class="text-center p-4 bg-red-50 dark:bg-red-900/20 rounded-lg">
                                <div class="text-2xl font-bold text-red-600 dark:text-red-400">
                                    {{ collect($results['responses'])->where('is_correct', false)->count() }}
                                </div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">Incorrect</div>
                            </div>
                            <div class="text-center p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                                <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                                    {{ count($results['responses']) }}
                                </div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">Total Questions</div>
                            </div>
                            <div class="text-center p-4 bg-purple-50 dark:bg-purple-900/20 rounded-lg">
                                <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">
                                    {{ number_format((collect($results['responses'])->where('is_correct', true)->count() / count($results['responses'])) * 100, 0) }}%
                                </div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">Accuracy</div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
