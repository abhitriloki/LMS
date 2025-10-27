

<?php $__env->startSection('title', 'Login'); ?>

<?php $__env->startSection('content'); ?>
<div>
    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Sign in to your account</h2>

    <form method="POST" action="<?php echo e(route('login')); ?>" class="space-y-6">
        <?php echo csrf_field(); ?>

        <!-- Email Address -->
        <div>
            <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Email address
            </label>
            <input id="email" type="email" name="email" value="<?php echo e(old('email')); ?>" required autofocus
                   class="input <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-red-500 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
            <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                <p class="mt-1 text-sm text-red-600 dark:text-red-400"><?php echo e($message); ?></p>
            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        </div>

        <!-- Password -->
        <div>
            <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Password
            </label>
            <input id="password" type="password" name="password" required
                   class="input <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-red-500 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
            <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                <p class="mt-1 text-sm text-red-600 dark:text-red-400"><?php echo e($message); ?></p>
            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        </div>

        <!-- Remember Me -->
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <input id="remember" type="checkbox" name="remember"
                       class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                <label for="remember" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                    Remember me
                </label>
            </div>

            <div class="text-sm">
                <a href="<?php echo e(route('password.request')); ?>" class="font-medium text-primary-600 hover:text-primary-500 dark:text-primary-400">
                    Forgot password?
                </a>
            </div>
        </div>

        <!-- Submit Button -->
        <div>
            <button type="submit" class="w-full btn btn-primary">
                Sign in
            </button>
        </div>

        <!-- Register Link -->
        <div class="text-center text-sm">
            <span class="text-gray-600 dark:text-gray-400">Don't have an account?</span>
            <a href="<?php echo e(route('register')); ?>" class="font-medium text-primary-600 hover:text-primary-500 dark:text-primary-400">
                Register here
            </a>
        </div>
    </form>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.guest', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\wamp64\www\corporate-lms\resources\views/auth/login.blade.php ENDPATH**/ ?>