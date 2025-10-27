{{-- Admin Dashboard --}}

<!-- Stats Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
    <!-- Total Users -->
    <div class="card hover:shadow-lg transition-shadow duration-200">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600 dark:text-gray-400">Total Users</p>
                <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1">{{ number_format($stats['totalUsers']) }}</p>
            </div>
            <div class="p-3 bg-primary-100 dark:bg-primary-900 rounded-lg">
                <svg class="w-8 h-8 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                </svg>
            </div>
        </div>
    </div>

    <!-- Total Courses -->
    <div class="card hover:shadow-lg transition-shadow duration-200">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600 dark:text-gray-400">Total Courses</p>
                <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1">{{ number_format($stats['totalCourses']) }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">{{ $stats['publishedCourses'] }} published</p>
            </div>
            <div class="p-3 bg-green-100 dark:bg-green-900 rounded-lg">
                <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                </svg>
            </div>
        </div>
    </div>

    <!-- Active Enrollments -->
    <div class="card hover:shadow-lg transition-shadow duration-200">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600 dark:text-gray-400">Active Enrollments</p>
                <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1">{{ number_format($stats['activeEnrollments']) }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">{{ $stats['completedEnrollments'] }} completed</p>
            </div>
            <div class="p-3 bg-accent-100 dark:bg-accent-900 rounded-lg">
                <svg class="w-8 h-8 text-accent-600 dark:text-accent-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                </svg>
            </div>
        </div>
    </div>

    <!-- Completion Rate -->
    <div class="card hover:shadow-lg transition-shadow duration-200">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600 dark:text-gray-400">Completion Rate</p>
                <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1">{{ $stats['averageCompletionRate'] }}%</p>
                <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">{{ $stats['totalCertificates'] }} certificates</p>
            </div>
            <div class="p-3 bg-secondary-100 dark:bg-secondary-900 rounded-lg">
                <svg class="w-8 h-8 text-secondary-600 dark:text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <!-- Enrollment Trends Chart -->
    <div class="card">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Enrollment Trends (Last 30 Days)</h3>
        <div class="relative h-64">
            <canvas id="enrollmentTrendsChart" class="w-full h-full"></canvas>
        </div>
    </div>

    <!-- Top Courses -->
    <div class="card">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Top Courses by Enrollment</h3>
        <div class="space-y-3">
            @forelse($topCourses as $course)
                <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                    <div class="flex-1">
                        <p class="font-medium text-gray-900 dark:text-white">{{ $course->title }}</p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ $course->enrollments_count }} enrollments</p>
                    </div>
                    <a href="{{ route('admin.courses.show', $course) }}" class="text-primary-600 hover:text-primary-700 dark:text-primary-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </a>
                </div>
            @empty
                <p class="text-gray-500 dark:text-gray-400 text-center py-4">No courses available</p>
            @endforelse
        </div>
    </div>
</div>

<!-- Department Statistics -->
<div class="card mb-6">
    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Department Statistics</h3>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-800">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Department</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Users</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Enrollments</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Avg Progress</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($departmentStats as $dept)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">{{ $dept->name }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">{{ $dept->user_count }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">{{ $dept->enrollment_count }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                            <div class="flex items-center">
                                <div class="w-16 bg-gray-200 dark:bg-gray-700 rounded-full h-2 mr-2">
                                    <div class="bg-primary-600 h-2 rounded-full" style="width: {{ $dept->avg_progress ?? 0 }}%"></div>
                                </div>
                                <span>{{ number_format($dept->avg_progress ?? 0, 1) }}%</span>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">No department data available</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Recent Activity -->
<div class="card">
    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Recent Activity</h3>
    <div class="space-y-3">
        @forelse($recentActivity as $activity)
            <div class="flex items-start space-x-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                <div class="flex-shrink-0">
                    @if($activity->user && $activity->user->avatar)
                        <img src="{{ Storage::url($activity->user->avatar) }}" alt="{{ $activity->user->name }}" class="w-10 h-10 rounded-full">
                    @else
                        <div class="w-10 h-10 rounded-full bg-primary-100 dark:bg-primary-900 flex items-center justify-center">
                            <span class="text-primary-600 dark:text-primary-400 font-semibold">{{ substr($activity->user->name ?? 'U', 0, 1) }}</span>
                        </div>
                    @endif
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm text-gray-900 dark:text-white">
                        <span class="font-medium">{{ $activity->user->name ?? 'User' }}</span>
                        {{ $activity->event_type }}
                        @if($activity->course)
                            <span class="font-medium">{{ $activity->course->title }}</span>
                        @endif
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $activity->created_at->diffForHumans() }}</p>
                </div>
            </div>
        @empty
            <p class="text-gray-500 dark:text-gray-400 text-center py-4">No recent activity</p>
        @endforelse
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Enrollment Trends Chart
    const ctx = document.getElementById('enrollmentTrendsChart');
    if (ctx) {
        const data = @json($enrollmentTrends);
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.map(d => d.date),
                datasets: [{
                    label: 'Enrollments',
                    data: data.map(d => d.count),
                    borderColor: 'rgb(59, 130, 246)',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });
    }
});
</script>
@endpush
