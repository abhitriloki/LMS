<?php

namespace App\Providers;

use App\Models\Assessment;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\Enrollment;
use App\Models\User;
use App\Policies\AssessmentPolicy;
use App\Policies\CertificatePolicy;
use App\Policies\CourseCategoryPolicy;
use App\Policies\CoursePolicy;
use App\Policies\EnrollmentPolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Course::class => CoursePolicy::class,
        CourseCategory::class => CourseCategoryPolicy::class,
        Assessment::class => AssessmentPolicy::class,
        Enrollment::class => EnrollmentPolicy::class,
        User::class => UserPolicy::class,
        Certificate::class => CertificatePolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        //
    }
}
