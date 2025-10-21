# Course Catalog Search Setup

This document explains how to set up and use the Laravel Scout search functionality with Meilisearch for the course catalog.

## Overview

The course catalog uses Laravel Scout with Meilisearch to provide fast, full-text search capabilities with filtering and highlighting.

## Installation

### 1. Install Meilisearch

**Using Docker:**
```bash
docker run -d -p 7700:7700 --name meilisearch getmeili/meilisearch:latest
```

**Using Homebrew (macOS):**
```bash
brew install meilisearch
brew services start meilisearch
```

**Using Windows:**
Download from https://www.meilisearch.com/docs/learn/getting_started/installation

### 2. Configure Environment

Add to your `.env` file:
```env
SCOUT_DRIVER=meilisearch
MEILISEARCH_HOST=http://127.0.0.1:7700
MEILISEARCH_KEY=
```

### 3. Index Existing Courses

Import all published courses to the search index:
```bash
php artisan scout:import "App\Models\Course"
```

## Features

### Full-Text Search
- Searches across course title, description, and short description
- Typo-tolerant search
- Relevance-based ranking

### Filtering
- Filter by category
- Filter by difficulty level
- Filter by tags
- Combine multiple filters

### Search Highlighting
- Search terms are highlighted in results
- Works for both title and description

### Performance
- Fast search results (< 50ms typical)
- Handles thousands of courses efficiently
- Automatic index updates when courses are created/updated

## Usage

### Automatic Indexing

Courses are automatically indexed when:
- A new course is created
- An existing course is updated
- A course is published (only published courses are searchable)

Courses are automatically removed from the index when:
- A course is unpublished
- A course is deleted

### Manual Indexing

**Import all courses:**
```bash
php artisan scout:import "App\Models\Course"
```

**Flush the index:**
```bash
php artisan scout:flush "App\Models\Course"
```

**Re-import after flushing:**
```bash
php artisan scout:flush "App\Models\Course"
php artisan scout:import "App\Models\Course"
```

### Search API

The catalog controller automatically uses Scout when a search term is provided:

```php
// With search term - uses Scout
GET /catalog?search=laravel

// Without search term - uses database queries
GET /catalog?category=1&difficulty=beginner
```

### Searchable Attributes

The following course attributes are indexed:
- `title` - Primary search field
- `description` - Full description text
- `short_description` - Brief description
- `tags` - Course tags array

### Filterable Attributes

The following attributes can be used for filtering:
- `category_id` - Course category
- `difficulty_level` - beginner, intermediate, advanced
- `is_published` - Only published courses are searchable
- `tags` - Array of tags

### Sortable Attributes

Results can be sorted by:
- `created_at` - Newest first (default)
- `title` - Alphabetical
- `difficulty_level` - By difficulty
- `estimated_duration` - By duration

## Configuration

### Index Settings

The search index is configured in `config/scout.php`:

```php
'meilisearch' => [
    'index-settings' => [
        'courses' => [
            'filterableAttributes' => ['category_id', 'difficulty_level', 'is_published', 'tags'],
            'sortableAttributes' => ['created_at', 'title', 'difficulty_level', 'estimated_duration'],
            'searchableAttributes' => ['title', 'description', 'short_description', 'tags'],
            'displayedAttributes' => ['*'],
            'rankingRules' => [
                'words',
                'typo',
                'proximity',
                'attribute',
                'sort',
                'exactness',
            ],
        ],
    ],
],
```

### Course Model Configuration

The Course model implements the `Searchable` trait and defines:

```php
// What data to index
public function toSearchableArray(): array

// When to index (only published courses)
public function shouldBeSearchable(): bool

// Index name
public function searchableAs(): string
```

## Troubleshooting

### Search not working

1. Check Meilisearch is running:
```bash
curl http://127.0.0.1:7700/health
```

2. Verify courses are indexed:
```bash
php artisan scout:import "App\Models\Course"
```

3. Check the index exists:
Visit http://127.0.0.1:7700 in your browser

### Courses not appearing in search

1. Ensure the course is published (`is_published = true`)
2. Re-import the course:
```bash
php artisan scout:import "App\Models\Course"
```

### Search results not updating

1. Clear and re-import:
```bash
php artisan scout:flush "App\Models\Course"
php artisan scout:import "App\Models\Course"
```

## Performance Tips

1. **Queue Indexing**: Enable queue-based indexing in production:
```env
SCOUT_QUEUE=true
```

2. **Batch Updates**: When updating many courses, use `searchable()` method:
```php
Course::where('category_id', 1)->searchable();
```

3. **Disable Indexing**: Temporarily disable during bulk operations:
```php
Course::withoutSyncingToSearch(function () {
    // Bulk operations here
});
```

## Advanced Usage

### Custom Search Queries

```php
// Search with filters
$results = Course::search('laravel')
    ->where('difficulty_level', 'beginner')
    ->where('category_id', 1)
    ->paginate(12);

// Search with custom options
$results = Course::search('laravel', function ($meilisearch, $query, $options) {
    $options['attributesToHighlight'] = ['title', 'description'];
    return $meilisearch->search($query, $options);
})->get();
```

### Monitoring

Access Meilisearch dashboard at: http://127.0.0.1:7700

View:
- Index statistics
- Search analytics
- Index settings
- Document count

## Resources

- [Laravel Scout Documentation](https://laravel.com/docs/scout)
- [Meilisearch Documentation](https://www.meilisearch.com/docs)
- [Meilisearch PHP Client](https://github.com/meilisearch/meilisearch-php)
