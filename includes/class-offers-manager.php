<?php
/**
 * Class VB_Offers_Manager
 * Manages the 'vb_offer' Custom Post Type and syncs with VikBooking coupons.
 */

if (!defined('ABSPATH')) {
    exit;
}

class VB_Offers_Manager {

    const POST_TYPE = 'vb_offer';

    public static function init() {
        add_action('init', [self::class, 'register_cpt']);
        add_action('add_meta_boxes', [self::class, 'add_meta_boxes']);
        add_action('save_post_' . self::POST_TYPE, [self::class, 'save_post_meta'], 10, 2);
        
        // Custom Admin Columns
        add_filter('manage_' . self::POST_TYPE . '_posts_columns', [self::class, 'set_custom_columns']);
        add_action('manage_' . self::POST_TYPE . '_posts_custom_column', [self::class, 'custom_column_data'], 10, 2);
    }

    public static function register_cpt() {
        $labels = [
            'name'                  => __('Offerte', 'vikbooking-integration-suite'),
            'singular_name'         => __('Offerta', 'vikbooking-integration-suite'),
            'menu_name'             => __('Offerte (A/B Test)', 'vikbooking-integration-suite'),
            'all_items'             => __('Tutte le Offerte', 'vikbooking-integration-suite'),
            'add_new'               => __('Aggiungi Nuova', 'vikbooking-integration-suite'),
            'add_new_item'          => __('Aggiungi Nuova Offerta', 'vikbooking-integration-suite'),
            'edit_item'             => __('Modifica Offerta', 'vikbooking-integration-suite'),
            'new_item'              => __('Nuova Offerta', 'vikbooking-integration-suite'),
            'view_item'             => __('Vedi Offerta', 'vikbooking-integration-suite'),
            'search_items'          => __('Cerca Offerte', 'vikbooking-integration-suite'),
            'not_found'             => __('Nessuna offerta trovata.', 'vikbooking-integration-suite'),
            'not_found_in_trash'    => __('Nessuna offerta nel cestino.', 'vikbooking-integration-suite'),
        ];

        $args = [
            'labels'             => $labels,
            'public'             => false,
            'publicly_queryable' => false,
            'show_ui'            => true,
            'show_in_menu'       => true, // Show as top-level menu
            'query_var'          => false,
            'rewrite'            => false,
            'capability_type'    => 'post',
            'has_archive'        => false,
            'hierarchical'       => false,
            'menu_position'      => 21, // Under Pages (20)
            'menu_icon'          => 'dashicons-tickets-alt', // Add a nice icon
            'supports'           => ['title', 'editor', 'thumbnail'],
        ];

        register_post_type(self::POST_TYPE, $args);
    }

    public static function add_meta_boxes() {
        add_meta_box(
            'vb_offer_details',
            __('Dettagli Offerta e Coupon', 'vikbooking-integration-suite'),
            [self::class, 'render_meta_box'],
            self::POST_TYPE,
            'normal',
            'high'
        );
    }

    public static function render_meta_box($post) {
        wp_nonce_field('vb_offer_save_meta', 'vb_offer_nonce');

        // Basic Fields
        $offer_type     = get_post_meta($post->ID, '_vb_offer_type', true) ?: 'coupon'; // 'coupon' | 'package'
        $coupon_code    = get_post_meta($post->ID, '_vb_offer_coupon_code', true);
        $custom_url     = get_post_meta($post->ID, '_vb_offer_custom_url', true);
        $discount_type  = get_post_meta($post->ID, '_vb_offer_discount_type', true) ?: '1'; // 1=Percent, 0=Fixed
        $discount_value = get_post_meta($post->ID, '_vb_offer_discount_value', true);
        $min_los        = get_post_meta($post->ID, '_vb_offer_min_los', true) ?: '1';
        $max_los        = get_post_meta($post->ID, '_vb_offer_max_los', true) ?: '0'; // 0 = no limit
        $active_ab      = get_post_meta($post->ID, '_vb_offer_active_ab', true) ?: 'yes'; // yes/no

        // Package Specific Fields
        $pkg_dfrom      = get_post_meta($post->ID, '_vb_offer_pkg_dfrom', true); // Format YYYY-MM-DD
        $pkg_dto        = get_post_meta($post->ID, '_vb_offer_pkg_dto', true);   // Format YYYY-MM-DD
        $pkg_excldates  = get_post_meta($post->ID, '_vb_offer_pkg_excldates', true); // Format YYYY-MM-DD,YYYY-MM-DD
        $pkg_cost       = get_post_meta($post->ID, '_vb_offer_pkg_cost', true);
        $pkg_pernight   = get_post_meta($post->ID, '_vb_offer_pkg_pernight', true) ?: '1'; // 1=per night, 0=total
        $pkg_perperson  = get_post_meta($post->ID, '_vb_offer_pkg_perperson', true) ?: '1'; // 1=per person, 0=total

        ?>
        <div style="background:#fff; padding:15px; border-bottom:1px solid #ccc; margin-bottom:15px;">
            <h3><?php _e('Impostazioni Generali', 'vikbooking-integration-suite'); ?></h3>
            <table class="form-table">
                <tr>
                    <th><label for="vb_offer_type"><?php _e('Tipo di Integrazione', 'vikbooking-integration-suite'); ?></label></th>
                    <td>
                        <select id="vb_offer_type" name="vb_offer_type" onchange="toggleVbOfferFields()">
                            <option value="coupon" <?php selected($offer_type, 'coupon'); ?>><?php _e('Genera Coupon Sconto', 'vikbooking-integration-suite'); ?></option>
                            <option value="package" <?php selected($offer_type, 'package'); ?>><?php _e('Genera Pacchetto VikBooking', 'vikbooking-integration-suite'); ?></option>
                            <option value="none" <?php selected($offer_type, 'none'); ?>><?php _e('Nessuna Sincronizzazione (Solo Popup)', 'vikbooking-integration-suite'); ?></option>
                        </select>
                        <p class="description"><?php _e('Stabilisce in quale sezione di VikBooking l\'offerta verrà ricreata automaticamente.', 'vikbooking-integration-suite'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label for="vb_offer_active_ab"><?php _e('Attiva in Popup A/B Test', 'vikbooking-integration-suite'); ?></label></th>
                    <td>
                        <select id="vb_offer_active_ab" name="vb_offer_active_ab">
                            <option value="yes" <?php selected($active_ab, 'yes'); ?>><?php _e('Sì', 'vikbooking-integration-suite'); ?></option>
                            <option value="no" <?php selected($active_ab, 'no'); ?>><?php _e('No', 'vikbooking-integration-suite'); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="vb_offer_custom_url"><?php _e('Link Offerta (Opzionale)', 'vikbooking-integration-suite'); ?></label></th>
                    <td>
                        <input type="url" id="vb_offer_custom_url" name="vb_offer_custom_url" value="<?php echo esc_url($custom_url); ?>" class="regular-text" placeholder="https://...">
                        <p class="description"><?php _e('Se compilato, il bottone del popup reindirizzerà a questo link. Altrimenti aprirà il calendario.', 'vikbooking-integration-suite'); ?></p>
                    </td>
                </tr>
            </table>
        </div>

        <div id="vb_offer_coupon_fields" style="background:#fff; padding:15px; border-bottom:1px solid #ccc; margin-bottom:15px; <?php echo $offer_type === 'coupon' ? '' : 'display:none;'; ?>">
            <h3><?php _e('Dettagli Coupon', 'vikbooking-integration-suite'); ?></h3>
            <table class="form-table">
                <tr>
                    <th><label for="vb_offer_coupon_code"><?php _e('Codice Coupon', 'vikbooking-integration-suite'); ?></label></th>
                    <td>
                        <input type="text" id="vb_offer_coupon_code" name="vb_offer_coupon_code" value="<?php echo esc_attr($coupon_code); ?>" class="regular-text" style="text-transform: uppercase;">
                    </td>
                </tr>
                <tr>
                    <th><label for="vb_offer_discount_type"><?php _e('Tipo Sconto', 'vikbooking-integration-suite'); ?></label></th>
                    <td>
                        <select id="vb_offer_discount_type" name="vb_offer_discount_type">
                            <option value="1" <?php selected($discount_type, '1'); ?>><?php _e('Percentuale (%)', 'vikbooking-integration-suite'); ?></option>
                            <option value="0" <?php selected($discount_type, '0'); ?>><?php _e('Valore Fisso', 'vikbooking-integration-suite'); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="vb_offer_discount_value"><?php _e('Valore Sconto', 'vikbooking-integration-suite'); ?></label></th>
                    <td>
                        <input type="number" step="0.01" id="vb_offer_discount_value" name="vb_offer_discount_value" value="<?php echo esc_attr($discount_value); ?>" class="regular-text">
                    </td>
                </tr>
            </table>
        </div>

        <div id="vb_offer_package_fields" style="background:#fff; padding:15px; border-bottom:1px solid #ccc; margin-bottom:15px; <?php echo $offer_type === 'package' ? '' : 'display:none;'; ?>">
            <h3><?php _e('Dettagli Pacchetto VikBooking', 'vikbooking-integration-suite'); ?></h3>
            <table class="form-table">
                <tr>
                    <th><label for="vb_offer_pkg_cost"><?php _e('Costo Pacchetto / Offerta', 'vikbooking-integration-suite'); ?></label></th>
                    <td>
                        <input type="number" step="0.01" id="vb_offer_pkg_cost" name="vb_offer_pkg_cost" value="<?php echo esc_attr($pkg_cost); ?>" class="regular-text">
                    </td>
                </tr>
                <tr>
                    <th><label><?php _e('Applicazione Costo', 'vikbooking-integration-suite'); ?></label></th>
                    <td>
                        <label><input type="radio" name="vb_offer_pkg_pernight" value="1" <?php checked($pkg_pernight, '1'); ?>> Per Notte</label> &nbsp;&nbsp;
                        <label><input type="radio" name="vb_offer_pkg_pernight" value="0" <?php checked($pkg_pernight, '0'); ?>> Per Soggiorno Intero</label>
                        <br><br>
                        <label><input type="radio" name="vb_offer_pkg_perperson" value="1" <?php checked($pkg_perperson, '1'); ?>> Per Persona</label> &nbsp;&nbsp;
                        <label><input type="radio" name="vb_offer_pkg_perperson" value="0" <?php checked($pkg_perperson, '0'); ?>> Per Camera/Totale</label>
                    </td>
                </tr>
                <tr>
                    <th><label for="vb_offer_min_los"><?php _e('Permanenza (Notti)', 'vikbooking-integration-suite'); ?></label></th>
                    <td>
                        Min: <input type="number" min="1" id="vb_offer_min_los" name="vb_offer_min_los" value="<?php echo esc_attr($min_los); ?>" class="small-text"> 
                        Max: <input type="number" min="0" id="vb_offer_max_los" name="vb_offer_max_los" value="<?php echo esc_attr($max_los); ?>" class="small-text">
                        <p class="description"><?php _e('Max 0 = nessun limite.', 'vikbooking-integration-suite'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label for="vb_offer_pkg_dfrom"><?php _e('Date Validità (Da - A)', 'vikbooking-integration-suite'); ?></label></th>
                    <td>
                        <input type="date" id="vb_offer_pkg_dfrom" name="vb_offer_pkg_dfrom" value="<?php echo esc_attr($pkg_dfrom); ?>"> - 
                        <input type="date" id="vb_offer_pkg_dto" name="vb_offer_pkg_dto" value="<?php echo esc_attr($pkg_dto); ?>">
                    </td>
                </tr>
                <tr>
                    <th><label for="vb_offer_pkg_excldates"><?php _e('Date Escluse', 'vikbooking-integration-suite'); ?></label></th>
                    <td>
                        <input type="text" id="vb_offer_pkg_excldates" name="vb_offer_pkg_excldates" value="<?php echo esc_attr($pkg_excldates); ?>" class="large-text" placeholder="YYYY-MM-DD,YYYY-MM-DD">
                        <p class="description"><?php _e('Inserisci le date escluse (es. ponti/festività) separate da virgola nel formato YYYY-MM-DD.', 'vikbooking-integration-suite'); ?></p>
                    </td>
                </tr>
            </table>
        </div>

        <script>
        function toggleVbOfferFields() {
            var type = document.getElementById('vb_offer_type').value;
            document.getElementById('vb_offer_coupon_fields').style.display = (type === 'coupon') ? 'block' : 'none';
            document.getElementById('vb_offer_package_fields').style.display = (type === 'package') ? 'block' : 'none';
        }
        </script>
        <?php
    }

    public static function save_post_meta($post_id, $post) {
        if (!isset($_POST['vb_offer_nonce']) || !wp_verify_nonce($_POST['vb_offer_nonce'], 'vb_offer_save_meta')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        $offer_type     = isset($_POST['vb_offer_type']) ? sanitize_text_field($_POST['vb_offer_type']) : 'coupon';
        $coupon_code    = isset($_POST['vb_offer_coupon_code']) ? sanitize_text_field(strtoupper($_POST['vb_offer_coupon_code'])) : '';
        $custom_url     = isset($_POST['vb_offer_custom_url']) ? esc_url_raw($_POST['vb_offer_custom_url']) : '';
        $discount_type  = isset($_POST['vb_offer_discount_type']) ? sanitize_text_field($_POST['vb_offer_discount_type']) : '1';
        $discount_value = isset($_POST['vb_offer_discount_value']) ? floatval($_POST['vb_offer_discount_value']) : 0;
        $min_los        = isset($_POST['vb_offer_min_los']) ? intval($_POST['vb_offer_min_los']) : 1;
        $max_los        = isset($_POST['vb_offer_max_los']) ? intval($_POST['vb_offer_max_los']) : 0;
        $active_ab      = isset($_POST['vb_offer_active_ab']) ? sanitize_text_field($_POST['vb_offer_active_ab']) : 'no';

        $pkg_dfrom      = isset($_POST['vb_offer_pkg_dfrom']) ? sanitize_text_field($_POST['vb_offer_pkg_dfrom']) : '';
        $pkg_dto        = isset($_POST['vb_offer_pkg_dto']) ? sanitize_text_field($_POST['vb_offer_pkg_dto']) : '';
        $pkg_excldates  = isset($_POST['vb_offer_pkg_excldates']) ? sanitize_text_field($_POST['vb_offer_pkg_excldates']) : '';
        $pkg_cost       = isset($_POST['vb_offer_pkg_cost']) ? floatval($_POST['vb_offer_pkg_cost']) : 0;
        $pkg_pernight   = isset($_POST['vb_offer_pkg_pernight']) ? sanitize_text_field($_POST['vb_offer_pkg_pernight']) : '1';
        $pkg_perperson  = isset($_POST['vb_offer_pkg_perperson']) ? sanitize_text_field($_POST['vb_offer_pkg_perperson']) : '1';

        update_post_meta($post_id, '_vb_offer_type', $offer_type);
        update_post_meta($post_id, '_vb_offer_coupon_code', $coupon_code);
        update_post_meta($post_id, '_vb_offer_custom_url', $custom_url);
        update_post_meta($post_id, '_vb_offer_discount_type', $discount_type);
        update_post_meta($post_id, '_vb_offer_discount_value', $discount_value);
        update_post_meta($post_id, '_vb_offer_min_los', $min_los);
        update_post_meta($post_id, '_vb_offer_max_los', $max_los);
        update_post_meta($post_id, '_vb_offer_active_ab', $active_ab);
        
        update_post_meta($post_id, '_vb_offer_pkg_dfrom', $pkg_dfrom);
        update_post_meta($post_id, '_vb_offer_pkg_dto', $pkg_dto);
        update_post_meta($post_id, '_vb_offer_pkg_excldates', $pkg_excldates);
        update_post_meta($post_id, '_vb_offer_pkg_cost', $pkg_cost);
        update_post_meta($post_id, '_vb_offer_pkg_pernight', $pkg_pernight);
        update_post_meta($post_id, '_vb_offer_pkg_perperson', $pkg_perperson);

        // Sync to VikBooking
        if ($offer_type === 'coupon' && !empty($coupon_code)) {
            self::sync_vikbooking_coupon($coupon_code, $discount_type, $discount_value, $min_los);
        } else if ($offer_type === 'package') {
            self::sync_vikbooking_package($post_id, [
                'dfrom' => $pkg_dfrom,
                'dto' => $pkg_dto,
                'excldates' => $pkg_excldates,
                'minlos' => $min_los,
                'maxlos' => $max_los,
                'cost' => $pkg_cost,
                'pernight' => $pkg_pernight,
                'perperson' => $pkg_perperson
            ]);
        }
    }

    private static function sync_vikbooking_coupon($code, $percentot, $value, $minlos) {
        if (!class_exists('VikBooking')) {
            return;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'vikbooking_coupons';

        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") != $table_name) {
            return;
        }

        // Check if coupon exists
        $existing = $wpdb->get_row($wpdb->prepare("SELECT id FROM {$table_name} WHERE code = %s", $code));

        $data = [
            'code'         => $code,
            'type'         => '1', // Default to generic type
            'percentot'    => $percentot,
            'value'        => $value,
            'allvehicles'  => '1', // Applies to all rooms
            'minlos'       => $minlos,
            'excludetaxes' => 0,
        ];

        if ($existing) {
            $wpdb->update($table_name, $data, ['id' => $existing->id]);
        } else {
            // New coupons might require some defaults for columns we don't handle
            $data['mintotord'] = 0;
            $data['maxtotord'] = 100000;
            $data['idrooms'] = '';
            $data['datevalid'] = '';
            $wpdb->insert($table_name, $data);
        }
    }

    private static function sync_vikbooking_package($post_id, $data) {
        if (!class_exists('VikBooking')) {
            return;
        }

        global $wpdb;
        $table_pkg = $wpdb->prefix . 'vikbooking_packages';
        $table_pkg_rooms = $wpdb->prefix . 'vikbooking_packages_rooms';

        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '{$table_pkg}'") != $table_pkg) {
            return;
        }

        $dfrom_ts = !empty($data['dfrom']) ? strtotime($data['dfrom']) : time();
        $dto_ts = !empty($data['dto']) ? strtotime($data['dto']) : strtotime('+2 years');
        $post_title = get_the_title($post_id);

        $package_data = [
            'name'           => $post_title,
            'alias'          => sanitize_title($post_title),
            'dfrom'          => $dfrom_ts,
            'dto'            => $dto_ts,
            'excldates'      => $data['excldates'],
            'minlos'         => $data['minlos'],
            'maxlos'         => $data['maxlos'],
            'cost'           => $data['cost'],
            'pernight_total' => $data['pernight'],
            'perperson'      => $data['perperson'],
            'showoptions'    => 1,
            'idiva'          => ''
        ];

        // Check if package already created for this post id
        $vb_pkg_id = get_post_meta($post_id, '_vb_offer_pkg_id', true);
        
        if ($vb_pkg_id && $wpdb->get_var($wpdb->prepare("SELECT id FROM {$table_pkg} WHERE id = %d", $vb_pkg_id))) {
            $wpdb->update($table_pkg, $package_data, ['id' => $vb_pkg_id]);
        } else {
            $wpdb->insert($table_pkg, $package_data);
            $vb_pkg_id = $wpdb->insert_id;
            update_post_meta($post_id, '_vb_offer_pkg_id', $vb_pkg_id);
        }

        // Assign to all active rooms
        $rooms = $wpdb->get_results("SELECT id FROM {$wpdb->prefix}vikbooking_rooms WHERE avail=1");
        if ($rooms && $vb_pkg_id) {
            $wpdb->delete($table_pkg_rooms, ['idpackage' => $vb_pkg_id]);
            foreach ($rooms as $r) {
                $wpdb->insert($table_pkg_rooms, ['idpackage' => $vb_pkg_id, 'idroom' => $r->id]);
            }
        }
    }

    public static function set_custom_columns($columns) {
        $columns['coupon'] = __('Coupon', 'vikbooking-integration-suite');
        $columns['discount'] = __('Sconto', 'vikbooking-integration-suite');
        $columns['ab_status'] = __('A/B Status', 'vikbooking-integration-suite');
        $columns['views'] = __('Views', 'vikbooking-integration-suite');
        $columns['clicks'] = __('Clicks', 'vikbooking-integration-suite');
        $columns['ctr'] = __('CTR (%)', 'vikbooking-integration-suite');
        return $columns;
    }

    public static function custom_column_data($column, $post_id) {
        switch ($column) {
            case 'coupon':
                echo esc_html(get_post_meta($post_id, '_vb_offer_coupon_code', true) ?: '-');
                break;
            case 'discount':
                $val = get_post_meta($post_id, '_vb_offer_discount_value', true);
                $type = get_post_meta($post_id, '_vb_offer_discount_type', true);
                echo esc_html($val . ($type == '1' ? '%' : '€'));
                break;
            case 'ab_status':
                $status = get_post_meta($post_id, '_vb_offer_active_ab', true);
                if ($status === 'yes') {
                    echo '<span style="color: green; font-weight: bold;">Attiva</span>';
                } else {
                    echo '<span style="color: grey;">Disattivata</span>';
                }
                break;
            case 'views':
                echo esc_html((int)get_post_meta($post_id, '_vb_offer_views', true));
                break;
            case 'clicks':
                echo esc_html((int)get_post_meta($post_id, '_vb_offer_clicks', true));
                break;
            case 'ctr':
                $views = (int)get_post_meta($post_id, '_vb_offer_views', true);
                $clicks = (int)get_post_meta($post_id, '_vb_offer_clicks', true);
                if ($views > 0) {
                    $ctr = round(($clicks / $views) * 100, 2);
                    echo esc_html($ctr . '%');
                } else {
                    echo '-';
                }
                break;
        }
    }
}
