from flask import Blueprint, request, jsonify
from flask_login import login_required, current_user
from models import db, Course, Progress, TestAttempt
from sqlalchemy import func
import numpy as np
from sklearn.metrics.pairwise import cosine_similarity

recommendations_bp = Blueprint('recommendations', __name__)

@recommendations_bp.route('/', methods=['GET'])
@login_required
def get_recommendations():
    """Get AI-powered course recommendations for the current user."""
    # Get user's learning profile
    user_profile = _build_user_profile(current_user.id)
    
    # Get all available courses
    all_courses = Course.query.all()
    
    # Get courses user is already enrolled in
    enrolled_course_ids = [course.id for course in current_user.enrolled_courses]
    
    # Filter out enrolled courses
    available_courses = [c for c in all_courses if c.id not in enrolled_course_ids]
    
    if not available_courses:
        return jsonify({
            'message': 'No new courses available for recommendations',
            'recommendations': []
        }), 200
    
    # Calculate recommendations
    recommendations = _calculate_recommendations(user_profile, available_courses, current_user.id)
    
    return jsonify({
        'recommendations': recommendations[:10]  # Top 10 recommendations
    }), 200

def _build_user_profile(user_id):
    """Build a user learning profile based on their activity."""
    profile = {
        'completed_courses': [],
        'categories': {},
        'difficulty_levels': {},
        'avg_test_score': 0,
        'total_courses_completed': 0
    }
    
    # Get completed courses
    completed_progress = Progress.query.filter_by(
        user_id=user_id,
        completed=True,
        lesson_id=None
    ).all()
    
    profile['total_courses_completed'] = len(completed_progress)
    
    for prog in completed_progress:
        course = prog.course
        profile['completed_courses'].append(course.id)
        
        # Track categories
        if course.category:
            profile['categories'][course.category] = profile['categories'].get(course.category, 0) + 1
        
        # Track difficulty levels
        if course.difficulty:
            profile['difficulty_levels'][course.difficulty] = profile['difficulty_levels'].get(course.difficulty, 0) + 1
    
    # Calculate average test score
    attempts = TestAttempt.query.filter_by(user_id=user_id).filter(
        TestAttempt.completed_at.isnot(None)
    ).all()
    
    if attempts:
        total_percentage = sum(att.percentage for att in attempts if att.percentage)
        profile['avg_test_score'] = total_percentage / len(attempts)
    
    return profile

def _calculate_recommendations(user_profile, available_courses, user_id):
    """Calculate course recommendations using collaborative and content-based filtering."""
    recommendations = []
    
    for course in available_courses:
        score = 0.0
        reasons = []
        
        # Content-based filtering: Category matching
        if course.category in user_profile['categories']:
            category_score = user_profile['categories'][course.category] / max(user_profile['total_courses_completed'], 1)
            score += category_score * 40  # 40% weight for category match
            reasons.append(f"Matches your interest in {course.category}")
        
        # Difficulty progression
        if course.difficulty:
            # Recommend next difficulty level based on performance
            if user_profile['avg_test_score'] >= 80:
                if course.difficulty == 'Advanced':
                    score += 30
                    reasons.append("Ready for advanced topics")
                elif course.difficulty == 'Intermediate':
                    score += 15
            elif user_profile['avg_test_score'] >= 60:
                if course.difficulty == 'Intermediate':
                    score += 30
                    reasons.append("Perfect next step in your learning journey")
                elif course.difficulty == 'Beginner':
                    score += 10
            else:
                if course.difficulty == 'Beginner':
                    score += 30
                    reasons.append("Great starting point")
        
        # Collaborative filtering: Popularity among similar users
        popularity_score = _calculate_popularity_score(course, user_profile)
        score += popularity_score * 20  # 20% weight for popularity
        
        if popularity_score > 0.5:
            reasons.append("Popular among learners like you")
        
        # Fresh content bonus
        from datetime import datetime, timedelta
        if course.created_at > datetime.utcnow() - timedelta(days=30):
            score += 10
            reasons.append("New course")
        
        recommendations.append({
            'course_id': course.id,
            'title': course.title,
            'description': course.description,
            'category': course.category,
            'difficulty': course.difficulty,
            'duration_hours': course.duration_hours,
            'recommendation_score': round(score, 2),
            'reasons': reasons
        })
    
    # Sort by recommendation score
    recommendations.sort(key=lambda x: x['recommendation_score'], reverse=True)
    
    return recommendations

def _calculate_popularity_score(course, user_profile):
    """Calculate popularity score based on similar users."""
    # Simple popularity metric: number of enrolled students
    enrolled_count = course.enrolled_users.count()
    
    if enrolled_count == 0:
        return 0.0
    
    # Normalize by total courses (simple approach)
    # In production, this would use more sophisticated collaborative filtering
    max_enrollment = 100  # Assume max 100 for normalization
    return min(enrolled_count / max_enrollment, 1.0)

@recommendations_bp.route('/similar/<int:course_id>', methods=['GET'])
@login_required
def get_similar_courses(course_id):
    """Get courses similar to a specific course."""
    target_course = Course.query.get_or_404(course_id)
    
    # Find similar courses based on category and difficulty
    similar_courses = Course.query.filter(
        Course.id != course_id,
        Course.category == target_course.category
    ).limit(5).all()
    
    return jsonify({
        'similar_courses': [{
            'id': course.id,
            'title': course.title,
            'description': course.description,
            'category': course.category,
            'difficulty': course.difficulty,
            'similarity_reason': 'Same category'
        } for course in similar_courses]
    }), 200

@recommendations_bp.route('/trending', methods=['GET'])
def get_trending_courses():
    """Get trending courses (most enrolled recently)."""
    from datetime import datetime, timedelta
    
    # Get courses with most recent enrollments
    # This is a simplified version; in production, you'd use a more complex query
    popular_courses = Course.query.join(Course.enrolled_users).group_by(Course.id).order_by(
        func.count().desc()
    ).limit(10).all()
    
    return jsonify({
        'trending_courses': [{
            'id': course.id,
            'title': course.title,
            'description': course.description,
            'category': course.category,
            'difficulty': course.difficulty,
            'enrolled_count': course.enrolled_users.count()
        } for course in popular_courses]
    }), 200
