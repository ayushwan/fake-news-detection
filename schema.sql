-- AI-Powered Fake News Detection System Database Schema
-- MySQL 8.0+ Compatible

-- Create Database
CREATE DATABASE IF NOT EXISTS fake_news_detection 
DEFAULT CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE fake_news_detection;

-- Users Table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') DEFAULT 'user',
    email_verified BOOLEAN DEFAULT FALSE,
    profile_image VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    status ENUM('active', 'suspended', 'banned') DEFAULT 'active',
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_status (status)
);

-- News Submissions Table
CREATE TABLE submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    submission_type ENUM('text', 'url', 'image') NOT NULL,
    content TEXT NOT NULL,
    original_url VARCHAR(1000) DEFAULT NULL,
    image_path VARCHAR(500) DEFAULT NULL,
    prediction ENUM('FAKE', 'REAL') NOT NULL,
    confidence DECIMAL(5,2) NOT NULL,
    processing_time DECIMAL(6,3) DEFAULT NULL,
    model_version VARCHAR(50) DEFAULT 'v1.0',
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent TEXT DEFAULT NULL,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_prediction (prediction),
    INDEX idx_confidence (confidence),
    INDEX idx_submitted_at (submitted_at),
    INDEX idx_submission_type (submission_type)
);

-- Community Flags Table
CREATE TABLE flags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    submission_id INT NOT NULL,
    reason ENUM('inappropriate', 'spam', 'incorrect_prediction', 'offensive', 'other') NOT NULL,
    description TEXT DEFAULT NULL,
    status ENUM('pending', 'reviewed', 'resolved', 'dismissed') DEFAULT 'pending',
    reviewed_by INT DEFAULT NULL,
    reviewed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (submission_id) REFERENCES submissions(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_submission_id (submission_id),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
);

-- Comments/Feedback Table
CREATE TABLE comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    submission_id INT NOT NULL,
    comment TEXT NOT NULL,
    rating TINYINT CHECK (rating >= 1 AND rating <= 5),
    helpful_votes INT DEFAULT 0,
    status ENUM('active', 'hidden', 'deleted') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (submission_id) REFERENCES submissions(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_submission_id (submission_id),
    INDEX idx_rating (rating),
    INDEX idx_status (status)
);

-- User Sessions Table (for session management)
CREATE TABLE user_sessions (
    id VARCHAR(128) PRIMARY KEY,
    user_id INT NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_expires_at (expires_at)
);

-- System Statistics Table (for caching dashboard data)
CREATE TABLE system_stats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    stat_key VARCHAR(100) UNIQUE NOT NULL,
    stat_value JSON NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_stat_key (stat_key)
);

-- Keyword Trends Table (for tracking fake news keywords)
CREATE TABLE keyword_trends (
    id INT AUTO_INCREMENT PRIMARY KEY,
    keyword VARCHAR(255) NOT NULL,
    category ENUM('fake', 'real') NOT NULL,
    frequency INT DEFAULT 1,
    first_seen TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_seen TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_keyword (keyword),
    INDEX idx_category (category),
    INDEX idx_frequency (frequency)
);