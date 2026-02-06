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

        wp_nonce_field( 'ptp_order_tracking', 'ptp_order_tracking_nonce' );

        $shipment_id     = get_post_meta( $order_id, '_ptp_shipment_id', true );
        $tracking_number = get_post_meta( $order_id, '_ptp_tracking_number', true );
        ?>
        <div class="ptp-order-tracking-meta">
            <?php if ( $shipment_id ) : ?>
                <?php
                $shipment = get_post( $shipment_id );
                if ( $shipment ) :
                    $tracking_number = get_post_meta( $shipment_id, '_ptp_tracking_number', true );
                    $status          = get_post_meta( $shipment_id, '_ptp_status', true );
                    $statuses        = PTP_Helper::get_statuses();
                    ?>
                    <p>
                        <strong><?php esc_html_e( 'Envoi lié:', 'plugin-tracking-personalise' ); ?></strong><br>
                        <a href="<?php echo esc_url( get_edit_post_link( $shipment_id ) ); ?>" target="_blank">
                            <?php echo esc_html( $tracking_number ); ?>
                        </a>
                    </p>
                    <p>
                        <strong><?php esc_html_e( 'Statut:', 'plugin-tracking-personalise' ); ?></strong><br>
                        <?php echo esc_html( $statuses[ $status ] ?? $status ); ?>
                    </p>
                <?php else : ?>
                    <p><?php esc_html_e( 'Envoi supprimé', 'plugin-tracking-personalise' ); ?></p>
                <?php endif; ?>
            <?php else : ?>
                <p>
                    <label for="ptp_tracking_number"><?php esc_html_e( 'Numéro de suivi', 'plugin-tracking-personalise' ); ?></label>
                    <input type="text" id="ptp_tracking_number" name="ptp_tracking_number" value="<?php echo esc_attr( $tracking_number ); ?>" class="widefat">
                </p>
                <p class="description">
                    <?php esc_html_e( 'Enregistrez pour créer automatiquement un envoi de suivi.', 'plugin-tracking-personalise' ); ?>
                </p>
            <?php endif; ?>
        </div>
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

        // Check if tracking number is set
        $tracking_number = isset( $_POST['ptp_tracking_number'] ) ? PTP_Helper::sanitize_tracking_number( $_POST['ptp_tracking_number'] ) : '';

        if ( empty( $tracking_number ) ) {
            return;
        }

        // Check if shipment already exists
        $shipment_id = get_post_meta( $order_id, '_ptp_shipment_id', true );
        if ( $shipment_id ) {
            return;
        }

        // Get order object
        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            return;
        }

        // Create shipment
        $shipment_id = $this->create_shipment_from_order( $order, $tracking_number );

        if ( $shipment_id ) {
            update_post_meta( $order_id, '_ptp_shipment_id', $shipment_id );
            update_post_meta( $order_id, '_ptp_tracking_number', $tracking_number );
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
        check_ajax_referer( 'ptp_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'edit_shop_orders' ) ) {
            wp_send_json_error( __( 'Permission refusée', 'plugin-tracking-personalise' ) );
        }

        $order_id        = absint( $_POST['order_id'] ?? 0 );
        $tracking_number = sanitize_text_field( $_POST['tracking_number'] ?? '' );

        if ( ! $order_id || ! $tracking_number ) {
            wp_send_json_error( __( 'Données invalides', 'plugin-tracking-personalise' ) );
        }

        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            wp_send_json_error( __( 'Commande introuvable', 'plugin-tracking-personalise' ) );
        }

        $shipment_id = $this->create_shipment_from_order( $order, $tracking_number );

        if ( $shipment_id ) {
            update_post_meta( $order_id, '_ptp_shipment_id', $shipment_id );
            update_post_meta( $order_id, '_ptp_tracking_number', $tracking_number );

            wp_send_json_success( [
                'message'     => __( 'Envoi créé avec succès', 'plugin-tracking-personalise' ),
                'shipment_id' => $shipment_id,
            ] );
        } else {
            wp_send_json_error( __( 'Erreur lors de la création', 'plugin-tracking-personalise' ) );
        }
    }
}
