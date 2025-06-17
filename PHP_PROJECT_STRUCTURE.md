# AI-Powered Fake News Detection System - PHP Implementation

## Project Structure

```
fake-news-detection/
├── backend/
│   ├── config/
│   │   ├── database.php
│   │   └── config.php
│   ├── controllers/
│   │   ├── AuthController.php
│   │   ├── NewsController.php
│   │   └── AdminController.php
│   ├── models/
│   │   ├── User.php
│   │   ├── Submission.php
│   │   └── Database.php
│   ├── utils/
│   │   ├── APIClient.php
│   │   ├── NewsExtractor.php
│   │   └── OCRProcessor.php
│   └── api/
│       ├── login.php
│       ├── register.php
│       ├── submit_news.php
│       └── admin_stats.php
├── frontend/
│   ├── assets/
│   │   ├── css/
│   │   │   ├── style.css
│   │   │   └── dashboard.css
│   │   ├── js/
│   │   │   ├── main.js
│   │   │   ├── dashboard.js
│   │   │   └── chart-config.js
│   │   └── images/
│   ├── includes/
│   │   ├── header.php
│   │   ├── footer.php
│   │   └── navbar.php
│   ├── pages/
│   │   ├── index.php
│   │   ├── login.php
│   │   ├── register.php
│   │   ├── dashboard.php
│   │   ├── admin.php
│   │   └── results.php
│   └── components/
│       ├── news-form.php
│       └── result-card.php
├── ml-api/
│   ├── app.py
│   ├── model/
│   │   ├── train_model.py
│   │   ├── model.pkl
│   │   └── vectorizer.pkl
│   ├── utils/
│   │   ├── preprocessor.py
│   │   └── news_extractor.py
│   └── requirements.txt
├── database/
│   ├── schema.sql
│   └── sample_data.sql
└── README.md
```

## Technology Stack

- **Frontend**: HTML5, CSS3, Bootstrap 5, JavaScript, Chart.js
- **Backend**: PHP 8.0+, MySQL 8.0+
- **ML API**: Python Flask, scikit-learn
- **Server**: Apache/Nginx with XAMPP for local development