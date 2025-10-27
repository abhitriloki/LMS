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
                <?php echo e(__('Content Analysis')); ?> - <?php echo e($course->title); ?>

            </h2>
            <div class="flex gap-2">
                <a href="<?php echo e(route('admin.courses.show', $course)); ?>" class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                    <?php echo e(__('Back to Course')); ?>

                </a>
                <?php if($analysis): ?>
                    <form action="<?php echo e(route('admin.content-analyzer.re-analyze', $course)); ?>" method="POST" class="inline">
                        <?php echo csrf_field(); ?>
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700">
                            <?php echo e(__('Re-Analyze')); ?>

                        </button>
                    </form>
                <?php else: ?>
                    <form action="<?php echo e(route('admin.content-analyzer.analyze', $course)); ?>" method="POST" class="inline">
                        <?php echo csrf_field(); ?>
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700">
                            <?php echo e(__('Analyze Content')); ?>

                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
     <?php $__env->endSlot(); ?>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <?php if(session('success')): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline"><?php echo e(session('success')); ?></span>
                </div>
            <?php endif; ?>

            <?php if(session('error')): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline"><?php echo e(session('error')); ?></span>
                </div>
            <?php endif; ?>

            <?php if($analysis): ?>
                <!-- Overall Score Card -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                            <?php echo e(__('Overall Quality Score')); ?>

                        </h3>
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <div class="flex items-center gap-4">
                                    <div class="text-6xl font-bold <?php echo e($analysis->getScoreGrade() === 'A' ? 'text-green-600' : ($analysis->getScoreGrade() === 'B' ? 'text-blue-600' : ($analysis->getScoreGrade() === 'C' ? 'text-yellow-600' : 'text-red-600'))); ?>">
                                        <?php echo e(number_format($analysis->overall_score, 1)); ?>

                                    </div>
                                    <div>
                                        <div class="text-3xl font-bold text-gray-700 dark:text-gray-300">
                                            <?php echo e($analysis->getScoreGrade()); ?>

                                        </div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">
                                            <?php echo e(__('Grade')); ?>

                                        </div>
                                    </div>
                                </div>
                                <div class="mt-4 w-full bg-gray-200 rounded-full h-4 dark:bg-gray-700">
                                    <div class="bg-blue-600 h-4 rounded-full" style="width: <?php echo e($analysis->overall_score); ?>%"></div>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-sm text-gray-500 dark:text-gray-400">
                                    <?php echo e(__('Analyzed')); ?>

                                </div>
                                <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                    <?php echo e($analysis->analyzed_at->diffForHumans()); ?>

                                </div>
                                <div class="text-xs text-gray-400 dark:text-gray-500">
                                    <?php echo e($analysis->analyzed_at->format('M d, Y H:i')); ?>

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
                                <?php echo e(__('Readability')); ?>

                            </h4>
                            <div class="text-3xl font-bold text-gray-900 dark:text-gray-100">
                                <?php echo e(number_format($analysis->readability_score, 1)); ?>

                            </div>
                            <div class="mt-2 w-full bg-gray-200 rounded-full h-2 dark:bg-gray-700">
                                <div class="bg-green-600 h-2 rounded-full" style="width: <?php echo e($analysis->readability_score); ?>%"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Engagement Score -->
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">
                                <?php echo e(__('Engagement')); ?>

                            </h4>
                            <div class="text-3xl font-bold text-gray-900 dark:text-gray-100">
                                <?php echo e(number_format($analysis->engagement_score, 1)); ?>

                            </div>
                            <div class="mt-2 w-full bg-gray-200 rounded-full h-2 dark:bg-gray-700">
                                <div class="bg-blue-600 h-2 rounded-full" style="width: <?php echo e($analysis->engagement_score); ?>%"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Complexity Level -->
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">
                                <?php echo e(__('Complexity')); ?>

                            </h4>
                            <div class="text-2xl font-bold text-gray-900 dark:text-gray-100 capitalize">
                                <?php echo e($analysis->complexity_level ?? 'N/A'); ?>

                            </div>
                            <div class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                                <?php echo e(__('Target Level')); ?>: <?php echo e(ucfirst($course->difficulty_level)); ?>

                            </div>
                        </div>
                    </div>
                </div>

                <!-- Content Gaps -->
                <?php if($analysis->hasGaps()): ?>
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                                <?php echo e(__('Content Gaps Identified')); ?>

                            </h3>
                            
                            <?php if(!empty($analysis->content_gaps['missing_topics'])): ?>
                                <div class="mb-4">
                                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        <?php echo e(__('Missing Topics')); ?>

                                    </h4>
                                    <ul class="list-disc list-inside space-y-1">
                                        <?php $__currentLoopData = $analysis->content_gaps['missing_topics']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $topic): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <li class="text-sm text-gray-600 dark:text-gray-400"><?php echo e($topic); ?></li>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </ul>
                                </div>
                            <?php endif; ?>

                            <?php if(!empty($analysis->content_gaps['progression_gaps'])): ?>
                                <div class="mb-4">
                                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        <?php echo e(__('Progression Gaps')); ?>

                                    </h4>
                                    <ul class="list-disc list-inside space-y-1">
                                        <?php $__currentLoopData = $analysis->content_gaps['progression_gaps']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $gap): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <li class="text-sm text-gray-600 dark:text-gray-400"><?php echo e($gap); ?></li>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </ul>
                                </div>
                            <?php endif; ?>

                            <?php if(!empty($analysis->content_gaps['needs_more_depth'])): ?>
                                <div class="mb-4">
                                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        <?php echo e(__('Topics Needing More Depth')); ?>

                                    </h4>
                                    <ul class="list-disc list-inside space-y-1">
                                        <?php $__currentLoopData = $analysis->content_gaps['needs_more_depth']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $topic): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <li class="text-sm text-gray-600 dark:text-gray-400"><?php echo e($topic); ?></li>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </ul>
                                </div>
                            <?php endif; ?>

                            <?php if(!empty($analysis->content_gaps['missing_prerequisites'])): ?>
                                <div>
                                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        <?php echo e(__('Missing Prerequisites')); ?>

                                    </h4>
                                    <ul class="list-disc list-inside space-y-1">
                                        <?php $__currentLoopData = $analysis->content_gaps['missing_prerequisites']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $prereq): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <li class="text-sm text-gray-600 dark:text-gray-400"><?php echo e($prereq); ?></li>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Suggestions -->
                <?php if(!empty($analysis->suggestions)): ?>
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                                <?php echo e(__('Improvement Suggestions')); ?>

                            </h3>
                            <div class="space-y-4">
                                <?php $__currentLoopData = $analysis->suggestions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $suggestion): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <div class="border-l-4 <?php echo e($suggestion['priority'] === 'high' ? 'border-red-500' : ($suggestion['priority'] === 'medium' ? 'border-yellow-500' : 'border-blue-500')); ?> pl-4 py-2">
                                        <div class="flex items-start justify-between">
                                            <div class="flex-1">
                                                <div class="flex items-center gap-2 mb-1">
                                                    <span class="text-xs font-semibold px-2 py-1 rounded <?php echo e($suggestion['priority'] === 'high' ? 'bg-red-100 text-red-800' : ($suggestion['priority'] === 'medium' ? 'bg-yellow-100 text-yellow-800' : 'bg-blue-100 text-blue-800')); ?>">
                                                        <?php echo e(ucfirst($suggestion['priority'])); ?>

                                                    </span>
                                                    <span class="text-xs text-gray-500 dark:text-gray-400 capitalize">
                                                        <?php echo e($suggestion['category']); ?>

                                                    </span>
                                                </div>
                                                <p class="text-sm text-gray-700 dark:text-gray-300">
                                                    <?php echo e($suggestion['suggestion']); ?>

                                                </p>
                                                <?php if(isset($suggestion['impact'])): ?>
                                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                        <span class="font-medium"><?php echo e(__('Impact')); ?>:</span> <?php echo e($suggestion['impact']); ?>

                                                    </p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Accessibility Issues -->
                <?php if($analysis->hasAccessibilityIssues()): ?>
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                                <?php echo e(__('Accessibility Issues')); ?>

                            </h3>
                            <div class="space-y-3">
                                <?php $__currentLoopData = $analysis->accessibility_issues; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $issue): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <div class="flex items-start gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                        <div class="flex-shrink-0">
                                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-full <?php echo e($issue['severity'] === 'high' ? 'bg-red-100 text-red-600' : 'bg-yellow-100 text-yellow-600'); ?>">
                                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                                </svg>
                                            </span>
                                        </div>
                                        <div class="flex-1">
                                            <div class="flex items-center gap-2 mb-1">
                                                <span class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">
                                                    <?php echo e($issue['type']); ?>

                                                </span>
                                                <?php if(isset($issue['lesson'])): ?>
                                                    <span class="text-xs text-gray-400 dark:text-gray-500">
                                                        • <?php echo e($issue['lesson']); ?>

                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <p class="text-sm text-gray-700 dark:text-gray-300">
                                                <?php echo e($issue['description']); ?>

                                            </p>
                                        </div>
                                    </div>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

            <?php else: ?>
                <!-- No Analysis Yet -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-12 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">
                            <?php echo e(__('No Analysis Available')); ?>

                        </h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            <?php echo e(__('Click the "Analyze Content" button to start analyzing this course.')); ?>

                        </p>
                    </div>
                </div>
            <?php endif; ?>
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
<?php /**PATH C:\wamp64\www\corporate-lms\resources\views/admin/content-analyzer/show.blade.php ENDPATH**/ ?>