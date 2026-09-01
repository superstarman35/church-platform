ALTER TABLE invitation_media
    ADD COLUMN original_file_bytes BIGINT UNSIGNED NULL AFTER mime_type,
    ADD COLUMN alt_text VARCHAR(255) NULL AFTER height,
    ADD COLUMN usage_consent TINYINT(1) NOT NULL DEFAULT 0 AFTER alt_text,
    ADD COLUMN deleted_at DATETIME NULL AFTER sort_order,
    ADD COLUMN deleted_by BIGINT UNSIGNED NULL AFTER deleted_at,
    ADD KEY idx_invitation_media_tenant_deleted (church_id, deleted_at, created_at),
    ADD CONSTRAINT fk_invitation_media_deleted_by FOREIGN KEY (deleted_by) REFERENCES users(id) ON DELETE SET NULL;
