# AI-Powered Corporate LMS

A comprehensive enterprise-grade Learning Management System built with Laravel 11, featuring advanced AI integration for personalized learning experiences.

## Features

- **User Management**: Role-based access control (Super Admin, HR Admin, Instructor, Employee)
- **Course Management**: Multi-format content delivery (video, PDF, SCORM, presentations)
- **AI-Powered Features**:
  - Personalized course recommendations
  - Automated question generation
  - Adaptive learning paths
  - Content analysis and optimization
  - Auto-grading for essays
  - AI chatbot assistant
- **Assessment Engine**: Multiple question types with automated grading
- **Certificate System**: Verifiable digital certificates with QR codes
- **Analytics & Reporting**: Comprehensive learning analytics and custom reports
- **Modern UI**: Responsive design with dark mode support

## Technology Stack

### Backend
- Laravel 11 (PHP 8.2+)
- MySQL 8.0+
- Redis (caching and queues)
- Laravel Sanctum (API authentication)
- Laravel Horizon (queue monitoring)
- Laravel Scout with Meilisearch (full-text search)

### Frontend
- Blade templating engine
- Alpine.js (reactive components)
- Tailwind CSS (styling)
- Livewire (dynamic interactions)
- Chart.js (analytics visualization)

### AI Services
- OpenAI GPT-4 API
- OpenAI DALL-E 3
- OpenAI Whisper
- OpenAI TTS

## Installation

### Prerequisites

- PHP 8.2 or higher
- Composer
- Node.js and npm
- MySQL 8.0+
- Redis

### Setup Steps

1. **Install PHP dependencies**:
   ```bash
   composer install
   ```

2. **Install Node.js dependencies**:
   ```bash
   npm install
   ```

3. **Environment Configuration**:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Configure your `.env` file**:
   - Database credentials
   - Redis connection
   - OpenAI API key
   - Mail settings
   - AWS S3 credentials (optional)

5. **Run database migrations**:
   ```bash
   php artisan migrate
   ```

6. **Create storage symlink**:
   ```bash
   php artisan storage:link
   ```

7. **Build frontend assets**:
   ```bash
   npm run build
   ```

8. **Start the development server**:
   ```bash
   php artisan serve
   ```

9. **Start queue workers** (in a separate terminal):
   ```bash
   php artisan queue:work
   ```

10. **Start Horizon** (optional, for queue monitoring):
    ```bash
    php artisan horizon
    ```

## Configuration

### Database

Configure your database connection in `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=corporate_lms
DB_USERNAME=root
DB_PASSWORD=
```

### Redis

Configure Redis for caching and queues:

```env
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
```

### OpenAI

Add your OpenAI API credentials:

```env
OPENAI_API_KEY=your_api_key_here
OPENAI_ORGANIZATION=your_org_id
OPENAI_MODEL=gpt-4
```

### File Storage

For local storage (default):
```env
FILESYSTEM_DISK=local
```

For AWS S3:
```env
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=your_key
AWS_SECRET_ACCESS_KEY=your_secret
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=your_bucket
```

### Search

Configure Meilisearch for full-text search:

```env
SCOUT_DRIVER=meilisearch
MEILISEARCH_HOST=http://127.0.0.1:7700
MEILISEARCH_KEY=
```

## Project Structure

```
app/
├── Console/          # Artisan commands
├── Http/
│   ├── Controllers/  # Request handlers
│   ├── Middleware/   # Request middleware
│   └── Requests/     # Form requests
├── Models/           # Eloquent models
├── Providers/        # Service providers
├── Repositories/     # Data access layer
└── Services/         # Business logic

resources/
├── css/              # Stylesheets
├── js/               # JavaScript
└── views/            # Blade templates

routes/
├── web.php           # Web routes
├── api.php           # API routes
└── auth.php          # Authentication routes
```

## Default User Roles

- **Super Admin**: Full system access
- **HR Admin**: User and course management
- **Instructor**: Course creation and management
- **Employee**: Course enrollment and learning

## Development

### Running Tests

```bash
php artisan test
```

### Code Style

```bash
./vendor/bin/pint
```

### Building Assets

Development:
```bash
npm run dev
```

Production:
```bash
npm run build
```

## License

This project is proprietary software.

## Support

For support, please contact your system administrator.
