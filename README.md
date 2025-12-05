# VikBooking Integration Suite

A comprehensive WordPress plugin that integrates VikBooking with Google Analytics 4, FluentCRM, and provides advanced booking management features including Channel Manager (OTA) support.

## Features

### 🏨 Channel Manager Integration
- **OTA Booking Detection**: Automatically detects bookings from Booking.com, Airbnb, and other OTA platforms
- **Email Notifications**: Receive instant email alerts for new Channel Manager bookings
- **Conditional Activation**: Only activates when VikChannelManager is installed
- **Smart Filtering**: Distinguishes between direct bookings and OTA bookings

### 📊 Google Analytics 4 Integration
- **Direct Booking Tracking**: Tracks only direct website bookings (excludes OTA)
- **Enhanced Ecommerce Events**: Implements GA4 ecommerce tracking with proper event structure
- **Server-Side Tracking**: Uses Measurement Protocol API for reliable tracking
- **Detailed Event Data**: Includes room name, number of nights, pricing, and customer information
- **Client & Server Tracking**: Dual tracking approach for maximum reliability

### 👥 FluentCRM Integration
- **Optimized Performance**: Uses native FluentCRM methods for better performance
- **Automatic Contact Creation**: Creates or updates contacts automatically
- **Smart Tagging**: Applies hotel-specific tags (Hotel Guest, VikBooking Customer, Room types)
- **List Management**: Automatically adds guests to "Ospiti Hotel" list
- **Custom Fields**: Tracks booking source, dates, and amounts
- **Source Tracking**: Identifies contacts from VikBooking

### 📝 Advanced Logging System
- **Structured Logging**: Database-backed logging system with categorization
- **Log Viewer**: Dedicated admin page with filters and search
- **Event Types**: 
  - `booking_direct` - Direct website bookings
  - `booking_ota` - Channel Manager bookings
  - `ga_tracking` - Google Analytics events
  - `fluentcrm` - CRM operations
  - `email` - Email notifications
  - `error` - Error tracking
- **Statistics Dashboard**: 7-day activity overview
- **Data Export**: View detailed JSON data for each log entry
- **Auto-Cleanup**: Configurable old log removal

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- **VikBooking** (required)
- **VikChannelManager** (optional, for OTA features)
- **FluentCRM** (optional, for CRM features)

## Installation

1. Upload the `vikbooking-integration-suite` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to **Settings → VikBooking Integration** to configure

## Configuration

### Google Analytics 4 Setup

1. Go to **Settings → VikBooking Integration**
2. Enter your **GA4 Measurement ID** (format: G-XXXXXXXXXX)
3. Enter your **GA4 API Secret** (from GA4 Admin → Data Streams → Measurement Protocol)
4. Save settings

The plugin will now track all direct bookings in Google Analytics.

### FluentCRM Setup

1. Install and activate FluentCRM
2. Go to **Settings → VikBooking Integration**
3. Enable **FluentCRM Integration**
4. Save settings

Contacts will be automatically created/updated with each booking.

### Channel Manager Email Notifications

1. Ensure **VikChannelManager** is installed and active
2. Go to **Settings → VikBooking Integration**
3. Enable **Channel Manager Email Notifications**
4. Enter the email address to receive notifications
5. Save settings

You'll now receive emails for every OTA booking.

## Usage

### Viewing Logs

Access the log viewer at **Settings → VB Integration Log**

**Features:**
- Filter by event type
- View last 100 events
- See 7-day statistics
- Expand log entries to view detailed JSON data
- Clear all logs with one click

### Hooks & Filters

The plugin uses official VikBooking hooks:

```php
// Direct booking confirmed
do_action('vikbooking_booking_conversion_tracking', $booking, $trk_info_record);

// Channel Manager booking (requires VikChannelManager)
do_action('onAfterSaveBookingHistoryVikBooking', $history_record, $prev_booking);
```

### Custom Integration

You can hook into the plugin's actions:

```php
// Track custom events in Google Analytics
do_action('vb_integration_track_booking', $booking_data, 'custom_event_type');

// Add contacts to FluentCRM
do_action('vb_integration_add_to_crm', $booking_data);

// Update contact status
do_action('vb_integration_update_crm_status', $booking_data, 'confirmed');
```

### Logging Custom Events

```php
// Log custom events
VB_Logger::log('custom_type', 'Your message', $booking_id, $additional_data);

// Get recent logs
$logs = VB_Logger::get_logs(50, 'booking_direct');

// Get statistics
$stats = VB_Logger::get_stats();

// Clear old logs (30 days)
VB_Logger::clear_old_logs(30);
```

## File Structure

```
vikbooking-integration-suite/
├── admin/
│   ├── admin-page.php          # Main settings page
│   └── logs-page.php            # Log viewer page
├── includes/
│   ├── class-booking-handler.php      # Booking event handler
│   ├── class-fluentcrm-integration.php # FluentCRM integration
│   ├── class-google-analytics.php      # GA4 integration
│   └── class-logger.php                # Logging system
└── vikbooking-integration-suite.php   # Main plugin file
```

## Database Tables

### `wp_vb_integration_logs`

Stores all plugin activity logs with the following structure:

- `id` - Auto-increment primary key
- `log_time` - Timestamp of the event
- `log_type` - Event category (booking_direct, booking_ota, etc.)
- `booking_id` - Associated booking ID (if applicable)
- `message` - Human-readable message
- `data` - JSON-encoded additional data

## Troubleshooting

### Logs Not Showing in Menu

1. Deactivate and reactivate the plugin
2. Clear browser cache
3. Ensure you have `manage_options` capability

### Channel Manager Features Not Available

- Verify VikChannelManager is installed and activated
- Check **Settings → VikBooking Integration** status table

### Google Analytics Not Tracking

1. Verify Measurement ID format (G-XXXXXXXXXX)
2. Ensure API Secret is correctly configured
3. Check logs at **Settings → VB Integration Log**
4. Look for `ga_tracking` events

### FluentCRM Not Creating Contacts

1. Verify FluentCRM is active
2. Check integration is enabled in settings
3. Review logs for `fluentcrm` events
4. Ensure email addresses are valid

## Changelog

### Version 1.0.0 (2025-12-05)

**Initial Release**

- ✅ Channel Manager (OTA) booking detection and notifications
- ✅ Google Analytics 4 integration with enhanced ecommerce
- ✅ FluentCRM integration with native methods
- ✅ Structured logging system with admin viewer
- ✅ Comprehensive admin interface
- ✅ Plugin status dashboard
- ✅ Conditional feature activation based on installed plugins

## Support

For issues, questions, or feature requests, please contact:
- **Developer**: Inhertzweb Agency
- **Plugin URI**: [Your Plugin URI]
- **Documentation**: [Your Documentation URL]

## License

This plugin is proprietary software developed by Inhertzweb Agency.

## Credits

Developed by **Inhertzweb Agency** for seamless VikBooking integration with modern marketing and CRM tools.

---

**Note**: This plugin requires VikBooking to function. VikChannelManager and FluentCRM are optional but enable additional features.
