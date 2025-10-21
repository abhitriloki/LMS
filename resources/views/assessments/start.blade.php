<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Start Assessment: {{ $assessment->title }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Instructions</h3>
                    
                    @if($assessment->instructions)
                        <div class="prose dark:prose-invert mb-6">
                            {!! nl2br(e($assessment->instructions)) !!}
                        </div>
                    @endif

                    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4 mb-6">
                        <h4 class="font-semibold text-blue-900 dark:text-blue-100 mb-2">Assessment Details:</h4>
                        <ul class="list-disc list-inside space-y-1 text-blue-800 dark:text-blue-200">
                            <li>Total Questions: {{ $assessment->getTotalQuestions() }}</li>
                            <li>Total Points: {{ $assessment->getTotalPoints() }}</li>
                            <li>Passing Score: {{ $assessment->passing_score }}%</li>
                            @if($assessment->time_limit > 0)
                                <li>Time Limit: {{ $assessment->time_limit }} minutes</li>
                            @else
                                <li>Time Limit: Unlimited</li>
                            @endif
                            @if($assessment->max_attempts > 0)
                                <li>Remaining Attempts: {{ $statistics['remaining_attempts'] }}</li>
                            @endif
                        </ul>
                    </div>

                    @if($statistics['best_score'] > 0)
                        <div class="bg-gray-50 dark:bg-gray-900/50 border border-gray-200 dark:border-gray-700 rounded-lg p-4 mb-6">
                            <h4 class="font-semibold text-gray-900 dark:text-gray-100 mb-2">Your Statistics:</h4>
                            <ul class="list-disc list-inside space-y-1 text-gray-700 dark:text-gray-300">
                                <li>Previous Attempts: {{ $statistics['completed_attempts'] }}</li>
                                <li>Best Score: {{ $statistics['best_score'] }}%</li>
                                <li>Average Score: {{ $statistics['average_score'] }}%</li>
                            </ul>
                        </div>
                    @endif

                    <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4 mb-6">
                        <h4 class="font-semibold text-yellow-900 dark:text-yellow-100 mb-2">Important Notes:</h4>
                        <ul class="list-disc list-inside space-y-1 text-yellow-800 dark:text-yellow-200">
                            <li>Once you start, the timer will begin (if applicable)</li>
                            <li>Your progress will be saved automatically</li>
                            <li>Make sure you have a stable internet connection</li>
                            @if($assessment->time_limit > 0)
                                <li>The assessment will auto-submit when time expires</li>
                            @endif
                        </ul>
                    </div>

                    <div class="flex justify-between items-center">
                        <a href="{{ route('assessments.show', $assessment) }}" 
                            class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200">
                            ← Back
                        </a>
                        <form method="POST" action="{{ route('attempts.begin', $assessment) }}">
                            @csrf
                            <button type="submit" 
                                class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-semibold">
                                Begin Assessment
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
