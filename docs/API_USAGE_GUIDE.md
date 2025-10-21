# API Usage Guide

## Overview

The Corporate LMS provides a comprehensive RESTful API for integrating with external systems, mobile applications, and third-party services.

## Table of Contents

1. [Authentication](#authentication)
2. [API Endpoints](#api-endpoints)
3. [Request/Response Format](#requestresponse-format)
4. [Error Handling](#error-handling)
5. [Rate Limiting](#rate-limiting)
6. [Pagination](#pagination)
7. [Filtering and Sorting](#filtering-and-sorting)
8. [Code Examples](#code-examples)

## Authentication

### Obtaining API Token

**Endpoint**: `POST /api/auth/login`

**Request**:
```json
{
  "email": "user@example.com",
  "password": "password123"
}
```

**Response**:
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "token": "1|abc123def456...",
    "token_type": "Bearer",
    "expires_in": 3600,
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "user@example.com",
      "role": "employee"
    }
  }
}
```

### Using API Token

Include the token in the Authorization header:

```
Authorization: Bearer 1|abc123def456...
```

### Revoking Token

**Endpoint**: `POST /api/auth/logout`

**Headers**:
```
Authorization: Bearer {token}
```

**Response**:
```json
{
  "success": true,
  "message": "Logged out successfully"
}
```


## API Endpoints

### Courses

#### List Courses

**Endpoint**: `GET /api/courses`

**Query Parameters**:
- `search` (string): Search term
- `category` (integer): Category ID
- `difficulty` (string): beginner, intermediate, advanced
- `per_page` (integer): Results per page (default: 15)
- `page` (integer): Page number

**Example Request**:
```bash
curl -X GET "https://lms.example.com/api/courses?search=python&difficulty=beginner&per_page=10" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

**Response**:
```json
{
  "success": true,
  "message": "Courses retrieved successfully",
  "data": [
    {
      "id": 1,
      "title": "Introduction to Python",
      "slug": "introduction-to-python",
      "description": "Learn Python basics...",
      "category": {
        "id": 5,
        "name": "Programming"
      },
      "difficulty_level": "beginner",
      "estimated_duration": 20,
      "thumbnail": "https://cdn.example.com/thumbnails/python.jpg",
      "is_published": true,
      "created_at": "2024-01-15T10:00:00Z",
      "updated_at": "2024-01-15T10:00:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 10,
    "total": 45,
    "last_page": 5
  }
}
```

#### Get Course Details

**Endpoint**: `GET /api/courses/{id}`

**Response**:
```json
{
  "success": true,
  "message": "Course retrieved successfully",
  "data": {
    "id": 1,
    "title": "Introduction to Python",
    "slug": "introduction-to-python",
    "description": "Complete course description...",
    "category": {
      "id": 5,
      "name": "Programming"
    },
    "difficulty_level": "beginner",
    "estimated_duration": 20,
    "thumbnail": "https://cdn.example.com/thumbnails/python.jpg",
    "modules": [
      {
        "id": 1,
        "title": "Getting Started",
        "order_index": 1,
        "lessons": [
          {
            "id": 1,
            "title": "Introduction",
            "content_type": "video",
            "duration": 15,
            "order_index": 1
          }
        ]
      }
    ],
    "created_at": "2024-01-15T10:00:00Z",
    "updated_at": "2024-01-15T10:00:00Z"
  }
}
```

#### Create Course

**Endpoint**: `POST /api/courses`

**Request**:
```json
{
  "title": "Advanced JavaScript",
  "description": "Master advanced JavaScript concepts",
  "category_id": 5,
  "difficulty_level": "advanced",
  "estimated_duration": 30,
  "is_published": false
}
```

**Response**:
```json
{
  "success": true,
  "message": "Course created successfully",
  "data": {
    "id": 42,
    "title": "Advanced JavaScript",
    "slug": "advanced-javascript",
    "created_at": "2024-01-17T14:30:00Z"
  }
}
```

### Enrollments

#### Enroll in Course

**Endpoint**: `POST /api/courses/{courseId}/enroll`

**Response**:
```json
{
  "success": true,
  "message": "Enrolled successfully",
  "data": {
    "enrollment_id": 123,
    "course_id": 1,
    "user_id": 5,
    "status": "active",
    "enrolled_at": "2024-01-17T15:00:00Z"
  }
}
```

#### Get User Enrollments

**Endpoint**: `GET /api/enrollments`

**Query Parameters**:
- `status` (string): active, completed, dropped
- `per_page` (integer): Results per page

**Response**:
```json
{
  "success": true,
  "message": "Enrollments retrieved successfully",
  "data": [
    {
      "id": 123,
      "course": {
        "id": 1,
        "title": "Introduction to Python",
        "thumbnail": "https://cdn.example.com/thumbnails/python.jpg"
      },
      "status": "active",
      "progress_percentage": 45.5,
      "enrolled_at": "2024-01-10T10:00:00Z",
      "last_accessed_at": "2024-01-17T14:30:00Z"
    }
  ]
}
```

### Lessons

#### Get Lesson Content

**Endpoint**: `GET /api/lessons/{id}`

**Response**:
```json
{
  "success": true,
  "message": "Lesson retrieved successfully",
  "data": {
    "id": 1,
    "title": "Introduction to Variables",
    "content_type": "video",
    "content_url": "https://cdn.example.com/videos/lesson-1.mp4",
    "duration": 15,
    "description": "Learn about variables...",
    "is_completed": false,
    "bookmark_position": 0
  }
}
```

#### Update Lesson Progress

**Endpoint**: `POST /api/lessons/{id}/progress`

**Request**:
```json
{
  "time_spent": 300,
  "bookmark_position": 450,
  "completed": false
}
```

**Response**:
```json
{
  "success": true,
  "message": "Progress updated successfully",
  "data": {
    "lesson_id": 1,
    "progress_percentage": 75,
    "time_spent": 300,
    "completed": false
  }
}
```

### Assessments

#### Start Assessment

**Endpoint**: `POST /api/assessments/{id}/start`

**Response**:
```json
{
  "success": true,
  "message": "Assessment started successfully",
  "data": {
    "attempt_id": 456,
    "assessment_id": 10,
    "time_limit": 3600,
    "started_at": "2024-01-17T15:00:00Z",
    "expires_at": "2024-01-17T16:00:00Z",
    "questions": [
      {
        "id": 1,
        "question_text": "What is a variable?",
        "question_type": "multiple_choice",
        "points": 5,
        "options": [
          {
            "id": 1,
            "option_text": "A container for data"
          },
          {
            "id": 2,
            "option_text": "A function"
          }
        ]
      }
    ]
  }
}
```

#### Submit Assessment

**Endpoint**: `POST /api/assessments/attempts/{attemptId}/submit`

**Request**:
```json
{
  "responses": [
    {
      "question_id": 1,
      "answer": 1
    },
    {
      "question_id": 2,
      "answer": "Variables store data values"
    }
  ]
}
```

**Response**:
```json
{
  "success": true,
  "message": "Assessment submitted successfully",
  "data": {
    "attempt_id": 456,
    "score": 85.5,
    "passed": true,
    "submitted_at": "2024-01-17T15:45:00Z"
  }
}
```

### Certificates

#### Get User Certificates

**Endpoint**: `GET /api/certificates`

**Response**:
```json
{
  "success": true,
  "message": "Certificates retrieved successfully",
  "data": [
    {
      "id": 789,
      "certificate_number": "CERT-2024-001234",
      "course": {
        "id": 1,
        "title": "Introduction to Python"
      },
      "issued_at": "2024-01-15T10:00:00Z",
      "download_url": "https://lms.example.com/certificates/789/download",
      "verification_url": "https://lms.example.com/verify/CERT-2024-001234"
    }
  ]
}
```

#### Download Certificate

**Endpoint**: `GET /api/certificates/{id}/download`

**Response**: PDF file download


## Request/Response Format

### Request Headers

All API requests should include:

```
Content-Type: application/json
Accept: application/json
Authorization: Bearer {token}
```

### Success Response Format

```json
{
  "success": true,
  "message": "Operation successful",
  "data": {
    // Response data
  },
  "meta": {
    // Pagination or additional metadata (optional)
  }
}
```

### Error Response Format

```json
{
  "success": false,
  "message": "Error message",
  "errors": {
    "field_name": [
      "Validation error message"
    ]
  }
}
```

## Error Handling

### HTTP Status Codes

- `200 OK`: Successful request
- `201 Created`: Resource created successfully
- `204 No Content`: Successful request with no content
- `400 Bad Request`: Invalid request data
- `401 Unauthorized`: Authentication required
- `403 Forbidden`: Insufficient permissions
- `404 Not Found`: Resource not found
- `422 Unprocessable Entity`: Validation errors
- `429 Too Many Requests`: Rate limit exceeded
- `500 Internal Server Error`: Server error

### Error Examples

**Validation Error (422)**:
```json
{
  "success": false,
  "message": "The given data was invalid",
  "errors": {
    "email": [
      "The email field is required."
    ],
    "password": [
      "The password must be at least 8 characters."
    ]
  }
}
```

**Authentication Error (401)**:
```json
{
  "success": false,
  "message": "Unauthenticated"
}
```

**Authorization Error (403)**:
```json
{
  "success": false,
  "message": "You do not have permission to perform this action"
}
```

**Not Found Error (404)**:
```json
{
  "success": false,
  "message": "Course not found"
}
```

**Rate Limit Error (429)**:
```json
{
  "success": false,
  "message": "Too many requests. Please try again later.",
  "retry_after": 60
}
```

## Rate Limiting

### Default Limits

- **Authenticated requests**: 60 requests per minute
- **Unauthenticated requests**: 10 requests per minute
- **Specific endpoints**: May have custom limits

### Rate Limit Headers

Response includes rate limit information:

```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 45
X-RateLimit-Reset: 1705507200
```

### Handling Rate Limits

When rate limit is exceeded:

```json
{
  "success": false,
  "message": "Too many requests",
  "retry_after": 60
}
```

Wait for the specified seconds before retrying.

## Pagination

### Paginated Responses

```json
{
  "success": true,
  "data": [...],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 150,
    "last_page": 10,
    "from": 1,
    "to": 15
  },
  "links": {
    "first": "https://api.example.com/courses?page=1",
    "last": "https://api.example.com/courses?page=10",
    "prev": null,
    "next": "https://api.example.com/courses?page=2"
  }
}
```

### Pagination Parameters

- `page`: Page number (default: 1)
- `per_page`: Items per page (default: 15, max: 100)

**Example**:
```
GET /api/courses?page=2&per_page=20
```

## Filtering and Sorting

### Filtering

Use query parameters to filter results:

```
GET /api/courses?category=5&difficulty=beginner&is_published=true
```

### Sorting

Use `sort` parameter:

```
GET /api/courses?sort=title
GET /api/courses?sort=-created_at  # Descending order
```

### Multiple Filters

Combine multiple filters:

```
GET /api/courses?category=5&difficulty=beginner&sort=-created_at&per_page=20
```


## Code Examples

### JavaScript (Axios)

```javascript
// Initialize Axios with base configuration
const api = axios.create({
  baseURL: 'https://lms.example.com/api',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  }
});

// Login and get token
async function login(email, password) {
  try {
    const response = await api.post('/auth/login', {
      email,
      password
    });
    
    const token = response.data.data.token;
    
    // Set token for future requests
    api.defaults.headers.common['Authorization'] = `Bearer ${token}`;
    
    return response.data.data;
  } catch (error) {
    console.error('Login failed:', error.response.data);
    throw error;
  }
}

// Get courses
async function getCourses(filters = {}) {
  try {
    const response = await api.get('/courses', {
      params: filters
    });
    
    return response.data.data;
  } catch (error) {
    console.error('Failed to fetch courses:', error.response.data);
    throw error;
  }
}

// Enroll in course
async function enrollInCourse(courseId) {
  try {
    const response = await api.post(`/courses/${courseId}/enroll`);
    return response.data.data;
  } catch (error) {
    console.error('Enrollment failed:', error.response.data);
    throw error;
  }
}

// Usage
(async () => {
  await login('user@example.com', 'password123');
  
  const courses = await getCourses({
    category: 5,
    difficulty: 'beginner',
    per_page: 10
  });
  
  console.log('Courses:', courses);
  
  await enrollInCourse(courses[0].id);
})();
```

### PHP (Guzzle)

```php
<?php

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class LMSApiClient
{
    private $client;
    private $token;
    
    public function __construct($baseUrl)
    {
        $this->client = new Client([
            'base_uri' => $baseUrl,
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ]
        ]);
    }
    
    public function login($email, $password)
    {
        try {
            $response = $this->client->post('/api/auth/login', [
                'json' => [
                    'email' => $email,
                    'password' => $password,
                ]
            ]);
            
            $data = json_decode($response->getBody(), true);
            $this->token = $data['data']['token'];
            
            return $data['data'];
        } catch (RequestException $e) {
            throw new Exception('Login failed: ' . $e->getMessage());
        }
    }
    
    public function getCourses($filters = [])
    {
        try {
            $response = $this->client->get('/api/courses', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->token,
                ],
                'query' => $filters,
            ]);
            
            $data = json_decode($response->getBody(), true);
            return $data['data'];
        } catch (RequestException $e) {
            throw new Exception('Failed to fetch courses: ' . $e->getMessage());
        }
    }
    
    public function enrollInCourse($courseId)
    {
        try {
            $response = $this->client->post("/api/courses/{$courseId}/enroll", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->token,
                ],
            ]);
            
            $data = json_decode($response->getBody(), true);
            return $data['data'];
        } catch (RequestException $e) {
            throw new Exception('Enrollment failed: ' . $e->getMessage());
        }
    }
}

// Usage
$api = new LMSApiClient('https://lms.example.com');
$api->login('user@example.com', 'password123');

$courses = $api->getCourses([
    'category' => 5,
    'difficulty' => 'beginner',
    'per_page' => 10,
]);

print_r($courses);

$enrollment = $api->enrollInCourse($courses[0]['id']);
print_r($enrollment);
```

### Python (Requests)

```python
import requests

class LMSApiClient:
    def __init__(self, base_url):
        self.base_url = base_url
        self.token = None
        self.session = requests.Session()
        self.session.headers.update({
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        })
    
    def login(self, email, password):
        """Login and store token"""
        response = self.session.post(
            f'{self.base_url}/api/auth/login',
            json={'email': email, 'password': password}
        )
        response.raise_for_status()
        
        data = response.json()
        self.token = data['data']['token']
        self.session.headers.update({
            'Authorization': f'Bearer {self.token}'
        })
        
        return data['data']
    
    def get_courses(self, **filters):
        """Get list of courses with optional filters"""
        response = self.session.get(
            f'{self.base_url}/api/courses',
            params=filters
        )
        response.raise_for_status()
        
        return response.json()['data']
    
    def enroll_in_course(self, course_id):
        """Enroll in a course"""
        response = self.session.post(
            f'{self.base_url}/api/courses/{course_id}/enroll'
        )
        response.raise_for_status()
        
        return response.json()['data']
    
    def get_enrollments(self, status=None):
        """Get user enrollments"""
        params = {'status': status} if status else {}
        response = self.session.get(
            f'{self.base_url}/api/enrollments',
            params=params
        )
        response.raise_for_status()
        
        return response.json()['data']

# Usage
api = LMSApiClient('https://lms.example.com')

# Login
user = api.login('user@example.com', 'password123')
print(f"Logged in as: {user['name']}")

# Get courses
courses = api.get_courses(
    category=5,
    difficulty='beginner',
    per_page=10
)
print(f"Found {len(courses)} courses")

# Enroll in first course
if courses:
    enrollment = api.enroll_in_course(courses[0]['id'])
    print(f"Enrolled in: {courses[0]['title']}")

# Get enrollments
enrollments = api.get_enrollments(status='active')
print(f"Active enrollments: {len(enrollments)}")
```

### cURL Examples

```bash
# Login
curl -X POST https://lms.example.com/api/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "email": "user@example.com",
    "password": "password123"
  }'

# Get courses (with token)
curl -X GET "https://lms.example.com/api/courses?category=5&difficulty=beginner" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"

# Enroll in course
curl -X POST https://lms.example.com/api/courses/1/enroll \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json"

# Get enrollments
curl -X GET https://lms.example.com/api/enrollments \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"

# Update lesson progress
curl -X POST https://lms.example.com/api/lessons/1/progress \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "time_spent": 300,
    "bookmark_position": 450,
    "completed": false
  }'
```

## Webhooks

### Available Webhooks

Configure webhooks to receive real-time notifications:

- `course.published`: Course is published
- `enrollment.created`: User enrolls in course
- `enrollment.completed`: User completes course
- `assessment.submitted`: Assessment is submitted
- `certificate.issued`: Certificate is generated

### Webhook Payload

```json
{
  "event": "enrollment.completed",
  "timestamp": "2024-01-17T15:00:00Z",
  "data": {
    "enrollment_id": 123,
    "user_id": 5,
    "course_id": 1,
    "completed_at": "2024-01-17T15:00:00Z"
  }
}
```

### Webhook Security

Verify webhook signatures:

```python
import hmac
import hashlib

def verify_webhook(payload, signature, secret):
    expected = hmac.new(
        secret.encode(),
        payload.encode(),
        hashlib.sha256
    ).hexdigest()
    
    return hmac.compare_digest(expected, signature)
```

## Best Practices

1. **Store tokens securely**: Never expose API tokens in client-side code
2. **Handle rate limits**: Implement exponential backoff for retries
3. **Use HTTPS**: Always use secure connections
4. **Validate responses**: Check response status and handle errors
5. **Cache when possible**: Cache frequently accessed data
6. **Use pagination**: Don't fetch all data at once
7. **Monitor API usage**: Track your API consumption
8. **Keep tokens fresh**: Refresh tokens before expiration

## Support

For API support:

- **API Documentation**: https://lms.example.com/api/documentation
- **Email**: api-support@yourcompany.com
- **Status Page**: https://status.yourcompany.com

---

**API Version**: 1.0  
**Last Updated**: 2024-01-17
