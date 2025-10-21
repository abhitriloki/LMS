<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Enrollment Details') }}
            </h2>
            <div class="flex space-x-2">
                <a href="{{ route('admin.enrollments.edit', $enrollment) }}" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    Edit
                </a>
                <a href="{{ route('admin.enrollments.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    Back to List
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Info -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Enrollment Overview -->
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Enrollment Overview</h3>
                            
                            <div class="grid grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">User</label>
                                    <div class="flex items-center">
                                        @if($enrollment->user->avatar)
                                            <img class="h-10 w-10 rounded-full mr-3" src="{{ Storage::url($enrollment->user->avatar) }}" alt="{{ $enrollment->user->name }}">
                                        @else
                                            <div class="h-10 w-10 rounded-full bg-gray-300 dark:bg-gray-600 flex items-center justify-center mr-3">
                                                <span class="text-gray-600 dark:text-gray-300 font-medium">{{ substr($enrollment->user->name, 0, 1) }}</span>
                                            </div>
                                        @endif
                                        <div>
                                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $enrollment->user->name }}</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $enrollment->user->email }}</p>
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Course</label>
                                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $enrollment->course->title }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $enrollment->course->category->name ?? 'N/A' }}</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Enrollment Type</label>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                        @if($enrollment->enrollment_type === 'mandatory') bg-yellow-100 text-yellow-800
                                        @elseif($enrollment->enrollment_type === 'assigned') bg-blue-100 text-blue-800
                                        @else bg-gray-100 text-gray-800
                                        @endif">
                                        {{ ucfirst($enrollment->enrollment_type) }}
                                    </span>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Status</label>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                        @if($enrollment->status === 'completed') bg-green-100 text-green-800
                                        @elseif($enrollment->status === 'active') bg-blue-100 text-blue-800
                                        @elseif($enrollment->status === 'expired') bg-red-100 text-red-800
                                        @else bg-gray-100 text-gray-800
                                        @endif">
                                        {{ ucfirst($enrollment->status) }}
                                    </span>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Enrollment Date</label>
                                    <p class="text-sm text-gray-900 dark:text-gray-100">{{ $enrollment->enrollment_date->format('M d, Y') }}</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Deadline</label>
                                    @if($enrollment->deadline)
                                        <p class="text-sm text-gray-900 dark:text-gray-100 @if($enrollment->isOverdue()) text-red-600 dark:text-red-400 font-semibold @endif">
                                            {{ $enrollment->deadline->format('M d, Y') }}
                                            @if($enrollment->isOverdue())
                                                <span class="text-xs">(Overdue)</span>
                                            @endif
                                        </p>
                                    @else
                                        <p class="text-sm text-gray-400">No deadline</p>
                                    @endif
                                </div>

                                @if($enrollment->enrolledBy)
                                    <div>
                                        <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Enrolled By</label>
                                        <p class="text-sm text-gray-900 dark:text-gray-100">{{ $enrollment->enrolledBy->name }}</p>
                                    </div>
                                @endif

                                @if($enrollment->completion_date)
                                    <div>
                                        <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Completion Date</label>
                                        <p class="text-sm text-gray-900 dark:text-gray-100">{{ $enrollment->completion_date->format('M d, Y') }}</p>
                                    </div>
                                @endif

                                @if($enrollment->last_accessed_at)
                                    <div>
                                        <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Last Accessed</label>
                                        <p class="text-sm text-gray-900 dark:text-gray-100">{{ $enrollment->last_accessed_at->diffForHumans() }}</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Progress Details -->
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Progress Details</h3>
                            
                            <div class="mb-6">
                                <div class="flex justify-between items-center mb-2">
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Overall Progress</span>
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ number_format($enrollment->progress_percentage, 0) }}%</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-4 dark:bg-gray-700">
                                    <div class="bg-blue-600 h-4 rounded-full transition-all duration-300" style="width: {{ $enrollment->progress_percentage }}%"></div>
                                </div>
                            </div>

                            @if($enrollment->lessonProgress->count() > 0)
                                <div class="space-y-3">
                                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300">Lesson Progress</h4>
                                    @foreach($enrollment->lessonProgress as $progress)
                                        <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                            <div class="flex-1">
                                                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $progress->lesson->title }}</p>
                                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $progress->lesson->module->title }}</p>
                                            </div>
                                            <div class="flex items-center space-x-3">
                                                @if($progress->is_completed)
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                        Completed
                                                    </span>
                                                @else
                                                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ number_format($progress->progress_percentage, 0) }}%</span>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-sm text-gray-500 dark:text-gray-400">No lesson progress recorded yet.</p>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Quick Stats -->
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Quick Stats</h3>
                            
                            <div class="space-y-4">
                                <div>
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="text-sm text-gray-600 dark:text-gray-400">Lessons Completed</span>
                                        <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                                            {{ $enrollment->lessonProgress->where('is_completed', true)->count() }} / {{ $enrollment->course->getTotalLessonsCount() }}
                                        </span>
                                    </div>
                                </div>

                                @if($enrollment->final_score)
                                    <div>
                                        <div class="flex items-center justify-between mb-1">
                                            <span class="text-sm text-gray-600 dark:text-gray-400">Final Score</span>
                                            <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ number_format($enrollment->final_score, 0) }}%</span>
                                        </div>
                                    </div>
                                @endif

                                @if($enrollment->certificate)
                                    <div>
                                        <div class="flex items-center justify-between mb-1">
                                            <span class="text-sm text-gray-600 dark:text-gray-400">Certificate</span>
                                            <a href="#" class="text-sm font-semibold text-blue-600 hover:text-blue-800">View</a>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Actions</h3>
                            
                            <div class="space-y-2">
                                <a href="{{ route('catalog.show', $enrollment->course->slug) }}" class="block w-full text-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                    View Course
                                </a>
                                
                                <a href="{{ route('admin.enrollments.edit', $enrollment) }}" class="block w-full text-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                    Edit Enrollment
                                </a>
                                
                                <form action="{{ route('admin.enrollments.destroy', $enrollment) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this enrollment?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="block w-full text-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 focus:bg-red-700 active:bg-red-900 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                        Delete Enrollment
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
