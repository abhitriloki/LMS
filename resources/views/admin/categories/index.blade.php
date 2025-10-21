<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Course Categories') }}
            </h2>
            @can('create', App\Models\CourseCategory::class)
                <a href="{{ route('admin.categories.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition">
                    {{ __('Add New Category') }}
                </a>
            @endcan
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            @if (session('error'))
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('error') }}</span>
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="mb-4 text-sm text-gray-600 dark:text-gray-400">
                        Organize your courses into hierarchical categories. Drag and drop to reorder.
                    </div>

                    <!-- Category Tree -->
                    <div class="space-y-2">
                        @forelse($categories as $category)
                            @include('admin.categories.partials.category-tree-item', ['category' => $category, 'level' => 0])
                        @empty
                            <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                                No categories found. Create your first category to get started.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
