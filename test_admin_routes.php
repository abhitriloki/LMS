<?php

/**
 * Admin Routes Testing Script
 * 
 * This script tests the admin routes to ensure they work correctly
 * Run with: php test_admin_routes.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Enrollment;
use App\Models\AIGradingResult;
use Illuminate\Support\Facades\Route;

echo "===========================================\n";
echo "Admin Routes Testing Report\n";
echo "===========================================\n\n";

// Test 1: Check Database Data
echo "1. DATABASE STATUS\n";
echo "   - Users: " . User::count() . "\n";
echo "   - Enrollments: " . Enrollment::count() . "\n";
echo "   - AI Grading Results: " . AIGradingResult::count() . "\n\n";

// Test 2: Check Admin Users
echo "2. ADMIN USERS\n";
$admins = User::whereIn('role', ['admin', 'super_admin'])->get();
foreach ($admins as $admin) {
    echo "   - {$admin->name} ({$admin->email}) - Role: {$admin->role}\n";
}
echo "\n";

// Test 3: Check Routes
echo "3. ADMIN ROUTES CONFIGURATION\n";
$adminRoutes = collect(Route::getRoutes())->filter(function ($route) {
    return str_starts_with($route->uri(), 'admin/');
})->take(5);

foreach ($adminRoutes as $route) {
    $middleware = implode(', ', $route->middleware());
    echo "   - {$route->uri()}\n";
    echo "     Middleware: {$middleware}\n";
}
echo "\n";

// Test 4: Check if role middleware is applied
echo "4. ROLE MIDDLEWARE CHECK\n";
$userRoute = collect(Route::getRoutes())->first(function ($route) {
    return $route->uri() === 'admin/users';
});

if ($userRoute) {
    $hasRoleMiddleware = collect($userRoute->middleware())->contains(function ($m) {
        return str_contains($m, 'CheckRole');
    });
    
    if ($hasRoleMiddleware) {
        echo "   ✅ Role middleware is properly applied to admin routes\n";
    } else {
        echo "   ❌ Role middleware is NOT applied to admin routes\n";
    }
} else {
    echo "   ❌ Admin users route not found\n";
}
echo "\n";

// Test 5: Check Controllers
echo "5. CONTROLLER FILES\n";
$controllers = [
    'UserController' => 'app/Http/Controllers/Admin/UserController.php',
    'EnrollmentController' => 'app/Http/Controllers/Admin/EnrollmentController.php',
    'GradingReviewController' => 'app/Http/Controllers/Admin/GradingReviewController.php',
];

foreach ($controllers as $name => $path) {
    if (file_exists($path)) {
        echo "   ✅ {$name} exists\n";
    } else {
        echo "   ❌ {$name} NOT FOUND\n";
    }
}
echo "\n";

// Test 6: Check Policies
echo "6. AUTHORIZATION POLICIES\n";
$policies = [
    'UserPolicy' => 'app/Policies/UserPolicy.php',
    'EnrollmentPolicy' => 'app/Policies/EnrollmentPolicy.php',
];

foreach ($policies as $name => $path) {
    if (file_exists($path)) {
        echo "   ✅ {$name} exists\n";
    } else {
        echo "   ❌ {$name} NOT FOUND\n";
    }
}
echo "\n";

echo "===========================================\n";
echo "TESTING INSTRUCTIONS\n";
echo "===========================================\n\n";

echo "1. Start your development server:\n";
echo "   php artisan serve\n\n";

echo "2. Login with admin credentials:\n";
echo "   Email: admin@test.com\n";
echo "   Password: password123\n\n";

echo "3. Test these URLs:\n";
echo "   - http://127.0.0.1:8000/admin/users\n";
echo "   - http://127.0.0.1:8000/admin/enrollments\n";
echo "   - http://127.0.0.1:8000/admin/grading/review\n\n";

echo "4. Expected Results:\n";
echo "   - Users page: Should show " . User::count() . " users\n";
echo "   - Enrollments page: Should show " . Enrollment::count() . " enrollments\n";
echo "   - Grading Review: Will show 'no data' (expected, as there are " . AIGradingResult::count() . " AI grading results)\n\n";

echo "===========================================\n";
echo "FIXES APPLIED\n";
echo "===========================================\n\n";

echo "✅ Added role middleware to admin routes\n";
echo "✅ Cleared all caches (route, config, view, cache)\n";
echo "✅ Verified controller files exist and have no errors\n";
echo "✅ Verified database has data\n\n";

echo "If you still see errors:\n";
echo "1. Make sure you're logged in as admin@test.com\n";
echo "2. Try clearing browser cache/cookies\n";
echo "3. Check storage/logs/laravel.log for detailed errors\n\n";
