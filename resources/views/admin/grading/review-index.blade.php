<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('AI Grading Review Queue') }}
            </h2>
            <a href="{{ route('admin.grading.statistics') }}" class="btn btn-secondary">
                View Statistics
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Filters -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <form method="GET" action="{{ route('admin.grading.review.index') }}" class="flex gap-4">
                        <div class="flex-1">
                            <label for="assessment_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Filter by Assessment
                            </label>
                            <select name="assessment_id" id="assessment_id" class="form-select w-full">
                                <option value="">All Assessments</option>
                                @foreach($assessments as $assessment)
                                    <option value="{{ $assessment->id }}" {{ request('assessment_id') == $assessment->id ? 'selected' : '' }}>
                                        {{ $assessment->title }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="flex-1">
                            <label for="max_confidence" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Max Confidence Score
                            </label>
                            <select name="max_confidence" id="max_confidence" class="form-select w-full">
                                <option value="">All</option>
                                <option value="0.5" {{ request('max_confidence') == '0.5' ? 'selected' : '' }}>≤ 0.5 (Very Low)</option>
                                <option value="0.6" {{ request('max_confidence') == '0.6' ? 'selected' : '' }}>≤ 0.6 (Low)</option>
                                <option value="0.7" {{ request('max_confidence') == '0.7' ? 'selected' : '' }}>≤ 0.7 (Medium)</option>
                            </select>
                        </div>

                        <div class="flex items-end">
                            <button type="submit" class="btn btn-primary">
                                Apply Filters
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Review Queue -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    @if($flaggedGradings->isEmpty())
                        <div class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">No responses pending review</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">All AI-graded responses have been reviewed.</p>
                        </div>
                    @else
                        <div class="space-y-4">
                            @foreach($flaggedGradings as $grading)
                                @php
                                    $response = $grading->attemptResponse;
                                    $attempt = $response->attempt;
                                    $question = $response->question;
                                @endphp
                                
                                <div class="border dark:border-gray-700 rounded-lg p-4 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                                    <div class="flex justify-between items-start">
                                        <div class="flex-1">
                                            <div class="flex items-center gap-3 mb-2">
                                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                                    {{ $attempt->assessment->title }}
                                                </h3>
                                                <span class="px-2 py-1 text-xs rounded-full 
                                                    {{ $grading->confidence_score < 0.5 ? 'bg-red-100 text-red-800' : 
                                                       ($grading->confidence_score < 0.7 ? 'bg-yellow-100 text-yellow-800' : 'bg-blue-100 text-blue-800') }}">
                                                    Confidence: {{ number_format($grading->confidence_score * 100, 0) }}%
                                                </span>
                                            </div>

                                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                                                Student: <span class="font-medium">{{ $attempt->user->name }}</span>
                                            </p>

                                            <p class="text-sm text-gray-700 dark:text-gray-300 mb-2">
                                                <strong>Question:</strong> {{ Str::limit($question->question_text, 100) }}
                                            </p>

                                            <div class="flex items-center gap-4 text-sm">
                                                <span class="text-gray-600 dark:text-gray-400">
                                                    AI Score: <span class="font-semibold">{{ $grading->ai_score }}/{{ $question->points }}</span>
                                                </span>
                                                <span class="text-gray-600 dark:text-gray-400">
                                                    Submitted: {{ $attempt->submitted_at->diffForHumans() }}
                                                </span>
                                                @if($grading->review_reason)
                                                    <span class="text-orange-600 dark:text-orange-400">
                                                        Reason: {{ $grading->review_reason }}
                                                    </span>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="flex gap-2">
                                            <a href="{{ route('admin.grading.review.show', $grading) }}" 
                                               class="btn btn-primary btn-sm">
                                                Review
                                            </a>
                                            <form method="POST" action="{{ route('admin.grading.review.accept', $grading) }}" class="inline">
                                                @csrf
                                                <button type="submit" class="btn btn-secondary btn-sm"
                                                        onclick="return confirm('Accept AI grading without changes?')">
                                                    Accept
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-6">
                            {{ $flaggedGradings->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
