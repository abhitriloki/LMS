{{-- Instructor Dashboard --}}

<!-- Stats Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
    <!-- Total Courses -->
    <div class="card hover:shadow-lg transition-shadow duration-200">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600 dark:text-gray-400">My Courses</p>
                <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1">{{ number_format($stats['totalCourses']) }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">{{ $stats['publishedCourses'] }} published</p>
            </div>
            <div class="p-3 bg-primary-100 dark:bg-primary-900 rounded-lg">
                <svg class="w-8 h-8 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                </svg>
            </div>
        </div>
    </div>

    <!-- Total Students -->
    <div class="card hover:shadow-lg transition-shadow duration-200">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600 dark:text-gray-400">Total Students</p>
                <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1">{{ number_format($stats['totalStudents']) }}</p>
            </div>
            <div class="p-3 bg-green-100 dark:bg-green-900 rounded-lg">
                <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                </svg>
            </div>
        </div>
    </div>

    <!-- Pending Grading -->
    <div class="card hover:shadow-lg transition-shadow duration-200">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600 dark:text-gray-400">Pending Grading</p>
                <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1">{{ number_format($stats['pendingGrading']) }}</p>
            </div>
            <div class="p-3 bg-yellow-100 dark:bg-yellow-900 rounded-lg">
                <svg class="w-8 h-8 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                </svg>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="card hover:shadow-lg transition-shadow duration-200 bg-gradient-to-br from-primary-50 to-accent-50 dark:from-primary-900/20 dark:to-accent-900/20">
        <div class="flex flex-col space-y-2">
            <a href="{{ route('admin.courses.create') }}" class="btn btn-primary btn-sm">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Create Course
            </a>
            <a href="{{ route('admin.grading.review.index') }}" class="btn btn-secondary btn-sm">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                </svg>
                Review Grading
            </a>
        </div>
    </div>
</div>

<!-- Welcome Message -->
<div class="card mb-6">
    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
        Welcome back, {{ Auth::user()->name }}!
    </h2>
    <p class="text-gray-600 dark:text-gray-400">
        Here's an overview of your courses and student activity.
    </p>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <!-- My Courses -->
    <div class="card">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">My Courses</h3>
            <a href="{{ route('admin.courses.index') }}" class="text-sm text-primary-600 hover:text-primary-700 dark:text-primary-400">View All</a>
        </div>
        <div class="space-y-3">
            @forelse($courses as $course)
                <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                    <div class="flex-1">
                        <div class="flex items-center space-x-2">
                            <p class="font-medium text-gray-900 dark:text-white">{{ $course->title }}</p>
                            @if($course->is_published)
                                <span class="px-2 py-1 text-xs font-medium text-green-700 bg-green-100 dark:bg-green-900 dark:text-green-300 rounded">Published</span>
                            @else
                                <span class="px-2 py-1 text-xs font-medium text-gray-700 bg-gray-100 dark:bg-gray-700 dark:text-gray-300 rounded">Draft</span>
                            @endif
                        </div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                            {{ $course->enrollments_count }} students • {{ $course->assessments_count }} assessments
                        </p>
                    </div>
                    <a href="{{ route('admin.courses.show', $course) }}" class="text-primary-600 hover:text-primary-700 dark:text-primary-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </a>
                </div>
            @empty
                <p class="text-gray-500 dark:text-gray-400 text-center py-4">No courses yet. Create your first course!</p>
            @endforelse
        </div>
    </div>

    <!-- Recent Student Activity -->
    <div class="card">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Recent Student Activity</h3>
        <div class="space-y-3">
            @forelse($recentStudentActivity as $enrollment)
                <div class="flex items-start space-x-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                    <div class="flex-shrink-0">
                        @if($enrollment->user->avatar)
                            <img src="{{ Storage::url($enrollment->user->avatar) }}" alt="{{ $enrollment->user->name }}" class="w-10 h-10 rounded-full">
                        @else
                            <div class="w-10 h-10 rounded-full bg-primary-100 dark:bg-primary-900 flex items-center justify-center">
                                <span class="text-primary-600 dark:text-primary-400 font-semibold">{{ substr($enrollment->user->name, 0, 1) }}</span>
                            </div>
                        @endif
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm text-gray-900 dark:text-white">
                            <span class="font-medium">{{ $enrollment->user->name }}</span>
                            accessed
                            <span class="font-medium">{{ $enrollment->course->title }}</span>
                        </p>
                        <div class="flex items-center mt-1">
                            <div class="flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-2 mr-2">
                                <div class="bg-primary-600 h-2 rounded-full" style="width: {{ $enrollment->progress_percentage }}%"></div>
                            </div>
                            <span class="text-xs text-gray-500 dark:text-gray-400">{{ number_format($enrollment->progress_percentage, 0) }}%</span>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $enrollment->last_accessed_at?->diffForHumans() ?? 'Never' }}</p>
                    </div>
                </div>
            @empty
                <p class="text-gray-500 dark:text-gray-400 text-center py-4">No recent student activity</p>
            @endforelse
        </div>
    </div>
</div>

<!-- Upcoming Deadlines -->
@if($upcomingDeadlines->isNotEmpty())
<div class="card">
    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Upcoming Course Deadlines</h3>
    <div class="space-y-3">
        @foreach($upcomingDeadlines as $enrollment)
            <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                <div class="flex-1">
                    <p class="font-medium text-gray-900 dark:text-white">{{ $enrollment->course->title }}</p>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Due {{ $enrollment->deadline->format('M d, Y') }} ({{ $enrollment->deadline->diffForHumans() }})
                    </p>
                </div>
                <span class="px-3 py-1 text-sm font-medium text-orange-700 bg-orange-100 dark:bg-orange-900 dark:text-orange-300 rounded">
                    {{ $enrollment->deadline->diffInDays() }} days left
                </span>
            </div>
        @endforeach
    </div>
</div>
@endif
