<?php
/**
 * Database management for tracking events table.
 */

defined( 'ABSPATH' ) || exit;

class PTP_Database {

    const DB_VERSION = '1.0';
    const TABLE_NAME = 'ptp_tracking_events';

    /**
     * Create or update database tables.
     */
    public static function create_tables(): void {
        global $wpdb;

        $table_name      = $wpdb->prefix . self::TABLE_NAME;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            shipment_id bigint(20) UNSIGNED NOT NULL,
            event_date datetime NOT NULL,
            status varchar(50) NOT NULL,
            location varchar(255) DEFAULT '',
            description text DEFAULT '',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY shipment_id (shipment_id),
            KEY event_date (event_date)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );

        update_option( 'ptp_db_version', self::DB_VERSION );
    }

    /**
     * Insert a tracking event.
     *
     * @param int    $shipment_id
     * @param string $event_date
     * @param string $status
     * @param string $location
     * @param string $description
     * @return int|false
     */
    public static function insert_event( int $shipment_id, string $event_date, string $status, string $location = '', string $description = '' ) {
        global $wpdb;

        $table_name = $wpdb->prefix . self::TABLE_NAME;

        return $wpdb->insert(
            $table_name,
            [
                'shipment_id' => $shipment_id,
                'event_date'  => $event_date,
                'status'      => $status,
                'location'    => $location,
                'description' => $description,
            ],
            [ '%d', '%s', '%s', '%s', '%s' ]
        );
    }

    /**
     * Get events for a shipment.
     *
     * @param int    $shipment_id
     * @param string $order_by
     * @return array
     */
    public static function get_events( int $shipment_id, string $order_by = 'DESC' ): array {
        global $wpdb;

        $table_name = $wpdb->prefix . self::TABLE_NAME;
        $order_by   = in_array( strtoupper( $order_by ), [ 'ASC', 'DESC' ], true ) ? $order_by : 'DESC';

        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table_name WHERE shipment_id = %d ORDER BY event_date $order_by, id $order_by",
                $shipment_id
            ),
            ARRAY_A
        );

        return $results ?: [];
    }

    /**
     * Update an event.
     *
     * @param int   $event_id
     * @param array $data
     * @return int|false
     */
    public static function update_event( int $event_id, array $data ) {
        global $wpdb;

        $table_name = $wpdb->prefix . self::TABLE_NAME;

        $allowed = [ 'event_date', 'status', 'location', 'description' ];
        $update  = [];
        $format  = [];

        foreach ( $allowed as $field ) {
            if ( isset( $data[ $field ] ) ) {
                $update[ $field ] = $data[ $field ];
                $format[]         = '%s';
            }
        }

        if ( empty( $update ) ) {
            return false;
        }

        return $wpdb->update(
            $table_name,
            $update,
            [ 'id' => $event_id ],
            $format,
            [ '%d' ]
        );
    }

    /**
     * Delete an event.
     *
     * @param int $event_id
     * @return int|false
     */
    public static function delete_event( int $event_id ) {
        global $wpdb;

        $table_name = $wpdb->prefix . self::TABLE_NAME;

        return $wpdb->delete(
            $table_name,
            [ 'id' => $event_id ],
            [ '%d' ]
        );
    }

    /**
     * Delete all events for a shipment.
     *
     * @param int $shipment_id
     * @return int|false
     */
    public static function delete_shipment_events( int $shipment_id ) {
        global $wpdb;

        $table_name = $wpdb->prefix . self::TABLE_NAME;

        return $wpdb->delete(
            $table_name,
            [ 'shipment_id' => $shipment_id ],
            [ '%d' ]
        );
    }

    /**
     * Get latest event for a shipment.
     *
     * @param int $shipment_id
     * @return array|null
     */
    public static function get_latest_event( int $shipment_id ): ?array {
        global $wpdb;

        $table_name = $wpdb->prefix . self::TABLE_NAME;

        $result = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table_name WHERE shipment_id = %d ORDER BY event_date DESC, id DESC LIMIT 1",
                $shipment_id
            ),
            ARRAY_A
        );

        return $result ?: null;
    }
}
