@props([
    'pdfUrl',
    'lessonId',
    'lastPosition' => 1,
])

<div x-data="pdfViewer({
    pdfUrl: '{{ $pdfUrl }}',
    lessonId: {{ $lessonId }},
    lastPosition: {{ $lastPosition }}
})" x-init="init()" class="pdf-viewer-container">
    
    <!-- PDF Toolbar -->
    <div class="pdf-toolbar bg-gray-100 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 p-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <!-- Navigation -->
                <button 
                    @click="previousPage()" 
                    :disabled="currentPage <= 1"
                    class="px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50 dark:hover:bg-gray-600"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                </button>
                
                <div class="flex items-center space-x-2">
                    <input 
                        type="number" 
                        x-model.number="currentPage"
                        @change="goToPage(currentPage)"
                        min="1"
                        :max="totalPages"
                        class="w-16 px-2 py-1 text-center border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white"
                    >
                    <span class="text-sm text-gray-600 dark:text-gray-400">
                        / <span x-text="totalPages"></span>
                    </span>
                </div>
                
                <button 
                    @click="nextPage()" 
                    :disabled="currentPage >= totalPages"
                    class="px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50 dark:hover:bg-gray-600"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </button>
            </div>
            
            <!-- Zoom Controls -->
            <div class="flex items-center space-x-4">
                <button 
                    @click="zoomOut()" 
                    class="px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM13 10H7"></path>
                    </svg>
                </button>
                
                <span class="text-sm text-gray-600 dark:text-gray-400" x-text="Math.round(scale * 100) + '%'"></span>
                
                <button 
                    @click="zoomIn()" 
                    class="px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v6m3-3H7"></path>
                    </svg>
                </button>
                
                <button 
                    @click="fitToWidth()" 
                    class="px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 text-sm"
                >
                    Fit Width
                </button>
            </div>
            
            <!-- Actions -->
            <div class="flex items-center space-x-2">
                <button 
                    @click="toggleAnnotations()" 
                    class="px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600"
                    :class="{ 'bg-blue-50 border-blue-500': annotationMode }"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                    </svg>
                </button>
                
                <button 
                    @click="markComplete()" 
                    class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700"
                >
                    Mark Complete
                </button>
            </div>
        </div>
    </div>
    
    <!-- PDF Canvas Container -->
    <div class="pdf-content bg-gray-200 dark:bg-gray-900 overflow-auto" style="height: calc(100vh - 200px);">
        <div class="flex justify-center p-8">
            <div class="relative">
                <canvas 
                    x-ref="pdfCanvas"
                    class="shadow-2xl bg-white"
                ></canvas>
                
                <!-- Annotation Layer -->
                <canvas 
                    x-ref="annotationCanvas"
                    x-show="annotationMode"
                    @mousedown="startDrawing($event)"
                    @mousemove="draw($event)"
                    @mouseup="stopDrawing()"
                    @mouseleave="stopDrawing()"
                    class="absolute top-0 left-0 cursor-crosshair"
                    style="touch-action: none;"
                ></canvas>
            </div>
        </div>
    </div>
    
    <!-- Loading Indicator -->
    <div x-show="isLoading" class="absolute inset-0 flex items-center justify-center bg-white dark:bg-gray-900 bg-opacity-75">
        <div class="text-center">
            <svg class="animate-spin h-12 w-12 text-blue-600 mx-auto mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <p class="text-gray-600 dark:text-gray-400">Loading PDF...</p>
        </div>
    </div>
</div>

@push('styles')
<style>
    .pdf-viewer-container {
        position: relative;
        width: 100%;
    }
    
    .pdf-content {
        position: relative;
    }
</style>
@endpush

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
<script>
// Set PDF.js worker
pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

document.addEventListener('alpine:init', () => {
    Alpine.data('pdfViewer', (config) => ({
        pdfUrl: config.pdfUrl,
        lessonId: config.lessonId,
        lastPosition: config.lastPosition,
        pdfDoc: null,
        currentPage: 1,
        totalPages: 0,
        scale: 1.5,
        isLoading: true,
        annotationMode: false,
        isDrawing: false,
        annotations: {},
        progressInterval: null,
        
        async init() {
            await this.loadPDF();
            this.startProgressTracking();
            
            // Restore last page
            if (this.lastPosition > 1) {
                this.currentPage = this.lastPosition;
                await this.renderPage(this.currentPage);
            }
        },
        
        async loadPDF() {
            try {
                this.isLoading = true;
                
                const loadingTask = pdfjsLib.getDocument(this.pdfUrl);
                this.pdfDoc = await loadingTask.promise;
                this.totalPages = this.pdfDoc.numPages;
                
                await this.renderPage(this.currentPage);
                
                this.isLoading = false;
            } catch (error) {
                console.error('Error loading PDF:', error);
                this.isLoading = false;
            }
        },
        
        async renderPage(pageNum) {
            if (!this.pdfDoc) return;
            
            try {
                const page = await this.pdfDoc.getPage(pageNum);
                const canvas = this.$refs.pdfCanvas;
                const context = canvas.getContext('2d');
                
                const viewport = page.getViewport({ scale: this.scale });
                
                canvas.height = viewport.height;
                canvas.width = viewport.width;
                
                // Setup annotation canvas
                if (this.$refs.annotationCanvas) {
                    this.$refs.annotationCanvas.height = viewport.height;
                    this.$refs.annotationCanvas.width = viewport.width;
                    this.redrawAnnotations();
                }
                
                const renderContext = {
                    canvasContext: context,
                    viewport: viewport
                };
                
                await page.render(renderContext).promise;
                
                // Save bookmark
                this.saveBookmark(pageNum);
                
                // Update progress
                this.updateProgress();
                
            } catch (error) {
                console.error('Error rendering page:', error);
            }
        },
        
        async nextPage() {
            if (this.currentPage < this.totalPages) {
                this.currentPage++;
                await this.renderPage(this.currentPage);
            }
        },
        
        async previousPage() {
            if (this.currentPage > 1) {
                this.currentPage--;
                await this.renderPage(this.currentPage);
            }
        },
        
        async goToPage(pageNum) {
            if (pageNum >= 1 && pageNum <= this.totalPages) {
                this.currentPage = pageNum;
                await this.renderPage(this.currentPage);
            }
        },
        
        async zoomIn() {
            this.scale += 0.25;
            await this.renderPage(this.currentPage);
        },
        
        async zoomOut() {
            if (this.scale > 0.5) {
                this.scale -= 0.25;
                await this.renderPage(this.currentPage);
            }
        },
        
        async fitToWidth() {
            const container = this.$el.querySelector('.pdf-content');
            const containerWidth = container.clientWidth - 64; // padding
            const page = await this.pdfDoc.getPage(this.currentPage);
            const viewport = page.getViewport({ scale: 1 });
            this.scale = containerWidth / viewport.width;
            await this.renderPage(this.currentPage);
        },
        
        toggleAnnotations() {
            this.annotationMode = !this.annotationMode;
        },
        
        startDrawing(event) {
            if (!this.annotationMode) return;
            
            this.isDrawing = true;
            const canvas = this.$refs.annotationCanvas;
            const rect = canvas.getBoundingClientRect();
            const ctx = canvas.getContext('2d');
            
            ctx.strokeStyle = '#3B82F6';
            ctx.lineWidth = 2;
            ctx.lineCap = 'round';
            
            ctx.beginPath();
            ctx.moveTo(event.clientX - rect.left, event.clientY - rect.top);
        },
        
        draw(event) {
            if (!this.isDrawing || !this.annotationMode) return;
            
            const canvas = this.$refs.annotationCanvas;
            const rect = canvas.getBoundingClientRect();
            const ctx = canvas.getContext('2d');
            
            ctx.lineTo(event.clientX - rect.left, event.clientY - rect.top);
            ctx.stroke();
        },
        
        stopDrawing() {
            if (this.isDrawing) {
                this.isDrawing = false;
                this.saveAnnotations();
            }
        },
        
        saveAnnotations() {
            const canvas = this.$refs.annotationCanvas;
            this.annotations[this.currentPage] = canvas.toDataURL();
        },
        
        redrawAnnotations() {
            if (this.annotations[this.currentPage]) {
                const canvas = this.$refs.annotationCanvas;
                const ctx = canvas.getContext('2d');
                const img = new Image();
                img.onload = () => {
                    ctx.drawImage(img, 0, 0);
                };
                img.src = this.annotations[this.currentPage];
            }
        },
        
        async saveBookmark(pageNum) {
            try {
                await fetch(`/lessons/${this.lessonId}/bookmark`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        position: pageNum
                    })
                });
            } catch (error) {
                console.error('Failed to save bookmark:', error);
            }
        },
        
        startProgressTracking() {
            this.progressInterval = setInterval(() => {
                this.updateProgress();
            }, 30000); // Every 30 seconds
        },
        
        async updateProgress() {
            const progress = (this.currentPage / this.totalPages) * 100;
            
            try {
                await fetch(`/lessons/${this.lessonId}/progress`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        progress_percentage: progress,
                        position: this.currentPage
                    })
                });
            } catch (error) {
                console.error('Failed to update progress:', error);
            }
        },
        
        async markComplete() {
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
                    alert('Lesson marked as complete! 🎉');
                    
                    window.dispatchEvent(new CustomEvent('lesson-completed', {
                        detail: data.data
                    }));
                }
            } catch (error) {
                console.error('Failed to mark lesson as complete:', error);
            }
        }
    }));
});
</script>
@endpush
