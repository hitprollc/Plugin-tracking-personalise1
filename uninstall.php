<?php
/**
 * Fired when the plugin is uninstalled.
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

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

// Delete options
$options = [
    'ptp_db_version',
    'ptp_page_suivi',
    'ptp_page_suivi_detail',
    'ptp_require_email',
    'ptp_carriers',
    'ptp_status_labels',
    'ptp_flush_rewrite',
];
foreach ( $options as $option ) {
    delete_option( $option );
}
