from flask import Blueprint, render_template, redirect, url_for, flash, request, jsonify, abort
from flask_login import login_required, current_user
from .models import User, Article
from . import db
from functools import wraps

admin_bp = Blueprint('admin', __name__)

# Decorator to ensure user is an admin
def admin_required(f):
    @wraps(f)
    def decorated_function(*args, **kwargs):
        if not current_user.is_authenticated or not current_user.is_admin:
            flash('You do not have permission to access this page.', 'danger')
            return redirect(url_for('main.index')) # Or a 403 page
        return f(*args, **kwargs)
    return decorated_function

@admin_bp.route('/')
@login_required
@admin_required
def dashboard():
    total_users = User.query.count()
    total_submissions = Article.query.count()
    fake_news_count = Article.query.filter_by(prediction_result='FAKE').count()
    real_news_count = Article.query.filter_by(prediction_result='REAL').count()
    # Potentially more stats for charts
    return render_template('admin/dashboard.html', 
                           title='Admin Dashboard',
                           total_users=total_users,
                           total_submissions=total_submissions,
                           fake_news_count=fake_news_count,
                           real_news_count=real_news_count)

@admin_bp.route('/users')
@login_required
@admin_required
def manage_users():
    page = request.args.get('page', 1, type=int)
    users = User.query.order_by(User.created_at.desc()).paginate(page=page, per_page=15)
    return render_template('admin/manage_users.html', title='Manage Users', users=users)

@admin_bp.route('/users/view/<int:user_id>')
@login_required
@admin_required
def view_user(user_id):
    user = User.query.get_or_404(user_id)
    user_articles = Article.query.filter_by(user_id=user.id).order_by(Article.submitted_at.desc()).all()
    return render_template('admin/view_user.html', title=f'View User - {user.username}', user=user, articles=user_articles)

@admin_bp.route('/users/block/<int:user_id>', methods=['POST'])
@login_required
@admin_required
def block_user(user_id):
    user = User.query.get_or_404(user_id)
    if user.is_admin: # Prevent admin from blocking another admin or self through this route
        flash('Admins cannot be blocked through this interface.', 'warning')
        return redirect(url_for('admin.manage_users'))
    user.is_blocked = True
    db.session.commit()
    flash(f'User {user.username} has been blocked.', 'success')
    return redirect(url_for('admin.manage_users'))

@admin_bp.route('/users/unblock/<int:user_id>', methods=['POST'])
@login_required
@admin_required
def unblock_user(user_id):
    user = User.query.get_or_404(user_id)
    user.is_blocked = False
    db.session.commit()
    flash(f'User {user.username} has been unblocked.', 'success')
    return redirect(url_for('admin.manage_users'))

@admin_bp.route('/users/delete/<int:user_id>', methods=['POST'])
@login_required
@admin_required
def delete_user(user_id):
    user = User.query.get_or_404(user_id)
    if user.is_admin: # Prevent admin from deleting another admin or self
        flash('Admins cannot be deleted.', 'warning')
        return redirect(url_for('admin.manage_users'))
    
    # Optionally, decide what to do with user's articles (delete, anonymize, etc.)
    # For now, let's delete them. Add cascading delete in model if preferred.
    Article.query.filter_by(user_id=user.id).delete()
    
    db.session.delete(user)
    db.session.commit()
    flash(f'User {user.username} and their articles have been deleted.', 'success')
    return redirect(url_for('admin.manage_users'))

@admin_bp.route('/articles')
@login_required
@admin_required
def view_articles():
    page = request.args.get('page', 1, type=int)
    articles = Article.query.join(User).order_by(Article.submitted_at.desc()).paginate(page=page, per_page=15)
    return render_template('admin/view_articles.html', title='View All Articles', articles=articles)

@admin_bp.route('/articles/view/<int:article_id>')
@login_required
@admin_required
def view_article_detail(article_id):
    article = Article.query.get_or_404(article_id)
    # Similar logic to main.article_details for displaying content
    display_content = article.input_text
    if article.content_source == 'file' and article.file_path and not article.input_text:
        try:
            with open(article.file_path, 'r', encoding='utf-8') as f:
                display_content = f.read()
        except Exception as e:
            # current_app.logger.error(f"Could not read file for article {article.id}: {e}")
            display_content = "Error: Could not load file content."
    elif article.content_source == 'url' and article.input_url:
        display_content = f"Content was fetched from URL: {article.input_url}"

    return render_template('admin/view_article_detail.html', title='Article Details', article=article, display_content=display_content)


@admin_bp.route('/reports')
@login_required
@admin_required
def reports():
    # Placeholder for report generation. Could be CSV, PDF, etc.
    # For now, just a simple page
    return render_template('admin/reports.html', title='Download Reports')

# Example: API endpoint for chart data on admin dashboard
@admin_bp.route('/api/stats/fake_vs_real')
@login_required
@admin_required
def fake_vs_real_stats():
    fake_count = Article.query.filter_by(prediction_result='FAKE').count()
    real_count = Article.query.filter_by(prediction_result='REAL').count()
    return jsonify({'labels': ['Fake', 'Real'], 'data': [fake_count, real_count]})

@admin_bp.route('/api/stats/submissions_over_time')
@login_required
@admin_required
def submissions_over_time_stats():
    # This would require more complex querying, e.g., grouping by date
    # For simplicity, returning dummy data
    # In a real app, query articles and group by date: SELECT DATE(submitted_at), COUNT(id) FROM articles GROUP BY DATE(submitted_at)
    labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun']
    data = [10, 15, 8, 22, 18, 30] # Dummy data
    return jsonify({'labels': labels, 'data': data})