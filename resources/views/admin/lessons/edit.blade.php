<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Edit Lesson') }}
            </h2>
            <a href="{{ route('admin.courses.builder', $lesson->module->course) }}" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200">
                ← Back to Course Builder
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('admin.lessons.update', $lesson) }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 space-y-6">
                        <!-- Course & Module Info -->
                        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                            <p class="text-sm text-blue-800 dark:text-blue-300">
                                <span class="font-medium">Course:</span> {{ $lesson->module->course->title }}
                            </p>
                            <p class="text-sm text-blue-800 dark:text-blue-300 mt-1">
                                <span class="font-medium">Module:</span> {{ $lesson->module->title }}
                            </p>
                        </div>

                        <!-- Lesson Title -->
                        <div>
                            <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Lesson Title <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="title" id="title" value="{{ old('title', $lesson->title) }}" required
                                class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            @error('title')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Lesson Description -->
                        <div>
                            <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Description
                            </label>
                            <textarea name="description" id="description" rows="4"
                                class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('description', $lesson->description) }}</textarea>
                            @error('description')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Content Type -->
                        <div>
                            <label for="content_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Content Type <span class="text-red-500">*</span>
                            </label>
                            <select name="content_type" id="content_type" required
                                class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Select type</option>
                                <option value="video" {{ old('content_type', $lesson->content_type) == 'video' ? 'selected' : '' }}>Video</option>
                                <option value="pdf" {{ old('content_type', $lesson->content_type) == 'pdf' ? 'selected' : '' }}>PDF</option>
                                <option value="text" {{ old('content_type', $lesson->content_type) == 'text' ? 'selected' : '' }}>Text</option>
                                <option value="scorm" {{ old('content_type', $lesson->content_type) == 'scorm' ? 'selected' : '' }}>SCORM</option>
                                <option value="presentation" {{ old('content_type', $lesson->content_type) == 'presentation' ? 'selected' : '' }}>Presentation</option>
                            </select>
                            @error('content_type')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Content URL -->
                        <div>
                            <label for="content_url" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Content URL
                            </label>
                            <input type="url" name="content_url" id="content_url" value="{{ old('content_url', $lesson->content_url) }}"
                                class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                placeholder="https://...">
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">For external video or content links</p>
                            @error('content_url')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Current Content File -->
                        @if($lesson->content_path)
                            <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4">
                                <p class="text-sm text-gray-700 dark:text-gray-300">
                                    <span class="font-medium">Current File:</span> {{ basename($lesson->content_path) }}
                                </p>
                            </div>
                        @endif

                        <!-- Content File Upload -->
                        <div>
                            <label for="content_file" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                {{ $lesson->content_path ? 'Replace Content File' : 'Upload Content File' }}
                            </label>
                            <input type="file" name="content_file" id="content_file"
                                class="w-full text-sm text-gray-500 dark:text-gray-400
                                    file:mr-4 file:py-2 file:px-4
                                    file:rounded-md file:border-0
                                    file:text-sm file:font-semibold
                                    file:bg-blue-50 file:text-blue-700
                                    hover:file:bg-blue-100">
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Upload video, PDF, or other content files</p>
                            @error('content_file')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Duration -->
                        <div>
                            <label for="duration" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Duration (minutes)
                            </label>
                            <input type="number" name="duration" id="duration" value="{{ old('duration', $lesson->duration) }}" min="0"
                                class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            @error('duration')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Is Downloadable -->
                        <div class="flex items-center">
                            <input type="checkbox" name="is_downloadable" id="is_downloadable" value="1" 
                                {{ old('is_downloadable', $lesson->is_downloadable) ? 'checked' : '' }}
                                class="rounded border-gray-300 dark:border-gray-700 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <label for="is_downloadable" class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                                Allow students to download this content
                            </label>
                        </div>

                        <!-- Lesson Stats -->
                        <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4">
                            <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-2">Lesson Statistics</h3>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <p class="text-xs text-gray-600 dark:text-gray-400">Order Position</p>
                                    <p class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $lesson->order_index + 1 }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-600 dark:text-gray-400">Content Type</p>
                                    <p class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ ucfirst($lesson->content_type) }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                            <a href="{{ route('admin.courses.builder', $lesson->module->course) }}" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                                Cancel
                            </a>
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition">
                                Update Lesson
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
