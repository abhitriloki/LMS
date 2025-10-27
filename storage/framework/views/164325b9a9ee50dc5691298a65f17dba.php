

<?php $__env->startSection('title', 'Dashboard'); ?>

<?php $__env->startSection('page-title', 'Dashboard'); ?>

<?php $__env->startSection('content'); ?>
<?php if(Auth::user()->role === 'super_admin' || Auth::user()->role === 'admin'): ?>
    <?php echo $__env->make('dashboard.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php elseif(Auth::user()->role === 'instructor'): ?>
    <?php echo $__env->make('dashboard.instructor', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php else: ?>
    <?php echo $__env->make('dashboard.employee', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.dashboard', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\wamp64\www\corporate-lms\resources\views/dashboard.blade.php ENDPATH**/ ?>