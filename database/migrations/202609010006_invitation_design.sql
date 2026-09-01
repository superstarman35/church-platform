ALTER TABLE invitations
    ADD COLUMN color_preset ENUM('forest','navy','rose','sand') NOT NULL DEFAULT 'forest' AFTER template_code,
    ADD COLUMN font_preset ENUM('sans','serif','rounded') NOT NULL DEFAULT 'sans' AFTER color_preset,
    ADD COLUMN button_preset ENUM('rounded','pill','square') NOT NULL DEFAULT 'rounded' AFTER font_preset;
