# Learning Management System (LMS)

A comprehensive AI-powered Learning Management System built with Flask. This digital school for your company enables employees to take online courses, complete tests and quizzes, earn certificates, track learning progress, and receive AI-powered course recommendations.

## 🎓 Features

### 1. **Online Courses**
- Browse courses across multiple categories and difficulty levels
- View detailed course information with lessons and assessments
- Enroll in courses of interest
- Access video lessons and learning materials

### 2. **Tests and Quizzes**
- Take interactive assessments to test knowledge
- Multiple choice questions with instant feedback
- Timed tests with passing score requirements
- Track all assessment attempts and scores

### 3. **Certificates**
- Automatically generate PDF certificates upon course completion
- Unique certificate codes for verification
- Download certificates for sharing
- Public certificate verification endpoint

### 4. **Learning Progress Tracking**
- Monitor progress for each enrolled course
- Track completed lessons and overall completion percentage
- View detailed learning statistics
- Access history of all learning activities

### 5. **AI-Powered Course Recommendations**
- Get personalized course recommendations based on:
  - Completed courses and categories of interest
  - Assessment performance and difficulty progression
  - Collaborative filtering from similar learners
  - Content-based filtering on course attributes
- Discover trending courses
- Find similar courses to ones you've completed

## 🚀 Quick Start

### Prerequisites
- Python 3.8 or higher
- pip (Python package manager)

### Installation

1. **Clone the repository:**
```bash
git clone https://github.com/abhitriloki/LMS.git
cd LMS
```

2. **Install dependencies:**
```bash
pip install -r requirements.txt
```

3. **Seed the database with sample data:**
```bash
python seed_data.py
```

4. **Run the application:**
```bash
python app.py
```

5. **Access the application:**
- Web Interface: http://localhost:5000
- API Documentation: http://localhost:5000/api

### Sample Credentials
After seeding the database, you can use these credentials:
- **Username:** john_doe, **Password:** password123
- **Username:** jane_smith, **Password:** password123

## 📚 API Endpoints

### Authentication
- `POST /auth/register` - Register a new user
- `POST /auth/login` - Login to the system
- `POST /auth/logout` - Logout (requires authentication)
- `GET /auth/profile` - Get current user profile (requires authentication)

### Courses
- `GET /courses/` - List all available courses
- `GET /courses/<id>` - Get course details
- `POST /courses/` - Create a new course (requires authentication)
- `POST /courses/<id>/enroll` - Enroll in a course (requires authentication)
- `POST /courses/<id>/lessons` - Add a lesson to a course (requires authentication)
- `GET /courses/enrolled` - Get enrolled courses (requires authentication)

### Assessments
- `POST /assessments/` - Create a new assessment (requires authentication)
- `GET /assessments/<id>` - Get assessment details (requires authentication)
- `POST /assessments/<id>/questions` - Add questions to assessment (requires authentication)
- `POST /assessments/<id>/start` - Start an assessment attempt (requires authentication)
- `POST /assessments/attempts/<id>/submit` - Submit assessment answers (requires authentication)
- `GET /assessments/attempts/<id>` - Get attempt results (requires authentication)
- `GET /assessments/my-attempts` - Get all user attempts (requires authentication)

### Progress
- `GET /progress/course/<id>` - Get progress for a specific course (requires authentication)
- `POST /progress/lesson/<id>/complete` - Mark a lesson as complete (requires authentication)
- `POST /progress/lesson/<id>/access` - Record lesson access (requires authentication)
- `GET /progress/my-progress` - Get progress for all enrolled courses (requires authentication)

### Certificates
- `POST /certificates/generate/<course_id>` - Generate a certificate (requires authentication)
- `GET /certificates/<id>/download` - Download certificate PDF (requires authentication)
- `GET /certificates/my-certificates` - Get all earned certificates (requires authentication)
- `GET /certificates/verify/<code>` - Verify a certificate (public endpoint)

### Recommendations
- `GET /recommendations/` - Get AI-powered course recommendations (requires authentication)
- `GET /recommendations/similar/<course_id>` - Get similar courses (requires authentication)
- `GET /recommendations/trending` - Get trending courses (public endpoint)

## 🧪 Testing

Run tests with:
```bash
python -m pytest tests/
```

## 📊 Database Schema

The LMS uses SQLite with the following main models:
- **User** - User accounts and authentication
- **Course** - Course information and metadata
- **Lesson** - Individual lessons within courses
- **Assessment** - Tests and quizzes
- **Question** - Assessment questions
- **QuestionOption** - Multiple choice options
- **TestAttempt** - User assessment attempts
- **UserAnswer** - User answers to questions
- **Progress** - Learning progress tracking
- **Certificate** - Course completion certificates

## 🏗️ Architecture

```
LMS/
├── app.py                  # Flask application factory
├── config.py              # Configuration settings
├── models.py              # Database models
├── requirements.txt       # Python dependencies
├── seed_data.py          # Database seeding script
├── routes/               # API route blueprints
│   ├── auth.py          # Authentication endpoints
│   ├── courses.py       # Course management
│   ├── assessments.py   # Assessment system
│   ├── progress.py      # Progress tracking
│   ├── certificates.py  # Certificate generation
│   └── recommendations.py # AI recommendations
├── templates/           # HTML templates
│   └── index.html      # Web interface
└── tests/              # Test suite
```

## 🔧 Configuration

Key configuration options in `config.py`:
- `SECRET_KEY` - Flask secret key for sessions
- `SQLALCHEMY_DATABASE_URI` - Database connection string
- `UPLOAD_FOLDER` - Directory for uploaded course materials
- `CERTIFICATES_FOLDER` - Directory for generated certificates
- `PASSING_SCORE` - Default passing score for assessments (70%)

## 🤖 AI Recommendation Algorithm

The recommendation system uses a hybrid approach:

1. **Content-Based Filtering**
   - Analyzes user's completed courses and categories
   - Matches with courses in similar categories
   - Considers course difficulty based on performance

2. **Collaborative Filtering**
   - Identifies popular courses among similar users
   - Recommends based on enrollment patterns

3. **Performance-Based Progression**
   - Recommends advanced courses for high performers
   - Suggests beginner courses for those needing foundation

4. **Scoring Algorithm**
   - Category match: 40% weight
   - Difficulty progression: 30% weight
   - Popularity among peers: 20% weight
   - New content bonus: 10% weight

## 📝 License

This project is open source and available under the MIT License.

## 👥 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## 📧 Support

For questions or issues, please open an issue on GitHub.
