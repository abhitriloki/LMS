<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Category Details') }}
            </h2>
            <div class="flex space-x-2">
                @can('update', $category)
                    <a href="{{ route('admin.categories.edit', $category) }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition">
                        {{ __('Edit Category') }}
                    </a>
                @endcan
                <a href="{{ route('admin.categories.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition">
                    {{ __('Back to Categories') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Category Info Card -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <div class="flex items-start space-x-6">
                        <!-- Icon and Color -->
                        <div class="flex flex-col items-center space-y-2">
                            @if($category->icon)
                                <div class="text-6xl">{{ $category->icon }}</div>
                            @endif
                            @if($category->color)
                                <div class="w-16 h-16 rounded-lg shadow" style="background-color: {{ $category->color }}"></div>
                            @endif
                        </div>

                        <!-- Details -->
                        <div class="flex-1">
                            <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-2">
                                {{ $category->name }}
                            </h3>
                            
                            @if($category->description)
                                <p class="text-gray-600 dark:text-gray-400 mb-4">
                                    {{ $category->description }}
                                </p>
                            @endif

                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-4">
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Slug</p>
                                    <p class="font-medium text-gray-900 dark:text-gray-100">{{ $category->slug }}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Parent Category</p>
                                    <p class="font-medium text-gray-900 dark:text-gray-100">
                                        @if($category->parent)
                                            <a href="{{ route('admin.categories.show', $category->parent) }}" class="text-blue-600 hover:text-blue-800">
                                                {{ $category->parent->name }}
                                            </a>
                                        @else
                                            Root Category
                                        @endif
                                    </p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Courses</p>
                                    <p class="font-medium text-gray-900 dark:text-gray-100">{{ $category->courses->count() }}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Subcategories</p>
                                    <p class="font-medium text-gray-900 dark:text-gray-100">{{ $category->children->count() }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Subcategories -->
            @if($category->children->count() > 0)
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <h4 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                            Subcategories ({{ $category->children->count() }})
                        </h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            @foreach($category->children as $child)
                                <a href="{{ route('admin.categories.show', $child) }}" 
                                   class="block p-4 border border-gray-200 dark:border-gray-700 rounded-lg hover:border-blue-500 dark:hover:border-blue-500 transition">
                                    <div class="flex items-center space-x-3">
                                        @if($child->icon)
                                            <span class="text-2xl">{{ $child->icon }}</span>
                                        @endif
                                        @if($child->color)
                                            <div class="w-4 h-4 rounded" style="background-color: {{ $child->color }}"></div>
                                        @endif
                                        <div class="flex-1">
                                            <h5 class="font-medium text-gray-900 dark:text-gray-100">{{ $child->name }}</h5>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $child->courses->count() }} courses</p>
                                        </div>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            <!-- Courses in this Category -->
            @if($category->courses->count() > 0)
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h4 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                            Courses in this Category ({{ $category->courses->count() }})
                        </h4>
                        <div class="space-y-3">
                            @foreach($category->courses as $course)
                                <div class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <h5 class="font-medium text-gray-900 dark:text-gray-100">{{ $course->title }}</h5>
                                            @if($course->short_description)
                                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                                    {{ Str::limit($course->short_description, 100) }}
                                                </p>
                                            @endif
                                            <div class="flex items-center space-x-4 mt-2 text-xs text-gray-500 dark:text-gray-400">
                                                <span class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded">
                                                    {{ ucfirst($course->difficulty_level ?? 'N/A') }}
                                                </span>
                                                @if($course->is_published)
                                                    <span class="px-2 py-1 bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200 rounded">
                                                        Published
                                                    </span>
                                                @else
                                                    <span class="px-2 py-1 bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200 rounded">
                                                        Draft
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @else
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-center text-gray-500 dark:text-gray-400">
                        No courses in this category yet.
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
