<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('User Details') }}
            </h2>
            <div class="flex gap-2">
                <a href="{{ route('admin.users.edit', $user) }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition">
                    {{ __('Edit User') }}
                </a>
                <a href="{{ route('admin.users.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition">
                    {{ __('Back to Users') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- User Profile Card -->
                <div class="lg:col-span-1">
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="text-center">
                                @if($user->avatar)
                                    <img src="{{ Storage::url($user->avatar) }}" alt="{{ $user->name }}" class="h-32 w-32 rounded-full object-cover mx-auto mb-4">
                                @else
                                    <div class="h-32 w-32 rounded-full bg-blue-600 flex items-center justify-center text-white text-4xl font-semibold mx-auto mb-4">
                                        {{ substr($user->name, 0, 1) }}
                                    </div>
                                @endif
                                
                                <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100">{{ $user->name }}</h3>
                                <p class="text-gray-600 dark:text-gray-400">{{ $user->email }}</p>
                                
                                <div class="mt-4">
                                    <span class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full 
                                        @if($user->role === 'super_admin') bg-purple-100 text-purple-800
                                        @elseif($user->role === 'admin') bg-red-100 text-red-800
                                        @elseif($user->role === 'instructor') bg-blue-100 text-blue-800
                                        @else bg-green-100 text-green-800
                                        @endif">
                                        {{ ucfirst(str_replace('_', ' ', $user->role)) }}
                                    </span>
                                </div>
                            </div>

                            <div class="mt-6 border-t border-gray-200 dark:border-gray-700 pt-6">
                                <dl class="space-y-4">
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Department</dt>
                                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $user->department?->name ?? 'N/A' }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Position</dt>
                                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $user->position ?? 'N/A' }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Phone</dt>
                                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $user->phone ?? 'N/A' }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Member Since</dt>
                                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $user->created_at->format('M d, Y') }}</dd>
                                    </div>
                                    @if($user->last_login_at)
                                        <div>
                                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Last Login</dt>
                                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $user->last_login_at->diffForHumans() }}</dd>
                                        </div>
                                    @endif
                                </dl>
                            </div>

                            @if($user->bio)
                                <div class="mt-6 border-t border-gray-200 dark:border-gray-700 pt-6">
                                    <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Bio</h4>
                                    <p class="text-sm text-gray-900 dark:text-gray-100">{{ $user->bio }}</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- User Activity -->
                <div class="lg:col-span-2">
                    <!-- Statistics -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Enrolled Courses</div>
                            <div class="mt-2 text-3xl font-semibold text-gray-900 dark:text-gray-100">{{ $user->enrollments->count() }}</div>
                        </div>
                        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Completed Courses</div>
                            <div class="mt-2 text-3xl font-semibold text-gray-900 dark:text-gray-100">
                                {{ $user->enrollments->where('status', 'completed')->count() }}
                            </div>
                        </div>
                        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Certificates</div>
                            <div class="mt-2 text-3xl font-semibold text-gray-900 dark:text-gray-100">{{ $user->certificates->count() }}</div>
                        </div>
                    </div>

                    <!-- Recent Enrollments -->
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Recent Enrollments</h3>
                            @if($user->enrollments->count() > 0)
                                <div class="space-y-4">
                                    @foreach($user->enrollments->take(5) as $enrollment)
                                        <div class="flex items-center justify-between border-b border-gray-200 dark:border-gray-700 pb-4 last:border-0">
                                            <div class="flex-1">
                                                <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                    {{ $enrollment->course->title }}
                                                </h4>
                                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                    Enrolled: {{ $enrollment->created_at->format('M d, Y') }}
                                                </p>
                                            </div>
                                            <div class="ml-4">
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full
                                                    @if($enrollment->status === 'completed') bg-green-100 text-green-800
                                                    @elseif($enrollment->status === 'in_progress') bg-blue-100 text-blue-800
                                                    @else bg-gray-100 text-gray-800
                                                    @endif">
                                                    {{ ucfirst($enrollment->status) }}
                                                </span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-gray-500 dark:text-gray-400">No enrollments yet.</p>
                            @endif
                        </div>
                    </div>

                    <!-- Certificates -->
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Certificates</h3>
                            @if($user->certificates->count() > 0)
                                <div class="space-y-4">
                                    @foreach($user->certificates->take(5) as $certificate)
                                        <div class="flex items-center justify-between border-b border-gray-200 dark:border-gray-700 pb-4 last:border-0">
                                            <div class="flex-1">
                                                <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                    {{ $certificate->course->title ?? 'Certificate' }}
                                                </h4>
                                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                    Issued: {{ $certificate->issued_at->format('M d, Y') }}
                                                </p>
                                            </div>
                                            <div class="ml-4">
                                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                                    #{{ $certificate->certificate_number }}
                                                </span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-gray-500 dark:text-gray-400">No certificates earned yet.</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
