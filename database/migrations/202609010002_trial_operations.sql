CREATE TABLE trial_operation_logs (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 church_id BIGINT UNSIGNED NOT NULL,
 subscription_id BIGINT UNSIGNED NOT NULL,
 actor_user_id BIGINT UNSIGNED NULL,
 operation ENUM('extend','expire','recover') NOT NULL,
 previous_status VARCHAR(30) NOT NULL,
 new_status VARCHAR(30) NOT NULL,
 previous_trial_ends_at DATETIME NULL,
 new_trial_ends_at DATETIME NULL,
 reason VARCHAR(500) NOT NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 KEY idx_trial_operations_church_created (church_id,created_at),
 CONSTRAINT fk_trial_operations_church FOREIGN KEY (church_id) REFERENCES churches(id) ON DELETE RESTRICT,
 CONSTRAINT fk_trial_operations_subscription FOREIGN KEY (subscription_id) REFERENCES subscriptions(id) ON DELETE RESTRICT,
 CONSTRAINT fk_trial_operations_actor FOREIGN KEY (actor_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
