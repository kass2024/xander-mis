-- Admin sidebar menu permissions (per-admin overrides)
-- Auto-created by helpers/admin_menu_permissions.php on first use.

CREATE TABLE IF NOT EXISTS admin_menu_permissions (
    admin_id INT UNSIGNED NOT NULL,
    permissions JSON NOT NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_by INT UNSIGNED NULL DEFAULT NULL,
    PRIMARY KEY (admin_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
