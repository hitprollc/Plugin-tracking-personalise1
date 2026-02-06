<?php
/**
 * Automatic tracking number generator.
 */

defined( 'ABSPATH' ) || exit;

class PTP_Tracking_Generator {

	/**
	 * Generate a unique tracking number.
	 * Format: PREFIX-YYYYMMDD-XXXXX
	 * Example: SHIP-20260206-00001
	 */
	public static function generate(): string {
		$prefix = get_option( 'ptp_tracking_prefix', 'TRACK' );
		$prefix = strtoupper( sanitize_text_field( $prefix ) );
		
		$date = date( 'Ymd' );
		
		// Get counter for today
		$counter_key = 'ptp_tracking_counter_' . $date;
		$counter = (int) get_option( $counter_key, 0 );
		$counter++;
		
		// Update counter
		update_option( $counter_key, $counter );
		
		// Format: PREFIX-YYYYMMDD-00001
		$number = sprintf( '%s-%s-%05d', $prefix, $date, $counter );
		
		// Verify uniqueness
		$existing = PTP_Helper::get_shipment_by_tracking( $number );
		if ( $existing ) {
			// If collision (rare), try again with incremented counter
			return self::generate();
		}
		
		return $number;
	}

	/**
	 * Get preview of tracking number format.
	 */
	public static function get_preview(): string {
		$prefix = get_option( 'ptp_tracking_prefix', 'TRACK' );
		$prefix = strtoupper( sanitize_text_field( $prefix ) );
		$date = date( 'Ymd' );
		
		return sprintf( '%s-%s-00001', $prefix, $date );
	}

	/**
	 * Validate tracking prefix.
	 */
	public static function validate_prefix( string $prefix ): bool {
		// Only alphanumeric, 2-10 characters
		return preg_match( '/^[A-Z0-9]{2,10}$/', strtoupper( $prefix ) );
	}
}
