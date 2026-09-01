CREATE TABLE public_contact_requests (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category ENUM('general', 'subscription', 'technical', 'policy') NOT NULL DEFAULT 'general',
    name VARCHAR(80) NOT NULL,
    email VARCHAR(190) NOT NULL,
    phone VARCHAR(30) NULL,
    church_name VARCHAR(120) NULL,
    subject VARCHAR(120) NOT NULL,
    message TEXT NOT NULL,
    agreed_terms TINYINT(1) NOT NULL DEFAULT 0,
    ip_address VARBINARY(16) NULL,
    user_agent VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_contact_requests_created_at (created_at),
    INDEX idx_contact_requests_email (email),
    INDEX idx_contact_requests_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
