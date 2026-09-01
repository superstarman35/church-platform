CREATE TABLE quota_change_requests (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    church_id BIGINT UNSIGNED NOT NULL,
    requested_by BIGINT UNSIGNED NOT NULL,
    request_type ENUM('traffic_reset','traffic_increase') NOT NULL,
    requested_bytes BIGINT UNSIGNED NULL,
    reason VARCHAR(500) NOT NULL,
    status ENUM('pending','approved','rejected','cancelled') NOT NULL DEFAULT 'pending',
    reviewed_by BIGINT UNSIGNED NULL,
    reviewed_at DATETIME NULL,
    review_note VARCHAR(500) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_quota_requests_church_status (church_id, status, created_at),
    KEY idx_quota_requests_status_created (status, created_at),
    CONSTRAINT fk_quota_requests_church FOREIGN KEY (church_id) REFERENCES churches(id) ON DELETE CASCADE,
    CONSTRAINT fk_quota_requests_requester FOREIGN KEY (requested_by) REFERENCES users(id) ON DELETE RESTRICT,
    CONSTRAINT fk_quota_requests_reviewer FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
);