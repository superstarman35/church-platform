CREATE TABLE subscription_change_requests (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    church_id BIGINT UNSIGNED NOT NULL,
    requested_by BIGINT UNSIGNED NOT NULL,
    requested_plan_id BIGINT UNSIGNED NOT NULL,
    status ENUM('pending','awaiting_payment','completed','rejected','cancelled') NOT NULL DEFAULT 'pending',
    reason VARCHAR(500) NULL,
    reviewed_by BIGINT UNSIGNED NULL,
    reviewed_at DATETIME NULL,
    review_note VARCHAR(500) NULL,
    payment_confirmed_by BIGINT UNSIGNED NULL,
    payment_confirmed_at DATETIME NULL,
    payment_reference VARCHAR(100) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_subscription_change_church (church_id,status,created_at),
    KEY idx_subscription_change_status (status,created_at),
    CONSTRAINT fk_subscription_change_church FOREIGN KEY (church_id) REFERENCES churches(id) ON DELETE CASCADE,
    CONSTRAINT fk_subscription_change_requester FOREIGN KEY (requested_by) REFERENCES users(id) ON DELETE RESTRICT,
    CONSTRAINT fk_subscription_change_plan FOREIGN KEY (requested_plan_id) REFERENCES plans(id) ON DELETE RESTRICT,
    CONSTRAINT fk_subscription_change_reviewer FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_subscription_change_payment_confirmer FOREIGN KEY (payment_confirmed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO plans(product_id,code,name,version,billing_cycle,price_krw,trial_days)
SELECT id,'invitation-basic','초대장 기본형',1,'monthly',4900,NULL FROM products WHERE code='invitation'
AND NOT EXISTS (SELECT 1 FROM plans WHERE code='invitation-basic' AND version=1);
INSERT INTO plans(product_id,code,name,version,billing_cycle,price_krw,trial_days)
SELECT id,'invitation-growth','초대장 성장형',1,'monthly',9900,NULL FROM products WHERE code='invitation'
AND NOT EXISTS (SELECT 1 FROM plans WHERE code='invitation-growth' AND version=1);

INSERT IGNORE INTO plan_features(plan_id,feature_code,enabled,limit_value)
SELECT p.id,f.feature_code,1,f.limit_value FROM plans p JOIN (
 SELECT 'invitation.enabled' feature_code,1 limit_value UNION ALL
 SELECT 'invitation.active_count',5 UNION ALL SELECT 'invitation.monthly_create_count',10 UNION ALL
 SELECT 'invitation.photos_per_item',15 UNION ALL SELECT 'application.max_count',500 UNION ALL
 SELECT 'traffic.monthly_bytes',10737418240 UNION ALL SELECT 'storage.total_bytes',524288000 UNION ALL
 SELECT 'admin.max_count',2 UNION ALL SELECT 'analytics.retention_days',180
) f WHERE p.code='invitation-basic' AND p.version=1;
INSERT IGNORE INTO plan_features(plan_id,feature_code,enabled,limit_value)
SELECT p.id,f.feature_code,1,f.limit_value FROM plans p JOIN (
 SELECT 'invitation.enabled' feature_code,1 limit_value UNION ALL
 SELECT 'invitation.active_count',20 UNION ALL SELECT 'invitation.monthly_create_count',50 UNION ALL
 SELECT 'invitation.photos_per_item',30 UNION ALL SELECT 'application.max_count',3000 UNION ALL
 SELECT 'traffic.monthly_bytes',32212254720 UNION ALL SELECT 'storage.total_bytes',1610612736 UNION ALL
 SELECT 'admin.max_count',5 UNION ALL SELECT 'analytics.retention_days',365
) f WHERE p.code='invitation-growth' AND p.version=1;
