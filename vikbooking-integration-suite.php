<?php
/**
 * Plugin Name: VikBooking Integration Suite
 * Description: Integrazione con VikBooking per Google Analytics e FluentCRM
 * Version: 1.1.0
 * Author: Inhertzweb Agency
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('VB_INTEGRATION_VERSION', '1.1.0');
define('VB_INTEGRATION_PLUGIN_URL', plugin_dir_url(__FILE__));
define('VB_INTEGRATION_PLUGIN_PATH', plugin_dir_path(__FILE__));

// Check if VikBooking is active - Fixed version
function vb_integration_check_vikbooking() {
    // Include the plugin.php file to access is_plugin_active function
    if (!function_exists('is_plugin_active')) {
        include_once(ABSPATH . 'wp-admin/includes/plugin.php');
    }
    
    if (!is_plugin_active('vikbooking/vikbooking.php')) {
        add_action('admin_notices', 'vb_integration_vikbooking_missing_notice');
        return false;
    }
    return true;
}

function vb_integration_vikbooking_missing_notice() {
    echo '<div class="notice notice-error"><p>VikBooking Integration Suite richiede che VikBooking sia installato e attivato.</p></div>';
}

// Alternative check: verify if VikBooking class exists
function vb_integration_vikbooking_exists() {
    return class_exists('VikBooking') || defined('VIKBOOKING_PLUGIN_URI');
}

// Initialize plugin only if VikBooking is available
function vb_integration_init() {
    // Check if VikBooking is available
    if (!vb_integration_vikbooking_exists()) {
        // Only show admin notice if we're in admin area
        if (is_admin()) {
            add_action('admin_notices', 'vb_integration_vikbooking_missing_notice');
        }
        return;
    }
    
    // Include required files
    require_once VB_INTEGRATION_PLUGIN_PATH . 'includes/class-logger.php';
    require_once VB_INTEGRATION_PLUGIN_PATH . 'includes/class-booking-handler.php';
    require_once VB_INTEGRATION_PLUGIN_PATH . 'includes/class-google-analytics.php';
    require_once VB_INTEGRATION_PLUGIN_PATH . 'includes/class-fluentcrm-integration.php';
    
    // Initialize logger
    VB_Logger::init();
    
    // Initialize classes
    new VB_Booking_Handler();
    new VB_Google_Analytics_Integration();
    new VB_FluentCRM_Integration();
    
    // Initialize Mobile Widget
    require_once VB_INTEGRATION_PLUGIN_PATH . 'includes/class-mobile-widget.php';
    VB_Mobile_Widget::init();
}

// Hook initialization to plugins_loaded to ensure all plugins are loaded
add_action('plugins_loaded', 'vb_integration_init');

// Admin menu
if (is_admin()) {
    add_action('admin_menu', 'vb_integration_admin_menu');
}

function vb_integration_admin_menu() {
    // Main settings page
    add_options_page(
        'VikBooking Integration',
        'VikBooking Integration',
        'manage_options',
        'vb-integration',
        'vb_integration_admin_page'
    );
    
    // Logs page as separate menu item
    add_options_page(
        'VikBooking Integration - Log',
        'VB Integration Log',
        'manage_options',
        'vb-integration-logs',
        'vb_integration_logs_page'
    );
}

function vb_integration_admin_page() {
    include VB_INTEGRATION_PLUGIN_PATH . 'admin/admin-page.php';
}

function vb_integration_logs_page() {
    include VB_INTEGRATION_PLUGIN_PATH . 'admin/logs-page.php';
}

// Create tables on activation
register_activation_hook(__FILE__, 'vb_integration_create_tables');

function vb_integration_create_tables()
{
    // Include logger class
    require_once plugin_dir_path(__FILE__) . 'includes/class-logger.php';
    
    // Create logs table using logger
    VB_Logger::create_table();
    
    // Set default options
    add_option('vb_integration_ga_tracking_id', '');
    add_option('vb_integration_ga_api_secret', '');
    add_option('vb_integration_cm_email_enabled', '0');
    add_option('vb_integration_cm_email_address', get_option('admin_email'));
    add_option('vb_integration_fluentcrm_enabled', '1');

    // Mobile Widget Options
    add_option('vb_integration_mw_enabled', '0');
    add_option('vb_integration_mw_prenota_bg', '#faff4d');
    add_option('vb_integration_mw_prenota_text', '#000000');
    add_option('vb_integration_mw_chiama_bg', '#ffffff');
    add_option('vb_integration_mw_chiama_text', '#000000');
    add_option('vb_integration_mw_chiama_link', '');
    add_option('vb_integration_mw_offerta_bg', '#69b1e9');
    add_option('vb_integration_mw_offerta_text', '#ffffff');
    add_option('vb_integration_mw_offerta_link', '');
    add_option('vb_integration_mw_booking_url', '');
}

// Clean up on deactivation
register_deactivation_hook(__FILE__, 'vb_integration_deactivate');

function vb_integration_deactivate() {
    // Clear any scheduled hooks
    wp_clear_scheduled_hook('vb_integration_send_precheckin_emails');
}