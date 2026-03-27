<?php
/**
 * Class VB_AB_Testing_Popup
 * Handles the frontend A/B Testing Offer popup delivery, and tracks impressions/clicks via AJAX.
 */

if (!defined('ABSPATH')) {
    exit;
}

class VB_AB_Testing_Popup {

    public static function init() {
        add_action('wp_enqueue_scripts', [self::class, 'enqueue_assets']);
        add_action('wp_footer', [self::class, 'render_popup_container']);
        
        // AJAX Endpoints
        add_action('wp_ajax_vb_track_offer_view', [self::class, 'ajax_track_view']);
        add_action('wp_ajax_nopriv_vb_track_offer_view', [self::class, 'ajax_track_view']);
        
        add_action('wp_ajax_vb_track_offer_click', [self::class, 'ajax_track_click']);
        add_action('wp_ajax_nopriv_vb_track_offer_click', [self::class, 'ajax_track_click']);
    }

    public static function enqueue_assets() {
        // Only load on frontend if there are active offers
        $active_offers = self::get_active_offers();
        if (empty($active_offers)) {
            return;
        }

        wp_enqueue_style('vb-offer-popup-style', VB_INTEGRATION_PLUGIN_URL . 'assets/css/offer-popup.css', [], VB_INTEGRATION_VERSION);
        wp_enqueue_script('vb-offer-popup-js', VB_INTEGRATION_PLUGIN_URL . 'assets/js/offer-popup.js', ['jquery'], VB_INTEGRATION_VERSION, true);

        wp_localize_script('vb-offer-popup-js', 'vbOfferSettings', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'offers'  => $active_offers,
            'labels'  => [
                'copyBtn' => __('Copia e Prenota', 'vikbooking-integration-suite'),
                'copied'  => __('Codice Copiato!', 'vikbooking-integration-suite'),
            ]
        ]);
    }

    private static function get_active_offers() {
        $args = [
            'post_type'   => VB_Offers_Manager::POST_TYPE,
            'post_status' => 'publish', // or any if we didn't add standard UI
            'posts_per_page' => -1,
            'meta_query'  => [
                [
                    'key'   => '_vb_offer_active_ab',
                    'value' => 'yes',
                ]
            ]
        ];
        
        // Since we registered the CPT as public=false, we might need to bypass normal post_status checks if they are drafted,
        // but by default WP saves them as publish even if not public.
        $query = new WP_Query($args);
        
        $offers = [];
        if ($query->have_posts()) {
            foreach ($query->posts as $post) {
                $coupon_code = get_post_meta($post->ID, '_vb_offer_coupon_code', true);
                $discount_value = get_post_meta($post->ID, '_vb_offer_discount_value', true);
                $discount_type = get_post_meta($post->ID, '_vb_offer_discount_type', true);
                $custom_url = get_post_meta($post->ID, '_vb_offer_custom_url', true);
                
                $offers[] = [
                    'id'          => $post->ID,
                    'title'       => get_the_title($post->ID),
                    'content'     => apply_filters('the_content', $post->post_content),
                    'thumbnail'   => get_the_post_thumbnail_url($post->ID, 'medium'),
                    'coupon'      => $coupon_code,
                    'is_percent'  => $discount_type === '1',
                    'amount'      => $discount_value,
                    'custom_url'  => $custom_url
                ];
            }
            wp_reset_postdata();
        }
        
        return $offers;
    }

    public static function render_popup_container() {
        $active_offers = self::get_active_offers();
        if (empty($active_offers)) {
            return;
        }
        // Container injected here. JS will handle displaying the right offer safely.
        echo '<div id="vb-offer-popup-container" style="display:none;"></div>';
    }

    public static function ajax_track_view() {
        $offer_id = isset($_POST['offer_id']) ? intval($_POST['offer_id']) : 0;
        if ($offer_id > 0) {
            $views = (int) get_post_meta($offer_id, '_vb_offer_views', true);
            update_post_meta($offer_id, '_vb_offer_views', $views + 1);
            wp_send_json_success();
        }
        wp_send_json_error();
    }

    public static function ajax_track_click() {
        $offer_id = isset($_POST['offer_id']) ? intval($_POST['offer_id']) : 0;
        if ($offer_id > 0) {
            $clicks = (int) get_post_meta($offer_id, '_vb_offer_clicks', true);
            update_post_meta($offer_id, '_vb_offer_clicks', $clicks + 1);
            wp_send_json_success();
        }
        wp_send_json_error();
    }
}
