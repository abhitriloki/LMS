@extends('layouts.dashboard')

@section('title', 'Course Recommendations')

@section('page-title', 'Course Recommendations')

@section('content')
<div class="mb-6">
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">AI-Powered Course Recommendations</h2>
            <p class="text-gray-600 dark:text-gray-400 mt-1">
                Personalized course suggestions based on your learning history and career goals
            </p>
        </div>
        <form action="{{ route('recommendations.generate') }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-primary">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                Generate New Recommendations
            </button>
        </form>
    </div>
</div>

@if($recommendations->isEmpty())
    <div class="card">
        <div class="text-center py-12">
            <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
            </svg>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">No Recommendations Yet</h3>
            <p class="text-gray-600 dark:text-gray-400 mb-4">
                Click the button above to generate personalized course recommendations based on your profile and learning history.
            </p>
        </div>
    </div>
@else
    <div class="grid grid-cols-1 gap-6">
        @foreach($recommendations as $recommendation)
            <div class="card hover:shadow-lg transition-shadow duration-200">
                <div class="flex flex-col md:flex-row gap-6">
                    <!-- Course Thumbnail -->
                    <div class="flex-shrink-0">
                        <img 
                            src="{{ $recommendation->course->thumbnail ? Storage::url($recommendation->course->thumbnail) : 'https://via.placeholder.com/300x200?text=Course' }}" 
                            alt="{{ $recommendation->course->title }}"
                            class="w-full md:w-48 h-32 object-cover rounded-lg"
                        >
                    </div>

                    <!-- Course Details -->
                    <div class="flex-grow">
                        <div class="flex items-start justify-between mb-2">
                            <div>
                                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-1">
                                    {{ $recommendation->course->title }}
                                </h3>
                                <div class="flex items-center gap-3 text-sm text-gray-600 dark:text-gray-400">
                                    <span class="inline-flex items-center">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                                        </svg>
                                        {{ $recommendation->course->category->name }}
                                    </span>
                                    <span class="inline-flex items-center">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        {{ $recommendation->course->estimated_duration }} hours
                                    </span>
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                                        @if($recommendation->course->difficulty_level === 'beginner') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                        @elseif($recommendation->course->difficulty_level === 'intermediate') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                                        @else bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                                        @endif">
                                        {{ ucfirst($recommendation->course->difficulty_level) }}
                                    </span>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-primary-100 text-primary-800 dark:bg-primary-900 dark:text-primary-200">
                                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                    </svg>
                                    {{ number_format($recommendation->relevance_score * 100, 0) }}% Match
                                </span>
                            </div>
                        </div>

                        <p class="text-gray-600 dark:text-gray-400 mb-3 line-clamp-2">
                            {{ $recommendation->course->short_description ?? $recommendation->course->description }}
                        </p>

                        <!-- AI Reasoning -->
                        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-3 mb-4">
                            <div class="flex items-start">
                                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mr-2 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <div>
                                    <p class="text-sm font-medium text-blue-900 dark:text-blue-200 mb-1">Why we recommend this:</p>
                                    <p class="text-sm text-blue-800 dark:text-blue-300">{{ $recommendation->reasoning }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center gap-3">
                            <a href="{{ route('catalog.show', $recommendation->course->slug) }}" class="btn btn-primary">
                                View Course
                            </a>
                            <form action="{{ route('recommendations.accept', $recommendation) }}" method="POST" class="inline">
                                @csrf
                                <input type="hidden" name="enroll" value="true">
                                <button type="submit" class="btn btn-secondary">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    Accept & Enroll
                                </button>
                            </form>
                            <form action="{{ route('recommendations.reject', $recommendation) }}" method="POST" class="inline" 
                                  onsubmit="return confirm('Are you sure you want to dismiss this recommendation?')">
                                @csrf
                                <button type="submit" class="btn btn-ghost text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                    Dismiss
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Pagination -->
    <div class="mt-6">
        {{ $recommendations->links() }}
    </div>
@endif
@endsection
