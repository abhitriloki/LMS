<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Report;
use App\Models\Course;
use App\Models\Department;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'super_admin']);
        Storage::fake('local');
    }

    public function test_reports_index_page_loads()
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.reports.index'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.reports.index');
        $response->assertViewHas('reports');
    }

    public function test_report_create_page_loads()
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.reports.create'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.reports.create');
        $response->assertViewHas('reportTypes');
    }

    public function test_generate_report_creates_report()
    {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.reports.store'), [
                'name' => 'Test Report',
                'report_type' => 'user_activity',
                'format' => 'pdf',
                'start_date' => now()->subDays(30)->format('Y-m-d'),
                'end_date' => now()->format('Y-m-d')
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('reports', [
            'name' => 'Test Report',
            'report_type' => 'user_activity',
            'generated_by' => $this->admin->id
        ]);
    }

    public function test_generate_report_with_filters()
    {
        $department = Department::factory()->create();

        $response = $this->actingAs($this->admin)
            ->post(route('admin.reports.store'), [
                'report_type' => 'department_performance',
                'format' => 'excel',
                'department_id' => $department->id,
                'start_date' => now()->subDays(30)->format('Y-m-d'),
                'end_date' => now()->format('Y-m-d')
            ]);

        $response->assertRedirect();
        
        $report = Report::latest()->first();
        $this->assertEquals($department->id, $report->parameters['department_id']);
    }

    public function test_schedule_report_creates_scheduled_report()
    {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.reports.schedule'), [
                'name' => 'Weekly Report',
                'report_type' => 'compliance',
                'format' => 'pdf',
                'schedule' => 'weekly'
            ]);

        $response->assertRedirect();
        
        $report = Report::latest()->first();
        $this->assertTrue($report->scheduled);
        $this->assertEquals('weekly', $report->schedule_config['frequency']);
    }

    public function test_view_report_details()
    {
        $report = Report::factory()->create([
            'generated_by' => $this->admin->id,
            'status' => 'completed'
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.reports.show', $report));

        $response->assertStatus(200);
        $response->assertViewIs('admin.reports.show');
        $response->assertViewHas('report');
    }

    public function test_download_completed_report()
    {
        $report = Report::factory()->create([
            'status' => 'completed',
            'file_path' => 'reports/test.pdf'
        ]);

        Storage::put($report->file_path, 'test content');

        $response = $this->actingAs($this->admin)
            ->get(route('admin.reports.download', $report));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_cannot_download_pending_report()
    {
        $report = Report::factory()->create([
            'status' => 'pending',
            'file_path' => null
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.reports.download', $report));

        $response->assertStatus(404);
    }

    public function test_delete_report()
    {
        $report = Report::factory()->create([
            'generated_by' => $this->admin->id
        ]);

        $response = $this->actingAs($this->admin)
            ->delete(route('admin.reports.destroy', $report));

        $response->assertRedirect();
        $this->assertDatabaseMissing('reports', ['id' => $report->id]);
    }

    public function test_view_scheduled_reports()
    {
        Report::factory()->count(3)->create([
            'scheduled' => true,
            'generated_by' => $this->admin->id
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.reports.scheduled'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.reports.scheduled');
        $response->assertViewHas('reports');
    }

    public function test_disable_scheduled_report()
    {
        $report = Report::factory()->create([
            'scheduled' => true,
            'generated_by' => $this->admin->id
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.reports.disable-schedule', $report));

        $response->assertRedirect();
        
        $report->refresh();
        $this->assertFalse($report->scheduled);
    }

    public function test_enable_scheduled_report()
    {
        $report = Report::factory()->create([
            'scheduled' => false,
            'generated_by' => $this->admin->id
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.reports.enable-schedule', $report));

        $response->assertRedirect();
        
        $report->refresh();
        $this->assertTrue($report->scheduled);
    }

    public function test_update_report_schedule()
    {
        $report = Report::factory()->create([
            'scheduled' => true,
            'schedule_config' => ['frequency' => 'daily'],
            'generated_by' => $this->admin->id
        ]);

        $response = $this->actingAs($this->admin)
            ->put(route('admin.reports.update-schedule', $report), [
                'schedule' => 'monthly',
                'enabled' => true
            ]);

        $response->assertRedirect();
        
        $report->refresh();
        $this->assertEquals('monthly', $report->schedule_config['frequency']);
    }

    public function test_preview_report_returns_data()
    {
        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.reports.preview'), [
                'report_type' => 'user_activity',
                'start_date' => now()->subDays(30)->format('Y-m-d'),
                'end_date' => now()->format('Y-m-d')
            ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $response->assertJsonStructure(['success', 'data']);
    }

    public function test_report_validation_fails_without_required_fields()
    {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.reports.store'), [
                'name' => 'Test Report'
                // Missing report_type and format
            ]);

        $response->assertSessionHasErrors(['report_type', 'format']);
    }

    public function test_unauthorized_user_cannot_access_reports()
    {
        $user = User::factory()->create(['role' => 'employee']);

        $response = $this->actingAs($user)
            ->get(route('admin.reports.index'));

        $response->assertStatus(403);
    }

    public function test_guest_cannot_access_reports()
    {
        $response = $this->get(route('admin.reports.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_filter_reports_by_type()
    {
        Report::factory()->create([
            'report_type' => 'user_activity',
            'generated_by' => $this->admin->id
        ]);
        
        Report::factory()->create([
            'report_type' => 'compliance',
            'generated_by' => $this->admin->id
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.reports.index', ['type' => 'user_activity']));

        $response->assertStatus(200);
        $response->assertSee('user_activity');
    }

    public function test_filter_reports_by_status()
    {
        Report::factory()->create([
            'status' => 'completed',
            'generated_by' => $this->admin->id
        ]);
        
        Report::factory()->create([
            'status' => 'pending',
            'generated_by' => $this->admin->id
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.reports.index', ['status' => 'completed']));

        $response->assertStatus(200);
    }
}
