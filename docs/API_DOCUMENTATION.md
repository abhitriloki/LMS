# LMS API Documentation

## Overview

The LMS API provides programmatic access to the Learning Management System. All API endpoints are prefixed with `/api` and require authentication using Laravel Sanctum tokens (except for public endpoints).

## Base URL

```
https://your-domain.com/api
```

## Authentication

The API uses token-based authentication via Laravel Sanctum. Include the token in the `Authorization` header:

```
Authorization: Bearer {your-token}
```

### Obtaining a Token

**POST /api/login**

Request:
```json
{
  "email": "user@example.com",
  "password": "password123"
}
```

Response:
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "user": { ... },
    "token": "1|abc123..."
  }
}
```

## Rate Limiting

- General API endpoints: 60 requests per minute
- Authentication endpoints: 5 requests per minute
- Heavy operations: 10 requests per minute

Rate limit headers are included in responses:
- `X-RateLimit-Limit`: Maximum requests allowed
- `X-RateLimit-Remaining`: Remaining requests
- `Retry-After`: Seconds until rate limit resets (when exceeded)

## Response Format

All API responses follow a consistent JSON structure:

### Success Response
```json
{
  "success": true,
  "message": "Optional message",
  "data": { ... }
}
```

### Error Response
```json
{
  "success": false,
  "message": "Error description",
  "errors": { ... }
}
```


## Pagination

Paginated endpoints return data in the following format:

```json
{
  "success": true,
  "data": [...],
  "links": {
    "first": "...",
    "last": "...",
    "prev": null,
    "next": "..."
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 5,
    "per_page": 15,
    "to": 15,
    "total": 75
  }
}
```

Query parameters:
- `page`: Page number (default: 1)
- `per_page`: Items per page (default: 15, max: 100)

## Filtering and Sorting

Most list endpoints support filtering and sorting:

Query parameters:
- `sort_by`: Field to sort by (e.g., `created_at`, `title`)
- `sort_order`: Sort direction (`asc` or `desc`)
- Additional filters vary by endpoint (see specific endpoint documentation)

Example:
```
GET /api/courses?sort_by=title&sort_order=asc&difficulty_level=beginner
```

## Error Codes

| Code | Description |
|------|-------------|
| 200 | Success |
| 201 | Created |
| 400 | Bad Request |
| 401 | Unauthorized |
| 403 | Forbidden |
| 404 | Not Found |
| 422 | Validation Error |
| 429 | Too Many Requests |
| 500 | Server Error |
| 503 | Service Unavailable |


## Endpoints

### Authentication

#### Register User
**POST /api/register**

Create a new user account.

Request:
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123",
  "password_confirmation": "password123",
  "role": "employee",
  "department_id": 1,
  "position": "Software Engineer"
}
```

Response (201):
```json
{
  "success": true,
  "message": "User registered successfully",
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com",
      "role": "employee",
      ...
    },
    "token": "1|abc123..."
  }
}
```

#### Login
**POST /api/login**

Authenticate and receive access token.

Rate limit: 5 requests per minute

Request:
```json
{
  "email": "john@example.com",
  "password": "password123"
}
```

Response (200):
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "user": { ... },
    "token": "1|abc123..."
  }
}
```

#### Get Current User
**GET /api/me**

Get authenticated user information.

Headers:
```
Authorization: Bearer {token}
```

Response (200):
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    ...
  }
}
```

#### Logout
**POST /api/logout**

Revoke current access token.

Headers:
```
Authorization: Bearer {token}
```

Response (200):
```json
{
  "success": true,
  "message": "Logged out successfully"
}
```

#### Logout All Devices
**POST /api/logout-all**

Revoke all access tokens for the user.

Headers:
```
Authorization: Bearer {token}
```

Response (200):
```json
{
  "success": true,
  "message": "Logged out from all devices successfully"
}
```


### Courses

#### List Courses
**GET /api/courses**

Get a paginated list of published courses.

Query Parameters:
- `page`: Page number
- `per_page`: Items per page (max: 100)
- `search`: Search in title and description
- `category_id`: Filter by category
- `difficulty_level`: Filter by difficulty (beginner, intermediate, advanced)
- `is_featured`: Filter featured courses (true/false)
- `is_mandatory`: Filter mandatory courses (true/false)
- `tags`: Filter by tags (comma-separated)
- `sort_by`: Sort field (default: created_at)
- `sort_order`: Sort direction (asc/desc, default: desc)

Example:
```
GET /api/courses?search=javascript&difficulty_level=beginner&per_page=20
```

Response (200):
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "title": "Introduction to JavaScript",
      "slug": "intro-to-javascript",
      "description": "...",
      "difficulty_level": "beginner",
      "estimated_duration": 120,
      "thumbnail": "https://...",
      "category": { ... },
      "creator": { ... },
      ...
    }
  ],
  "links": { ... },
  "meta": { ... }
}
```

#### Get Course Details
**GET /api/courses/{id}**

Get detailed information about a specific course.

Response (200):
```json
{
  "success": true,
  "data": {
    "id": 1,
    "title": "Introduction to JavaScript",
    "modules": [
      {
        "id": 1,
        "title": "Getting Started",
        "lessons": [ ... ]
      }
    ],
    ...
  }
}
```

#### Get Course Modules
**GET /api/courses/{id}/modules**

Get all modules and lessons for a course.

Response (200):
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "title": "Module 1",
      "order_index": 1,
      "lessons": [
        {
          "id": 1,
          "title": "Lesson 1",
          "content_type": "video",
          ...
        }
      ]
    }
  ]
}
```


### Enrollments

#### List User Enrollments
**GET /api/enrollments**

Get authenticated user's course enrollments.

Query Parameters:
- `page`: Page number
- `per_page`: Items per page
- `status`: Filter by status (active, completed, dropped)
- `sort_by`: Sort field
- `sort_order`: Sort direction

Response (200):
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "course_id": 1,
      "user_id": 1,
      "status": "active",
      "progress_percentage": 45.5,
      "course": { ... },
      ...
    }
  ],
  "links": { ... },
  "meta": { ... }
}
```

#### Enroll in Course
**POST /api/courses/{id}/enroll**

Enroll the authenticated user in a course.

Response (201):
```json
{
  "success": true,
  "message": "Successfully enrolled in course",
  "data": {
    "id": 1,
    "course_id": 1,
    "user_id": 1,
    "status": "active",
    ...
  }
}
```

Error Response (400):
```json
{
  "success": false,
  "message": "Prerequisites not met"
}
```

#### Get Enrollment Details
**GET /api/enrollments/{id}**

Get details of a specific enrollment.

Response (200):
```json
{
  "success": true,
  "data": {
    "id": 1,
    "course": { ... },
    "user": { ... },
    "progress_percentage": 45.5,
    "certificate": { ... },
    ...
  }
}
```

#### Unenroll from Course
**DELETE /api/enrollments/{id}**

Unenroll from a course.

Response (200):
```json
{
  "success": true,
  "message": "Successfully unenrolled from course"
}
```


### Lessons

#### Get Lesson Details
**GET /api/lessons/{id}**

Get details of a specific lesson.

Response (200):
```json
{
  "success": true,
  "data": {
    "id": 1,
    "title": "Introduction",
    "content_type": "video",
    "content_path": "https://...",
    "duration": 600,
    ...
  }
}
```

#### Track Lesson Progress
**POST /api/lessons/{id}/progress**

Track user progress on a lesson.

Request:
```json
{
  "time_spent": 300,
  "bookmark_position": 150,
  "completed": false
}
```

Response (200):
```json
{
  "success": true,
  "message": "Progress tracked successfully"
}
```

#### Mark Lesson Complete
**POST /api/lessons/{id}/complete**

Mark a lesson as completed.

Response (200):
```json
{
  "success": true,
  "message": "Lesson marked as complete"
}
```

### Assessments

#### List Course Assessments
**GET /api/courses/{id}/assessments**

Get assessments for a specific course.

Query Parameters:
- `page`: Page number
- `per_page`: Items per page

Response (200):
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "title": "Final Exam",
      "passing_score": 70,
      "time_limit": 3600,
      "questions_count": 20,
      ...
    }
  ],
  "links": { ... },
  "meta": { ... }
}
```

#### Get Assessment Details
**GET /api/assessments/{id}**

Get detailed information about an assessment including questions.

Response (200):
```json
{
  "success": true,
  "data": {
    "id": 1,
    "title": "Final Exam",
    "instructions": "...",
    "questions": [
      {
        "id": 1,
        "question_text": "What is...?",
        "question_type": "multiple_choice",
        "options": [ ... ]
      }
    ],
    ...
  }
}
```

#### Get User's Assessment Attempts
**GET /api/assessments/{id}/attempts**

Get authenticated user's attempts for an assessment.

Response (200):
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "score": 85.5,
      "status": "completed",
      "started_at": "2024-01-15T10:00:00Z",
      "completed_at": "2024-01-15T11:00:00Z",
      ...
    }
  ]
}
```


### Certificates

#### List User Certificates
**GET /api/certificates**

Get authenticated user's certificates.

Query Parameters:
- `page`: Page number
- `per_page`: Items per page

Response (200):
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "certificate_number": "CERT-2024-001",
      "course": { ... },
      "issued_at": "2024-01-15T10:00:00Z",
      "expires_at": null,
      "pdf_path": "https://...",
      ...
    }
  ],
  "links": { ... },
  "meta": { ... }
}
```

#### Get Certificate Details
**GET /api/certificates/{id}**

Get details of a specific certificate.

Response (200):
```json
{
  "success": true,
  "data": {
    "id": 1,
    "certificate_number": "CERT-2024-001",
    "user": { ... },
    "course": { ... },
    "issued_at": "2024-01-15T10:00:00Z",
    "qr_code": "data:image/png;base64,...",
    ...
  }
}
```

#### Verify Certificate
**POST /api/certificates/verify**

Verify a certificate by certificate number (public endpoint).

Request:
```json
{
  "certificate_number": "CERT-2024-001"
}
```

Response (200):
```json
{
  "success": true,
  "data": {
    "certificate": { ... },
    "is_valid": true
  }
}
```

Error Response (404):
```json
{
  "success": false,
  "message": "Certificate not found"
}
```

### Users

#### List Users (Admin Only)
**GET /api/users**

Get a paginated list of users.

Query Parameters:
- `page`: Page number
- `per_page`: Items per page
- `search`: Search in name, email, position
- `role`: Filter by role
- `department_id`: Filter by department
- `sort_by`: Sort field
- `sort_order`: Sort direction

Response (200):
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com",
      "role": "employee",
      "department": { ... },
      ...
    }
  ],
  "links": { ... },
  "meta": { ... }
}
```

#### Get User Details
**GET /api/users/{id}**

Get details of a specific user.

Response (200):
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "role": "employee",
    "department": { ... },
    ...
  }
}
```


#### Update Profile
**PUT /api/profile**

Update authenticated user's profile.

Request:
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "phone": "+1234567890",
  "bio": "Software engineer with 5 years experience",
  "position": "Senior Developer"
}
```

For avatar upload, use `multipart/form-data`:
```
Content-Type: multipart/form-data

name: John Doe
avatar: [file]
```

Response (200):
```json
{
  "success": true,
  "message": "Profile updated successfully",
  "data": {
    "id": 1,
    "name": "John Doe",
    "avatar": "https://...",
    ...
  }
}
```

#### Update Password
**PUT /api/profile/password**

Update authenticated user's password.

Request:
```json
{
  "current_password": "oldpassword123",
  "password": "newpassword123",
  "password_confirmation": "newpassword123"
}
```

Response (200):
```json
{
  "success": true,
  "message": "Password updated successfully"
}
```

Error Response (400):
```json
{
  "success": false,
  "message": "Current password is incorrect"
}
```

## Code Examples

### JavaScript (Fetch API)

```javascript
// Login
const login = async (email, password) => {
  const response = await fetch('https://your-domain.com/api/login', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json'
    },
    body: JSON.stringify({ email, password })
  });
  
  const data = await response.json();
  
  if (data.success) {
    localStorage.setItem('token', data.data.token);
    return data.data.user;
  }
  
  throw new Error(data.message);
};

// Get courses
const getCourses = async (page = 1) => {
  const token = localStorage.getItem('token');
  
  const response = await fetch(`https://your-domain.com/api/courses?page=${page}`, {
    headers: {
      'Authorization': `Bearer ${token}`,
      'Accept': 'application/json'
    }
  });
  
  return await response.json();
};
```

### PHP (cURL)

```php
// Login
$ch = curl_init('https://your-domain.com/api/login');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'email' => 'user@example.com',
    'password' => 'password123'
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);

$response = curl_exec($ch);
$data = json_decode($response, true);
$token = $data['data']['token'];

// Get courses
$ch = curl_init('https://your-domain.com/api/courses');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $token,
    'Accept: application/json'
]);

$response = curl_exec($ch);
$courses = json_decode($response, true);
```


### Python (Requests)

```python
import requests

# Login
response = requests.post('https://your-domain.com/api/login', json={
    'email': 'user@example.com',
    'password': 'password123'
})

data = response.json()
token = data['data']['token']

# Get courses
headers = {
    'Authorization': f'Bearer {token}',
    'Accept': 'application/json'
}

response = requests.get('https://your-domain.com/api/courses', headers=headers)
courses = response.json()
```

## Webhooks (Future Feature)

Webhook support for real-time notifications is planned for a future release. This will allow external systems to receive notifications for events such as:

- Course completion
- Certificate issuance
- Enrollment changes
- Assessment submissions

## Versioning

The API currently uses implicit versioning. Future versions will be explicitly versioned in the URL path (e.g., `/api/v2/...`).

## Support

For API support and questions:
- Email: api-support@your-domain.com
- Documentation: https://your-domain.com/docs/api
- Status Page: https://status.your-domain.com

## Changelog

### Version 1.0.0 (Current)
- Initial API release
- Authentication endpoints
- Course management
- Enrollment management
- Assessment access
- Certificate management
- User profile management

