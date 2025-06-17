-- Sample Data for Fake News Detection System

USE fake_news_detection;

-- Insert sample users
INSERT INTO users (name, email, password, role, email_verified, status) VALUES
('Admin User', 'admin@fakenews.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', TRUE, 'active'),
('John Doe', 'john@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', TRUE, 'active'),
('Jane Smith', 'jane@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', TRUE, 'active'),
('Bob Wilson', 'bob@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', FALSE, 'active'),
('Alice Brown', 'alice@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', TRUE, 'suspended');

-- Insert sample submissions
INSERT INTO submissions (user_id, submission_type, content, original_url, prediction, confidence, processing_time, ip_address) VALUES
(2, 'text', 'Breaking: Scientists discover new planet with potential for life. The research was published in Nature journal after peer review.', NULL, 'REAL', 87.50, 1.245, '192.168.1.100'),
(2, 'url', 'Local government announces new infrastructure funding for the community development project.', 'https://example-news.com/infrastructure', 'REAL', 92.30, 2.150, '192.168.1.100'),
(3, 'text', 'SHOCKING: Secret government conspiracy revealed! They dont want you to know this amazing truth that will change everything!', NULL, 'FAKE', 95.80, 0.890, '192.168.1.101'),
(3, 'text', 'University researchers publish study on climate change impacts in peer-reviewed scientific journal.', NULL, 'REAL', 89.20, 1.100, '192.168.1.101'),
(4, 'url', 'Miracle cure discovered! Doctors hate this one simple trick that big pharma doesnt want you to know!', 'https://fake-news-site.com/miracle-cure', 'FAKE', 98.50, 1.520, '192.168.1.102'),
(4, 'text', 'Weather service issues storm warning for coastal areas. Residents advised to take precautionary measures.', NULL, 'REAL', 85.70, 0.950, '192.168.1.102'),
(2, 'text', 'Celebrities secretly control world government through illuminati meetings held in underground bunkers.', NULL, 'FAKE', 97.20, 1.340, '192.168.1.100'),
(3, 'url', 'Economic report shows steady growth in technology sector with new job opportunities.', 'https://economic-times.com/tech-growth', 'REAL', 88.90, 1.890, '192.168.1.101');

-- Insert sample flags
INSERT INTO flags (user_id, submission_id, reason, description, status) VALUES
(3, 3, 'inappropriate', 'Contains conspiracy theory content that could mislead users', 'pending'),
(4, 5, 'spam', 'Looks like promotional content for questionable products', 'pending'),
(2, 7, 'other', 'Extremely unrealistic claims without any evidence', 'reviewed');

-- Insert sample comments
INSERT INTO comments (user_id, submission_id, comment, rating) VALUES
(2, 1, 'Great analysis! The prediction seems accurate for this scientific news.', 5),
(3, 2, 'I agree with the REAL classification. This looks like legitimate news.', 4),
(4, 3, 'The system correctly identified this as fake news. Good detection!', 5),
(2, 4, 'Accurate prediction. This is clearly real news from a reputable source.', 4),
(3, 5, 'Obviously fake news. The system did well to catch this.', 5);

-- Insert sample keyword trends
INSERT INTO keyword_trends (keyword, category, frequency) VALUES
('conspiracy', 'fake', 15),
('government', 'fake', 12),
('secret', 'fake', 18),
('shocking', 'fake', 22),
('miracle', 'fake', 14),
('scientists', 'real', 25),
('research', 'real', 30),
('university', 'real', 20),
('published', 'real', 18),
('study', 'real', 28),
('journal', 'real', 16),
('official', 'real', 22);

-- Insert system statistics
INSERT INTO system_stats (stat_key, stat_value) VALUES
('daily_submissions', '{"date": "2024-01-15", "total": 45, "fake": 18, "real": 27}'),
('user_activity', '{"active_users": 156, "new_registrations": 12, "avg_submissions_per_user": 2.8}'),
('model_performance', '{"accuracy": 0.92, "precision": 0.89, "recall": 0.94, "f1_score": 0.91}');