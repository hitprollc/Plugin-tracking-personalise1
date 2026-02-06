<?php
/**
 * Fired when the plugin is uninstalled.
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

// Check if user wants to keep data (default: YES)
$keep_data = get_option( 'ptp_keep_data', 1 );

if ( $keep_data ) {
    // Keep all shipment data, only remove technical options
    delete_option( 'ptp_flush_rewrite' );
    
    // Log that data was preserved
    error_log( 'PTP: Plugin uninstalled but data preserved as per user settings.' );
    
    return; // Exit without deleting data
}

// User explicitly chose to delete everything
global $wpdb;

// Delete custom table
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}ptp_tracking_events" );

// Delete CPT posts
$posts = get_posts( [
    'post_type'      => 'ptp_shipment',
    'posts_per_page' => -1,
    'post_status'    => 'any',
    'fields'         => 'ids',
] );
foreach ( $posts as $post_id ) {
    wp_delete_post( $post_id, true );
}

// Delete all plugin options
$options = [
    'ptp_version',
    'ptp_db_version',
    'ptp_page_suivi',
    'ptp_page_suivi_detail',
    'ptp_require_email',
    'ptp_carriers',
    'ptp_status_labels',
    'ptp_flush_rewrite',
    'ptp_keep_data',
    'ptp_auto_generate',
    'ptp_tracking_prefix',
];
foreach ( $options as $option ) {
    delete_option( $option );
}

// Delete daily counter options (ptp_tracking_counter_YYYYMMDD)
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE 'ptp_tracking_counter_%'" );

error_log( 'PTP: Plugin uninstalled and all data deleted as per user settings.' );
