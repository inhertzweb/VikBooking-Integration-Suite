<?php

class VB_Logger
{
    private static $table_name = null;

    /**
     * Initialize logger and create table if needed
     */
    public static function init()
    {
        global $wpdb;
        self::$table_name = $wpdb->prefix . 'vb_integration_logs';
    }

    /**
     * Create logs table
     */
    public static function create_table()
    {
        global $wpdb;
        self::init();

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS " . self::$table_name . " (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            log_time datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            log_type varchar(50) NOT NULL,
            booking_id bigint(20) DEFAULT NULL,
            message text NOT NULL,
            data longtext DEFAULT NULL,
            PRIMARY KEY  (id),
            KEY log_type (log_type),
            KEY log_time (log_time),
            KEY booking_id (booking_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Log an event
     */
    public static function log($type, $message, $booking_id = null, $data = null)
    {
        global $wpdb;
        self::init();

        $wpdb->insert(
            self::$table_name,
            array(
                'log_type' => $type,
                'booking_id' => $booking_id,
                'message' => $message,
                'data' => is_array($data) ? json_encode($data) : $data,
                'log_time' => current_time('mysql')
            ),
            array('%s', '%d', '%s', '%s', '%s')
        );

        // Also log to error_log for debugging
        error_log("VB Integration [{$type}]: {$message}" . ($booking_id ? " (Booking ID: {$booking_id})" : ""));
    }

    /**
     * Get recent logs
     */
    public static function get_logs($limit = 50, $type = null)
    {
        global $wpdb;
        self::init();

        $where = '';
        if ($type) {
            $where = $wpdb->prepare(" WHERE log_type = %s", $type);
        }

        $query = "SELECT * FROM " . self::$table_name . $where . " ORDER BY log_time DESC LIMIT %d";
        
        return $wpdb->get_results($wpdb->prepare($query, $limit));
    }

    /**
     * Clear old logs (older than X days)
     */
    public static function clear_old_logs($days = 30)
    {
        global $wpdb;
        self::init();

        $wpdb->query($wpdb->prepare(
            "DELETE FROM " . self::$table_name . " WHERE log_time < DATE_SUB(NOW(), INTERVAL %d DAY)",
            $days
        ));
    }

    /**
     * Get log statistics
     */
    public static function get_stats()
    {
        global $wpdb;
        self::init();

        $stats = $wpdb->get_results(
            "SELECT log_type, COUNT(*) as count 
            FROM " . self::$table_name . " 
            WHERE log_time > DATE_SUB(NOW(), INTERVAL 7 DAY)
            GROUP BY log_type"
        );

        $result = array();
        foreach ($stats as $stat) {
            $result[$stat->log_type] = $stat->count;
        }

        return $result;
    }

    /**
     * Clear all logs
     */
    public static function clear_all_logs()
    {
        global $wpdb;
        self::init();

        $wpdb->query("TRUNCATE TABLE " . self::$table_name);
    }
}
