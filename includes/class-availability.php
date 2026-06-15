<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class Lab_Availability
 * Manages business availability (open hours per day).
 */
class Lab_Availability {

    public static $days = array(
        'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'
    );

    public static function init() {
        add_action( 'wp_ajax_lab_save_availability', array( __CLASS__, 'ajax_save_availability' ) );
        add_action( 'wp_ajax_lab_get_closed_days',   array( __CLASS__, 'ajax_get_closed_days' ) );
    }

    /**
     * Get business hours for all 7 days.
     *
     * @param int $business_id
     * @return array [ 'monday' => ['open'=>'09:00','close'=>'18:00'], ... ]
     */
    public static function get_business_hours( $business_id ) {
        $hours = array();
        foreach ( self::$days as $day ) {
            $open  = get_post_meta( $business_id, '_lab_avail_' . $day . '_open', true );
            $close = get_post_meta( $business_id, '_lab_avail_' . $day . '_close', true );
            $hours[ $day ] = array(
                'open'  => $open ?: '',
                'close' => $close ?: '',
            );
        }
        return $hours;
    }

    /**
     * Check if a business is open on a given date and time.
     *
     * @param int    $business_id
     * @param string $date (Y-m-d)
     * @param string $time (H:i)
     * @return bool
     */
    public static function is_business_open( $business_id, $date, $time ) {
        $day_name = strtolower( date( 'l', strtotime( $date ) ) );
        $open     = get_post_meta( $business_id, '_lab_avail_' . $day_name . '_open', true );
        $close    = get_post_meta( $business_id, '_lab_avail_' . $day_name . '_close', true );

        if ( empty( $open ) || empty( $close ) ) {
            return false; /* Closed that day */
        }

        return ( $time >= $open && $time < $close );
    }

    /**
     * Check if a slot is available (no confirmed booking).
     *
     * @param int    $business_id
     * @param string $date (Y-m-d)
     * @param string $time (H:i)
     * @return bool
     */
    public static function check_slot_available( $business_id, $date, $time ) {
        global $wpdb;
        $table = $wpdb->prefix . 'lab_bookings';
        $count = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE business_id = %d AND booking_date = %s AND booking_time = %s AND status = 'confirmed'",
            $business_id,
            $date,
            $time . ':00'
        ) );
        return ( intval( $count ) === 0 );
    }

    /**
     * Get available time slots for a business on a given date.
     *
     * @param int    $business_id
     * @param string $date (Y-m-d)
     * @return array ['09:00','09:30',...]
     */
    public static function get_available_slots( $business_id, $date ) {
        $day_name = strtolower( date( 'l', strtotime( $date ) ) );
        $open     = get_post_meta( $business_id, '_lab_avail_' . $day_name . '_open', true );
        $close    = get_post_meta( $business_id, '_lab_avail_' . $day_name . '_close', true );

        if ( empty( $open ) || empty( $close ) ) {
            return array(); /* Closed */
        }

        $slots       = array();
        $current     = strtotime( $open );
        $end         = strtotime( $close );
        $interval    = 30 * 60; /* 30 minutes */

        while ( $current < $end ) {
            $time_str = date( 'H:i', $current );
            if ( self::check_slot_available( $business_id, $date, $time_str ) ) {
                $slots[] = $time_str;
            }
            $current += $interval;
        }

        return $slots;
    }

    /**
     * Get closed days of the week (0=Sunday, 6=Saturday) for date picker.
     *
     * @param int $business_id
     * @return array [ 0, 6 ]  (Sunday, Saturday closed)
     */
    public static function get_closed_day_numbers( $business_id ) {
        $map = array(
            'sunday'    => 0,
            'monday'    => 1,
            'tuesday'   => 2,
            'wednesday' => 3,
            'thursday'  => 4,
            'friday'    => 5,
            'saturday'  => 6,
        );

        $closed = array();
        foreach ( self::$days as $day ) {
            $open = get_post_meta( $business_id, '_lab_avail_' . $day . '_open', true );
            if ( empty( $open ) ) {
                $closed[] = $map[ $day ];
            }
        }
        return $closed;
    }

    /**
     * AJAX: Save availability (business owner).
     */
    public static function ajax_save_availability() {
        check_ajax_referer( 'lab_nonce', 'nonce' );

        if ( ! is_user_logged_in() || ! current_user_can( 'lab_manage_own_availability' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'labeng' ) ) );
        }

        $business_id = absint( $_POST['business_id'] ?? 0 );
        $owner_id    = get_post_meta( $business_id, '_lab_owner_id', true );

        if ( absint( $owner_id ) !== get_current_user_id() ) {
            wp_send_json_error( array( 'message' => __( 'Not your business.', 'labeng' ) ) );
        }

        foreach ( self::$days as $day ) {
            $is_open = isset( $_POST[ $day . '_open_check' ] ) && $_POST[ $day . '_open_check' ] === '1';
            if ( $is_open ) {
                $open  = sanitize_text_field( $_POST[ $day . '_open' ] ?? '' );
                $close = sanitize_text_field( $_POST[ $day . '_close' ] ?? '' );
                update_post_meta( $business_id, '_lab_avail_' . $day . '_open', $open );
                update_post_meta( $business_id, '_lab_avail_' . $day . '_close', $close );
            } else {
                update_post_meta( $business_id, '_lab_avail_' . $day . '_open', '' );
                update_post_meta( $business_id, '_lab_avail_' . $day . '_close', '' );
            }
        }

        wp_send_json_success( array( 'message' => __( 'Availability saved.', 'labeng' ) ) );
    }

    /**
     * AJAX: Get closed day numbers for date picker.
     */
    public static function ajax_get_closed_days() {
        check_ajax_referer( 'lab_nonce', 'nonce' );

        $business_id = absint( $_POST['business_id'] ?? 0 );
        $closed = self::get_closed_day_numbers( $business_id );

        wp_send_json_success( array( 'closed_days' => $closed ) );
    }
}
