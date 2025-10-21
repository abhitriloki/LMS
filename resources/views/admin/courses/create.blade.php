<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Create New Course') }}
            </h2>
            <a href="{{ route('admin.courses.index') }}" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200">
                ← Back to Courses
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <!-- Tabs -->
            <div class="mb-6" x-data="{ activeTab: 'basic' }">
                <div class="border-b border-gray-200 dark:border-gray-700">
                    <nav class="-mb-px flex space-x-8">
                        <button type="button" @click="activeTab = 'basic'" 
                            :class="activeTab === 'basic' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'"
                            class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                            Basic Information
                        </button>
                        <button type="button" @click="activeTab = 'content'" 
                            :class="activeTab === 'content' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'"
                            class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                            Content & Objectives
                        </button>
                        <button type="button" @click="activeTab = 'settings'" 
                            :class="activeTab === 'settings' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'"
                            class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                            Settings
                        </button>
                    </nav>
                </div>

                <form method="POST" action="{{ route('admin.courses.store') }}" enctype="multipart/form-data">
                    @csrf

                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mt-6">
                        <div class="p-6 space-y-6">
                            <!-- Basic Information Tab -->
                            <div x-show="activeTab === 'basic'" x-cloak>
                                <!-- Basic Information Content -->
                                <div>
                            
                            <!-- Title -->
                            <div class="mb-4">
                                <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Course Title <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="title" id="title" value="{{ old('title') }}" required
                                    class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                @error('title')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Short Description -->
                            <div class="mb-4">
                                <label for="short_description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Short Description
                                </label>
                                <input type="text" name="short_description" id="short_description" value="{{ old('short_description') }}"
                                    class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    maxlength="500">
                                @error('short_description')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Description -->
                            <div class="mb-4">
                                <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Full Description <span class="text-red-500">*</span>
                                </label>
                                <textarea name="description" id="description" rows="6" required
                                    class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('description') }}</textarea>
                                @error('description')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Category and Difficulty -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label for="category_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Category <span class="text-red-500">*</span>
                                    </label>
                                    <select name="category_id" id="category_id" required
                                        class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        <option value="">Select a category</option>
                                        @foreach($categories as $category)
                                            <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                                {{ $category->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('category_id')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="difficulty_level" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Difficulty Level <span class="text-red-500">*</span>
                                    </label>
                                    <select name="difficulty_level" id="difficulty_level" required
                                        class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        <option value="">Select difficulty</option>
                                        <option value="beginner" {{ old('difficulty_level') == 'beginner' ? 'selected' : '' }}>Beginner</option>
                                        <option value="intermediate" {{ old('difficulty_level') == 'intermediate' ? 'selected' : '' }}>Intermediate</option>
                                        <option value="advanced" {{ old('difficulty_level') == 'advanced' ? 'selected' : '' }}>Advanced</option>
                                    </select>
                                    @error('difficulty_level')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Duration and Language -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label for="estimated_duration" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Estimated Duration (minutes)
                                    </label>
                                    <input type="number" name="estimated_duration" id="estimated_duration" value="{{ old('estimated_duration') }}" min="0"
                                        class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    @error('estimated_duration')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="language" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Language
                                    </label>
                                    <input type="text" name="language" id="language" value="{{ old('language', 'English') }}"
                                        class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    @error('language')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Thumbnail -->
                            <div class="mb-4">
                                <label for="thumbnail" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Course Thumbnail
                                </label>
                                <input type="file" name="thumbnail" id="thumbnail" accept="image/*"
                                    class="w-full text-sm text-gray-500 dark:text-gray-400
                                        file:mr-4 file:py-2 file:px-4
                                        file:rounded-md file:border-0
                                        file:text-sm file:font-semibold
                                        file:bg-blue-50 file:text-blue-700
                                        hover:file:bg-blue-100">
                                @error('thumbnail')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                                </div>
                            </div>

                            <!-- Content & Objectives Tab -->
                            <div x-show="activeTab === 'content'" x-cloak>
                                <!-- Learning Objectives -->
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Learning Objectives
                                    </label>
                                    <div id="objectives-container" class="space-y-2">
                                        <div class="flex items-center space-x-2">
                                            <input type="text" name="learning_objectives[]"
                                                class="flex-1 rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                            <button type="button" onclick="this.parentElement.remove()" class="text-red-600 hover:text-red-800">
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                    <button type="button" onclick="addObjective()" class="mt-2 text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400">
                                        + Add Learning Objective
                                    </button>
                                </div>

                                <!-- Tags -->
                                <div class="mb-4">
                                    <label for="tags_input" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Tags
                                    </label>
                                    <input type="text" id="tags_input" placeholder="Enter tags separated by commas"
                                        class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <input type="hidden" name="tags" id="tags_hidden">
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Separate tags with commas</p>
                                </div>

                                <!-- Preview Video -->
                                <div class="mb-4">
                                    <label for="preview_video" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Preview Video URL
                                    </label>
                                    <input type="url" name="preview_video" id="preview_video" value="{{ old('preview_video') }}"
                                        class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                        placeholder="https://...">
                                    @error('preview_video')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Settings Tab -->
                            <div x-show="activeTab === 'settings'" x-cloak>

                            <!-- Target Audience -->
                            <div class="mb-4">
                                <label for="target_audience" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Target Audience
                                </label>
                                <textarea name="target_audience" id="target_audience" rows="3"
                                    class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('target_audience') }}</textarea>
                                @error('target_audience')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Prerequisites -->
                            <div class="mb-4">
                                <label for="prerequisites" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Prerequisites
                                </label>
                                <select name="prerequisites[]" id="prerequisites" multiple
                                    class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    @foreach($courses as $prereqCourse)
                                        <option value="{{ $prereqCourse->id }}">{{ $prereqCourse->title }}</option>
                                    @endforeach
                                </select>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Hold Ctrl/Cmd to select multiple courses</p>
                                @error('prerequisites')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Checkboxes -->
                            <div class="space-y-2">
                                <div class="flex items-center">
                                    <input type="checkbox" name="is_mandatory" id="is_mandatory" value="1" {{ old('is_mandatory') ? 'checked' : '' }}
                                        class="rounded border-gray-300 dark:border-gray-700 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <label for="is_mandatory" class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                                        Mandatory Course
                                    </label>
                                </div>

                                <div class="flex items-center">
                                    <input type="checkbox" name="is_featured" id="is_featured" value="1" {{ old('is_featured') ? 'checked' : '' }}
                                        class="rounded border-gray-300 dark:border-gray-700 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <label for="is_featured" class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                                        Featured Course
                                    </label>
                                </div>
                            </div>
                            </div>

                            <!-- Actions -->
                            <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                            <a href="{{ route('admin.courses.index') }}" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                                Cancel
                            </a>
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition">
                                Create Course
                            </button>
                        </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function addObjective() {
            const container = document.getElementById('objectives-container');
            const div = document.createElement('div');
            div.className = 'flex items-center space-x-2';
            div.innerHTML = `
                <input type="text" name="learning_objectives[]" 
                    class="flex-1 rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                <button type="button" onclick="this.parentElement.remove()" class="text-red-600 hover:text-red-800">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            `;
            container.appendChild(div);
        }

        // Handle tags input
        document.getElementById('tags_input').addEventListener('blur', function() {
            const tags = this.value.split(',').map(tag => tag.trim()).filter(tag => tag);
            document.getElementById('tags_hidden').value = JSON.stringify(tags);
        });
    </script>
    @endpush
</x-app-layout>
