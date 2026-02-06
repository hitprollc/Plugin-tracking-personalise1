<?php
/**
 * Helper utility class with static methods.
 */

defined( 'ABSPATH' ) || exit;

class PTP_Helper {

    /**
     * Get available tracking statuses.
     *
     * @return array
     */
    public static function get_statuses(): array {
        $defaults = [
            'pending'     => __( 'En attente', 'plugin-tracking-personalise' ),
            'in_transit'  => __( 'En transit', 'plugin-tracking-personalise' ),
            'out_for_delivery' => __( 'En livraison', 'plugin-tracking-personalise' ),
            'delivered'   => __( 'Livré', 'plugin-tracking-personalise' ),
            'exception'   => __( 'Exception', 'plugin-tracking-personalise' ),
            'returned'    => __( 'Retourné', 'plugin-tracking-personalise' ),
        ];

        $custom = get_option( 'ptp_status_labels', [] );
        return array_merge( $defaults, $custom );
    }

    /**
     * Get available carriers.
     *
     * @return array
     */
    public static function get_carriers(): array {
        $defaults = [
            'ups'     => 'UPS',
            'fedex'   => 'FedEx',
            'usps'    => 'USPS',
            'dhl'     => 'DHL',
            'other'   => __( 'Autre', 'plugin-tracking-personalise' ),
        ];

        $custom = get_option( 'ptp_carriers', [] );
        return array_merge( $defaults, $custom );
    }

    /**
     * Sanitize tracking number.
     *
     * @param string $tracking_number
     * @return string
     */
    public static function sanitize_tracking_number( string $tracking_number ): string {
        return sanitize_text_field( strtoupper( trim( $tracking_number ) ) );
    }

    /**
     * Format date for display.
     *
     * @param string $date
     * @return string
     */
    public static function format_date( string $date ): string {
        return date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $date ) );
    }

    /**
     * Get shipment by tracking number.
     *
     * @param string $tracking_number
     * @return \WP_Post|null
     */
    public static function get_shipment_by_tracking( string $tracking_number ): ?\WP_Post {
        $args = [
            'post_type'      => 'ptp_shipment',
            'posts_per_page' => 1,
            'post_status'    => 'publish',
            'meta_query'     => [
                [
                    'key'     => '_ptp_tracking_number',
                    'value'   => $tracking_number,
                    'compare' => '=',
                ],
            ],
        ];

        $posts = get_posts( $args );
        return $posts[0] ?? null;
    }

    /**
     * Check if email matches shipment.
     *
     * @param int    $shipment_id
     * @param string $email
     * @return bool
     */
    public static function verify_shipment_email( int $shipment_id, string $email ): bool {
        $shipment_email = get_post_meta( $shipment_id, '_ptp_customer_email', true );
        return ! empty( $shipment_email ) && strtolower( $shipment_email ) === strtolower( $email );
    }

    /**
     * Get progress percentage for a status.
     *
     * @param string $status
     * @return int
     */
    public static function get_status_progress( string $status ): int {
        $progress_map = [
            'pending'          => 10,
            'in_transit'       => 50,
            'out_for_delivery' => 80,
            'delivered'        => 100,
            'exception'        => 50,
            'returned'         => 100,
        ];

        return $progress_map[ $status ] ?? 0;
    }

    /**
     * Get CSS class for status.
     *
     * @param string $status
     * @return string
     */
    public static function get_status_class( string $status ): string {
        $class_map = [
            'pending'          => 'ptp-status-pending',
            'in_transit'       => 'ptp-status-in-transit',
            'out_for_delivery' => 'ptp-status-out-for-delivery',
            'delivered'        => 'ptp-status-delivered',
            'exception'        => 'ptp-status-exception',
            'returned'         => 'ptp-status-returned',
        ];

        return $class_map[ $status ] ?? 'ptp-status-default';
    }

    /**
     * Check if current user can manage plugin.
     *
     * @return bool
     */
    public static function current_user_can_manage(): bool {
        return current_user_can( 'edit_posts' );
    }

    /**
     * Get default carriers for initial setup.
     *
     * @return array
     */
    public static function default_carriers(): array {
        return [
            'ups'     => 'UPS',
            'fedex'   => 'FedEx',
            'usps'    => 'USPS',
            'dhl'     => 'DHL',
            'other'   => __( 'Autre', 'plugin-tracking-personalise' ),
        ];
    }

    /**
     * Get default statuses for initial setup.
     *
     * @return array
     */
    public static function default_statuses(): array {
        return [
            'pending'          => __( 'En attente', 'plugin-tracking-personalise' ),
            'in_transit'       => __( 'En transit', 'plugin-tracking-personalise' ),
            'out_for_delivery' => __( 'En livraison', 'plugin-tracking-personalise' ),
            'delivered'        => __( 'Livré', 'plugin-tracking-personalise' ),
            'exception'        => __( 'Exception', 'plugin-tracking-personalise' ),
            'returned'         => __( 'Retourné', 'plugin-tracking-personalise' ),
        ];
    }

    /**
     * Get badge color for status.
     *
     * @param string $status
     * @return string
     */
    public static function get_status_color( string $status ): string {
        $colors = [
            'label_created'    => '#6c757d', // Gray
            'picked_up'        => '#0d6efd', // Blue
            'in_transit'       => '#0dcaf0', // Cyan
            'out_for_delivery' => '#fd7e14', // Orange
            'delivered'        => '#28a745', // Green
            'exception'        => '#dc3545', // Red
            'returned'         => '#6f42c1', // Purple
            'other'            => '#6c757d', // Gray
        ];
        
        return $colors[ $status ] ?? '#6c757d';
    }
}
