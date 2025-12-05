<?php
// Security check
if (!defined('ABSPATH')) {
    exit;
}

// Include logger
require_once VB_INTEGRATION_PLUGIN_PATH . 'includes/class-logger.php';

// Handle log actions
if (isset($_POST['clear_logs'])) {
    check_admin_referer('vb_integration_logs');
    VB_Logger::clear_all_logs();
    echo '<div class="notice notice-success"><p>Log cancellati con successo!</p></div>';
}

// Get filter
$filter_type = isset($_GET['log_type']) ? sanitize_text_field($_GET['log_type']) : null;

// Get logs
$logs = VB_Logger::get_logs(100, $filter_type);
$stats = VB_Logger::get_stats();
?>

<div class="wrap">
    <h2>Log Attività Plugin</h2>
    
    <!-- Statistics -->
    <div class="card" style="max-width: none;">
        <h3>Statistiche Ultimi 7 Giorni</h3>
        <table class="widefat">
            <thead>
                <tr>
                    <th>Tipo Evento</th>
                    <th>Conteggio</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($stats)): ?>
                    <tr>
                        <td colspan="2">Nessuna attività negli ultimi 7 giorni</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($stats as $type => $count): ?>
                        <tr>
                            <td><strong><?php echo esc_html($type); ?></strong></td>
                            <td><?php echo esc_html($count); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <br>

    <!-- Filters -->
    <div class="tablenav top">
        <div class="alignleft actions">
            <select name="log_type" onchange="window.location.href='?page=vb-integration-logs&log_type=' + this.value;">
                <option value="">Tutti i tipi</option>
                <option value="booking_direct" <?php selected($filter_type, 'booking_direct'); ?>>Prenotazioni Dirette</option>
                <option value="booking_ota" <?php selected($filter_type, 'booking_ota'); ?>>Prenotazioni OTA</option>
                <option value="ga_tracking" <?php selected($filter_type, 'ga_tracking'); ?>>Google Analytics</option>
                <option value="fluentcrm" <?php selected($filter_type, 'fluentcrm'); ?>>FluentCRM</option>
                <option value="email" <?php selected($filter_type, 'email'); ?>>Email</option>
                <option value="error" <?php selected($filter_type, 'error'); ?>>Errori</option>
            </select>
        </div>
        <div class="alignright actions">
            <form method="post" style="display: inline;">
                <?php wp_nonce_field('vb_integration_logs'); ?>
                <button type="submit" name="clear_logs" class="button" onclick="return confirm('Sei sicuro di voler cancellare tutti i log?');">
                    Cancella Tutti i Log
                </button>
            </form>
        </div>
    </div>

    <!-- Logs Table -->
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th style="width: 150px;">Data/Ora</th>
                <th style="width: 120px;">Tipo</th>
                <th style="width: 80px;">Booking ID</th>
                <th>Messaggio</th>
                <th style="width: 100px;">Dettagli</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($logs)): ?>
                <tr>
                    <td colspan="5">Nessun log disponibile</td>
                </tr>
            <?php else: ?>
                <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?php echo esc_html(date('d/m/Y H:i:s', strtotime($log->log_time))); ?></td>
                        <td>
                            <span class="log-type-badge log-type-<?php echo esc_attr($log->log_type); ?>">
                                <?php echo esc_html($log->log_type); ?>
                            </span>
                        </td>
                        <td><?php echo $log->booking_id ? esc_html($log->booking_id) : '-'; ?></td>
                        <td><?php echo esc_html($log->message); ?></td>
                        <td>
                            <?php if ($log->data): ?>
                                <button type="button" class="button button-small" onclick="toggleLogData(<?php echo $log->id; ?>)">
                                    Mostra
                                </button>
                                <div id="log-data-<?php echo $log->id; ?>" style="display: none; margin-top: 10px;">
                                    <pre style="background: #f5f5f5; padding: 10px; font-size: 11px; max-height: 200px; overflow: auto;"><?php 
                                        $data = json_decode($log->data, true);
                                        echo esc_html($data ? print_r($data, true) : $log->data);
                                    ?></pre>
                                </div>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<style>
.log-type-badge {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: bold;
    color: white;
}
.log-type-booking_direct { background: #2271b1; }
.log-type-booking_ota { background: #50575e; }
.log-type-ga_tracking { background: #f0b849; color: #333; }
.log-type-fluentcrm { background: #00a32a; }
.log-type-email { background: #8c8f94; }
.log-type-error { background: #d63638; }
</style>

<script>
function toggleLogData(id) {
    var element = document.getElementById('log-data-' + id);
    if (element.style.display === 'none') {
        element.style.display = 'block';
    } else {
        element.style.display = 'none';
    }
}
</script>
