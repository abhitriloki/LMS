<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Review: {{ $attempt->assessment->title }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <!-- Score Summary -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex justify-between items-center">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Your Score</h3>
                            <p class="text-3xl font-bold {{ $attempt->passed ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                {{ number_format($results['percentage'], 1) }}%
                            </p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                {{ $results['score'] }} / {{ $attempt->assessment->getTotalPoints() }} points
                            </p>
                        </div>
                        <div class="text-right">
                            <div class="text-sm text-gray-500 dark:text-gray-400">Submitted</div>
                            <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                {{ $results['submitted_at']->format('M d, Y H:i') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Questions Review -->
            @if(isset($results['responses']))
                @foreach($results['responses'] as $index => $response)
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-start justify-between mb-4">
                                <div class="flex-1">
                                    <div class="flex items-center space-x-2 mb-2">
                                        <span class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                            Question {{ $index + 1 }}
                                        </span>
                                        <span class="px-2 py-1 text-xs rounded-full bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200">
                                            {{ ucfirst(str_replace('_', ' ', $response['question_type'])) }}
                                        </span>
                                        <span class="text-sm text-gray-600 dark:text-gray-400">
                                            {{ $response['points'] }} points
                                        </span>
                                    </div>
                                    <h4 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                        {{ $response['question_text'] }}
                                    </h4>
                                </div>
                                <div class="ml-4">
                                    @if($response['is_correct'])
                                        <div class="flex items-center space-x-1 text-green-600 dark:text-green-400">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                            </svg>
                                            <span class="font-semibold">{{ $response['points_earned'] }}/{{ $response['points'] }}</span>
                                        </div>
                                    @else
                                        <div class="flex items-center space-x-1 text-red-600 dark:text-red-400">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                            </svg>
                                            <span class="font-semibold">{{ $response['points_earned'] }}/{{ $response['points'] }}</span>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Your Answer -->
                            <div class="mb-4">
                                <div class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Your Answer:</div>
                                <div class="p-3 bg-gray-50 dark:bg-gray-900/50 rounded-lg">
                                    @if(is_array($response['user_response']))
                                        @foreach($response['user_response'] as $answer)
                                            <div class="text-gray-900 dark:text-gray-100">{{ $answer }}</div>
                                        @endforeach
                                    @else
                                        <div class="text-gray-900 dark:text-gray-100">{{ $response['user_response'] ?? 'No answer provided' }}</div>
                                    @endif
                                </div>
                            </div>

                            <!-- Correct Answer (if configured to show) -->
                            @if($results['show_correct_answers'] && isset($response['correct_answer']))
                                <div class="mb-4">
                                    <div class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Correct Answer:</div>
                                    <div class="p-3 bg-green-50 dark:bg-green-900/20 rounded-lg">
                                        @if(is_array($response['correct_answer']))
                                            @foreach($response['correct_answer'] as $answer)
                                                <div class="text-green-800 dark:text-green-200">{{ $answer }}</div>
                                            @endforeach
                                        @else
                                            <div class="text-green-800 dark:text-green-200">{{ $response['correct_answer'] }}</div>
                                        @endif
                                    </div>
                                </div>
                            @endif

                            <!-- Explanation -->
                            @if($results['show_correct_answers'] && isset($response['explanation']) && $response['explanation'])
                                <div class="p-3 bg-blue-50 dark:bg-blue-900/20 border-l-4 border-blue-500 rounded">
                                    <div class="text-sm font-medium text-blue-800 dark:text-blue-200 mb-1">Explanation:</div>
                                    <div class="text-sm text-blue-700 dark:text-blue-300">{{ $response['explanation'] }}</div>
                                </div>
                            @endif

                            <!-- Feedback -->
                            @if(isset($response['feedback']) && $response['feedback'])
                                <div class="mt-3 p-3 bg-purple-50 dark:bg-purple-900/20 border-l-4 border-purple-500 rounded">
                                    <div class="text-sm font-medium text-purple-800 dark:text-purple-200 mb-1">Instructor Feedback:</div>
                                    <div class="text-sm text-purple-700 dark:text-purple-300">{{ $response['feedback'] }}</div>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            @endif

            <!-- Back Button -->
            <div class="flex justify-center">
                <a href="{{ route('assessments.show', $attempt->assessment) }}" 
                    class="bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 px-6 py-2 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600">
                    Back to Assessment
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
