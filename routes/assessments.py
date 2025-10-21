from flask import Blueprint, request, jsonify
from flask_login import login_required, current_user
from models import db, Assessment, Question, QuestionOption, TestAttempt, UserAnswer
from datetime import datetime

assessments_bp = Blueprint('assessments', __name__)

@assessments_bp.route('/', methods=['POST'])
@login_required
def create_assessment():
    """Create a new assessment (test or quiz)."""
    data = request.get_json()
    
    if not data or not data.get('course_id') or not data.get('title'):
        return jsonify({'error': 'Course ID and title are required'}), 400
    
    assessment = Assessment(
        course_id=data['course_id'],
        title=data['title'],
        type=data.get('type', 'quiz'),
        passing_score=data.get('passing_score', 70),
        time_limit_minutes=data.get('time_limit_minutes')
    )
    
    db.session.add(assessment)
    db.session.commit()
    
    return jsonify({
        'message': 'Assessment created successfully',
        'assessment': {
            'id': assessment.id,
            'title': assessment.title,
            'type': assessment.type
        }
    }), 201

@assessments_bp.route('/<int:assessment_id>', methods=['GET'])
@login_required
def get_assessment(assessment_id):
    """Get assessment details."""
    assessment = Assessment.query.get_or_404(assessment_id)
    
    questions = [{
        'id': q.id,
        'question_text': q.question_text,
        'question_type': q.question_type,
        'points': q.points,
        'options': [{
            'id': opt.id,
            'option_text': opt.option_text
        } for opt in q.options.order_by(QuestionOption.order)]
    } for q in assessment.questions.order_by(Question.order)]
    
    return jsonify({
        'id': assessment.id,
        'title': assessment.title,
        'type': assessment.type,
        'passing_score': assessment.passing_score,
        'time_limit_minutes': assessment.time_limit_minutes,
        'questions': questions
    }), 200

@assessments_bp.route('/<int:assessment_id>/questions', methods=['POST'])
@login_required
def add_question(assessment_id):
    """Add a question to an assessment."""
    assessment = Assessment.query.get_or_404(assessment_id)
    data = request.get_json()
    
    if not data or not data.get('question_text'):
        return jsonify({'error': 'Question text is required'}), 400
    
    question = Question(
        assessment_id=assessment_id,
        question_text=data['question_text'],
        question_type=data.get('question_type', 'multiple_choice'),
        points=data.get('points', 1),
        order=data.get('order', assessment.questions.count() + 1)
    )
    
    db.session.add(question)
    db.session.flush()  # Get question.id
    
    # Add options if provided
    if data.get('options'):
        for idx, option_data in enumerate(data['options']):
            option = QuestionOption(
                question_id=question.id,
                option_text=option_data['text'],
                is_correct=option_data.get('is_correct', False),
                order=idx + 1
            )
            db.session.add(option)
    
    db.session.commit()
    
    return jsonify({
        'message': 'Question added successfully',
        'question': {
            'id': question.id,
            'question_text': question.question_text
        }
    }), 201

@assessments_bp.route('/<int:assessment_id>/start', methods=['POST'])
@login_required
def start_assessment(assessment_id):
    """Start a new assessment attempt."""
    assessment = Assessment.query.get_or_404(assessment_id)
    
    # Create new attempt
    attempt = TestAttempt(
        user_id=current_user.id,
        assessment_id=assessment_id,
        started_at=datetime.utcnow()
    )
    
    db.session.add(attempt)
    db.session.commit()
    
    return jsonify({
        'message': 'Assessment started',
        'attempt': {
            'id': attempt.id,
            'started_at': attempt.started_at.isoformat()
        }
    }), 201

@assessments_bp.route('/attempts/<int:attempt_id>/submit', methods=['POST'])
@login_required
def submit_assessment(attempt_id):
    """Submit answers for an assessment attempt."""
    attempt = TestAttempt.query.get_or_404(attempt_id)
    
    # Verify ownership
    if attempt.user_id != current_user.id:
        return jsonify({'error': 'Unauthorized'}), 403
    
    data = request.get_json()
    answers = data.get('answers', [])
    
    total_score = 0
    max_score = 0
    
    # Process each answer
    for answer_data in answers:
        question = Question.query.get(answer_data['question_id'])
        if not question:
            continue
        
        max_score += question.points
        
        user_answer = UserAnswer(
            attempt_id=attempt_id,
            question_id=question.id,
            selected_option_id=answer_data.get('selected_option_id'),
            answer_text=answer_data.get('answer_text', '')
        )
        
        # Check if answer is correct
        if answer_data.get('selected_option_id'):
            option = QuestionOption.query.get(answer_data['selected_option_id'])
            if option and option.is_correct:
                user_answer.is_correct = True
                user_answer.points_earned = question.points
                total_score += question.points
            else:
                user_answer.is_correct = False
        
        db.session.add(user_answer)
    
    # Update attempt
    attempt.score = total_score
    attempt.max_score = max_score
    attempt.percentage = (total_score / max_score * 100) if max_score > 0 else 0
    attempt.passed = attempt.percentage >= attempt.assessment.passing_score
    attempt.completed_at = datetime.utcnow()
    
    db.session.commit()
    
    return jsonify({
        'message': 'Assessment submitted successfully',
        'result': {
            'score': total_score,
            'max_score': max_score,
            'percentage': round(attempt.percentage, 2),
            'passed': attempt.passed
        }
    }), 200

@assessments_bp.route('/attempts/<int:attempt_id>', methods=['GET'])
@login_required
def get_attempt_results(attempt_id):
    """Get results of an assessment attempt."""
    attempt = TestAttempt.query.get_or_404(attempt_id)
    
    # Verify ownership
    if attempt.user_id != current_user.id:
        return jsonify({'error': 'Unauthorized'}), 403
    
    answers = [{
        'question_id': ans.question_id,
        'question_text': ans.question.question_text,
        'selected_option_id': ans.selected_option_id,
        'is_correct': ans.is_correct,
        'points_earned': ans.points_earned
    } for ans in attempt.answers]
    
    return jsonify({
        'attempt_id': attempt.id,
        'assessment_title': attempt.assessment.title,
        'score': attempt.score,
        'max_score': attempt.max_score,
        'percentage': round(attempt.percentage, 2),
        'passed': attempt.passed,
        'started_at': attempt.started_at.isoformat(),
        'completed_at': attempt.completed_at.isoformat() if attempt.completed_at else None,
        'answers': answers
    }), 200

@assessments_bp.route('/my-attempts', methods=['GET'])
@login_required
def get_my_attempts():
    """Get all assessment attempts by the current user."""
    attempts = TestAttempt.query.filter_by(user_id=current_user.id).all()
    
    return jsonify({
        'attempts': [{
            'id': att.id,
            'assessment_id': att.assessment_id,
            'assessment_title': att.assessment.title,
            'score': att.score,
            'max_score': att.max_score,
            'percentage': round(att.percentage, 2) if att.percentage else 0,
            'passed': att.passed,
            'completed_at': att.completed_at.isoformat() if att.completed_at else None
        } for att in attempts]
    }), 200
