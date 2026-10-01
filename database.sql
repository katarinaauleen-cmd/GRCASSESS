CREATE DATABASE IF NOT EXISTS grc_assessment;

USE grc_assessment;

CREATE TABLE IF NOT EXISTS assessments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    target_url VARCHAR(500) NOT NULL,
    score INT NOT NULL,
    risk_level VARCHAR(20) NOT NULL,
    findings TEXT NOT NULL,
    recommendations TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);