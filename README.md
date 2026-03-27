# VikBooking Integration Suite

A comprehensive WordPress plugin that integrates VikBooking with Google Analytics 4, FluentCRM, and provides advanced booking management features including Channel Manager (OTA) support and a Mobile Booking Widget.

**Current Version: 1.1.6**

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

### 📱 Mobile Booking Widget
- **Sticky Bottom Bar**: Fixed action bar rendered on mobile devices via `wp_footer`
- **Three Quick-Action Buttons**:
  - **PRENOTA** – Opens a modal with an interactive date-range calendar
  - **CHIAMA** – Direct call-to-action link (configurable phone number URL)
  - **RICHIEDI OFFERTA** – Link to a custom offer/inquiry page
- **Interactive Availability Calendar**:
  - Multi-month scrollable calendar built in pure JavaScript (no external dependencies)
  - Real-time availability via AJAX: queries VikBooking busy records for the next 18 months
  - Fully booked dates and structural closing dates (from VikBooking) are displayed as unavailable
  - Date-range selection with check-in / check-out picking
  - Validation: prevents selection of ranges that include unavailable dates
- **Guest Selector**: Counter inputs for Adults (default 2) and Children (default 0), included in the booking URL query string
- **Booking URL Builder**: On confirmation, redirects to the configured booking page with `checkin`, `checkout`, `adults`, and `children` URL parameters
- **Fully Customizable Colors** (per button): background and text color via WordPress options
- **Dynamic CSS Variables**: Colors are injected as CSS custom properties (`--vb-mw-prenota-bg`, etc.) for easy theming
- **i18n Ready**: All labels use `__()` / `_e()` with the `vikbooking-integration-suite` text domain
- **Conditional Activation**: The widget only loads (assets + HTML) when explicitly enabled in settings

### 🎁 Offers System & A/B Testing
- **New Custom Post Type (`vb_offer`)**: Create promotional offers directly from the WordPress Admin menu (under VikBooking Integration).
- **Auto-sync Coupons**: When an offer is created or updated, the associated coupon code and discount are automatically written into the VikBooking internal tables. 
- **Non-Invasive Popup UI**: 
  - On desktop: Appears as a sleek toast-notification at the bottom left.
  - On mobile: Appears from the bottom without overlapping the sticky Mobile Booking Widget.
  - Includes image thumbnail, offer text, and a Call-To-Action (Copia e Prenota).
- **Smart Logic & CTA**: Clicking the CTA automatically copies the discount code to the clipboard and triggers the Mobile Booking Widget calendar to open instantly.
- **Native A/B Testing**: 
  - The plugin automatically fetches active offers and randomly assigns one per user.
  - The choice is saved in `localStorage` to guarantee consistent experience across multiple pages within the same session.
- **Performance Metrics**: 
  - Impressions (Views) and Clicks are incremented asynchronously via AJAX.
  - The Admin list table displays the metrics and automatically calculates the **CTR (Click-Through Rate)** for each offer to measure the best performer.

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

### Mobile Booking Widget Setup

1. Go to **Settings → VikBooking Integration**
2. Enable **Mobile Booking Widget**
3. Set the **Booking URL** – the page where VikBooking's booking form is located (relative path or full URL)
4. Configure the **CHIAMA** link (phone number URL, e.g. `tel:+390000000000`)
5. Configure the **RICHIEDI OFFERTA** link (URL to your offer/contact page)
6. Customize button colors (background and text) for each of the three action buttons:
   - **Prenota**: default `#faff4d` background / `#000000` text
   - **Chiama**: default `#ffffff` background / `#000000` text
   - **Offerta**: default `#69b1e9` background / `#ffffff` text
7. Save settings

The widget will appear as a sticky bar at the bottom of every frontend page on mobile.

| Option Key | Description | Default |
|---|---|---|
| `vb_integration_mw_enabled` | Enable/disable the widget | `0` |
| `vb_integration_mw_booking_url` | URL of the VikBooking booking page | `` |
| `vb_integration_mw_prenota_bg` | Prenota button background color | `#faff4d` |
| `vb_integration_mw_prenota_text` | Prenota button text color | `#000000` |
| `vb_integration_mw_chiama_bg` | Chiama button background color | `#ffffff` |
| `vb_integration_mw_chiama_text` | Chiama button text color | `#000000` |
| `vb_integration_mw_chiama_link` | Chiama button URL | `` |
| `vb_integration_mw_offerta_bg` | Offerta button background color | `#69b1e9` |
| `vb_integration_mw_offerta_text` | Offerta button text color | `#ffffff` |
| `vb_integration_mw_offerta_link` | Offerta button URL | `` |

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

### Mobile Widget – AJAX Availability Endpoint

The widget exposes a public AJAX action to fetch fully booked dates:

```
POST /wp-admin/admin-ajax.php
action: vb_mw_get_availability
```

**Response (success):**
```json
{
  "success": true,
  "data": ["2025-08-01", "2025-08-02", "2025-12-25"]
}
```

The endpoint:
1. Loads all active VikBooking rooms and their total unit count
2. Queries busy records for the next 18 months via `VikBooking::loadBusyRecords()`
3. Calculates daily occupancy and flags dates where all units are occupied
4. Merges global structural closing dates via `VikBooking::getClosingDates()`
5. Returns the combined array of unavailable date strings (`YYYY-MM-DD`)

The JavaScript calendar uses this response to render unavailable days and prevent invalid date-range selection.

## File Structure

```
vikbooking-integration-suite/
├── admin/
│   ├── admin-page.php               # Main settings page (incl. Mobile Widget options)
│   └── logs-page.php                # Log viewer page
├── assets/
│   ├── css/
│   │   ├── mobile-widget.css        # Mobile widget styles & CSS variables
│   │   └── offer-popup.css          # Offers A/B Testing Popup styles
│   └── js/
│       ├── mobile-widget.js         # Calendar, AJAX availability, booking URL builder
│       └── offer-popup.js           # Offers A/B Testing & Tracking logic
├── includes/
│   ├── class-ab-testing-popup.php      # A/B Testing Frontend & Tracking
│   ├── class-booking-handler.php       # Booking event handler
│   ├── class-fluentcrm-integration.php # FluentCRM integration
│   ├── class-google-analytics.php      # GA4 integration
│   ├── class-logger.php                # Logging system
│   ├── class-mobile-widget.php         # Mobile booking widget
│   └── class-offers-manager.php        # Offers CPT & VikBooking Coupon Sync
└── vikbooking-integration-suite.php    # Main plugin file
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

### Mobile Widget Not Appearing

1. Verify the widget is enabled in **Settings → VikBooking Integration**
2. The widget only renders on the frontend (not in the WordPress admin)
3. Check that the **Booking URL** option points to your VikBooking page
4. Open the browser DevTools console and look for JavaScript errors
5. Confirm VikBooking is active (`VikBooking` class must exist)
6. If the calendar shows no unavailable dates, verify that `JFactory::getDbo()` is accessible (requires VikBooking's Joomla compatibility layer)

### Mobile Widget Calendar Not Loading Availability

- Open DevTools → Network tab and inspect the `admin-ajax.php` request with `action=vb_mw_get_availability`
- A `success: false` response indicates a VikBooking class resolution failure
- Ensure rooms have `avail = 1` in the VikBooking rooms table

## Changelog

### Version 1.1.6 (2026-03-27)

- 🎉 **New Feature**: Offers System with A/B Testing (`vb_offer` CPT)
- 🎉 **New Feature**: Auto-synchronization of offers with VikBooking coupons
- 🎉 **New Feature**: Smart, non-invasive frontend popup that tracks Views & Clicks
- 🐛 Bug fixes and stability improvements for the Mobile Widget

### Version 1.1.0 (2026-01-01)

**Mobile Booking Widget**

- ✅ Sticky mobile bottom bar with three action buttons (Prenota, Chiama, Richiedi Offerta)
- ✅ Interactive multi-month date-range calendar (pure JS, no dependencies)
- ✅ Real-time availability via AJAX using VikBooking busy records
- ✅ Structural closing dates integration via `VikBooking::getClosingDates()`
- ✅ Guest selector (Adults / Children) with counter inputs
- ✅ Booking URL builder with check-in, check-out, adults, children query params
- ✅ Fully customizable button colors via WordPress options
- ✅ Dynamic CSS custom properties injection for theming
- ✅ i18n ready with `vikbooking-integration-suite` text domain

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
