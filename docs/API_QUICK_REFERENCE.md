# API Quick Reference

## Base URL
```
https://your-domain.com/api
```

## Authentication
```
Authorization: Bearer {token}
```

## Rate Limits
- General: 60/min
- Auth: 5/min
- Heavy: 10/min

## Common Headers
```
Content-Type: application/json
Accept: application/json
Authorization: Bearer {token}
```

## Quick Endpoint Reference

### Authentication
| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| POST | /register | Register new user | No |
| POST | /login | Login user | No |
| GET | /me | Get current user | Yes |
| POST | /logout | Logout | Yes |
| POST | /logout-all | Logout all devices | Yes |

### Courses
| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| GET | /courses | List courses | Yes |
| GET | /courses/{id} | Get course | Yes |
| GET | /courses/{id}/modules | Get modules | Yes |

### Enrollments
| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| GET | /enrollments | List enrollments | Yes |
| POST | /courses/{id}/enroll | Enroll in course | Yes |
| GET | /enrollments/{id} | Get enrollment | Yes |
| DELETE | /enrollments/{id} | Unenroll | Yes |

### Lessons
| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| GET | /lessons/{id} | Get lesson | Yes |
| POST | /lessons/{id}/progress | Track progress | Yes |
| POST | /lessons/{id}/complete | Mark complete | Yes |

### Assessments
| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| GET | /courses/{id}/assessments | List assessments | Yes |
| GET | /assessments/{id} | Get assessment | Yes |
| GET | /assessments/{id}/attempts | Get attempts | Yes |

### Certificates
| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| GET | /certificates | List certificates | Yes |
| GET | /certificates/{id} | Get certificate | Yes |
| POST | /certificates/verify | Verify certificate | No |

### Users
| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| GET | /users | List users (admin) | Yes |
| GET | /users/{id} | Get user | Yes |
| PUT | /profile | Update profile | Yes |
| PUT | /profile/password | Update password | Yes |

## Common Query Parameters
- `page`: Page number
- `per_page`: Items per page (max: 100)
- `sort_by`: Sort field
- `sort_order`: asc/desc
- `search`: Search term

## Response Codes
- 200: Success
- 201: Created
- 400: Bad Request
- 401: Unauthorized
- 403: Forbidden
- 404: Not Found
- 422: Validation Error
- 429: Rate Limit Exceeded
- 500: Server Error

