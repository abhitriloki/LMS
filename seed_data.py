"""
Seed script to populate the database with sample data for demonstration.
"""
from app import create_app
from models import db, User, Course, Lesson, Assessment, Question, QuestionOption
from datetime import datetime

def seed_database():
    """Populate database with sample data."""
    app = create_app()
    
    with app.app_context():
        # Clear existing data (optional - comment out if you want to keep existing data)
        print("Clearing existing data...")
        db.drop_all()
        db.create_all()
        
        print("Creating sample users...")
        # Create sample users
        user1 = User(
            username='john_doe',
            email='john@example.com',
            first_name='John',
            last_name='Doe'
        )
        user1.set_password('password123')
        
        user2 = User(
            username='jane_smith',
            email='jane@example.com',
            first_name='Jane',
            last_name='Smith'
        )
        user2.set_password('password123')
        
        db.session.add_all([user1, user2])
        db.session.commit()
        
        print("Creating sample courses...")
        # Create sample courses
        course1 = Course(
            title='Introduction to Python Programming',
            description='Learn the fundamentals of Python programming language from scratch. Perfect for beginners!',
            category='Programming',
            difficulty='Beginner',
            duration_hours=10
        )
        
        course2 = Course(
            title='Web Development with Flask',
            description='Build modern web applications using Flask framework. Learn routing, templates, and databases.',
            category='Programming',
            difficulty='Intermediate',
            duration_hours=15
        )
        
        course3 = Course(
            title='Data Science Fundamentals',
            description='Introduction to data science concepts, tools, and techniques using Python.',
            category='Data Science',
            difficulty='Beginner',
            duration_hours=20
        )
        
        course4 = Course(
            title='Machine Learning Basics',
            description='Understand core machine learning algorithms and their applications.',
            category='Data Science',
            difficulty='Intermediate',
            duration_hours=25
        )
        
        course5 = Course(
            title='Advanced Python Techniques',
            description='Master advanced Python concepts including decorators, generators, and metaclasses.',
            category='Programming',
            difficulty='Advanced',
            duration_hours=12
        )
        
        db.session.add_all([course1, course2, course3, course4, course5])
        db.session.commit()
        
        print("Creating lessons for courses...")
        # Add lessons to Python course
        lessons_python = [
            Lesson(course_id=course1.id, title='Introduction to Python', 
                   content='What is Python and why learn it?', order=1,
                   video_url='https://example.com/video1'),
            Lesson(course_id=course1.id, title='Variables and Data Types', 
                   content='Learn about variables, strings, numbers, and booleans.', order=2,
                   video_url='https://example.com/video2'),
            Lesson(course_id=course1.id, title='Control Flow', 
                   content='If statements, loops, and conditional logic.', order=3,
                   video_url='https://example.com/video3'),
            Lesson(course_id=course1.id, title='Functions', 
                   content='Creating and using functions in Python.', order=4,
                   video_url='https://example.com/video4'),
            Lesson(course_id=course1.id, title='Lists and Dictionaries', 
                   content='Working with Python data structures.', order=5,
                   video_url='https://example.com/video5'),
        ]
        
        # Add lessons to Flask course
        lessons_flask = [
            Lesson(course_id=course2.id, title='Flask Basics', 
                   content='Getting started with Flask framework.', order=1),
            Lesson(course_id=course2.id, title='Routing and Views', 
                   content='Creating routes and handling requests.', order=2),
            Lesson(course_id=course2.id, title='Templates with Jinja2', 
                   content='Rendering dynamic HTML templates.', order=3),
            Lesson(course_id=course2.id, title='Database Integration', 
                   content='Working with SQLAlchemy and databases.', order=4),
        ]
        
        # Add lessons to Data Science course
        lessons_ds = [
            Lesson(course_id=course3.id, title='Introduction to Data Science', 
                   content='What is data science and its applications.', order=1),
            Lesson(course_id=course3.id, title='Data Analysis with Pandas', 
                   content='Learn to manipulate data using Pandas.', order=2),
            Lesson(course_id=course3.id, title='Data Visualization', 
                   content='Creating charts and graphs with Matplotlib.', order=3),
        ]
        
        db.session.add_all(lessons_python + lessons_flask + lessons_ds)
        db.session.commit()
        
        print("Creating assessments...")
        # Create assessments
        assessment1 = Assessment(
            course_id=course1.id,
            title='Python Basics Quiz',
            type='quiz',
            passing_score=70,
            time_limit_minutes=30
        )
        
        assessment2 = Assessment(
            course_id=course2.id,
            title='Flask Final Test',
            type='test',
            passing_score=75,
            time_limit_minutes=60
        )
        
        db.session.add_all([assessment1, assessment2])
        db.session.commit()
        
        print("Creating questions for assessments...")
        # Add questions to Python quiz
        q1 = Question(
            assessment_id=assessment1.id,
            question_text='What is the correct way to declare a variable in Python?',
            question_type='multiple_choice',
            points=1,
            order=1
        )
        db.session.add(q1)
        db.session.flush()
        
        options_q1 = [
            QuestionOption(question_id=q1.id, option_text='var x = 5', is_correct=False, order=1),
            QuestionOption(question_id=q1.id, option_text='x = 5', is_correct=True, order=2),
            QuestionOption(question_id=q1.id, option_text='int x = 5', is_correct=False, order=3),
            QuestionOption(question_id=q1.id, option_text='let x = 5', is_correct=False, order=4),
        ]
        
        q2 = Question(
            assessment_id=assessment1.id,
            question_text='Which data type is used for text in Python?',
            question_type='multiple_choice',
            points=1,
            order=2
        )
        db.session.add(q2)
        db.session.flush()
        
        options_q2 = [
            QuestionOption(question_id=q2.id, option_text='int', is_correct=False, order=1),
            QuestionOption(question_id=q2.id, option_text='string', is_correct=False, order=2),
            QuestionOption(question_id=q2.id, option_text='str', is_correct=True, order=3),
            QuestionOption(question_id=q2.id, option_text='text', is_correct=False, order=4),
        ]
        
        q3 = Question(
            assessment_id=assessment1.id,
            question_text='What keyword is used to create a function in Python?',
            question_type='multiple_choice',
            points=1,
            order=3
        )
        db.session.add(q3)
        db.session.flush()
        
        options_q3 = [
            QuestionOption(question_id=q3.id, option_text='function', is_correct=False, order=1),
            QuestionOption(question_id=q3.id, option_text='def', is_correct=True, order=2),
            QuestionOption(question_id=q3.id, option_text='func', is_correct=False, order=3),
            QuestionOption(question_id=q3.id, option_text='define', is_correct=False, order=4),
        ]
        
        db.session.add_all(options_q1 + options_q2 + options_q3)
        db.session.commit()
        
        print("Enrolling sample users in courses...")
        # Enroll users in courses
        user1.enrolled_courses.append(course1)
        user1.enrolled_courses.append(course3)
        user2.enrolled_courses.append(course1)
        user2.enrolled_courses.append(course2)
        
        db.session.commit()
        
        print("\n✅ Database seeded successfully!")
        print("\nSample credentials:")
        print("Username: john_doe, Password: password123")
        print("Username: jane_smith, Password: password123")
        print("\nSample courses created:")
        print(f"- {course1.title} ({course1.difficulty})")
        print(f"- {course2.title} ({course2.difficulty})")
        print(f"- {course3.title} ({course3.difficulty})")
        print(f"- {course4.title} ({course4.difficulty})")
        print(f"- {course5.title} ({course5.difficulty})")

if __name__ == '__main__':
    seed_database()
