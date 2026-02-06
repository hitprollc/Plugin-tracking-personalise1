<?php
/**
 * Admin settings page.
 */

defined( 'ABSPATH' ) || exit;

class PTP_Admin_Settings {

    public function __construct() {
        add_action( 'admin_init', [ $this, 'register_settings' ] );
    }

    /**
     * Register plugin settings.
     */
    public function register_settings(): void {
        register_setting( 'ptp_settings', 'ptp_page_suivi' );
        register_setting( 'ptp_settings', 'ptp_page_suivi_detail' );
        register_setting( 'ptp_settings', 'ptp_require_email' );
        register_setting( 'ptp_settings', 'ptp_carriers' );
        register_setting( 'ptp_settings', 'ptp_status_labels' );
    }

    /**
     * Render settings page.
     */
    public static function render(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        // Handle form submission
        if ( isset( $_POST['ptp_settings_submit'] ) ) {
            check_admin_referer( 'ptp_settings_action', 'ptp_settings_nonce' );

            update_option( 'ptp_page_suivi', absint( $_POST['ptp_page_suivi'] ?? 0 ) );
            update_option( 'ptp_page_suivi_detail', absint( $_POST['ptp_page_suivi_detail'] ?? 0 ) );
            update_option( 'ptp_require_email', isset( $_POST['ptp_require_email'] ) ? '1' : '0' );

            echo '<div class="notice notice-success"><p>' . esc_html__( 'Réglages enregistrés', 'plugin-tracking-personalise' ) . '</p></div>';
        }

        $page_suivi        = get_option( 'ptp_page_suivi', 0 );
        $page_suivi_detail = get_option( 'ptp_page_suivi_detail', 0 );
        $require_email     = get_option( 'ptp_require_email', '0' );

        $pages = get_pages();
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Réglages du Tracking', 'plugin-tracking-personalise' ); ?></h1>

            <form method="post" action="">
                <?php wp_nonce_field( 'ptp_settings_action', 'ptp_settings_nonce' ); ?>

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="ptp_page_suivi"><?php esc_html_e( 'Page de recherche de suivi', 'plugin-tracking-personalise' ); ?></label>
                        </th>
                        <td>
                            <select id="ptp_page_suivi" name="ptp_page_suivi">
                                <option value="0"><?php esc_html_e( 'Sélectionner une page...', 'plugin-tracking-personalise' ); ?></option>
                                <?php foreach ( $pages as $page ) : ?>
                                    <option value="<?php echo esc_attr( $page->ID ); ?>" <?php selected( $page_suivi, $page->ID ); ?>>
                                        <?php echo esc_html( $page->post_title ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description">
                                <?php esc_html_e( 'Page contenant le shortcode [ptp_tracking_lookup]', 'plugin-tracking-personalise' ); ?>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="ptp_page_suivi_detail"><?php esc_html_e( 'Page de résultat de suivi', 'plugin-tracking-personalise' ); ?></label>
                        </th>
                        <td>
                            <select id="ptp_page_suivi_detail" name="ptp_page_suivi_detail">
                                <option value="0"><?php esc_html_e( 'Sélectionner une page...', 'plugin-tracking-personalise' ); ?></option>
                                <?php foreach ( $pages as $page ) : ?>
                                    <option value="<?php echo esc_attr( $page->ID ); ?>" <?php selected( $page_suivi_detail, $page->ID ); ?>>
                                        <?php echo esc_html( $page->post_title ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description">
                                <?php esc_html_e( 'Page contenant le shortcode [ptp_tracking_result]', 'plugin-tracking-personalise' ); ?>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <?php esc_html_e( 'Vérification par email', 'plugin-tracking-personalise' ); ?>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" id="ptp_require_email" name="ptp_require_email" value="1" <?php checked( $require_email, '1' ); ?>>
                                <?php esc_html_e( 'Exiger l\'email du client pour afficher le suivi', 'plugin-tracking-personalise' ); ?>
                            </label>
                        </td>
                    </tr>
                </table>

                <h2><?php esc_html_e( 'Transporteurs personnalisés', 'plugin-tracking-personalise' ); ?></h2>
                <p class="description">
                    <?php esc_html_e( 'Les transporteurs par défaut (UPS, FedEx, USPS, DHL) sont toujours disponibles.', 'plugin-tracking-personalise' ); ?>
                </p>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label><?php esc_html_e( 'Transporteurs supplémentaires', 'plugin-tracking-personalise' ); ?></label>
                        </th>
                        <td>
                            <p><em><?php esc_html_e( 'Fonctionnalité à venir', 'plugin-tracking-personalise' ); ?></em></p>
                        </td>
                    </tr>
                </table>

                <h2><?php esc_html_e( 'Statuts personnalisés', 'plugin-tracking-personalise' ); ?></h2>
                <p class="description">
                    <?php esc_html_e( 'Les statuts par défaut sont toujours disponibles.', 'plugin-tracking-personalise' ); ?>
                </p>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label><?php esc_html_e( 'Statuts supplémentaires', 'plugin-tracking-personalise' ); ?></label>
                        </th>
                        <td>
                            <p><em><?php esc_html_e( 'Fonctionnalité à venir', 'plugin-tracking-personalise' ); ?></em></p>
                        </td>
                    </tr>
                </table>

                <?php submit_button( __( 'Enregistrer les réglages', 'plugin-tracking-personalise' ), 'primary', 'ptp_settings_submit' ); ?>
            </form>
        </div>
        <?php
    }
}
