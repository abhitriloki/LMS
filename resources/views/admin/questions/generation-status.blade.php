<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Question Generation Status') }}
            </h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <!-- Lesson Info -->
                    <div class="mb-6 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <h3 class="font-semibold text-lg text-gray-900 dark:text-gray-100 mb-2">
                            {{ $job->lesson->title }}
                        </h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            Course: {{ $job->lesson->module->course->title }}
                        </p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            Requested by: {{ $job->requestedBy->name }} on {{ $job->created_at->format('M d, Y H:i') }}
                        </p>
                    </div>

                    <!-- Status -->
                    <div class="mb-6">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Status</span>
                            <span class="px-3 py-1 rounded-full text-sm font-medium
                                @if($job->isPending()) bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300
                                @elseif($job->isProcessing()) bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300
                                @elseif($job->isCompleted()) bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300
                                @elseif($job->hasFailed()) bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300
                                @endif">
                                {{ ucfirst($job->status) }}
                            </span>
                        </div>

                        @if($job->isProcessing() || $job->isPending())
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5">
                                <div class="bg-primary-600 h-2.5 rounded-full animate-pulse" style="width: 45%"></div>
                            </div>
                            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                                Generating questions... This may take a few moments.
                            </p>
                        @endif

                        @if($job->hasFailed())
                            <div class="mt-4 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                                <p class="text-sm text-red-800 dark:text-red-200 font-medium">Error:</p>
                                <p class="text-sm text-red-700 dark:text-red-300 mt-1">{{ $job->error_message }}</p>
                            </div>
                        @endif
                    </div>

                    <!-- Parameters -->
                    <div class="mb-6">
                        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Generation Parameters</h4>
                        <dl class="grid grid-cols-2 gap-4">
                            <div>
                                <dt class="text-sm text-gray-500 dark:text-gray-400">Questions Count</dt>
                                <dd class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                    {{ $job->parameters['count'] ?? 'N/A' }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm text-gray-500 dark:text-gray-400">Difficulty</dt>
                                <dd class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                    {{ ucfirst($job->parameters['difficulty'] ?? 'N/A') }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm text-gray-500 dark:text-gray-400">Question Types</dt>
                                <dd class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                    {{ implode(', ', array_map('ucfirst', $job->parameters['types'] ?? [])) }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm text-gray-500 dark:text-gray-400">Points per Question</dt>
                                <dd class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                    {{ $job->parameters['points_per_question'] ?? 'N/A' }}
                                </dd>
                            </div>
                        </dl>
                    </div>

                    <!-- Results -->
                    @if($job->isCompleted())
                        <div class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                            <div class="flex items-center">
                                <svg class="h-5 w-5 text-green-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <div>
                                    <p class="text-sm font-medium text-green-800 dark:text-green-200">
                                        Generation Complete!
                                    </p>
                                    <p class="text-sm text-green-700 dark:text-green-300">
                                        {{ $job->questions_count }} questions generated and ready for review.
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Actions -->
                    <div class="flex items-center justify-end space-x-3">
                        <a href="{{ route('admin.lessons.edit', $job->lesson) }}" 
                            class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                            Back to Lesson
                        </a>

                        @if($job->isCompleted())
                            <a href="{{ route('admin.questions.generator.review', $job) }}" 
                                class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700">
                                Review Questions
                            </a>
                        @elseif($job->isPending() || $job->isProcessing())
                            <button type="button" onclick="location.reload()" 
                                class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                Refresh Status
                            </button>
                        @elseif($job->hasFailed())
                            <a href="{{ route('admin.questions.generator.create', ['lesson_id' => $job->lesson_id]) }}" 
                                class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700">
                                Try Again
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if($job->isPending() || $job->isProcessing())
        <script>
            // Auto-refresh every 5 seconds while processing
            setTimeout(() => {
                location.reload();
            }, 5000);
        </script>
    @endif
</x-app-layout>
