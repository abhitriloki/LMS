<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    {{ __('Course Builder') }}
                </h2>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ $course->title }}</p>
            </div>
            <div class="flex space-x-2">
                <a href="{{ route('admin.courses.show', $course) }}" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200">
                    ← Back to Course
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            @if (session('error'))
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('error') }}</span>
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Course Structure -->
                <div class="lg:col-span-2">
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex justify-between items-center mb-6">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Course Structure</h3>
                                <button type="button" onclick="openAddModuleModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition text-sm">
                                    + Add Module
                                </button>
                            </div>

                            <div id="modules-container" class="space-y-4">
                                @forelse($course->modules->sortBy('order_index') as $module)
                                    <div class="module-item border border-gray-200 dark:border-gray-700 rounded-lg" data-module-id="{{ $module->id }}">
                                        <!-- Module Header -->
                                        <div class="bg-gray-50 dark:bg-gray-900 p-4 rounded-t-lg cursor-move module-handle">
                                            <div class="flex justify-between items-start">
                                                <div class="flex items-start space-x-3 flex-1">
                                                    <svg class="h-5 w-5 text-gray-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                                    </svg>
                                                    <div class="flex-1">
                                                        <h4 class="text-base font-semibold text-gray-900 dark:text-gray-100">
                                                            {{ $module->title }}
                                                        </h4>
                                                        @if($module->description)
                                                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ $module->description }}</p>
                                                        @endif
                                                        <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                                                            {{ $module->lessons->count() }} {{ Str::plural('lesson', $module->lessons->count()) }}
                                                        </p>
                                                    </div>
                                                </div>
                                                <div class="flex items-center space-x-2">
                                                    <button type="button" onclick="toggleModule({{ $module->id }})" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                                                        <svg class="h-5 w-5 transform transition-transform" id="module-toggle-{{ $module->id }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                                        </svg>
                                                    </button>
                                                    <button type="button" onclick="editModule({{ $module->id }})" class="text-blue-600 hover:text-blue-800 dark:text-blue-400">
                                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                        </svg>
                                                    </button>
                                                    <button type="button" onclick="deleteModule({{ $module->id }})" class="text-red-600 hover:text-red-800 dark:text-red-400">
                                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                        </svg>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Lessons List -->
                                        <div id="module-content-{{ $module->id }}" class="p-4 space-y-2 lessons-container" data-module-id="{{ $module->id }}">
                                            @forelse($module->lessons->sortBy('order_index') as $lesson)
                                                <div class="lesson-item flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-900 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 cursor-move" data-lesson-id="{{ $lesson->id }}">
                                                    <div class="flex items-center space-x-3 flex-1">
                                                        <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16" />
                                                        </svg>
                                                        <div class="flex items-center space-x-2">
                                                            @if($lesson->content_type === 'video')
                                                                <svg class="h-5 w-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                                </svg>
                                                            @elseif($lesson->content_type === 'pdf')
                                                                <svg class="h-5 w-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                                                </svg>
                                                            @elseif($lesson->content_type === 'text')
                                                                <svg class="h-5 w-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                                </svg>
                                                            @else
                                                                <svg class="h-5 w-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                                </svg>
                                                            @endif
                                                            <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $lesson->title }}</span>
                                                        </div>
                                                        @if($lesson->duration)
                                                            <span class="text-xs text-gray-500 dark:text-gray-400">{{ $lesson->duration }} min</span>
                                                        @endif
                                                    </div>
                                                    <div class="flex items-center space-x-2">
                                                        <button type="button" onclick="editLesson({{ $lesson->id }})" class="text-blue-600 hover:text-blue-800 dark:text-blue-400">
                                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                            </svg>
                                                        </button>
                                                        <button type="button" onclick="deleteLesson({{ $lesson->id }})" class="text-red-600 hover:text-red-800 dark:text-red-400">
                                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                            </svg>
                                                        </button>
                                                    </div>
                                                </div>
                                            @empty
                                                <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-4">No lessons yet. Add your first lesson.</p>
                                            @endforelse

                                            <button type="button" onclick="openAddLessonModal({{ $module->id }})" class="w-full py-2 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-600 dark:text-gray-400 hover:border-blue-500 hover:text-blue-600 dark:hover:text-blue-400 transition">
                                                + Add Lesson
                                            </button>
                                        </div>
                                    </div>
                                @empty
                                    <div class="text-center py-12 text-gray-500 dark:text-gray-400">
                                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        <p class="mt-2">No modules yet. Create your first module to get started.</p>
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="lg:col-span-1">
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg sticky top-6">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Course Info</h3>
                            
                            <div class="space-y-4">
                                <div>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">Total Modules</p>
                                    <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $course->modules->count() }}</p>
                                </div>

                                <div>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">Total Lessons</p>
                                    <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $course->modules->sum(fn($m) => $m->lessons->count()) }}</p>
                                </div>

                                <div>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">Status</p>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $course->is_published ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                        {{ $course->is_published ? 'Published' : 'Draft' }}
                                    </span>
                                </div>

                                <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                                    <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-2">Quick Actions</h4>
                                    <div class="space-y-2">
                                        <a href="{{ route('admin.courses.edit', $course) }}" class="block w-full text-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                                            Edit Course Details
                                        </a>
                                        @if($course->is_published)
                                            <form action="{{ route('admin.courses.unpublish', $course) }}" method="POST">
                                                @csrf
                                                @method('PUT')
                                                <button type="submit" class="block w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                                                    Unpublish Course
                                                </button>
                                            </form>
                                        @else
                                            <form action="{{ route('admin.courses.publish', $course) }}" method="POST">
                                                @csrf
                                                @method('PUT')
                                                <button type="submit" class="block w-full px-4 py-2 bg-green-600 text-white rounded-md text-sm hover:bg-green-700 transition">
                                                    Publish Course
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Module Modal -->
    <div id="addModuleModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white dark:bg-gray-800">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Add New Module</h3>
                <form id="addModuleForm" action="{{ route('admin.modules.store', $course) }}" method="POST">
                    @csrf
                    <div class="space-y-4">
                        <div>
                            <label for="module_title" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Module Title <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="title" id="module_title" required
                                class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div>
                            <label for="module_description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Description
                            </label>
                            <textarea name="description" id="module_description" rows="3"
                                class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                        </div>
                    </div>
                    <div class="flex justify-end space-x-3 mt-6">
                        <button type="button" onclick="closeAddModuleModal()" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition">
                            Add Module
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Lesson Modal -->
    <div id="addLessonModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white dark:bg-gray-800">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Add New Lesson</h3>
                <form id="addLessonForm" method="POST">
                    @csrf
                    <input type="hidden" name="module_id" id="lesson_module_id">
                    <div class="space-y-4">
                        <div>
                            <label for="lesson_title" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Lesson Title <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="title" id="lesson_title" required
                                class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div>
                            <label for="lesson_content_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Content Type <span class="text-red-500">*</span>
                            </label>
                            <select name="content_type" id="lesson_content_type" required
                                class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Select type</option>
                                <option value="video">Video</option>
                                <option value="pdf">PDF</option>
                                <option value="text">Text</option>
                                <option value="scorm">SCORM</option>
                            </select>
                        </div>
                        <div>
                            <label for="lesson_duration" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Duration (minutes)
                            </label>
                            <input type="number" name="duration" id="lesson_duration" min="0"
                                class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                    </div>
                    <div class="flex justify-end space-x-3 mt-6">
                        <button type="button" onclick="closeAddLessonModal()" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition">
                            Add Lesson
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
    <script>
        // Initialize Sortable for modules
        const modulesContainer = document.getElementById('modules-container');
        if (modulesContainer) {
            new Sortable(modulesContainer, {
                handle: '.module-handle',
                animation: 150,
                onEnd: function(evt) {
                    updateModuleOrder();
                }
            });
        }

        // Initialize Sortable for lessons in each module
        document.querySelectorAll('.lessons-container').forEach(container => {
            new Sortable(container, {
                group: 'lessons',
                handle: '.lesson-item',
                animation: 150,
                onEnd: function(evt) {
                    updateLessonOrder(evt.to.dataset.moduleId);
                }
            });
        });

        function updateModuleOrder() {
            const moduleIds = Array.from(document.querySelectorAll('.module-item')).map(el => el.dataset.moduleId);
            
            fetch('{{ route("admin.modules.reorder", $course) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ order: moduleIds })
            });
        }

        function updateLessonOrder(moduleId) {
            const container = document.querySelector(`.lessons-container[data-module-id="${moduleId}"]`);
            const lessonIds = Array.from(container.querySelectorAll('.lesson-item')).map(el => el.dataset.lessonId);
            
            fetch(`/admin/modules/${moduleId}/lessons/reorder`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ order: lessonIds })
            });
        }

        function toggleModule(moduleId) {
            const content = document.getElementById(`module-content-${moduleId}`);
            const icon = document.getElementById(`module-toggle-${moduleId}`);
            
            if (content.classList.contains('hidden')) {
                content.classList.remove('hidden');
                icon.classList.remove('rotate-180');
            } else {
                content.classList.add('hidden');
                icon.classList.add('rotate-180');
            }
        }

        function openAddModuleModal() {
            document.getElementById('addModuleModal').classList.remove('hidden');
        }

        function closeAddModuleModal() {
            document.getElementById('addModuleModal').classList.add('hidden');
            document.getElementById('addModuleForm').reset();
        }

        function openAddLessonModal(moduleId) {
            document.getElementById('lesson_module_id').value = moduleId;
            document.getElementById('addLessonForm').action = `/admin/modules/${moduleId}/lessons`;
            document.getElementById('addLessonModal').classList.remove('hidden');
        }

        function closeAddLessonModal() {
            document.getElementById('addLessonModal').classList.add('hidden');
            document.getElementById('addLessonForm').reset();
        }

        function editModule(moduleId) {
            window.location.href = `/admin/modules/${moduleId}/edit`;
        }

        function editLesson(lessonId) {
            window.location.href = `/admin/lessons/${lessonId}/edit`;
        }

        function deleteModule(moduleId) {
            if (confirm('Are you sure you want to delete this module? All lessons in this module will also be deleted.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = `/admin/modules/${moduleId}`;
                
                const csrfToken = document.createElement('input');
                csrfToken.type = 'hidden';
                csrfToken.name = '_token';
                csrfToken.value = '{{ csrf_token() }}';
                
                const methodField = document.createElement('input');
                methodField.type = 'hidden';
                methodField.name = '_method';
                methodField.value = 'DELETE';
                
                form.appendChild(csrfToken);
                form.appendChild(methodField);
                document.body.appendChild(form);
                form.submit();
            }
        }

        function deleteLesson(lessonId) {
            if (confirm('Are you sure you want to delete this lesson?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = `/admin/lessons/${lessonId}`;
                
                const csrfToken = document.createElement('input');
                csrfToken.type = 'hidden';
                csrfToken.name = '_token';
                csrfToken.value = '{{ csrf_token() }}';
                
                const methodField = document.createElement('input');
                methodField.type = 'hidden';
                methodField.name = '_method';
                methodField.value = 'DELETE';
                
                form.appendChild(csrfToken);
                form.appendChild(methodField);
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Close modals when clicking outside
        window.onclick = function(event) {
            const addModuleModal = document.getElementById('addModuleModal');
            const addLessonModal = document.getElementById('addLessonModal');
            
            if (event.target === addModuleModal) {
                closeAddModuleModal();
            }
            if (event.target === addLessonModal) {
                closeAddLessonModal();
            }
        }
    </script>
    @endpush
</x-app-layout>
