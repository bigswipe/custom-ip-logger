<?php
/*
Plugin Name: Custom IP Logger
Description: Logs IP, GPS, Battery, Device Info, and shows logs in admin.
Version: 5.1
Author: Venkatesh Kumar
*/

// Exit if accessed directly
defined('ABSPATH') || exit;

// Define plugin constants
define('IPLOGGER_PLUGIN_FILE', __FILE__);
define('IPLOGGER_PLUGIN_DIR', plugin_dir_path(IPLOGGER_PLUGIN_FILE));
define('IPLOGGER_PLUGIN_URL', plugin_dir_url(IPLOGGER_PLUGIN_FILE));

// Load essential functionality
require_once IPLOGGER_PLUGIN_DIR . 'includes/database.php';
require_once IPLOGGER_PLUGIN_DIR . 'includes/logger.php';
require_once IPLOGGER_PLUGIN_DIR . 'includes/redirect-handler.php';
require_once IPLOGGER_PLUGIN_DIR . 'includes/utils.php';
require_once IPLOGGER_PLUGIN_DIR . 'ip-logger-public-stats.php';

// Admin menu
add_action('admin_menu', 'iplogger_admin_setup');
function iplogger_admin_setup() {
    add_menu_page('IP Logger', 'IP Logger', 'manage_options', 'ip-logger-dashboard', 'iplogger_render_dashboard', 'dashicons-location-alt', 26);
    add_submenu_page('ip-logger-dashboard', 'Shortlinks', 'Shortlinks', 'manage_options', 'ip-logger-shortlinks', 'iplogger_render_shortlinks_page');
    add_submenu_page('ip-logger-dashboard', 'IP Logger Settings', 'Settings', 'manage_options', 'ip-logger-settings', 'iplogger_render_settings_page');

    require_once IPLOGGER_PLUGIN_DIR . 'admin/dashboard.php';
    require_once IPLOGGER_PLUGIN_DIR . 'admin/shortlinks.php';
    require_once IPLOGGER_PLUGIN_DIR . 'admin/settings.php';
}

// Enqueue admin assets
add_action('admin_enqueue_scripts', 'iplogger_enqueue_admin_assets');
function iplogger_enqueue_admin_assets($hook_suffix) {
    // We will only load our assets on our plugin's pages to avoid conflicts.
    if (strpos($hook_suffix, 'ip-logger') === false) {
        return;
    }
    
        // START: ADD THESE TWO LINES
    wp_enqueue_style('leaflet-css', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', [], '1.9.4');
    wp_enqueue_script('leaflet-js', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', [], '1.9.4', true);
    // END: ADD THESE TWO LINES
    
    // ✅ FINAL VERSION: Use filemtime() for production-safe cache-busting.
    wp_enqueue_style('iplogger-admin-style', IPLOGGER_PLUGIN_URL . 'assets/css/admin-style.css', [], filemtime(IPLOGGER_PLUGIN_DIR . 'assets/css/admin-style.css'));
    wp_enqueue_script('iplogger-admin-script', IPLOGGER_PLUGIN_URL . 'assets/js/admin-script.js', ['jquery'], filemtime(IPLOGGER_PLUGIN_DIR . 'assets/js/admin-script.js'), true);

    // Reliably pass the AJAX URL to our script.
    wp_localize_script(
        'iplogger-admin-script',
        'iplogger_ajax_object',
        array('ajax_url' => admin_url('admin-ajax.php'))
    );

    // ✅ NEW: Reliably pass the AJAX URL to our script. This is the modern WordPress standard.
    wp_localize_script(
        'iplogger-admin-script',
        'iplogger_ajax_object',
        array('ajax_url' => admin_url('admin-ajax.php'))
    );
}

// Plugin activation/deactivation hooks...
register_activation_hook(__FILE__, 'iplogger_activate');
function iplogger_activate() {
    iplogger_create_database_table();
    if (!wp_next_scheduled('iplogger_daily_event')) {
        wp_schedule_event(time(), 'daily', 'iplogger_daily_event');
    }
}
register_deactivation_hook(__FILE__, 'iplogger_deactivate');
function iplogger_deactivate() {
    wp_clear_scheduled_hook('iplogger_daily_event');
}
add_action('iplogger_daily_event', 'iplogger_handle_cron_task');
function iplogger_handle_cron_task() {}


// AJAX handler for saving notes...
add_action('wp_ajax_iplogger_save_note', function() {
    check_ajax_referer('iplogger_save_note_nonce', 'nonce');
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Permission denied.'], 403);
    }
    $log_id = isset($_POST['log_id']) ? intval($_POST['log_id']) : 0;
    $note_text = isset($_POST['note_text']) ? sanitize_textarea_field(stripslashes($_POST['note_text'])) : '';
    if (empty($log_id)) {
        wp_send_json_error(['message' => 'Invalid Log ID.'], 400);
    }
    global $wpdb;
    $table_name = $wpdb->prefix . 'ip_logs';
    $result = $wpdb->update($table_name, ['notes' => $note_text], ['id' => $log_id], ['%s'], ['%d']);
    if ($result === false) {
        wp_send_json_error(['message' => 'Failed to update note in the database.']);
    } else {
        $display_note = !empty($note_text) ? nl2br(esc_html($note_text)) : '<span style="color:#999;">Click to add a note</span>';
        wp_send_json_success(['message' => 'Note saved!', 'display_note' => $display_note]);
    }
});

// Nonce field for the AJAX request...
add_action('admin_footer', function() {
    // ✅ FIX: We only need to check if we are on an admin page, and if our script is loaded.
    if (is_admin() && wp_script_is('iplogger-admin-script', 'enqueued')) {
        wp_nonce_field('iplogger_save_note_nonce', 'iplogger_save_note_nonce');
    }
});