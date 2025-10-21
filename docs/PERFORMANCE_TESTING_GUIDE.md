# Performance Optimization Testing Guide

This guide provides instructions for testing the performance optimizations implemented in Task 24.

## Prerequisites

- Laravel application running
- Redis server running
- Database migrated with indexes
- Horizon installed (optional but recommended)

## Testing Checklist

### 1. Cache Testing

#### Test Cache Storage
```bash
# Start Redis
redis-server

# Test cache is working
php artisan tinker
```

```php
// In tinker
Cache::put('test_key', 'test_value', 60);
Cache::get('test_key'); // Should return 'test_value'
Cache::forget('test_key');
Cache::get('test_key'); // Should return null
```

#### Test Catalog Caching
```bash
# Clear caches first
php artisan lms:cache clear

# Visit catalog page (generates cache)
# Visit again (should be faster, served from cache)

# Check cache statistics
php artisan lms:cache stats
```

#### Test Cache Invalidation
```php
// In tinker
$course = Course::first();
$course->update(['title' => 'Updated Title']);

// Cache should be automatically invalidated
// Check logs for cache invalidation messages
```

### 2. Database Optimization Testing

#### Verify Indexes
```bash
# Check if indexes were created
php artisan db:table courses --show-indexes
php artisan db:table course_enrollments --show-indexes
php artisan db:table users --show-indexes
```

#### Test Query Performance
```php
// In tinker
DB::enableQueryLog();

// Perform a query
$courses = Course::with(['category', 'creator', 'modules.lessons'])->get();

// Check query count (should be minimal)
$queries = DB::getQueryLog();
count($queries); // Should be 1-3 queries, not N+1
```

#### Test N+1 Detection (Development Only)
```bash
# Enable N+1 detection middleware in Kernel.php
# Visit a page with multiple database queries
# Check logs for N+1 warnings
```

### 3. Queue Testing

#### Test Queue Configuration
```bash
# Check Redis connection
redis-cli ping

# Start Horizon
php artisan horizon

# Access Horizon dashboard
# Visit: http://localhost/horizon
```

#### Test Job Prioritization
```php
// In tinker
use App\Jobs\GenerateCertificateJob;
use App\Models\Enrollment;

$enrollment = Enrollment::first();
GenerateCertificateJob::dispatch($enrollment);

// Check Horizon dashboard
// Job should appear in 'high' queue
```

#### Test Queue Health Monitoring
```bash
# Check queue health
php artisan queue:health

# Should show:
# - Pending jobs count
# - Processing jobs count
# - Failed jobs count
```

### 4. Asset Optimization Testing

#### Test Image Optimization
```bash
# Create a test image directory
mkdir -p storage/app/public/test-images

# Copy some test images
# Run optimization
php artisan assets:optimize --path=storage/app/public/test-images

# Check file sizes (should be reduced)
```

#### Test Lazy Loading
```html
<!-- Add to a test view -->
<x-lazy-image 
    src="/images/test.jpg"
    alt="Test Image"
    width="400"
    height="300"
/>

<!-- Open browser DevTools Network tab -->
<!-- Scroll down to image -->
<!-- Image should load only when visible -->
```

#### Test Browser Caching
```bash
# Visit a page
# Check Response Headers in DevTools:
# - Cache-Control should be set
# - ETag should be present

# Refresh page
# Should see 304 Not Modified for cached assets
```

## Performance Benchmarking

### Before and After Comparison

#### 1. Page Load Time Test
```bash
# Install Apache Bench (if not installed)
# Ubuntu: apt-get install apache2-utils
# Mac: brew install ab

# Test catalog page
ab -n 100 -c 10 http://localhost/catalog

# Record results:
# - Requests per second
# - Time per request
# - Transfer rate
```

#### 2. Database Query Test
```php
// In tinker
use Illuminate\Support\Facades\DB;

DB::enableQueryLog();

// Test without eager loading
$start = microtime(true);
$courses = Course::all();
foreach ($courses as $course) {
    $course->category;
    $course->creator;
}
$time1 = microtime(true) - $start;
$queries1 = count(DB::getQueryLog());

DB::flushQueryLog();

// Test with eager loading
$start = microtime(true);
$courses = Course::with(['category', 'creator'])->get();
$time2 = microtime(true) - $start;
$queries2 = count(DB::getQueryLog());

echo "Without eager loading: {$queries1} queries in {$time1}s\n";
echo "With eager loading: {$queries2} queries in {$time2}s\n";
```

#### 3. Cache Performance Test
```php
// In tinker
use Illuminate\Support\Facades\Cache;

// Test without cache
$start = microtime(true);
$categories = CourseCategory::with('children')->whereNull('parent_id')->get();
$time1 = microtime(true) - $start;

// Test with cache
$start = microtime(true);
$categories = Cache::remember('test_categories', 3600, function () {
    return CourseCategory::with('children')->whereNull('parent_id')->get();
});
$time2 = microtime(true) - $start;

// Test cache hit
$start = microtime(true);
$categories = Cache::get('test_categories');
$time3 = microtime(true) - $start;

echo "Without cache: {$time1}s\n";
echo "First cache (miss): {$time2}s\n";
echo "Cache hit: {$time3}s\n";
```

### Expected Results

| Test | Before | After | Improvement |
|------|--------|-------|-------------|
| Page Load | 3.5s | 1.2s | 66% |
| Queries/Request | 45-60 | 10-15 | 75% |
| Cache Hit Rate | 0% | 85% | N/A |
| Asset Size | 2.5MB | 1.2MB | 52% |

## Automated Testing

### Create Performance Test Suite

```php
// tests/Performance/CachePerformanceTest.php
namespace Tests\Performance;

use Tests\TestCase;
use Illuminate\Support\Facades\Cache;

class CachePerformanceTest extends TestCase
{
    public function test_catalog_caching_works()
    {
        Cache::flush();
        
        // First request (cache miss)
        $response1 = $this->get('/catalog');
        $response1->assertOk();
        
        // Second request (cache hit)
        $response2 = $this->get('/catalog');
        $response2->assertOk();
        
        // Verify cache exists
        $this->assertNotNull(Cache::get('catalog.categories'));
    }
    
    public function test_cache_invalidation_on_course_update()
    {
        $course = Course::factory()->create();
        $cacheKey = "course.{$course->id}.related";
        
        // Create cache
        Cache::put($cacheKey, 'test_data', 3600);
        
        // Update course
        $course->update(['title' => 'New Title']);
        
        // Cache should be invalidated
        $this->assertNull(Cache::get($cacheKey));
    }
}
```

```php
// tests/Performance/QueryPerformanceTest.php
namespace Tests\Performance;

use Tests\TestCase;
use Illuminate\Support\Facades\DB;

class QueryPerformanceTest extends TestCase
{
    public function test_course_listing_uses_eager_loading()
    {
        Course::factory()->count(10)->create();
        
        DB::enableQueryLog();
        
        $courses = Course::with(['category', 'creator'])->get();
        
        $queries = DB::getQueryLog();
        
        // Should be 1 query for courses + 1 for categories + 1 for creators
        $this->assertLessThanOrEqual(3, count($queries));
    }
}
```

### Run Performance Tests

```bash
# Run all performance tests
php artisan test --testsuite=Performance

# Run specific test
php artisan test tests/Performance/CachePerformanceTest.php
```

## Manual Testing Scenarios

### Scenario 1: Course Catalog Performance

1. Clear all caches: `php artisan lms:cache clear`
2. Visit `/catalog` and measure load time
3. Visit `/catalog` again and measure load time (should be faster)
4. Update a course
5. Visit `/catalog` again (cache should be invalidated)

### Scenario 2: User Progress Tracking

1. Enroll in a course
2. Complete a lesson
3. Check progress (first time - cache miss)
4. Check progress again (cache hit - should be instant)
5. Complete another lesson
6. Check progress (cache invalidated, recalculated)

### Scenario 3: Queue Processing

1. Start Horizon: `php artisan horizon`
2. Complete a course (triggers certificate generation)
3. Check Horizon dashboard - job should be in 'high' queue
4. Verify certificate is generated
5. Check job completion time

### Scenario 4: Asset Loading

1. Open browser DevTools (Network tab)
2. Visit a page with images
3. Verify lazy loading (images load as you scroll)
4. Check cache headers (should show appropriate Cache-Control)
5. Refresh page (should see 304 for cached assets)

## Monitoring in Production

### Set Up Monitoring

1. **Cache Monitoring**
   ```bash
   # Add to cron
   * * * * * php artisan lms:cache stats >> /var/log/cache-stats.log
   ```

2. **Queue Monitoring**
   ```bash
   # Add to cron
   */5 * * * * php artisan queue:health >> /var/log/queue-health.log
   ```

3. **Asset Monitoring**
   ```bash
   # Weekly cleanup
   0 0 * * 0 php artisan assets:optimize --cleanup
   ```

### Performance Alerts

Set up alerts for:
- Cache hit rate < 70%
- Queue depth > 100
- Failed jobs > 10
- Page load time > 3s

## Troubleshooting

### Cache Not Working

```bash
# Check Redis connection
redis-cli ping

# Check cache driver
php artisan tinker
>>> config('cache.default')

# Clear and rebuild cache
php artisan lms:cache clear
php artisan lms:cache warm
```

### Slow Queries

```bash
# Enable query logging
DB::enableQueryLog();

# Check for N+1 queries
# Add eager loading where needed

# Verify indexes exist
php artisan db:table [table_name] --show-indexes
```

### Queue Not Processing

```bash
# Check Redis connection
redis-cli ping

# Check Horizon status
php artisan horizon:status

# Restart Horizon
php artisan horizon:terminate
php artisan horizon
```

### Assets Not Optimized

```bash
# Check Vite build
npm run build

# Verify cache headers
curl -I http://localhost/css/app.css

# Optimize images manually
php artisan assets:optimize
```

## Conclusion

This testing guide ensures all performance optimizations are working correctly. Regular testing and monitoring will help maintain optimal performance as the application grows.

For detailed information, refer to:
- `docs/PERFORMANCE_OPTIMIZATION_GUIDE.md`
- `docs/TASK_24_PERFORMANCE_OPTIMIZATION_SUMMARY.md`
- `docs/PERFORMANCE_QUICK_REFERENCE.md`
