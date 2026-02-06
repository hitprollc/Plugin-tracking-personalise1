<?php
defined( 'ABSPATH' ) || exit;

/**
 * Plugin activation tasks.
 */
class PTP_Activator {

    public static function activate(): void {
        $installed_version = get_option( 'ptp_version', '0.0.0' );
        
        // First installation
        if ( version_compare( $installed_version, '1.0.0', '<' ) ) {
            self::initial_install();
        }
        
        // Migrations for future versions
        if ( version_compare( $installed_version, '1.0.1', '<' ) ) {
            self::migrate_to_101();
        }
        
        // Update version
        update_option( 'ptp_version', PTP_VERSION );
        
        // Flush rewrite rules on next load
        update_option( 'ptp_flush_rewrite', 1 );
    }

    /**
     * Initial installation (v1.0.0).
     */
    private static function initial_install(): void {
        // Create DB table
        PTP_Database::create_tables();

        // Register CPT to flush rewrite rules
        PTP_Post_Types::register();

        // Create pages
        self::create_pages();

        // Set default options
        if ( false === get_option( 'ptp_require_email' ) ) {
            update_option( 'ptp_require_email', 0 );
        }
        if ( false === get_option( 'ptp_carriers' ) ) {
            update_option( 'ptp_carriers', PTP_Helper::default_carriers() );
        }
        if ( false === get_option( 'ptp_status_labels' ) ) {
            update_option( 'ptp_status_labels', PTP_Helper::default_statuses() );
        }
        if ( false === get_option( 'ptp_keep_data' ) ) {
            update_option( 'ptp_keep_data', 1 ); // Default: keep data on uninstall
        }
        if ( false === get_option( 'ptp_auto_generate' ) ) {
            update_option( 'ptp_auto_generate', 1 );
        }
        if ( false === get_option( 'ptp_tracking_prefix' ) ) {
            update_option( 'ptp_tracking_prefix', 'TRACK' );
        }
    }

    /**
     * Migration to v1.0.1.
     */
    private static function migrate_to_101(): void {
        // Add new option if not exists
        if ( false === get_option( 'ptp_keep_data' ) ) {
            update_option( 'ptp_keep_data', 1 );
        }
        
        // Log migration
        error_log( 'PTP: Migrated to version 1.0.1' );
    }

    /**
     * Create default pages.
     */
    private static function create_pages(): void {
        // Page "Suivi"
        $suivi_id = (int) get_option( 'ptp_page_suivi', 0 );
        if ( ! $suivi_id || ! get_post( $suivi_id ) ) {
            $suivi_id = wp_insert_post( [
                'post_title'   => __( 'Suivi', 'plugin-tracking-personalise' ),
                'post_name'    => 'suivi',
                'post_content' => '[ptp_tracking_lookup]',
                'post_status'  => 'publish',
                'post_type'    => 'page',
            ] );
            if ( $suivi_id && ! is_wp_error( $suivi_id ) ) {
                update_option( 'ptp_page_suivi', $suivi_id );
            }
        }

        // Page "Détail suivi"
        $detail_id = (int) get_option( 'ptp_page_suivi_detail', 0 );
        if ( ! $detail_id || ! get_post( $detail_id ) ) {
            $detail_id = wp_insert_post( [
                'post_title'   => __( 'Détail suivi', 'plugin-tracking-personalise' ),
                'post_name'    => 'suivi-detail',
                'post_content' => '[ptp_tracking_result]',
                'post_status'  => 'publish',
                'post_type'    => 'page',
            ] );
            if ( $detail_id && ! is_wp_error( $detail_id ) ) {
                update_option( 'ptp_page_suivi_detail', $detail_id );
            }
        }
    }
}
