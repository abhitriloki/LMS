<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ $course->title }}
            </h2>
            <div class="flex space-x-2">
                @can('update', $course)
                    <a href="{{ route('admin.courses.edit', $course) }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition">
                        Edit Course
                    </a>
                    <a href="{{ route('admin.courses.builder', $course) }}" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg transition">
                        Course Builder
                    </a>
                    <a href="{{ route('admin.content-analyzer.show', $course) }}" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition">
                        Analyze Content
                    </a>
                @endcan
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Content -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Course Details -->
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            @if($course->thumbnail)
                                <img src="{{ Storage::url($course->thumbnail) }}" alt="{{ $course->title }}" class="w-full h-64 object-cover rounded-lg mb-4">
                            @endif

                            <div class="mb-4">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">Description</h3>
                                <p class="text-gray-700 dark:text-gray-300">{{ $course->description }}</p>
                            </div>

                            @if($course->target_audience)
                                <div class="mb-4">
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">Target Audience</h3>
                                    <p class="text-gray-700 dark:text-gray-300">{{ $course->target_audience }}</p>
                                </div>
                            @endif

                            @if($course->learning_objectives && count($course->learning_objectives) > 0)
                                <div class="mb-4">
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">Learning Objectives</h3>
                                    <ul class="list-disc list-inside space-y-1 text-gray-700 dark:text-gray-300">
                                        @foreach($course->learning_objectives as $objective)
                                            <li>{{ $objective }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Course Content -->
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Course Content</h3>
                            
                            @if($course->modules->count() > 0)
                                <div class="space-y-4">
                                    @foreach($course->modules as $module)
                                        <div class="border border-gray-200 dark:border-gray-700 rounded-lg">
                                            <div class="bg-gray-50 dark:bg-gray-900 px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                                                <h4 class="font-medium text-gray-900 dark:text-gray-100">
                                                    Module {{ $module->order_index + 1 }}: {{ $module->title }}
                                                </h4>
                                                @if($module->description)
                                                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ $module->description }}</p>
                                                @endif
                                            </div>
                                            <div class="p-4">
                                                @if($module->lessons->count() > 0)
                                                    <ul class="space-y-2">
                                                        @foreach($module->lessons as $lesson)
                                                            <li class="flex items-center text-sm text-gray-700 dark:text-gray-300">
                                                                <svg class="h-5 w-5 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    @if($lesson->content_type === 'video')
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                                    @elseif($lesson->content_type === 'pdf')
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                                                    @else
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                                    @endif
                                                                </svg>
                                                                <span>{{ $lesson->title }}</span>
                                                                @if($lesson->duration)
                                                                    <span class="ml-auto text-gray-500 dark:text-gray-400">{{ $lesson->duration }} min</span>
                                                                @endif
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                @else
                                                    <p class="text-sm text-gray-500 dark:text-gray-400">No lessons in this module yet.</p>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-gray-500 dark:text-gray-400">No modules added yet. Use the Course Builder to add content.</p>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Course Info -->
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Course Information</h3>
                            
                            <dl class="space-y-3">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</dt>
                                    <dd class="mt-1">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                            {{ $course->is_published ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                            {{ $course->is_published ? 'Published' : 'Draft' }}
                                        </span>
                                    </dd>
                                </div>

                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Category</dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $course->category->name ?? 'N/A' }}</dd>
                                </div>

                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Difficulty</dt>
                                    <dd class="mt-1">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                            @if($course->difficulty_level === 'beginner') bg-green-100 text-green-800
                                            @elseif($course->difficulty_level === 'intermediate') bg-yellow-100 text-yellow-800
                                            @else bg-red-100 text-red-800
                                            @endif">
                                            {{ ucfirst($course->difficulty_level) }}
                                        </span>
                                    </dd>
                                </div>

                                @if($course->estimated_duration)
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Duration</dt>
                                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $course->estimated_duration }} minutes</dd>
                                    </div>
                                @endif

                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Language</dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $course->language ?? 'English' }}</dd>
                                </div>

                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Created By</dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $course->creator->name ?? 'N/A' }}</dd>
                                </div>

                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Created</dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $course->created_at->format('M d, Y') }}</dd>
                                </div>
                            </dl>

                            @if($course->is_mandatory || $course->is_featured)
                                <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                                    @if($course->is_mandatory)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 mr-2">
                                            Mandatory
                                        </span>
                                    @endif
                                    @if($course->is_featured)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                            Featured
                                        </span>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Actions -->
                    @can('update', $course)
                        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Actions</h3>
                                
                                <div class="space-y-2">
                                    @if(!$course->is_published)
                                        <form method="POST" action="{{ route('admin.courses.publish', $course) }}">
                                            @csrf
                                            <button type="submit" class="w-full px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition">
                                                Publish Course
                                            </button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('admin.courses.unpublish', $course) }}">
                                            @csrf
                                            <button type="submit" class="w-full px-4 py-2 bg-yellow-600 text-white rounded-md hover:bg-yellow-700 transition">
                                                Unpublish Course
                                            </button>
                                        </form>
                                    @endif

                                    <form method="POST" action="{{ route('admin.courses.clone', $course) }}">
                                        @csrf
                                        <button type="submit" class="w-full px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition">
                                            Clone Course
                                        </button>
                                    </form>

                                    @can('delete', $course)
                                        <form method="POST" action="{{ route('admin.courses.destroy', $course) }}" onsubmit="return confirm('Are you sure you want to delete this course?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="w-full px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition">
                                                Delete Course
                                            </button>
                                        </form>
                                    @endcan
                                </div>
                            </div>
                        </div>
                    @endcan
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
