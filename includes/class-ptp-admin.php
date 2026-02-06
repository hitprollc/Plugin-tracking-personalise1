<?php
/**
 * Admin menu and assets management.
 */

defined( 'ABSPATH' ) || exit;

class PTP_Admin {

    public function __construct() {
        add_action( 'admin_menu', [ $this, 'add_menu' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
    }

    /**
     * Add admin menu.
     */
    public function add_menu(): void {
        add_menu_page(
            __( 'Plugin Tracking Personalise', 'plugin-tracking-personalise' ),
            __( 'Plugin Tracking Personalise', 'plugin-tracking-personalise' ),
            'manage_options',
            'ptp-shipments',
            [ $this, 'render_shipments_page' ],
            'dashicons-location',
            56
        );

        add_submenu_page(
            'ptp-shipments',
            __( 'Envois', 'plugin-tracking-personalise' ),
            __( 'Tous les envois', 'plugin-tracking-personalise' ),
            'manage_options',
            'ptp-shipments'
        );

        add_submenu_page(
            'ptp-shipments',
            __( 'Ajouter un envoi', 'plugin-tracking-personalise' ),
            __( 'Ajouter', 'plugin-tracking-personalise' ),
            'manage_options',
            'post-new.php?post_type=ptp_shipment'
        );

        add_submenu_page(
            'ptp-shipments',
            __( 'Réglages', 'plugin-tracking-personalise' ),
            __( 'Réglages', 'plugin-tracking-personalise' ),
            'manage_options',
            'ptp-settings',
            [ 'PTP_Admin_Settings', 'render' ]
        );
    }

    /**
     * Render shipments list page.
     */
    public function render_shipments_page(): void {
        // Load required WordPress files
        if ( ! class_exists( 'WP_List_Table' ) ) {
            require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
        }
        if ( ! class_exists( 'WP_Posts_List_Table' ) ) {
            require_once ABSPATH . 'wp-admin/includes/class-wp-posts-list-table.php';
        }
        
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php esc_html_e( 'Envois', 'plugin-tracking-personalise' ); ?></h1>
            <a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=ptp_shipment' ) ); ?>" class="page-title-action">
                <?php esc_html_e( 'Ajouter un envoi', 'plugin-tracking-personalise' ); ?>
            </a>
            <hr class="wp-header-end">

            <?php
            $list_table = new WP_Posts_List_Table( [ 'screen' => 'ptp_shipment' ] );
            $list_table->prepare_items();
            $list_table->display();
            ?>
        </div>
        <?php
    }

    /**
     * Enqueue admin scripts and styles.
     */
    public function enqueue_scripts( string $hook ): void {
        // Only load on our plugin pages and post edit pages
        if ( strpos( $hook, 'ptp-' ) !== false || ( $hook === 'post.php' || $hook === 'post-new.php' ) && get_post_type() === 'ptp_shipment' ) {
            wp_enqueue_style(
                'ptp-admin',
                PTP_PLUGIN_URL . 'assets/css/ptp-admin.css',
                [],
                PTP_VERSION
            );

            wp_enqueue_script(
                'ptp-admin',
                PTP_PLUGIN_URL . 'assets/js/ptp-admin.js',
                [ 'jquery' ],
                PTP_VERSION,
                true
            );

            wp_localize_script( 'ptp-admin', 'ptpAdmin', [
                'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                'nonce'   => wp_create_nonce( 'ptp_admin_nonce' ),
            ] );
        }
    }
}
