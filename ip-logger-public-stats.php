<?php
// Exit if accessed directly
defined('ABSPATH') || exit;

add_shortcode('ip_logger_stats', 'iplogger_render_public_stats');

function iplogger_render_public_stats($atts) {
    $enabled = get_option('iplogger_public_stats_enabled');
    $password = get_option('iplogger_stats_password');
    $log_dir = WP_CONTENT_DIR . '/uploads/ip-logger/logs/';
    $today = date('Y-m-d');
    $log_file = $log_dir . "ip-log-{$today}.json";

    ob_start();

    if (!$enabled) {
        echo "<p>🔒 Public stats page is disabled by the admin.</p>";
        return ob_get_clean();
    }

    // Handle password form
    $entered = $_POST['iplogger_stats_password'] ?? '';
    $granted = ($password && $entered && $entered === $password);

    if (!$granted) {
        echo "<form method='post'>
                <p>This page is protected. Please enter the password:</p>
                <input type='password' name='iplogger_stats_password' />
                <input type='submit' value='Access' />
              </form>";
        return ob_get_clean();
    }

    if (!file_exists($log_file)) {
        echo "<p>No logs found for today.</p>";
        return ob_get_clean();
    }

    $logs = json_decode(file_get_contents($log_file), true);
    if (!is_array($logs)) {
        echo "<p>Corrupted log file.</p>";
        return ob_get_clean();
    }

    echo "<h2>Visitor Stats for {$today}</h2>";
    echo "<table border='1' cellpadding='8' style='border-collapse: collapse; width: 100%; font-size: 14px;'>
        <thead>
            <tr>
                <th>Time</th><th>Slug</th><th>IP</th><th>Country</th><th>City</th><th>State</th>
                <th>ISP</th><th>Platform</th><th>Accuracy</th><th>Radius</th>
                <th>Battery</th><th>Screen</th><th>Lang</th><th>Timezone</th>
            </tr>
        </thead><tbody>";

    foreach ($logs as $entry) {
        echo "<tr>
            <td>" . esc_html($entry['time']) . "</td>
            <td>" . esc_html($entry['slug']) . "</td>
            <td>" . esc_html($entry['ip']) . "</td>
            <td>" . esc_html($entry['country'] ?? 'N/A') . "</td>
            <td>" . esc_html($entry['city'] ?? 'N/A') . "</td>
            <td>" . esc_html($entry['region'] ?? 'N/A') . "</td>
            <td>" . esc_html($entry['org'] ?? 'N/A') . "</td>
            <td>" . esc_html($entry['platform'] ?? 'N/A') . "</td>
            <td>" . esc_html($entry['accuracy_type'] ?? 'N/A') . "</td>
            <td>" . esc_html($entry['accuracy_radius'] ?? 'N/A') . "</td>
            <td>" . esc_html($entry['battery'] ?? '-') . " (" . esc_html($entry['charging'] ?? '-') . ")</td>
            <td>" . esc_html($entry['screen'] ?? '-') . "</td>
            <td>" . esc_html($entry['language'] ?? '-') . "</td>
            <td>" . esc_html($entry['timezone'] ?? '-') . "</td>
        </tr>";
    }

    echo "</tbody></table>";

    return ob_get_clean();
}
