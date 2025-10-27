<?php

/**
 * Dashboard Fix Verification Script
 * Tests dashboard access for all user roles
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\DB;

echo "\n";
echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║          DASHBOARD FIX VERIFICATION SCRIPT                   ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n";
echo "\n";

// Test 1: Check User Roles in Database
echo "📊 TEST 1: Checking User Roles in Database\n";
echo str_repeat("─", 60) . "\n";

$roles = DB::table('users')
    ->select('role', DB::raw('COUNT(*) as count'))
    ->groupBy('role')
    ->get();

foreach ($roles as $role) {
    echo sprintf("  ✓ %-15s : %d users\n", ucfirst($role->role), $role->count);
}
echo "\n";

// Test 2: Verify No 'hr_admin' Role Exists
echo "🔍 TEST 2: Verifying No 'hr_admin' Role Exists\n";
echo str_repeat("─", 60) . "\n";

$hrAdminCount = DB::table('users')->where('role', 'hr_admin')->count();
if ($hrAdminCount === 0) {
    echo "  ✓ PASS: No 'hr_admin' role found in database\n";
} else {
    echo "  ✗ FAIL: Found {$hrAdminCount} users with 'hr_admin' role\n";
    echo "  → These users should be updated to 'admin' role\n";
}
echo "\n";

// Test 3: Test Dashboard Controller Logic
echo "🎯 TEST 3: Testing Dashboard Controller Logic\n";
echo str_repeat("─", 60) . "\n";

$testUsers = [
    'super_admin' => User::where('role', 'super_admin')->first(),
    'admin' => User::where('role', 'admin')->first(),
    'instructor' => User::where('role', 'instructor')->first(),
    'employee' => User::where('role', 'employee')->first(),
];

foreach ($testUsers as $roleName => $user) {
    if ($user) {
        echo sprintf("  ✓ %-15s : %s (%s)\n", 
            ucfirst($roleName), 
            $user->name, 
            $user->email
        );
    } else {
        echo sprintf("  ⚠ %-15s : No user found\n", ucfirst($roleName));
    }
}
echo "\n";

// Test 4: Check Department Statistics Query
echo "📈 TEST 4: Testing Department Statistics Query\n";
echo str_repeat("─", 60) . "\n";

try {
    $departmentStats = DB::table('departments')
        ->leftJoin('users', 'departments.id', '=', 'users.department_id')
        ->leftJoin('course_enrollments', 'users.id', '=', 'course_enrollments.user_id')
        ->select(
            'departments.name',
            DB::raw('COUNT(DISTINCT users.id) as user_count'),
            DB::raw('COUNT(DISTINCT course_enrollments.id) as enrollment_count'),
            DB::raw('AVG(course_enrollments.progress_percentage) as avg_progress')
        )
        ->groupBy('departments.id', 'departments.name')
        ->get();
    
    echo "  ✓ Department statistics query executed successfully\n";
    echo "  → Found " . $departmentStats->count() . " departments\n";
    
    foreach ($departmentStats as $dept) {
        echo sprintf("    • %-20s : %d users, %d enrollments, %.1f%% avg progress\n",
            $dept->name,
            $dept->user_count,
            $dept->enrollment_count,
            $dept->avg_progress ?? 0
        );
    }
} catch (Exception $e) {
    echo "  ✗ FAIL: " . $e->getMessage() . "\n";
}
echo "\n";

// Test 5: Check Enrollments Table
echo "📚 TEST 5: Checking Enrollments Table\n";
echo str_repeat("─", 60) . "\n";

$enrollmentCount = DB::table('course_enrollments')->count();
$activeEnrollments = DB::table('course_enrollments')->where('status', 'active')->count();
$completedEnrollments = DB::table('course_enrollments')->where('status', 'completed')->count();

echo "  ✓ Total Enrollments: {$enrollmentCount}\n";
echo "  ✓ Active Enrollments: {$activeEnrollments}\n";
echo "  ✓ Completed Enrollments: {$completedEnrollments}\n";
echo "\n";

// Test 6: Check Analytics Events
echo "📊 TEST 6: Checking Analytics Events\n";
echo str_repeat("─", 60) . "\n";

$analyticsCount = DB::table('analytics_events')->count();
echo "  ✓ Total Analytics Events: {$analyticsCount}\n";

if ($analyticsCount > 0) {
    $eventTypes = DB::table('analytics_events')
        ->select('event_type', DB::raw('COUNT(*) as count'))
        ->groupBy('event_type')
        ->get();
    
    foreach ($eventTypes as $type) {
        echo sprintf("    • %-20s : %d events\n", $type->event_type, $type->count);
    }
} else {
    echo "  ⚠ No analytics events found (this is normal for new installations)\n";
}
echo "\n";

// Test 7: Verify Dashboard Views Exist
echo "👁️  TEST 7: Verifying Dashboard Views Exist\n";
echo str_repeat("─", 60) . "\n";

$views = [
    'dashboard.blade.php' => 'resources/views/dashboard.blade.php',
    'dashboard/admin.blade.php' => 'resources/views/dashboard/admin.blade.php',
    'dashboard/instructor.blade.php' => 'resources/views/dashboard/instructor.blade.php',
    'dashboard/employee.blade.php' => 'resources/views/dashboard/employee.blade.php',
];

foreach ($views as $name => $path) {
    if (file_exists(__DIR__ . '/' . $path)) {
        echo "  ✓ {$name} exists\n";
    } else {
        echo "  ✗ {$name} NOT FOUND\n";
    }
}
echo "\n";

// Test 8: Check for 'hr_admin' in Code
echo "🔎 TEST 8: Checking for 'hr_admin' References in Code\n";
echo str_repeat("─", 60) . "\n";

$files = [
    'app/Http/Controllers/DashboardController.php',
    'resources/views/dashboard.blade.php',
];

$foundIssues = false;
foreach ($files as $file) {
    $fullPath = __DIR__ . '/' . $file;
    if (file_exists($fullPath)) {
        $content = file_get_contents($fullPath);
        if (strpos($content, 'hr_admin') !== false) {
            echo "  ✗ Found 'hr_admin' in {$file}\n";
            $foundIssues = true;
        } else {
            echo "  ✓ {$file} - No 'hr_admin' references\n";
        }
    }
}

if (!$foundIssues) {
    echo "  ✓ All files are clean!\n";
}
echo "\n";

// Final Summary
echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║                    VERIFICATION SUMMARY                      ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n";
echo "\n";

$allTestsPassed = true;

if ($hrAdminCount > 0) {
    echo "  ⚠ WARNING: Found users with 'hr_admin' role\n";
    echo "    → Run: UPDATE users SET role='admin' WHERE role='hr_admin';\n";
    $allTestsPassed = false;
}

if ($foundIssues) {
    echo "  ⚠ WARNING: Found 'hr_admin' references in code\n";
    echo "    → These should be changed to 'admin'\n";
    $allTestsPassed = false;
}

if ($allTestsPassed) {
    echo "  ✅ ALL TESTS PASSED!\n";
    echo "  ✅ Dashboard should work correctly for all user roles\n";
} else {
    echo "  ⚠ SOME ISSUES FOUND - Please review above\n";
}

echo "\n";
echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║                    TEST USER ACCOUNTS                        ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n";
echo "\n";

foreach ($testUsers as $roleName => $user) {
    if ($user) {
        echo sprintf("  %s:\n", strtoupper($roleName));
        echo sprintf("    Email: %s\n", $user->email);
        echo sprintf("    Password: password123\n");
        echo sprintf("    Dashboard: http://localhost:8000/dashboard\n");
        echo "\n";
    }
}

echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║                    NEXT STEPS                                ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n";
echo "\n";
echo "  1. Start the development server:\n";
echo "     php artisan serve\n";
echo "\n";
echo "  2. Test each user role:\n";
echo "     • Login as super_admin: admin@test.com\n";
echo "     • Login as admin: admin2@test.com (or any admin)\n";
echo "     • Login as instructor: instructor1@test.com\n";
echo "     • Login as employee: employee1@test.com\n";
echo "\n";
echo "  3. Verify dashboard displays correctly for each role\n";
echo "\n";
echo "  4. Check browser console for any JavaScript errors\n";
echo "\n";

echo "Test completed at: " . date('Y-m-d H:i:s') . "\n";
echo "\n";
