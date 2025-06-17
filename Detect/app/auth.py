from flask import Blueprint, render_template, redirect, url_for, flash, request
from flask_login import login_user, logout_user, login_required, current_user
from werkzeug.security import generate_password_hash, check_password_hash
from .models import User
from . import db
from .forms import LoginForm, RegistrationForm # We will create forms.py next

auth_bp = Blueprint('auth', __name__)

@auth_bp.route('/register', methods=['GET', 'POST'])
def register():
    if current_user.is_authenticated:
        return redirect(url_for('main.index'))
    form = RegistrationForm()
    if form.validate_on_submit():
        hashed_password = generate_password_hash(form.password.data)
        new_user = User(username=form.username.data, email=form.email.data, password_hash=hashed_password)
        db.session.add(new_user)
        try:
            db.session.commit()
            flash('Your account has been created! You are now able to log in.', 'success')
            # Log in the user automatically after registration
            login_user(new_user)
            return redirect(url_for('main.index'))
        except Exception as e:
            db.session.rollback()
            if 'UNIQUE constraint failed: users.email' in str(e):
                 flash('Registration failed. Email already exists.', 'danger')
            elif 'UNIQUE constraint failed: users.username' in str(e):
                 flash('Registration failed. Username already exists.', 'danger')
            else:
                flash(f'An error occurred: {str(e)}', 'danger')
    return render_template('auth/register.html', title='Register', form=form)

@auth_bp.route('/login', methods=['GET', 'POST'])
def login():
    if current_user.is_authenticated:
        return redirect(url_for('main.index'))
    form = LoginForm()
    if form.validate_on_submit():
        user = User.query.filter_by(email=form.email.data).first()
        if user and user.check_password(form.password.data):
            if user.is_blocked:
                flash('Your account has been blocked. Please contact support.', 'danger')
                return redirect(url_for('auth.login'))
            login_user(user, remember=form.remember.data)
            next_page = request.args.get('next')
            flash('Login successful!', 'success')
            return redirect(next_page) if next_page else redirect(url_for('main.index'))
        else:
            flash('Login Unsuccessful. Please check email and password.', 'danger')
    return render_template('auth/login.html', title='Login', form=form)

@auth_bp.route('/logout')
@login_required
def logout():
    logout_user()
    flash('You have been logged out.', 'info')
    return redirect(url_for('main.index'))