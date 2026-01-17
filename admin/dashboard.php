<?php
// Exit if accessed directly
defined('ABSPATH') || exit;

function iplogger_render_dashboard() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'ip_logs';

    if (isset($_GET['view_slug'])) {
        /***************************************/
        /** RENDER THE DETAILED LOG VIEW      */
        /***************************************/
        $view_slug = sanitize_title($_GET['view_slug']);
        require_once IPLOGGER_PLUGIN_DIR . 'includes/class-ip-logger-list-table.php';

        echo '<div class="wrap">';
        echo '<h1>Logs for Slug: <code>' . esc_html($view_slug) . '</code></h1>';
        echo '<a href="?page=ip-logger-dashboard" class="button">&larr; Back to All Links</a>';

        if (isset($_GET['message']) && $_GET['message'] === '1') {
             echo '<div class="notice notice-success is-dismissible" style="margin-top:15px;"><p>Selected log entries have been deleted.</p></div>';
        }

        $log_table = new IP_Logger_List_Table(['slug' => $view_slug]);
        $log_table->prepare_items();
        
        echo '<form method="post">';
        echo '<input type="hidden" name="view_slug" value="' . esc_attr($view_slug) . '" />';
        
        // ✅ FIX: Changed the nonce action to match the WP_List_Table standard.
        wp_nonce_field('bulk-ip_logs'); 
        
        $log_table->display();
        echo '</form>';
        echo '</div>';

    } else {
        /***************************************/
        /** RENDER THE MAIN SHORTLINK SUMMARY VIEW */
        /***************************************/
        $shortlinks = get_option('iplogger_shortlinks', []);
        
        echo '<div class="wrap">';
        echo '<h1>IP Logger Dashboard</h1>';
        echo '<p>This dashboard shows a summary of all your created shortlinks. Click "View Logs" to see the detailed activity for each link.</p>';
        
        echo '<table class="wp-list-table widefat fixed striped table-view-list">';
        echo '<thead><tr><th>Shortlink Slug</th><th>Destination URL</th><th>Total Clicks</th><th>Unique Visitors</th><th>Actions</th></tr></thead>';
        echo '<tbody>';

        if (empty($shortlinks)) {
            echo '<tr><td colspan="5">No shortlinks have been created yet. <a href="?page=ip-logger-shortlinks">Create one now</a>.</td></tr>';
        } else {
            foreach ($shortlinks as $slug => $link) {
                $total_clicks = $wpdb->get_var($wpdb->prepare("SELECT COUNT(id) FROM $table_name WHERE slug = %s", $slug));
                $unique_visitors = $wpdb->get_var($wpdb->prepare("SELECT COUNT(DISTINCT ip) FROM $table_name WHERE slug = %s", $slug));

                echo '<tr>';
                echo '<td><strong><code>' . esc_html($slug) . '</code></strong></td>';
                echo '<td><a href="' . esc_url($link['url']) . '" target="_blank">' . esc_html(urldecode($link['url'])) . '</a></td>';
                echo '<td>' . esc_html($total_clicks) . '</td>';
                echo '<td>' . esc_html($unique_visitors) . '</td>';
                echo '<td><a href="?page=ip-logger-dashboard&view_slug=' . esc_attr($slug) . '" class="button button-primary">View Logs</a></td>';
                echo '</tr>';
            }
        }
        
        echo '</tbody></table>';
        echo '</div>';
    }
}