ALTER TABLE public_contact_requests
    ADD COLUMN status ENUM('open', 'in_progress', 'answered', 'closed') NOT NULL DEFAULT 'open' AFTER user_agent,
    ADD COLUMN handled_by_user_id BIGINT UNSIGNED NULL AFTER status,
    ADD COLUMN handled_at DATETIME NULL AFTER handled_by_user_id,
    ADD COLUMN handled_note TEXT NULL AFTER handled_at,
    ADD INDEX idx_public_contact_requests_status (status),
    ADD INDEX idx_public_contact_requests_handled_by_user_id (handled_by_user_id);

