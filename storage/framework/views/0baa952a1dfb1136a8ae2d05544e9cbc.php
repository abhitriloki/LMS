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
                <?php echo e(__('Certificate Details')); ?>

            </h2>
            <a href="<?php echo e(route('admin.certificates.index')); ?>" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200">← Back to Certificates</a>
        </div>
     <?php $__env->endSlot(); ?>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Certificate Number</div>
                            <div class="text-lg font-semibold text-gray-900 dark:text-white"><?php echo e($certificate->certificate_number); ?></div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Issued At</div>
                            <div class="text-lg font-semibold text-gray-900 dark:text-white"><?php echo e(optional($certificate->issued_at)->format('M d, Y') ?: '—'); ?></div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">User</div>
                            <div class="text-lg font-semibold text-gray-900 dark:text-white"><?php echo e($certificate->user?->name); ?> <span class="text-sm text-gray-500"><?php echo e($certificate->user?->email); ?></span></div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Course</div>
                            <div class="text-lg font-semibold text-gray-900 dark:text-white"><?php echo e($certificate->course?->title); ?></div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Expires At</div>
                            <div class="text-lg font-semibold text-gray-900 dark:text-white"><?php echo e(optional($certificate->expires_at)->format('M d, Y') ?: 'Never'); ?></div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Status</div>
                            <div>
                                <?php if($certificate->isValid()): ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Valid</span>
                                <?php elseif($certificate->isExpired()): ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Expired</span>
                                <?php else: ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">Invalid</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center space-x-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <a href="<?php echo e(route('certificates.download', $certificate->id)); ?>" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Download PDF</a>
                        <form method="POST" action="<?php echo e(route('admin.certificates.regenerate', $certificate)); ?>">
                            <?php echo csrf_field(); ?>
                            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">Regenerate PDF</button>
                        </form>
                        <a href="<?php echo e(route('admin.certificates.index')); ?>" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">Back</a>
                    </div>
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
<?php /**PATH C:\wamp64\www\corporate-lms\resources\views/admin/certificates/show.blade.php ENDPATH**/ ?>