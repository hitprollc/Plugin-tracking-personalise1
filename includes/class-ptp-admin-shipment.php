<?php
/**
 * Admin shipment editing interface with metaboxes.
 */

defined( 'ABSPATH' ) || exit;

class PTP_Admin_Shipment {

    public function __construct() {
        add_action( 'add_meta_boxes', [ $this, 'add_meta_boxes' ] );
        add_action( 'save_post_ptp_shipment', [ $this, 'save_shipment' ], 10, 2 );
        add_action( 'before_delete_post', [ $this, 'delete_shipment_events' ] );
        add_filter( 'manage_ptp_shipment_posts_columns', [ $this, 'set_columns' ] );
        add_action( 'manage_ptp_shipment_posts_custom_column', [ $this, 'render_column' ], 10, 2 );
        add_action( 'wp_ajax_ptp_add_event', [ $this, 'ajax_add_event' ] );
        add_action( 'wp_ajax_ptp_delete_event', [ $this, 'ajax_delete_event' ] );
        add_action( 'wp_ajax_ptp_generate_tracking', [ $this, 'ajax_generate_tracking' ] );
    }

    /**
     * Add metaboxes.
     */
    public function add_meta_boxes(): void {
        add_meta_box(
            'ptp_shipment_details',
            __( 'Détails de l\'envoi', 'plugin-tracking-personalise' ),
            [ $this, 'render_details_metabox' ],
            'ptp_shipment',
            'normal',
            'high'
        );

        add_meta_box(
            'ptp_tracking_events',
            __( 'Événements de suivi', 'plugin-tracking-personalise' ),
            [ $this, 'render_events_metabox' ],
            'ptp_shipment',
            'normal',
            'high'
        );
    }

    /**
     * Render shipment details metabox.
     */
    public function render_details_metabox( \WP_Post $post ): void {
        wp_nonce_field( 'ptp_save_shipment', 'ptp_shipment_nonce' );

        $tracking_number = get_post_meta( $post->ID, '_ptp_tracking_number', true );
        $carrier         = get_post_meta( $post->ID, '_ptp_carrier', true );
        $status          = get_post_meta( $post->ID, '_ptp_status', true );
        $customer_name   = get_post_meta( $post->ID, '_ptp_customer_name', true );
        $customer_email  = get_post_meta( $post->ID, '_ptp_customer_email', true );
        $order_id        = get_post_meta( $post->ID, '_ptp_order_id', true );

        $statuses = PTP_Helper::get_statuses();
        $carriers = PTP_Helper::get_carriers();
        ?>
        <table class="form-table">
            <tr>
                <th><label for="ptp_tracking_number"><?php esc_html_e( 'Numéro de suivi', 'plugin-tracking-personalise' ); ?> *</label></th>
                <td>
                    <div style="display:flex;gap:8px;align-items:center;">
                        <input type="text" id="ptp_tracking_number" name="ptp_tracking_number" value="<?php echo esc_attr( $tracking_number ); ?>" class="regular-text" required>
                        <?php if ( get_option( 'ptp_auto_generate', 1 ) ): ?>
                            <button type="button" id="ptp-generate-tracking" class="button button-secondary">
                                🔄 <?php esc_html_e( 'Générer', 'plugin-tracking-personalise' ); ?>
                            </button>
                        <?php endif; ?>
                    </div>
                    <p class="description">
                        <?php esc_html_e( 'Numéro unique de suivi du colis', 'plugin-tracking-personalise' ); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th><label for="ptp_carrier"><?php esc_html_e( 'Transporteur', 'plugin-tracking-personalise' ); ?></label></th>
                <td>
                    <select id="ptp_carrier" name="ptp_carrier">
                        <option value=""><?php esc_html_e( 'Sélectionner...', 'plugin-tracking-personalise' ); ?></option>
                        <?php foreach ( $carriers as $key => $label ) : ?>
                            <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $carrier, $key ); ?>>
                                <?php echo esc_html( $label ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="ptp_status"><?php esc_html_e( 'Statut actuel', 'plugin-tracking-personalise' ); ?></label></th>
                <td>
                    <select id="ptp_status" name="ptp_status">
                        <?php foreach ( $statuses as $key => $label ) : ?>
                            <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $status, $key ); ?>>
                                <?php echo esc_html( $label ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="ptp_customer_name"><?php esc_html_e( 'Nom du client', 'plugin-tracking-personalise' ); ?></label></th>
                <td>
                    <input type="text" id="ptp_customer_name" name="ptp_customer_name" value="<?php echo esc_attr( $customer_name ); ?>" class="regular-text">
                </td>
            </tr>
            <tr>
                <th><label for="ptp_customer_email"><?php esc_html_e( 'Email du client', 'plugin-tracking-personalise' ); ?></label></th>
                <td>
                    <input type="email" id="ptp_customer_email" name="ptp_customer_email" value="<?php echo esc_attr( $customer_email ); ?>" class="regular-text">
                </td>
            </tr>
            <tr>
                <th><label for="ptp_order_id"><?php esc_html_e( 'ID de commande WooCommerce', 'plugin-tracking-personalise' ); ?></label></th>
                <td>
                    <input type="number" id="ptp_order_id" name="ptp_order_id" value="<?php echo esc_attr( $order_id ); ?>" class="small-text">
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Render tracking events metabox.
     */
    public function render_events_metabox( \WP_Post $post ): void {
        $events   = PTP_Database::get_events( $post->ID );
        $statuses = PTP_Helper::get_statuses();
        ?>
        <div id="ptp-events-container">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Date', 'plugin-tracking-personalise' ); ?></th>
                        <th><?php esc_html_e( 'Statut', 'plugin-tracking-personalise' ); ?></th>
                        <th><?php esc_html_e( 'Localisation', 'plugin-tracking-personalise' ); ?></th>
                        <th><?php esc_html_e( 'Description', 'plugin-tracking-personalise' ); ?></th>
                        <th><?php esc_html_e( 'Actions', 'plugin-tracking-personalise' ); ?></th>
                    </tr>
                </thead>
                <tbody id="ptp-events-list">
                    <?php if ( ! empty( $events ) ) : ?>
                        <?php foreach ( $events as $event ) : ?>
                            <tr data-event-id="<?php echo esc_attr( $event['id'] ); ?>">
                                <td><?php echo esc_html( PTP_Helper::format_date( $event['event_date'] ) ); ?></td>
                                <td><?php echo esc_html( $statuses[ $event['status'] ] ?? $event['status'] ); ?></td>
                                <td><?php echo esc_html( $event['location'] ); ?></td>
                                <td><?php echo esc_html( $event['description'] ); ?></td>
                                <td>
                                    <button type="button" class="button ptp-delete-event" data-event-id="<?php echo esc_attr( $event['id'] ); ?>">
                                        <?php esc_html_e( 'Supprimer', 'plugin-tracking-personalise' ); ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr class="ptp-no-events">
                            <td colspan="5"><?php esc_html_e( 'Aucun événement', 'plugin-tracking-personalise' ); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <div class="ptp-add-event-form">
                <h3><?php esc_html_e( 'Ajouter un événement', 'plugin-tracking-personalise' ); ?></h3>
                <table class="form-table">
                    <tr>
                        <th><label for="ptp_event_date"><?php esc_html_e( 'Date/Heure', 'plugin-tracking-personalise' ); ?></label></th>
                        <td><input type="datetime-local" id="ptp_event_date" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th><label for="ptp_event_status"><?php esc_html_e( 'Statut', 'plugin-tracking-personalise' ); ?></label></th>
                        <td>
                            <select id="ptp_event_status">
                                <?php foreach ( $statuses as $key => $label ) : ?>
                                    <option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="ptp_event_location"><?php esc_html_e( 'Localisation', 'plugin-tracking-personalise' ); ?></label></th>
                        <td><input type="text" id="ptp_event_location" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="ptp_event_description"><?php esc_html_e( 'Description', 'plugin-tracking-personalise' ); ?></label></th>
                        <td><textarea id="ptp_event_description" rows="3" class="large-text"></textarea></td>
                    </tr>
                </table>
                <button type="button" id="ptp-add-event-btn" class="button button-primary">
                    <?php esc_html_e( 'Ajouter l\'événement', 'plugin-tracking-personalise' ); ?>
                </button>
            </div>
        </div>
        <?php
    }

    /**
     * Save shipment meta data.
     */
    public function save_shipment( int $post_id, \WP_Post $post ): void {
        // Verify nonce
        if ( ! isset( $_POST['ptp_shipment_nonce'] ) || ! wp_verify_nonce( $_POST['ptp_shipment_nonce'], 'ptp_save_shipment' ) ) {
            return;
        }

        // Check autosave
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        // Check permissions
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        // Save tracking number
        if ( isset( $_POST['ptp_tracking_number'] ) ) {
            $tracking_number = PTP_Helper::sanitize_tracking_number( $_POST['ptp_tracking_number'] );
            update_post_meta( $post_id, '_ptp_tracking_number', $tracking_number );
        }

        // Save other fields
        $fields = [
            'ptp_carrier'        => '_ptp_carrier',
            'ptp_status'         => '_ptp_status',
            'ptp_customer_name'  => '_ptp_customer_name',
            'ptp_customer_email' => '_ptp_customer_email',
            'ptp_order_id'       => '_ptp_order_id',
        ];

        foreach ( $fields as $field => $meta_key ) {
            if ( isset( $_POST[ $field ] ) ) {
                update_post_meta( $post_id, $meta_key, sanitize_text_field( $_POST[ $field ] ) );
            }
        }
    }

    /**
     * Delete shipment events when post is deleted.
     */
    public function delete_shipment_events( int $post_id ): void {
        if ( get_post_type( $post_id ) === 'ptp_shipment' ) {
            PTP_Database::delete_shipment_events( $post_id );
        }
    }

    /**
     * Set custom columns for shipment list.
     */
    public function set_columns( array $columns ): array {
        $new_columns = [
            'cb'              => $columns['cb'],
            'title'           => __( 'Titre', 'plugin-tracking-personalise' ),
            'tracking_number' => __( 'Numéro de suivi', 'plugin-tracking-personalise' ),
            'carrier'         => __( 'Transporteur', 'plugin-tracking-personalise' ),
            'status'          => __( 'Statut', 'plugin-tracking-personalise' ),
            'customer'        => __( 'Client', 'plugin-tracking-personalise' ),
            'date'            => __( 'Date', 'plugin-tracking-personalise' ),
        ];

        return $new_columns;
    }

    /**
     * Render custom column content.
     */
    public function render_column( string $column, int $post_id ): void {
        switch ( $column ) {
            case 'tracking_number':
                echo esc_html( get_post_meta( $post_id, '_ptp_tracking_number', true ) );
                break;

            case 'carrier':
                $carrier  = get_post_meta( $post_id, '_ptp_carrier', true );
                $carriers = PTP_Helper::get_carriers();
                echo esc_html( $carriers[ $carrier ] ?? $carrier );
                break;

            case 'status':
                $status   = get_post_meta( $post_id, '_ptp_status', true );
                $statuses = PTP_Helper::get_statuses();
                $label    = $statuses[ $status ] ?? $status;
                $class    = PTP_Helper::get_status_class( $status );
                echo '<span class="' . esc_attr( $class ) . '">' . esc_html( $label ) . '</span>';
                break;

            case 'customer':
                $name  = get_post_meta( $post_id, '_ptp_customer_name', true );
                $email = get_post_meta( $post_id, '_ptp_customer_email', true );
                if ( $name ) {
                    echo esc_html( $name );
                    if ( $email ) {
                        echo '<br><small>' . esc_html( $email ) . '</small>';
                    }
                }
                break;
        }
    }

    /**
     * AJAX: Add tracking event.
     */
    public function ajax_add_event(): void {
        check_ajax_referer( 'ptp_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( __( 'Permission refusée', 'plugin-tracking-personalise' ) );
        }

        $shipment_id = absint( $_POST['shipment_id'] ?? 0 );
        $event_date  = sanitize_text_field( $_POST['event_date'] ?? '' );
        $status      = sanitize_text_field( $_POST['status'] ?? '' );
        $location    = sanitize_text_field( $_POST['location'] ?? '' );
        $description = sanitize_textarea_field( $_POST['description'] ?? '' );

        if ( ! $shipment_id || ! $event_date || ! $status ) {
            wp_send_json_error( __( 'Données invalides', 'plugin-tracking-personalise' ) );
        }

        // Convert datetime-local to MySQL format
        $event_date = date( 'Y-m-d H:i:s', strtotime( $event_date ) );

        $result = PTP_Database::insert_event( $shipment_id, $event_date, $status, $location, $description );

        if ( $result ) {
            wp_send_json_success( [
                'message' => __( 'Événement ajouté', 'plugin-tracking-personalise' ),
            ] );
        } else {
            wp_send_json_error( __( 'Erreur lors de l\'ajout', 'plugin-tracking-personalise' ) );
        }
    }

    /**
     * AJAX: Delete tracking event.
     */
    public function ajax_delete_event(): void {
        check_ajax_referer( 'ptp_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( __( 'Permission refusée', 'plugin-tracking-personalise' ) );
        }

        $event_id = absint( $_POST['event_id'] ?? 0 );

        if ( ! $event_id ) {
            wp_send_json_error( __( 'ID invalide', 'plugin-tracking-personalise' ) );
        }

        $result = PTP_Database::delete_event( $event_id );

        if ( $result ) {
            wp_send_json_success( [
                'message' => __( 'Événement supprimé', 'plugin-tracking-personalise' ),
            ] );
        } else {
            wp_send_json_error( __( 'Erreur lors de la suppression', 'plugin-tracking-personalise' ) );
        }
    }

    /**
     * AJAX handler to generate tracking number.
     */
    public function ajax_generate_tracking(): void {
        check_ajax_referer( 'ptp_admin_nonce', 'nonce' );
        
        if ( ! PTP_Helper::current_user_can_manage() ) {
            wp_send_json_error( [ 'message' => __( 'Accès refusé', 'plugin-tracking-personalise' ) ] );
        }
        
        if ( ! get_option( 'ptp_auto_generate', 1 ) ) {
            wp_send_json_error( [ 'message' => __( 'Génération désactivée', 'plugin-tracking-personalise' ) ] );
        }
        
        $tracking = PTP_Tracking_Generator::generate();
        
        wp_send_json_success( [
            'tracking' => $tracking,
            'message'  => __( 'Numéro généré avec succès', 'plugin-tracking-personalise' ),
        ] );
    }
}
