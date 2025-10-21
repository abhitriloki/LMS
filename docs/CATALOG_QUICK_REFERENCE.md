# Course Catalog - Quick Reference

## URLs

- **Catalog Home**: `/catalog`
- **Course Detail**: `/catalog/{course-slug}`

## Search & Filter Examples

### Basic Search
```
/catalog?search=laravel
```

### Filter by Category
```
/catalog?category=1
```

### Filter by Difficulty
```
/catalog?difficulty=beginner
```

### Filter by Tags
```
/catalog?tags[]=php&tags[]=web-development
```

### Combined Filters
```
/catalog?search=laravel&category=1&difficulty=beginner&tags[]=php
```

### Sorting
```
/catalog?sort=title&order=asc
/catalog?sort=popular
/catalog?sort=duration&order=desc
```

## Scout Commands

### Import all courses to search index
```bash
php artisan scout:import "App\Models\Course"
```

### Clear search index
```bash
php artisan scout:flush "App\Models\Course"
```

### Check Meilisearch status
```bash
curl http://127.0.0.1:7700/health
```

## Controller Methods

### CatalogController::index()
- Displays course catalog with filters
- Uses Scout search when search term present
- Falls back to database queries otherwise

### CatalogController::show()
- Displays course detail page
- Shows related courses
- Checks enrollment status

## Components

### course-card
```blade
<x-course-card :course="$course" />
```

### search-highlight
```blade
<x-search-highlight :text="$text" :search="$searchTerm" />
```

## Model Scopes

```php
Course::published()          // Only published courses
Course::featured()           // Only featured courses
Course::mandatory()          // Only mandatory courses
Course::difficulty('level')  // Filter by difficulty
Course::inCategory($id)      // Filter by category
Course::search('term')       // Full-text search
```

## Searchable Attributes

- `title` - Course title
- `description` - Full description
- `short_description` - Brief description
- `tags` - Array of tags

## Filterable Attributes

- `category_id` - Course category
- `difficulty_level` - beginner, intermediate, advanced
- `is_published` - Published status
- `tags` - Course tags

## Sortable Attributes

- `created_at` - Creation date
- `title` - Course title
- `difficulty_level` - Difficulty
- `estimated_duration` - Duration

## Common Tasks

### Add a new course to search
```php
$course = Course::create([...]);
// Automatically indexed if published
```

### Update course in search
```php
$course->update([...]);
// Automatically re-indexed
```

### Remove course from search
```php
$course->update(['is_published' => false]);
// Automatically removed from index
```

### Manually sync a course
```php
$course->searchable();
```

### Batch sync courses
```php
Course::where('category_id', 1)->searchable();
```

## Troubleshooting

### Search not working
1. Check Meilisearch is running: `curl http://127.0.0.1:7700/health`
2. Re-import courses: `php artisan scout:import "App\Models\Course"`

### Course not appearing in search
1. Verify course is published: `$course->is_published === true`
2. Manually index: `$course->searchable()`

### Search results outdated
1. Clear and re-import:
```bash
php artisan scout:flush "App\Models\Course"
php artisan scout:import "App\Models\Course"
```
