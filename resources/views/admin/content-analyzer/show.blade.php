<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Content Analysis') }} - {{ $course->title }}
            </h2>
            <div class="flex gap-2">
                <a href="{{ route('admin.courses.show', $course) }}" class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                    {{ __('Back to Course') }}
                </a>
                @if($analysis)
                    <form action="{{ route('admin.content-analyzer.re-analyze', $course) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700">
                            {{ __('Re-Analyze') }}
                        </button>
                    </form>
                @else
                    <form action="{{ route('admin.content-analyzer.analyze', $course) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700">
                            {{ __('Analyze Content') }}
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            @if(session('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('error') }}</span>
                </div>
            @endif

            @if($analysis)
                <!-- Overall Score Card -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                            {{ __('Overall Quality Score') }}
                        </h3>
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <div class="flex items-center gap-4">
                                    <div class="text-6xl font-bold {{ $analysis->getScoreGrade() === 'A' ? 'text-green-600' : ($analysis->getScoreGrade() === 'B' ? 'text-blue-600' : ($analysis->getScoreGrade() === 'C' ? 'text-yellow-600' : 'text-red-600')) }}">
                                        {{ number_format($analysis->overall_score, 1) }}
                                    </div>
                                    <div>
                                        <div class="text-3xl font-bold text-gray-700 dark:text-gray-300">
                                            {{ $analysis->getScoreGrade() }}
                                        </div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">
                                            {{ __('Grade') }}
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-4 w-full bg-gray-200 rounded-full h-4 dark:bg-gray-700">
                                    <div class="bg-blue-600 h-4 rounded-full" style="width: {{ $analysis->overall_score }}%"></div>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ __('Analyzed') }}
                                </div>
                                <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                    {{ $analysis->analyzed_at->diffForHumans() }}
                                </div>
                                <div class="text-xs text-gray-400 dark:text-gray-500">
                                    {{ $analysis->analyzed_at->format('M d, Y H:i') }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Score Breakdown -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Readability Score -->
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">
                                {{ __('Readability') }}
                            </h4>
                            <div class="text-3xl font-bold text-gray-900 dark:text-gray-100">
                                {{ number_format($analysis->readability_score, 1) }}
                            </div>
                            <div class="mt-2 w-full bg-gray-200 rounded-full h-2 dark:bg-gray-700">
                                <div class="bg-green-600 h-2 rounded-full" style="width: {{ $analysis->readability_score }}%"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Engagement Score -->
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">
                                {{ __('Engagement') }}
                            </h4>
                            <div class="text-3xl font-bold text-gray-900 dark:text-gray-100">
                                {{ number_format($analysis->engagement_score, 1) }}
                            </div>
                            <div class="mt-2 w-full bg-gray-200 rounded-full h-2 dark:bg-gray-700">
                                <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $analysis->engagement_score }}%"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Complexity Level -->
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">
                                {{ __('Complexity') }}
                            </h4>
                            <div class="text-2xl font-bold text-gray-900 dark:text-gray-100 capitalize">
                                {{ $analysis->complexity_level ?? 'N/A' }}
                            </div>
                            <div class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                                {{ __('Target Level') }}: {{ ucfirst($course->difficulty_level) }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Content Gaps -->
                @if($analysis->hasGaps())
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                                {{ __('Content Gaps Identified') }}
                            </h3>
                            
                            @if(!empty($analysis->content_gaps['missing_topics']))
                                <div class="mb-4">
                                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        {{ __('Missing Topics') }}
                                    </h4>
                                    <ul class="list-disc list-inside space-y-1">
                                        @foreach($analysis->content_gaps['missing_topics'] as $topic)
                                            <li class="text-sm text-gray-600 dark:text-gray-400">{{ $topic }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            @if(!empty($analysis->content_gaps['progression_gaps']))
                                <div class="mb-4">
                                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        {{ __('Progression Gaps') }}
                                    </h4>
                                    <ul class="list-disc list-inside space-y-1">
                                        @foreach($analysis->content_gaps['progression_gaps'] as $gap)
                                            <li class="text-sm text-gray-600 dark:text-gray-400">{{ $gap }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            @if(!empty($analysis->content_gaps['needs_more_depth']))
                                <div class="mb-4">
                                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        {{ __('Topics Needing More Depth') }}
                                    </h4>
                                    <ul class="list-disc list-inside space-y-1">
                                        @foreach($analysis->content_gaps['needs_more_depth'] as $topic)
                                            <li class="text-sm text-gray-600 dark:text-gray-400">{{ $topic }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            @if(!empty($analysis->content_gaps['missing_prerequisites']))
                                <div>
                                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        {{ __('Missing Prerequisites') }}
                                    </h4>
                                    <ul class="list-disc list-inside space-y-1">
                                        @foreach($analysis->content_gaps['missing_prerequisites'] as $prereq)
                                            <li class="text-sm text-gray-600 dark:text-gray-400">{{ $prereq }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                <!-- Suggestions -->
                @if(!empty($analysis->suggestions))
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                                {{ __('Improvement Suggestions') }}
                            </h3>
                            <div class="space-y-4">
                                @foreach($analysis->suggestions as $suggestion)
                                    <div class="border-l-4 {{ $suggestion['priority'] === 'high' ? 'border-red-500' : ($suggestion['priority'] === 'medium' ? 'border-yellow-500' : 'border-blue-500') }} pl-4 py-2">
                                        <div class="flex items-start justify-between">
                                            <div class="flex-1">
                                                <div class="flex items-center gap-2 mb-1">
                                                    <span class="text-xs font-semibold px-2 py-1 rounded {{ $suggestion['priority'] === 'high' ? 'bg-red-100 text-red-800' : ($suggestion['priority'] === 'medium' ? 'bg-yellow-100 text-yellow-800' : 'bg-blue-100 text-blue-800') }}">
                                                        {{ ucfirst($suggestion['priority']) }}
                                                    </span>
                                                    <span class="text-xs text-gray-500 dark:text-gray-400 capitalize">
                                                        {{ $suggestion['category'] }}
                                                    </span>
                                                </div>
                                                <p class="text-sm text-gray-700 dark:text-gray-300">
                                                    {{ $suggestion['suggestion'] }}
                                                </p>
                                                @if(isset($suggestion['impact']))
                                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                        <span class="font-medium">{{ __('Impact') }}:</span> {{ $suggestion['impact'] }}
                                                    </p>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Accessibility Issues -->
                @if($analysis->hasAccessibilityIssues())
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                                {{ __('Accessibility Issues') }}
                            </h3>
                            <div class="space-y-3">
                                @foreach($analysis->accessibility_issues as $issue)
                                    <div class="flex items-start gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                        <div class="flex-shrink-0">
                                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-full {{ $issue['severity'] === 'high' ? 'bg-red-100 text-red-600' : 'bg-yellow-100 text-yellow-600' }}">
                                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                                </svg>
                                            </span>
                                        </div>
                                        <div class="flex-1">
                                            <div class="flex items-center gap-2 mb-1">
                                                <span class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">
                                                    {{ $issue['type'] }}
                                                </span>
                                                @if(isset($issue['lesson']))
                                                    <span class="text-xs text-gray-400 dark:text-gray-500">
                                                        • {{ $issue['lesson'] }}
                                                    </span>
                                                @endif
                                            </div>
                                            <p class="text-sm text-gray-700 dark:text-gray-300">
                                                {{ $issue['description'] }}
                                            </p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif

            @else
                <!-- No Analysis Yet -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-12 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">
                            {{ __('No Analysis Available') }}
                        </h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            {{ __('Click the "Analyze Content" button to start analyzing this course.') }}
                        </p>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
