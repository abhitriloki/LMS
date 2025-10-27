<div class="card">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-xl font-bold text-gray-900 dark:text-white">Recommended For You</h3>
        @if($recommendations->isNotEmpty())
            <a href="{{ route('recommendations.index') }}" class="text-sm text-primary-600 dark:text-primary-400 hover:underline">
                View All
            </a>
        @endif
    </div>

    @if($recommendations->isEmpty())
        <div class="text-center py-8">
            <svg class="w-12 h-12 mx-auto text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
            </svg>
            <p class="text-gray-600 dark:text-gray-400 mb-3">
                @if($needsNew)
                    Get personalized course recommendations powered by AI
                @else
                    No active recommendations at the moment
                @endif
            </p>
            <form action="{{ route('recommendations.generate') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-primary btn-sm">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                    Generate Recommendations
                </button>
            </form>
        </div>
    @else
        <div class="space-y-4">
            @foreach($recommendations as $recommendation)
                <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 hover:border-primary-500 dark:hover:border-primary-400 transition-colors">
                    <div class="flex gap-4">
                        <!-- Thumbnail -->
                        <div class="flex-shrink-0">
                            <img 
                                src="{{ $recommendation->course->thumbnail ? Storage::url($recommendation->course->thumbnail) : 'https://via.placeholder.com/100x75?text=Course' }}" 
                                alt="{{ $recommendation->course->title }}"
                                class="w-24 h-18 object-cover rounded"
                            >
                        </div>

                        <!-- Content -->
                        <div class="flex-grow min-w-0">
                            <div class="flex items-start justify-between mb-1">
                                <h4 class="font-semibold text-gray-900 dark:text-white truncate">
                                    {{ $recommendation->course->title }}
                                </h4>
                                <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-primary-100 text-primary-800 dark:bg-primary-900 dark:text-primary-200 flex-shrink-0">
                                    {{ number_format($recommendation->relevance_score * 100, 0) }}%
                                </span>
                            </div>

                            <div class="flex items-center gap-2 text-xs text-gray-600 dark:text-gray-400 mb-2">
                                <span>{{ $recommendation->course->category->name }}</span>
                                <span>•</span>
                                <span>{{ $recommendation->course->estimated_duration }}h</span>
                                <span>•</span>
                                <span class="capitalize">{{ $recommendation->course->difficulty_level }}</span>
                            </div>

                            <p class="text-sm text-gray-600 dark:text-gray-400 line-clamp-2 mb-3">
                                {{ $recommendation->reasoning }}
                            </p>

                            <div class="flex items-center gap-2">
                                <a href="{{ route('catalog.show', $recommendation->course->slug) }}" class="text-sm text-primary-600 dark:text-primary-400 hover:underline">
                                    View Details
                                </a>
                                <span class="text-gray-300 dark:text-gray-600">|</span>
                                <form action="{{ route('recommendations.accept', $recommendation) }}" method="POST" class="inline">
                                    @csrf
                                    <input type="hidden" name="enroll" value="true">
                                    <button type="submit" class="text-sm text-green-600 dark:text-green-400 hover:underline">
                                        Accept & Enroll
                                    </button>
                                </form>
                                <span class="text-gray-300 dark:text-gray-600">|</span>
                                <form action="{{ route('recommendations.reject', $recommendation) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="text-sm text-gray-600 dark:text-gray-400 hover:underline">
                                        Dismiss
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-4 text-center">
            <a href="{{ route('recommendations.index') }}" class="btn btn-secondary btn-sm w-full">
                View All Recommendations
            </a>
        </div>
    @endif
</div>
