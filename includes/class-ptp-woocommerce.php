<?php
/**
 * WooCommerce integration - metabox, my-account, and email hooks.
 */

defined( 'ABSPATH' ) || exit;

class PTP_WooCommerce {

    public function __construct() {
        // Add metabox to order edit page
        add_action( 'add_meta_boxes', [ $this, 'add_order_metabox' ] );
        add_action( 'save_post_shop_order', [ $this, 'save_order_tracking' ], 10, 2 );
        add_action( 'woocommerce_process_shop_order_meta', [ $this, 'save_order_tracking' ], 10, 2 );

        // Add tracking info to My Account
        add_action( 'woocommerce_view_order', [ $this, 'display_tracking_in_order' ], 20 );
        add_action( 'woocommerce_order_details_after_order_table', [ $this, 'display_tracking_after_table' ], 10 );

        // Add tracking info to emails
        add_action( 'woocommerce_email_order_meta', [ $this, 'add_tracking_to_email' ], 10, 4 );

        // Admin order list columns (CPT)
        add_filter( 'manage_edit-shop_order_columns', [ $this, 'add_tracking_column' ], 20 );
        add_action( 'manage_shop_order_posts_custom_column', [ $this, 'render_tracking_column' ], 10, 2 );
        
        // HPOS columns
        add_filter( 'manage_woocommerce_page_wc-orders_columns', [ $this, 'add_tracking_column_hpos' ], 20 );
        add_action( 'manage_woocommerce_page_wc-orders_custom_column', [ $this, 'render_tracking_column_hpos' ], 10, 2 );

        // AJAX for creating shipment from order
        add_action( 'wp_ajax_ptp_create_shipment_from_order', [ $this, 'ajax_create_shipment_from_order' ] );
    }

    /**
     * Add metabox to order edit page.
     */
    public function add_order_metabox(): void {
        add_meta_box(
            'ptp_order_tracking',
            __( 'Suivi d\'expédition', 'plugin-tracking-personalise' ),
            [ $this, 'render_order_metabox' ],
            'shop_order',
            'side',
            'high'
        );

        // Support for HPOS (High-Performance Order Storage)
        if ( class_exists( 'Automattic\WooCommerce\Utilities\OrderUtil' ) ) {
            add_meta_box(
                'ptp_order_tracking',
                __( 'Suivi d\'expédition', 'plugin-tracking-personalise' ),
                [ $this, 'render_order_metabox' ],
                'woocommerce_page_wc-orders',
                'side',
                'high'
            );
        }
    }

    /**
     * Render order tracking metabox.
     */
    public function render_order_metabox( $post_or_order ): void {
        $order_id = $post_or_order instanceof \WP_Post ? $post_or_order->ID : $post_or_order->get_id();
        $order = wc_get_order( $order_id );

        wp_nonce_field( 'ptp_order_tracking', 'ptp_order_tracking_nonce' );

        // Get tracking numbers array
        $tracking_numbers = $order->get_meta( '_ptp_tracking_numbers', true );
        if ( ! is_array( $tracking_numbers ) ) {
            $tracking_numbers = [];
        }
        ?>
        <div class="ptp-order-tracking-meta">
            <?php if ( ! empty( $tracking_numbers ) ) : ?>
                <p><strong><?php esc_html_e( 'Numéros de suivi liés :', 'plugin-tracking-personalise' ); ?></strong></p>
                <ul style="margin:0 0 15px 0;padding:0;list-style:none;">
                    <?php foreach ( $tracking_numbers as $tracking ) : ?>
                        <?php
                        $shipment = PTP_Helper::get_shipment_by_tracking( $tracking );
                        ?>
                        <li style="margin-bottom:8px;">
                            <code style="font-size:11px;"><?php echo esc_html( $tracking ); ?></code>
                            <?php if ( $shipment ) : ?>
                                <a href="<?php echo esc_url( get_edit_post_link( $shipment->ID ) ); ?>" class="button button-small" target="_blank">
                                    <?php esc_html_e( 'Voir', 'plugin-tracking-personalise' ); ?>
                                </a>
                            <?php endif; ?>
                            <button type="button" class="button button-small ptp-remove-tracking" data-tracking="<?php echo esc_attr( $tracking ); ?>" style="color:#b32d2e;">
                                ×
                            </button>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <div style="margin-bottom:15px;">
                <input type="text" name="ptp_tracking_new" placeholder="<?php esc_attr_e( 'Ex: 1Z999AA10123456784', 'plugin-tracking-personalise' ); ?>" style="width:70%;margin-right:8px;">
                <button type="submit" class="button button-primary"><?php esc_html_e( 'Ajouter', 'plugin-tracking-personalise' ); ?></button>
            </div>

            <div style="margin-top:10px;padding-top:10px;border-top:1px solid #ddd;">
                <button type="button" id="ptp-generate-from-order" class="button button-secondary" style="width:100%;">
                    ✨ <?php esc_html_e( 'Générer et créer automatiquement avec infos client', 'plugin-tracking-personalise' ); ?>
                </button>
                <p class="description" style="margin-top:8px;">
                    <?php esc_html_e( 'Génère un numéro, crée l\'envoi et récupère automatiquement : nom, email, téléphone, adresse de livraison.', 'plugin-tracking-personalise' ); ?>
                </p>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            $('#ptp-generate-from-order').on('click', function(e) {
                e.preventDefault();
                
                const $btn = $(this);
                const orderId = <?php echo absint( $order_id ); ?>;
                
                if (!confirm('✨ Créer un envoi automatiquement avec les infos de cette commande ?\n\nCela va :\n- Générer un numéro de tracking\n- Créer l\'envoi\n- Récupérer les infos client\n- Lier à cette commande')) {
                    return;
                }
                
                $btn.prop('disabled', true).text('⏳ Création en cours...');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'ptp_create_shipment_from_order',
                        order_id: orderId,
                        nonce: '<?php echo wp_create_nonce( 'ptp_create_shipment' ); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('✅ Envoi créé avec succès !\n\n📦 Tracking: ' + response.data.tracking + '\n\nLa page va se recharger...');
                            location.reload();
                        } else {
                            alert('❌ Erreur : ' + response.data.message);
                            $btn.prop('disabled', false).html('✨ Générer et créer automatiquement avec infos client');
                        }
                    },
                    error: function() {
                        alert('❌ Erreur de connexion au serveur');
                        $btn.prop('disabled', false).html('✨ Générer et créer automatiquement avec infos client');
                    }
                });
            });

            $('.ptp-remove-tracking').on('click', function(e) {
                e.preventDefault();
                const tracking = $(this).data('tracking');
                if (confirm('Supprimer le lien avec le tracking: ' + tracking + ' ?')) {
                    $('<input>').attr({
                        type: 'hidden',
                        name: 'ptp_remove_tracking',
                        value: tracking
                    }).appendTo('form');
                    $('form').submit();
                }
            });
        });
        </script>
        <?php
    }

    /**
     * Save order tracking information.
     */
    public function save_order_tracking( int $order_id, $post ): void {
        // Verify nonce
        if ( ! isset( $_POST['ptp_order_tracking_nonce'] ) || ! wp_verify_nonce( $_POST['ptp_order_tracking_nonce'], 'ptp_order_tracking' ) ) {
            return;
        }

        // Check permissions
        if ( ! current_user_can( 'edit_shop_order', $order_id ) && ! current_user_can( 'edit_shop_orders' ) ) {
            return;
        }

        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            return;
        }

        // Get existing tracking numbers
        $tracking_numbers = $order->get_meta( '_ptp_tracking_numbers', true );
        if ( ! is_array( $tracking_numbers ) ) {
            $tracking_numbers = [];
        }

        // Handle remove tracking
        if ( isset( $_POST['ptp_remove_tracking'] ) ) {
            $remove = sanitize_text_field( $_POST['ptp_remove_tracking'] );
            $tracking_numbers = array_diff( $tracking_numbers, [ $remove ] );
            $order->update_meta_data( '_ptp_tracking_numbers', array_values( $tracking_numbers ) );
            $order->save();
            return;
        }

        // Handle add new tracking
        $new_tracking = isset( $_POST['ptp_tracking_new'] ) ? PTP_Helper::sanitize_tracking_number( $_POST['ptp_tracking_new'] ) : '';
        
        if ( ! empty( $new_tracking ) && ! in_array( $new_tracking, $tracking_numbers, true ) ) {
            $tracking_numbers[] = $new_tracking;
            $order->update_meta_data( '_ptp_tracking_numbers', $tracking_numbers );
            $order->save();
            
            // Add order note
            $order->add_order_note(
                sprintf(
                    __( '📦 Numéro de tracking ajouté : %s', 'plugin-tracking-personalise' ),
                    $new_tracking
                )
            );
        }
    }

    /**
     * Create shipment from WooCommerce order.
     */
    private function create_shipment_from_order( \WC_Order $order, string $tracking_number ): int {
        $shipment_id = wp_insert_post( [
            'post_title'  => sprintf( __( 'Envoi pour commande #%d', 'plugin-tracking-personalise' ), $order->get_id() ),
            'post_type'   => 'ptp_shipment',
            'post_status' => 'publish',
        ] );

        if ( $shipment_id && ! is_wp_error( $shipment_id ) ) {
            update_post_meta( $shipment_id, '_ptp_tracking_number', $tracking_number );
            update_post_meta( $shipment_id, '_ptp_status', 'pending' );
            update_post_meta( $shipment_id, '_ptp_order_id', $order->get_id() );
            update_post_meta( $shipment_id, '_ptp_customer_name', $order->get_formatted_billing_full_name() );
            update_post_meta( $shipment_id, '_ptp_customer_email', $order->get_billing_email() );

            // Add initial event
            PTP_Database::insert_event(
                $shipment_id,
                current_time( 'mysql' ),
                'pending',
                '',
                __( 'Envoi créé depuis la commande WooCommerce', 'plugin-tracking-personalise' )
            );
        }

        return $shipment_id;
    }

    /**
     * Display tracking info in order view.
     */
    public function display_tracking_in_order( int $order_id ): void {
        $this->display_tracking_info( $order_id );
    }

    /**
     * Display tracking info after order table.
     */
    public function display_tracking_after_table( \WC_Order $order ): void {
        $this->display_tracking_info( $order->get_id() );
    }

    /**
     * Display tracking information.
     */
    private function display_tracking_info( int $order_id ): void {
        $shipment_id = get_post_meta( $order_id, '_ptp_shipment_id', true );

        if ( ! $shipment_id ) {
            return;
        }

        $shipment = get_post( $shipment_id );
        if ( ! $shipment ) {
            return;
        }

        $tracking_number = get_post_meta( $shipment_id, '_ptp_tracking_number', true );
        $carrier         = get_post_meta( $shipment_id, '_ptp_carrier', true );
        $status          = get_post_meta( $shipment_id, '_ptp_status', true );

        $carriers     = PTP_Helper::get_carriers();
        $statuses     = PTP_Helper::get_statuses();
        $status_label = $statuses[ $status ] ?? $status;
        $carrier_label = $carriers[ $carrier ] ?? $carrier;

        $tracking_page = get_option( 'ptp_page_suivi_detail', 0 );
        $tracking_url  = '';
        if ( $tracking_page ) {
            $tracking_url = add_query_arg( 'tracking_number', $tracking_number, get_permalink( $tracking_page ) );
        }
        ?>
        <section class="woocommerce-order-tracking">
            <h2><?php esc_html_e( 'Informations de suivi', 'plugin-tracking-personalise' ); ?></h2>
            <table class="woocommerce-table woocommerce-table--order-tracking">
                <tbody>
                    <tr>
                        <th><?php esc_html_e( 'Numéro de suivi:', 'plugin-tracking-personalise' ); ?></th>
                        <td><strong><?php echo esc_html( $tracking_number ); ?></strong></td>
                    </tr>
                    <?php if ( $carrier ) : ?>
                        <tr>
                            <th><?php esc_html_e( 'Transporteur:', 'plugin-tracking-personalise' ); ?></th>
                            <td><?php echo esc_html( $carrier_label ); ?></td>
                        </tr>
                    <?php endif; ?>
                    <tr>
                        <th><?php esc_html_e( 'Statut:', 'plugin-tracking-personalise' ); ?></th>
                        <td><?php echo esc_html( $status_label ); ?></td>
                    </tr>
                    <?php if ( $tracking_url ) : ?>
                        <tr>
                            <th><?php esc_html_e( 'Suivi complet:', 'plugin-tracking-personalise' ); ?></th>
                            <td>
                                <a href="<?php echo esc_url( $tracking_url ); ?>" class="button">
                                    <?php esc_html_e( 'Suivre mon colis', 'plugin-tracking-personalise' ); ?>
                                </a>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>
        <?php
    }

    /**
     * Add tracking info to order emails.
     */
    public function add_tracking_to_email( \WC_Order $order, bool $sent_to_admin, bool $plain_text, $email ): void {
        $shipment_id = get_post_meta( $order->get_id(), '_ptp_shipment_id', true );

        if ( ! $shipment_id ) {
            return;
        }

        $tracking_number = get_post_meta( $shipment_id, '_ptp_tracking_number', true );
        $carrier         = get_post_meta( $shipment_id, '_ptp_carrier', true );
        $status          = get_post_meta( $shipment_id, '_ptp_status', true );

        if ( ! $tracking_number ) {
            return;
        }

        $carriers      = PTP_Helper::get_carriers();
        $statuses      = PTP_Helper::get_statuses();
        $status_label  = $statuses[ $status ] ?? $status;
        $carrier_label = $carriers[ $carrier ] ?? $carrier;

        $tracking_page = get_option( 'ptp_page_suivi_detail', 0 );
        $tracking_url  = '';
        if ( $tracking_page ) {
            $tracking_url = add_query_arg( 'tracking_number', $tracking_number, get_permalink( $tracking_page ) );
        }

        if ( $plain_text ) {
            echo "\n" . esc_html__( 'INFORMATIONS DE SUIVI', 'plugin-tracking-personalise' ) . "\n";
            echo esc_html__( 'Numéro de suivi:', 'plugin-tracking-personalise' ) . ' ' . esc_html( $tracking_number ) . "\n";
            if ( $carrier ) {
                echo esc_html__( 'Transporteur:', 'plugin-tracking-personalise' ) . ' ' . esc_html( $carrier_label ) . "\n";
            }
            echo esc_html__( 'Statut:', 'plugin-tracking-personalise' ) . ' ' . esc_html( $status_label ) . "\n";
            if ( $tracking_url ) {
                echo esc_html__( 'Suivre votre colis:', 'plugin-tracking-personalise' ) . ' ' . esc_url( $tracking_url ) . "\n";
            }
            echo "\n";
        } else {
            ?>
            <h2><?php esc_html_e( 'Informations de suivi', 'plugin-tracking-personalise' ); ?></h2>
            <table cellspacing="0" cellpadding="6" style="width: 100%; border: 1px solid #eee;">
                <tbody>
                    <tr>
                        <th style="text-align:left; border: 1px solid #eee;"><?php esc_html_e( 'Numéro de suivi:', 'plugin-tracking-personalise' ); ?></th>
                        <td style="text-align:left; border: 1px solid #eee;"><strong><?php echo esc_html( $tracking_number ); ?></strong></td>
                    </tr>
                    <?php if ( $carrier ) : ?>
                        <tr>
                            <th style="text-align:left; border: 1px solid #eee;"><?php esc_html_e( 'Transporteur:', 'plugin-tracking-personalise' ); ?></th>
                            <td style="text-align:left; border: 1px solid #eee;"><?php echo esc_html( $carrier_label ); ?></td>
                        </tr>
                    <?php endif; ?>
                    <tr>
                        <th style="text-align:left; border: 1px solid #eee;"><?php esc_html_e( 'Statut:', 'plugin-tracking-personalise' ); ?></th>
                        <td style="text-align:left; border: 1px solid #eee;"><?php echo esc_html( $status_label ); ?></td>
                    </tr>
                    <?php if ( $tracking_url ) : ?>
                        <tr>
                            <th style="text-align:left; border: 1px solid #eee;"><?php esc_html_e( 'Suivi complet:', 'plugin-tracking-personalise' ); ?></th>
                            <td style="text-align:left; border: 1px solid #eee;">
                                <a href="<?php echo esc_url( $tracking_url ); ?>"><?php esc_html_e( 'Suivre mon colis', 'plugin-tracking-personalise' ); ?></a>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <?php
        }
    }

    /**
     * AJAX: Create shipment from order.
     */
    public function ajax_create_shipment_from_order(): void {
        check_ajax_referer( 'ptp_create_shipment', 'nonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( [ 'message' => __( 'Accès refusé', 'plugin-tracking-personalise' ) ] );
        }

        $order_id = absint( $_POST['order_id'] ?? 0 );
        if ( ! $order_id ) {
            wp_send_json_error( [ 'message' => __( 'Commande invalide', 'plugin-tracking-personalise' ) ] );
        }

        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            wp_send_json_error( [ 'message' => __( 'Commande introuvable', 'plugin-tracking-personalise' ) ] );
        }

        // Generate tracking number
        if ( ! class_exists( 'PTP_Tracking_Generator' ) ) {
            wp_send_json_error( [ 'message' => __( 'Générateur de tracking non disponible', 'plugin-tracking-personalise' ) ] );
        }

        $tracking = PTP_Tracking_Generator::generate();

        // Get customer info
        $customer_name  = trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() );
        $customer_email = $order->get_billing_email();
        $customer_phone = $order->get_billing_phone();

        // Get shipping address
        $shipping_parts = [];
        if ( $order->get_shipping_address_1() ) {
            $shipping_parts[] = $order->get_shipping_address_1();
        }
        if ( $order->get_shipping_address_2() ) {
            $shipping_parts[] = $order->get_shipping_address_2();
        }
        if ( $order->get_shipping_city() ) {
            $shipping_parts[] = $order->get_shipping_city();
        }
        if ( $order->get_shipping_postcode() ) {
            $shipping_parts[] = $order->get_shipping_postcode();
        }
        if ( $order->get_shipping_state() ) {
            $shipping_parts[] = $order->get_shipping_state();
        }
        if ( $order->get_shipping_country() ) {
            $shipping_parts[] = WC()->countries->countries[ $order->get_shipping_country() ] ?? $order->get_shipping_country();
        }

        $shipping_address = implode( ', ', array_filter( $shipping_parts ) );

        // Fallback to billing if no shipping
        if ( empty( $shipping_address ) ) {
            $billing_parts = [];
            if ( $order->get_billing_address_1() ) {
                $billing_parts[] = $order->get_billing_address_1();
            }
            if ( $order->get_billing_city() ) {
                $billing_parts[] = $order->get_billing_city();
            }
            if ( $order->get_billing_postcode() ) {
                $billing_parts[] = $order->get_billing_postcode();
            }
            if ( $order->get_billing_country() ) {
                $billing_parts[] = WC()->countries->countries[ $order->get_billing_country() ] ?? $order->get_billing_country();
            }
            $shipping_address = implode( ', ', array_filter( $billing_parts ) );
        }

        // Create shipment
        $shipment_id = wp_insert_post( [
            'post_type'   => 'ptp_shipment',
            'post_title'  => $tracking,
            'post_status' => 'publish',
        ] );

        if ( is_wp_error( $shipment_id ) ) {
            wp_send_json_error( [ 'message' => __( 'Erreur lors de la création de l\'envoi', 'plugin-tracking-personalise' ) ] );
        }

        // Save shipment meta
        update_post_meta( $shipment_id, '_ptp_tracking_number', $tracking );
        update_post_meta( $shipment_id, '_ptp_carrier', 'other' );
        update_post_meta( $shipment_id, '_ptp_status_global', 'label_created' );
        update_post_meta( $shipment_id, '_ptp_sender', get_bloginfo( 'name' ) );
        update_post_meta( $shipment_id, '_ptp_destination', $shipping_address );
        update_post_meta( $shipment_id, '_ptp_email_allowed', $customer_email );
        update_post_meta( $shipment_id, '_ptp_order_id', $order_id );

        // Build notes with all customer info
        $notes_parts = [
            sprintf( __( 'Commande WooCommerce : #%s', 'plugin-tracking-personalise' ), $order->get_order_number() ),
            sprintf( __( 'Client : %s', 'plugin-tracking-personalise' ), $customer_name ),
            sprintf( __( 'Email : %s', 'plugin-tracking-personalise' ), $customer_email ),
        ];

        if ( $customer_phone ) {
            $notes_parts[] = sprintf( __( 'Téléphone : %s', 'plugin-tracking-personalise' ), $customer_phone );
        }

        $notes_parts[] = sprintf( __( 'Adresse : %s', 'plugin-tracking-personalise' ), $shipping_address );

        update_post_meta( $shipment_id, '_ptp_notes', implode( "\n", $notes_parts ) );

        // Create first event
        PTP_Database::insert_event( $shipment_id, current_time( 'mysql' ), 'label_created', get_bloginfo( 'name' ), sprintf(
            __( 'Étiquette créée automatiquement pour la commande #%s', 'plugin-tracking-personalise' ),
            $order->get_order_number()
        ) );

        // Link tracking to order
        $existing = $order->get_meta( '_ptp_tracking_numbers', true );
        if ( ! is_array( $existing ) ) {
            $existing = [];
        }
        $existing[] = $tracking;
        $order->update_meta_data( '_ptp_tracking_numbers', $existing );
        $order->save();

        // Add order note
        $order->add_order_note(
            sprintf(
                __( '📦 Numéro de tracking généré automatiquement : %s', 'plugin-tracking-personalise' ),
                $tracking
            )
        );

        wp_send_json_success( [
            'tracking'    => $tracking,
            'shipment_id' => $shipment_id,
            'message'     => __( 'Envoi créé avec succès', 'plugin-tracking-personalise' ),
            'customer'    => [
                'name'    => $customer_name,
                'email'   => $customer_email,
                'phone'   => $customer_phone,
                'address' => $shipping_address,
            ],
        ] );
    }

    /**
     * Add tracking column to order list (CPT).
     */
    public function add_tracking_column( array $columns ): array {
        $new_columns = [];
        
        foreach ( $columns as $key => $label ) {
            $new_columns[ $key ] = $label;
            
            // Insert after "Order Status"
            if ( 'order_status' === $key ) {
                $new_columns['ptp_tracking'] = __( '📦 Tracking', 'plugin-tracking-personalise' );
            }
        }
        
        return $new_columns;
    }

    /**
     * Render tracking column content (CPT).
     */
    public function render_tracking_column( string $column, int $post_id ): void {
        if ( 'ptp_tracking' !== $column ) {
            return;
        }
        
        $tracking_numbers = get_post_meta( $post_id, '_ptp_tracking_numbers', true );
        
        if ( empty( $tracking_numbers ) || ! is_array( $tracking_numbers ) ) {
            echo '<span style="color:#999;">—</span>';
            return;
        }
        
        foreach ( $tracking_numbers as $tracking ) {
            $shipment = PTP_Helper::get_shipment_by_tracking( $tracking );
            if ( $shipment ) {
                $status = get_post_meta( $shipment->ID, '_ptp_status_global', true );
                $badge_color = PTP_Helper::get_status_color( $status );
                
                echo sprintf(
                    '<a href="%s" style="display:inline-block;padding:4px 8px;background:%s;color:#fff;border-radius:4px;text-decoration:none;font-size:11px;margin:2px 0;">%s</a><br>',
                    esc_url( admin_url( 'admin.php?page=ptp-edit&id=' . $shipment->ID ) ),
                    esc_attr( $badge_color ),
                    esc_html( $tracking )
                );
            } else {
                echo '<code style="font-size:11px;">' . esc_html( $tracking ) . '</code><br>';
            }
        }
    }

    /**
     * Add tracking column to HPOS order list.
     */
    public function add_tracking_column_hpos( array $columns ): array {
        return $this->add_tracking_column( $columns );
    }

    /**
     * Render tracking column for HPOS.
     */
    public function render_tracking_column_hpos( string $column, $order ): void {
        if ( 'ptp_tracking' !== $column ) {
            return;
        }
        
        if ( is_numeric( $order ) ) {
            $order = wc_get_order( $order );
        }
        
        if ( ! $order ) {
            return;
        }
        
        $tracking_numbers = $order->get_meta( '_ptp_tracking_numbers', true );
        
        if ( empty( $tracking_numbers ) || ! is_array( $tracking_numbers ) ) {
            echo '<span style="color:#999;">—</span>';
            return;
        }
        
        foreach ( $tracking_numbers as $tracking ) {
            $shipment = PTP_Helper::get_shipment_by_tracking( $tracking );
            if ( $shipment ) {
                $status = get_post_meta( $shipment->ID, '_ptp_status_global', true );
                $badge_color = PTP_Helper::get_status_color( $status );
                
                echo sprintf(
                    '<a href="%s" style="display:inline-block;padding:4px 8px;background:%s;color:#fff;border-radius:4px;text-decoration:none;font-size:11px;margin:2px 0;">%s</a><br>',
                    esc_url( admin_url( 'admin.php?page=ptp-edit&id=' . $shipment->ID ) ),
                    esc_attr( $badge_color ),
                    esc_html( $tracking )
                );
            } else {
                echo '<code style="font-size:11px;">' . esc_html( $tracking ) . '</code><br>';
            }
        }
    }
}
