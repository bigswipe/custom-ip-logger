<?php
// Exit if accessed directly
defined('ABSPATH') || exit;

$log_dir = WP_CONTENT_DIR . '/uploads/ip-logger/logs/';
$password = get_option('cip_stats_password', '');
$entered_password = $_POST['access_password'] ?? '';

if ($password && $entered_password !== $password) {
    echo "<h2 style='text-align:center;'>🔐 Enter Password to View Stats</h2>";
    echo "<form method='POST' style='text-align:center;margin-top:20px;'>
            <input type='password' name='access_password' placeholder='Enter password...' style='padding:10px;width:250px;' />
            <br><br><input type='submit' value='Access Stats' class='button'>
        </form>";
    return;
}

echo "<div class='wrap'><h1>📊 Visitor Stats</h1>";

if (!file_exists($log_dir)) {
    echo "<p>No logs directory found.</p>";
    return;
}

$files = glob($log_dir . 'ip-log-*.json');
rsort($files);

echo "<table class='widefat striped'><thead><tr>
        <th>Date</th><th>Visits</th><th>Unique IPs</th><th>Download</th>
    </tr></thead><tbody>";

foreach ($files as $file) {
    $filename = basename($file);
    $date = str_replace(['ip-log-', '.json'], '', $filename);
    $json = json_decode(file_get_contents($file), true);
    $visits = is_array($json) ? count($json) : 0;
    $unique_ips = is_array($json) ? count(array_unique(array_column($json, 'ip'))) : 0;

    echo "<tr>
        <td>" . esc_html($date) . "</td>
        <td>" . esc_html($visits) . "</td>
        <td>" . esc_html($unique_ips) . "</td>
        <td><a href='" . content_url("uploads/ip-logger/logs/{$filename}") . "' target='_blank'>Download</a></td>
    </tr>";
}

echo "</tbody></table></div>";
