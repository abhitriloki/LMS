@echo off
echo ===================================
echo Controller Fixes Verification
echo ===================================
echo.

echo 1. Checking database data...
php artisan tinker --execute="echo 'Users: ' . App\Models\User::count() . PHP_EOL; echo 'Courses: ' . App\Models\Course::count() . PHP_EOL; echo 'Categories: ' . App\Models\CourseCategory::count() . PHP_EOL; echo 'Assessments: ' . App\Models\Assessment::count() . PHP_EOL; echo 'Enrollments: ' . App\Models\Enrollment::count() . PHP_EOL;"

echo.
echo 2. Checking admin user...
php artisan tinker --execute="$admin = App\Models\User::where('email', 'admin@test.com')->first(); if ($admin) { echo 'Admin user exists: ' . $admin->name . ' (' . $admin->email . ')' . PHP_EOL; echo 'Role: ' . $admin->role . PHP_EOL; } else { echo 'Admin user not found' . PHP_EOL; }"

echo.
echo ===================================
echo Verification Complete!
echo ===================================
echo.
echo Login at: http://127.0.0.1:8000
echo Email: admin@test.com
echo Password: password123
echo.
pause
