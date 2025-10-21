<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Course;
use App\Models\Department;
use App\Models\Enrollment;
use App\Models\AnalyticsEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AnalyticsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'super_admin']);
    }

    public function test_analytics_dashboard_page_loads()
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.analytics.index'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.analytics.index');
        $response->assertViewHas('metrics');
    }

    public function test_get_dashboard_data_returns_json()
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.analytics.dashboard-data'));

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'total_users',
                'active_users',
                'total_courses',
                'total_enrollments',
                'completed_courses',
                'certificates_issued',
                'average_completion_rate',
                'total_learning_hours'
            ]
        ]);
    }

    public function test_get_user_analytics_returns_json()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($this->admin)
            ->get(route('admin.analytics.user', $user));

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'enrollments',
                'progress',
                'assessments',
                'activity',
                'certificates',
                'learning_time'
            ]
        ]);
    }

    public function test_get_course_analytics_returns_json()
    {
        $course = Course::factory()->create(['is_published' => true]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.analytics.course', $course));

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'enrollments',
                'completion',
                'engagement',
                'assessments'
            ]
        ]);
    }

    public function test_get_department_analytics_returns_json()
    {
        $department = Department::factory()->create();

        $response = $this->actingAs($this->admin)
            ->get(route('admin.analytics.department', $department));

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'users',
                'enrollments',
                'completion',
                'compliance',
                'top_performers',
                'popular_courses'
            ]
        ]);
    }

    public function test_track_event_creates_analytics_event()
    {
        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.analytics.track-event'), [
                'event_type' => 'test_event',
                'properties' => ['key' => 'value']
            ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('analytics_events', [
            'event_type' => 'test_event',
            'user_id' => $this->admin->id
        ]);
    }

    public function test_get_enrollment_trends_returns_data()
    {
        $course = Course::factory()->create(['is_published' => true]);
        $user = User::factory()->create();
        
        Enrollment::factory()->create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'created_at' => now()->subDays(5)
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.analytics.enrollment-trends', [
                'start_date' => now()->subDays(7)->format('Y-m-d'),
                'end_date' => now()->format('Y-m-d')
            ]));

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $response->assertJsonStructure(['success', 'data']);
    }

    public function test_get_completion_trends_returns_data()
    {
        $course = Course::factory()->create(['is_published' => true]);
        $user = User::factory()->create();
        
        Enrollment::factory()->create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'status' => 'completed',
            'completion_date' => now()->subDays(3)
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.analytics.completion-trends', [
                'start_date' => now()->subDays(7)->format('Y-m-d'),
                'end_date' => now()->format('Y-m-d')
            ]));

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $response->assertJsonStructure(['success', 'data']);
    }

    public function test_get_activity_heatmap_returns_data()
    {
        AnalyticsEvent::factory()->create([
            'user_id' => $this->admin->id,
            'event_type' => 'course_view',
            'created_at' => now()->subDays(2)
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.analytics.activity-heatmap', [
                'start_date' => now()->subDays(7)->format('Y-m-d'),
                'end_date' => now()->format('Y-m-d')
            ]));

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $response->assertJsonStructure(['success', 'data']);
    }

    public function test_clear_cache_succeeds()
    {
        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.analytics.clear-cache'));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Analytics cache cleared successfully'
        ]);
    }

    public function test_unauthorized_user_cannot_access_analytics()
    {
        $user = User::factory()->create(['role' => 'employee']);

        $response = $this->actingAs($user)
            ->get(route('admin.analytics.index'));

        $response->assertStatus(403);
    }

    public function test_guest_cannot_access_analytics()
    {
        $response = $this->get(route('admin.analytics.index'));

        $response->assertRedirect(route('login'));
    }
}
