<?php

class VB_Booking_Handler
{

    /**
     * Constructor - Initialize hooks
     */
    public function __construct()
    {
        // Use official VikBooking hooks instead of database monitoring
        add_action('vikbooking_booking_conversion_tracking', array($this, 'handle_booking_conversion'), 10, 2);
        add_action('vikbooking_before_save_tracking_information', array($this, 'handle_tracking_step'), 10, 3);
        
        // Hook for Channel Manager bookings (only if VikChannelManager is installed)
        if (class_exists('VikChannelManager')) {
            add_action('onAfterSaveBookingHistoryVikBooking', array($this, 'handle_channel_manager_booking'), 10, 2);
        }
    }

    // ===========================================
    // OFFICIAL VIKBOOKING HOOK HANDLERS
    // ===========================================

    /**
     * Handle booking conversion (confirmed/paid booking)
     * This is the main hook for our integrations
     */
    public function handle_booking_conversion($booking, $trk_info_record)
    {
        error_log('VikBooking Integration: Booking conversion detected');

        // Extract booking ID and customer info
        $booking_id = isset($booking['id']) ? $booking['id'] : null;

        if (!$booking_id) {
            error_log('VikBooking Integration: No booking ID found');
            return;
        }

        // Get full booking data
        $booking_data = $this->get_booking_data($booking_id);

        if ($booking_data) {
            // Trigger all integrations
            $this->track_google_analytics($booking_data, 'booking_confirmed');
            $this->add_to_fluentcrm($booking_data);
        }
    }

    /**
     * Handle tracking steps during booking process
     * Can be used for additional analytics
     */
    public function handle_tracking_step($trk_info_record, $id_tracking, $trk)
    {
        // Optional: Track booking steps for enhanced analytics
        error_log('VikBooking Integration: Tracking step - ' . print_r($trk_info_record, true));
    }

    /**
     * Handle Channel Manager bookings
     * This hook is triggered when VikChannelManager saves a new OTA booking
     */
    public function handle_channel_manager_booking($history_record, $prev_booking)
    {
        // Check if VikChannelManager is installed
        if (!class_exists('VikChannelManager')) {
            error_log('VikBooking Integration: VikChannelManager not installed, skipping OTA booking handler');
            return;
        }

        // Check if this is a new OTA booking event
        if (!is_object($history_record) || $history_record->type !== 'NO') {
            return;
        }

        error_log('VikBooking Integration: Channel Manager booking detected - ID: ' . $history_record->idorder);

        // Get full booking data
        $booking_data = $this->get_booking_data($history_record->idorder);

        if (!$booking_data) {
            error_log('VikBooking Integration: Could not load booking data for ID: ' . $history_record->idorder);
            return;
        }

        // Send email notification if enabled
        if (get_option('vb_integration_cm_email_enabled', '0')) {
            $this->send_channel_manager_email($booking_data);
        }

        // Trigger other integrations (Google Analytics, FluentCRM, etc.)
        $this->track_google_analytics($booking_data, 'channel_manager_booking');
        $this->add_to_fluentcrm($booking_data);
    }

    // ===========================================
    // INTEGRATION METHODS
    // ===========================================

    /**
     * Track booking in Google Analytics
     */
    private function track_google_analytics($booking_data, $event_type)
    {
        // Implementazione per Google Analytics
        do_action('vb_integration_track_booking', $booking_data, $event_type);
    }

    /**
     * Add customer to FluentCRM
     */
    private function add_to_fluentcrm($booking_data)
    {
        // Implementazione per FluentCRM
        do_action('vb_integration_add_to_crm', $booking_data);
    }

    /**
     * Log action
     */
    private function log_action($booking_id, $action, $status, $data = null)
    {
        // Implementazione log
        error_log("VB Integration - Action: $action, Status: $status, Booking ID: $booking_id");
    }


    // ===========================================
    // DATA RETRIEVAL METHODS
    // ===========================================

    /**
     * Get booking data from VikBooking database
     */
    private function get_booking_data($booking_id)
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'vikbooking_orders';

        $booking = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE id = %d",
            $booking_id
        ), ARRAY_A);

        if ($booking) {
            // Get customer data
            $customer_table = $wpdb->prefix . 'vikbooking_customers_orders';
            $customer = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$customer_table} WHERE idorder = %d",
                $booking_id
            ), ARRAY_A);

            if ($customer) {
                $booking['customer'] = $customer;
            }
        }

        return $booking;
    }

    /**
     * Check if booking is from Channel Manager
     */
    private function is_channel_manager_booking($booking_data)
    {
        // Check if the booking has an external OTA ID
        if (!empty($booking_data['idorderota'])) {
            return true;
        }

        // Check if the channel field is populated (format: ChannelName_Source)
        if (!empty($booking_data['channel'])) {
            return true;
        }

        return false;
    }

    /**
     * Send email notification for channel manager bookings
     */
    private function send_channel_manager_email($booking_data)
    {
        $to = get_option('vb_integration_cm_email_address', get_option('admin_email'));
        $subject = 'Nuova Prenotazione da Channel Manager - ID: ' . ($booking_data['id'] ?? 'N/A');

        $message = "È arrivata una nuova prenotazione dal Channel Manager.\n\n";
        $message .= "ID Prenotazione: " . ($booking_data['id'] ?? 'N/A') . "\n";
        $message .= "Cliente: " . ($booking_data['custdata'] ?? 'N/A') . "\n";
        $message .= "Email: " . ($booking_data['custmail'] ?? 'N/A') . "\n";
        $message .= "Totale: " . ($booking_data['total'] ?? 'N/A') . " " . ($booking_data['chcurrency'] ?? '') . "\n";
        $message .= "Stato: " . ($booking_data['status'] ?? 'N/A') . "\n";
        $message .= "Check-in: " . ($booking_data['checkin'] ? date('d/m/Y', $booking_data['checkin']) : 'N/A') . "\n";
        $message .= "Check-out: " . ($booking_data['checkout'] ? date('d/m/Y', $booking_data['checkout']) : 'N/A') . "\n";
        $message .= "Canale: " . ($booking_data['channel'] ?? 'N/A') . "\n";
        $message .= "ID OTA: " . ($booking_data['idorderota'] ?? 'N/A') . "\n";

        wp_mail($to, $subject, $message);

        error_log('VB Integration - Sent Channel Manager email to ' . $to);
    }
}