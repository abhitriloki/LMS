<x-app-layout>
    <div class="flex h-screen overflow-hidden bg-gray-50 dark:bg-gray-900">
        <!-- Lesson Navigation Sidebar -->
        <div class="hidden lg:flex lg:flex-shrink-0 w-80">
            <x-lesson-navigation 
                :modules="$modules" 
                :currentLesson="$lesson" 
                :enrollment="$enrollment" 
            />
        </div>
        
        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Lesson Header -->
            <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 px-6 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center space-x-3">
                            <!-- Mobile Menu Toggle -->
                            <button 
                                @click="$dispatch('toggle-sidebar')"
                                class="lg:hidden p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700"
                            >
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                                </svg>
                            </button>
                            
                            <div>
                                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                                    {{ $lesson->title }}
                                </h1>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                    {{ $course->title }} • {{ $lesson->module->title }}
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="flex items-center space-x-3">
                        @if($lesson->is_downloadable)
                            <button 
                                @click="downloadContent()"
                                class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600"
                            >
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                </svg>
                                Download
                            </button>
                        @endif
                        
                        @if($previousLesson)
                            <a 
                                href="{{ route('lessons.show', $previousLesson->id) }}"
                                class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600"
                            >
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                                </svg>
                                Previous
                            </a>
                        @endif
                        
                        @if($nextLesson)
                            <a 
                                href="{{ route('lessons.show', $nextLesson->id) }}"
                                class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700"
                            >
                                Next
                                <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </a>
                        @else
                            <button 
                                @click="completeCourse()"
                                class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg text-sm font-medium hover:bg-green-700"
                            >
                                <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                                Complete Course
                            </button>
                        @endif
                    </div>
                </div>
            </div>
            
            <!-- Content Area -->
            <div class="flex-1 overflow-auto">
                @if($lesson->description)
                    <div class="bg-blue-50 dark:bg-blue-900/20 border-b border-blue-100 dark:border-blue-900 px-6 py-4">
                        <p class="text-sm text-blue-900 dark:text-blue-100">
                            {{ $lesson->description }}
                        </p>
                    </div>
                @endif
                
                <div class="p-6">
                    @if($lesson->isVideo())
                        <x-video-player 
                            :videoUrl="$progress->content_url ?? $lesson->content_url"
                            :lessonId="$lesson->id"
                            :lastPosition="$progress->last_position ?? 0"
                        />
                    @elseif($lesson->isPdf())
                        <x-pdf-viewer 
                            :pdfUrl="$progress->content_url ?? $lesson->content_url"
                            :lessonId="$lesson->id"
                            :lastPosition="$progress->last_position ?? 1"
                        />
                    @elseif($lesson->isText())
                        <div class="prose dark:prose-invert max-w-none">
                            {!! $lesson->content_path ? Storage::get($lesson->content_path) : $lesson->content_url !!}
                        </div>
                        
                        <div class="mt-8 flex justify-end">
                            <button 
                                @click="markComplete()"
                                class="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700"
                            >
                                Mark as Complete
                            </button>
                        </div>
                    @else
                        <div class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">Content not available</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                This lesson content is not yet available.
                            </p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    
    @push('scripts')
    <script>
        async function downloadContent() {
            try {
                const response = await fetch(`/lessons/{{ $lesson->id }}/download`, {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                
                const data = await response.json();
                
                if (data.success) {
                    window.open(data.data.download_url, '_blank');
                } else {
                    alert(data.message);
                }
            } catch (error) {
                console.error('Download failed:', error);
                alert('Failed to download content');
            }
        }
        
        async function markComplete() {
            try {
                const response = await fetch(`/lessons/{{ $lesson->id }}/complete`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert('Lesson marked as complete! 🎉');
                    location.reload();
                }
            } catch (error) {
                console.error('Failed to mark complete:', error);
            }
        }
        
        function completeCourse() {
            if (confirm('Congratulations! You have completed all lessons. Mark this course as complete?')) {
                markComplete();
            }
        }
    </script>
    @endpush
</x-app-layout>
