<?php
// Exit if accessed directly
defined('ABSPATH') || exit;

// Register the REST API endpoint with the secure permission check
add_action('rest_api_init', function () {
    register_rest_route('custom-logger/v1', '/log', [
        'methods' => 'POST',
        'callback' => 'iplogger_handle_log_request',
        'permission_callback' => function ($request) {
            return wp_verify_nonce($request->get_header('X-WP-Nonce'), 'wp_rest');
        },
    ]);
});

function iplogger_handle_log_request($request) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'ip_logs';
    $data = $request->get_json_params();
    $ip = sanitize_text_field($_SERVER['REMOTE_ADDR']);

    // --- Start Geo Data Fetching (with Caching) ---
    $geo_data = [];
    $transient_key = 'iplogger_geo_' . md5($ip);
    $cached_geo = get_transient($transient_key);

    if ($cached_geo !== false) {
        $geo_data = $cached_geo;
    } else {
        $response = wp_remote_get("https://ipapi.co/{$ip}/json/", ['timeout' => 10]);
        if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
            $geo_data = json_decode(wp_remote_retrieve_body($response), true);
            set_transient($transient_key, $geo_data, DAY_IN_SECONDS);
        }
    }
    // --- End Geo Data Fetching ---

    $user_agent = sanitize_text_field($data['userAgent'] ?? '');
    
    // Convert time to MySQL UTC format
    $log_time = current_time('mysql', 1);
    if (!empty($data['time'])) {
        try {
            $date_obj = new DateTime(sanitize_text_field($data['time']));
            $log_time = $date_obj->format('Y-m-d H:i:s');
        } catch (Exception $e) {
            // Fallback to current time on error
        }
    }
    
    // Consolidate all data for insertion
    $entry_data = [
        'log_time'        => $log_time,
        'slug'            => sanitize_title($data['slug'] ?? ''),
        'ip'              => $ip,
        'latitude'        => sanitize_text_field($data['latitude'] ?? 'N/A'),
        'longitude'       => sanitize_text_field($data['longitude'] ?? 'N/A'),
        'user_agent'      => $user_agent,
        'battery'         => sanitize_text_field($data['battery'] ?? 'N/A'),
        'charging'        => sanitize_text_field($data['charging'] ?? 'N/A'),
        'screen'          => sanitize_text_field($data['screen'] ?? 'N/A'),
        'language'        => sanitize_text_field($data['language'] ?? 'N/A'),
        'timezone'        => sanitize_text_field($data['timezone'] ?? 'N/A'),
        'isp'             => sanitize_text_field($geo_data['org'] ?? 'N/A'),
        'country'         => sanitize_text_field($geo_data['country_name'] ?? 'N/A'),
        'region'          => sanitize_text_field($geo_data['region'] ?? 'N/A'),
        'city'            => sanitize_text_field($geo_data['city'] ?? 'N/A'),
        'ip_version'      => sanitize_text_field($geo_data['version'] ?? 'N/A'),
        'platform'        => iplogger_detect_os($user_agent),
        'accuracy_type'   => sanitize_text_field($data['accuracyType'] ?? 'Geo'),
        'accuracy_radius' => sanitize_text_field($data['accuracyRadius'] ?? 'N/A'),
    ];

    // ✅ Always insert a new row for every click. This fixes the logging bug.
    $wpdb->insert($table_name, $entry_data);

    return rest_ensure_response(['status' => 'logged']);
}