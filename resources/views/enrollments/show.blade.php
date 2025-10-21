<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Enroll in Course') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <!-- Course Header -->
                    <div class="flex items-start space-x-6 mb-8">
                        @if($course->thumbnail)
                            <img src="{{ Storage::url($course->thumbnail) }}" alt="{{ $course->title }}" class="w-48 h-32 object-cover rounded-lg">
                        @else
                            <div class="w-48 h-32 bg-gradient-to-br from-blue-500 to-purple-600 rounded-lg"></div>
                        @endif
                        
                        <div class="flex-1">
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-2">
                                {{ $course->title }}
                            </h1>
                            <p class="text-gray-600 dark:text-gray-400 mb-4">
                                {{ $course->short_description }}
                            </p>
                            
                            <div class="flex items-center space-x-4 text-sm text-gray-500 dark:text-gray-400">
                                @if($course->category)
                                    <span class="inline-flex items-center">
                                        <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                        </svg>
                                        {{ $course->category->name }}
                                    </span>
                                @endif
                                
                                <span class="inline-flex items-center">
                                    <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    {{ $course->estimated_duration }} minutes
                                </span>
                                
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                    @if($course->difficulty_level === 'beginner') bg-green-100 text-green-800
                                    @elseif($course->difficulty_level === 'intermediate') bg-yellow-100 text-yellow-800
                                    @else bg-red-100 text-red-800
                                    @endif">
                                    {{ ucfirst($course->difficulty_level) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Prerequisites Check -->
                    @if($course->hasPrerequisites())
                        <div class="mb-8">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Prerequisites</h3>
                            
                            @if($canEnroll)
                                <div class="bg-green-50 dark:bg-green-900/20 border-l-4 border-green-400 p-4">
                                    <div class="flex">
                                        <div class="flex-shrink-0">
                                            <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                        <div class="ml-3">
                                            <p class="text-sm text-green-700 dark:text-green-300">
                                                You have completed all prerequisite courses.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div class="bg-red-50 dark:bg-red-900/20 border-l-4 border-red-400 p-4 mb-4">
                                    <div class="flex">
                                        <div class="flex-shrink-0">
                                            <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                        <div class="ml-3">
                                            <p class="text-sm text-red-700 dark:text-red-300">
                                                You must complete the following prerequisite courses before enrolling:
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div class="space-y-3">
                                    @foreach($missingPrerequisites as $prerequisite)
                                        <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                            <div>
                                                <h4 class="font-medium text-gray-900 dark:text-gray-100">{{ $prerequisite->title }}</h4>
                                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $prerequisite->short_description }}</p>
                                            </div>
                                            <a href="{{ route('catalog.show', $prerequisite->slug) }}" class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                                View Course
                                            </a>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endif

                    <!-- Course Details -->
                    <div class="mb-8">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">What You'll Learn</h3>
                        @if($course->learning_objectives && count($course->learning_objectives) > 0)
                            <ul class="space-y-2">
                                @foreach($course->learning_objectives as $objective)
                                    <li class="flex items-start">
                                        <svg class="h-5 w-5 text-green-500 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                        </svg>
                                        <span class="text-gray-700 dark:text-gray-300">{{ $objective }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <p class="text-gray-600 dark:text-gray-400">{{ $course->description }}</p>
                        @endif
                    </div>

                    <!-- Course Content Overview -->
                    @if($course->modules->count() > 0)
                        <div class="mb-8">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Course Content</h3>
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                                <div class="flex items-center justify-between text-sm text-gray-600 dark:text-gray-400">
                                    <span>{{ $course->modules->count() }} modules</span>
                                    <span>{{ $course->getTotalLessonsCount() }} lessons</span>
                                    <span>{{ floor($course->getTotalDuration() / 60) }} hours total</span>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Enrollment Actions -->
                    <div class="flex items-center justify-between pt-6 border-t border-gray-200 dark:border-gray-700">
                        <a href="{{ route('catalog.show', $course->slug) }}" class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100">
                            ← Back to course details
                        </a>
                        
                        @if($canEnroll)
                            <form action="{{ route('enrollments.store', $course) }}" method="POST">
                                @csrf
                                <button type="submit" class="inline-flex items-center px-6 py-3 bg-blue-600 border border-transparent rounded-md font-semibold text-sm text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                    Enroll Now
                                </button>
                            </form>
                        @else
                            <button disabled class="inline-flex items-center px-6 py-3 bg-gray-400 border border-transparent rounded-md font-semibold text-sm text-white uppercase tracking-widest cursor-not-allowed">
                                Complete Prerequisites First
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
