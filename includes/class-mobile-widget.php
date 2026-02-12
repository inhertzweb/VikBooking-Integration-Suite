<?php
/**
 * Class VB_Mobile_Widget
 * Handles the mobile booking widget logic.
 */

if (!defined('ABSPATH')) {
    exit;
}

class VB_Mobile_Widget {

    public static function init() {
        if (get_option('vb_integration_mw_enabled', '0') !== '1') {
            return;
        }

        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_assets']);
        add_action('wp_footer', [self::class, 'render_widget']);

        // AJAX for availability
        add_action('wp_ajax_vb_mw_get_availability', [self::class, 'ajax_get_availability']);
        add_action('wp_ajax_nopriv_vb_mw_get_availability', [self::class, 'ajax_get_availability']);
    }

    public static function enqueue_assets() {
        wp_enqueue_style('vb-mobile-widget-style', VB_INTEGRATION_PLUGIN_URL . 'assets/css/mobile-widget.css', [], VB_INTEGRATION_VERSION);
        wp_enqueue_script('vb-mobile-widget-js', VB_INTEGRATION_PLUGIN_URL . 'assets/js/mobile-widget.js', ['jquery'], VB_INTEGRATION_VERSION, true);

        // Pass settings to JS
        wp_localize_script('vb-mobile-widget-js', 'vbMWSettings', [
            'ajaxUrl'      => admin_url('admin-ajax.php'),
            'bookingUrl'   => self::get_booking_url(),
            'dateFormat'   => class_exists('VikBooking') ? VikBooking::getDateFormat() : '%d/%m/%Y',
        ]);
        
        // Dynamic CSS for colors
        $prenota_bg = get_option('vb_integration_mw_prenota_bg', '#faff4d');
        $prenota_text = get_option('vb_integration_mw_prenota_text', '#000000');
        $chiama_bg = get_option('vb_integration_mw_chiama_bg', '#ffffff');
        $chiama_text = get_option('vb_integration_mw_chiama_text', '#000000');
        $offerta_bg = get_option('vb_integration_mw_offerta_bg', '#69b1e9');
        $offerta_text = get_option('vb_integration_mw_offerta_text', '#ffffff');
        
        $custom_css = "
            :root {
                --vb-mw-prenota-bg: {$prenota_bg};
                --vb-mw-prenota-text: {$prenota_text};
                --vb-mw-chiama-bg: {$chiama_bg};
                --vb-mw-chiama-text: {$chiama_text};
                --vb-mw-offerta-bg: {$offerta_bg};
                --vb-mw-offerta-text: {$offerta_text};
            }
        ";
        wp_add_inline_style('vb-mobile-widget-style', $custom_css);
    }

    private static function get_booking_url() {
        $booking_url = get_option('vb_integration_mw_booking_url');
        if ($booking_url) {
            return $booking_url;
        }
        
        // Fallback to searching for a page with the shortcode if no URL is set
        // This is a bit resource intensive so it's better if the user sets it
        return home_url('/');
    }

    public static function render_widget() {
        $chiama_link = get_option('vb_integration_mw_chiama_link', '');
        $offerta_link = get_option('vb_integration_mw_offerta_link', '');
        ?>
        <div id="vb-mobile-widget" class="vb-mw-container">
            <div class="vb-mw-bar">
                <button id="vb-mw-open-calendar" class="vb-mw-btn vb-mw-prenota">
                    <svg viewBox="0 0 24 24" width="28" height="28" fill="currentColor"><path d="M19,7H11V14H3V5H1V20H3V17H21V20H23V11A4,4 0 0,0 19,7M7,13A3,3 0 0,0 10,10A3,3 0 0,0 7,7A3,3 0 0,0 4,10A3,3 0 0,0 7,13Z"/></svg>
                    <span>PRENOTA</span>
                </button>

                <a href="<?php echo esc_url($chiama_link ?: '#'); ?>" class="vb-mw-btn vb-mw-chiama">
                    <svg viewBox="0 0 24 24" width="28" height="28" fill="currentColor"><path d="M6.62,10.79C8.06,13.62 10.38,15.94 13.21,17.38L15.41,15.18C15.69,14.9 16.08,14.82 16.43,14.93C17.55,15.3 18.75,15.5 20,15.5A1,1 0 0,1 21,16.5V20A1,1 0 0,1 20,21A17,17 0 0,1 3,4A1,1 0 0,1 4,3H7.5A1,1 0 0,1 8.5,4C8.5,5.25 8.7,6.45 9.07,7.57C9.18,7.92 9.1,8.31 8.82,8.59L6.62,10.79Z"/></svg>
                    <span>CHIAMA</span>
                </a>

                <a href="<?php echo esc_url($offerta_link ?: '#'); ?>" class="vb-mw-btn vb-mw-offerta">
                    <svg viewBox="0 0 24 24" width="28" height="28" fill="currentColor"><path d="M20,8L12,13L4,8V6L12,11L20,6M20,4H4C2.89,4 2,4.89 2,6V18A2,2 0 0,0 4,20H20A2,2 0 0,0 22,18V6C22,4.89 21.1,4 20,4Z"/></svg>
                    <span>RICHIEDI OFFERTA</span>
                </a>
            </div>

            <!-- Booking Modal -->
            <div id="vb-mw-modal" class="vb-mw-modal" style="display: none;">
                <div class="vb-mw-modal-content">
                    <div class="vb-mw-modal-header">
                        <h3>Seleziona Date</h3>
                        <button id="vb-mw-modal-close" class="vb-mw-close">&times;</button>
                    </div>
                    <div class="vb-mw-modal-body">
                        <div id="vb-mw-calendar-container" class="vb-mw-calendar-scroll">
                            <!-- Calendar JS will inject here -->
                        </div>
                        <div class="vb-mw-guests-selector">
                            <div class="vb-mw-guest-row">
                                <span>Adulti</span>
                                <div class="vb-mw-counter">
                                    <button class="vb-mw-minus" data-target="adults">-</button>
                                    <input type="number" id="mw-adults" value="2" readonly>
                                    <button class="vb-mw-plus" data-target="adults">+</button>
                                </div>
                            </div>
                            <div class="vb-mw-guest-row">
                                <span>Bambini</span>
                                <div class="vb-mw-counter">
                                    <button class="vb-mw-minus" data-target="children">-</button>
                                    <input type="number" id="mw-children" value="0" readonly>
                                    <button class="vb-mw-plus" data-target="children">+</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="vb-mw-modal-footer">
                        <a href="#" id="vb-mw-confirm" class="vb-mw-confirm-btn" style="display:block; text-align:center; text-decoration:none;">CONFERMA E PRENOTA</a>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * AJAX handler to get booked dates across all rooms.
     * Returns an array of dates (YYYY-MM-DD) that are fully booked.
     */
    public static function ajax_get_availability() {
        if (!class_exists('VikBooking')) {
            wp_send_json_error('VikBooking not found');
        }

        $dbo = JFactory::getDbo();
        
        // 1. Get all active rooms and their total units
        $q = "SELECT `id`, `units` FROM `#__vikbooking_rooms` WHERE `avail`='1';";
        $dbo->setQuery($q);
        $rooms = $dbo->loadAssocList();
        
        if (!$rooms) {
            wp_send_json_success([]);
        }

        $total_units_available = 0;
        $room_ids = [];
        foreach ($rooms as $room) {
            $room_ids[] = (int)$room['id'];
            $total_units_available += (int)$room['units'];
        }

        // 2. Load busy records for the next 18 months
        $from_ts = time();
        $max_ts = strtotime('+18 months', $from_ts);
        
        // Load busy records using VikBooking helper
        $busy_records = VikBooking::loadBusyRecords($room_ids, $from_ts, $max_ts);

        // 3. Map busy units per day
        $daily_occupancy = [];
        
        foreach ($busy_records as $rid => $records) {
            foreach ($records as $record) {
                $checkin = (int)$record['checkin'];
                $realback = (int)$record['realback'];
                
                // Iterate through each day of the reservation
                // Using 86400 as day step, starting from check-in normalized to noon to avoid DST issues
                $current = mktime(12, 0, 0, date('n', $checkin), date('j', $checkin), date('Y', $checkin));
                $end = mktime(12, 0, 0, date('n', $realback), date('j', $realback), date('Y', $realback));
                
                while ($current < $end) {
                    $date_key = date('Y-m-d', $current);
                    if (!isset($daily_occupancy[$date_key])) {
                        $daily_occupancy[$date_key] = 0;
                    }
                    $daily_occupancy[$date_key]++;
                    $current += 86400;
                }
            }
        }

        // 4. Identify dates where all rooms units are occupied
        $fully_booked_dates = [];
        foreach ($daily_occupancy as $date => $occupied_units) {
            if ($occupied_units >= $total_units_available) {
                $fully_booked_dates[] = $date;
            }
        }

        // 5. Also include global structural closing dates
        $closing_dates = VikBooking::getClosingDates();
        foreach ($closing_dates as $cd) {
            $current = mktime(12, 0, 0, date('n', $cd['from']), date('j', $cd['from']), date('Y', $cd['from']));
            $end = mktime(12, 0, 0, date('n', $cd['to']), date('j', $cd['to']), date('Y', $cd['to']));
            
            while ($current <= $end) {
                $date_key = date('Y-m-d', $current);
                if (!in_array($date_key, $fully_booked_dates)) {
                    $fully_booked_dates[] = $date_key;
                }
                $current += 86400;
            }
        }

        wp_send_json_success($fully_booked_dates);
    }

    /**
     * Helper to adjust brightness of hex color for hover states/gradients
     */
    private static function adjust_brightness($hex, $steps) {
        $steps = max(-255, min(255, $steps));
        $hex = str_replace('#', '', $hex);
        if (strlen($hex) == 3) {
            $hex = str_repeat(substr($hex, 0, 1), 2) . str_repeat(substr($hex, 1, 1), 2) . str_repeat(substr($hex, 2, 1), 2);
        }
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        $r = max(0, min(255, $r + $steps));
        $g = max(0, min(255, $g + $steps));
        $b = max(0, min(255, $b + $steps));

        $r_hex = str_pad(dechex($r), 2, '0', STR_PAD_LEFT);
        $g_hex = str_pad(dechex($g), 2, '0', STR_PAD_LEFT);
        $b_hex = str_pad(dechex($b), 2, '0', STR_PAD_LEFT);

        return '#' . $r_hex . $g_hex . $b_hex;
    }
}
