@props([
    'videoUrl',
    'lessonId',
    'lastPosition' => 0,
    'autoplay' => false,
])

<div x-data="videoPlayer({
    videoUrl: '{{ $videoUrl }}',
    lessonId: {{ $lessonId }},
    lastPosition: {{ $lastPosition }},
    autoplay: {{ $autoplay ? 'true' : 'false' }}
})" x-init="init()" class="video-player-container">
    <video 
        x-ref="videoElement"
        class="video-js vjs-default-skin vjs-big-play-centered w-full"
        controls
        preload="auto"
        data-setup='{"fluid": true, "aspectRatio": "16:9"}'
    >
        <source :src="videoUrl" type="video/mp4">
        <p class="vjs-no-js">
            To view this video please enable JavaScript, and consider upgrading to a web browser that
            <a href="https://videojs.com/html5-video-support/" target="_blank">supports HTML5 video</a>
        </p>
    </video>

    <!-- Progress indicator -->
    <div x-show="isSaving" class="absolute top-4 right-4 bg-white dark:bg-gray-800 rounded-lg shadow-lg px-4 py-2">
        <div class="flex items-center space-x-2">
            <svg class="animate-spin h-4 w-4 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span class="text-sm text-gray-700 dark:text-gray-300">Saving progress...</span>
        </div>
    </div>
</div>

@push('styles')
<link href="https://vjs.zencdn.net/8.10.0/video-js.css" rel="stylesheet" />
<style>
    .video-player-container {
        position: relative;
        width: 100%;
    }
    
    .video-js {
        width: 100%;
        height: auto;
    }
    
    .vjs-big-play-button {
        border-radius: 50%;
        width: 2em;
        height: 2em;
        line-height: 2em;
        border: 0.06666em solid #fff;
        background-color: rgba(43, 51, 63, 0.7);
        font-size: 3.5em;
        left: 50%;
        top: 50%;
        margin-left: -1em;
        margin-top: -1em;
    }
    
    .vjs-big-play-button:hover {
        background-color: rgba(43, 51, 63, 0.9);
    }
</style>
@endpush

@push('scripts')
<script src="https://vjs.zencdn.net/8.10.0/video.min.js"></script>
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('videoPlayer', (config) => ({
        player: null,
        videoUrl: config.videoUrl,
        lessonId: config.lessonId,
        lastPosition: config.lastPosition,
        autoplay: config.autoplay,
        isSaving: false,
        progressUpdateInterval: null,
        bookmarkInterval: null,
        lastSavedProgress: 0,
        
        init() {
            this.$nextTick(() => {
                this.initializePlayer();
            });
        },
        
        initializePlayer() {
            const videoElement = this.$refs.videoElement;
            
            this.player = videojs(videoElement, {
                controls: true,
                autoplay: this.autoplay,
                preload: 'auto',
                fluid: true,
                aspectRatio: '16:9',
                playbackRates: [0.5, 0.75, 1, 1.25, 1.5, 2],
                controlBar: {
                    children: [
                        'playToggle',
                        'volumePanel',
                        'currentTimeDisplay',
                        'timeDivider',
                        'durationDisplay',
                        'progressControl',
                        'playbackRateMenuButton',
                        'qualitySelector',
                        'fullscreenToggle'
                    ]
                }
            });
            
            // Restore last position
            if (this.lastPosition > 0) {
                this.player.ready(() => {
                    this.player.currentTime(this.lastPosition);
                    this.showNotification('Resuming from where you left off');
                });
            }
            
            // Track progress
            this.player.on('timeupdate', () => {
                this.handleTimeUpdate();
            });
            
            // Track when video ends
            this.player.on('ended', () => {
                this.handleVideoEnded();
            });
            
            // Save bookmark periodically
            this.bookmarkInterval = setInterval(() => {
                this.saveBookmark();
            }, 30000); // Every 30 seconds
            
            // Cleanup on page unload
            window.addEventListener('beforeunload', () => {
                this.saveBookmark();
                this.cleanup();
            });
        },
        
        handleTimeUpdate() {
            if (!this.player) return;
            
            const currentTime = this.player.currentTime();
            const duration = this.player.duration();
            
            if (duration > 0) {
                const progress = (currentTime / duration) * 100;
                
                // Update progress every 5%
                if (Math.abs(progress - this.lastSavedProgress) >= 5) {
                    this.updateProgress(progress, currentTime);
                    this.lastSavedProgress = progress;
                }
            }
        },
        
        async updateProgress(percentage, position) {
            try {
                this.isSaving = true;
                
                const response = await fetch(`/lessons/${this.lessonId}/progress`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        progress_percentage: Math.min(percentage, 100),
                        position: Math.floor(position),
                        time_spent: Math.floor(position)
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Dispatch event for other components
                    window.dispatchEvent(new CustomEvent('progress-updated', {
                        detail: data.data
                    }));
                }
            } catch (error) {
                console.error('Failed to update progress:', error);
            } finally {
                this.isSaving = false;
            }
        },
        
        async saveBookmark() {
            if (!this.player) return;
            
            const currentTime = Math.floor(this.player.currentTime());
            
            if (currentTime === 0) return;
            
            try {
                await fetch(`/lessons/${this.lessonId}/bookmark`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        position: currentTime
                    })
                });
            } catch (error) {
                console.error('Failed to save bookmark:', error);
            }
        },
        
        async handleVideoEnded() {
            // Mark lesson as complete
            try {
                const response = await fetch(`/lessons/${this.lessonId}/complete`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                
                const data = await response.json();
                
                if (data.success) {
                    this.showNotification('Lesson completed! 🎉');
                    
                    // Dispatch event
                    window.dispatchEvent(new CustomEvent('lesson-completed', {
                        detail: data.data
                    }));
                }
            } catch (error) {
                console.error('Failed to mark lesson as complete:', error);
            }
        },
        
        showNotification(message) {
            // You can integrate with your toast notification system here
            console.log(message);
        },
        
        cleanup() {
            if (this.bookmarkInterval) {
                clearInterval(this.bookmarkInterval);
            }
            
            if (this.player) {
                this.player.dispose();
            }
        }
    }));
});
</script>
@endpush
