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
        register_setting( 'ptp_settings', 'ptp_auto_generate' );
        register_setting( 'ptp_settings', 'ptp_tracking_prefix' );
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

            // Auto generate settings
            $auto_generate = isset( $_POST['ptp_auto_generate'] ) ? 1 : 0;
            update_option( 'ptp_auto_generate', $auto_generate );

            $tracking_prefix = strtoupper( sanitize_text_field( $_POST['ptp_tracking_prefix'] ?? 'TRACK' ) );
            if ( PTP_Tracking_Generator::validate_prefix( $tracking_prefix ) ) {
                update_option( 'ptp_tracking_prefix', $tracking_prefix );
            } else {
                add_settings_error( 'ptp_settings', 'invalid_prefix', __( 'Préfixe invalide. Utilisez 2-10 caractères alphanumériques.', 'plugin-tracking-personalise' ), 'error' );
            }

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

                <h2><?php esc_html_e( 'Génération automatique de tracking', 'plugin-tracking-personalise' ); ?></h2>
                <table class="form-table">
                    <tr>
                        <th>
                            <label for="ptp_auto_generate"><?php esc_html_e( 'Activer la génération automatique', 'plugin-tracking-personalise' ); ?></label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" id="ptp_auto_generate" name="ptp_auto_generate" value="1" <?php checked( get_option( 'ptp_auto_generate', 1 ), 1 ); ?>>
                                <?php esc_html_e( 'Permettre la génération de numéros de tracking', 'plugin-tracking-personalise' ); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th>
                            <label for="ptp_tracking_prefix"><?php esc_html_e( 'Préfixe de tracking', 'plugin-tracking-personalise' ); ?></label>
                        </th>
                        <td>
                            <input type="text" id="ptp_tracking_prefix" name="ptp_tracking_prefix" 
                                   value="<?php echo esc_attr( get_option( 'ptp_tracking_prefix', 'TRACK' ) ); ?>" 
                                   class="regular-text" 
                                   maxlength="10" 
                                   pattern="[A-Z0-9]{2,10}"
                                   style="text-transform:uppercase;">
                            <p class="description">
                                <?php esc_html_e( '2-10 caractères alphanumériques majuscules. Exemple : SHIP, TRACK, ORDER', 'plugin-tracking-personalise' ); ?>
                            </p>
                            <p class="description">
                                <strong><?php esc_html_e( 'Aperçu :', 'plugin-tracking-personalise' ); ?></strong>
                                <code id="ptp-tracking-preview"><?php echo esc_html( PTP_Tracking_Generator::get_preview() ); ?></code>
                            </p>
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
