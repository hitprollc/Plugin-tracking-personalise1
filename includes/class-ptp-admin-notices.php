<?php
defined( 'ABSPATH' ) || exit;

/**
 * Admin notices.
 */
class PTP_Admin_Notices {

    public function __construct() {
        add_action( 'admin_notices', [ $this, 'version_update_notice' ] );
    }

    /**
     * Show notice after version update.
     */
    public function version_update_notice(): void {
        $current_version = get_option( 'ptp_version', '0.0.0' );
        $notice_dismissed = get_option( 'ptp_notice_dismissed_' . PTP_VERSION, 0 );
        
        if ( version_compare( $current_version, PTP_VERSION, '<' ) && ! $notice_dismissed ) {
            ?>
            <div class="notice notice-success is-dismissible" data-notice="ptp-version-update">
                <h3>🎉 <?php esc_html_e( 'Plugin Tracking Personalise mis à jour !', 'plugin-tracking-personalise' ); ?></h3>
                <p>
                    <?php printf(
                        esc_html__( 'Version %s installée avec succès. Vos données ont été conservées.', 'plugin-tracking-personalise' ),
                        '<strong>' . esc_html( PTP_VERSION ) . '</strong>'
                    ); ?>
                </p>
                <p>
                    <strong><?php esc_html_e( 'Nouveautés :', 'plugin-tracking-personalise' ); ?></strong>
                </p>
                <ul style="list-style:disc;margin-left:20px;">
                    <li>✅ <?php esc_html_e( 'Système de mise à jour WordPress natif', 'plugin-tracking-personalise' ); ?></li>
                    <li>✅ <?php esc_html_e( 'Protection des données à la désinstallation (option configurable)', 'plugin-tracking-personalise' ); ?></li>
                    <li>✅ <?php esc_html_e( 'Backup automatique avant suppression', 'plugin-tracking-personalise' ); ?></li>
                </ul>
                <p>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=ptp-settings' ) ); ?>" class="button button-primary">
                        <?php esc_html_e( 'Voir les réglages', 'plugin-tracking-personalise' ); ?>
                    </a>
                </p>
            </div>
            <script>
            jQuery(document).on('click', '[data-notice="ptp-version-update"] .notice-dismiss', function() {
                jQuery.post(ajaxurl, {
                    action: 'ptp_dismiss_notice',
                    version: '<?php echo esc_js( PTP_VERSION ); ?>',
                    nonce: '<?php echo esc_js( wp_create_nonce( 'ptp_dismiss_notice' ) ); ?>'
                });
            });
            </script>
            <?php
        }
    }
}

// AJAX handler for dismissing notice
add_action( 'wp_ajax_ptp_dismiss_notice', function() {
    check_ajax_referer( 'ptp_dismiss_notice', 'nonce' );
    $version = sanitize_text_field( $_POST['version'] ?? '' );
    if ( $version ) {
        update_option( 'ptp_notice_dismissed_' . $version, 1 );
    }
    wp_send_json_success();
} );
