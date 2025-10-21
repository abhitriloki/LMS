<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Create Assessment') }}
            </h2>
            <a href="{{ route('admin.assessments.index') }}" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200">
                ← Back to Assessments
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('admin.assessments.store') }}">
                @csrf

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 space-y-6">
                        <!-- Course Selection -->
                        <div>
                            <label for="course_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Course <span class="text-red-500">*</span>
                            </label>
                            <select name="course_id" id="course_id" required
                                class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                                <option value="">Select a course</option>
                                @foreach($courses as $courseOption)
                                    <option value="{{ $courseOption->id }}" {{ old('course_id', $course?->id) == $courseOption->id ? 'selected' : '' }}>
                                        {{ $courseOption->title }}
                                    </option>
                                @endforeach
                            </select>
                            @error('course_id')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Title -->
                        <div>
                            <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Assessment Title <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="title" id="title" value="{{ old('title') }}" required
                                class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                            @error('title')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Description -->
                        <div>
                            <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Description
                            </label>
                            <textarea name="description" id="description" rows="3"
                                class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">{{ old('description') }}</textarea>
                            @error('description')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Instructions -->
                        <div>
                            <label for="instructions" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Instructions
                            </label>
                            <textarea name="instructions" id="instructions" rows="4"
                                class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">{{ old('instructions') }}</textarea>
                            @error('instructions')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Settings Grid -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Passing Score -->
                            <div>
                                <label for="passing_score" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Passing Score (%) <span class="text-red-500">*</span>
                                </label>
                                <input type="number" name="passing_score" id="passing_score" value="{{ old('passing_score', 70) }}" 
                                    min="0" max="100" required
                                    class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                                @error('passing_score')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Time Limit -->
                            <div>
                                <label for="time_limit" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Time Limit (minutes, 0 = unlimited)
                                </label>
                                <input type="number" name="time_limit" id="time_limit" value="{{ old('time_limit', 0) }}" 
                                    min="0"
                                    class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                                @error('time_limit')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Max Attempts -->
                            <div>
                                <label for="max_attempts" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Max Attempts (0 = unlimited)
                                </label>
                                <input type="number" name="max_attempts" id="max_attempts" value="{{ old('max_attempts', 0) }}" 
                                    min="0"
                                    class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                                @error('max_attempts')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Checkboxes -->
                        <div class="space-y-3">
                            <div class="flex items-center">
                                <input type="checkbox" name="randomize_questions" id="randomize_questions" value="1" 
                                    {{ old('randomize_questions') ? 'checked' : '' }}
                                    class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <label for="randomize_questions" class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                                    Randomize question order
                                </label>
                            </div>

                            <div class="flex items-center">
                                <input type="checkbox" name="randomize_options" id="randomize_options" value="1" 
                                    {{ old('randomize_options') ? 'checked' : '' }}
                                    class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <label for="randomize_options" class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                                    Randomize answer options
                                </label>
                            </div>

                            <div class="flex items-center">
                                <input type="checkbox" name="show_results" id="show_results" value="1" 
                                    {{ old('show_results', true) ? 'checked' : '' }}
                                    class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <label for="show_results" class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                                    Show results to students
                                </label>
                            </div>

                            <div class="flex items-center">
                                <input type="checkbox" name="show_correct_answers" id="show_correct_answers" value="1" 
                                    {{ old('show_correct_answers') ? 'checked' : '' }}
                                    class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <label for="show_correct_answers" class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                                    Show correct answers after submission
                                </label>
                            </div>

                            <div class="flex items-center">
                                <input type="checkbox" name="allow_review" id="allow_review" value="1" 
                                    {{ old('allow_review', true) ? 'checked' : '' }}
                                    class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <label for="allow_review" class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                                    Allow students to review their attempts
                                </label>
                            </div>

                            <div class="flex items-center">
                                <input type="checkbox" name="is_published" id="is_published" value="1" 
                                    {{ old('is_published') ? 'checked' : '' }}
                                    class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <label for="is_published" class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                                    Publish immediately
                                </label>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="flex justify-end space-x-3">
                            <a href="{{ route('admin.assessments.index') }}" 
                                class="bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 px-4 py-2 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600">
                                Cancel
                            </a>
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
                                Create Assessment
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
