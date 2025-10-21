# Analytics and Reporting System - Testing Guide

## Overview
This guide covers testing for the Analytics and Reporting System, including unit tests, feature tests, and manual testing procedures.

## Test Files

### Unit Tests
- `tests/Unit/Services/AnalyticsServiceTest.php` - Tests for AnalyticsService
- `tests/Unit/Services/ReportServiceTest.php` - Tests for ReportService

### Feature Tests
- `tests/Feature/AnalyticsTest.php` - Tests for analytics endpoints
- `tests/Feature/ReportTest.php` - Tests for report generation and management

### Factories
- `database/factories/AnalyticsEventFactory.php` - Factory for analytics events
- `database/factories/ReportFactory.php` - Factory for reports

## Running Tests

### Run All Analytics Tests
```bash
php artisan test --filter=Analytics
```

### Run All Report Tests
```bash
php artisan test --filter=Report
```

### Run Specific Test Class
```bash
php artisan test --filter=AnalyticsServiceTest
php artisan test --filter=ReportServiceTest
php artisan test --filter=AnalyticsTest
php artisan test --filter=ReportTest
```

### Run Specific Test Method
```bash
php artisan test --filter=test_track_event_creates_analytics_event
php artisan test --filter=test_generate_report_creates_report
```

### Run with Coverage
```bash
php artisan test --coverage --filter=Analytics
```

## Unit Test Coverage

### AnalyticsServiceTest

**Event Tracking:**
- ✓ Track event creates analytics event
- ✓ Determine category returns correct category

**User Analytics:**
- ✓ Get user analytics returns correct structure
- ✓ Get user enrollment stats
- ✓ Get user progress stats
- ✓ Get user assessment stats
- ✓ Get user activity stats
- ✓ Get user certificate stats
- ✓ Get user learning time

**Course Analytics:**
- ✓ Get course analytics returns correct structure
- ✓ Get course completion stats
- ✓ Get course enrollment stats
- ✓ Get course engagement stats
- ✓ Get course assessment stats

**Department Analytics:**
- ✓ Get department analytics returns correct structure
- ✓ Get department user stats
- ✓ Get department enrollment stats
- ✓ Get department completion stats
- ✓ Get department compliance stats

**Dashboard Metrics:**
- ✓ Get dashboard metrics returns correct structure

**Caching:**
- ✓ Analytics data is cached
- ✓ Clear cache removes analytics cache
- ✓ Clear all cache

### ReportServiceTest

**Report Generation:**
- ✓ Generate report creates report record
- ✓ Schedule report creates scheduled report

**Schedule Management:**
- ✓ Parse schedule returns correct config
- ✓ Calculate next run time for daily
- ✓ Calculate next run time for weekly
- ✓ Calculate next run time for monthly

**Report Management:**
- ✓ Get default report name returns correct name
- ✓ Delete report removes file and record
- ✓ Get download URL returns URL for completed report
- ✓ Get download URL returns null for pending report

## Feature Test Coverage

### AnalyticsTest

**Dashboard:**
- ✓ Analytics dashboard page loads
- ✓ Get dashboard data returns JSON

**Analytics Endpoints:**
- ✓ Get user analytics returns JSON
- ✓ Get course analytics returns JSON
- ✓ Get department analytics returns JSON

**Event Tracking:**
- ✓ Track event creates analytics event

**Trends:**
- ✓ Get enrollment trends returns data
- ✓ Get completion trends returns data
- ✓ Get activity heatmap returns data

**Cache Management:**
- ✓ Clear cache succeeds

**Authorization:**
- ✓ Unauthorized user cannot access analytics
- ✓ Guest cannot access analytics

### ReportTest

**Report Pages:**
- ✓ Reports index page loads
- ✓ Report create page loads
- ✓ View report details

**Report Generation:**
- ✓ Generate report creates report
- ✓ Generate report with filters
- ✓ Schedule report creates scheduled report

**Report Download:**
- ✓ Download completed report
- ✓ Cannot download pending report

**Report Management:**
- ✓ Delete report
- ✓ View scheduled reports
- ✓ Disable scheduled report
- ✓ Enable scheduled report
- ✓ Update report schedule

**Report Preview:**
- ✓ Preview report returns data

**Validation:**
- ✓ Report validation fails without required fields

**Filtering:**
- ✓ Filter reports by type
- ✓ Filter reports by status

**Authorization:**
- ✓ Unauthorized user cannot access reports
- ✓ Guest cannot access reports

## Manual Testing Procedures

### 1. Analytics Dashboard Testing

**Test Steps:**
1. Login as admin user
2. Navigate to `/admin/analytics`
3. Verify key metrics are displayed:
   - Total Users
   - Total Courses
   - Enrollments
   - Certificates
4. Verify completion rate progress bar
5. Verify learning hours display
6. Check enrollment trends chart loads
7. Check completion trends chart loads
8. Change date range and verify data updates
9. Click refresh button and verify data reloads

**Expected Results:**
- All metrics display correctly
- Charts render without errors
- Date filtering works
- Refresh updates data

### 2. Report Generation Testing

**Test Steps:**
1. Navigate to `/admin/reports/create`
2. Select report type: "User Activity Report"
3. Enter report name: "Test User Activity"
4. Select date range: Last 30 days
5. Select department filter (optional)
6. Select format: PDF
7. Click "Preview Report"
8. Verify preview displays correctly
9. Click "Generate Report"
10. Wait for generation to complete
11. Verify redirect to report details page
12. Click "Download Report"
13. Verify PDF downloads correctly

**Expected Results:**
- Preview shows sample data
- Report generates successfully
- PDF downloads and opens correctly
- Data matches preview

### 3. Scheduled Report Testing

**Test Steps:**
1. Navigate to `/admin/reports/create`
2. Select report type: "Compliance Report"
3. Enter report name: "Weekly Compliance"
4. Select format: Excel
5. Select schedule: Weekly
6. Submit form
7. Navigate to `/admin/reports/scheduled/list`
8. Verify report appears in list
9. Check next run time is set
10. Click "Disable" button
11. Verify status changes to "Disabled"
12. Click "Enable" button
13. Verify status changes to "Active"

**Expected Results:**
- Scheduled report created successfully
- Next run time calculated correctly
- Enable/disable toggles work
- Schedule updates properly

### 4. Analytics API Testing

**Test Endpoints:**

```bash
# Get dashboard data
curl -X GET "http://localhost/admin/analytics/dashboard-data" \
  -H "Authorization: Bearer {token}"

# Get user analytics
curl -X GET "http://localhost/admin/analytics/users/1" \
  -H "Authorization: Bearer {token}"

# Get course analytics
curl -X GET "http://localhost/admin/analytics/courses/1" \
  -H "Authorization: Bearer {token}"

# Track event
curl -X POST "http://localhost/admin/analytics/track-event" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{"event_type":"custom_event","properties":{"key":"value"}}'

# Get enrollment trends
curl -X GET "http://localhost/admin/analytics/enrollment-trends?start_date=2024-01-01&end_date=2024-01-31" \
  -H "Authorization: Bearer {token}"
```

**Expected Results:**
- All endpoints return 200 status
- JSON responses have correct structure
- Data is accurate and complete

### 5. Report Export Format Testing

**Test Each Format:**

**PDF:**
1. Generate report with PDF format
2. Download and open PDF
3. Verify formatting is correct
4. Check all data is visible
5. Verify header and footer

**Excel:**
1. Generate report with Excel format
2. Download and open in Excel
3. Verify data in spreadsheet
4. Check column headers
5. Verify formulas work (if any)

**CSV:**
1. Generate report with CSV format
2. Download and open in text editor
3. Verify comma-separated values
4. Check data integrity
5. Import into Excel to verify

### 6. Performance Testing

**Test Scenarios:**

**Large Dataset:**
1. Create 1000+ analytics events
2. Generate user activity report
3. Measure generation time
4. Verify report completes successfully

**Concurrent Requests:**
1. Open multiple browser tabs
2. Load analytics dashboard in each
3. Verify all load correctly
4. Check for race conditions

**Cache Testing:**
1. Load analytics dashboard
2. Note load time
3. Reload page
4. Verify faster load time (cached)
5. Clear cache
6. Reload and verify slower load time

### 7. Error Handling Testing

**Test Error Scenarios:**

**Invalid Report Type:**
```php
POST /admin/reports
{
    "report_type": "invalid_type",
    "format": "pdf"
}
```
Expected: Validation error

**Missing Required Fields:**
```php
POST /admin/reports
{
    "name": "Test Report"
    // Missing report_type and format
}
```
Expected: Validation errors for missing fields

**Invalid Date Range:**
```php
POST /admin/reports
{
    "report_type": "user_activity",
    "format": "pdf",
    "start_date": "2024-12-31",
    "end_date": "2024-01-01"
}
```
Expected: Validation error for invalid date range

## Test Data Setup

### Seed Test Data
```bash
php artisan db:seed --class=AnalyticsTestSeeder
```

### Create Test Users
```php
User::factory()->count(50)->create();
```

### Create Test Courses
```php
Course::factory()->count(20)->create(['is_published' => true]);
```

### Create Test Enrollments
```php
Enrollment::factory()->count(100)->create();
```

### Create Test Analytics Events
```php
AnalyticsEvent::factory()->count(500)->create();
```

## Troubleshooting Tests

### Common Issues

**1. Tests Fail Due to Missing Data:**
- Solution: Ensure factories are properly set up
- Run: `php artisan migrate:fresh --seed`

**2. Cache-Related Test Failures:**
- Solution: Clear cache before tests
- Run: `php artisan cache:clear`

**3. File Storage Issues:**
- Solution: Use Storage::fake() in tests
- Ensure storage directory is writable

**4. Database Connection Errors:**
- Solution: Check `.env.testing` configuration
- Verify test database exists

**5. Memory Limit Errors:**
- Solution: Increase PHP memory limit
- Add to `phpunit.xml`: `<env name="MEMORY_LIMIT" value="512M"/>`

## Continuous Integration

### GitHub Actions Example
```yaml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
      - name: Install Dependencies
        run: composer install
      - name: Run Tests
        run: php artisan test --filter=Analytics
```

## Test Coverage Goals

- **Unit Tests**: 80%+ coverage
- **Feature Tests**: 70%+ coverage
- **Integration Tests**: 60%+ coverage

## Best Practices

1. **Use Factories**: Always use factories for test data
2. **Mock External Services**: Mock AI services and external APIs
3. **Test Edge Cases**: Test boundary conditions and error scenarios
4. **Keep Tests Fast**: Use database transactions, avoid unnecessary setup
5. **Test One Thing**: Each test should verify one specific behavior
6. **Use Descriptive Names**: Test names should describe what they test
7. **Clean Up**: Ensure tests clean up after themselves
8. **Avoid Test Dependencies**: Tests should be independent

## Conclusion

Comprehensive testing ensures the Analytics and Reporting System works correctly and reliably. Follow this guide to maintain high test coverage and catch issues early in development.
