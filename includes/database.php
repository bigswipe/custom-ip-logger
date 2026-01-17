<?php
// Exit if accessed directly
defined('ABSPATH') || exit;

function iplogger_create_database_table() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'ip_logs';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        log_time DATETIME DEFAULT CURRENT_TIMESTAMP,
        slug VARCHAR(255),
        ip VARCHAR(100),
        latitude VARCHAR(100),
        longitude VARCHAR(100),
        user_agent TEXT,
        battery VARCHAR(10),
        charging VARCHAR(10),
        screen VARCHAR(50),
        language VARCHAR(20),
        timezone VARCHAR(50),
        isp VARCHAR(255),
        country VARCHAR(100),
        region VARCHAR(100),
        city VARCHAR(100),
        ip_version VARCHAR(20),
        platform VARCHAR(100),
        accuracy_type VARCHAR(20),
        accuracy_radius VARCHAR(50),
        click_count INT DEFAULT 1,
        unique_ips TEXT,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
}
