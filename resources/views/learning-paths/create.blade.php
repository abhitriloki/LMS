<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Create Learning Path') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="mb-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Generate Your Personalized Learning Path</h3>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            Our AI will analyze your current skills and create an optimized learning path to help you reach your career goals.
                        </p>
                    </div>

                    @if(session('error'))
                        <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                            <span class="block sm:inline">{{ session('error') }}</span>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('learning-paths.store') }}" class="space-y-6">
                        @csrf

                        <div>
                            <label for="target_role" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Target Role <span class="text-red-500">*</span>
                            </label>
                            <input type="text" 
                                   name="target_role" 
                                   id="target_role" 
                                   value="{{ old('target_role') }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                   placeholder="e.g., Senior Developer, Project Manager, Data Analyst"
                                   required>
                            @error('target_role')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                Enter the role you want to work towards. Be specific for better results.
                            </p>
                        </div>

                        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-blue-800 dark:text-blue-300">
                                        How it works
                                    </h3>
                                    <div class="mt-2 text-sm text-blue-700 dark:text-blue-400">
                                        <ul class="list-disc list-inside space-y-1">
                                            <li>AI analyzes your completed courses and current skills</li>
                                            <li>Identifies skill gaps for your target role</li>
                                            <li>Creates an optimized sequence of courses</li>
                                            <li>Respects prerequisites and difficulty progression</li>
                                            <li>Provides milestones and estimated timeline</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                            <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-2">Your Current Profile</h4>
                            <dl class="grid grid-cols-1 gap-x-4 gap-y-2 sm:grid-cols-2">
                                <div>
                                    <dt class="text-xs text-gray-500 dark:text-gray-400">Current Role</dt>
                                    <dd class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ Auth::user()->position ?? 'Not set' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs text-gray-500 dark:text-gray-400">Department</dt>
                                    <dd class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ Auth::user()->department->name ?? 'Not set' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs text-gray-500 dark:text-gray-400">Completed Courses</dt>
                                    <dd class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ Auth::user()->enrollments()->where('status', 'completed')->count() }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs text-gray-500 dark:text-gray-400">In Progress</dt>
                                    <dd class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ Auth::user()->enrollments()->where('status', 'active')->count() }}</dd>
                                </div>
                            </dl>
                        </div>

                        <div class="flex items-center justify-between pt-4">
                            <a href="{{ route('learning-paths.index') }}" class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200">
                                Cancel
                            </a>
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                </svg>
                                Generate Learning Path
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
