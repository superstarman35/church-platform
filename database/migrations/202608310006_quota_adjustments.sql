CREATE TABLE quota_overrides (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    church_id BIGINT UNSIGNED NOT NULL,
    feature_code VARCHAR(100) NOT NULL,
    extra_limit BIGINT UNSIGNED NOT NULL DEFAULT 0,
    updated_by BIGINT UNSIGNED NULL,
    reason VARCHAR(500) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_quota_override_church_feature (church_id, feature_code),
    CONSTRAINT fk_quota_override_church FOREIGN KEY (church_id) REFERENCES churches(id) ON DELETE CASCADE,
    CONSTRAINT fk_quota_override_user FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
);
CREATE TABLE traffic_reset_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    church_id BIGINT UNSIGNED NOT NULL,
    request_id BIGINT UNSIGNED NULL,
    previous_bytes BIGINT UNSIGNED NOT NULL,
    processed_by BIGINT UNSIGNED NOT NULL,
    reason VARCHAR(500) NOT NULL,
    reset_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_traffic_reset_church_date (church_id, reset_at),
    CONSTRAINT fk_traffic_reset_church FOREIGN KEY (church_id) REFERENCES churches(id) ON DELETE CASCADE,
    CONSTRAINT fk_traffic_reset_request FOREIGN KEY (request_id) REFERENCES quota_change_requests(id) ON DELETE SET NULL,
    CONSTRAINT fk_traffic_reset_user FOREIGN KEY (processed_by) REFERENCES users(id) ON DELETE RESTRICT
);
