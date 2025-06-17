from flask import Flask
from flask_sqlalchemy import SQLAlchemy
from flask_login import LoginManager
from flask_migrate import Migrate
import os
from dotenv import load_dotenv

load_dotenv()

db = SQLAlchemy()
login_manager = LoginManager()

def create_app():
    app = Flask(__name__)
    app.config['SECRET_KEY'] = os.environ.get('SECRET_KEY', 'your_default_secret_key')
    app.config['SQLALCHEMY_DATABASE_URI'] = os.environ.get('DATABASE_URL', 'mysql+mysqlconnector://user:password@host/db_name')
    app.config['SQLALCHEMY_TRACK_MODIFICATIONS'] = False

    db.init_app(app)
    login_manager.init_app(app)
    login_manager.login_view = 'auth.login' # Redirect to login page if user is not authenticated
    login_manager.login_message_category = 'info' # Flash message category

    migrate = Migrate(app, db)

    from .models import User # Import models here to avoid circular imports

    @login_manager.user_loader
    def load_user(user_id):
        return User.query.get(int(user_id))

    # Register Blueprints
    from .routes import main_bp
    app.register_blueprint(main_bp)

    from .auth import auth_bp
    app.register_blueprint(auth_bp, url_prefix='/auth')

    from .admin import admin_bp
    app.register_blueprint(admin_bp, url_prefix='/admin')

    # Create database tables if they don't exist
    with app.app_context():
        db.create_all()
        # You might want to move db.create_all() to a migration script for production

    return app