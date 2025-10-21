# Analytics and Reporting - Quick Reference

## Analytics Service

### Track Events
```php
use App\Services\AnalyticsService;

$service = app(AnalyticsService::class);

// Track custom event
$service->trackEvent('event_type', $user, ['key' => 'value']);

// Using model methods
AnalyticsEvent::trackCourseView($course, $user);
AnalyticsEvent::trackEnrollment($enrollment);
AnalyticsEvent::trackCourseCompletion($enrollment);
AnalyticsEvent::trackAssessmentAttempt($attempt);
```

### Get Analytics
```php
// User analytics
$analytics = $service->getUserAnalytics($user, $startDate, $endDate);

// Course analytics
$analytics = $service->getCourseAnalytics($course, $startDate, $endDate);

// Department analytics
$analytics = $service->getDepartmentAnalytics($department, $startDate, $endDate);

// Dashboard metrics
$metrics = $service->getDashboardMetrics($startDate, $endDate);
```

### Clear Cache
```php
// Clear all analytics cache
$service->clearCache();

// Clear specific cache
$service->clearCache('user', $userId);
$service->clearCache('course', $courseId);
```

## Report Service

### Generate Reports
```php
use App\Services\ReportService;

$service = app(ReportService::class);

// Generate one-time report
$report = $service->generateReport('user_activity', [
    'name' => 'Monthly User Activity',
    'start_date' => '2024-01-01',
    'end_date' => '2024-01-31',
    'department_id' => 1,
    'format' => 'pdf'
], $user);

// Schedule recurring report
$report = $service->scheduleReport('compliance', [
    'name' => 'Weekly Compliance Report',
    'format' => 'excel'
], 'weekly', $user);
```

### Report Types
- `user_activity` - User activity and engagement
- `course_completion` - Course completion statistics
- `department_performance` - Department-level metrics
- `compliance` - Mandatory course compliance
- `assessment_results` - Assessment scores and pass rates
- `enrollment_summary` - Enrollment statistics
- `certificate_issuance` - Certificate generation data
- `learning_hours` - Learning time tracking

### Export Formats
- `pdf` - PDF document
- `excel` - Excel spreadsheet (.xlsx)
- `csv` - CSV file

### Schedule Frequencies
- `daily` - Every day
- `weekly` - Every week
- `monthly` - Every month
- `quarterly` - Every 3 months

## Controller Routes

### Analytics Routes
```
GET    /admin/analytics                          - Dashboard
GET    /admin/analytics/dashboard-data           - Dashboard data (AJAX)
GET    /admin/analytics/users/{user}             - User analytics
GET    /admin/analytics/courses/{course}         - Course analytics
GET    /admin/analytics/departments/{department} - Department analytics
GET    /admin/analytics/enrollment-trends        - Enrollment trends
GET    /admin/analytics/completion-trends        - Completion trends
GET    /admin/analytics/activity-heatmap         - Activity heatmap
POST   /admin/analytics/track-event              - Track event
POST   /admin/analytics/clear-cache              - Clear cache
```

### Report Routes
```
GET    /admin/reports                    - Report list
GET    /admin/reports/create             - Report builder
POST   /admin/reports                    - Generate report
GET    /admin/reports/{report}           - Report details
GET    /admin/reports/{report}/download  - Download report
DELETE /admin/reports/{report}           - Delete report
POST   /admin/reports/schedule           - Schedule report
GET    /admin/reports/scheduled/list     - Scheduled reports
PUT    /admin/reports/{report}/schedule  - Update schedule
POST   /admin/reports/{report}/disable-schedule - Disable
POST   /admin/reports/{report}/enable-schedule  - Enable
POST   /admin/reports/preview            - Preview report
```

## Frontend Integration

### Load Analytics Data
```javascript
// Fetch dashboard data
fetch('/admin/analytics/dashboard-data?start_date=2024-01-01&end_date=2024-01-31')
    .then(response => response.json())
    .then(data => {
        console.log(data.data);
    });

// Fetch enrollment trends
fetch('/admin/analytics/enrollment-trends?start_date=2024-01-01&end_date=2024-01-31')
    .then(response => response.json())
    .then(data => {
        // data.data contains array of {date, count}
        renderChart(data.data);
    });
```

### Track Custom Event
```javascript
fetch('/admin/analytics/track-event', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
    },
    body: JSON.stringify({
        event_type: 'custom_action',
        properties: {
            action: 'button_click',
            page: 'dashboard'
        }
    })
});
```

### Preview Report
```javascript
const formData = new FormData(document.getElementById('reportForm'));

fetch('/admin/reports/preview', {
    method: 'POST',
    headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
    },
    body: formData
})
.then(response => response.json())
.then(result => {
    if (result.success) {
        displayPreview(result.data);
    }
});
```

## Chart.js Integration

### Enrollment Trends Chart
```javascript
new Chart(document.getElementById('enrollmentChart'), {
    type: 'line',
    data: {
        labels: dates,
        datasets: [{
            label: 'Enrollments',
            data: counts,
            borderColor: 'rgb(59, 130, 246)',
            backgroundColor: 'rgba(59, 130, 246, 0.1)',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false
    }
});
```

### Completion Trends Chart
```javascript
new Chart(document.getElementById('completionChart'), {
    type: 'bar',
    data: {
        labels: dates,
        datasets: [{
            label: 'Completions',
            data: counts,
            backgroundColor: 'rgba(16, 185, 129, 0.8)'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false
    }
});
```

## Common Filters

### Report Parameters
```php
[
    'name' => 'Report Name',
    'description' => 'Optional description',
    'start_date' => '2024-01-01',
    'end_date' => '2024-01-31',
    'course_id' => 1,              // Optional
    'department_id' => 1,          // Optional
    'category_id' => 1,            // Optional
    'assessment_id' => 1,          // Optional
    'role' => 'employee',          // Optional
    'format' => 'pdf'              // Required: pdf, excel, csv
]
```

## Model Scopes

### AnalyticsEvent Scopes
```php
// Filter by type
AnalyticsEvent::ofType('course_view')->get();

// Filter by category
AnalyticsEvent::inCategory('course')->get();

// Date range
AnalyticsEvent::dateRange($startDate, $endDate)->get();

// For specific user
AnalyticsEvent::forUser($userId)->get();

// Recent events (last 7 days)
AnalyticsEvent::recent()->get();
AnalyticsEvent::recent(30)->get(); // Last 30 days
```

### Report Scopes
```php
// Scheduled reports
Report::scheduled()->get();

// By type
Report::ofType('user_activity')->get();

// By status
Report::completed()->get();
Report::pending()->get();
Report::failed()->get();

// Due for generation
Report::dueForGeneration()->get();
```

## Blade Components

### Display Metrics
```blade
<div class="metric-card">
    <p class="metric-label">Total Users</p>
    <p class="metric-value">{{ number_format($metrics['total_users']) }}</p>
</div>
```

### Progress Bar
```blade
<div class="w-full bg-gray-200 rounded-full h-4">
    <div class="bg-green-600 h-4 rounded-full" 
         style="width: {{ $metrics['average_completion_rate'] }}%">
    </div>
</div>
```

### Status Badge
```blade
@if($report->status === 'completed')
    <span class="badge badge-success">Completed</span>
@elseif($report->status === 'pending')
    <span class="badge badge-warning">Pending</span>
@else
    <span class="badge badge-danger">Failed</span>
@endif
```

## Tips & Best Practices

1. **Cache Wisely**: Analytics data is cached for performance. Clear cache after major data changes.
2. **Date Ranges**: Use reasonable date ranges to avoid performance issues.
3. **Scheduled Reports**: Set up scheduled reports for regular monitoring.
4. **Export Format**: Use CSV for large datasets, PDF for presentation.
5. **Preview First**: Always preview reports before generating to verify data.
6. **Filter Data**: Use filters to narrow down report scope for better insights.
7. **Track Events**: Consistently track important user actions for accurate analytics.

## Performance Tips

1. Use caching for frequently accessed analytics
2. Limit date ranges for large datasets
3. Use pagination for report lists
4. Queue heavy report generation
5. Index database columns used in analytics queries
6. Clear old analytics events periodically

## Troubleshooting

### No Data in Analytics
- Check if events are being tracked
- Verify date range includes data
- Clear cache and refresh

### Report Generation Fails
- Check file permissions on storage directory
- Verify required packages are installed
- Check error logs for details

### Charts Not Loading
- Verify Chart.js CDN is accessible
- Check browser console for errors
- Ensure data format is correct
