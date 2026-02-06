<?php
/**
 * Deactivation handler.
 */

defined( 'ABSPATH' ) || exit;

class PTP_Deactivator {

    /**
     * Run on plugin deactivation.
     */
    public static function deactivate(): void {
        // Flush rewrite rules
        flush_rewrite_rules();
    }
}
