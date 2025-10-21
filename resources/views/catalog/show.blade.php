@extends('layouts.app')

@section('title', $course->title)

@section('content')
<div class="max-w-7xl mx-auto">
    <!-- Course Header -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden mb-6">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 p-6">
            <!-- Left Column - Course Info -->
            <div class="lg:col-span-2">
                <!-- Breadcrumb -->
                <nav class="flex mb-4 text-sm">
                    <a href="{{ route('catalog.index') }}" class="text-primary-600 hover:text-primary-700 dark:text-primary-400">
                        Catalog
                    </a>
                    <span class="mx-2 text-gray-500">/</span>
                    @if($course->category)
                        <a href="{{ route('catalog.index', ['category' => $course->category->id]) }}" class="text-primary-600 hover:text-primary-700 dark:text-primary-400">
                            {{ $course->category->name }}
                        </a>
                        <span class="mx-2 text-gray-500">/</span>
                    @endif
                    <span class="text-gray-500 dark:text-gray-400">{{ $course->title }}</span>
                </nav>

                <!-- Title -->
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">
                    {{ $course->title }}
                </h1>

                <!-- Short Description -->
                @if($course->short_description)
                    <p class="text-lg text-gray-600 dark:text-gray-400 mb-4">
                        {{ $course->short_description }}
                    </p>
                @endif

                <!-- Meta Info -->
                <div class="flex flex-wrap gap-4 mb-6">
                    <div class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                        <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>{{ $course->estimated_duration }} minutes</span>
                    </div>

                    <div class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                        <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        <span class="px-2 py-1 rounded-full text-xs font-semibold
                            @if($course->difficulty_level === 'beginner') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                            @elseif($course->difficulty_level === 'intermediate') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                            @else bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                            @endif">
                            {{ ucfirst($course->difficulty_level) }}
                        </span>
                    </div>

                    @if($course->creator)
                        <div class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                            <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            <span>{{ $course->creator->name }}</span>
                        </div>
                    @endif
                </div>

                <!-- Tags -->
                @if($course->tags && count($course->tags) > 0)
                    <div class="flex flex-wrap gap-2 mb-6">
                        @foreach($course->tags as $tag)
                            <span class="px-3 py-1 text-sm bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-full">
                                {{ $tag }}
                            </span>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Right Column - Enrollment Card -->
            <div class="lg:col-span-1">
                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6 sticky top-6">
                    @if($course->thumbnail)
                        <img 
                            src="{{ Storage::url($course->thumbnail) }}" 
                            alt="{{ $course->title }}"
                            class="w-full h-48 object-cover rounded-lg mb-4"
                        >
                    @endif

                    @if($isEnrolled)
                        <div class="mb-4 p-3 bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 rounded-lg text-sm text-center">
                            ✓ You are enrolled in this course
                        </div>
                        <a 
                            href="{{ route('enrollments.index') }}" 
                            class="block w-full text-center px-4 py-3 bg-primary-600 hover:bg-primary-700 text-white font-semibold rounded-lg transition"
                        >
                            Continue Learning
                        </a>
                    @else
                        @auth
                            <a 
                                href="{{ route('enrollments.show', $course) }}"
                                class="block w-full text-center px-4 py-3 bg-primary-600 hover:bg-primary-700 text-white font-semibold rounded-lg transition"
                            >
                                Enroll Now
                            </a>
                        @else
                            <a 
                                href="{{ route('login') }}" 
                                class="block w-full text-center px-4 py-3 bg-primary-600 hover:bg-primary-700 text-white font-semibold rounded-lg transition"
                            >
                                Login to Enroll
                            </a>
                        @endauth
                    @endif

                    <div class="mt-4 space-y-2 text-sm text-gray-600 dark:text-gray-400">
                        <div class="flex justify-between">
                            <span>Modules:</span>
                            <span class="font-semibold">{{ $course->modules->count() }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Lessons:</span>
                            <span class="font-semibold">{{ $course->getTotalLessonsCount() }}</span>
                        </div>
                        @if($course->assessments->count() > 0)
                            <div class="flex justify-between">
                                <span>Assessments:</span>
                                <span class="font-semibold">{{ $course->assessments->count() }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Course Content Tabs -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden" x-data="{ tab: 'overview' }">
        <!-- Tab Navigation -->
        <div class="border-b border-gray-200 dark:border-gray-700">
            <nav class="flex -mb-px">
                <button 
                    @click="tab = 'overview'"
                    :class="tab === 'overview' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'"
                    class="px-6 py-4 border-b-2 font-medium text-sm transition"
                >
                    Overview
                </button>
                <button 
                    @click="tab = 'curriculum'"
                    :class="tab === 'curriculum' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'"
                    class="px-6 py-4 border-b-2 font-medium text-sm transition"
                >
                    Curriculum
                </button>
                @if($course->learning_objectives && count($course->learning_objectives) > 0)
                    <button 
                        @click="tab = 'objectives'"
                        :class="tab === 'objectives' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'"
                        class="px-6 py-4 border-b-2 font-medium text-sm transition"
                    >
                        Learning Objectives
                    </button>
                @endif
            </nav>
        </div>

        <!-- Tab Content -->
        <div class="p-6">
            <!-- Overview Tab -->
            <div x-show="tab === 'overview'" class="prose dark:prose-invert max-w-none">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">About This Course</h2>
                <div class="text-gray-700 dark:text-gray-300">
                    {!! nl2br(e($course->description)) !!}
                </div>

                @if($course->target_audience)
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mt-6 mb-3">Target Audience</h3>
                    <p class="text-gray-700 dark:text-gray-300">{{ $course->target_audience }}</p>
                @endif

                @if($course->hasPrerequisites())
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mt-6 mb-3">Prerequisites</h3>
                    <ul class="list-disc list-inside text-gray-700 dark:text-gray-300">
                        @foreach($course->prerequisites as $prerequisite)
                            <li>{{ $prerequisite }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>

            <!-- Curriculum Tab -->
            <div x-show="tab === 'curriculum'">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Course Curriculum</h2>
                <div class="space-y-4">
                    @foreach($course->modules as $module)
                        <div class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                            <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3">
                                <h3 class="font-semibold text-gray-900 dark:text-white">
                                    {{ $module->title }}
                                </h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                    {{ $module->lessons->count() }} {{ Str::plural('lesson', $module->lessons->count()) }}
                                </p>
                            </div>
                            <ul class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($module->lessons as $lesson)
                                    <li class="px-4 py-3 flex items-center justify-between">
                                        <div class="flex items-center">
                                            <svg class="w-5 h-5 text-gray-400 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                @if($lesson->content_type === 'video')
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                @elseif($lesson->content_type === 'pdf')
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                                @else
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                @endif
                                            </svg>
                                            <span class="text-gray-700 dark:text-gray-300">{{ $lesson->title }}</span>
                                        </div>
                                        @if($lesson->duration)
                                            <span class="text-sm text-gray-500 dark:text-gray-400">
                                                {{ $lesson->duration }} min
                                            </span>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Learning Objectives Tab -->
            @if($course->learning_objectives && count($course->learning_objectives) > 0)
                <div x-show="tab === 'objectives'">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">What You'll Learn</h2>
                    <ul class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        @foreach($course->learning_objectives as $objective)
                            <li class="flex items-start">
                                <svg class="w-5 h-5 text-green-500 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                                <span class="text-gray-700 dark:text-gray-300">{{ $objective }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>

    <!-- Related Courses -->
    @if($relatedCourses->count() > 0)
        <div class="mt-8">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Related Courses</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                @foreach($relatedCourses as $relatedCourse)
                    <x-course-card :course="$relatedCourse" />
                @endforeach
            </div>
        </div>
    @endif
</div>
@endsection
