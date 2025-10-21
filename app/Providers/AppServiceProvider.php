<?php

namespace App\Providers;

use App\Events\CourseCompleted;
use App\Listeners\GenerateCertificate;
use App\Models\Course;
use App\Models\Enrollment;
use App\Observers\CourseObserver;
use App\Observers\EnrollmentObserver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Schema::defaultStringLength(191);
        
        // Register model observers for cache invalidation
        Course::observe(CourseObserver::class);
        Enrollment::observe(EnrollmentObserver::class);
        
        // Register event listeners
        Event::listen(
            CourseCompleted::class,
            GenerateCertificate::class,
        );
        
        // Configure API rate limiting
        $this->configureRateLimiting();
    }
    
    /**
     * Configure the rate limiters for the application.
     */
    protected function configureRateLimiting(): void
    {
        // General API rate limit: 60 requests per minute
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
        
        // Authentication endpoints: 5 requests per minute
        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });
        
        // Heavy operations: 10 requests per minute
        RateLimiter::for('heavy', function (Request $request) {
            return Limit::perMinute(10)->by($request->user()?->id ?: $request->ip());
        });
    }
}
