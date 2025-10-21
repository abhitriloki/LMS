# API Usage Examples

This document provides practical examples of using the LMS API.

## Authentication

### Register a New User
```bash
curl -X POST http://localhost:5000/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "username": "john_doe",
    "email": "john@example.com",
    "password": "password123",
    "first_name": "John",
    "last_name": "Doe"
  }'
```

### Login
```bash
curl -X POST http://localhost:5000/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username":"john_doe","password":"password123"}' \
  -c cookies.txt
```

### Get User Profile
```bash
curl -X GET http://localhost:5000/auth/profile \
  -b cookies.txt
```

## Courses

### List All Courses
```bash
curl -X GET http://localhost:5000/courses/
```

### Get Course Details
```bash
curl -X GET http://localhost:5000/courses/1
```

### Enroll in a Course
```bash
curl -X POST http://localhost:5000/courses/1/enroll \
  -b cookies.txt
```

### Get Enrolled Courses
```bash
curl -X GET http://localhost:5000/courses/enrolled \
  -b cookies.txt
```

### Create a New Course
```bash
curl -X POST http://localhost:5000/courses/ \
  -H "Content-Type: application/json" \
  -b cookies.txt \
  -d '{
    "title": "Introduction to Machine Learning",
    "description": "Learn ML fundamentals",
    "category": "Data Science",
    "difficulty": "Intermediate",
    "duration_hours": 20
  }'
```

### Add a Lesson to a Course
```bash
curl -X POST http://localhost:5000/courses/1/lessons \
  -H "Content-Type: application/json" \
  -b cookies.txt \
  -d '{
    "title": "Introduction to Python",
    "content": "Learn Python basics...",
    "order": 1,
    "video_url": "https://example.com/video1"
  }'
```

## Assessments

### Create an Assessment
```bash
curl -X POST http://localhost:5000/assessments/ \
  -H "Content-Type: application/json" \
  -b cookies.txt \
  -d '{
    "course_id": 1,
    "title": "Python Basics Quiz",
    "type": "quiz",
    "passing_score": 70,
    "time_limit_minutes": 30
  }'
```

### Add Questions to Assessment
```bash
curl -X POST http://localhost:5000/assessments/1/questions \
  -H "Content-Type: application/json" \
  -b cookies.txt \
  -d '{
    "question_text": "What is Python?",
    "question_type": "multiple_choice",
    "points": 1,
    "options": [
      {"text": "A programming language", "is_correct": true},
      {"text": "A snake", "is_correct": false},
      {"text": "A database", "is_correct": false}
    ]
  }'
```

### Start an Assessment
```bash
curl -X POST http://localhost:5000/assessments/1/start \
  -b cookies.txt
```

### Submit Assessment Answers
```bash
curl -X POST http://localhost:5000/assessments/attempts/1/submit \
  -H "Content-Type: application/json" \
  -b cookies.txt \
  -d '{
    "answers": [
      {"question_id": 1, "selected_option_id": 2},
      {"question_id": 2, "selected_option_id": 5}
    ]
  }'
```

### Get Assessment Results
```bash
curl -X GET http://localhost:5000/assessments/attempts/1 \
  -b cookies.txt
```

## Progress Tracking

### Get Course Progress
```bash
curl -X GET http://localhost:5000/progress/course/1 \
  -b cookies.txt
```

### Mark Lesson as Complete
```bash
curl -X POST http://localhost:5000/progress/lesson/1/complete \
  -b cookies.txt
```

### Get All Progress
```bash
curl -X GET http://localhost:5000/progress/my-progress \
  -b cookies.txt
```

## Certificates

### Generate Certificate
```bash
curl -X POST http://localhost:5000/certificates/generate/1 \
  -b cookies.txt
```

### Download Certificate
```bash
curl -X GET http://localhost:5000/certificates/1/download \
  -b cookies.txt \
  -o certificate.pdf
```

### Get My Certificates
```bash
curl -X GET http://localhost:5000/certificates/my-certificates \
  -b cookies.txt
```

### Verify Certificate (Public)
```bash
curl -X GET http://localhost:5000/certificates/verify/LMS-ABC123DEF456
```

## AI Recommendations

### Get Personalized Recommendations
```bash
curl -X GET http://localhost:5000/recommendations/ \
  -b cookies.txt
```

### Get Similar Courses
```bash
curl -X GET http://localhost:5000/recommendations/similar/1 \
  -b cookies.txt
```

### Get Trending Courses
```bash
curl -X GET http://localhost:5000/recommendations/trending
```

## Python Examples

### Using the requests library

```python
import requests

# Base URL
BASE_URL = "http://localhost:5000"

# Create a session to persist cookies
session = requests.Session()

# Register
response = session.post(f"{BASE_URL}/auth/register", json={
    "username": "alice",
    "email": "alice@example.com",
    "password": "secure123",
    "first_name": "Alice",
    "last_name": "Smith"
})
print(response.json())

# Login
response = session.post(f"{BASE_URL}/auth/login", json={
    "username": "alice",
    "password": "secure123"
})
print(response.json())

# Get courses
response = session.get(f"{BASE_URL}/courses/")
courses = response.json()["courses"]
print(f"Found {len(courses)} courses")

# Enroll in first course
if courses:
    course_id = courses[0]["id"]
    response = session.post(f"{BASE_URL}/courses/{course_id}/enroll")
    print(response.json())

# Get recommendations
response = session.get(f"{BASE_URL}/recommendations/")
recommendations = response.json()["recommendations"]
for rec in recommendations[:3]:
    print(f"- {rec['title']} (Score: {rec['recommendation_score']})")
```

## Complete Learning Flow Example

```bash
# 1. Register and login
curl -X POST http://localhost:5000/auth/register \
  -H "Content-Type: application/json" \
  -d '{"username":"learner","email":"learner@example.com","password":"pass123","first_name":"Jane","last_name":"Learner"}' \
  -c cookies.txt

curl -X POST http://localhost:5000/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username":"learner","password":"pass123"}' \
  -c cookies.txt

# 2. Browse courses
curl -X GET http://localhost:5000/courses/ -b cookies.txt

# 3. Enroll in a course
curl -X POST http://localhost:5000/courses/1/enroll -b cookies.txt

# 4. Start learning - mark lessons complete
curl -X POST http://localhost:5000/progress/lesson/1/complete -b cookies.txt
curl -X POST http://localhost:5000/progress/lesson/2/complete -b cookies.txt

# 5. Take an assessment
curl -X POST http://localhost:5000/assessments/1/start -b cookies.txt
curl -X POST http://localhost:5000/assessments/attempts/1/submit \
  -H "Content-Type: application/json" \
  -b cookies.txt \
  -d '{"answers":[{"question_id":1,"selected_option_id":2}]}'

# 6. Check progress
curl -X GET http://localhost:5000/progress/my-progress -b cookies.txt

# 7. Generate certificate (after completing all lessons)
curl -X POST http://localhost:5000/certificates/generate/1 -b cookies.txt

# 8. Get AI recommendations
curl -X GET http://localhost:5000/recommendations/ -b cookies.txt
```

## Response Examples

### Course List Response
```json
{
  "courses": [
    {
      "id": 1,
      "title": "Introduction to Python Programming",
      "description": "Learn Python from scratch",
      "category": "Programming",
      "difficulty": "Beginner",
      "duration_hours": 10,
      "lessons_count": 5,
      "enrolled_students": 2
    }
  ]
}
```

### Recommendation Response
```json
{
  "recommendations": [
    {
      "course_id": 2,
      "title": "Web Development with Flask",
      "description": "Build web apps with Flask",
      "category": "Programming",
      "difficulty": "Intermediate",
      "duration_hours": 15,
      "recommendation_score": 85.5,
      "reasons": [
        "Matches your interest in Programming",
        "Perfect next step in your learning journey",
        "Popular among learners like you"
      ]
    }
  ]
}
```

### Progress Response
```json
{
  "progress": [
    {
      "course_id": 1,
      "course_title": "Introduction to Python Programming",
      "total_lessons": 5,
      "completed_lessons": 3,
      "progress_percentage": 60.0,
      "completed": false,
      "last_accessed": "2025-10-21T10:30:00"
    }
  ]
}
```
