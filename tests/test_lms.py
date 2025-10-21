"""
Unit tests for the Learning Management System.
"""
import unittest
import json
from app import create_app
from models import db, User, Course, Lesson, Assessment, Question, QuestionOption
from config import Config

class TestConfig(Config):
    """Test configuration."""
    TESTING = True
    SQLALCHEMY_DATABASE_URI = 'sqlite:///:memory:'
    WTF_CSRF_ENABLED = False

class LMSTestCase(unittest.TestCase):
    """Base test case for LMS."""
    
    def setUp(self):
        """Set up test client and database."""
        self.app = create_app(TestConfig)
        self.client = self.app.test_client()
        
        with self.app.app_context():
            db.create_all()
            self._create_test_data()
    
    def tearDown(self):
        """Clean up after tests."""
        with self.app.app_context():
            db.session.remove()
            db.drop_all()
    
    def _create_test_data(self):
        """Create test data."""
        # Create test user
        user = User(
            username='testuser',
            email='test@example.com',
            first_name='Test',
            last_name='User'
        )
        user.set_password('testpass123')
        db.session.add(user)
        
        # Create test course
        course = Course(
            title='Test Course',
            description='A test course',
            category='Testing',
            difficulty='Beginner',
            duration_hours=5
        )
        db.session.add(course)
        db.session.flush()
        
        # Create test lesson
        lesson = Lesson(
            course_id=course.id,
            title='Test Lesson',
            content='Test content',
            order=1
        )
        db.session.add(lesson)
        
        db.session.commit()

class TestAuth(LMSTestCase):
    """Test authentication endpoints."""
    
    def test_register_user(self):
        """Test user registration."""
        response = self.client.post('/auth/register', 
            json={
                'username': 'newuser',
                'email': 'new@example.com',
                'password': 'newpass123',
                'first_name': 'New',
                'last_name': 'User'
            })
        
        self.assertEqual(response.status_code, 201)
        data = json.loads(response.data)
        self.assertEqual(data['message'], 'User registered successfully')
        self.assertEqual(data['user']['username'], 'newuser')
    
    def test_register_duplicate_username(self):
        """Test registration with duplicate username."""
        response = self.client.post('/auth/register',
            json={
                'username': 'testuser',  # Already exists
                'email': 'another@example.com',
                'password': 'pass123'
            })
        
        self.assertEqual(response.status_code, 400)
        data = json.loads(response.data)
        self.assertIn('error', data)
    
    def test_login_success(self):
        """Test successful login."""
        response = self.client.post('/auth/login',
            json={
                'username': 'testuser',
                'password': 'testpass123'
            })
        
        self.assertEqual(response.status_code, 200)
        data = json.loads(response.data)
        self.assertEqual(data['message'], 'Login successful')
    
    def test_login_invalid_credentials(self):
        """Test login with invalid credentials."""
        response = self.client.post('/auth/login',
            json={
                'username': 'testuser',
                'password': 'wrongpassword'
            })
        
        self.assertEqual(response.status_code, 401)

class TestCourses(LMSTestCase):
    """Test course endpoints."""
    
    def test_list_courses(self):
        """Test listing all courses."""
        response = self.client.get('/courses/')
        
        self.assertEqual(response.status_code, 200)
        data = json.loads(response.data)
        self.assertIn('courses', data)
        self.assertGreater(len(data['courses']), 0)
    
    def test_get_course_details(self):
        """Test getting course details."""
        with self.app.app_context():
            course = Course.query.first()
            course_id = course.id
        
        response = self.client.get(f'/courses/{course_id}')
        
        self.assertEqual(response.status_code, 200)
        data = json.loads(response.data)
        self.assertEqual(data['title'], 'Test Course')
    
    def test_create_course(self):
        """Test creating a new course."""
        # Login first
        self.client.post('/auth/login',
            json={'username': 'testuser', 'password': 'testpass123'})
        
        response = self.client.post('/courses/',
            json={
                'title': 'New Course',
                'description': 'A new test course',
                'category': 'Testing',
                'difficulty': 'Intermediate'
            })
        
        self.assertEqual(response.status_code, 201)
        data = json.loads(response.data)
        self.assertEqual(data['message'], 'Course created successfully')
    
    def test_enroll_in_course(self):
        """Test enrolling in a course."""
        # Login first
        self.client.post('/auth/login',
            json={'username': 'testuser', 'password': 'testpass123'})
        
        with self.app.app_context():
            course_id = Course.query.first().id
        
        response = self.client.post(f'/courses/{course_id}/enroll')
        
        self.assertEqual(response.status_code, 200)
        data = json.loads(response.data)
        self.assertEqual(data['message'], 'Successfully enrolled in course')

class TestAssessments(LMSTestCase):
    """Test assessment endpoints."""
    
    def test_create_assessment(self):
        """Test creating an assessment."""
        self.client.post('/auth/login',
            json={'username': 'testuser', 'password': 'testpass123'})
        
        with self.app.app_context():
            course_id = Course.query.first().id
        
        response = self.client.post('/assessments/',
            json={
                'course_id': course_id,
                'title': 'Test Quiz',
                'type': 'quiz',
                'passing_score': 70
            })
        
        self.assertEqual(response.status_code, 201)
        data = json.loads(response.data)
        self.assertEqual(data['assessment']['title'], 'Test Quiz')

class TestProgress(LMSTestCase):
    """Test progress tracking endpoints."""
    
    def test_mark_lesson_complete(self):
        """Test marking a lesson as complete."""
        self.client.post('/auth/login',
            json={'username': 'testuser', 'password': 'testpass123'})
        
        # Enroll in course first
        with self.app.app_context():
            course = Course.query.first()
            lesson = Lesson.query.first()
            course_id = course.id
            lesson_id = lesson.id
            self.client.post(f'/courses/{course_id}/enroll')
        
        # Mark lesson complete
        response = self.client.post(f'/progress/lesson/{lesson_id}/complete')
        
        self.assertEqual(response.status_code, 200)
        data = json.loads(response.data)
        self.assertIn('message', data)

class TestRecommendations(LMSTestCase):
    """Test recommendation endpoints."""
    
    def test_get_recommendations(self):
        """Test getting course recommendations."""
        self.client.post('/auth/login',
            json={'username': 'testuser', 'password': 'testpass123'})
        
        response = self.client.get('/recommendations/')
        
        self.assertEqual(response.status_code, 200)
        data = json.loads(response.data)
        self.assertIn('recommendations', data)

if __name__ == '__main__':
    unittest.main()
