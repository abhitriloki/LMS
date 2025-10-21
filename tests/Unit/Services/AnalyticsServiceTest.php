<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\AnalyticsService;
use App\Models\User;
use App\Models\Course;
use App\Models\Department;
use App\Models\Enrollment;
use App\Models\AssessmentAttempt;
use App\Models\AnalyticsEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

class AnalyticsServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AnalyticsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AnalyticsService();
    }

    public function test_track_event_creates_analytics_event()
    {
        $user = User::factory()->create();

        $event = $this->service->trackEvent('test_event', $user, ['key' => 'value']);

        $this->assertInstanceOf(AnalyticsEvent::class, $event);
        $this->assertEquals('test_event', $event->event_type);
        $this->assertEquals($user->id, $event->user_id);
        $this->assertEquals(['key' => 'value'], $event->event_data);
    }

    public function test_get_user_analytics_returns_correct_structure()
    {
        $user = User::factory()->create();
        $course = Course::factory()->create(['is_published' => true]);
        Enrollment::factory()->create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'status' => 'completed'
        ]);

        $analytics = $this->service->getUserAnalytics($user);

        $this->assertIsArray($analytics);
        $this->assertArrayHasKey('enrollments', $analytics);
        $this->assertArrayHasKey('progress', $analytics);
        $this->assertArrayHasKey('assessments', $analytics);
        $this->assertArrayHasKey('activity', $analytics);
        $this->assertArrayHasKey('certificates', $analytics);
        $this->assertArrayHasKey('learning_time', $analytics);
    }

    public function test_get_user_enrollment_stats()
    {
        $user = User::factory()->create();
        $course = Course::factory()->create(['is_published' => true]);
        
        Enrollment::factory()->create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'status' => 'active',
            'progress_percentage' => 50
        ]);
        
        Enrollment::factory()->create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'status' => 'completed'
        ]);

        $analytics = $this->service->getUserAnalytics($user);

        $this->assertEquals(2, $analytics['enrollments']['total']);
        $this->assertEquals(1, $analytics['enrollments']['active']);
        $this->assertEquals(1, $analytics['enrollments']['completed']);
    }

    public function test_get_course_analytics_returns_correct_structure()
    {
        $course = Course::factory()->create(['is_published' => true]);
        $user = User::factory()->create();
        Enrollment::factory()->create([
            'user_id' => $user->id,
            'course_id' => $course->id
        ]);

        $analytics = $this->service->getCourseAnalytics($course);

        $this->assertIsArray($analytics);
        $this->assertArrayHasKey('enrollments', $analytics);
        $this->assertArrayHasKey('completion', $analytics);
        $this->assertArrayHasKey('engagement', $analytics);
        $this->assertArrayHasKey('assessments', $analytics);
    }

    public function test_get_course_completion_stats()
    {
        $course = Course::factory()->create(['is_published' => true]);
        
        Enrollment::factory()->count(5)->create([
            'course_id' => $course->id,
            'status' => 'active'
        ]);
        
        Enrollment::factory()->count(3)->create([
            'course_id' => $course->id,
            'status' => 'completed'
        ]);

        $analytics = $this->service->getCourseAnalytics($course);

        $this->assertEquals(8, $analytics['completion']['total_enrollments']);
        $this->assertEquals(3, $analytics['completion']['completed']);
        $this->assertEquals(37.5, $analytics['completion']['completion_rate']);
    }

    public function test_get_department_analytics_returns_correct_structure()
    {
        $department = Department::factory()->create();
        $user = User::factory()->create(['department_id' => $department->id]);

        $analytics = $this->service->getDepartmentAnalytics($department);

        $this->assertIsArray($analytics);
        $this->assertArrayHasKey('users', $analytics);
        $this->assertArrayHasKey('enrollments', $analytics);
        $this->assertArrayHasKey('completion', $analytics);
        $this->assertArrayHasKey('compliance', $analytics);
        $this->assertArrayHasKey('top_performers', $analytics);
        $this->assertArrayHasKey('popular_courses', $analytics);
    }

    public function test_get_dashboard_metrics_returns_correct_structure()
    {
        User::factory()->count(5)->create();
        Course::factory()->count(3)->create(['is_published' => true]);

        $metrics = $this->service->getDashboardMetrics();

        $this->assertIsArray($metrics);
        $this->assertArrayHasKey('total_users', $metrics);
        $this->assertArrayHasKey('active_users', $metrics);
        $this->assertArrayHasKey('total_courses', $metrics);
        $this->assertArrayHasKey('total_enrollments', $metrics);
        $this->assertArrayHasKey('completed_courses', $metrics);
        $this->assertArrayHasKey('certificates_issued', $metrics);
        $this->assertArrayHasKey('average_completion_rate', $metrics);
        $this->assertArrayHasKey('total_learning_hours', $metrics);
    }

    public function test_analytics_data_is_cached()
    {
        $user = User::factory()->create();
        
        Cache::shouldReceive('remember')
            ->once()
            ->andReturn([
                'enrollments' => ['total' => 0],
                'progress' => ['average_progress' => 0],
                'assessments' => ['total_attempts' => 0],
                'activity' => ['total_events' => 0],
                'certificates' => ['total' => 0],
                'learning_time' => 0
            ]);

        $this->service->getUserAnalytics($user);
    }

    public function test_clear_cache_removes_analytics_cache()
    {
        Cache::shouldReceive('forget')->once();
        Cache::shouldReceive('flush')->never();

        $this->service->clearCache('user', 1);
    }

    public function test_clear_all_cache()
    {
        Cache::shouldReceive('flush')->once();

        $this->service->clearCache();
    }

    public function test_determine_category_returns_correct_category()
    {
        $user = User::factory()->create();

        $event = $this->service->trackEvent('course_view', $user);
        $this->assertEquals('course', $event->event_category);

        $event = $this->service->trackEvent('lesson_view', $user);
        $this->assertEquals('content', $event->event_category);

        $event = $this->service->trackEvent('assessment_start', $user);
        $this->assertEquals('assessment', $event->event_category);
    }
}
