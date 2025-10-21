from flask import Blueprint, request, jsonify
from flask_login import login_required, current_user
from models import db, Progress, Course, Lesson
from datetime import datetime

progress_bp = Blueprint('progress', __name__)

@progress_bp.route('/course/<int:course_id>', methods=['GET'])
@login_required
def get_course_progress(course_id):
    """Get user's progress for a specific course."""
    course = Course.query.get_or_404(course_id)
    
    # Get all lessons in the course
    lessons = course.lessons.all()
    total_lessons = len(lessons)
    
    # Get completed lessons
    completed_lessons = Progress.query.filter_by(
        user_id=current_user.id,
        course_id=course_id,
        completed=True
    ).count()
    
    # Calculate overall progress
    overall_progress = (completed_lessons / total_lessons * 100) if total_lessons > 0 else 0
    
    # Get progress for each lesson
    lesson_progress = []
    for lesson in lessons:
        progress = Progress.query.filter_by(
            user_id=current_user.id,
            course_id=course_id,
            lesson_id=lesson.id
        ).first()
        
        lesson_progress.append({
            'lesson_id': lesson.id,
            'lesson_title': lesson.title,
            'completed': progress.completed if progress else False,
            'last_accessed': progress.last_accessed.isoformat() if progress and progress.last_accessed else None
        })
    
    return jsonify({
        'course_id': course_id,
        'course_title': course.title,
        'total_lessons': total_lessons,
        'completed_lessons': completed_lessons,
        'overall_progress': round(overall_progress, 2),
        'lesson_progress': lesson_progress
    }), 200

@progress_bp.route('/lesson/<int:lesson_id>/complete', methods=['POST'])
@login_required
def mark_lesson_complete(lesson_id):
    """Mark a lesson as completed."""
    lesson = Lesson.query.get_or_404(lesson_id)
    
    # Check if user is enrolled in the course
    if lesson.course not in current_user.enrolled_courses:
        return jsonify({'error': 'Not enrolled in this course'}), 403
    
    # Check if progress record exists
    progress = Progress.query.filter_by(
        user_id=current_user.id,
        course_id=lesson.course_id,
        lesson_id=lesson_id
    ).first()
    
    if progress:
        progress.completed = True
        progress.completed_at = datetime.utcnow()
        progress.last_accessed = datetime.utcnow()
    else:
        progress = Progress(
            user_id=current_user.id,
            course_id=lesson.course_id,
            lesson_id=lesson_id,
            completed=True,
            completed_at=datetime.utcnow(),
            last_accessed=datetime.utcnow()
        )
        db.session.add(progress)
    
    # Update course completion percentage
    total_lessons = lesson.course.lessons.count()
    completed_lessons = Progress.query.filter_by(
        user_id=current_user.id,
        course_id=lesson.course_id,
        completed=True
    ).count()
    
    completion_percentage = (completed_lessons / total_lessons * 100) if total_lessons > 0 else 0
    
    # Update or create course-level progress
    course_progress = Progress.query.filter_by(
        user_id=current_user.id,
        course_id=lesson.course_id,
        lesson_id=None
    ).first()
    
    if course_progress:
        course_progress.completion_percentage = completion_percentage
        course_progress.completed = (completion_percentage == 100)
        if course_progress.completed:
            course_progress.completed_at = datetime.utcnow()
    else:
        course_progress = Progress(
            user_id=current_user.id,
            course_id=lesson.course_id,
            lesson_id=None,
            completion_percentage=completion_percentage,
            completed=(completion_percentage == 100),
            completed_at=datetime.utcnow() if completion_percentage == 100 else None
        )
        db.session.add(course_progress)
    
    db.session.commit()
    
    return jsonify({
        'message': 'Lesson marked as complete',
        'course_progress': round(completion_percentage, 2),
        'course_completed': completion_percentage == 100
    }), 200

@progress_bp.route('/my-progress', methods=['GET'])
@login_required
def get_my_progress():
    """Get progress for all enrolled courses."""
    enrolled_courses = current_user.enrolled_courses
    
    progress_data = []
    for course in enrolled_courses:
        total_lessons = course.lessons.count()
        completed_lessons = Progress.query.filter_by(
            user_id=current_user.id,
            course_id=course.id,
            completed=True
        ).filter(Progress.lesson_id.isnot(None)).count()
        
        overall_progress = (completed_lessons / total_lessons * 100) if total_lessons > 0 else 0
        
        # Get course-level progress
        course_progress = Progress.query.filter_by(
            user_id=current_user.id,
            course_id=course.id,
            lesson_id=None
        ).first()
        
        progress_data.append({
            'course_id': course.id,
            'course_title': course.title,
            'total_lessons': total_lessons,
            'completed_lessons': completed_lessons,
            'progress_percentage': round(overall_progress, 2),
            'completed': course_progress.completed if course_progress else False,
            'last_accessed': course_progress.last_accessed.isoformat() if course_progress and course_progress.last_accessed else None
        })
    
    return jsonify({'progress': progress_data}), 200

@progress_bp.route('/lesson/<int:lesson_id>/access', methods=['POST'])
@login_required
def record_lesson_access(lesson_id):
    """Record that a user accessed a lesson."""
    lesson = Lesson.query.get_or_404(lesson_id)
    
    # Check if user is enrolled in the course
    if lesson.course not in current_user.enrolled_courses:
        return jsonify({'error': 'Not enrolled in this course'}), 403
    
    # Update or create progress record
    progress = Progress.query.filter_by(
        user_id=current_user.id,
        course_id=lesson.course_id,
        lesson_id=lesson_id
    ).first()
    
    if progress:
        progress.last_accessed = datetime.utcnow()
    else:
        progress = Progress(
            user_id=current_user.id,
            course_id=lesson.course_id,
            lesson_id=lesson_id,
            completed=False,
            last_accessed=datetime.utcnow()
        )
        db.session.add(progress)
    
    db.session.commit()
    
    return jsonify({'message': 'Lesson access recorded'}), 200
