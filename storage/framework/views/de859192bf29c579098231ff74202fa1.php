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
                <?php echo e(__('AI Grading Review Queue')); ?>

            </h2>
            <a href="<?php echo e(route('admin.grading.statistics')); ?>" class="btn btn-secondary">
                View Statistics
            </a>
        </div>
     <?php $__env->endSlot(); ?>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Filters -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <form method="GET" action="<?php echo e(route('admin.grading.review.index')); ?>" class="flex gap-4">
                        <div class="flex-1">
                            <label for="assessment_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Filter by Assessment
                            </label>
                            <select name="assessment_id" id="assessment_id" class="form-select w-full">
                                <option value="">All Assessments</option>
                                <?php $__currentLoopData = $assessments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $assessment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($assessment->id); ?>" <?php echo e(request('assessment_id') == $assessment->id ? 'selected' : ''); ?>>
                                        <?php echo e($assessment->title); ?>

                                    </option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>

                        <div class="flex-1">
                            <label for="max_confidence" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Max Confidence Score
                            </label>
                            <select name="max_confidence" id="max_confidence" class="form-select w-full">
                                <option value="">All</option>
                                <option value="0.5" <?php echo e(request('max_confidence') == '0.5' ? 'selected' : ''); ?>>≤ 0.5 (Very Low)</option>
                                <option value="0.6" <?php echo e(request('max_confidence') == '0.6' ? 'selected' : ''); ?>>≤ 0.6 (Low)</option>
                                <option value="0.7" <?php echo e(request('max_confidence') == '0.7' ? 'selected' : ''); ?>>≤ 0.7 (Medium)</option>
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
                    <?php if($flaggedGradings->isEmpty()): ?>
                        <div class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">No responses pending review</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">All AI-graded responses have been reviewed.</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-4">
                            <?php $__currentLoopData = $flaggedGradings; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $grading): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php
                                    $response = $grading->attemptResponse;
                                    $attempt = $response->attempt;
                                    $question = $response->question;
                                ?>
                                
                                <div class="border dark:border-gray-700 rounded-lg p-4 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                                    <div class="flex justify-between items-start">
                                        <div class="flex-1">
                                            <div class="flex items-center gap-3 mb-2">
                                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                                    <?php echo e($attempt->assessment->title); ?>

                                                </h3>
                                                <span class="px-2 py-1 text-xs rounded-full 
                                                    <?php echo e($grading->confidence_score < 0.5 ? 'bg-red-100 text-red-800' : 
                                                       ($grading->confidence_score < 0.7 ? 'bg-yellow-100 text-yellow-800' : 'bg-blue-100 text-blue-800')); ?>">
                                                    Confidence: <?php echo e(number_format($grading->confidence_score * 100, 0)); ?>%
                                                </span>
                                            </div>

                                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                                                Student: <span class="font-medium"><?php echo e($attempt->user->name); ?></span>
                                            </p>

                                            <p class="text-sm text-gray-700 dark:text-gray-300 mb-2">
                                                <strong>Question:</strong> <?php echo e(Str::limit($question->question_text, 100)); ?>

                                            </p>

                                            <div class="flex items-center gap-4 text-sm">
                                                <span class="text-gray-600 dark:text-gray-400">
                                                    AI Score: <span class="font-semibold"><?php echo e($grading->ai_score); ?>/<?php echo e($question->points); ?></span>
                                                </span>
                                                <span class="text-gray-600 dark:text-gray-400">
                                                    Submitted: <?php echo e($attempt->submitted_at->diffForHumans()); ?>

                                                </span>
                                                <?php if($grading->review_reason): ?>
                                                    <span class="text-orange-600 dark:text-orange-400">
                                                        Reason: <?php echo e($grading->review_reason); ?>

                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <div class="flex gap-2">
                                            <a href="<?php echo e(route('admin.grading.review.show', $grading)); ?>" 
                                               class="btn btn-primary btn-sm">
                                                Review
                                            </a>
                                            <form method="POST" action="<?php echo e(route('admin.grading.review.accept', $grading)); ?>" class="inline">
                                                <?php echo csrf_field(); ?>
                                                <button type="submit" class="btn btn-secondary btn-sm"
                                                        onclick="return confirm('Accept AI grading without changes?')">
                                                    Accept
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>

                        <div class="mt-6">
                            <?php echo e($flaggedGradings->links()); ?>

                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
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
<?php /**PATH C:\wamp64\www\corporate-lms\resources\views/admin/grading/review-index.blade.php ENDPATH**/ ?>