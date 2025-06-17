from flask import Blueprint, render_template, request, jsonify, redirect, url_for, flash, current_app
from flask_login import login_required, current_user
from .models import Article, User
from . import db
import joblib # For loading the ML model
import os
from newspaper import Article as NewspaperArticle # To avoid naming conflict
from werkzeug.utils import secure_filename
import time # For simulating delay

# Load the ML model and vectorizer (adjust paths as needed)
# Ensure these files are present in your project directory or a specified model path
MODEL_PATH = os.path.join(os.path.dirname(__file__), '..', 'ml_model')
# Create a dummy model and vectorizer for now if they don't exist
# You'll need to train and save your actual model and vectorizer
if not os.path.exists(MODEL_PATH):
    os.makedirs(MODEL_PATH)

# Dummy model and vectorizer paths - replace with your actual files
TFIDF_VECTORIZER_PATH = os.path.join(MODEL_PATH, 'tfidf_vectorizer.pkl')
LOGISTIC_MODEL_PATH = os.path.join(MODEL_PATH, 'logistic_model.pkl')

# Try to load model and vectorizer, handle if not found
try:
    tfidf_vectorizer = joblib.load(TFIDF_VECTORIZER_PATH)
    model = joblib.load(LOGISTIC_MODEL_PATH)
except FileNotFoundError:
    tfidf_vectorizer = None
    model = None
    print(f"Warning: Model or TfidfVectorizer not found. Prediction will not work.")
    print(f"Expected TfidfVectorizer at: {TFIDF_VECTORIZER_PATH}")
    print(f"Expected Model at: {LOGISTIC_MODEL_PATH}")

main_bp = Blueprint('main', __name__)

ALLOWED_EXTENSIONS = {'txt'}
UPLOAD_FOLDER = os.path.join(os.path.dirname(__file__), 'uploads')
if not os.path.exists(UPLOAD_FOLDER):
    os.makedirs(UPLOAD_FOLDER)

def allowed_file(filename):
    return '.' in filename and \
           filename.rsplit('.', 1)[1].lower() in ALLOWED_EXTENSIONS

@main_bp.route('/')
def index():
    return render_template('index.html', title='Home')

@main_bp.route('/submit', methods=['GET', 'POST'])
@login_required
def submit_news():
    if request.method == 'POST':
        submission_type = request.form.get('submission_type')
        news_text = None
        news_url = None
        file_path_to_save = None
        original_input_for_db = None # Store the original input for the db

        if submission_type == 'text':
            news_text = request.form.get('news_text')
            original_input_for_db = news_text
            if not news_text or len(news_text.strip()) < 10: # Basic validation
                flash('Please enter sufficient news text.', 'danger')
                return render_template('submit_news.html', title='Submit News')
        elif submission_type == 'url':
            news_url = request.form.get('news_url')
            original_input_for_db = news_url
            if not news_url:
                flash('Please enter a news URL.', 'danger')
                return render_template('submit_news.html', title='Submit News')
            try:
                article_parser = NewspaperArticle(news_url)
                article_parser.download()
                article_parser.parse()
                news_text = article_parser.text
                if not news_text:
                    flash('Could not extract text from the URL. Try another source or plain text.', 'warning')
                    return render_template('submit_news.html', title='Submit News')
            except Exception as e:
                current_app.logger.error(f"Error processing URL {news_url}: {e}")
                flash(f'Error processing URL: {e}. Please try again or use plain text.', 'danger')
                return render_template('submit_news.html', title='Submit News')
        elif submission_type == 'file':
            if 'news_file' not in request.files:
                flash('No file part.', 'danger')
                return render_template('submit_news.html', title='Submit News')
            file = request.files['news_file']
            if file.filename == '':
                flash('No selected file.', 'danger')
                return render_template('submit_news.html', title='Submit News')
            if file and allowed_file(file.filename):
                filename = secure_filename(file.filename)
                file_path = os.path.join(UPLOAD_FOLDER, f"{current_user.id}_{int(time.time())}_{filename}")
                file.save(file_path)
                file_path_to_save = file_path
                original_input_for_db = filename # Store filename as original input
                try:
                    with open(file_path, 'r', encoding='utf-8') as f:
                        news_text = f.read()
                    if not news_text:
                        flash('The uploaded file is empty.', 'warning')
                        return render_template('submit_news.html', title='Submit News')
                except Exception as e:
                    current_app.logger.error(f"Error reading file {file_path}: {e}")
                    flash(f'Error reading file: {e}. Ensure it is a valid text file.', 'danger')
                    return render_template('submit_news.html', title='Submit News')
            else:
                flash('Invalid file type. Only .txt files are allowed.', 'danger')
                return render_template('submit_news.html', title='Submit News')
        else:
            flash('Invalid submission type.', 'danger')
            return render_template('submit_news.html', title='Submit News')

        # Prediction logic
        prediction = 'N/A'
        confidence = 0.0
        if model and tfidf_vectorizer and news_text:
            try:
                text_vectorized = tfidf_vectorizer.transform([news_text])
                pred_proba = model.predict_proba(text_vectorized)[0]
                if model.predict(text_vectorized)[0] == 1: # Assuming 1 is FAKE, 0 is REAL
                    prediction = 'FAKE'
                    confidence = pred_proba[1] * 100
                else:
                    prediction = 'REAL'
                    confidence = pred_proba[0] * 100
            except Exception as e:
                current_app.logger.error(f"Error during prediction: {e}")
                flash('Error during prediction. Please try again.', 'danger')
                # Fallback or default result if prediction fails
                prediction = 'ERROR'
                confidence = 0.0
        else:
            flash('ML model not loaded. Prediction unavailable.', 'warning')
            # Simulate a result if model is not available for UI testing
            # prediction = 'FAKE' if len(news_text) % 2 == 0 else 'REAL' 
            # confidence = 75.5

        # Save to database
        new_article = Article(
            title=news_text[:100] + '...' if news_text and len(news_text) > 100 else news_text, # Truncate for title
            content_source=submission_type,
            input_text=news_text if submission_type != 'url' else None, # Store extracted text for file/text, not for URL to save space
            input_url=news_url if submission_type == 'url' else None,
            file_path=file_path_to_save if submission_type == 'file' else None,
            prediction_result=prediction,
            confidence_score=round(confidence, 2),
            user_id=current_user.id
        )
        db.session.add(new_article)
        db.session.commit()

        return render_template('result.html', 
                                title='Prediction Result', 
                                prediction=prediction, 
                                confidence=round(confidence, 2),
                                news_input=original_input_for_db, # Show original input on result page
                                article_id=new_article.id)

    return render_template('submit_news.html', title='Submit News')

@main_bp.route('/history')
@login_required
def history():
    page = request.args.get('page', 1, type=int)
    user_articles = Article.query.filter_by(user_id=current_user.id)\
                                .order_by(Article.submitted_at.desc())\
                                .paginate(page=page, per_page=10)
    return render_template('history.html', title='Submission History', articles=user_articles)

@main_bp.route('/profile')
@login_required
def profile():
    total_submissions = Article.query.filter_by(user_id=current_user.id).count()
    fake_submissions = Article.query.filter_by(user_id=current_user.id, prediction_result='FAKE').count()
    real_submissions = Article.query.filter_by(user_id=current_user.id, prediction_result='REAL').count()
    
    fake_ratio = (fake_submissions / total_submissions * 100) if total_submissions > 0 else 0
    real_ratio = (real_submissions / total_submissions * 100) if total_submissions > 0 else 0

    stats = {
        'total_submissions': total_submissions,
        'fake_submissions': fake_submissions,
        'real_submissions': real_submissions,
        'fake_ratio': round(fake_ratio, 2),
        'real_ratio': round(real_ratio, 2)
    }
    return render_template('profile.html', title='User Profile', user=current_user, stats=stats)


@main_bp.route('/article_details/<int:article_id>')
@login_required
def article_details(article_id):
    article = Article.query.get_or_404(article_id)
    if article.user_id != current_user.id and not current_user.is_admin:
        flash('You do not have permission to view this article.', 'danger')
        return redirect(url_for('main.history'))
    
    # For 'file' type, try to read content if not stored in input_text
    display_content = article.input_text
    if article.content_source == 'file' and article.file_path and not article.input_text:
        try:
            with open(article.file_path, 'r', encoding='utf-8') as f:
                display_content = f.read()
        except Exception as e:
            current_app.logger.error(f"Could not read file for article {article.id}: {e}")
            display_content = "Error: Could not load file content."
    elif article.content_source == 'url' and article.input_url:
        # Optionally, you could re-fetch or show a link to the URL
        display_content = f"Content was fetched from URL: {article.input_url}"
        # If you stored extracted text for URLs, use that instead
        # display_content = article.input_text 

    return render_template('article_details.html', title='Article Details', article=article, display_content=display_content)

# Custom Error Pages
@main_bp.app_errorhandler(404)
def page_not_found(e):
    return render_template('errors/404.html'), 404

@main_bp.app_errorhandler(500)
def internal_server_error(e):
    # Log the error if needed
    current_app.logger.error(f"Server Error: {e}", exc_info=True)
    return render_template('errors/500.html'), 500

@main_bp.app_errorhandler(403)
def forbidden_error(e):
    return render_template('errors/403.html'), 403

# A dummy route to test model loading (optional)
@main_bp.route('/test_model_loading')
def test_model_loading():
    if model and tfidf_vectorizer:
        return "Model and TfidfVectorizer loaded successfully!"
    else:
        return "Error: Model or TfidfVectorizer not loaded. Check logs.", 500