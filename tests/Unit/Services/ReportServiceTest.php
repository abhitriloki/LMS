<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\ReportService;
use App\Services\AnalyticsService;
use App\Models\User;
use App\Models\Report;
use App\Models\Course;
use App\Models\Department;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

class ReportServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ReportService $service;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $analyticsService = $this->createMock(AnalyticsService::class);
        $this->service = new ReportService($analyticsService);
        $this->user = User::factory()->create(['role' => 'super_admin']);
        
        Storage::fake('local');
    }

    public function test_generate_report_creates_report_record()
    {
        $report = $this->service->generateReport('user_activity', [
            'name' => 'Test Report',
            'format' => 'pdf',
            'start_date' => now()->subDays(30)->format('Y-m-d'),
            'end_date' => now()->format('Y-m-d')
        ], $this->user);

        $this->assertInstanceOf(Report::class, $report);
        $this->assertEquals('Test Report', $report->name);
        $this->assertEquals('user_activity', $report->report_type);
        $this->assertEquals($this->user->id, $report->generated_by);
    }

    public function test_schedule_report_creates_scheduled_report()
    {
        $report = $this->service->scheduleReport('compliance', [
            'name' => 'Weekly Compliance',
            'format' => 'excel'
        ], 'weekly', $this->user);

        $this->assertInstanceOf(Report::class, $report);
        $this->assertTrue($report->scheduled);
        $this->assertEquals('weekly', $report->schedule_config['frequency']);
        $this->assertNotNull($report->next_run_at);
    }

    public function test_parse_schedule_returns_correct_config()
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('parseSchedule');
        $method->setAccessible(true);

        $config = $method->invoke($this->service, 'daily');

        $this->assertEquals('daily', $config['frequency']);
        $this->assertTrue($config['enabled']);
    }

    public function test_calculate_next_run_time_for_daily()
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('calculateNextRunTime');
        $method->setAccessible(true);

        $nextRun = $method->invoke($this->service, ['frequency' => 'daily']);

        $this->assertEquals(now()->addDay()->format('Y-m-d'), $nextRun->format('Y-m-d'));
    }

    public function test_calculate_next_run_time_for_weekly()
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('calculateNextRunTime');
        $method->setAccessible(true);

        $nextRun = $method->invoke($this->service, ['frequency' => 'weekly']);

        $this->assertEquals(now()->addWeek()->format('Y-m-d'), $nextRun->format('Y-m-d'));
    }

    public function test_calculate_next_run_time_for_monthly()
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('calculateNextRunTime');
        $method->setAccessible(true);

        $nextRun = $method->invoke($this->service, ['frequency' => 'monthly']);

        $this->assertEquals(now()->addMonth()->format('Y-m-d'), $nextRun->format('Y-m-d'));
    }

    public function test_get_default_report_name_returns_correct_name()
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('getDefaultReportName');
        $method->setAccessible(true);

        $name = $method->invoke($this->service, 'user_activity');
        $this->assertEquals('User Activity Report', $name);

        $name = $method->invoke($this->service, 'compliance');
        $this->assertEquals('Compliance Report', $name);
    }

    public function test_delete_report_removes_file_and_record()
    {
        $report = Report::factory()->create([
            'file_path' => 'reports/test.pdf',
            'status' => 'completed'
        ]);

        Storage::put($report->file_path, 'test content');

        $result = $this->service->deleteReport($report);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('reports', ['id' => $report->id]);
        Storage::assertMissing($report->file_path);
    }

    public function test_get_download_url_returns_url_for_completed_report()
    {
        $report = Report::factory()->create([
            'status' => 'completed',
            'file_path' => 'reports/test.pdf'
        ]);

        $url = $this->service->getDownloadUrl($report);

        $this->assertNotNull($url);
        $this->assertStringContainsString('reports', $url);
    }

    public function test_get_download_url_returns_null_for_pending_report()
    {
        $report = Report::factory()->create([
            'status' => 'pending',
            'file_path' => null
        ]);

        $url = $this->service->getDownloadUrl($report);

        $this->assertNull($url);
    }
}
