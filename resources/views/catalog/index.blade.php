@extends('layouts.app')

@section('title', 'Course Catalog')

@section('content')
<div class="flex flex-col lg:flex-row gap-6">
    <!-- Filter Sidebar -->
    <aside class="w-full lg:w-64 flex-shrink-0">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 sticky top-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Filters</h2>
            
            <form method="GET" action="{{ route('catalog.index') }}" id="filterForm">
                <!-- Search -->
                <div class="mb-6">
                    <label for="search" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Search
                    </label>
                    <div class="relative">
                        <input 
                            type="text" 
                            name="search" 
                            id="search" 
                            value="{{ request('search') }}"
                            placeholder="Search courses..."
                            class="w-full px-3 py-2 pr-10 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white"
                        >
                        <button 
                            type="submit"
                            class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                        >
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Category Filter -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Category
                    </label>
                    <select 
                        name="category" 
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white"
                        onchange="this.form.submit()"
                    >
                        <option value="">All Categories</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ request('category') == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                            @foreach($category->children as $child)
                                <option value="{{ $child->id }}" {{ request('category') == $child->id ? 'selected' : '' }}>
                                    &nbsp;&nbsp;{{ $child->name }}
                                </option>
                            @endforeach
                        @endforeach
                    </select>
                </div>

                <!-- Difficulty Filter -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Difficulty Level
                    </label>
                    <select 
                        name="difficulty" 
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white"
                        onchange="this.form.submit()"
                    >
                        <option value="">All Levels</option>
                        @foreach($difficultyLevels as $level)
                            <option value="{{ $level }}" {{ request('difficulty') == $level ? 'selected' : '' }}>
                                {{ ucfirst($level) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Tags Filter -->
                @if(count($popularTags) > 0)
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Popular Tags
                    </label>
                    <div class="flex flex-wrap gap-2">
                        @foreach($popularTags as $tag)
                            <label class="inline-flex items-center">
                                <input 
                                    type="checkbox" 
                                    name="tags[]" 
                                    value="{{ $tag }}"
                                    {{ in_array($tag, (array) request('tags', [])) ? 'checked' : '' }}
                                    class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                                    onchange="this.form.submit()"
                                >
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ $tag }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
                @endif

                <!-- Clear Filters -->
                <div class="mt-6">
                    <a 
                        href="{{ route('catalog.index') }}" 
                        class="block w-full text-center px-4 py-2 text-sm text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition"
                    >
                        Clear Filters
                    </a>
                </div>
            </form>
        </div>
    </aside>

    <!-- Course Grid -->
    <div class="flex-1">
        <!-- Header with Sort -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Course Catalog</h1>
                <p class="text-gray-600 dark:text-gray-400 mt-1">
                    {{ $courses->total() }} {{ Str::plural('course', $courses->total()) }} found
                    @if(request('search'))
                        <span class="text-primary-600 dark:text-primary-400">
                            for "{{ request('search') }}"
                        </span>
                    @endif
                </p>
            </div>

            <div class="flex items-center gap-2">
                <label for="sort" class="text-sm text-gray-700 dark:text-gray-300">Sort by:</label>
                <select 
                    name="sort" 
                    id="sort" 
                    class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-white"
                    onchange="window.location.href = updateQueryString('sort', this.value)"
                >
                    <option value="created_at" {{ request('sort') == 'created_at' ? 'selected' : '' }}>Newest</option>
                    <option value="title" {{ request('sort') == 'title' ? 'selected' : '' }}>Title</option>
                    <option value="difficulty" {{ request('sort') == 'difficulty' ? 'selected' : '' }}>Difficulty</option>
                    <option value="duration" {{ request('sort') == 'duration' ? 'selected' : '' }}>Duration</option>
                    <option value="popular" {{ request('sort') == 'popular' ? 'selected' : '' }}>Most Popular</option>
                </select>
            </div>
        </div>

        <!-- Course Grid -->
        @if($courses->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                @foreach($courses as $course)
                    <x-course-card :course="$course" />
                @endforeach
            </div>

            <!-- Pagination -->
            <div class="mt-8">
                {{ $courses->links() }}
            </div>
        @else
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No courses found</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Try adjusting your filters or search terms.</p>
            </div>
        @endif
    </div>
</div>

<script>
function updateQueryString(key, value) {
    const url = new URL(window.location);
    url.searchParams.set(key, value);
    return url.toString();
}
</script>
@endsection
