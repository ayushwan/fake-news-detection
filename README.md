# AI-Powered Fake News Detection System

A comprehensive full-stack web application that uses machine learning to detect fake news with high accuracy. The system supports multiple input methods including text, URL extraction, and image OCR analysis.

## 🚀 Features

### Core Functionality
- **AI-Powered Detection**: Advanced ML model using TF-IDF + Logistic Regression
- **Multiple Input Methods**: Text, URL extraction, and image OCR
- **Real-time Analysis**: Instant results with confidence scores
- **User Authentication**: Secure registration and login system
- **Admin Dashboard**: Comprehensive management and analytics

### Analysis Capabilities
- **Text Analysis**: Direct input of news content
- **URL Extraction**: Automatic content extraction from news URLs
- **Image OCR**: Text extraction from images using pytesseract
- **Confidence Scoring**: Detailed probability assessments
- **Processing Metrics**: Response time and analysis statistics

### User Management
- **Role-based Access**: User and Admin roles
- **Profile Management**: User settings and preferences
- **Submission History**: Track previous analyses
- **Community Features**: Comments and content flagging

### Admin Features
- **Dashboard Analytics**: Comprehensive statistics and charts
- **User Management**: Ban, suspend, and manage users
- **Content Moderation**: Review flags and submissions
- **System Monitoring**: Performance and usage metrics
- **Data Export**: CSV and JSON export capabilities

## 🛠 Technology Stack

### Frontend
- **HTML5, CSS3, Bootstrap 5**: Responsive UI design
- **JavaScript (ES6+)**: Interactive functionality
- **Chart.js**: Data visualization and analytics
- **Font Awesome**: Icons and visual elements

### Backend
- **PHP 8.0+**: Server-side logic and API endpoints
- **MySQL 8.0+**: Database management
- **cURL**: HTTP client for ML API communication
- **Session Management**: Secure user authentication

### Machine Learning API
- **Flask**: Python web framework
- **scikit-learn**: ML algorithms and preprocessing
- **NLTK**: Natural language processing
- **TF-IDF Vectorizer**: Feature extraction
- **Logistic Regression**: Classification model

### Additional Tools
- **newspaper3k**: Web content extraction
- **pytesseract**: OCR text extraction
- **Flask-CORS**: Cross-origin resource sharing

## 📋 Requirements

### System Requirements
- **PHP**: 8.0 or higher
- **MySQL**: 8.0 or higher
- **Python**: 3.8 or higher
- **Apache/Nginx**: Web server
- **Tesseract OCR**: For image text extraction

### PHP Extensions
- PDO MySQL
- cURL
- GD Library
- JSON
- OpenSSL

### Python Packages
```bash
flask==2.3.3
flask-cors==4.0.0
scikit-learn==1.3.0
nltk==3.8.1
newspaper3k==0.2.8
pytesseract==0.3.10
numpy==1.24.3
requests==2.31.0
```

## 🚀 Installation Guide

### 1. Clone Repository
```bash
git clone https://github.com/your-repo/fake-news-detection.git
cd fake-news-detection
```

### 2. Database Setup
```sql
-- Import the database schema
mysql -u root -p < schema.sql

-- Import sample data (optional)
mysql -u root -p < sample_data.sql
```

### 3. PHP Configuration
```php
// Update database-config.php
private const HOST = 'localhost';
private const USERNAME = 'your_username';
private const PASSWORD = 'your_password';
private const DATABASE = 'fake_news_detection';
```

### 4. Python ML API Setup
```bash
# Install Python dependencies
pip install -r requirements.txt

# Download NLTK data
python -c "import nltk; nltk.download('punkt'); nltk.download('stopwords')"

# Start the Flask API
python app.py
```

### 5. Web Server Configuration

#### Apache (.htaccess)
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]
```

#### Nginx
```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/fake-news-detection;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_index index.php;
        include fastcgi_params;
    }
}
```

## 🔧 Configuration

### Environment Variables
```php
// app-config.php
define('ML_API_URL', 'http://localhost:5000');
define('APP_URL', 'http://localhost/fake-news-detection');
define('SESSION_TIMEOUT', 3600);
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
```

### Security Settings
```php
// Generate secure session key
define('JWT_SECRET', 'your-secret-key-here');
define('PASSWORD_MIN_LENGTH', 8);
define('MAX_LOGIN_ATTEMPTS', 5);
```

## 🎯 Usage

### For End Users

1. **Registration**: Create account at `/register.php`
2. **Login**: Access dashboard at `/login.php`
3. **Analysis**: Submit content via three methods:
   - **Text Input**: Paste article content
   - **URL Analysis**: Enter article URL
   - **Image Upload**: Upload image with text
4. **Results**: View confidence scores and analysis
5. **History**: Track previous submissions

### For Administrators

1. **Dashboard**: Access analytics at `/admin.php`
2. **User Management**: Monitor and manage users
3. **Content Moderation**: Review flagged content
4. **System Monitoring**: Track performance metrics
5. **Data Export**: Generate reports

## 📊 API Endpoints

### ML Analysis API
```
POST /analyze
{
    "news": "article content",
    "content_type": "text|url|image"
}

Response:
{
    "prediction": "fake|real",
    "confidence": 0.92,
    "processing_time": 1.23
}
```

### PHP API Endpoints
- `POST /api/submit_news.php` - Submit content for analysis
- `GET /api/history.php` - Get user submission history
- `POST /api/flag.php` - Flag inappropriate content
- `GET /api/stats.php` - Get system statistics (admin)

## 🧪 Testing

### Unit Tests
```bash
# Python ML API tests
python -m pytest tests/

# PHP backend tests
phpunit tests/
```

### Load Testing
```bash
# Test ML API performance
ab -n 1000 -c 10 http://localhost:5000/analyze
```

## 📈 Performance

### Benchmarks
- **Analysis Speed**: ~1.2 seconds per request
- **Accuracy**: 92% on test dataset
- **Throughput**: 100+ requests per minute
- **Memory Usage**: <512MB Python process

### Optimization Tips
1. **Caching**: Implement Redis for frequent queries
2. **CDN**: Use CDN for static assets
3. **Database**: Add indexes for performance
4. **API**: Consider async processing for batch requests

## 🔐 Security

### Implementation
- **CSRF Protection**: All forms include CSRF tokens
- **SQL Injection**: Parameterized queries
- **XSS Prevention**: Input sanitization
- **Rate Limiting**: API and submission limits
- **Session Security**: Secure session management

### Best Practices
1. Regular security updates
2. Strong password policies
3. Input validation and sanitization
4. Secure file upload handling
5. Error logging and monitoring

## 🤝 Contributing

### Development Setup
1. Fork the repository
2. Create feature branch
3. Make changes with tests
4. Submit pull request

### Code Standards
- **PHP**: PSR-12 coding standards
- **JavaScript**: ES6+ with proper formatting
- **Python**: PEP 8 style guide
- **Documentation**: Comprehensive comments

## 📄 License

This project is licensed under the MIT License - see LICENSE file for details.

## 🆘 Support

### Documentation
- [API Documentation](docs/api.md)
- [Deployment Guide](docs/deployment.md)
- [Troubleshooting](docs/troubleshooting.md)

### Contact
- **Email**: support@fakenewsdetective.com
- **Issues**: GitHub Issues page
- **Discord**: Community chat server

## 🎖 Acknowledgments

- **Dataset**: Training data derived from public news sources
- **Libraries**: Thanks to scikit-learn, Flask, and Bootstrap teams
- **Community**: Contributors and beta testers

---

**Version**: 1.0.0  
**Last Updated**: December 2024  
**Maintainer**: Development Team