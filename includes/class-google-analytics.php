<?php

class VB_Google_Analytics_Integration
{

    private $tracking_id;

    public function __construct()
    {
        $this->tracking_id = get_option('vb_integration_ga_tracking_id');

        if ($this->tracking_id) {
            add_action('vb_integration_track_booking', array($this, 'track_booking'), 10, 2);
            //add_action('wp_head', array($this, 'add_ga_code'));
        }
    }

    public function add_ga_code()
    {
        if (!$this->tracking_id) return;

        ?>
        <!-- Global site tag (gtag.js) - Google Analytics -->
        <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo esc_attr($this->tracking_id); ?>"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', '<?php echo esc_js($this->tracking_id); ?>');
        </script>
        <?php
    }

    public function track_booking($booking_data, $event_type)
    {
        if (!$this->tracking_id || !$booking_data) return;

        // Skip tracking for Channel Manager bookings (OTA)
        if ($this->is_channel_manager_booking($booking_data)) {
            error_log('VB Integration - Skipping GA tracking for Channel Manager booking ID: ' . ($booking_data['id'] ?? 'N/A'));
            return;
        }

        error_log('VB Integration - Tracking direct booking in GA - ID: ' . ($booking_data['id'] ?? 'N/A') . ', Event: ' . $event_type);

        $event_data = $this->prepare_event_data($booking_data, $event_type);

        // Send event to Google Analytics via Measurement Protocol
        $this->send_ga_event($event_data);

        // Add JavaScript for client-side tracking
        add_action('wp_footer', function () use ($event_data) {
            $this->add_client_tracking($event_data);
        });
    }

    private function is_channel_manager_booking($booking_data)
    {
        // Check if booking has OTA ID or channel field
        if (!empty($booking_data['idorderota']) || !empty($booking_data['channel'])) {
            return true;
        }

        return false;
    }

    private function prepare_event_data($booking_data, $event_type)
    {
        $total_amount = floatval($booking_data['total'] ?? $booking_data['totpaid'] ?? 0);
        $currency = $booking_data['chcurrency'] ?? 'EUR';
        $booking_id = $booking_data['id'] ?? '';
        $customer_name = $booking_data['custdata'] ?? 'Guest';
        
        // Extract room information if available
        $room_name = $booking_data['room_name'] ?? 'Hotel Room';
        $nights = 1;
        
        if (isset($booking_data['checkin']) && isset($booking_data['checkout'])) {
            $nights = max(1, ceil(($booking_data['checkout'] - $booking_data['checkin']) / 86400));
        }

        $event_data = array(
            'event_name' => $this->get_ga_event_name($event_type),
            'transaction_id' => $booking_id,
            'value' => $total_amount,
            'currency' => $currency,
            'items' => array(
                array(
                    'item_id' => $booking_id,
                    'item_name' => $room_name,
                    'item_category' => 'Hotel Booking',
                    'item_category2' => 'Direct Booking',
                    'quantity' => $nights,
                    'price' => $total_amount / $nights
                )
            )
        );

        // Add customer info for purchase events
        if ($event_type === 'booking_confirmed' || $event_type === 'payment_completed') {
            $event_data['customer_name'] = $customer_name;
            $event_data['customer_email'] = $booking_data['custmail'] ?? '';
        }

        return $event_data;
    }

    private function get_ga_event_name($event_type)
    {
        switch ($event_type) {
            case 'new_booking':
                return 'begin_checkout';
            case 'booking_confirmed':
                return 'purchase';
            case 'payment_completed':
                return 'purchase';
            case 'channel_manager_booking':
                // This should never be called since we skip CM bookings
                return 'ota_booking';
            default:
                return 'booking_event';
        }
    }

    private function send_ga_event($event_data)
    {
        // Send event via Measurement Protocol API (GA4)
        $measurement_id = $this->tracking_id;
        $api_secret = $this->get_api_secret();

        if (!$api_secret) {
            error_log('VB Integration - GA API secret not configured');
            return;
        }

        $url = 'https://www.google-analytics.com/mp/collect';

        $payload = array(
            'client_id' => $this->get_client_id(),
            'events' => array($event_data)
        );

        $args = array(
            'body' => json_encode($payload),
            'headers' => array(
                'Content-Type' => 'application/json'
            ),
            'timeout' => 10
        );

        $response = wp_remote_post($url . '?measurement_id=' . $measurement_id . '&api_secret=' . $api_secret, $args);

        if (is_wp_error($response)) {
            error_log('VB Integration - GA tracking error: ' . $response->get_error_message());
        } else {
            error_log('VB Integration - GA event sent successfully: ' . $event_data['event_name']);
        }
    }

    private function add_client_tracking($event_data)
    {
        ?>
        <script>
            if (typeof gtag !== 'undefined') {
                gtag('event', '<?php echo esc_js($event_data['event_name']); ?>', {
                    'transaction_id': '<?php echo esc_js($event_data['transaction_id']); ?>',
                    'value': <?php echo floatval($event_data['value']); ?>,
                    'currency': '<?php echo esc_js($event_data['currency']); ?>',
                    'items': <?php echo json_encode($event_data['items']); ?>
                });
                console.log('VB Integration - GA event tracked:', '<?php echo esc_js($event_data['event_name']); ?>');
            }
        </script>
        <?php
    }

    private function get_client_id()
    {
        // Generate or retrieve a unique client ID
        $client_id = get_option('vb_integration_ga_client_id');
        if (!$client_id) {
            $client_id = wp_generate_uuid4();
            update_option('vb_integration_ga_client_id', $client_id);
        }
        return $client_id;
    }

    private function get_api_secret()
    {
        // Return Google Analytics API secret
        return get_option('vb_integration_ga_api_secret', '');
    }
}