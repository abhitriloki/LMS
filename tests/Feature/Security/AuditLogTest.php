<?php

namespace Tests\Feature\Security;

use App\Models\AuditLog;
use App\Models\Course;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    protected AuditLogService $auditLogService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->auditLogService = app(AuditLogService::class);
    }

    public function test_audit_log_is_created_on_model_creation(): void
    {
        $user = User::factory()->create(['role' => 'instructor']);
        $this->actingAs($user);

        $course = Course::factory()->create(['created_by' => $user->id]);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'event_type' => 'created',
            'auditable_type' => Course::class,
            'auditable_id' => $course->id,
        ]);
    }

    public function test_audit_log_is_created_on_model_update(): void
    {
        $user = User::factory()->create(['role' => 'instructor']);
        $this->actingAs($user);

        $course = Course::factory()->create(['created_by' => $user->id]);
        
        // Clear the creation log
        AuditLog::truncate();

        $course->update(['title' => 'Updated Title']);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'event_type' => 'updated',
            'auditable_type' => Course::class,
            'auditable_id' => $course->id,
        ]);
    }

    public function test_audit_log_is_created_on_model_deletion(): void
    {
        $user = User::factory()->create(['role' => 'instructor']);
        $this->actingAs($user);

        $course = Course::factory()->create(['created_by' => $user->id]);
        
        // Clear previous logs
        AuditLog::truncate();

        $course->delete();

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'event_type' => 'deleted',
            'auditable_type' => Course::class,
            'auditable_id' => $course->id,
        ]);
    }

    public function test_login_is_logged(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->auditLogService->logLogin($user, true);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'event_type' => 'login_success',
        ]);
    }

    public function test_failed_login_is_logged(): void
    {
        $user = User::factory()->create();

        $this->auditLogService->logLogin($user, false);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'event_type' => 'login_failed',
        ]);
    }

    public function test_logout_is_logged(): void
    {
        $user = User::factory()->create();

        $this->auditLogService->logLogout($user);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'event_type' => 'logout',
        ]);
    }

    public function test_permission_change_is_logged(): void
    {
        $user = User::factory()->create(['role' => 'employee']);

        $this->auditLogService->logPermissionChange($user, 'employee', 'instructor');

        $log = AuditLog::where('user_id', $user->id)
            ->where('event_type', 'permission_changed')
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals('employee', $log->old_values['role']);
        $this->assertEquals('instructor', $log->new_values['role']);
    }

    public function test_security_event_is_logged(): void
    {
        $this->actingAs(User::factory()->create());

        $this->auditLogService->logSecurityEvent(
            'suspicious_activity',
            'Multiple failed login attempts detected',
            ['attempts' => 5]
        );

        $this->assertDatabaseHas('audit_logs', [
            'event_type' => 'security_suspicious_activity',
            'description' => 'Multiple failed login attempts detected',
        ]);
    }

    public function test_admin_can_view_audit_logs(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        
        AuditLog::factory()->count(5)->create();

        $response = $this->actingAs($admin)->get(route('admin.audit-logs.index'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.audit-logs.index');
        $response->assertViewHas('logs');
    }

    public function test_non_admin_cannot_view_audit_logs(): void
    {
        $user = User::factory()->create(['role' => 'employee']);

        $response = $this->actingAs($user)->get(route('admin.audit-logs.index'));

        $response->assertStatus(403);
    }

    public function test_audit_logs_can_be_filtered_by_event_type(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        
        AuditLog::factory()->create(['event_type' => 'created']);
        AuditLog::factory()->create(['event_type' => 'updated']);
        AuditLog::factory()->create(['event_type' => 'deleted']);

        $response = $this->actingAs($admin)->get(route('admin.audit-logs.index', ['event_type' => 'created']));

        $response->assertStatus(200);
    }

    public function test_audit_logs_can_be_exported_to_csv(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        
        AuditLog::factory()->count(3)->create();

        $response = $this->actingAs($admin)->get(route('admin.audit-logs.export'));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }

    public function test_audit_log_stores_ip_address_and_user_agent(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $course = Course::factory()->create(['created_by' => $user->id]);

        $log = AuditLog::where('auditable_id', $course->id)->first();

        $this->assertNotNull($log->ip_address);
        $this->assertNotNull($log->user_agent);
    }
}
