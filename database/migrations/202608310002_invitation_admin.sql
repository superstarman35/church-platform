CREATE TABLE invitations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) NOT NULL,
    church_id BIGINT UNSIGNED NOT NULL,
    slug VARCHAR(80) NOT NULL,
    title VARCHAR(150) NOT NULL,
    event_type ENUM('worship', 'evangelism', 'conference', 'education', 'volunteer', 'community', 'other') NOT NULL DEFAULT 'worship',
    template_code ENUM('portrait', 'square', 'landscape') NOT NULL DEFAULT 'portrait',
    status ENUM('draft', 'published', 'ended') NOT NULL DEFAULT 'draft',
    summary VARCHAR(255) NULL,
    body TEXT NULL,
    event_at DATETIME NULL,
    venue_name VARCHAR(150) NULL,
    venue_address VARCHAR(255) NULL,
    map_url VARCHAR(500) NULL,
    youtube_url VARCHAR(500) NULL,
    contact_name VARCHAR(100) NULL,
    contact_phone VARCHAR(30) NULL,
    published_at DATETIME NULL,
    ended_at DATETIME NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    updated_by BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_invitations_uuid (uuid),
    UNIQUE KEY uq_invitations_church_slug (church_id, slug),
    KEY idx_invitations_church_status (church_id, status, created_at),
    CONSTRAINT fk_invitations_church FOREIGN KEY (church_id) REFERENCES churches(id) ON DELETE CASCADE,
    CONSTRAINT fk_invitations_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT,
    CONSTRAINT fk_invitations_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE invitation_media (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) NOT NULL,
    church_id BIGINT UNSIGNED NOT NULL,
    invitation_id BIGINT UNSIGNED NOT NULL,
    kind ENUM('hero', 'gallery') NOT NULL DEFAULT 'gallery',
    file_path VARCHAR(500) NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    file_bytes BIGINT UNSIGNED NOT NULL,
    width SMALLINT UNSIGNED NULL,
    height SMALLINT UNSIGNED NULL,
    sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_invitation_media_uuid (uuid),
    KEY idx_invitation_media_tenant (church_id, invitation_id, sort_order),
    CONSTRAINT fk_invitation_media_church FOREIGN KEY (church_id) REFERENCES churches(id) ON DELETE CASCADE,
    CONSTRAINT fk_invitation_media_invitation FOREIGN KEY (invitation_id) REFERENCES invitations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE invitation_applications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    church_id BIGINT UNSIGNED NOT NULL,
    invitation_id BIGINT UNSIGNED NOT NULL,
    applicant_name VARCHAR(100) NOT NULL,
    phone VARCHAR(30) NULL,
    email VARCHAR(190) NULL,
    attendee_count SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    message VARCHAR(1000) NULL,
    status ENUM('new', 'confirmed', 'cancelled') NOT NULL DEFAULT 'new',
    consented_at DATETIME NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_applications_tenant_invitation (church_id, invitation_id, created_at),
    CONSTRAINT fk_applications_church FOREIGN KEY (church_id) REFERENCES churches(id) ON DELETE CASCADE,
    CONSTRAINT fk_applications_invitation FOREIGN KEY (invitation_id) REFERENCES invitations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE invitation_daily_stats (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    church_id BIGINT UNSIGNED NOT NULL,
    invitation_id BIGINT UNSIGNED NOT NULL,
    stat_date DATE NOT NULL,
    views BIGINT UNSIGNED NOT NULL DEFAULT 0,
    shares BIGINT UNSIGNED NOT NULL DEFAULT 0,
    applications BIGINT UNSIGNED NOT NULL DEFAULT 0,
    traffic_bytes BIGINT UNSIGNED NOT NULL DEFAULT 0,
    UNIQUE KEY uq_invitation_daily_stats (church_id, invitation_id, stat_date),
    KEY idx_invitation_stats_tenant_date (church_id, stat_date),
    CONSTRAINT fk_invitation_stats_church FOREIGN KEY (church_id) REFERENCES churches(id) ON DELETE CASCADE,
    CONSTRAINT fk_invitation_stats_invitation FOREIGN KEY (invitation_id) REFERENCES invitations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO plans (product_id, code, name, version, billing_cycle, price_krw)
SELECT id, 'invitation-basic', '초대장 베이직', 1, 'monthly', 4900 FROM products WHERE code = 'invitation';
INSERT INTO plans (product_id, code, name, version, billing_cycle, price_krw)
SELECT id, 'invitation-growth', '초대장 그로스', 1, 'monthly', 9900 FROM products WHERE code = 'invitation';

INSERT INTO plan_features (plan_id, feature_code, enabled, limit_value)
SELECT p.id, f.feature_code, 1, f.limit_value FROM plans p JOIN (
    SELECT 'invitation.enabled' feature_code, 1 limit_value
    UNION ALL SELECT 'invitation.active_count', 5
    UNION ALL SELECT 'invitation.monthly_create_count', 10
    UNION ALL SELECT 'invitation.photos_per_item', 15
    UNION ALL SELECT 'application.max_count', 500
    UNION ALL SELECT 'traffic.monthly_bytes', 10737418240
    UNION ALL SELECT 'storage.total_bytes', 524288000
    UNION ALL SELECT 'admin.max_count', 2
    UNION ALL SELECT 'analytics.retention_days', 183
) f WHERE p.code = 'invitation-basic' AND p.version = 1;

INSERT INTO plan_features (plan_id, feature_code, enabled, limit_value)
SELECT p.id, f.feature_code, 1, f.limit_value FROM plans p JOIN (
    SELECT 'invitation.enabled' feature_code, 1 limit_value
    UNION ALL SELECT 'invitation.active_count', 20
    UNION ALL SELECT 'invitation.monthly_create_count', 50
    UNION ALL SELECT 'invitation.photos_per_item', 30
    UNION ALL SELECT 'application.max_count', 3000
    UNION ALL SELECT 'traffic.monthly_bytes', 32212254720
    UNION ALL SELECT 'storage.total_bytes', 1610612736
    UNION ALL SELECT 'admin.max_count', 5
    UNION ALL SELECT 'analytics.retention_days', 365
) f WHERE p.code = 'invitation-growth' AND p.version = 1;
