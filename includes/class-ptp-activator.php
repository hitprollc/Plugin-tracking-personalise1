<?php
/**
 * Activation handler - creates database tables and default pages.
 */

defined( 'ABSPATH' ) || exit;

class PTP_Activator {

    /**
     * Run on plugin activation.
     */
    public static function activate(): void {
        // Create database tables
        PTP_Database::create_tables();

        // Create default pages
        self::create_default_pages();

        // Flush rewrite rules
        PTP_Post_Types::register();
        flush_rewrite_rules();
        update_option( 'ptp_flush_rewrite', '1' );
    }

    /**
     * Create default tracking pages.
     */
    private static function create_default_pages(): void {
        // Tracking lookup page
        $lookup_page_id = get_option( 'ptp_page_suivi' );
        if ( ! $lookup_page_id || ! get_post( $lookup_page_id ) ) {
            $page_id = wp_insert_post( [
                'post_title'   => __( 'Suivi de colis', 'plugin-tracking-personalise' ),
                'post_content' => '[ptp_tracking_lookup]',
                'post_status'  => 'publish',
                'post_type'    => 'page',
                'post_author'  => get_current_user_id(),
            ] );

            if ( $page_id && ! is_wp_error( $page_id ) ) {
                update_option( 'ptp_page_suivi', $page_id );
            }
        }

        // Tracking result page
        $result_page_id = get_option( 'ptp_page_suivi_detail' );
        if ( ! $result_page_id || ! get_post( $result_page_id ) ) {
            $page_id = wp_insert_post( [
                'post_title'   => __( 'Résultat du suivi', 'plugin-tracking-personalise' ),
                'post_content' => '[ptp_tracking_result]',
                'post_status'  => 'publish',
                'post_type'    => 'page',
                'post_author'  => get_current_user_id(),
            ] );

            if ( $page_id && ! is_wp_error( $page_id ) ) {
                update_option( 'ptp_page_suivi_detail', $page_id );
            }
        }

        // Set default options
        if ( ! get_option( 'ptp_require_email' ) ) {
            update_option( 'ptp_require_email', '0' );
        }
    }
}
