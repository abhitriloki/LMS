<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Analysis History') }} - {{ $course->title }}
            </h2>
            <a href="{{ route('admin.content-analyzer.show', $course) }}" class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                {{ __('Back to Current Analysis') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    @if($analyses->count() > 0)
                        <div class="space-y-4">
                            @foreach($analyses as $analysis)
                                <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 {{ $analysis->is_current ? 'bg-blue-50 dark:bg-blue-900/20' : '' }}">
                                    <div class="flex items-center justify-between">
                                        <div class="flex-1">
                                            <div class="flex items-center gap-4">
                                                <div>
                                                    <div class="text-2xl font-bold {{ $analysis->getScoreGrade() === 'A' ? 'text-green-600' : ($analysis->getScoreGrade() === 'B' ? 'text-blue-600' : ($analysis->getScoreGrade() === 'C' ? 'text-yellow-600' : 'text-red-600')) }}">
                                                        {{ number_format($analysis->overall_score, 1) }}
                                                    </div>
                                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                                        {{ __('Grade') }}: {{ $analysis->getScoreGrade() }}
                                                    </div>
                                                </div>
                                                <div class="flex-1">
                                                    <div class="grid grid-cols-3 gap-4">
                                                        <div>
                                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                                {{ __('Readability') }}
                                                            </div>
                                                            <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                                {{ number_format($analysis->readability_score, 1) }}
                                                            </div>
                                                        </div>
                                                        <div>
                                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                                {{ __('Engagement') }}
                                                            </div>
                                                            <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                                {{ number_format($analysis->engagement_score, 1) }}
                                                            </div>
                                                        </div>
                                                        <div>
                                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                                {{ __('Issues') }}
                                                            </div>
                                                            <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                                {{ $analysis->getTotalIssues() }}
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            @if($analysis->is_current)
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                                    {{ __('Current') }}
                                                </span>
                                            @endif
                                            <div class="text-sm text-gray-900 dark:text-gray-100 mt-1">
                                                {{ $analysis->analyzed_at->format('M d, Y') }}
                                            </div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ $analysis->analyzed_at->format('H:i') }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-6">
                            {{ $analyses->links() }}
                        </div>
                    @else
                        <div class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">
                                {{ __('No Analysis History') }}
                            </h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                {{ __('No previous analyses found for this course.') }}
                            </p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
