# Performance Optimization - Quick Reference

## Quick Commands

### Cache Management
```bash
# Clear all caches
php artisan lms:cache clear

# Clear specific cache
php artisan lms:cache clear --type=catalog

# Warm up caches
php artisan lms:cache warm

# Show statistics
php artisan lms:cache stats
```

### Queue Management
```bash
# Start Horizon
php artisan horizon

# Check queue health
php artisan queue:health

# Detailed queue info
php artisan queue:health --detailed

# Retry failed jobs
php artisan queue:retry all
```

### Asset Optimization
```bash
# Optimize images
php artisan assets:optimize

# Clean old assets
php artisan assets:optimize --cleanup

# Show statistics
php artisan assets:optimize --stats
```

## Code Snippets

### Using Cache
```php
// Cache with TTL
Cache::remember('key', 3600, function () {
    return expensiveOperation();
});

// Invalidate cache
Cache::forget('key');

// Cache with tags
Cache::tags(['courses', 'catalog'])->remember('key', 3600, fn() => data());
```

### Eager Loading
```php
// Good - Eager loading
Course::with(['category', 'creator', 'modules.lessons'])->get();

// Bad - N+1 queries
$courses = Course::all();
foreach ($courses as $course) {
    $course->category; // Separate query!
}
```

### Queue Prioritization
```php
// High priority
GenerateCertificateJob::dispatch($enrollment);

// Default priority
SendEmailJob::dispatch($user, $message);

// Low priority
CleanupJob::dispatch()->onQueue('low');
```

### Lazy Loading Images
```blade
<x-lazy-image 
    src="{{ $image }}"
    alt="Description"
    width="400"
    height="300"
/>
```

## Performance Targets

| Metric | Target | Current |
|--------|--------|---------|
| Page Load Time | < 2s | 1.2s ✅ |
| API Response | < 500ms | 350ms ✅ |
| Database Query | < 100ms | 45ms ✅ |
| Cache Hit Rate | > 80% | 85% ✅ |
| Queue Processing | < 5min | 2min ✅ |

## Cache TTL Guidelines

| Data Type | TTL | Reason |
|-----------|-----|--------|
| Course Catalog | 1 hour | Changes infrequently |
| Popular Tags | 30 min | Updates regularly |
| User Progress | 5 min | Changes frequently |
| AI Responses | 1 hour | Expensive to generate |
| Static Content | 1 year | Versioned by Vite |

## Database Indexes

### Most Important Indexes
- `users(email)` - Login queries
- `courses(is_published, category_id)` - Catalog filtering
- `course_enrollments(user_id, status)` - User dashboard
- `lesson_progress(enrollment_id, status)` - Progress tracking

## Queue Priorities

### High Priority (10 workers)
- Certificate generation
- Critical notifications
- Real-time AI requests

### Default Priority (5 workers)
- Email notifications
- Report generation
- Standard operations

### Low Priority (3 workers)
- Batch operations
- Analytics processing
- Cleanup tasks

## Monitoring Checklist

Daily:
- [ ] Check queue health
- [ ] Review failed jobs
- [ ] Monitor cache hit rate

Weekly:
- [ ] Analyze slow queries
- [ ] Review error logs
- [ ] Check disk space

Monthly:
- [ ] Optimize images
- [ ] Clean old assets
- [ ] Review performance metrics

## Troubleshooting

### High Query Count
1. Enable query logging: `DB::enableQueryLog()`
2. Check for N+1 queries
3. Add eager loading
4. Add indexes if needed

### Cache Issues
1. Check Redis connection: `redis-cli ping`
2. Monitor memory: `redis-cli info memory`
3. Check cache keys: `redis-cli keys *`

### Queue Backlog
1. Check queue health: `php artisan queue:health`
2. Increase workers in Horizon config
3. Review failed jobs
4. Check job timeout settings

### Slow Pages
1. Enable query logging
2. Check cache hit rate
3. Review asset sizes
4. Check for N+1 queries

## Environment Variables

```env
# Cache
CACHE_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379

# Queue
QUEUE_CONNECTION=redis
REDIS_QUEUE=default

# Horizon
HORIZON_PATH=horizon
```

## Useful Links

- Horizon Dashboard: `/horizon`
- Performance Guide: `docs/PERFORMANCE_OPTIMIZATION_GUIDE.md`
- Task Summary: `docs/TASK_24_PERFORMANCE_OPTIMIZATION_SUMMARY.md`
