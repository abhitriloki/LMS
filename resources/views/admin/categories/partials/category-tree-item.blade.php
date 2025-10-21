<div class="category-item border border-gray-200 dark:border-gray-700 rounded-lg p-4 bg-gray-50 dark:bg-gray-900" 
     style="margin-left: {{ $level * 2 }}rem;">
    <div class="flex items-center justify-between">
        <div class="flex items-center space-x-4 flex-1">
            <!-- Icon and Color -->
            <div class="flex items-center space-x-2">
                @if($category->icon)
                    <span class="text-2xl">{{ $category->icon }}</span>
                @endif
                @if($category->color)
                    <div class="w-6 h-6 rounded" style="background-color: {{ $category->color }}"></div>
                @endif
            </div>

            <!-- Category Info -->
            <div class="flex-1">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                    {{ $category->name }}
                </h3>
                @if($category->description)
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        {{ Str::limit($category->description, 100) }}
                    </p>
                @endif
                <div class="flex items-center space-x-4 mt-2 text-xs text-gray-500 dark:text-gray-400">
                    <span>{{ $category->courses->count() }} courses</span>
                    @if($category->children->count() > 0)
                        <span>{{ $category->children->count() }} subcategories</span>
                    @endif
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center space-x-2">
            @can('view', $category)
                <a href="{{ route('admin.categories.show', $category) }}" 
                   class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 px-3 py-1 rounded hover:bg-blue-50 dark:hover:bg-blue-900/20">
                    View
                </a>
            @endcan
            
            @can('update', $category)
                <a href="{{ route('admin.categories.edit', $category) }}" 
                   class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 px-3 py-1 rounded hover:bg-indigo-50 dark:hover:bg-indigo-900/20">
                    Edit
                </a>
            @endcan
            
            @can('delete', $category)
                <form action="{{ route('admin.categories.destroy', $category) }}" method="POST" class="inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" 
                            class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300 px-3 py-1 rounded hover:bg-red-50 dark:hover:bg-red-900/20" 
                            onclick="return confirm('Are you sure you want to delete this category? Child categories will be moved to the parent level.')">
                        Delete
                    </button>
                </form>
            @endcan
        </div>
    </div>
</div>

<!-- Render children recursively -->
@if($category->children->count() > 0)
    <div class="mt-2 space-y-2">
        @foreach($category->children as $child)
            @include('admin.categories.partials.category-tree-item', ['category' => $child, 'level' => $level + 1])
        @endforeach
    </div>
@endif
