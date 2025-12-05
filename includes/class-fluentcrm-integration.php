<?php

use FluentCrm\App\Models\Subscriber;
use FluentCrm\App\Models\Tag;
use FluentCrm\App\Models\Lists;

class VB_FluentCRM_Integration
{

    public function __construct()
    {
        add_action('vb_integration_add_to_crm', array($this, 'add_contact_to_crm'));
        add_action('vb_integration_update_crm_status', array($this, 'update_contact_status'), 10, 2);
    }

    public function add_contact_to_crm($booking_data)
    {
        if (!$this->is_fluentcrm_active() || !get_option('vb_integration_fluentcrm_enabled', '1')) {
            return false;
        }

        $customer_data = $this->extract_customer_data($booking_data);

        if (!$customer_data['email']) {
            return false;
        }

        try {
            // Use FluentCRM native API to create or update contact
            $contact = FluentCrmApi('contacts')->createOrUpdate($customer_data, false, false);

            if ($contact) {
                // Add hotel tags using native method
                $this->add_hotel_tags($contact, $booking_data);

                // Add to lists using native method
                $this->add_to_lists($contact, $booking_data);

                error_log('VB Integration - Contact added/updated in FluentCRM: ' . $contact->email);

                return $contact;
            }
        } catch (Exception $e) {
            error_log('VB Integration - FluentCRM Error: ' . $e->getMessage());
        }

        return false;
    }

    public function update_contact_status($booking_data, $status)
    {
        if (!$this->is_fluentcrm_active()) {
            return false;
        }

        $email = $booking_data['customer']['email'] ?? $booking_data['custmail'] ?? '';

        if (!$email) {
            return false;
        }

        try {
            $contact = FluentCrmApi('contacts')->getContact($email);

            if ($contact) {
                // Update custom fields using native method
                $custom_fields = array(
                    'booking_status' => $status,
                    'last_booking_date' => current_time('Y-m-d H:i:s'),
                    'booking_id' => $booking_data['id'] ?? ''
                );

                $contact->syncCustomFieldValues($custom_fields, false);

                // Add status tag using native method
                $this->add_status_tag($contact, $status);

                error_log('VB Integration - Contact status updated in FluentCRM: ' . $contact->email);

                return true;
            }
        } catch (Exception $e) {
            error_log('VB Integration - FluentCRM Update Error: ' . $e->getMessage());
        }

        return false;
    }

    private function extract_customer_data($booking_data)
    {
        $customer = $booking_data['customer'] ?? array();

        $first_name = $customer['first_name'] ?? '';
        $last_name = $customer['last_name'] ?? '';
        $email = $customer['email'] ?? $booking_data['custmail'] ?? '';
        $phone = $customer['phone'] ?? $booking_data['phone'] ?? '';

        $address_line_1 = '';
        $city = '';
        $state = '';
        $postal_code = '';
        $country = '';

        // Extract from custdata if needed
        if (isset($booking_data['custdata'])) {
            $custdata = $booking_data['custdata'];

            if (strpos($custdata, 'Nome:') !== false && strpos($custdata, 'Cognome:') !== false) {
                preg_match('/Nome:\s*(.*?)(?:\s+Cognome:|$)/i', $custdata, $matches);
                if (!empty($matches[1]))
                    $first_name = trim($matches[1]);

                preg_match('/Cognome:\s*(.*?)(?:\s+e-Mail:|$)/i', $custdata, $matches);
                if (!empty($matches[1]))
                    $last_name = trim($matches[1]);

                preg_match('/e-Mail:\s*(.*?)(?:\s+Telefono:|$)/i', $custdata, $matches);
                if (!empty($matches[1]))
                    $email = trim($matches[1]);

                preg_match('/Telefono:\s*(.*?)(?:\s+Indirizzo:|$)/i', $custdata, $matches);
                if (!empty($matches[1]))
                    $phone = trim($matches[1]);

                preg_match('/Indirizzo:\s*(.*?)(?:\s+CAP:|$)/i', $custdata, $matches);
                if (!empty($matches[1]))
                    $address_line_1 = trim($matches[1]);

                preg_match('/CAP:\s*(.*?)(?:\s+Città:|$)/i', $custdata, $matches);
                if (!empty($matches[1]))
                    $postal_code = trim($matches[1]);

                preg_match('/Città:\s*(.*?)(?:\s+Nazione:|$)/i', $custdata, $matches);
                if (!empty($matches[1]))
                    $city = trim($matches[1]);

                preg_match('/Nazione:\s*(.*?)(?:\s+Provincia:|$)/i', $custdata, $matches);
                if (!empty($matches[1]))
                    $country = trim($matches[1]);

                preg_match('/Provincia:\s*(.*?)(?:\s+Ragione sociale:|$)/i', $custdata, $matches);
                if (!empty($matches[1]))
                    $state = trim($matches[1]);

            } elseif (!$first_name && !$last_name) {
                $name_parts = explode(' ', $custdata, 2);
                $first_name = $name_parts[0] ?? '';
                $last_name = $name_parts[1] ?? '';
            }
        }

        return array(
            'email' => $email,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'phone' => $phone,
            'address_line_1' => $address_line_1,
            'city' => $city,
            'state' => $state,
            'postal_code' => $postal_code,
            'country' => $country,
            'status' => 'subscribed',
            'source' => 'vikbooking',
            'custom_values' => array(
                'first_booking_date' => current_time('Y-m-d H:i:s'),
                'last_booking_amount' => floatval($booking_data['total'] ?? $booking_data['totpaid'] ?? 0)
            )
        );
    }

    private function add_hotel_tags($contact, $booking_data)
    {
        $tag_names = array('Hotel Guest', 'VikBooking Customer');

        // Add room type tag if available
        if (isset($booking_data['room_name'])) {
            $tag_names[] = 'Room: ' . $booking_data['room_name'];
        }

        $tag_ids = array();

        foreach ($tag_names as $tag_name) {
            // Find or create tag
            $tag = Tag::where('title', $tag_name)->first();
            
            if (!$tag) {
                $tag = Tag::create(array(
                    'title' => $tag_name,
                    'slug' => sanitize_title($tag_name)
                ));
            }

            if ($tag) {
                $tag_ids[] = $tag->id;
            }
        }

        // Attach tags using native method
        if ($tag_ids) {
            $contact->attachTags($tag_ids);
        }
    }

    private function add_status_tag($contact, $status)
    {
        $status_tags = array(
            'confirmed' => 'Booking Confirmed',
            'paid' => 'Booking Paid',
            'cancelled' => 'Booking Cancelled'
        );

        if (isset($status_tags[$status])) {
            $tag_name = $status_tags[$status];
            
            // Find or create tag
            $tag = Tag::where('title', $tag_name)->first();
            
            if (!$tag) {
                $tag = Tag::create(array(
                    'title' => $tag_name,
                    'slug' => sanitize_title($tag_name)
                ));
            }

            if ($tag) {
                $contact->attachTags(array($tag->id));
            }
        }
    }

    private function add_to_lists($contact, $booking_data)
    {
        $list_name = 'Ospiti Hotel';

        // Find or create list
        $list = Lists::where('title', $list_name)->first();
        
        if (!$list) {
            $list = Lists::create(array(
                'title' => $list_name,
                'slug' => sanitize_title($list_name)
            ));
        }

        if ($list) {
            $contact->attachLists(array($list->id));
        }
    }

    private function is_fluentcrm_active()
    {
        return function_exists('FluentCrmApi') && class_exists('FluentCrm\\App\\Models\\Subscriber');
    }
}