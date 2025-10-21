{{-- Employee Dashboard --}}

<!-- Stats Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
    <!-- Enrolled Courses -->
    <div class="card hover:shadow-lg transition-shadow duration-200">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600 dark:text-gray-400">Enrolled Courses</p>
                <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1">{{ number_format($stats['enrolledCourses']) }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">{{ $stats['activeCourses'] }} active</p>
            </div>
            <div class="p-3 bg-primary-100 dark:bg-primary-900 rounded-lg">
                <svg class="w-8 h-8 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                </svg>
            </div>
        </div>
    </div>

    <!-- Completed Courses -->
    <div class="card hover:shadow-lg transition-shadow duration-200">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600 dark:text-gray-400">Completed</p>
                <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1">{{ number_format($stats['completedCourses']) }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">{{ number_format($stats['averageProgress'], 1) }}% avg progress</p>
            </div>
            <div class="p-3 bg-green-100 dark:bg-green-900 rounded-lg">
                <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
        </div>
    </div>

    <!-- Certificates -->
    <div class="card hover:shadow-lg transition-shadow duration-200">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600 dark:text-gray-400">Certificates</p>
                <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1">{{ number_format($stats['certificates']) }}</p>
            </div>
            <div class="p-3 bg-accent-100 dark:bg-accent-900 rounded-lg">
                <svg class="w-8 h-8 text-accent-600 dark:text-accent-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path>
                </svg>
            </div>
        </div>
    </div>

    <!-- Learning Hours -->
    <div class="card hover:shadow-lg transition-shadow duration-200">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600 dark:text-gray-400">Learning Hours</p>
                <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1">{{ $stats['learningHours'] }}</p>
            </div>
            <div class="p-3 bg-secondary-100 dark:bg-secondary-900 rounded-lg">
                <svg class="w-8 h-8 text-secondary-600 dark:text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
        </div>
    </div>
</div>

<!-- Welcome Message -->
<div class="card mb-6">
    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
        Welcome back, {{ Auth::user()->name }}!
    </h2>
    <p class="text-gray-600 dark:text-gray-400">
        Ready to continue your learning journey? Check out your courses and recommendations below.
    </p>
</div>

<!-- AI Recommendations -->
@if($recommendations->isNotEmpty())
<div class="card mb-6">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Recommended for You</h3>
        <a href="{{ route('recommendations.index') }}" class="text-sm text-primary-600 hover:text-primary-700 dark:text-primary-400">View All</a>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        @foreach($recommendations as $recommendation)
            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 hover:shadow-md transition-shadow">
                @if($recommendation->course->thumbnail)
                    <img src="{{ Storage::url($recommendation->course->thumbnail) }}" alt="{{ $recommendation->course->title }}" class="w-full h-32 object-cover rounded-lg mb-3">
                @else
                    <div class="w-full h-32 bg-gradient-to-br from-primary-100 to-accent-100 dark:from-primary-900 dark:to-accent-900 rounded-lg mb-3 flex items-center justify-center">
                        <svg class="w-12 h-12 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                        </svg>
                    </div>
                @endif
                <h4 class="font-semibold text-gray-900 dark:text-white mb-2">{{ $recommendation->course->title }}</h4>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">{{ Str::limit($recommendation->reasoning, 100) }}</p>
                <div class="flex items-center justify-between">
                    <span class="text-xs text-primary-600 dark:text-primary-400 font-medium">{{ number_format($recommendation->score * 100) }}% match</span>
                    <a href="{{ route('catalog.show', $recommendation->course) }}" class="text-sm text-primary-600 hover:text-primary-700 dark:text-primary-400">View Course</a>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <!-- In Progress Courses -->
    <div class="card">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Continue Learning</h3>
            <a href="{{ route('enrollments.index') }}" class="text-sm text-primary-600 hover:text-primary-700 dark:text-primary-400">View All</a>
        </div>
        <div class="space-y-3">
            @forelse($inProgressCourses as $enrollment)
                <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                    <div class="flex items-start space-x-3">
                        @if($enrollment->course->thumbnail)
                            <img src="{{ Storage::url($enrollment->course->thumbnail) }}" alt="{{ $enrollment->course->title }}" class="w-16 h-16 object-cover rounded">
                        @else
                            <div class="w-16 h-16 bg-gradient-to-br from-primary-100 to-accent-100 dark:from-primary-900 dark:to-accent-900 rounded flex items-center justify-center flex-shrink-0">
                                <svg class="w-8 h-8 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                                </svg>
                            </div>
                        @endif
                        <div class="flex-1 min-w-0">
                            <h4 class="font-medium text-gray-900 dark:text-white">{{ $enrollment->course->title }}</h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">{{ $enrollment->course->category->name ?? 'Uncategorized' }}</p>
                            <div class="flex items-center">
                                <div class="flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-2 mr-2">
                                    <div class="bg-primary-600 h-2 rounded-full transition-all duration-300" style="width: {{ $enrollment->progress_percentage }}%"></div>
                                </div>
                                <span class="text-xs text-gray-500 dark:text-gray-400">{{ number_format($enrollment->progress_percentage, 0) }}%</span>
                            </div>
                            <a href="{{ route('enrollments.show', $enrollment) }}" class="text-sm text-primary-600 hover:text-primary-700 dark:text-primary-400 mt-2 inline-block">Continue →</a>
                        </div>
                    </div>
                </div>
            @empty
                <p class="text-gray-500 dark:text-gray-400 text-center py-4">No courses in progress. <a href="{{ route('catalog.index') }}" class="text-primary-600 hover:text-primary-700">Browse catalog</a></p>
            @endforelse
        </div>
    </div>

    <!-- Recent Certificates -->
    <div class="card">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Recent Achievements</h3>
            <a href="{{ route('certificates.index') }}" class="text-sm text-primary-600 hover:text-primary-700 dark:text-primary-400">View All</a>
        </div>
        <div class="space-y-3">
            @forelse($recentCertificates as $certificate)
                <div class="p-3 bg-gradient-to-r from-accent-50 to-primary-50 dark:from-accent-900/20 dark:to-primary-900/20 rounded-lg border border-accent-200 dark:border-accent-800">
                    <div class="flex items-center space-x-3">
                        <div class="flex-shrink-0">
                            <svg class="w-12 h-12 text-accent-600 dark:text-accent-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path>
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h4 class="font-medium text-gray-900 dark:text-white">{{ $certificate->course->title }}</h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Issued {{ $certificate->issued_at->format('M d, Y') }}</p>
                            <a href="{{ route('certificates.show', $certificate) }}" class="text-sm text-primary-600 hover:text-primary-700 dark:text-primary-400 mt-1 inline-block">View Certificate →</a>
                        </div>
                    </div>
                </div>
            @empty
                <p class="text-gray-500 dark:text-gray-400 text-center py-4">No certificates yet. Complete courses to earn certificates!</p>
            @endforelse
        </div>
    </div>
</div>

<!-- Upcoming Deadlines -->
@if($upcomingDeadlines->isNotEmpty())
<div class="card">
    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Upcoming Deadlines</h3>
    <div class="space-y-3">
        @foreach($upcomingDeadlines as $enrollment)
            <div class="flex items-center justify-between p-3 bg-orange-50 dark:bg-orange-900/20 rounded-lg border border-orange-200 dark:border-orange-800">
                <div class="flex-1">
                    <p class="font-medium text-gray-900 dark:text-white">{{ $enrollment->course->title }}</p>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Due {{ $enrollment->deadline->format('M d, Y') }} ({{ $enrollment->deadline->diffForHumans() }})
                    </p>
                    <div class="flex items-center mt-2">
                        <div class="flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-2 mr-2">
                            <div class="bg-orange-600 h-2 rounded-full" style="width: {{ $enrollment->progress_percentage }}%"></div>
                        </div>
                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ number_format($enrollment->progress_percentage, 0) }}%</span>
                    </div>
                </div>
                <a href="{{ route('enrollments.show', $enrollment) }}" class="ml-4 btn btn-sm btn-primary">
                    Continue
                </a>
            </div>
        @endforeach
    </div>
</div>
@endif
