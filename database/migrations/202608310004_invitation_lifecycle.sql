ALTER TABLE invitations
    ADD COLUMN publish_at DATETIME NULL AFTER contact_phone,
    ADD COLUMN expires_at DATETIME NULL AFTER publish_at,
    ADD COLUMN deleted_at DATETIME NULL AFTER ended_at,
    ADD KEY idx_invitations_lifecycle (church_id, deleted_at, status, publish_at, expires_at);
