<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Generate Questions with AI') }}
            </h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    @if($lesson)
                        <div class="mb-6 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                            <h3 class="font-semibold text-lg text-blue-900 dark:text-blue-100 mb-2">
                                {{ $lesson->title }}
                            </h3>
                            <p class="text-sm text-blue-700 dark:text-blue-300">
                                Course: {{ $lesson->module->course->title }} > Module: {{ $lesson->module->title }}
                            </p>
                            <p class="text-sm text-blue-600 dark:text-blue-400 mt-1">
                                Content Type: {{ ucfirst($lesson->content_type) }}
                            </p>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('admin.questions.generator.generate') }}" class="space-y-6">
                        @csrf

                        @if($lesson)
                            <input type="hidden" name="lesson_id" value="{{ $lesson->id }}">
                        @else
                            <!-- Lesson Selection -->
                            <div>
                                <label for="lesson_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Select Lesson
                                </label>
                                <select name="lesson_id" id="lesson_id" required
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                    <option value="">Choose a lesson...</option>
                                </select>
                                @error('lesson_id')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                        @endif

                        <!-- Number of Questions -->
                        <div>
                            <label for="count" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Number of Questions
                            </label>
                            <input type="number" name="count" id="count" min="1" max="20" value="5" required
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                Generate between 1 and 20 questions (recommended: 5-10)
                            </p>
                            @error('count')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Question Types -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Question Types
                            </label>
                            <div class="space-y-2">
                                @foreach($supportedTypes as $type)
                                    <label class="inline-flex items-center mr-6">
                                        <input type="checkbox" name="types[]" value="{{ $type }}" 
                                            {{ $type === 'multiple_choice' ? 'checked' : '' }}
                                            class="rounded border-gray-300 dark:border-gray-600 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                                            {{ ucwords(str_replace('_', ' ', $type)) }}
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                Select at least one question type
                            </p>
                            @error('types')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Difficulty Level -->
                        <div>
                            <label for="difficulty" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Difficulty Level
                            </label>
                            <select name="difficulty" id="difficulty" required
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                @foreach($difficultyLevels as $level)
                                    <option value="{{ $level }}" {{ $level === 'medium' ? 'selected' : '' }}>
                                        {{ ucfirst($level) }}
                                    </option>
                                @endforeach
                            </select>
                            @error('difficulty')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Points per Question -->
                        <div>
                            <label for="points_per_question" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Points per Question
                            </label>
                            <input type="number" name="points_per_question" id="points_per_question" 
                                min="0.5" max="10" step="0.5" value="1" required
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                            @error('points_per_question')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Include Explanation -->
                        <div>
                            <label class="inline-flex items-center">
                                <input type="checkbox" name="include_explanation" value="1" checked
                                    class="rounded border-gray-300 dark:border-gray-600 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                                    Include explanations for correct answers
                                </span>
                            </label>
                        </div>

                        <!-- Info Box -->
                        <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">
                                        AI-Generated Questions Require Review
                                    </h3>
                                    <div class="mt-2 text-sm text-yellow-700 dark:text-yellow-300">
                                        <p>Questions will be generated using AI and saved for your review. You can approve, reject, or modify them before adding to an assessment.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center justify-end space-x-3">
                            <a href="{{ url()->previous() }}" 
                                class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                                Cancel
                            </a>
                            <button type="submit"
                                class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                                Generate Questions
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
