from flask import Blueprint, request, jsonify, send_file
from flask_login import login_required, current_user
from models import db, Certificate, Course, Progress
from datetime import datetime
import os
import uuid
from io import BytesIO
from reportlab.lib.pagesizes import letter, A4
from reportlab.pdfgen import canvas
from reportlab.lib.units import inch

certificates_bp = Blueprint('certificates', __name__)

@certificates_bp.route('/generate/<int:course_id>', methods=['POST'])
@login_required
def generate_certificate(course_id):
    """Generate a certificate for course completion."""
    course = Course.query.get_or_404(course_id)
    
    # Check if user is enrolled
    if course not in current_user.enrolled_courses:
        return jsonify({'error': 'Not enrolled in this course'}), 403
    
    # Check if course is completed
    course_progress = Progress.query.filter_by(
        user_id=current_user.id,
        course_id=course_id,
        lesson_id=None
    ).first()
    
    if not course_progress or not course_progress.completed:
        return jsonify({'error': 'Course not completed yet'}), 400
    
    # Check if certificate already exists
    existing_cert = Certificate.query.filter_by(
        user_id=current_user.id,
        course_id=course_id
    ).first()
    
    if existing_cert:
        return jsonify({
            'message': 'Certificate already exists',
            'certificate': {
                'id': existing_cert.id,
                'certificate_code': existing_cert.certificate_code,
                'issued_at': existing_cert.issued_at.isoformat()
            }
        }), 200
    
    # Generate unique certificate code
    certificate_code = f"LMS-{uuid.uuid4().hex[:12].upper()}"
    
    # Create certificate record
    certificate = Certificate(
        user_id=current_user.id,
        course_id=course_id,
        certificate_code=certificate_code,
        issued_at=datetime.utcnow()
    )
    
    db.session.add(certificate)
    db.session.flush()  # Get certificate.id
    
    # Generate PDF certificate
    from flask import current_app
    cert_folder = current_app.config['CERTIFICATES_FOLDER']
    os.makedirs(cert_folder, exist_ok=True)
    
    filename = f"certificate_{certificate.id}_{certificate_code}.pdf"
    filepath = os.path.join(cert_folder, filename)
    
    _create_certificate_pdf(
        filepath,
        current_user.first_name + ' ' + current_user.last_name,
        course.title,
        certificate_code,
        certificate.issued_at
    )
    
    certificate.file_path = filepath
    db.session.commit()
    
    return jsonify({
        'message': 'Certificate generated successfully',
        'certificate': {
            'id': certificate.id,
            'certificate_code': certificate_code,
            'issued_at': certificate.issued_at.isoformat(),
            'download_url': f'/certificates/{certificate.id}/download'
        }
    }), 201

@certificates_bp.route('/<int:certificate_id>/download', methods=['GET'])
@login_required
def download_certificate(certificate_id):
    """Download a certificate PDF."""
    certificate = Certificate.query.get_or_404(certificate_id)
    
    # Verify ownership
    if certificate.user_id != current_user.id:
        return jsonify({'error': 'Unauthorized'}), 403
    
    if not certificate.file_path or not os.path.exists(certificate.file_path):
        return jsonify({'error': 'Certificate file not found'}), 404
    
    return send_file(
        certificate.file_path,
        as_attachment=True,
        download_name=f"certificate_{certificate.certificate_code}.pdf"
    )

@certificates_bp.route('/my-certificates', methods=['GET'])
@login_required
def get_my_certificates():
    """Get all certificates earned by the current user."""
    certificates = Certificate.query.filter_by(user_id=current_user.id).all()
    
    return jsonify({
        'certificates': [{
            'id': cert.id,
            'certificate_code': cert.certificate_code,
            'course_id': cert.course_id,
            'course_title': cert.course.title,
            'issued_at': cert.issued_at.isoformat(),
            'download_url': f'/certificates/{cert.id}/download'
        } for cert in certificates]
    }), 200

@certificates_bp.route('/verify/<certificate_code>', methods=['GET'])
def verify_certificate(certificate_code):
    """Verify a certificate by its code (public endpoint)."""
    certificate = Certificate.query.filter_by(certificate_code=certificate_code).first()
    
    if not certificate:
        return jsonify({
            'valid': False,
            'message': 'Certificate not found'
        }), 404
    
    return jsonify({
        'valid': True,
        'certificate_code': certificate.certificate_code,
        'user_name': certificate.user.first_name + ' ' + certificate.user.last_name,
        'course_title': certificate.course.title,
        'issued_at': certificate.issued_at.isoformat()
    }), 200

def _create_certificate_pdf(filepath, user_name, course_title, cert_code, issued_date):
    """Create a PDF certificate."""
    c = canvas.Canvas(filepath, pagesize=letter)
    width, height = letter
    
    # Draw border
    c.setLineWidth(2)
    c.rect(0.5*inch, 0.5*inch, width - 1*inch, height - 1*inch)
    
    # Title
    c.setFont("Helvetica-Bold", 36)
    c.drawCentredString(width/2, height - 2*inch, "CERTIFICATE OF COMPLETION")
    
    # Decorative line
    c.setLineWidth(1)
    c.line(2*inch, height - 2.5*inch, width - 2*inch, height - 2.5*inch)
    
    # Body text
    c.setFont("Helvetica", 16)
    c.drawCentredString(width/2, height - 3.5*inch, "This is to certify that")
    
    # User name
    c.setFont("Helvetica-Bold", 24)
    c.drawCentredString(width/2, height - 4.2*inch, user_name)
    
    # Course info
    c.setFont("Helvetica", 16)
    c.drawCentredString(width/2, height - 5*inch, "has successfully completed the course")
    
    # Course title
    c.setFont("Helvetica-Bold", 20)
    c.drawCentredString(width/2, height - 5.7*inch, course_title)
    
    # Date
    c.setFont("Helvetica", 14)
    date_str = issued_date.strftime("%B %d, %Y")
    c.drawCentredString(width/2, height - 7*inch, f"Issued on: {date_str}")
    
    # Certificate code
    c.setFont("Helvetica", 10)
    c.drawCentredString(width/2, 1*inch, f"Certificate Code: {cert_code}")
    
    # Signature line (placeholder)
    c.setFont("Helvetica-Oblique", 12)
    c.line(1.5*inch, 2*inch, 3.5*inch, 2*inch)
    c.drawCentredString(2.5*inch, 1.7*inch, "Authorized Signature")
    
    c.save()
