# Fake News Detection System

A full-stack web application built with Python (Flask) for detecting fake news using machine learning. The system allows users to submit news articles in various formats (text, URL, file) and provides predictions on their authenticity.

## Features

- **User Authentication**
  - Registration and login functionality
  - User profile management
  - Role-based access control (Admin/User)

- **News Submission**
  - Multiple input methods:
    - Plain text input
    - URL submission
    - File upload (.txt)
  - Real-time prediction results
  - Confidence score display

- **Result Sharing**
  - Social media sharing options
  - PDF export functionality
  - Copy to clipboard feature

- **User Dashboard**
  - Submission history
  - Personal statistics
  - Profile management

- **Admin Panel**
  - User management
  - Article monitoring
  - System statistics
  - Report generation

- **Modern UI/UX**
  - Responsive design using Bootstrap
  - Dark/Light theme toggle
  - Interactive animations
  - Toast notifications

## Tech Stack

- **Backend**: Python, Flask
- **Frontend**: HTML, CSS, JavaScript
- **Database**: MySQL
- **ML Model**: Scikit-learn
- **Additional Libraries**: 
  - Flask-Login (Authentication)
  - Flask-SQLAlchemy (ORM)
  - Flask-WTF (Forms)
  - Newspaper3k (Article extraction)
  - jsPDF (PDF generation)
  - Chart.js (Statistics visualization)

## Installation

1. Clone the repository:
   ```bash
   git clone <repository-url>
   cd fake-news-detection
   ```

2. Create and activate a virtual environment:
   ```bash
   python -m venv venv
   source venv/bin/activate  # On Windows: .\venv\Scripts\activate
   ```

3. Install dependencies:
   ```bash
   pip install -r requirements.txt
   ```

4. Set up environment variables in `.env`:
   ```env
   FLASK_APP=run.py
   FLASK_ENV=development
   SECRET_KEY=your-secret-key
   DATABASE_URI=mysql://username:password@localhost/dbname
   ```

5. Initialize the database:
   ```bash
   flask db init
   flask db migrate
   flask db upgrade
   ```

6. Run the application:
   ```bash
   flask run
   ```

## Project Structure

```
├── app/
│   ├── __init__.py
│   ├── admin.py
│   ├── auth.py
│   ├── forms.py
│   ├── models.py
│   ├── routes.py
│   ├── static/
│   │   ├── css/
│   │   └── js/
│   └── templates/
│       ├── admin/
│       ├── auth/
│       ├── errors/
│       ├── macros/
│       ├── partials/
│       └── *.html
├── ml_model/
│   ├── fake_news_model.pkl
│   └── vectorizer.pkl
├── requirements.txt
├── run.py
└── .env
```

## Usage

1. Register a new account or login with existing credentials
2. Submit news for analysis through any of the available methods:
   - Enter news text directly
   - Provide a news article URL
   - Upload a text file
3. View the prediction result and confidence score
4. Share or export the results as needed
5. Access your submission history and statistics in the profile section

## Admin Access

To access the admin panel:
1. Create an admin user in the database
2. Login with admin credentials
3. Access the admin panel through the navigation menu

Admin features include:
- User management (block/unblock/delete users)
- View all submissions
- Access system statistics
- Generate reports

## Contributing

1. Fork the repository
2. Create a feature branch
3. Commit your changes
4. Push to the branch
5. Create a Pull Request

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Acknowledgments

- Bootstrap for the UI components
- Font Awesome for icons
- Chart.js for data visualization
- All other open-source libraries used in this project