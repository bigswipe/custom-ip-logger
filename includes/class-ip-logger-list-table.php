<?php
// Exit if accessed directly
defined('ABSPATH') || exit;

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class IP_Logger_List_Table extends WP_List_Table {

    private $view_slug = '';
    private $table_name = '';

    public function __construct($args = []) {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'ip_logs'; 
        if (!empty($args['slug'])) {
            $this->view_slug = sanitize_title($args['slug']);
        }
        parent::__construct([
            'singular' => 'Log Entry',
            'plural'   => 'ip_logs',
            'ajax'     => false
        ]);
    }

    public function get_columns() { return [ 'cb' => '<input type="checkbox" />', 'log_time' => 'Date/Time', 'ip' => 'IP Address', 'isp' => 'ISP', 'country' => 'Country', 'city' => 'City', 'platform' => 'Platform', 'device' => 'Device', 'notes' => 'Notes', 'smart_data' => 'Smart Data' ]; }
    protected function get_sortable_columns() { return [ 'log_time' => ['log_time', false], 'ip' => ['ip', false], 'country' => ['country', false], ]; }
    public function column_notes($item) { $note = esc_html($item->notes ?? ''); $display_note = !empty($note) ? nl2br($note) : '<span style="color:#999;">Click to add a note</span>'; return '<div class="iplogger-note-container" data-log-id="' . esc_attr($item->id) . '"><div class="note-display">' . $display_note . '</div><div class="note-edit" style="display:none;"><textarea rows="3" style="width:100%;">' . $note . '</textarea><div style="margin-top:5px;"><button type="button" class="button button-primary note-save">Save</button><button type="button" class="button note-cancel">Cancel</button><span class="spinner" style="float:none;vertical-align:middle;"></span></div></div></div>'; }
    public function get_bulk_actions() { return ['bulk-delete' => 'Delete']; }
    public function column_cb($item) { return sprintf('<input type="checkbox" name="log_id[]" value="%s" />', esc_attr($item->id)); }
    public function column_device($item) { return esc_html(iplogger_detect_device($item->user_agent)); }
    public function column_smart_data($item) {
    $modal_id = 'iplogger-modal-' . esc_attr($item->id);
    $map_id = 'iplogger-map-' . esc_attr($item->id);
    
    // Data for the map
    $lat = esc_attr($item->latitude ?? 'N/A');
    $lon = esc_attr($item->longitude ?? 'N/A');

    $button = '<button type="button" class="button button-secondary open-smart-data-modal" data-modal-id="' . $modal_id . '">View Data</button>';
    
    // New modal content structure
    $modal_content = '<div id="' . $modal_id . '" class="iplogger-modal" style="display:none;">
        <div class="iplogger-modal-content">
            <span class="iplogger-close" style="float:right;cursor:pointer;font-size:28px;line-height:1;">&times;</span>
            <h2>Smart Data</h2>
            
            <div class="iplogger-modal-body">
                <div class="iplogger-data-table-container">
                    <table class="iplogger-smart-data-table">
                        <tr><td>IP Version:</td><td>' . esc_html($item->ip_version ?? '—') . '</td></tr>
                        <tr><td>Platform:</td><td>' . esc_html($item->platform ?? '—') . '</td></tr>
                        <tr><td>Language:</td><td>' . esc_html($item->language ?? '—') . '</td></tr>
                        <tr><td>Timezone:</td><td>' . esc_html($item->timezone ?? '—') . '</td></tr>
                        <tr><td>Latitude:</td><td>' . esc_html($lat) . '</td></tr>
                        <tr><td>Longitude:</td><td>' . esc_html($lon) . '</td></tr>
                        <tr><td>Accuracy Radius:</td><td>' . esc_html($item->accuracy_radius ?? '—') . '</td></tr>
                        <tr><td>Battery:</td><td>' . esc_html($item->battery ?? '—') . ' (' . esc_html($item->charging ?? '—') . ')</td></tr>
                        <tr><td>Screen Res:</td><td>' . esc_html($item->screen ?? '—') . '</td></tr>
                        <tr><td>User Agent:</td><td style="word-break:break-all;">' . esc_html($item->user_agent ?? '—') . '</td></tr>
                    </table>
                </div>
                <div class="iplogger-map-container" id="' . $map_id . '" data-lat="' . $lat . '" data-lon="' . $lon . '">
                    </div>
            </div>

        </div>
    </div>';
    
    return $button . $modal_content;
}
    public function column_ip($item) { $delete_url = sprintf( '?page=%s&action=delete&log_id=%s&view_slug=%s&_wpnonce=%s', esc_attr($_REQUEST['page']), esc_attr($item->id), esc_attr($this->view_slug), wp_create_nonce('iplogger_delete_log') ); $actions = [ 'delete' => '<a href="' . $delete_url . '" onclick="return confirm(\'Are you sure you want to delete this log?\');">Delete</a>' ]; return '<strong>' . esc_html($item->ip) . '</strong>' . $this->row_actions($actions); }
    public function column_default($item, $column_name) { if ($column_name === 'log_time') { try { $utc_time = new DateTime($item->log_time, new DateTimeZone('UTC')); $utc_time->setTimezone(wp_timezone()); return esc_html($utc_time->format('d-m-Y h:i A')); } catch (Exception $e) { return esc_html($item->log_time); } } if (property_exists($item, $column_name)) { return esc_html($item->$column_name); } return ''; }

    public function process_bulk_action() {
        global $wpdb;
        $redirect_url = admin_url('admin.php?page=ip-logger-dashboard');
        if (!empty($_REQUEST['view_slug'])) {
            $redirect_url = add_query_arg('view_slug', sanitize_title($_REQUEST['view_slug']), $redirect_url);
        }

        // Handle single row deletion
        if ('delete' === $this->current_action() && isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'iplogger_delete_log')) {
            $wpdb->delete($this->table_name, ['id' => intval($_GET['log_id'])], ['%d']);
            wp_redirect(add_query_arg('message', '1', $redirect_url));
            exit;
        }
        
        // ✅ FIX: The nonce action now matches the one created in dashboard.php ('bulk-ip_logs').
        if ('bulk-delete' === $this->current_action() && check_admin_referer('bulk-ip_logs')) {
             if (!empty($_POST['log_id']) && is_array($_POST['log_id'])) {
                $log_ids_sql = implode(',', array_map('intval', $_POST['log_id']));
                $wpdb->query("DELETE FROM {$this->table_name} WHERE id IN ($log_ids_sql)");
                wp_redirect(add_query_arg('message', '1', $redirect_url));
                exit;
            }
        }
    }

    public function prepare_items() {
        $this->process_bulk_action();
        global $wpdb;
        
        $this->_column_headers = [$this->get_columns(), [], $this->get_sortable_columns()];
        $per_page = 20;
        $current_page = $this->get_pagenum();
        
        $orderby = !empty($_REQUEST['orderby']) ? sanitize_sql_orderby($_REQUEST['orderby']) : 'log_time';
        $order   = !empty($_REQUEST['order']) && in_array(strtoupper($_REQUEST['order']), ['ASC', 'DESC']) ? $_REQUEST['order'] : 'DESC';
        
        $base_query = "FROM {$this->table_name}";
        $params = [];
        if (!empty($this->view_slug)) {
            $base_query .= " WHERE slug = %s";
            $params[] = $this->view_slug;
        }
        $total_items = $wpdb->get_var($wpdb->prepare("SELECT COUNT(id) " . $base_query, $params));
        $this->set_pagination_args([ 'total_items' => $total_items, 'per_page' => $per_page ]);
        $offset = ($current_page - 1) * $per_page;
        $query_with_pagination = $base_query . " ORDER BY $orderby $order LIMIT %d OFFSET %d";
        $params[] = $per_page;
        $params[] = $offset;
        $this->items = $wpdb->get_results($wpdb->prepare("SELECT * " . $query_with_pagination, $params));
    }
}