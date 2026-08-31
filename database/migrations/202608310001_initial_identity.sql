CREATE TABLE churches (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) NOT NULL,
    slug VARCHAR(80) NOT NULL,
    name VARCHAR(150) NOT NULL,
    organization_type ENUM('church', 'organization') NOT NULL DEFAULT 'church',
    status ENUM('trial', 'active', 'suspended', 'archived') NOT NULL DEFAULT 'trial',
    product_family ENUM('invitation', 'website', 'custom') NOT NULL DEFAULT 'invitation',
    contact_name VARCHAR(100) NULL,
    contact_email VARCHAR(190) NULL,
    contact_phone VARCHAR(30) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_churches_uuid (uuid),
    UNIQUE KEY uq_churches_slug (slug),
    KEY idx_churches_status_product (status, product_family)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) NOT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(190) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    status ENUM('active', 'locked', 'disabled') NOT NULL DEFAULT 'active',
    failed_login_count SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    locked_until DATETIME NULL,
    last_login_at DATETIME NULL,
    password_changed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_users_uuid (uuid),
    UNIQUE KEY uq_users_email (email),
    KEY idx_users_status_locked (status, locked_until)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE platform_user_roles (
    user_id BIGINT UNSIGNED NOT NULL,
    role ENUM('platform_admin', 'platform_operator') NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id),
    CONSTRAINT fk_platform_roles_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE platform_mfa_credentials (
    user_id BIGINT UNSIGNED NOT NULL,
    encrypted_secret TEXT NOT NULL,
    enabled_at DATETIME NOT NULL,
    last_used_counter BIGINT NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id),
    CONSTRAINT fk_platform_mfa_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE church_users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    church_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    role ENUM('owner', 'admin', 'content_manager') NOT NULL DEFAULT 'admin',
    status ENUM('invited', 'active', 'suspended') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_church_users_membership (church_id, user_id),
    KEY idx_church_users_user_status (user_id, status),
    KEY idx_church_users_church_role (church_id, role, status),
    CONSTRAINT fk_church_users_church FOREIGN KEY (church_id) REFERENCES churches(id) ON DELETE CASCADE,
    CONSTRAINT fk_church_users_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE products (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(60) NOT NULL,
    product_family ENUM('invitation', 'website', 'custom') NOT NULL,
    name VARCHAR(100) NOT NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_products_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE plans (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id BIGINT UNSIGNED NOT NULL,
    code VARCHAR(60) NOT NULL,
    name VARCHAR(100) NOT NULL,
    version SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    billing_cycle ENUM('trial', 'monthly', 'yearly') NOT NULL,
    price_krw INT UNSIGNED NOT NULL DEFAULT 0,
    trial_days SMALLINT UNSIGNED NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_plans_code_version (code, version),
    CONSTRAINT fk_plans_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE plan_features (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    plan_id BIGINT UNSIGNED NOT NULL,
    feature_code VARCHAR(100) NOT NULL,
    enabled TINYINT(1) NOT NULL DEFAULT 1,
    limit_value BIGINT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_plan_features (plan_id, feature_code),
    CONSTRAINT fk_plan_features_plan FOREIGN KEY (plan_id) REFERENCES plans(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE subscriptions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    church_id BIGINT UNSIGNED NOT NULL,
    plan_id BIGINT UNSIGNED NOT NULL,
    status ENUM('trialing', 'active', 'past_due', 'suspended', 'cancelled', 'expired') NOT NULL DEFAULT 'trialing',
    starts_at DATETIME NOT NULL,
    trial_ends_at DATETIME NULL,
    current_period_starts_at DATETIME NOT NULL,
    current_period_ends_at DATETIME NOT NULL,
    cancelled_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_subscriptions_church_status (church_id, status),
    KEY idx_subscriptions_trial_end (status, trial_ends_at),
    CONSTRAINT fk_subscriptions_church FOREIGN KEY (church_id) REFERENCES churches(id) ON DELETE CASCADE,
    CONSTRAINT fk_subscriptions_plan FOREIGN KEY (plan_id) REFERENCES plans(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE admin_audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    actor_user_id BIGINT UNSIGNED NULL,
    church_id BIGINT UNSIGNED NULL,
    action VARCHAR(100) NOT NULL,
    subject_type VARCHAR(80) NULL,
    subject_id VARCHAR(80) NULL,
    metadata JSON NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_audit_church_created (church_id, created_at),
    KEY idx_audit_actor_created (actor_user_id, created_at),
    CONSTRAINT fk_audit_actor FOREIGN KEY (actor_user_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_audit_church FOREIGN KEY (church_id) REFERENCES churches(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO products (code, product_family, name)
VALUES ('invitation', 'invitation', '전도지·초대장 전용');

INSERT INTO plans (product_id, code, name, version, billing_cycle, price_krw, trial_days)
SELECT id, 'invitation-trial', '초대장 30일 무료체험', 1, 'trial', 0, 30
FROM products WHERE code = 'invitation';

INSERT INTO plan_features (plan_id, feature_code, enabled, limit_value)
SELECT p.id, f.feature_code, 1, f.limit_value
FROM plans p
JOIN (
    SELECT 'invitation.enabled' feature_code, 1 limit_value
    UNION ALL SELECT 'invitation.active_count', 2
    UNION ALL SELECT 'invitation.monthly_create_count', 2
    UNION ALL SELECT 'invitation.photos_per_item', 5
    UNION ALL SELECT 'application.max_count', 50
    UNION ALL SELECT 'traffic.monthly_bytes', 2147483648
    UNION ALL SELECT 'storage.total_bytes', 209715200
    UNION ALL SELECT 'admin.max_count', 1
    UNION ALL SELECT 'analytics.retention_days', 30
) f
WHERE p.code = 'invitation-trial' AND p.version = 1;
