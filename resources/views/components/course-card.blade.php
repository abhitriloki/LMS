@props(['course'])

<div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden hover:shadow-lg transition-shadow duration-300">
    <!-- Course Thumbnail -->
    <div class="relative h-48 bg-gray-200 dark:bg-gray-700">
        @if($course->thumbnail)
            <img 
                src="{{ Storage::url($course->thumbnail) }}" 
                alt="{{ $course->title }}"
                class="w-full h-full object-cover"
            >
        @else
            <div class="w-full h-full flex items-center justify-center">
                <svg class="w-16 h-16 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                </svg>
            </div>
        @endif

        <!-- Difficulty Badge -->
        <div class="absolute top-3 right-3">
            <span class="px-2 py-1 text-xs font-semibold rounded-full
                @if($course->difficulty_level === 'beginner') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                @elseif($course->difficulty_level === 'intermediate') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                @else bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                @endif">
                {{ ucfirst($course->difficulty_level) }}
            </span>
        </div>

        <!-- Featured Badge -->
        @if($course->is_featured)
            <div class="absolute top-3 left-3">
                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-primary-100 text-primary-800 dark:bg-primary-900 dark:text-primary-200">
                    Featured
                </span>
            </div>
        @endif
    </div>

    <!-- Course Content -->
    <div class="p-5">
        <!-- Category -->
        @if($course->category)
            <div class="flex items-center text-xs text-gray-500 dark:text-gray-400 mb-2">
                @if($course->category->icon)
                    <span class="mr-1">{{ $course->category->icon }}</span>
                @endif
                <span>{{ $course->category->name }}</span>
            </div>
        @endif

        <!-- Title -->
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2 line-clamp-2">
            <a href="{{ route('catalog.show', $course->slug) }}" class="hover:text-primary-600 dark:hover:text-primary-400">
                @if(request('search'))
                    <x-search-highlight :text="$course->title" />
                @else
                    {{ $course->title }}
                @endif
            </a>
        </h3>

        <!-- Description -->
        @if($course->short_description)
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4 line-clamp-2">
                @if(request('search'))
                    <x-search-highlight :text="$course->short_description" />
                @else
                    {{ $course->short_description }}
                @endif
            </p>
        @endif

        <!-- Course Meta -->
        <div class="flex items-center justify-between text-sm text-gray-500 dark:text-gray-400 mb-4">
            <div class="flex items-center">
                <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>{{ $course->estimated_duration }} min</span>
            </div>

            <div class="flex items-center">
                <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                </svg>
                <span>{{ $course->modules->count() }} {{ Str::plural('module', $course->modules->count()) }}</span>
            </div>
        </div>

        <!-- Tags -->
        @if($course->tags && count($course->tags) > 0)
            <div class="flex flex-wrap gap-1 mb-4">
                @foreach(array_slice($course->tags, 0, 3) as $tag)
                    <span class="px-2 py-1 text-xs bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded">
                        {{ $tag }}
                    </span>
                @endforeach
            </div>
        @endif

        <!-- Action Button -->
        <a 
            href="{{ route('catalog.show', $course->slug) }}" 
            class="block w-full text-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg transition"
        >
            View Course
        </a>
    </div>
</div>
