# API Testing Guide

## Running Tests

### Run All API Tests
```bash
php artisan test --filter Api
```

### Run Specific Test Classes
```bash
# Authentication tests
php artisan test tests/Feature/Api/AuthenticationTest.php

# Course API tests
php artisan test tests/Feature/Api/CourseApiTest.php

# Enrollment API tests
php artisan test tests/Feature/Api/EnrollmentApiTest.php

# Certificate API tests
php artisan test tests/Feature/Api/CertificateApiTest.php

# Rate limiting tests
php artisan test tests/Feature/Api/RateLimitingTest.php
```

### Run with Coverage
```bash
php artisan test --filter Api --coverage
```

## Manual Testing

### 1. Setup Test Environment

```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Run migrations
php artisan migrate

# Seed database (optional)
php artisan db:seed
```

### 2. Start Development Server

```bash
php artisan serve
```

The API will be available at `http://localhost:8000/api`

### 3. Test Authentication

#### Register a New User
```bash
curl -X POST http://localhost:8000/api/register \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "Test User",
    "email": "test@example.com",
    "password": "password123",
    "password_confirmation": "password123",
    "role": "employee"
  }'
```

Expected Response:
```json
{
  "success": true,
  "message": "User registered successfully",
  "data": {
    "user": { ... },
    "token": "1|abc123..."
  }
}
```

#### Login
```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "password123"
  }'
```

Save the token from the response for subsequent requests.


### 4. Test Course Endpoints

#### List Courses
```bash
curl -X GET "http://localhost:8000/api/courses?page=1&per_page=10" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

#### Search Courses
```bash
curl -X GET "http://localhost:8000/api/courses?search=javascript&difficulty_level=beginner" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

#### Get Course Details
```bash
curl -X GET "http://localhost:8000/api/courses/1" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

### 5. Test Enrollment Endpoints

#### Enroll in Course
```bash
curl -X POST "http://localhost:8000/api/courses/1/enroll" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

#### List My Enrollments
```bash
curl -X GET "http://localhost:8000/api/enrollments?status=active" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

#### Unenroll from Course
```bash
curl -X DELETE "http://localhost:8000/api/enrollments/1" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

### 6. Test Lesson Progress

#### Track Lesson Progress
```bash
curl -X POST "http://localhost:8000/api/lessons/1/progress" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "time_spent": 300,
    "bookmark_position": 150,
    "completed": false
  }'
```

#### Mark Lesson Complete
```bash
curl -X POST "http://localhost:8000/api/lessons/1/complete" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

### 7. Test Certificate Endpoints

#### List My Certificates
```bash
curl -X GET "http://localhost:8000/api/certificates" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

#### Verify Certificate (Public)
```bash
curl -X POST "http://localhost:8000/api/certificates/verify" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "certificate_number": "CERT-2024-001"
  }'
```

### 8. Test Rate Limiting

#### Test Authentication Rate Limit
```bash
# Run this script to test rate limiting (5 requests/minute)
for i in {1..6}; do
  echo "Request $i:"
  curl -X POST http://localhost:8000/api/login \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -d '{"email":"test@example.com","password":"wrong"}' \
    -w "\nHTTP Status: %{http_code}\n\n"
done
```

The 6th request should return HTTP 429 (Too Many Requests).

## Using Postman

### 1. Import OpenAPI Specification

1. Open Postman
2. Click "Import"
3. Select `docs/openapi.yaml`
4. All endpoints will be imported automatically

### 2. Set Up Environment

Create a new environment with these variables:
- `base_url`: `http://localhost:8000/api`
- `token`: (will be set after login)

### 3. Test Authentication Flow

1. Send POST request to `/register`
2. Copy the token from response
3. Set the `token` environment variable
4. All subsequent requests will use this token automatically

### 4. Test Collection

Create a collection with these requests in order:
1. Register User
2. Login
3. Get Current User
4. List Courses
5. Enroll in Course
6. List Enrollments
7. Track Progress
8. Get Certificates
9. Logout

## Using Insomnia

### 1. Import OpenAPI Specification

1. Open Insomnia
2. Click "Create" → "Import From" → "File"
3. Select `docs/openapi.yaml`

### 2. Configure Authentication

1. Go to any authenticated request
2. Select "Auth" tab
3. Choose "Bearer Token"
4. Enter your token

### 3. Use Environment Variables

Create environment with:
```json
{
  "base_url": "http://localhost:8000/api",
  "token": "your-token-here"
}
```

## Testing with JavaScript

### Using Fetch API

```javascript
// Login
const login = async () => {
  const response = await fetch('http://localhost:8000/api/login', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json'
    },
    body: JSON.stringify({
      email: 'test@example.com',
      password: 'password123'
    })
  });
  
  const data = await response.json();
  localStorage.setItem('token', data.data.token);
  return data;
};

// Get courses
const getCourses = async () => {
  const token = localStorage.getItem('token');
  const response = await fetch('http://localhost:8000/api/courses', {
    headers: {
      'Authorization': `Bearer ${token}`,
      'Accept': 'application/json'
    }
  });
  
  return await response.json();
};
```

### Using Axios

```javascript
import axios from 'axios';

const api = axios.create({
  baseURL: 'http://localhost:8000/api',
  headers: {
    'Accept': 'application/json',
    'Content-Type': 'application/json'
  }
});

// Add token to requests
api.interceptors.request.use(config => {
  const token = localStorage.getItem('token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

// Login
const login = async (email, password) => {
  const response = await api.post('/login', { email, password });
  localStorage.setItem('token', response.data.data.token);
  return response.data;
};

// Get courses
const getCourses = async (params = {}) => {
  const response = await api.get('/courses', { params });
  return response.data;
};
```


## Testing with Python

### Using Requests Library

```python
import requests

class LMSClient:
    def __init__(self, base_url='http://localhost:8000/api'):
        self.base_url = base_url
        self.token = None
        self.session = requests.Session()
        self.session.headers.update({
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        })
    
    def login(self, email, password):
        response = self.session.post(
            f'{self.base_url}/login',
            json={'email': email, 'password': password}
        )
        data = response.json()
        self.token = data['data']['token']
        self.session.headers.update({
            'Authorization': f'Bearer {self.token}'
        })
        return data
    
    def get_courses(self, **params):
        response = self.session.get(
            f'{self.base_url}/courses',
            params=params
        )
        return response.json()
    
    def enroll(self, course_id):
        response = self.session.post(
            f'{self.base_url}/courses/{course_id}/enroll'
        )
        return response.json()

# Usage
client = LMSClient()
client.login('test@example.com', 'password123')
courses = client.get_courses(difficulty_level='beginner')
print(courses)
```

## Common Test Scenarios

### Scenario 1: New User Registration and Course Enrollment

```bash
# 1. Register
TOKEN=$(curl -s -X POST http://localhost:8000/api/register \
  -H "Content-Type: application/json" \
  -d '{"name":"John Doe","email":"john@example.com","password":"password123","password_confirmation":"password123"}' \
  | jq -r '.data.token')

# 2. List available courses
curl -X GET "http://localhost:8000/api/courses" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json"

# 3. Enroll in a course
curl -X POST "http://localhost:8000/api/courses/1/enroll" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json"

# 4. Check enrollments
curl -X GET "http://localhost:8000/api/enrollments" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json"
```

### Scenario 2: Course Progress Tracking

```bash
# 1. Login
TOKEN=$(curl -s -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"john@example.com","password":"password123"}' \
  | jq -r '.data.token')

# 2. Get lesson details
curl -X GET "http://localhost:8000/api/lessons/1" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json"

# 3. Track progress
curl -X POST "http://localhost:8000/api/lessons/1/progress" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"time_spent":600,"bookmark_position":300,"completed":false}'

# 4. Mark complete
curl -X POST "http://localhost:8000/api/lessons/1/complete" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json"
```

### Scenario 3: Certificate Verification

```bash
# Public endpoint - no authentication required
curl -X POST "http://localhost:8000/api/certificates/verify" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"certificate_number":"CERT-2024-001"}'
```

## Troubleshooting

### Issue: 401 Unauthorized

**Cause:** Missing or invalid token

**Solution:**
- Ensure you're including the Authorization header
- Check that the token is valid and not expired
- Login again to get a fresh token

### Issue: 422 Validation Error

**Cause:** Invalid request data

**Solution:**
- Check the error response for specific validation errors
- Ensure all required fields are provided
- Verify data types and formats

### Issue: 429 Too Many Requests

**Cause:** Rate limit exceeded

**Solution:**
- Wait for the rate limit to reset (check Retry-After header)
- Reduce request frequency
- Use different user accounts for testing

### Issue: 404 Not Found

**Cause:** Resource doesn't exist or endpoint is incorrect

**Solution:**
- Verify the resource ID exists
- Check the endpoint URL
- Ensure the resource is published (for courses)

### Issue: 403 Forbidden

**Cause:** Insufficient permissions

**Solution:**
- Check user role and permissions
- Verify you're accessing your own resources
- Use an admin account if needed

## Performance Testing

### Using Apache Bench

```bash
# Test login endpoint
ab -n 100 -c 10 -p login.json -T application/json \
  http://localhost:8000/api/login

# Test authenticated endpoint
ab -n 100 -c 10 -H "Authorization: Bearer TOKEN" \
  http://localhost:8000/api/courses
```

### Using Artillery

Create `artillery-config.yml`:
```yaml
config:
  target: 'http://localhost:8000'
  phases:
    - duration: 60
      arrivalRate: 10
scenarios:
  - name: "API Load Test"
    flow:
      - post:
          url: "/api/login"
          json:
            email: "test@example.com"
            password: "password123"
          capture:
            - json: "$.data.token"
              as: "token"
      - get:
          url: "/api/courses"
          headers:
            Authorization: "Bearer {{ token }}"
```

Run: `artillery run artillery-config.yml`

## Best Practices

1. **Always use HTTPS in production**
2. **Store tokens securely** (not in localStorage for sensitive apps)
3. **Handle rate limiting gracefully** with exponential backoff
4. **Validate responses** before using data
5. **Log errors** for debugging
6. **Use environment variables** for configuration
7. **Implement retry logic** for transient failures
8. **Cache responses** when appropriate
9. **Monitor API usage** and performance
10. **Keep documentation updated** with API changes

## Additional Resources

- [API Documentation](./API_DOCUMENTATION.md)
- [API Quick Reference](./API_QUICK_REFERENCE.md)
- [OpenAPI Specification](./openapi.yaml)
- [Laravel Sanctum Documentation](https://laravel.com/docs/sanctum)
- [RESTful API Best Practices](https://restfulapi.net/)
