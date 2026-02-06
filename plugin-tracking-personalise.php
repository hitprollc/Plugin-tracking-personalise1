<?php
/**
 * Plugin Name: Plugin Tracking Personalise
 * Plugin URI:  https://example.com/plugin-tracking-personalise
 * Description: Système de suivi d'expédition (tracking colis) personnalisé, similaire à UPS/USPS. Compatible WooCommerce.
 * Version:     1.0.0
 * Author:      HitPro LLC
 * Author URI:  https://example.com
 * License:     GPL-2.0+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: plugin-tracking-personalise
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 8.1
 * WC requires at least: 7.0
 * WC tested up to: 9.0
 */

defined( 'ABSPATH' ) || exit;

define( 'PTP_VERSION', '1.0.0' );
define( 'PTP_PLUGIN_FILE', __FILE__ );
define( 'PTP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'PTP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'PTP_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// Autoload includes
$ptp_includes = [
    'includes/class-ptp-compatibility.php',
    'includes/class-ptp-helper.php',
    'includes/class-ptp-database.php',
    'includes/class-ptp-activator.php',
    'includes/class-ptp-deactivator.php',
    'includes/class-ptp-post-types.php',
    'includes/class-ptp-admin.php',
    'includes/class-ptp-admin-shipment.php',
    'includes/class-ptp-admin-settings.php',
    'includes/class-ptp-shortcodes.php',
    'includes/class-ptp-woocommerce.php',
];

foreach ( $ptp_includes as $file ) {
    $path = PTP_PLUGIN_DIR . $file;
    if ( file_exists( $path ) ) {
        require_once $path;
    }
}

// Activation / Deactivation
register_activation_hook( __FILE__, [ 'PTP_Activator', 'activate' ] );
register_deactivation_hook( __FILE__, [ 'PTP_Deactivator', 'deactivate' ] );

/**
 * Main plugin class (singleton).
 */
final class Plugin_Tracking_Personalise {

    private static ?self $instance = null;

    public static function instance(): self {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'init', [ $this, 'load_textdomain' ] );
        add_action( 'init', [ $this, 'init' ] );
    }

    public function load_textdomain(): void {
        load_plugin_textdomain(
            'plugin-tracking-personalise',
            false,
            dirname( PTP_PLUGIN_BASENAME ) . '/languages'
        );
    }

    public function init(): void {
        // Register CPT
        PTP_Post_Types::register();

        // Admin
        if ( is_admin() ) {
            new PTP_Admin();
            new PTP_Admin_Shipment();
            new PTP_Admin_Settings();
        }

        // Shortcodes
        PTP_Shortcodes::register();

        // WooCommerce integration
        if ( class_exists( 'WooCommerce' ) ) {
            new PTP_WooCommerce();
        }
    }
}

Plugin_Tracking_Personalise::instance();
