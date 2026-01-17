<?php
// Exit if accessed directly
defined('ABSPATH') || exit;

// Add submenu under IP Logger
add_action('admin_menu', function () {
    add_submenu_page(
        'ip-logger-dashboard',
        'IP Logger Settings',
        'Settings',
        'manage_options',
        'ip-logger-settings',
        'iplogger_render_settings_page'
    );
});

// Register plugin settings
add_action('admin_init', function () {
    register_setting('iplogger_settings_group', 'iplogger_email_enabled');
    register_setting('iplogger_settings_group', 'iplogger_email_frequency');
    register_setting('iplogger_settings_group', 'iplogger_email_recipients');

    register_setting('iplogger_settings_group', 'iplogger_cleanup_enabled');
    register_setting('iplogger_settings_group', 'iplogger_log_retention_days');

    register_setting('iplogger_settings_group', 'iplogger_public_stats_enabled');
    register_setting('iplogger_settings_group', 'iplogger_stats_password');
});

// Settings page content
function iplogger_render_settings_page() {
    ob_start(); // Start output buffer
    ?>
    <div class="wrap iplogger-admin">
        <h1>IP Logger Settings</h1>
        <form method="post" action="options.php">
            <?php settings_fields('iplogger_settings_group'); ?>
            <?php do_settings_sections('iplogger_settings_group'); ?>

            <h2>Email Log Reports</h2>
            <table class="form-table">
                <tr>
                    <th scope="row">Enable Email Reports</th>
                    <td>
                        <input type="checkbox" name="iplogger_email_enabled" value="1" <?php checked(get_option('iplogger_email_enabled'), 1); ?>>
                        <label for="iplogger_email_enabled">Send logs by email</label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Frequency</th>
                    <td>
                        <select name="iplogger_email_frequency">
                            <option value="daily" <?php selected(get_option('iplogger_email_frequency'), 'daily'); ?>>Daily</option>
                            <option value="weekly" <?php selected(get_option('iplogger_email_frequency'), 'weekly'); ?>>Weekly</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Recipient Email(s)</th>
                    <td>
                        <input type="text" name="iplogger_email_recipients" value="<?php echo esc_attr(get_option('iplogger_email_recipients')); ?>" placeholder="you@example.com, team@example.com" size="50">
                        <p class="description">Separate multiple emails with commas.</p>
                    </td>
                </tr>
            </table>

            <h2>Log Cleanup</h2>
            <table class="form-table">
                <tr>
                    <th scope="row">Enable Auto-Delete</th>
                    <td>
                        <input type="checkbox" name="iplogger_cleanup_enabled" value="1" <?php checked(get_option('iplogger_cleanup_enabled'), 1); ?>>
                        <label for="iplogger_cleanup_enabled">Automatically delete old logs</label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Keep Logs for</th>
                    <td>
                        <select name="iplogger_log_retention_days">
                            <option value="7" <?php selected(get_option('iplogger_log_retention_days'), 7); ?>>7 days</option>
                            <option value="30" <?php selected(get_option('iplogger_log_retention_days'), 30); ?>>30 days</option>
                            <option value="60" <?php selected(get_option('iplogger_log_retention_days'), 60); ?>>60 days</option>
                            <option value="90" <?php selected(get_option('iplogger_log_retention_days'), 90); ?>>90 days</option>
                        </select>
                    </td>
                </tr>
            </table>

            <h2>Public Stats Page</h2>
            <table class="form-table">
                <tr>
                    <th scope="row">Enable Public Stats</th>
                    <td>
                        <input type="checkbox" name="iplogger_public_stats_enabled" value="1" <?php checked(get_option('iplogger_public_stats_enabled'), 1); ?>>
                        <label for="iplogger_public_stats_enabled">Allow public access to stats</label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Stats Page Password</th>
                    <td>
                        <input type="password" name="iplogger_stats_password" value="<?php echo esc_attr(get_option('iplogger_stats_password')); ?>" size="20">
                        <p class="description">Visitors must enter this password to view stats.</p>
                    </td>
                </tr>
            </table>

            <?php submit_button(); ?>
        </form>
    </div>
    <?php
    echo ob_get_clean(); // Safely flush buffer
}
