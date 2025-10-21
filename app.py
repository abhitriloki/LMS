from flask import Flask
from flask_login import LoginManager
from config import Config
from models import db, User

def create_app(config_class=Config):
    """Application factory pattern."""
    app = Flask(__name__)
    app.config.from_object(config_class)
    
    # Initialize extensions
    db.init_app(app)
    
    # Setup Flask-Login
    login_manager = LoginManager()
    login_manager.init_app(app)
    login_manager.login_view = 'auth.login'
    
    @login_manager.user_loader
    def load_user(user_id):
        return User.query.get(int(user_id))
    
    # Register blueprints
    from routes.auth import auth_bp
    from routes.courses import courses_bp
    from routes.assessments import assessments_bp
    from routes.progress import progress_bp
    from routes.certificates import certificates_bp
    from routes.recommendations import recommendations_bp
    
    app.register_blueprint(auth_bp, url_prefix='/auth')
    app.register_blueprint(courses_bp, url_prefix='/courses')
    app.register_blueprint(assessments_bp, url_prefix='/assessments')
    app.register_blueprint(progress_bp, url_prefix='/progress')
    app.register_blueprint(certificates_bp, url_prefix='/certificates')
    app.register_blueprint(recommendations_bp, url_prefix='/recommendations')
    
    # Create database tables
    with app.app_context():
        db.create_all()
        
        # Create directories if they don't exist
        import os
        os.makedirs(app.config['UPLOAD_FOLDER'], exist_ok=True)
        os.makedirs(app.config['CERTIFICATES_FOLDER'], exist_ok=True)
    
    # Index route
    @app.route('/')
    def index():
        from flask import render_template
        return render_template('index.html')
    
    @app.route('/api')
    def api_info():
        return {
            'message': 'Welcome to the Learning Management System (LMS) API',
            'features': [
                'Online courses',
                'Tests and quizzes',
                'Certificates',
                'Learning progress tracking',
                'AI-powered course recommendations'
            ],
            'endpoints': {
                'auth': '/auth/...',
                'courses': '/courses/...',
                'assessments': '/assessments/...',
                'progress': '/progress/...',
                'certificates': '/certificates/...',
                'recommendations': '/recommendations/...'
            }
        }
    
    return app

if __name__ == '__main__':
    app = create_app()
    app.run(debug=True)
