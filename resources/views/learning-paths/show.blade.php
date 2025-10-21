<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    {{ $learningPath->target_role }}
                </h2>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    Learning Path
                </p>
            </div>
            <div class="flex items-center space-x-2">
                @if($learningPath->status === 'draft')
                    <form method="POST" action="{{ route('learning-paths.start', $learningPath) }}" class="inline">
                        @csrf
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700">
                            Start Path
                        </button>
                    </form>
                @elseif($learningPath->status === 'active')
                    <form method="POST" action="{{ route('learning-paths.pause', $learningPath) }}" class="inline">
                        @csrf
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-yellow-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-yellow-700">
                            Pause Path
                        </button>
                    </form>
                @elseif($learningPath->status === 'paused')
                    <form method="POST" action="{{ route('learning-paths.resume', $learningPath) }}" class="inline">
                        @csrf
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700">
                            Resume Path
                        </button>
                    </form>
                @endif

                @if($learningPath->status !== 'completed')
                    <form method="POST" action="{{ route('learning-paths.optimize', $learningPath) }}" class="inline">
                        @csrf
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            Optimize
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            @if(session('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('error') }}</span>
                </div>
            @endif

            <!-- Overview Card -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                        <div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Status</div>
                            <div class="mt-1">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    @if($learningPath->status === 'active') bg-green-100 text-green-800
                                    @elseif($learningPath->status === 'completed') bg-blue-100 text-blue-800
                                    @elseif($learningPath->status === 'paused') bg-yellow-100 text-yellow-800
                                    @else bg-gray-100 text-gray-800
                                    @endif">
                                    {{ ucfirst($learningPath->status) }}
                                </span>
                            </div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Total Courses</div>
                            <div class="mt-1 text-2xl font-semibold text-gray-900 dark:text-gray-100">
                                {{ count($enrichedCourses) }}
                            </div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Estimated Duration</div>
                            <div class="mt-1 text-2xl font-semibold text-gray-900 dark:text-gray-100">
                                {{ $learningPath->estimated_duration }} weeks
                            </div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Progress</div>
                            <div class="mt-1 text-2xl font-semibold text-gray-900 dark:text-gray-100">
                                {{ number_format($learningPath->progress_percentage, 0) }}%
                            </div>
                        </div>
                    </div>

                    <div class="mt-6">
                        <div class="w-full bg-gray-200 rounded-full h-3 dark:bg-gray-700">
                            <div class="bg-blue-600 h-3 rounded-full transition-all duration-300" style="width: {{ $learningPath->progress_percentage }}%"></div>
                        </div>
                    </div>

                    @if($learningPath->path_data['reasoning'] ?? null)
                        <div class="mt-6 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                            <h4 class="text-sm font-medium text-blue-900 dark:text-blue-300 mb-2">AI Reasoning</h4>
                            <p class="text-sm text-blue-800 dark:text-blue-400">{{ $learningPath->path_data['reasoning'] }}</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Milestones -->
            @if(!empty($learningPath->path_data['milestones']))
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Milestones</h3>
                        <div class="space-y-4">
                            @foreach($learningPath->path_data['milestones'] as $milestone)
                                <div class="flex items-start">
                                    <div class="flex-shrink-0">
                                        <div class="flex items-center justify-center h-8 w-8 rounded-full bg-blue-100 dark:bg-blue-900">
                                            <svg class="h-5 w-5 text-blue-600 dark:text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $milestone['name'] }}</h4>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $milestone['description'] }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            <!-- Course Path -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-6">Learning Path</h3>
                    
                    <div class="space-y-4">
                        @foreach($enrichedCourses as $index => $pathCourse)
                            @php
                                $course = $pathCourse['course'];
                                $enrollment = $enrollments->get($course->id ?? null);
                                $isCompleted = $enrollment && $enrollment->status === 'completed';
                                $isInProgress = $enrollment && $enrollment->status === 'active';
                                $isNext = $nextCourse && $nextCourse['course_id'] === $course->id;
                            @endphp

                            <div class="relative">
                                <!-- Connector Line -->
                                @if(!$loop->last)
                                    <div class="absolute left-6 top-16 bottom-0 w-0.5 bg-gray-200 dark:bg-gray-700 -mb-4"></div>
                                @endif

                                <div class="flex items-start space-x-4 p-4 rounded-lg border-2 transition-all
                                    @if($isNext) border-blue-500 bg-blue-50 dark:bg-blue-900/20
                                    @elseif($isCompleted) border-green-300 dark:border-green-700 bg-green-50 dark:bg-green-900/20
                                    @elseif($isInProgress) border-yellow-300 dark:border-yellow-700 bg-yellow-50 dark:bg-yellow-900/20
                                    @else border-gray-200 dark:border-gray-700
                                    @endif">
                                    
                                    <!-- Step Number -->
                                    <div class="flex-shrink-0">
                                        <div class="flex items-center justify-center h-12 w-12 rounded-full font-semibold
                                            @if($isCompleted) bg-green-500 text-white
                                            @elseif($isInProgress) bg-yellow-500 text-white
                                            @elseif($isNext) bg-blue-500 text-white
                                            @else bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-400
                                            @endif">
                                            @if($isCompleted)
                                                <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                                </svg>
                                            @else
                                                {{ $pathCourse['order'] }}
                                            @endif
                                        </div>
                                    </div>

                                    <!-- Course Info -->
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-start justify-between">
                                            <div class="flex-1">
                                                <h4 class="text-base font-semibold text-gray-900 dark:text-gray-100">
                                                    {{ $course->title ?? 'Course not found' }}
                                                </h4>
                                                @if($pathCourse['milestone'] ?? null)
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300 mt-1">
                                                        {{ $pathCourse['milestone'] }}
                                                    </span>
                                                @endif
                                                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                                    {{ $course->short_description ?? $course->description ?? '' }}
                                                </p>
                                                @if($pathCourse['reason'] ?? null)
                                                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-500 italic">
                                                        💡 {{ $pathCourse['reason'] }}
                                                    </p>
                                                @endif
                                                <div class="mt-2 flex items-center space-x-4 text-xs text-gray-500 dark:text-gray-400">
                                                    <span class="flex items-center">
                                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                        </svg>
                                                        {{ $pathCourse['estimated_weeks'] ?? 1 }} weeks
                                                    </span>
                                                    <span class="flex items-center">
                                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                                                        </svg>
                                                        {{ ucfirst($course->difficulty_level ?? 'beginner') }}
                                                    </span>
                                                </div>
                                            </div>

                                            <!-- Action Button -->
                                            <div class="ml-4 flex-shrink-0">
                                                @if($isCompleted)
                                                    <a href="{{ route('courses.show', $course) }}" class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                                        Review
                                                    </a>
                                                @elseif($isInProgress)
                                                    <a href="{{ route('enrollments.show', $enrollment) }}" class="inline-flex items-center px-3 py-2 border border-transparent rounded-md text-sm font-medium text-white bg-yellow-600 hover:bg-yellow-700">
                                                        Continue
                                                    </a>
                                                @elseif($isNext)
                                                    @if($enrollment)
                                                        <a href="{{ route('enrollments.show', $enrollment) }}" class="inline-flex items-center px-3 py-2 border border-transparent rounded-md text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                                                            Start Course
                                                        </a>
                                                    @else
                                                        <a href="{{ route('courses.show', $course) }}" class="inline-flex items-center px-3 py-2 border border-transparent rounded-md text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                                                            Enroll Now
                                                        </a>
                                                    @endif
                                                @else
                                                    <span class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-400 dark:text-gray-500">
                                                        Locked
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Actions</h3>
                    <div class="flex flex-wrap gap-3">
                        <form method="POST" action="{{ route('learning-paths.update-progress', $learningPath) }}" class="inline">
                            @csrf
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-600">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                                Refresh Progress
                            </button>
                        </form>

                        <form method="POST" action="{{ route('learning-paths.destroy', $learningPath) }}" class="inline" onsubmit="return confirm('Are you sure you want to delete this learning path?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                                Delete Path
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
