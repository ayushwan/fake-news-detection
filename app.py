#!/usr/bin/env python3
"""
Flask ML API for Fake News Detection
AI-Powered Fake News Detection System
"""

from flask import Flask, request, jsonify, session, render_template, send_from_directory
from flask_cors import CORS
from flask_sqlalchemy import SQLAlchemy
import pickle
import re
import time
import logging
from datetime import datetime
import numpy as np
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.linear_model import LogisticRegression
from sklearn.pipeline import Pipeline
from sklearn.model_selection import train_test_split
import nltk
from nltk.corpus import stopwords
from nltk.tokenize import word_tokenize
import os

# Download required NLTK data
try:
    nltk.data.find('tokenizers/punkt')
except LookupError:
    nltk.download('punkt')

try:
    nltk.data.find('tokenizers/punkt_tab')
except LookupError:
    nltk.download('punkt_tab')

try:
    nltk.data.find('corpora/stopwords')
except LookupError:
    nltk.download('stopwords')

# Initialize Flask app
app = Flask(__name__)
CORS(app)  # Enable CORS for PHP frontend

# Configure app
app.config['SECRET_KEY'] = os.environ.get('SESSION_SECRET', 'dev-secret-key')
database_url = os.environ.get('DATABASE_URL')
if database_url and database_url.startswith('postgres://'):
    database_url = database_url.replace('postgres://', 'postgresql://', 1)
app.config['SQLALCHEMY_DATABASE_URI'] = database_url
app.config['SQLALCHEMY_TRACK_MODIFICATIONS'] = False

# Initialize database
from models import db, User, Submission, Flag, Comment, KeywordTrend, SystemStat, APILog
from models import UserRole, UserStatus, SubmissionType, PredictionResult, FlagReason, FlagStatus

db.init_app(app)

# Configure logging
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

# Global variables
model = None
vectorizer = None
model_info = {
    'name': 'Fake News Detector',
    'version': '1.0.0',
    'accuracy': 0.0,
    'trained_at': None,
    'features_count': 0
}

class FakeNewsDetector:
    def __init__(self):
        self.model = None
        self.stop_words = set(stopwords.words('english'))
        
    def preprocess_text(self, text):
        """Clean and preprocess text for analysis"""
        if not isinstance(text, str):
            return ""
        
        # Convert to lowercase
        text = text.lower()
        
        # Remove URLs
        text = re.sub(r'http[s]?://(?:[a-zA-Z]|[0-9]|[$-_@.&+]|[!*\\(\\),]|(?:%[0-9a-fA-F][0-9a-fA-F]))+', '', text)
        
        # Remove HTML tags
        text = re.sub(r'<.*?>', '', text)
        
        # Remove special characters but keep basic punctuation
        text = re.sub(r'[^a-zA-Z0-9\s\.\,\!\?\;\:]', '', text)
        
        # Remove extra whitespace
        text = re.sub(r'\s+', ' ', text)
        
        # Tokenize and remove stopwords
        tokens = word_tokenize(text)
        tokens = [word for word in tokens if word not in self.stop_words and len(word) > 2]
        
        return ' '.join(tokens)
    
    def create_training_data(self):
        """Create synthetic training data for fake news detection"""
        
        # Real news patterns - factual, structured, citing sources
        real_news_samples = [
            "The Federal Reserve announced today that interest rates will remain unchanged following their two-day policy meeting. The decision was made unanimously by the Federal Open Market Committee after careful consideration of current economic indicators.",
            "Scientists at Stanford University published a new study in the journal Nature showing promising results in cancer treatment research. The peer-reviewed study involved 500 patients over a period of two years.",
            "The Department of Labor reported that unemployment rates decreased by 0.2% last month, reaching the lowest level since 2019. The report cited increased hiring in the technology and healthcare sectors.",
            "Local authorities confirmed that the new infrastructure project will be completed ahead of schedule. The $50 million investment will improve transportation access for over 100,000 residents in the metropolitan area.",
            "According to data released by the World Health Organization, vaccination rates in developing countries have increased by 15% compared to last year. The organization credits improved distribution networks and international cooperation.",
            "The Environmental Protection Agency issued new guidelines for air quality standards based on recent scientific research. The updated regulations will take effect next year and are expected to reduce emissions by 20%.",
            "University researchers collaborated with industry partners to develop new renewable energy technology. The innovation was presented at the International Conference on Sustainable Energy and has received peer review.",
            "The Supreme Court heard arguments today in a case involving digital privacy rights. Legal experts from both sides presented evidence and constitutional interpretations to the nine justices.",
            "Government officials announced the successful completion of infrastructure repairs following last month's natural disaster. The $25 million project restored power and water services to affected communities.",
            "Medical professionals at Johns Hopkins Hospital reported successful treatment outcomes using a new surgical technique. The minimally invasive procedure was developed over three years of clinical trials.",
            "The National Weather Service issued seasonal forecasts based on atmospheric data and climate models. Meteorologists predict normal precipitation levels for the upcoming season across most regions.",
            "Economic analysts reported steady growth in the manufacturing sector according to the latest quarterly data. Production indices show a 3% increase compared to the same period last year.",
            "Transportation officials completed safety inspections of public transit systems meeting federal requirements. The comprehensive review ensures compliance with updated safety standards and protocols.",
            "Academic institutions received federal funding to continue climate change research initiatives. The multi-year grants will support graduate students and advanced scientific equipment purchases.",
            "Healthcare organizations published clinical guidelines based on evidence from randomized controlled trials. The recommendations were developed by panels of medical experts and underwent rigorous peer review.",
            "Financial institutions reported quarterly earnings that aligned with analyst predictions for the banking sector. The results reflect steady performance in lending and investment services during challenging market conditions.",
            "Technology companies announced software updates that address security vulnerabilities identified by independent researchers. The patches will be automatically distributed to users over the next two weeks.",
            "Education officials released standardized test results showing improvements in mathematics and reading scores. The data represents responses from over 2 million students across public school districts.",
            "Public health departments issued evidence-based recommendations for seasonal health preparations. The guidelines are based on surveillance data and consultation with infectious disease specialists.",
            "Research institutions published findings from longitudinal studies tracking environmental changes over the past decade. The peer-reviewed analysis provides insights into ecosystem adaptation and conservation strategies."
        ]
        
        # Fake news patterns - sensational, unverified, conspiracy theories
        fake_news_samples = [
            "BREAKING: Secret government documents leaked revealing shocking conspiracy that mainstream media refuses to report! Anonymous whistleblower exposes terrifying truth about mind control experiments conducted on unsuspecting citizens.",
            "MIRACLE CURE DISCOVERED! Doctors hate this one weird trick that big pharmaceutical companies have been hiding for decades. Natural remedy completely eliminates all diseases without any side effects or expensive treatments.",
            "EXPOSED: Celebrities secretly meeting in underground bunkers to control world government through illuminati connections. Hidden camera footage reveals disturbing plans for population control and manipulation of global events.",
            "SHOCKING REVELATION: Scientists discover ancient alien technology buried beneath famous landmarks. Government covers up evidence of extraterrestrial visitation and advanced civilizations that ruled Earth thousands of years ago.",
            "URGENT WARNING: Dangerous chemicals in everyday products causing mass health crisis that authorities desperately want to keep secret. Millions of people at risk from toxic substances deliberately added to food and water supplies.",
            "INCREDIBLE DISCOVERY: Time traveler from 2050 reveals devastating future events that will change everything. Prophetic warnings about upcoming disasters and political upheavals that world leaders are trying to prevent public from knowing.",
            "BOMBSHELL INVESTIGATION: Banking elite planning complete economic collapse to establish new world order. Secret meetings recorded showing billionaires discussing plans to control global financial systems and eliminate personal freedoms.",
            "AMAZING BREAKTHROUGH: Revolutionary technology suppressed by powerful corporations because it threatens their profits. Free energy device invented decades ago could solve climate crisis but oil companies refuse to allow its release.",
            "DISTURBING EVIDENCE: Weather modification programs creating artificial natural disasters to justify emergency government powers. Classified documents reveal systematic manipulation of climate patterns for political control and social engineering.",
            "EXCLUSIVE REPORT: Underground tunnels connecting major cities used for trafficking operations by global criminal network. Law enforcement agencies compromised and unable to investigate due to high-level corruption and blackmail schemes.",
            "UNBELIEVABLE TRUTH: Vaccines contain microchips designed for mass surveillance and behavior modification. Secret technology allows government tracking and mind control of entire populations through wireless transmission signals.",
            "HIDDEN AGENDA: Educational institutions indoctrinating children with propaganda to create compliant future citizens. Teachers following scripted curriculum designed to eliminate critical thinking and independent thought processes.",
            "LEAKED FOOTAGE: Military testing advanced weapons on civilian populations without consent or knowledge. Classified experiments using directed energy weapons and biological agents conducted in major metropolitan areas.",
            "EMERGENCY ALERT: Food supply deliberately contaminated with substances that cause dependency and reduce intelligence. Agricultural corporations working with government agencies to control population through nutritional manipulation and chemical additives.",
            "SECRET PLAN REVEALED: Technology giants collecting personal data to create detailed psychological profiles for behavioral manipulation. Social media platforms designed as psychological warfare tools to influence elections and public opinion.",
            "TERRIFYING DISCOVERY: Ancient prophecies accurately predicting current world events prove existence of supernatural forces controlling human destiny. Religious texts contain coded messages about upcoming apocalyptic events and spiritual transformation.",
            "COVER-UP EXPOSED: Space agencies hiding evidence of massive planet approaching Earth that will cause global catastrophe. Astronomical data suppressed to prevent mass panic while elite prepare underground survival bunkers.",
            "INSIDER INFORMATION: Pharmaceutical companies deliberately creating diseases to sell expensive treatments and maintain profits. Medical establishment suppressing natural cures to keep people sick and dependent on costly medications.",
            "FORBIDDEN KNOWLEDGE: Historical events completely fabricated by ruling elite to maintain control over educational narratives. Actual human history hidden from public because truth would revolutionize understanding of civilization and consciousness.",
            "SHOCKING CONFESSION: Former government agent reveals existence of parallel dimension where reptilian beings control human affairs through shape-shifting technology. Interdimensional warfare affecting global politics and economic systems."
        ]
        
        # Combine data with labels
        data = []
        labels = []
        
        for text in real_news_samples:
            data.append(text)
            labels.append(0)  # 0 = Real
        
        for text in fake_news_samples:
            data.append(text)
            labels.append(1)  # 1 = Fake
        
        return data, labels
    
    def train_model(self):
        """Train the fake news detection model"""
        logger.info("Starting model training...")
        
        # Create training data
        texts, labels = self.create_training_data()
        
        # Preprocess texts
        processed_texts = [self.preprocess_text(text) for text in texts]
        
        # Split data
        X_train, X_test, y_train, y_test = train_test_split(
            processed_texts, labels, test_size=0.2, random_state=42, stratify=labels
        )
        
        # Create pipeline
        self.model = Pipeline([
            ('tfidf', TfidfVectorizer(
                max_features=5000,
                ngram_range=(1, 3),
                stop_words='english',
                min_df=2,
                max_df=0.8
            )),
            ('classifier', LogisticRegression(
                random_state=42,
                max_iter=1000,
                C=1.0
            ))
        ])
        
        # Train model
        self.model.fit(X_train, y_train)
        
        # Calculate accuracy
        accuracy = self.model.score(X_test, y_test)
        
        # Update model info
        global model_info
        model_info['accuracy'] = round(accuracy * 100, 2)
        model_info['trained_at'] = datetime.now().isoformat()
        model_info['features_count'] = self.model.named_steps['tfidf'].get_feature_names_out().shape[0]
        
        logger.info(f"Model trained successfully with {accuracy:.2%} accuracy")
        return self.model
    
    def predict(self, text):
        """Predict if text is fake or real news"""
        if not self.model:
            raise ValueError("Model not trained yet")
        
        # Preprocess text
        processed_text = self.preprocess_text(text)
        
        if not processed_text or len(processed_text.split()) < 3:
            return "UNCERTAIN", 0.5
        
        try:
            # Make prediction
            prediction = self.model.predict([processed_text])[0]
            probabilities = self.model.predict_proba([processed_text])[0]
            
            # Convert to readable format
            if prediction == 1:
                result = "FAKE"
                confidence = probabilities[1]
            else:
                result = "REAL"
                confidence = probabilities[0]
            
            return result, confidence
            
        except Exception as e:
            logger.error(f"Prediction error: {e}")
            return "UNCERTAIN", 0.5

# Initialize detector
detector = FakeNewsDetector()

def initialize_model():
    """Initialize and train the model when the app starts"""
    global model
    try:
        model = detector.train_model()
        logger.info("ML model initialized successfully")
    except Exception as e:
        logger.error(f"Failed to initialize model: {e}")

@app.route('/', methods=['GET'])
def home():
    """Main web interface"""
    return render_template('index.html')

@app.route('/admin', methods=['GET'])
def admin_dashboard():
    """Admin dashboard interface"""
    return render_template('admin.html')

@app.route('/dashboard', methods=['GET'])
def user_dashboard():
    """User dashboard interface"""
    return render_template('dashboard.html')

@app.route('/api', methods=['GET'])
def api_info():
    """API information endpoint"""
    return jsonify({
        'message': 'AI-Powered Fake News Detection API',
        'version': model_info['version'],
        'status': 'running',
        'endpoints': {
            '/analyze': 'POST - Analyze news content',
            '/batch-analyze': 'POST - Batch analyze multiple items',
            '/health': 'GET - Health check',
            '/info': 'GET - Model information',
            '/ping': 'GET - Simple ping'
        }
    })

@app.route('/ping', methods=['GET'])
def ping():
    """Health check endpoint"""
    return jsonify({
        'status': 'ok',
        'timestamp': time.time(),
        'response_time': 0.001
    })

@app.route('/health', methods=['GET'])
def health():
    """Detailed health check"""
    return jsonify({
        'status': 'healthy' if model else 'unhealthy',
        'model_loaded': model is not None,
        'timestamp': time.time(),
        'version': model_info['version'],
        'uptime': time.time()
    })

@app.route('/info', methods=['GET'])
def info():
    """Get model information"""
    return jsonify(model_info)

@app.route('/analyze', methods=['POST'])
def analyze_news():
    """Analyze news content for fake news detection"""
    start_time = time.time()
    user_id = session.get('user_id')
    
    try:
        # Validate request
        if not request.is_json:
            return jsonify({'error': 'Content-Type must be application/json'}), 400
        
        data = request.get_json()
        news_text = data.get('news', '').strip()
        content_type = data.get('content_type', 'text')
        original_url = data.get('original_url')
        
        if not news_text:
            return jsonify({'error': 'News text is required'}), 400
        
        if len(news_text) < 10:
            return jsonify({'error': 'News text is too short for analysis'}), 400
        
        if len(news_text) > 50000:
            return jsonify({'error': 'News text is too long (max 50,000 characters)'}), 400
        
        # Make prediction
        prediction, confidence = detector.predict(news_text)
        
        processing_time = time.time() - start_time
        
        # Map prediction to enum
        prediction_enum = PredictionResult.FAKE if prediction == 'FAKE' else (
            PredictionResult.REAL if prediction == 'REAL' else PredictionResult.UNCERTAIN
        )
        
        # Save to database if user is logged in
        submission_id = None
        if user_id:
            try:
                submission_type_enum = SubmissionType.TEXT
                if content_type == 'url':
                    submission_type_enum = SubmissionType.URL
                elif content_type == 'image':
                    submission_type_enum = SubmissionType.IMAGE
                
                submission = Submission(
                    user_id=user_id,
                    submission_type=submission_type_enum,
                    content=news_text,
                    original_url=original_url,
                    prediction=prediction_enum,
                    confidence=float(confidence),
                    processing_time=processing_time,
                    model_version=model_info['version'],
                    ip_address=request.remote_addr,
                    user_agent=request.headers.get('User-Agent')
                )
                db.session.add(submission)
                db.session.commit()
                submission_id = submission.id
                
                # Update keyword trends (defined later)
                try:
                    update_keyword_trends(news_text, prediction.lower())
                except Exception as e:
                    logger.error(f"Failed to update trends: {e}")
                
            except Exception as e:
                logger.error(f"Failed to save submission: {e}")
                db.session.rollback()
        
        # Log API call
        try:
            api_log = APILog(
                user_id=user_id,
                endpoint='/analyze',
                method='POST',
                status_code=200,
                processing_time=processing_time,
                ip_address=request.remote_addr,
                user_agent=request.headers.get('User-Agent'),
                request_data={'content_length': len(news_text), 'content_type': content_type},
                response_data={'prediction': prediction, 'confidence': float(confidence)}
            )
            db.session.add(api_log)
            db.session.commit()
        except Exception as e:
            logger.error(f"Failed to log API call: {e}")
        
        # Log prediction
        logger.info(f"Prediction made: {prediction} ({confidence:.3f}) in {processing_time:.3f}s")
        
        # Prepare response
        response = {
            'submission_id': submission_id,
            'prediction': prediction.lower(),
            'confidence': round(float(confidence), 4),
            'processing_time': round(processing_time, 3),
            'model_version': model_info['version'],
            'content_type': content_type,
            'text_length': len(news_text),
            'timestamp': time.time()
        }
        
        # Add features for debugging (optional)
        if request.args.get('include_features') == 'true' and detector.model:
            try:
                # Get top features that influenced the prediction
                tfidf = detector.model.named_steps['tfidf']
                classifier = detector.model.named_steps['classifier']
                
                processed_text = detector.preprocess_text(news_text)
                text_vector = tfidf.transform([processed_text])
                feature_names = tfidf.get_feature_names_out()
                coefficients = classifier.coef_[0]
                
                # Get top 10 features
                feature_scores = text_vector.toarray()[0] * coefficients
                top_indices = np.argsort(np.abs(feature_scores))[-10:]
                
                top_features = []
                for idx in reversed(top_indices):
                    if feature_scores[idx] != 0:
                        top_features.append({
                            'feature': feature_names[idx],
                            'score': round(float(feature_scores[idx]), 4)
                        })
                
                response['features'] = top_features
                
            except Exception as e:
                logger.warning(f"Failed to extract features: {e}")
        
        return jsonify(response)
        
    except Exception as e:
        logger.error(f"Analysis error: {e}")
        return jsonify({
            'error': 'Internal server error during analysis',
            'processing_time': time.time() - start_time
        }), 500

@app.route('/batch-analyze', methods=['POST'])
def batch_analyze():
    """Analyze multiple news items at once"""
    start_time = time.time()
    
    try:
        if not request.is_json:
            return jsonify({'error': 'Content-Type must be application/json'}), 400
        
        data = request.get_json()
        batch_items = data.get('batch', [])
        
        if not batch_items or not isinstance(batch_items, list):
            return jsonify({'error': 'Batch items are required as a list'}), 400
        
        if len(batch_items) > 50:
            return jsonify({'error': 'Maximum 50 items per batch'}), 400
        
        results = []
        
        for i, item in enumerate(batch_items):
            try:
                text = item.get('news', '').strip() if isinstance(item, dict) else str(item).strip()
                
                if len(text) < 10:
                    results.append({
                        'index': i,
                        'prediction': 'uncertain',
                        'confidence': 0.5,
                        'error': 'Text too short'
                    })
                    continue
                
                prediction, confidence = detector.predict(text)
                
                results.append({
                    'index': i,
                    'prediction': prediction.lower(),
                    'confidence': round(float(confidence), 4),
                    'text_length': len(text)
                })
                
            except Exception as e:
                results.append({
                    'index': i,
                    'prediction': 'uncertain',
                    'confidence': 0.5,
                    'error': str(e)
                })
        
        processing_time = time.time() - start_time
        
        return jsonify({
            'results': results,
            'batch_size': len(batch_items),
            'processing_time': round(processing_time, 3),
            'timestamp': time.time()
        })
        
    except Exception as e:
        logger.error(f"Batch analysis error: {e}")
        return jsonify({
            'error': 'Internal server error during batch analysis',
            'processing_time': time.time() - start_time
        }), 500

@app.route('/feedback', methods=['POST'])
def receive_feedback():
    """Receive feedback to improve model (for future retraining)"""
    try:
        if not request.is_json:
            return jsonify({'error': 'Content-Type must be application/json'}), 400
        
        data = request.get_json()
        
        # Log feedback for future model improvement
        feedback_entry = {
            'submission_id': data.get('submission_id'),
            'actual_label': data.get('actual_label'),
            'predicted_label': data.get('predicted_label'),
            'confidence': data.get('confidence'),
            'user_feedback': data.get('user_feedback'),
            'timestamp': time.time()
        }
        
        logger.info(f"Feedback received: {feedback_entry}")
        
        # In a production system, you would store this feedback
        # and use it for periodic model retraining
        
        return jsonify({
            'success': True,
            'message': 'Feedback received successfully'
        })
        
    except Exception as e:
        logger.error(f"Feedback error: {e}")
        return jsonify({'error': 'Failed to process feedback'}), 500

def update_keyword_trends(text, category):
    """Update keyword trends based on analysis"""
    try:
        # Simple keyword extraction (in production, use more sophisticated NLP)
        words = re.findall(r'\b\w{4,}\b', text.lower())
        stop_words = set(['this', 'that', 'with', 'have', 'will', 'been', 'said', 'from', 'they'])
        keywords = [word for word in words if word not in stop_words][:10]  # Top 10
        
        for keyword in keywords:
            trend = KeywordTrend.query.filter_by(keyword=keyword, category=category).first()
            if trend:
                trend.frequency += 1
                trend.last_seen = datetime.utcnow()
            else:
                trend = KeywordTrend(keyword=keyword, category=category)
                db.session.add(trend)
        
        db.session.commit()
    except Exception as e:
        logger.error(f"Failed to update keyword trends: {e}")
        db.session.rollback()

@app.route('/login', methods=['POST'])
def login():
    """User login endpoint"""
    try:
        if not request.is_json:
            return jsonify({'error': 'Content-Type must be application/json'}), 400
        
        data = request.get_json()
        email = data.get('email', '').strip().lower()
        password = data.get('password', '')
        
        if not email or not password:
            return jsonify({'error': 'Email and password are required'}), 400
        
        user = User.query.filter_by(email=email).first()
        
        if not user or not user.check_password(password):
            return jsonify({'error': 'Invalid email or password'}), 401
        
        if not user.is_active():
            return jsonify({'error': 'Account is suspended or banned'}), 403
        
        # Update last login
        user.last_login = datetime.utcnow()
        db.session.commit()
        
        # Set session
        session['user_id'] = user.id
        session['user_role'] = user.role.value
        
        return jsonify({
            'success': True,
            'user': user.to_dict(),
            'redirect': '/admin' if user.is_admin() else '/dashboard'
        })
        
    except Exception as e:
        logger.error(f"Login error: {e}")
        return jsonify({'error': 'Login failed'}), 500

@app.route('/register', methods=['POST'])
def register():
    """User registration endpoint"""
    try:
        if not request.is_json:
            return jsonify({'error': 'Content-Type must be application/json'}), 400
        
        data = request.get_json()
        name = data.get('name', '').strip()
        email = data.get('email', '').strip().lower()
        password = data.get('password', '')
        
        # Validation
        if not name or len(name) < 2:
            return jsonify({'error': 'Name must be at least 2 characters'}), 400
        
        if not email or '@' not in email:
            return jsonify({'error': 'Valid email is required'}), 400
        
        if len(password) < 8:
            return jsonify({'error': 'Password must be at least 8 characters'}), 400
        
        # Check if user exists
        if User.query.filter_by(email=email).first():
            return jsonify({'error': 'User with this email already exists'}), 400
        
        # Create user
        user = User(name=name, email=email)
        user.set_password(password)
        db.session.add(user)
        db.session.commit()
        
        # Set session
        session['user_id'] = user.id
        session['user_role'] = user.role.value
        
        return jsonify({
            'success': True,
            'user': user.to_dict(),
            'message': 'Account created successfully'
        })
        
    except Exception as e:
        logger.error(f"Registration error: {e}")
        db.session.rollback()
        return jsonify({'error': 'Registration failed'}), 500

@app.route('/logout', methods=['POST'])
def logout():
    """User logout endpoint"""
    session.clear()
    return jsonify({'success': True, 'message': 'Logged out successfully'})

@app.route('/user/submissions', methods=['GET'])
def get_user_submissions():
    """Get user's submission history"""
    user_id = session.get('user_id')
    if not user_id:
        return jsonify({'error': 'Authentication required'}), 401
    
    try:
        page = int(request.args.get('page', 1))
        per_page = min(int(request.args.get('per_page', 20)), 100)
        
        submissions = Submission.query.filter_by(user_id=user_id)\
            .order_by(Submission.submitted_at.desc())\
            .paginate(page=page, per_page=per_page, error_out=False)
        
        return jsonify({
            'submissions': [s.to_dict() for s in submissions.items],
            'pagination': {
                'page': page,
                'per_page': per_page,
                'total': submissions.total,
                'pages': submissions.pages,
                'has_next': submissions.has_next,
                'has_prev': submissions.has_prev
            }
        })
        
    except Exception as e:
        logger.error(f"Failed to get user submissions: {e}")
        return jsonify({'error': 'Failed to retrieve submissions'}), 500

@app.route('/admin/stats', methods=['GET'])
def get_admin_stats():
    """Get admin dashboard statistics"""
    user_id = session.get('user_id')
    if not user_id:
        return jsonify({'error': 'Authentication required'}), 401
    
    user = User.query.get(user_id)
    if not user or not user.is_admin():
        return jsonify({'error': 'Admin access required'}), 403
    
    try:
        # Get various statistics
        total_users = User.query.count()
        total_submissions = Submission.query.count()
        fake_submissions = Submission.query.filter_by(prediction=PredictionResult.FAKE).count()
        real_submissions = Submission.query.filter_by(prediction=PredictionResult.REAL).count()
        
        # Recent activity
        recent_submissions = Submission.query.order_by(Submission.submitted_at.desc()).limit(10).all()
        
        # Trending keywords
        trending_fake = KeywordTrend.query.filter_by(category='fake')\
            .order_by(KeywordTrend.frequency.desc()).limit(10).all()
        trending_real = KeywordTrend.query.filter_by(category='real')\
            .order_by(KeywordTrend.frequency.desc()).limit(10).all()
        
        return jsonify({
            'users': {
                'total': total_users,
                'active': User.query.filter_by(status=UserStatus.ACTIVE).count(),
                'suspended': User.query.filter_by(status=UserStatus.SUSPENDED).count(),
                'banned': User.query.filter_by(status=UserStatus.BANNED).count()
            },
            'submissions': {
                'total': total_submissions,
                'fake': fake_submissions,
                'real': real_submissions,
                'fake_percentage': (fake_submissions / total_submissions * 100) if total_submissions > 0 else 0
            },
            'recent_activity': [s.to_dict() for s in recent_submissions],
            'trending_keywords': {
                'fake': [k.to_dict() for k in trending_fake],
                'real': [k.to_dict() for k in trending_real]
            },
            'model_info': model_info
        })
        
    except Exception as e:
        logger.error(f"Failed to get admin stats: {e}")
        return jsonify({'error': 'Failed to retrieve statistics'}), 500

@app.route('/stats', methods=['GET'])
def get_stats():
    """Get API usage statistics"""
    try:
        total_requests = APILog.query.count()
        today_requests = APILog.query.filter(
            APILog.created_at >= datetime.utcnow().replace(hour=0, minute=0, second=0, microsecond=0)
        ).count()
        
        avg_response_time = db.session.query(db.func.avg(APILog.processing_time)).scalar() or 0
        
        return jsonify({
            'requests_today': today_requests,
            'requests_total': total_requests,
            'avg_response_time': round(float(avg_response_time), 3),
            'model_accuracy': model_info['accuracy'],
            'uptime': time.time()
        })
    except Exception as e:
        logger.error(f"Failed to get stats: {e}")
        return jsonify({
            'requests_today': 0,
            'requests_total': 0,
            'avg_response_time': 0.15,
            'model_accuracy': model_info['accuracy'],
            'uptime': time.time()
        })

@app.errorhandler(404)
def not_found(error):
    return jsonify({'error': 'Endpoint not found'}), 404

@app.errorhandler(405)
def method_not_allowed(error):
    return jsonify({'error': 'Method not allowed'}), 405

@app.errorhandler(500)
def internal_error(error):
    return jsonify({'error': 'Internal server error'}), 500

# Initialize database and model on startup
with app.app_context():
    try:
        db.create_all()
        logger.info("Database tables created successfully")
        
        # Create default admin user if it doesn't exist
        admin_user = User.query.filter_by(email='admin@fakenews.com').first()
        if not admin_user:
            admin_user = User(
                name='System Administrator',
                email='admin@fakenews.com',
                role=UserRole.ADMIN,
                status=UserStatus.ACTIVE,
                email_verified=True
            )
            admin_user.set_password('admin123')
            db.session.add(admin_user)
            db.session.commit()
            logger.info("Default admin user created")
        
    except Exception as e:
        logger.error(f"Database initialization failed: {e}")

initialize_model()

if __name__ == '__main__':
    # Run the Flask app
    app.run(host='0.0.0.0', port=5000, debug=True)