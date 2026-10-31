CREATE TABLE IF NOT EXISTS practical12_events (
    event_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    description TEXT NOT NULL,
    category VARCHAR(80) NOT NULL,
    event_date DATETIME NOT NULL,
    location VARCHAR(180) NOT NULL,
    status ENUM('open', 'closed') NOT NULL DEFAULT 'open',
    poster VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_practical12_event_date (event_date),
    INDEX idx_practical12_event_status (status),
    INDEX idx_practical12_event_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
