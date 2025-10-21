@props([
    'modules',
    'currentLesson',
    'enrollment',
])

<div x-data="lessonNavigation()" class="lesson-navigation bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 h-full overflow-y-auto">
    <!-- Course Progress Header -->
    <div class="p-4 border-b border-gray-200 dark:border-gray-700">
        <div class="mb-2">
            <div class="flex justify-between items-center mb-1">
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Course Progress</span>
                <span class="text-sm font-semibold text-blue-600 dark:text-blue-400" x-text="overallProgress + '%'"></span>
            </div>
            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                <div 
                    class="bg-blue-600 h-2 rounded-full transition-all duration-300"
                    :style="'width: ' + overallProgress + '%'"
                ></div>
            </div>
        </div>
        <p class="text-xs text-gray-500 dark:text-gray-400">
            <span x-text="completedLessons"></span> of <span x-text="totalLessons"></span> lessons completed
        </p>
    </div>
    
    <!-- Module List -->
    <div class="p-2">
        @foreach($modules as $module)
            <div class="mb-2" x-data="{ expanded: {{ $module->lessons->contains('id', $currentLesson->id) ? 'true' : 'false' }} }">
                <!-- Module Header -->
                <button 
                    @click="expanded = !expanded"
                    class="w-full flex items-center justify-between p-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                >
                    <div class="flex items-center space-x-3 flex-1 text-left">
                        <div class="flex-shrink-0">
                            <svg 
                                class="w-5 h-5 text-gray-400 transition-transform"
                                :class="{ 'rotate-90': expanded }"
                                fill="none" 
                                stroke="currentColor" 
                                viewBox="0 0 24 24"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white truncate">
                                {{ $module->title }}
                            </h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $module->lessons->count() }} lessons
                            </p>
                        </div>
                    </div>
                    
                    <!-- Module Progress Badge -->
                    @php
                        $moduleProgress = $module->lessons->filter(function($lesson) use ($enrollment) {
                            return $lesson->progress->where('enrollment_id', $enrollment->id)->where('status', 'completed')->isNotEmpty();
                        })->count();
                        $moduleTotal = $module->lessons->count();
                        $modulePercentage = $moduleTotal > 0 ? round(($moduleProgress / $moduleTotal) * 100) : 0;
                    @endphp
                    
                    @if($modulePercentage === 100)
                        <span class="flex-shrink-0 inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            Complete
                        </span>
                    @elseif($moduleProgress > 0)
                        <span class="flex-shrink-0 inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                            {{ $moduleProgress }}/{{ $moduleTotal }}
                        </span>
                    @endif
                </button>
                
                <!-- Lesson List -->
                <div 
                    x-show="expanded"
                    x-collapse
                    class="ml-8 mt-1 space-y-1"
                >
                    @foreach($module->lessons as $lesson)
                        @php
                            $lessonProgress = $lesson->progress->where('enrollment_id', $enrollment->id)->first();
                            $isCompleted = $lessonProgress && $lessonProgress->status === 'completed';
                            $isInProgress = $lessonProgress && $lessonProgress->status === 'in_progress';
                            $isCurrent = $lesson->id === $currentLesson->id;
                        @endphp
                        
                        <a 
                            href="{{ route('lessons.show', $lesson->id) }}"
                            class="flex items-center space-x-3 p-2 rounded-lg transition-colors group {{ $isCurrent ? 'bg-blue-50 dark:bg-blue-900/20' : 'hover:bg-gray-50 dark:hover:bg-gray-700' }}"
                        >
                            <!-- Status Icon -->
                            <div class="flex-shrink-0">
                                @if($isCompleted)
                                    <div class="w-6 h-6 rounded-full bg-green-500 flex items-center justify-center">
                                        <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                        </svg>
                                    </div>
                                @elseif($isInProgress)
                                    <div class="w-6 h-6 rounded-full border-2 border-blue-500 flex items-center justify-center">
                                        <div class="w-2 h-2 rounded-full bg-blue-500"></div>
                                    </div>
                                @else
                                    <div class="w-6 h-6 rounded-full border-2 border-gray-300 dark:border-gray-600"></div>
                                @endif
                            </div>
                            
                            <!-- Lesson Info -->
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium truncate {{ $isCurrent ? 'text-blue-600 dark:text-blue-400' : 'text-gray-900 dark:text-white' }}">
                                    {{ $lesson->title }}
                                </p>
                                <div class="flex items-center space-x-2 text-xs text-gray-500 dark:text-gray-400">
                                    <!-- Content Type Icon -->
                                    @if($lesson->content_type === 'video')
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M2 6a2 2 0 012-2h6a2 2 0 012 2v8a2 2 0 01-2 2H4a2 2 0 01-2-2V6zM14.553 7.106A1 1 0 0014 8v4a1 1 0 00.553.894l2 1A1 1 0 0018 13V7a1 1 0 00-1.447-.894l-2 1z"></path>
                                        </svg>
                                        <span>Video</span>
                                    @elseif($lesson->content_type === 'pdf')
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"></path>
                                        </svg>
                                        <span>PDF</span>
                                    @elseif($lesson->content_type === 'text')
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"></path>
                                        </svg>
                                        <span>Text</span>
                                    @endif
                                    
                                    @if($lesson->duration)
                                        <span>•</span>
                                        <span>{{ gmdate('i:s', $lesson->duration) }}</span>
                                    @endif
                                </div>
                            </div>
                            
                            <!-- Current Indicator -->
                            @if($isCurrent)
                                <div class="flex-shrink-0">
                                    <div class="w-2 h-2 rounded-full bg-blue-600 animate-pulse"></div>
                                </div>
                            @endif
                        </a>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('lessonNavigation', () => ({
        overallProgress: {{ $enrollment->progress_percentage ?? 0 }},
        completedLessons: {{ $enrollment->lessonProgress()->where('status', 'completed')->count() }},
        totalLessons: {{ $modules->sum(fn($m) => $m->lessons->count()) }},
        
        init() {
            // Listen for progress updates
            window.addEventListener('progress-updated', (event) => {
                this.updateProgress();
            });
            
            window.addEventListener('lesson-completed', (event) => {
                this.updateProgress();
            });
        },
        
        updateProgress() {
            // Reload progress data
            fetch(window.location.href, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.text())
            .then(html => {
                // Parse and update progress values
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                
                // Update progress bar (you might want to implement a more sophisticated update)
                location.reload();
            })
            .catch(error => {
                console.error('Failed to update progress:', error);
            });
        }
    }));
});
</script>
@endpush
