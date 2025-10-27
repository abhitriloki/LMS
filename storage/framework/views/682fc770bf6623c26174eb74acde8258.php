<?php if (isset($component)) { $__componentOriginal9ac128a9029c0e4701924bd2d73d7f54 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54 = $attributes; } ?>
<?php $component = App\View\Components\AppLayout::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('app-layout'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\App\View\Components\AppLayout::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
     <?php $__env->slot('header', null, []); ?> 
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                <?php echo e(__('Edit Course')); ?>

            </h2>
            <a href="<?php echo e(route('admin.courses.show', $course)); ?>" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200">
                ← Back to Course
            </a>
        </div>
     <?php $__env->endSlot(); ?>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <!-- Tabs -->
            <div class="mb-6" x-data="{ activeTab: 'basic' }">
                <div class="border-b border-gray-200 dark:border-gray-700">
                    <nav class="-mb-px flex space-x-8">
                        <button type="button" @click="activeTab = 'basic'" 
                            :class="activeTab === 'basic' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'"
                            class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                            Basic Information
                        </button>
                        <button type="button" @click="activeTab = 'content'" 
                            :class="activeTab === 'content' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'"
                            class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                            Content & Objectives
                        </button>
                        <button type="button" @click="activeTab = 'settings'" 
                            :class="activeTab === 'settings' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'"
                            class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                            Settings
                        </button>
                    </nav>
                </div>

                <form method="POST" action="<?php echo e(route('admin.courses.update', $course)); ?>" enctype="multipart/form-data">
                    <?php echo csrf_field(); ?>
                    <?php echo method_field('PUT'); ?>

                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mt-6">
                        <div class="p-6 space-y-6">
                            <!-- Basic Information Tab -->
                            <div x-show="activeTab === 'basic'" x-cloak>
                            <!-- Basic Information Tab Content -->
                            <div>
                            
                            <!-- Title -->
                            <div class="mb-4">
                                <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Course Title <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="title" id="title" value="<?php echo e(old('title', $course->title)); ?>" required
                                    class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <?php $__errorArgs = ['title'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                    <p class="mt-1 text-sm text-red-600"><?php echo e($message); ?></p>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>

                            <!-- Short Description -->
                            <div class="mb-4">
                                <label for="short_description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Short Description
                                </label>
                                <input type="text" name="short_description" id="short_description" value="<?php echo e(old('short_description', $course->short_description)); ?>"
                                    class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    maxlength="500">
                                <?php $__errorArgs = ['short_description'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                    <p class="mt-1 text-sm text-red-600"><?php echo e($message); ?></p>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>

                            <!-- Description -->
                            <div class="mb-4">
                                <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Full Description <span class="text-red-500">*</span>
                                </label>
                                <textarea name="description" id="description" rows="6" required
                                    class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"><?php echo e(old('description', $course->description)); ?></textarea>
                                <?php $__errorArgs = ['description'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                    <p class="mt-1 text-sm text-red-600"><?php echo e($message); ?></p>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>

                            <!-- Category and Difficulty -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label for="category_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Category <span class="text-red-500">*</span>
                                    </label>
                                    <select name="category_id" id="category_id" required
                                        class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        <option value="">Select a category</option>
                                        <?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <option value="<?php echo e($category->id); ?>" <?php echo e(old('category_id', $course->category_id) == $category->id ? 'selected' : ''); ?>>
                                                <?php echo e($category->name); ?>

                                            </option>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </select>
                                    <?php $__errorArgs = ['category_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                        <p class="mt-1 text-sm text-red-600"><?php echo e($message); ?></p>
                                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                </div>

                                <div>
                                    <label for="difficulty_level" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Difficulty Level <span class="text-red-500">*</span>
                                    </label>
                                    <select name="difficulty_level" id="difficulty_level" required
                                        class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        <option value="">Select difficulty</option>
                                        <option value="beginner" <?php echo e(old('difficulty_level', $course->difficulty_level) == 'beginner' ? 'selected' : ''); ?>>Beginner</option>
                                        <option value="intermediate" <?php echo e(old('difficulty_level', $course->difficulty_level) == 'intermediate' ? 'selected' : ''); ?>>Intermediate</option>
                                        <option value="advanced" <?php echo e(old('difficulty_level', $course->difficulty_level) == 'advanced' ? 'selected' : ''); ?>>Advanced</option>
                                    </select>
                                    <?php $__errorArgs = ['difficulty_level'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                        <p class="mt-1 text-sm text-red-600"><?php echo e($message); ?></p>
                                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                </div>
                            </div>

                            <!-- Duration and Language -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label for="estimated_duration" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Estimated Duration (minutes)
                                    </label>
                                    <input type="number" name="estimated_duration" id="estimated_duration" value="<?php echo e(old('estimated_duration', $course->estimated_duration)); ?>" min="0"
                                        class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <?php $__errorArgs = ['estimated_duration'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                        <p class="mt-1 text-sm text-red-600"><?php echo e($message); ?></p>
                                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                </div>

                                <div>
                                    <label for="language" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Language
                                    </label>
                                    <input type="text" name="language" id="language" value="<?php echo e(old('language', $course->language ?? 'English')); ?>"
                                        class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <?php $__errorArgs = ['language'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                        <p class="mt-1 text-sm text-red-600"><?php echo e($message); ?></p>
                                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                </div>
                            </div>

                            <!-- Current Thumbnail -->
                            <?php if($course->thumbnail): ?>
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Current Thumbnail
                                    </label>
                                    <img src="<?php echo e(Storage::url($course->thumbnail)); ?>" alt="<?php echo e($course->title); ?>" class="h-32 w-auto rounded">
                                </div>
                            <?php endif; ?>

                            <!-- Thumbnail -->
                            <div class="mb-4">
                                <label for="thumbnail" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    <?php echo e($course->thumbnail ? 'Change Thumbnail' : 'Course Thumbnail'); ?>

                                </label>
                                <input type="file" name="thumbnail" id="thumbnail" accept="image/*"
                                    class="w-full text-sm text-gray-500 dark:text-gray-400
                                        file:mr-4 file:py-2 file:px-4
                                        file:rounded-md file:border-0
                                        file:text-sm file:font-semibold
                                        file:bg-blue-50 file:text-blue-700
                                        hover:file:bg-blue-100">
                                <?php $__errorArgs = ['thumbnail'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                    <p class="mt-1 text-sm text-red-600"><?php echo e($message); ?></p>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                            </div>

                            <!-- Content & Objectives Tab -->
                            <div x-show="activeTab === 'content'" x-cloak>
                                <!-- Learning Objectives -->
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Learning Objectives
                                    </label>
                                    <div id="objectives-container" class="space-y-2">
                                        <?php if($course->learning_objectives && is_array($course->learning_objectives)): ?>
                                            <?php $__currentLoopData = $course->learning_objectives; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $objective): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <div class="flex items-center space-x-2">
                                                    <input type="text" name="learning_objectives[]" value="<?php echo e($objective); ?>"
                                                        class="flex-1 rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                                    <button type="button" onclick="this.parentElement.remove()" class="text-red-600 hover:text-red-800">
                                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                        </svg>
                                                    </button>
                                                </div>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        <?php endif; ?>
                                    </div>
                                    <button type="button" onclick="addObjective()" class="mt-2 text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400">
                                        + Add Learning Objective
                                    </button>
                                </div>

                                <!-- Tags -->
                                <div class="mb-4">
                                    <label for="tags_input" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Tags
                                    </label>
                                    <input type="text" id="tags_input" placeholder="Enter tags separated by commas"
                                        value="<?php echo e(is_array($course->tags) ? implode(', ', $course->tags) : ''); ?>"
                                        class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <input type="hidden" name="tags" id="tags_hidden">
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Separate tags with commas</p>
                                </div>

                                <!-- Preview Video -->
                                <div class="mb-4">
                                    <label for="preview_video" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Preview Video URL
                                    </label>
                                    <input type="url" name="preview_video" id="preview_video" value="<?php echo e(old('preview_video', $course->preview_video)); ?>"
                                        class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                        placeholder="https://...">
                                    <?php $__errorArgs = ['preview_video'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                        <p class="mt-1 text-sm text-red-600"><?php echo e($message); ?></p>
                                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                </div>
                            </div>

                            <!-- Settings Tab -->
                            <div x-show="activeTab === 'settings'" x-cloak>

                            <!-- Target Audience -->
                            <div class="mb-4">
                                <label for="target_audience" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Target Audience
                                </label>
                                <textarea name="target_audience" id="target_audience" rows="3"
                                    class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"><?php echo e(old('target_audience', $course->target_audience)); ?></textarea>
                                <?php $__errorArgs = ['target_audience'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                    <p class="mt-1 text-sm text-red-600"><?php echo e($message); ?></p>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>

                            <!-- Prerequisites -->
                            <div class="mb-4">
                                <label for="prerequisites" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Prerequisites
                                </label>
                                <select name="prerequisites[]" id="prerequisites" multiple
                                    class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <?php $__currentLoopData = $courses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $prereqCourse): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($prereqCourse->id); ?>" 
                                            <?php echo e(in_array($prereqCourse->id, old('prerequisites', $course->prerequisites ?? [])) ? 'selected' : ''); ?>>
                                            <?php echo e($prereqCourse->title); ?>

                                        </option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Hold Ctrl/Cmd to select multiple courses</p>
                                <?php $__errorArgs = ['prerequisites'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                    <p class="mt-1 text-sm text-red-600"><?php echo e($message); ?></p>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>

                            <!-- Checkboxes -->
                            <div class="space-y-2">
                                <div class="flex items-center">
                                    <input type="checkbox" name="is_mandatory" id="is_mandatory" value="1" 
                                        <?php echo e(old('is_mandatory', $course->is_mandatory) ? 'checked' : ''); ?>

                                        class="rounded border-gray-300 dark:border-gray-700 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <label for="is_mandatory" class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                                        Mandatory Course
                                    </label>
                                </div>

                                <div class="flex items-center">
                                    <input type="checkbox" name="is_featured" id="is_featured" value="1" 
                                        <?php echo e(old('is_featured', $course->is_featured) ? 'checked' : ''); ?>

                                        class="rounded border-gray-300 dark:border-gray-700 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <label for="is_featured" class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                                        Featured Course
                                    </label>
                                </div>
                            </div>
                            </div>

                            <!-- Actions -->
                            <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                            <a href="<?php echo e(route('admin.courses.show', $course)); ?>" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                                Cancel
                            </a>
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition">
                                Update Course
                            </button>
                        </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php $__env->startPush('scripts'); ?>
    <script>
        function addObjective() {
            const container = document.getElementById('objectives-container');
            const div = document.createElement('div');
            div.className = 'flex items-center space-x-2';
            div.innerHTML = `
                <input type="text" name="learning_objectives[]" 
                    class="flex-1 rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                <button type="button" onclick="this.parentElement.remove()" class="text-red-600 hover:text-red-800">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            `;
            container.appendChild(div);
        }

        // Handle tags input
        document.getElementById('tags_input').addEventListener('blur', function() {
            const tags = this.value.split(',').map(tag => tag.trim()).filter(tag => tag);
            document.getElementById('tags_hidden').value = JSON.stringify(tags);
        });

        // Set initial tags value
        document.addEventListener('DOMContentLoaded', function() {
            const tagsInput = document.getElementById('tags_input');
            if (tagsInput.value) {
                const tags = tagsInput.value.split(',').map(tag => tag.trim()).filter(tag => tag);
                document.getElementById('tags_hidden').value = JSON.stringify(tags);
            }
        });
    </script>
    <?php $__env->stopPush(); ?>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54)): ?>
<?php $attributes = $__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54; ?>
<?php unset($__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9ac128a9029c0e4701924bd2d73d7f54)): ?>
<?php $component = $__componentOriginal9ac128a9029c0e4701924bd2d73d7f54; ?>
<?php unset($__componentOriginal9ac128a9029c0e4701924bd2d73d7f54); ?>
<?php endif; ?>
<?php /**PATH C:\wamp64\www\corporate-lms\resources\views/admin/courses/edit.blade.php ENDPATH**/ ?>