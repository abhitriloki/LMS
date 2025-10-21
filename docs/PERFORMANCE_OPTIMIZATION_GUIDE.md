# Performance Optimization Guide

This document outlines the performance optimization strategies implemented in the AI-Powered Corporate LMS.

## Table of Contents

1. [Caching Strategy](#caching-strategy)
2. [Database Optimization](#database-optimization)
3. [Queue Management](#queue-management)
4. [Asset Optimization](#asset-optimization)
5. [Monitoring and Maintenance](#monitoring-and-maintenance)

## Caching Strategy

### Overview

The application uses Redis for caching to improve response times and reduce database load.

### Implemented Caches

#### 1. Catalog Caching

**Course Categories** (1 hour TTL)
```php
Cache::remember('catalog.categories', 3600, function () {
    return CourseCategory::with('children')
        ->whereNull('parent_id')
        ->orderBy('order_index')
        ->get();
});
```

**Popular Tags** (30 minutes TTL)
```php
Cache::remember('catalog.popular_tags', 1800, function () {
    // Tag aggregation logic
});
```

**Related Courses** (1 hour TTL)
```php
Cache::remember("course.{$courseId}.related", 3600, function () {
    // Related courses query
});
```

#### 2. Progress Tracking Caching

**Enrollment Progress** (5 minutes TTL)
```php
Cache::remember("enrollment.{$enrollmentId}.progress", 300, function () {
    // Progress calculation
});
```

**User Progress** (10 minutes TTL)
```php
Cache::remember("user.{$userId}.progress", 600, function () {
    // User progress aggregation
});
```

#### 3. AI Response Caching

**Text Generation** (Configurable TTL)
```php
$options = ['cache' => true, 'cache_ttl' => 3600];
$aiService->generateText($prompt, $options);
```

**Text Analysis** (1 hour TTL)
```php
Cache::remember("ai.analysis.{$hash}", 3600, function () {
    // AI analysis
});
```

### Cache Invalidation

Automatic cache invalidation is handled through model observers:

- **CourseObserver**: Invalidates catalog and course-specific caches
- **EnrollmentObserver**: Invalidates user and enrollment progress caches

### Manual Cache Management

```bash
# Clear all caches
php artisan lms:cache clear

# Clear specific cache type
php artisan lms:cache clear --type=catalog

# Warm up caches
php artisan lms:cache warm

# Show cache statistics
php artisan lms:cache stats
```

## Database Optimization

### Indexes

Comprehensive indexes have been added to improve query performance:

#### Users Table
- `email`, `role`, `department_id`
- Composite: `(role, department_id)`

#### Courses Table
- `category_id`, `is_published`, `is_mandatory`, `is_featured`
- Composite: `(is_published, category_id)`, `(is_published, is_featured)`

#### Enrollments Table
- `user_id`, `course_id`, `status`
- Composite: `(user_id, status)`, `(course_id, status)`

#### Lesson Progress Table
- `enrollment_id`, `lesson_id`, `status`
- Composite: `(enrollment_id, status)`

### Query Optimization

#### Eager Loading

All repository methods use eager loading to prevent N+1 queries:

```php
// Good - Eager loading
Course::with(['category', 'creator', 'modules.lessons'])->get();

// Bad - N+1 queries
$courses = Course::all();
foreach ($courses as $course) {
    $course->category; // Separate query for each course
}
```

#### Query Monitoring

Use the `DetectNPlusOneQueries` middleware in development:

```php
// Automatically logs warnings for:
// - High query counts (> 50 queries)
// - Potential N+1 issues
// - Slow queries (> 100ms)
```

### Database Maintenance

```bash
# Run migrations with indexes
php artisan migrate

# Analyze query performance
php artisan db:show
php artisan db:table users --show-indexes
```

## Queue Management

### Queue Configuration

Three priority queues are configured:

1. **High Priority** (`high` queue)
   - Certificate generation
   - Critical notifications
   - Real-time AI requests

2. **Default Priority** (`default` queue)
   - Standard operations
   - Email notifications
   - Report generation

3. **Low Priority** (`low` queue)
   - Batch operations
   - Analytics processing
   - Cleanup tasks

### Laravel Horizon

Horizon provides queue monitoring and management:

```bash
# Start Horizon
php artisan horizon

# Terminate Horizon
php artisan horizon:terminate

# Pause queue processing
php artisan horizon:pause

# Continue queue processing
php artisan horizon:continue
```

Access Horizon dashboard at: `/horizon`

### Queue Monitoring

```bash
# Check queue health
php artisan queue:health

# Show detailed queue information
php artisan queue:health --detailed

# Monitor failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all
```

### Job Prioritization

Jobs automatically use appropriate queues:

```php
// High priority
GenerateCertificateJob::dispatch($enrollment);

// Default priority (default)
SendEmailJob::dispatch($user, $message);

// Low priority
CleanupOldDataJob::dispatch()->onQueue('low');
```

## Asset Optimization

### Build Optimization

Vite configuration includes:

- **Minification**: Terser for JavaScript
- **Code Splitting**: Vendor and chart libraries separated
- **CSS Optimization**: Code splitting enabled
- **Tree Shaking**: Unused code removed

### Image Optimization

```bash
# Optimize all images
php artisan assets:optimize

# Optimize specific path
php artisan assets:optimize --path=/path/to/images

# Clean up old cached assets
php artisan assets:optimize --cleanup

# Show asset statistics
php artisan assets:optimize --stats
```

### Lazy Loading

Use the lazy-image component for images:

```blade
<x-lazy-image 
    src="{{ $course->thumbnail }}"
    alt="{{ $course->title }}"
    width="400"
    height="300"
    class="rounded-lg"
/>
```

### Browser Caching

The `SetCacheHeaders` middleware automatically sets appropriate cache headers:

- **HTML**: 5 minutes
- **Images**: 1 year (immutable)
- **CSS/JS**: 1 year (immutable, versioned by Vite)
- **Fonts**: 1 year (immutable)

### CDN Integration

For production, configure a CDN:

```env
ASSET_URL=https://cdn.yourdomain.com
```

## Monitoring and Maintenance

### Performance Monitoring

#### Query Analysis

```php
// Enable query logging
$queryService->enableQueryLog();

// Perform operations...

// Analyze queries
$analysis = $queryService->analyzeQueries();

// Log analysis
$queryService->logAnalysis();
```

#### Cache Statistics

```bash
# View cache statistics
php artisan lms:cache stats
```

#### Queue Health

```bash
# Monitor queue health
php artisan queue:health --detailed
```

### Regular Maintenance Tasks

#### Daily Tasks

```bash
# Clean up old cached assets
php artisan assets:optimize --cleanup

# Process failed jobs
php artisan queue:retry all
```

#### Weekly Tasks

```bash
# Optimize images
php artisan assets:optimize

# Clear old cache entries
php artisan cache:prune-stale-tags
```

#### Monthly Tasks

```bash
# Analyze database performance
php artisan db:show

# Review slow query logs
# Check application logs for performance warnings
```

### Performance Benchmarks

Target performance metrics:

- **Page Load Time**: < 2 seconds
- **API Response Time**: < 500ms
- **Database Query Time**: < 100ms per query
- **Cache Hit Rate**: > 80%
- **Queue Processing**: < 5 minutes for high priority

### Troubleshooting

#### High Query Count

1. Check for N+1 queries in logs
2. Add eager loading to affected queries
3. Consider adding indexes

#### Slow Cache Performance

1. Check Redis connection
2. Monitor Redis memory usage
3. Consider cache key optimization

#### Queue Backlog

1. Check queue health: `php artisan queue:health`
2. Increase worker processes in Horizon config
3. Review failed jobs: `php artisan queue:failed`

#### High Memory Usage

1. Review Horizon memory limits
2. Check for memory leaks in jobs
3. Optimize image processing

## Best Practices

### Caching

1. Always set appropriate TTL values
2. Invalidate caches when data changes
3. Use cache tags for grouped invalidation
4. Monitor cache hit rates

### Database

1. Always use indexes for frequently queried columns
2. Use eager loading for relationships
3. Avoid SELECT * queries
4. Use database transactions for data integrity

### Queues

1. Use appropriate queue priorities
2. Implement proper error handling
3. Set reasonable timeout values
4. Monitor queue metrics

### Assets

1. Optimize images before upload
2. Use lazy loading for below-fold images
3. Leverage browser caching
4. Consider using a CDN

## Configuration Reference

### Cache Configuration

```env
CACHE_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

### Queue Configuration

```env
QUEUE_CONNECTION=redis
REDIS_QUEUE=default
```

### Horizon Configuration

See `config/horizon.php` for detailed configuration options.

## Additional Resources

- [Laravel Caching Documentation](https://laravel.com/docs/cache)
- [Laravel Queue Documentation](https://laravel.com/docs/queues)
- [Laravel Horizon Documentation](https://laravel.com/docs/horizon)
- [Vite Documentation](https://vitejs.dev/)
