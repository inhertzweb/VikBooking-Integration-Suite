<?php
// Security check
if (!defined('ABSPATH')) {
    exit;
}

// Handle form submission
if (isset($_POST['vb_integration_save'])) {
    check_admin_referer('vb_integration_settings');
    
    update_option('vb_integration_ga_tracking_id', sanitize_text_field($_POST['ga_tracking_id']));
    update_option('vb_integration_ga_api_secret', sanitize_text_field($_POST['ga_api_secret']));
    update_option('vb_integration_cm_email_enabled', isset($_POST['cm_email_enabled']) ? '1' : '0');
    update_option('vb_integration_cm_email_address', sanitize_email($_POST['cm_email_address']));
    update_option('vb_integration_fluentcrm_enabled', isset($_POST['fluentcrm_enabled']) ? '1' : '0');

    // Mobile Widget Settings
    update_option('vb_integration_mw_enabled', isset($_POST['mw_enabled']) ? '1' : '0');
    update_option('vb_integration_mw_prenota_bg', sanitize_hex_color($_POST['mw_prenota_bg']));
    update_option('vb_integration_mw_prenota_text', sanitize_hex_color($_POST['mw_prenota_text']));
    update_option('vb_integration_mw_chiama_bg', sanitize_hex_color($_POST['mw_chiama_bg']));
    update_option('vb_integration_mw_chiama_text', sanitize_hex_color($_POST['mw_chiama_text']));
    update_option('vb_integration_mw_chiama_link', sanitize_text_field($_POST['mw_chiama_link']));
    update_option('vb_integration_mw_offerta_bg', sanitize_hex_color($_POST['mw_offerta_bg']));
    update_option('vb_integration_mw_offerta_text', sanitize_hex_color($_POST['mw_offerta_text']));
    update_option('vb_integration_mw_offerta_link', sanitize_text_field($_POST['mw_offerta_link']));
    
    echo '<div class="notice notice-success"><p>Impostazioni salvate con successo!</p></div>';
}

// Get current settings
$ga_tracking_id = get_option('vb_integration_ga_tracking_id', '');
$ga_api_secret = get_option('vb_integration_ga_api_secret', '');
$cm_email_enabled = get_option('vb_integration_cm_email_enabled', '0');
$cm_email_address = get_option('vb_integration_cm_email_address', get_option('admin_email'));
$fluentcrm_enabled = get_option('vb_integration_fluentcrm_enabled', '1');

// Mobile Widget Settings
$mw_enabled = get_option('vb_integration_mw_enabled', '0');
$mw_prenota_bg = get_option('vb_integration_mw_prenota_bg', '#faff4d');
$mw_prenota_text = get_option('vb_integration_mw_prenota_text', '#000000');
$mw_chiama_bg = get_option('vb_integration_mw_chiama_bg', '#ffffff');
$mw_chiama_text = get_option('vb_integration_mw_chiama_text', '#000000');
$mw_chiama_link = get_option('vb_integration_mw_chiama_link', '');
$mw_offerta_bg = get_option('vb_integration_mw_offerta_bg', '#69b1e9');
$mw_offerta_text = get_option('vb_integration_mw_offerta_text', '#ffffff');
$mw_offerta_link = get_option('vb_integration_mw_offerta_link', '');
?>

<div class="wrap">
    <h1>VikBooking Integration Suite</h1>
    
    <form method="post" action="">
        <?php wp_nonce_field('vb_integration_settings'); ?>
        
        <h2>Google Analytics 4</h2>
        <table class="form-table">
            <tr>
                <th scope="row">GA4 Measurement ID</th>
                <td>
                    <input type="text" name="ga_tracking_id" value="<?php echo esc_attr($ga_tracking_id); ?>" class="regular-text" placeholder="G-XXXXXXXXXX" />
                    <p class="description">Inserisci il tuo GA4 Measurement ID (es. G-XXXXXXXXXX)</p>
                </td>
            </tr>
            <tr>
                <th scope="row">GA4 API Secret</th>
                <td>
                    <input type="text" name="ga_api_secret" value="<?php echo esc_attr($ga_api_secret); ?>" class="regular-text" />
                    <p class="description">API Secret per il Measurement Protocol (necessario per il tracking server-side)</p>
                </td>
            </tr>
        </table>

        <h2>Notifiche Email Channel Manager</h2>
        <?php if (class_exists('VikChannelManager')): ?>
            <table class="form-table">
                <tr>
                    <th scope="row">Abilita Notifiche</th>
                    <td>
                        <label>
                            <input type="checkbox" name="cm_email_enabled" value="1" <?php checked($cm_email_enabled, '1'); ?> />
                            Invia email per nuove prenotazioni da Channel Manager (OTA)
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Indirizzo Email</th>
                    <td>
                        <input type="email" name="cm_email_address" value="<?php echo esc_attr($cm_email_address); ?>" class="regular-text" />
                        <p class="description">Email dove ricevere le notifiche delle prenotazioni OTA</p>
                    </td>
                </tr>
            </table>
        <?php else: ?>
            <div class="notice notice-warning inline">
                <p><strong>VikChannelManager non installato.</strong> Installa e attiva VikChannelManager per ricevere notifiche delle prenotazioni OTA (Booking.com, Airbnb, etc.)</p>
            </div>
        <?php endif; ?>

        <h2>FluentCRM</h2>
        <table class="form-table">
            <tr>
                <th scope="row">Integrazione FluentCRM</th>
                <td>
                    <label>
                        <input type="checkbox" name="fluentcrm_enabled" value="1" <?php checked($fluentcrm_enabled, '1'); ?> />
                        Aggiungi automaticamente i clienti a FluentCRM
                    </label>
                    <p class="description">I contatti verranno aggiunti alla lista "Ospiti Hotel" con tag appropriati</p>
                </td>
            </tr>
        </table>

        <h2>Mobile Booking Widget</h2>
        <table class="form-table">
            <tr>
                <th scope="row">Abilita Widget Mobile</th>
                <td>
                    <label>
                        <input type="checkbox" name="mw_enabled" value="1" <?php checked($mw_enabled, '1'); ?> />
                        Mostra il widget in fondo alla pagina sui dispositivi mobili
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row">Tasto "PRENOTA"</th>
                <td>
                    BG: <input type="color" name="mw_prenota_bg" value="<?php echo esc_attr($mw_prenota_bg); ?>" />
                    Testo: <input type="color" name="mw_prenota_text" value="<?php echo esc_attr($mw_prenota_text); ?>" />
                </td>
            </tr>
            <tr>
                <th scope="row">Tasto "CHIAMA"</th>
                <td>
                    BG: <input type="color" name="mw_chiama_bg" value="<?php echo esc_attr($mw_chiama_bg); ?>" />
                    Testo: <input type="color" name="mw_chiama_text" value="<?php echo esc_attr($mw_chiama_text); ?>" />
                    <br><br>
                    Link: <input type="text" name="mw_chiama_link" value="<?php echo esc_attr($mw_chiama_link); ?>" class="regular-text" placeholder="tel:+39012345678" />
                </td>
            </tr>
            <tr>
                <th scope="row">Tasto "OFFERTA"</th>
                <td>
                    BG: <input type="color" name="mw_offerta_bg" value="<?php echo esc_attr($mw_offerta_bg); ?>" />
                    Testo: <input type="color" name="mw_offerta_text" value="<?php echo esc_attr($mw_offerta_text); ?>" />
                    <br><br>
                    Link: <input type="text" name="mw_offerta_link" value="<?php echo esc_attr($mw_offerta_link); ?>" class="regular-text" placeholder="https://..." />
                </td>
            </tr>
        </table>

        <?php submit_button('Salva Impostazioni', 'primary', 'vb_integration_save'); ?>
    </form>

    <hr>

    <h2>Stato Integrazioni</h2>
    <table class="widefat">
        <thead>
            <tr>
                <th>Servizio</th>
                <th>Stato</th>
                <th>Note</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>Google Analytics 4</strong></td>
                <td>
                    <?php if (!empty($ga_tracking_id) && !empty($ga_api_secret)): ?>
                        <span style="color: green;">✓ Configurato</span>
                    <?php elseif (!empty($ga_tracking_id)): ?>
                        <span style="color: orange;">⚠ Parziale (manca API Secret)</span>
                    <?php else: ?>
                        <span style="color: red;">✗ Non configurato</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if (!empty($ga_tracking_id)): ?>
                        Tracking ID: <?php echo esc_html($ga_tracking_id); ?>
                    <?php else: ?>
                        Configura Measurement ID e API Secret
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <td><strong>FluentCRM</strong></td>
                <td>
                    <?php if (function_exists('FluentCrmApi')): ?>
                        <span style="color: green;">✓ Plugin Attivo</span>
                    <?php else: ?>
                        <span style="color: red;">✗ Plugin non attivo</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if (function_exists('FluentCrmApi')): ?>
                        Integrazione: <?php echo $fluentcrm_enabled ? 'Abilitata' : 'Disabilitata'; ?>
                    <?php else: ?>
                        Installa e attiva FluentCRM
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <td><strong>VikBooking</strong></td>
                <td>
                    <?php if (class_exists('VikBookingLoader')): ?>
                        <span style="color: green;">✓ Plugin Attivo</span>
                    <?php else: ?>
                        <span style="color: red;">✗ Plugin non attivo</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if (class_exists('VikBookingLoader')): ?>
                        Plugin installato e funzionante
                    <?php else: ?>
                        VikBooking è richiesto
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <td><strong>VikChannelManager</strong></td>
                <td>
                    <?php if (class_exists('VikChannelManager')): ?>
                        <span style="color: green;">✓ Plugin Attivo</span>
                    <?php else: ?>
                        <span style="color: orange;">⚠ Plugin non attivo</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if (class_exists('VikChannelManager')): ?>
                        Plugin installato e funzionante
                    <?php else: ?>
                        Opzionale per tracking OTA
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <td><strong>Notifiche Email CM</strong></td>
                <td>
                    <?php if (class_exists('VikChannelManager') && $cm_email_enabled): ?>
                        <span style="color: green;">✓ Abilitate</span>
                    <?php elseif (!class_exists('VikChannelManager')): ?>
                        <span style="color: gray;">○ VCM non installato</span>
                    <?php else: ?>
                        <span style="color: gray;">○ Disabilitate</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if (class_exists('VikChannelManager') && $cm_email_enabled): ?>
                        Invio a: <?php echo esc_html($cm_email_address); ?>
                    <?php elseif (!class_exists('VikChannelManager')): ?>
                        Richiede VikChannelManager
                    <?php else: ?>
                        Abilita per ricevere notifiche OTA
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <td><strong>Mobile Widget</strong></td>
                <td>
                    <?php if ($mw_enabled): ?>
                        <span style="color: green;">✓ Abilitato</span>
                    <?php else: ?>
                        <span style="color: gray;">○ Disabilitato</span>
                    <?php endif; ?>
                </td>
                <td>
                    Configura colori e collegamenti sopra
                </td>
            </tr>
        </tbody>
    </table>

    <hr>

    <h2>Informazioni</h2>
    <p>
        <strong>VikBooking Integration Suite</strong> integra automaticamente VikBooking con:
    </p>
    <ul style="list-style: disc; margin-left: 20px;">
        <li><strong>Google Analytics 4:</strong> Traccia tutte le prenotazioni dirette (esclude OTA)</li>
        <li><strong>FluentCRM:</strong> Aggiunge automaticamente i clienti con tag e liste personalizzate</li>
        <li><strong>Email Notifiche:</strong> Ricevi email per prenotazioni da Channel Manager (Booking.com, Airbnb, etc.)</li>
    </ul>
    
    <p>
        <strong>Hook disponibili:</strong>
    </p>
    <ul style="list-style: disc; margin-left: 20px;">
        <li><code>vikbooking_booking_conversion_tracking</code> - Prenotazioni dirette confermate</li>
        <li><code>onAfterSaveBookingHistoryVikBooking</code> - Prenotazioni da Channel Manager</li>
    </ul>
</div>