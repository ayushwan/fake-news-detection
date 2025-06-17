"""
Database Models for Fake News Detection System
"""
from flask_sqlalchemy import SQLAlchemy
from datetime import datetime
from werkzeug.security import generate_password_hash, check_password_hash
import enum

db = SQLAlchemy()

class UserRole(enum.Enum):
    USER = "user"
    ADMIN = "admin"

class UserStatus(enum.Enum):
    ACTIVE = "active"
    SUSPENDED = "suspended"
    BANNED = "banned"

class SubmissionType(enum.Enum):
    TEXT = "text"
    URL = "url"
    IMAGE = "image"

class PredictionResult(enum.Enum):
    FAKE = "FAKE"
    REAL = "REAL"
    UNCERTAIN = "UNCERTAIN"

class FlagReason(enum.Enum):
    INAPPROPRIATE = "inappropriate"
    SPAM = "spam"
    INCORRECT_PREDICTION = "incorrect_prediction"
    OFFENSIVE = "offensive"
    OTHER = "other"

class FlagStatus(enum.Enum):
    PENDING = "pending"
    REVIEWED = "reviewed"
    RESOLVED = "resolved"
    DISMISSED = "dismissed"

class User(db.Model):
    __tablename__ = 'users'
    
    id = db.Column(db.Integer, primary_key=True)
    name = db.Column(db.String(100), nullable=False)
    email = db.Column(db.String(255), unique=True, nullable=False, index=True)
    password_hash = db.Column(db.String(255), nullable=False)
    role = db.Column(db.Enum(UserRole), default=UserRole.USER, nullable=False)
    status = db.Column(db.Enum(UserStatus), default=UserStatus.ACTIVE, nullable=False)
    email_verified = db.Column(db.Boolean, default=False)
    profile_image = db.Column(db.String(255))
    created_at = db.Column(db.DateTime, default=datetime.utcnow, nullable=False)
    updated_at = db.Column(db.DateTime, default=datetime.utcnow, onupdate=datetime.utcnow)
    last_login = db.Column(db.DateTime)
    
    # Relationships
    submissions = db.relationship('Submission', backref='user', lazy=True, cascade='all, delete-orphan')
    flags = db.relationship('Flag', backref='flagger', lazy=True, foreign_keys='Flag.user_id')
    comments = db.relationship('Comment', backref='commenter', lazy=True, cascade='all, delete-orphan')
    
    def set_password(self, password):
        """Set password hash"""
        self.password_hash = generate_password_hash(password)
    
    def check_password(self, password):
        """Check password against hash"""
        return check_password_hash(self.password_hash, password)
    
    def is_admin(self):
        """Check if user is admin"""
        return self.role == UserRole.ADMIN
    
    def is_active(self):
        """Check if user account is active"""
        return self.status == UserStatus.ACTIVE
    
    def to_dict(self):
        """Convert to dictionary"""
        return {
            'id': self.id,
            'name': self.name,
            'email': self.email,
            'role': self.role.value,
            'status': self.status.value,
            'email_verified': self.email_verified,
            'created_at': self.created_at.isoformat() if self.created_at else None,
            'last_login': self.last_login.isoformat() if self.last_login else None
        }

class Submission(db.Model):
    __tablename__ = 'submissions'
    
    id = db.Column(db.Integer, primary_key=True)
    user_id = db.Column(db.Integer, db.ForeignKey('users.id'), nullable=False, index=True)
    submission_type = db.Column(db.Enum(SubmissionType), nullable=False)
    content = db.Column(db.Text, nullable=False)
    original_url = db.Column(db.String(1000))
    image_path = db.Column(db.String(500))
    prediction = db.Column(db.Enum(PredictionResult), nullable=False)
    confidence = db.Column(db.Float, nullable=False)
    processing_time = db.Column(db.Float)
    model_version = db.Column(db.String(50), default='v1.0')
    ip_address = db.Column(db.String(45))
    user_agent = db.Column(db.Text)
    submitted_at = db.Column(db.DateTime, default=datetime.utcnow, nullable=False, index=True)
    updated_at = db.Column(db.DateTime, default=datetime.utcnow, onupdate=datetime.utcnow)
    
    # Relationships
    flags = db.relationship('Flag', backref='submission', lazy=True, cascade='all, delete-orphan')
    comments = db.relationship('Comment', backref='submission', lazy=True, cascade='all, delete-orphan')
    
    def to_dict(self):
        """Convert to dictionary"""
        return {
            'id': self.id,
            'user_id': self.user_id,
            'user_name': getattr(self.user, 'name', None) if hasattr(self, 'user') and self.user else None,
            'submission_type': self.submission_type.value,
            'content': self.content[:500] + '...' if len(self.content) > 500 else self.content,
            'original_url': self.original_url,
            'prediction': self.prediction.value,
            'confidence': round(self.confidence, 4),
            'processing_time': self.processing_time,
            'model_version': self.model_version,
            'submitted_at': self.submitted_at.isoformat() if self.submitted_at else None
        }

class Flag(db.Model):
    __tablename__ = 'flags'
    
    id = db.Column(db.Integer, primary_key=True)
    user_id = db.Column(db.Integer, db.ForeignKey('users.id'), nullable=False)
    submission_id = db.Column(db.Integer, db.ForeignKey('submissions.id'), nullable=False)
    reason = db.Column(db.Enum(FlagReason), nullable=False)
    description = db.Column(db.Text)
    status = db.Column(db.Enum(FlagStatus), default=FlagStatus.PENDING, nullable=False)
    reviewed_by = db.Column(db.Integer, db.ForeignKey('users.id'))
    reviewed_at = db.Column(db.DateTime)
    created_at = db.Column(db.DateTime, default=datetime.utcnow, nullable=False, index=True)
    
    # Relationships
    reviewer = db.relationship('User', foreign_keys=[reviewed_by], backref='reviewed_flags')
    
    def to_dict(self):
        """Convert to dictionary"""
        return {
            'id': self.id,
            'user_id': self.user_id,
            'submission_id': self.submission_id,
            'reason': self.reason.value,
            'description': self.description,
            'status': self.status.value,
            'reviewed_by': self.reviewed_by,
            'created_at': self.created_at.isoformat() if self.created_at else None,
            'reviewed_at': self.reviewed_at.isoformat() if self.reviewed_at else None
        }

class Comment(db.Model):
    __tablename__ = 'comments'
    
    id = db.Column(db.Integer, primary_key=True)
    user_id = db.Column(db.Integer, db.ForeignKey('users.id'), nullable=False)
    submission_id = db.Column(db.Integer, db.ForeignKey('submissions.id'), nullable=False)
    comment = db.Column(db.Text, nullable=False)
    rating = db.Column(db.Integer)  # 1-5 star rating
    helpful_votes = db.Column(db.Integer, default=0)
    status = db.Column(db.String(20), default='active')  # active, hidden, deleted
    created_at = db.Column(db.DateTime, default=datetime.utcnow, nullable=False)
    updated_at = db.Column(db.DateTime, default=datetime.utcnow, onupdate=datetime.utcnow)
    
    def to_dict(self):
        """Convert to dictionary"""
        return {
            'id': self.id,
            'user_id': self.user_id,
            'user_name': getattr(self.commenter, 'name', None) if hasattr(self, 'commenter') and self.commenter else None,
            'submission_id': self.submission_id,
            'comment': self.comment,
            'rating': self.rating,
            'helpful_votes': self.helpful_votes,
            'status': self.status,
            'created_at': self.created_at.isoformat() if self.created_at else None
        }

class KeywordTrend(db.Model):
    __tablename__ = 'keyword_trends'
    
    id = db.Column(db.Integer, primary_key=True)
    keyword = db.Column(db.String(255), nullable=False, index=True)
    category = db.Column(db.String(10), nullable=False)  # 'fake' or 'real'
    frequency = db.Column(db.Integer, default=1)
    first_seen = db.Column(db.DateTime, default=datetime.utcnow)
    last_seen = db.Column(db.DateTime, default=datetime.utcnow, onupdate=datetime.utcnow)
    
    __table_args__ = (
        db.Index('idx_keyword_category', 'keyword', 'category'),
    )
    
    def to_dict(self):
        """Convert to dictionary"""
        return {
            'id': self.id,
            'keyword': self.keyword,
            'category': self.category,
            'frequency': self.frequency,
            'first_seen': self.first_seen.isoformat() if self.first_seen else None,
            'last_seen': self.last_seen.isoformat() if self.last_seen else None
        }

class SystemStat(db.Model):
    __tablename__ = 'system_stats'
    
    id = db.Column(db.Integer, primary_key=True)
    stat_key = db.Column(db.String(100), unique=True, nullable=False)
    stat_value = db.Column(db.JSON, nullable=False)
    updated_at = db.Column(db.DateTime, default=datetime.utcnow, onupdate=datetime.utcnow)
    
    def to_dict(self):
        """Convert to dictionary"""
        return {
            'id': self.id,
            'stat_key': self.stat_key,
            'stat_value': self.stat_value,
            'updated_at': self.updated_at.isoformat() if self.updated_at else None
        }

class APILog(db.Model):
    __tablename__ = 'api_logs'
    
    id = db.Column(db.Integer, primary_key=True)
    user_id = db.Column(db.Integer, db.ForeignKey('users.id'))
    endpoint = db.Column(db.String(255), nullable=False)
    method = db.Column(db.String(10), nullable=False)
    status_code = db.Column(db.Integer, nullable=False)
    processing_time = db.Column(db.Float, nullable=False)
    ip_address = db.Column(db.String(45), nullable=False)
    user_agent = db.Column(db.Text)
    request_data = db.Column(db.JSON)
    response_data = db.Column(db.JSON)
    created_at = db.Column(db.DateTime, default=datetime.utcnow, nullable=False, index=True)
    
    def to_dict(self):
        """Convert to dictionary"""
        return {
            'id': self.id,
            'user_id': self.user_id,
            'endpoint': self.endpoint,
            'method': self.method,
            'status_code': self.status_code,
            'processing_time': self.processing_time,
            'ip_address': self.ip_address,
            'created_at': self.created_at.isoformat() if self.created_at else None
        }