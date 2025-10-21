from flask import Blueprint, request, jsonify
from flask_login import login_required, current_user
from models import db, Course, Lesson, enrollments
from sqlalchemy import func

courses_bp = Blueprint('courses', __name__)

@courses_bp.route('/', methods=['GET'])
def list_courses():
    """List all available courses."""
    category = request.args.get('category')
    difficulty = request.args.get('difficulty')
    
    query = Course.query
    
    if category:
        query = query.filter_by(category=category)
    if difficulty:
        query = query.filter_by(difficulty=difficulty)
    
    courses = query.all()
    
    return jsonify({
        'courses': [{
            'id': course.id,
            'title': course.title,
            'description': course.description,
            'category': course.category,
            'difficulty': course.difficulty,
            'duration_hours': course.duration_hours,
            'lessons_count': course.lessons.count(),
            'enrolled_students': course.enrolled_users.count()
        } for course in courses]
    }), 200

@courses_bp.route('/<int:course_id>', methods=['GET'])
def get_course(course_id):
    """Get details of a specific course."""
    course = Course.query.get_or_404(course_id)
    
    lessons = [{
        'id': lesson.id,
        'title': lesson.title,
        'content': lesson.content,
        'order': lesson.order,
        'video_url': lesson.video_url
    } for lesson in course.lessons.order_by(Lesson.order)]
    
    assessments = [{
        'id': assessment.id,
        'title': assessment.title,
        'type': assessment.type,
        'passing_score': assessment.passing_score,
        'questions_count': assessment.questions.count()
    } for assessment in course.assessments]
    
    return jsonify({
        'id': course.id,
        'title': course.title,
        'description': course.description,
        'category': course.category,
        'difficulty': course.difficulty,
        'duration_hours': course.duration_hours,
        'lessons': lessons,
        'assessments': assessments,
        'enrolled_students': course.enrolled_users.count()
    }), 200

@courses_bp.route('/', methods=['POST'])
@login_required
def create_course():
    """Create a new course (admin/instructor only)."""
    data = request.get_json()
    
    if not data or not data.get('title'):
        return jsonify({'error': 'Course title is required'}), 400
    
    course = Course(
        title=data['title'],
        description=data.get('description', ''),
        category=data.get('category', ''),
        difficulty=data.get('difficulty', 'Beginner'),
        duration_hours=data.get('duration_hours', 0)
    )
    
    db.session.add(course)
    db.session.commit()
    
    return jsonify({
        'message': 'Course created successfully',
        'course': {
            'id': course.id,
            'title': course.title
        }
    }), 201

@courses_bp.route('/<int:course_id>/enroll', methods=['POST'])
@login_required
def enroll_course(course_id):
    """Enroll current user in a course."""
    course = Course.query.get_or_404(course_id)
    
    # Check if already enrolled
    if course in current_user.enrolled_courses:
        return jsonify({'error': 'Already enrolled in this course'}), 400
    
    current_user.enrolled_courses.append(course)
    db.session.commit()
    
    return jsonify({
        'message': 'Successfully enrolled in course',
        'course': {
            'id': course.id,
            'title': course.title
        }
    }), 200

@courses_bp.route('/<int:course_id>/lessons', methods=['POST'])
@login_required
def add_lesson(course_id):
    """Add a lesson to a course."""
    course = Course.query.get_or_404(course_id)
    data = request.get_json()
    
    if not data or not data.get('title'):
        return jsonify({'error': 'Lesson title is required'}), 400
    
    lesson = Lesson(
        course_id=course_id,
        title=data['title'],
        content=data.get('content', ''),
        order=data.get('order', course.lessons.count() + 1),
        video_url=data.get('video_url', '')
    )
    
    db.session.add(lesson)
    db.session.commit()
    
    return jsonify({
        'message': 'Lesson added successfully',
        'lesson': {
            'id': lesson.id,
            'title': lesson.title
        }
    }), 201

@courses_bp.route('/enrolled', methods=['GET'])
@login_required
def get_enrolled_courses():
    """Get courses the current user is enrolled in."""
    courses = [{
        'id': course.id,
        'title': course.title,
        'description': course.description,
        'category': course.category,
        'difficulty': course.difficulty,
        'progress': get_course_progress(current_user.id, course.id)
    } for course in current_user.enrolled_courses]
    
    return jsonify({'enrolled_courses': courses}), 200

def get_course_progress(user_id, course_id):
    """Helper function to calculate course progress."""
    from models import Progress
    
    course = Course.query.get(course_id)
    total_lessons = course.lessons.count()
    
    if total_lessons == 0:
        return 0.0
    
    completed_lessons = Progress.query.filter_by(
        user_id=user_id,
        course_id=course_id,
        completed=True
    ).count()
    
    return round((completed_lessons / total_lessons) * 100, 2)
