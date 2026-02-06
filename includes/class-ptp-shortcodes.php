<?php
/**
 * Shortcodes for public tracking lookup and display.
 */

defined( 'ABSPATH' ) || exit;

class PTP_Shortcodes {

    /**
     * Register all shortcodes.
     */
    public static function register(): void {
        add_shortcode( 'ptp_tracking_lookup', [ __CLASS__, 'render_lookup_form' ] );
        add_shortcode( 'ptp_tracking_result', [ __CLASS__, 'render_tracking_result' ] );

        // Enqueue public assets
        add_action( 'wp_enqueue_scripts', [ __CLASS__, 'enqueue_scripts' ] );

        // AJAX handlers for event checking
        add_action( 'wp_ajax_ptp_check_events', [ __CLASS__, 'ajax_check_events' ] );
        add_action( 'wp_ajax_nopriv_ptp_check_events', [ __CLASS__, 'ajax_check_events' ] );
    }

    /**
     * Enqueue public scripts and styles.
     */
    public static function enqueue_scripts(): void {
        wp_enqueue_style(
            'ptp-public',
            PTP_PLUGIN_URL . 'assets/css/ptp-public.css',
            [],
            PTP_VERSION
        );

        wp_enqueue_script(
            'ptp-public',
            PTP_PLUGIN_URL . 'assets/js/ptp-public.js',
            [ 'jquery' ],
            PTP_VERSION,
            true
        );
    }

    /**
     * Render tracking lookup form.
     */
    public static function render_lookup_form( array $atts ): string {
        $atts = shortcode_atts( [
            'redirect' => get_option( 'ptp_page_suivi_detail', 0 ),
        ], $atts );

        $require_email = get_option( 'ptp_require_email', '0' ) === '1';

        ob_start();
        ?>
        <div class="ptp-tracking-lookup">
            <form method="get" action="<?php echo esc_url( get_permalink( $atts['redirect'] ) ); ?>" class="ptp-lookup-form">
                <div class="ptp-form-field">
                    <label for="ptp_tracking_number"><?php esc_html_e( 'Numéro de suivi', 'plugin-tracking-personalise' ); ?></label>
                    <input type="text" id="ptp_tracking_number" name="tracking_number" required placeholder="<?php esc_attr_e( 'Ex: 1Z999AA10123456784', 'plugin-tracking-personalise' ); ?>">
                </div>

                <?php if ( $require_email ) : ?>
                    <div class="ptp-form-field">
                        <label for="ptp_email"><?php esc_html_e( 'Email', 'plugin-tracking-personalise' ); ?></label>
                        <input type="email" id="ptp_email" name="email" required placeholder="<?php esc_attr_e( 'votre@email.com', 'plugin-tracking-personalise' ); ?>">
                    </div>
                <?php endif; ?>

                <button type="submit" class="ptp-submit-btn">
                    <?php esc_html_e( 'Suivre mon colis', 'plugin-tracking-personalise' ); ?>
                </button>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render tracking result.
     */
    public static function render_tracking_result( array $atts ): string {
        $tracking_number = sanitize_text_field( $_GET['tracking_number'] ?? '' );
        $email           = sanitize_email( $_GET['email'] ?? '' );

        if ( empty( $tracking_number ) ) {
            return '<div class="ptp-error">' . esc_html__( 'Veuillez fournir un numéro de suivi.', 'plugin-tracking-personalise' ) . '</div>';
        }

        $tracking_number = PTP_Helper::sanitize_tracking_number( $tracking_number );
        $shipment        = PTP_Helper::get_shipment_by_tracking( $tracking_number );

        if ( ! $shipment ) {
            return '<div class="ptp-error">' . esc_html__( 'Aucun envoi trouvé avec ce numéro de suivi.', 'plugin-tracking-personalise' ) . '</div>';
        }

        // Verify email if required
        $require_email = get_option( 'ptp_require_email', '0' ) === '1';
        if ( $require_email ) {
            if ( empty( $email ) || ! PTP_Helper::verify_shipment_email( $shipment->ID, $email ) ) {
                return '<div class="ptp-error">' . esc_html__( 'Email invalide ou incorrect.', 'plugin-tracking-personalise' ) . '</div>';
            }
        }

        return self::render_shipment_details( $shipment );
    }

    /**
     * Render shipment details and timeline.
     */
    private static function render_shipment_details( \WP_Post $shipment ): string {
        $tracking_number = get_post_meta( $shipment->ID, '_ptp_tracking_number', true );
        $carrier         = get_post_meta( $shipment->ID, '_ptp_carrier', true );
        $status          = get_post_meta( $shipment->ID, '_ptp_status', true );
        $customer_name   = get_post_meta( $shipment->ID, '_ptp_customer_name', true );

        $carriers = PTP_Helper::get_carriers();
        $statuses = PTP_Helper::get_statuses();
        $events   = PTP_Database::get_events( $shipment->ID );

        $status_label = $statuses[ $status ] ?? $status;
        $carrier_label = $carriers[ $carrier ] ?? $carrier;
        $progress = PTP_Helper::get_status_progress( $status );
        $status_class = PTP_Helper::get_status_class( $status );

        ob_start();
        ?>
        <div class="ptp-tracking-result">
            <div class="ptp-result-header">
                <h2><?php esc_html_e( 'Informations de suivi', 'plugin-tracking-personalise' ); ?></h2>
                <div class="ptp-tracking-info">
                    <div class="ptp-info-item">
                        <strong><?php esc_html_e( 'Numéro de suivi:', 'plugin-tracking-personalise' ); ?></strong>
                        <span><?php echo esc_html( $tracking_number ); ?></span>
                    </div>
                    <?php if ( $carrier ) : ?>
                        <div class="ptp-info-item">
                            <strong><?php esc_html_e( 'Transporteur:', 'plugin-tracking-personalise' ); ?></strong>
                            <span><?php echo esc_html( $carrier_label ); ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if ( $customer_name ) : ?>
                        <div class="ptp-info-item">
                            <strong><?php esc_html_e( 'Destinataire:', 'plugin-tracking-personalise' ); ?></strong>
                            <span><?php echo esc_html( $customer_name ); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="ptp-status-section">
                <div class="ptp-current-status <?php echo esc_attr( $status_class ); ?>">
                    <h3><?php echo esc_html( $status_label ); ?></h3>
                </div>

                <div class="ptp-progress-bar">
                    <div class="ptp-progress-fill" style="width: <?php echo esc_attr( $progress ); ?>%"></div>
                </div>
                <div class="ptp-progress-label"><?php echo esc_html( $progress ); ?>%</div>
            </div>

            <?php if ( ! empty( $events ) ) : ?>
                <div class="ptp-timeline">
                    <h3><?php esc_html_e( 'Historique de suivi', 'plugin-tracking-personalise' ); ?></h3>
                    <div class="ptp-timeline-events">
                        <?php foreach ( $events as $event ) : ?>
                            <?php
                            $event_status = $statuses[ $event['status'] ] ?? $event['status'];
                            $event_class  = PTP_Helper::get_status_class( $event['status'] );
                            ?>
                            <div class="ptp-timeline-event <?php echo esc_attr( $event_class ); ?>">
                                <div class="ptp-timeline-dot"></div>
                                <div class="ptp-timeline-content">
                                    <div class="ptp-event-date"><?php echo esc_html( PTP_Helper::format_date( $event['event_date'] ) ); ?></div>
                                    <div class="ptp-event-status"><?php echo esc_html( $event_status ); ?></div>
                                    <?php if ( ! empty( $event['location'] ) ) : ?>
                                        <div class="ptp-event-location"><?php echo esc_html( $event['location'] ); ?></div>
                                    <?php endif; ?>
                                    <?php if ( ! empty( $event['description'] ) ) : ?>
                                        <div class="ptp-event-description"><?php echo esc_html( $event['description'] ); ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Auto-refresh events -->
            <script>
            (function() {
                const shipmentId = <?php echo absint( $shipment->ID ); ?>;
                const lastEventTime = '<?php echo esc_js( ! empty( $events ) ? $events[0]['created_at'] : '' ); ?>';
                
                // Check for new events every 30 seconds
                setInterval(function() {
                    checkNewEvents(shipmentId, lastEventTime);
                }, 30000);
            })();

            function checkNewEvents(shipmentId, lastTime) {
                fetch('<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'action=ptp_check_events&shipment_id=' + shipmentId + '&last_time=' + lastTime
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success && data.data.has_new) {
                        // Show notification
                        showNewEventNotification();
                        // Reload page after 2 seconds
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    }
                });
            }

            function showNewEventNotification() {
                const notification = document.createElement('div');
                notification.className = 'ptp-notification';
                notification.innerHTML = '🔔 <?php esc_html_e( 'Nouveaux événements disponibles...', 'plugin-tracking-personalise' ); ?>';
                notification.style.cssText = 'position:fixed;top:20px;right:20px;background:#28a745;color:#fff;padding:15px 25px;border-radius:8px;box-shadow:0 4px 12px rgba(0,0,0,0.15);z-index:9999;animation:slideIn 0.3s ease;';
                document.body.appendChild(notification);
            }
            </script>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * AJAX: Check for new events.
     */
    public static function ajax_check_events(): void {
        $shipment_id = absint( $_POST['shipment_id'] ?? 0 );
        $last_time   = sanitize_text_field( $_POST['last_time'] ?? '' );
        
        if ( ! $shipment_id ) {
            wp_send_json_error();
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'ptp_tracking_events';
        
        $count = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE shipment_id = %d AND created_at > %s",
            $shipment_id,
            $last_time
        ) );
        
        wp_send_json_success( [
            'has_new' => ( $count > 0 ),
            'count'   => (int) $count,
        ] );
    }
}
