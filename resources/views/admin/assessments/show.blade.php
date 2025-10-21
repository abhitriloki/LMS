<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ $assessment->title }}
            </h2>
            <div class="flex space-x-2">
                <a href="{{ route('admin.assessments.edit', $assessment) }}" 
                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
                    Edit
                </a>
                <a href="{{ route('admin.assessments.index') }}" 
                    class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200">
                    ← Back
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <!-- Assessment Info -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Assessment Details</h3>
                            <dl class="space-y-2">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Course</dt>
                                    <dd class="text-sm text-gray-900 dark:text-gray-100">{{ $assessment->course->title }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</dt>
                                    <dd class="text-sm">
                                        @if($assessment->is_published)
                                            <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">
                                                Published
                                            </span>
                                        @else
                                            <span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                                Draft
                                            </span>
                                        @endif
                                    </dd>
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
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Max Attempts</dt>
                                    <dd class="text-sm text-gray-900 dark:text-gray-100">
                                        {{ $assessment->max_attempts > 0 ? $assessment->max_attempts : 'Unlimited' }}
                                    </dd>
                                </div>
                            </dl>
                        </div>

                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Statistics</h3>
                            <dl class="space-y-2">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Questions</dt>
                                    <dd class="text-sm text-gray-900 dark:text-gray-100">{{ $assessment->getTotalQuestions() }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Points</dt>
                                    <dd class="text-sm text-gray-900 dark:text-gray-100">{{ $assessment->getTotalPoints() }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Attempts</dt>
                                    <dd class="text-sm text-gray-900 dark:text-gray-100">{{ $statistics['total_attempts'] }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Average Score</dt>
                                    <dd class="text-sm text-gray-900 dark:text-gray-100">{{ $statistics['average_score'] }}%</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Pass Rate</dt>
                                    <dd class="text-sm text-gray-900 dark:text-gray-100">{{ $statistics['pass_rate'] }}%</dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    <div class="mt-6 flex space-x-3">
                        @if(!$assessment->is_published)
                            <form method="POST" action="{{ route('admin.assessments.publish', $assessment) }}">
                                @csrf
                                @method('PUT')
                                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg">
                                    Publish
                                </button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('admin.assessments.unpublish', $assessment) }}">
                                @csrf
                                @method('PUT')
                                <button type="submit" class="bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded-lg">
                                    Unpublish
                                </button>
                            </form>
                        @endif

                        <form method="POST" action="{{ route('admin.assessments.clone', $assessment) }}">
                            @csrf
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
                                Clone
                            </button>
                        </form>

                        <a href="{{ route('admin.assessments.attempts', $assessment) }}" 
                            class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg">
                            View Attempts
                        </a>
                    </div>
                </div>
            </div>

            <!-- Questions -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Questions</h3>
                        <a href="{{ route('admin.questions.create', $assessment) }}" 
                            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
                            Add Question
                        </a>
                    </div>

                    @if($assessment->questions->count() > 0)
                        <div class="space-y-4">
                            @foreach($assessment->questions as $question)
                                <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                                    <div class="flex justify-between items-start">
                                        <div class="flex-1">
                                            <div class="flex items-center space-x-2 mb-2">
                                                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                                    Q{{ $question->order_index }}
                                                </span>
                                                <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100">
                                                    {{ ucfirst(str_replace('_', ' ', $question->question_type)) }}
                                                </span>
                                                <span class="text-sm text-gray-500 dark:text-gray-400">
                                                    {{ $question->points }} points
                                                </span>
                                            </div>
                                            <p class="text-gray-900 dark:text-gray-100 mb-2">{{ $question->question_text }}</p>
                                            
                                            @if($question->options->count() > 0)
                                                <ul class="ml-4 space-y-1">
                                                    @foreach($question->options as $option)
                                                        <li class="text-sm {{ $option->is_correct ? 'text-green-600 dark:text-green-400 font-medium' : 'text-gray-600 dark:text-gray-400' }}">
                                                            {{ $option->is_correct ? '✓' : '○' }} {{ $option->option_text }}
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            @endif
                                        </div>
                                        <div class="flex space-x-2 ml-4">
                                            <a href="{{ route('admin.questions.edit', [$assessment, $question]) }}" 
                                                class="text-blue-600 hover:text-blue-900 dark:text-blue-400">
                                                Edit
                                            </a>
                                            <form method="POST" action="{{ route('admin.questions.destroy', [$assessment, $question]) }}" 
                                                onsubmit="return confirm('Are you sure you want to delete this question?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-900 dark:text-red-400">
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-gray-500 dark:text-gray-400 text-center py-8">
                            No questions added yet. Click "Add Question" to get started.
                        </p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
