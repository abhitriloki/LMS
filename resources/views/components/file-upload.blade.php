@props([
    'type' => 'video',
    'lessonId' => null,
    'maxSize' => null,
    'accept' => null,
    'label' => 'Upload File',
    'name' => 'file',
])

@php
    $uploadConfig = [
        'video' => [
            'accept' => '.mp4,.mov,.avi,.wmv,.flv,.webm,.mkv',
            'maxSize' => config('upload.max_video_size', 512000),
            'icon' => 'video',
            'label' => 'Upload Video',
        ],
        'pdf' => [
            'accept' => '.pdf',
            'maxSize' => config('upload.max_pdf_size', 51200),
            'icon' => 'document',
            'label' => 'Upload PDF',
        ],
        'presentation' => [
            'accept' => '.ppt,.pptx,.odp,.key',
            'maxSize' => config('upload.max_presentation_size', 51200),
            'icon' => 'presentation',
            'label' => 'Upload Presentation',
        ],
        'scorm' => [
            'accept' => '.zip',
            'maxSize' => config('upload.max_scorm_size', 102400),
            'icon' => 'archive',
            'label' => 'Upload SCORM Package',
        ],
    ];

    $config = $uploadConfig[$type] ?? $uploadConfig['video'];
    $accept = $accept ?? $config['accept'];
    $maxSize = $maxSize ?? $config['maxSize'];
    $label = $label === 'Upload File' ? $config['label'] : $label;
    $maxSizeMB = round($maxSize / 1024, 2);
@endphp

<div x-data="fileUpload({
    type: '{{ $type }}',
    lessonId: {{ $lessonId ?? 'null' }},
    maxSize: {{ $maxSize }},
    uploadUrl: '{{ route('admin.upload.' . $type) }}',
})" class="file-upload-component">
    <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-6 text-center hover:border-primary-500 dark:hover:border-primary-400 transition-colors">
        <!-- Upload Area -->
        <div x-show="!uploading && !uploadComplete" class="space-y-4">
            <div class="flex justify-center">
                <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                </svg>
            </div>
            
            <div>
                <label for="{{ $name }}" class="cursor-pointer">
                    <span class="inline-flex items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-colors">
                        {{ $label }}
                    </span>
                    <input 
                        type="file" 
                        id="{{ $name }}" 
                        name="{{ $name }}"
                        accept="{{ $accept }}"
                        @change="handleFileSelect"
                        class="hidden"
                    >
                </label>
            </div>
            
            <p class="text-sm text-gray-500 dark:text-gray-400">
                or drag and drop
            </p>
            
            <p class="text-xs text-gray-400 dark:text-gray-500">
                Maximum file size: {{ $maxSizeMB }}MB
            </p>
        </div>

        <!-- Upload Progress -->
        <div x-show="uploading" class="space-y-4">
            <div class="flex justify-center">
                <svg class="animate-spin h-12 w-12 text-primary-600" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </div>
            
            <div>
                <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Uploading... <span x-text="uploadProgress + '%'"></span>
                </p>
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                    <div 
                        class="bg-primary-600 h-2 rounded-full transition-all duration-300"
                        :style="'width: ' + uploadProgress + '%'"
                    ></div>
                </div>
            </div>
            
            <p class="text-xs text-gray-500 dark:text-gray-400" x-show="fileName">
                <span x-text="fileName"></span>
            </p>
        </div>

        <!-- Upload Complete -->
        <div x-show="uploadComplete" class="space-y-4">
            <div class="flex justify-center">
                <svg class="w-12 h-12 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            
            <p class="text-sm font-medium text-green-600 dark:text-green-400">
                Upload complete!
            </p>
            
            <button 
                @click="reset"
                class="text-sm text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300"
            >
                Upload another file
            </button>
        </div>

        <!-- Error Message -->
        <div x-show="error" class="mt-4 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
            <p class="text-sm text-red-600 dark:text-red-400" x-text="error"></p>
        </div>
    </div>
</div>

<script>
function fileUpload(config) {
    return {
        uploading: false,
        uploadComplete: false,
        uploadProgress: 0,
        fileName: '',
        error: '',
        selectedFile: null,
        
        handleFileSelect(event) {
            const file = event.target.files[0];
            if (!file) return;
            
            this.selectedFile = file;
            this.fileName = file.name;
            this.error = '';
            
            // Validate file size
            if (file.size > config.maxSize * 1024) {
                this.error = `File size exceeds maximum allowed size of ${Math.round(config.maxSize / 1024)}MB`;
                return;
            }
            
            // Start upload
            this.uploadFile(file);
        },
        
        async uploadFile(file) {
            this.uploading = true;
            this.uploadProgress = 0;
            this.error = '';
            
            const formData = new FormData();
            formData.append(config.type, file);
            if (config.lessonId) {
                formData.append('lesson_id', config.lessonId);
            }
            
            try {
                const xhr = new XMLHttpRequest();
                
                // Track upload progress
                xhr.upload.addEventListener('progress', (e) => {
                    if (e.lengthComputable) {
                        this.uploadProgress = Math.round((e.loaded / e.total) * 100);
                    }
                });
                
                // Handle completion
                xhr.addEventListener('load', () => {
                    if (xhr.status === 200) {
                        const response = JSON.parse(xhr.responseText);
                        this.uploading = false;
                        this.uploadComplete = true;
                        
                        // Dispatch custom event with upload data
                        this.$dispatch('file-uploaded', response.data);
                    } else {
                        const response = JSON.parse(xhr.responseText);
                        this.error = response.message || 'Upload failed';
                        this.uploading = false;
                    }
                });
                
                // Handle errors
                xhr.addEventListener('error', () => {
                    this.error = 'Upload failed. Please try again.';
                    this.uploading = false;
                });
                
                // Send request
                xhr.open('POST', config.uploadUrl);
                xhr.setRequestHeader('X-CSRF-TOKEN', document.querySelector('meta[name="csrf-token"]').content);
                xhr.send(formData);
                
            } catch (err) {
                this.error = 'Upload failed: ' + err.message;
                this.uploading = false;
            }
        },
        
        reset() {
            this.uploading = false;
            this.uploadComplete = false;
            this.uploadProgress = 0;
            this.fileName = '';
            this.error = '';
            this.selectedFile = null;
        }
    };
}
</script>
