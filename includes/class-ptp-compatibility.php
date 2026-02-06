<?php
defined( 'ABSPATH' ) || exit;

/**
 * WooCommerce compatibility declarations.
 */
class PTP_Compatibility {

    public function __construct() {
        add_action( 'before_woocommerce_init', [ $this, 'declare_compatibility' ] );
    }

    /**
     * Declare compatibility with WooCommerce features.
     */
    public function declare_compatibility(): void {
        if ( ! class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
            return;
        }

        // Declare HPOS (High-Performance Order Storage) compatibility
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
            'custom_order_tables',
            PTP_PLUGIN_FILE,
            true
        );

        // Declare cart/checkout blocks compatibility
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
            'cart_checkout_blocks',
            PTP_PLUGIN_FILE,
            true
        );
    }
}

new PTP_Compatibility();
