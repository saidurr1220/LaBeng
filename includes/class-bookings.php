<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class Lab_Bookings
 * Full booking lifecycle: create, get slots, update status.
 */
class Lab_Bookings {

    public static function init() {
        add_action( 'wp_ajax_lab_create_booking',           array( __CLASS__, 'ajax_create_booking' ) );
        add_action( 'wp_ajax_lab_get_slots',                array( __CLASS__, 'ajax_get_slots' ) );
        add_action( 'wp_ajax_nopriv_lab_get_slots',         array( __CLASS__, 'ajax_get_slots' ) );
        add_action( 'wp_ajax_lab_update_booking_status',    array( __CLASS__, 'ajax_update_booking_status' ) );
        add_action( 'wp_ajax_lab_cancel_booking',           array( __CLASS__, 'ajax_cancel_booking' ) );
        add_action( 'wp_ajax_lab_create_payment_intent',    array( __CLASS__, 'ajax_create_payment_intent' ) );
    }

    /**
     * Create a booking.
     *
     * @param array $data
     * @return int|WP_Error Booking ID on success.
     */
    public static function create_booking( $data ) {
        global $wpdb;

        $business_id  = absint( $data['business_id'] );
        $customer_id  = absint( $data['customer_id'] );
        $service_name = sanitize_text_field( $data['service_name'] );
        $service_price= floatval( $data['service_price'] );
        $booking_date = sanitize_text_field( $data['booking_date'] );
        $booking_time = sanitize_text_field( $data['booking_time'] );
        $notes        = sanitize_textarea_field( $data['notes'] ?? '' );

        /* 1. Verify customer is logged in */
        if ( ! $customer_id ) {
            return new WP_Error( 'not_logged_in', __( 'You must be logged in to book.', 'labeng' ) );
        }

        /* 2. Verify business exists and is approved */
        $business = get_post( $business_id );
        if ( ! $business || $business->post_type !== 'lab_business' ) {
            return new WP_Error( 'invalid_business', __( 'Business not found.', 'labeng' ) );
        }
        $biz_status = get_post_meta( $business_id, '_lab_status', true );
        if ( $biz_status !== 'approved' ) {
            return new WP_Error( 'business_not_approved', __( 'This business is not currently accepting bookings.', 'labeng' ) );
        }

        /* 3. Verify service exists in business services or custom booking steps */
        $booking_steps_json = get_post_meta( $business_id, '_lab_booking_steps', true );
        $booking_steps      = json_decode( $booking_steps_json, true );
        $found = false;

        if ( ! empty( $booking_steps ) && is_array( $booking_steps ) ) {
            foreach ( $booking_steps as $step ) {
                if ( ! empty( $step['options'] ) && is_array( $step['options'] ) ) {
                    foreach ( $step['options'] as $opt ) {
                        if ( $opt['name'] === $service_name ) {
                            $service_price = floatval( $data['service_price'] );
                            $found = true;
                            break 2;
                        }
                    }
                }
            }
        }

        if ( ! $found ) {
            $services = json_decode( get_post_meta( $business_id, '_lab_services', true ), true );
            if ( is_array( $services ) ) {
                foreach ( $services as $svc ) {
                    if ( $svc['name'] === $service_name ) {
                        $service_price = floatval( $svc['price'] );
                        $found = true;
                        break;
                    }
                }
            }
        }

        if ( ! $found ) {
            return new WP_Error( 'invalid_service', __( 'Service not found.', 'labeng' ) );
        }

        /* 4. Verify business is open on that day + time */
        if ( ! Lab_Availability::is_business_open( $business_id, $booking_date, $booking_time ) ) {
            return new WP_Error( 'closed', __( 'Business is closed at the selected date/time.', 'labeng' ) );
        }

        /* 5. Verify slot not already booked */
        if ( ! Lab_Availability::check_slot_available( $business_id, $booking_date, $booking_time ) ) {
            return new WP_Error( 'slot_taken', __( 'This time slot is already booked.', 'labeng' ) );
        }

        /* 6. Calculate commission */
        $commission = Lab_Commissions::calculate( $business_id, $service_price );

        /* 7. Verify Stripe PaymentIntent if provided */
        $payment_intent_id = sanitize_text_field( $data['payment_intent_id'] ?? '' );
        $payment_status    = 'unpaid';
        $payment_method    = '';

        if ( $payment_intent_id && $service_price > 0 ) {
            $verified = self::verify_stripe_payment( $payment_intent_id, $service_price );
            if ( is_wp_error( $verified ) ) {
                return $verified;
            }
            $payment_status = 'paid';
            $payment_method = 'stripe';
        } elseif ( $service_price <= 0 ) {
            $payment_status = 'free';
        }

        /* 8. All bookings start as pending — the business owner confirms.
         *    Payment status is tracked separately; a paid booking stays
         *    pending until the owner explicitly confirms it. */
        $status = 'pending';

        $table = $wpdb->prefix . 'lab_bookings';
        $inserted = $wpdb->insert(
            $table,
            array(
                'business_id'      => $business_id,
                'customer_id'      => $customer_id,
                'service_name'     => $service_name,
                'service_price'    => $service_price,
                'booking_date'     => $booking_date,
                'booking_time'     => $booking_time . ':00',
                'status'           => $status,
                'total_amount'     => $service_price,
                'commission_due'   => $commission,
                'payment_status'   => $payment_status,
                'payment_intent_id'=> $payment_intent_id ?: null,
                'payment_method'   => $payment_method ?: null,
                'notes'            => $notes,
            ),
            array( '%d', '%d', '%s', '%f', '%s', '%s', '%s', '%f', '%f', '%s', '%s', '%s', '%s' )
        );

        if ( ! $inserted ) {
            return new WP_Error( 'db_error', __( 'Failed to create booking.', 'labeng' ) );
        }

        $booking_id = $wpdb->insert_id;

        /* 9. Email customer */
        Lab_Email::lab_email_booking_confirmation( $booking_id );

        /* 10. Email business owner */
        Lab_Email::lab_email_booking_to_business( $booking_id );

        return $booking_id;
    }

    /**
     * Update booking status with permission checks.
     *
     * @param int    $booking_id
     * @param string $new_status
     * @param int    $actor_id
     * @return true|WP_Error
     */
    public static function update_status( $booking_id, $new_status, $actor_id ) {
        global $wpdb;
        $table   = $wpdb->prefix . 'lab_bookings';
        $booking = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $booking_id ) );

        if ( ! $booking ) {
            return new WP_Error( 'not_found', __( 'Booking not found.', 'labeng' ) );
        }

        $current = $booking->status;

        /* Define allowed transitions */
        $allowed = array(
            'pending'   => array( 'confirmed', 'cancelled' ),
            'confirmed' => array( 'completed', 'cancelled' ),
        );

        if ( ! isset( $allowed[ $current ] ) || ! in_array( $new_status, $allowed[ $current ], true ) ) {
            return new WP_Error( 'invalid_transition', __( 'Invalid status transition.', 'labeng' ) );
        }

        /* Check permissions */
        $user       = get_userdata( $actor_id );
        $is_admin   = $user && in_array( 'administrator', $user->roles, true );
        $is_owner   = absint( get_post_meta( $booking->business_id, '_lab_owner_id', true ) ) === $actor_id;
        $is_customer= absint( $booking->customer_id ) === $actor_id;

        if ( $new_status === 'confirmed' || $new_status === 'completed' ) {
            if ( ! $is_owner && ! $is_admin ) {
                return new WP_Error( 'permission', __( 'Only the business owner or admin can do this.', 'labeng' ) );
            }
        }

        if ( $new_status === 'cancelled' ) {
            if ( $current === 'pending' ) {
                if ( ! $is_customer && ! $is_owner && ! $is_admin ) {
                    return new WP_Error( 'permission', __( 'Permission denied.', 'labeng' ) );
                }
            } else {
                if ( ! $is_owner && ! $is_admin ) {
                    return new WP_Error( 'permission', __( 'Only the business owner or admin can cancel a confirmed booking.', 'labeng' ) );
                }
            }
        }

        $wpdb->update(
            $table,
            array( 'status' => $new_status ),
            array( 'id' => $booking_id ),
            array( '%s' ),
            array( '%d' )
        );

        Lab_Email::lab_email_booking_status_change( $booking_id, $new_status );

        return true;
    }

    /**
     * Get a single booking row.
     */
    public static function get_booking( $booking_id ) {
        global $wpdb;
        $table = $wpdb->prefix . 'lab_bookings';
        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $booking_id ) );
    }

    /**
     * The SQL condition that defines an "earning" booking — money the
     * platform/business has actually earned. A booking counts when it is
     * paid online OR the service was completed (offline cash), and it was
     * never cancelled. Single source of truth for all revenue reporting.
     */
    public static function earning_where() {
        return "status <> 'cancelled' AND ( payment_status = 'paid' OR status = 'completed' )";
    }

    /**
     * Aggregate earnings. Pass a business ID for one business, or 0/empty
     * for platform-wide totals.
     *
     * @return array { revenue, commission, net, count }
     */
    public static function get_earnings( $business_id = 0 ) {
        global $wpdb;
        $table = $wpdb->prefix . 'lab_bookings';
        $where = self::earning_where();

        $sql = "SELECT
                    COALESCE(SUM(total_amount),0)   AS revenue,
                    COALESCE(SUM(commission_due),0) AS commission,
                    COUNT(*)                        AS cnt
                FROM {$table}
                WHERE {$where}";

        if ( $business_id ) {
            $sql .= $wpdb->prepare( ' AND business_id = %d', $business_id );
        }

        $row        = $wpdb->get_row( $sql );
        $revenue    = $row ? floatval( $row->revenue ) : 0;
        $commission = $row ? floatval( $row->commission ) : 0;

        return array(
            'revenue'    => $revenue,
            'commission' => $commission,
            'net'        => $revenue - $commission,
            'count'      => $row ? intval( $row->cnt ) : 0,
        );
    }

    /* ──────────────────────────────────────────────────────────
     * AJAX Handlers
     * ────────────────────────────────────────────────────────── */

    public static function ajax_create_booking() {
        check_ajax_referer( 'lab_nonce', 'nonce' );

        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array( 'message' => __( 'You must be logged in.', 'labeng' ) ) );
        }

        $result = self::create_booking( array(
            'business_id'      => absint( $_POST['business_id'] ?? 0 ),
            'customer_id'      => get_current_user_id(),
            'service_name'     => sanitize_text_field( $_POST['service_name'] ?? '' ),
            'service_price'    => floatval( $_POST['service_price'] ?? 0 ),
            'booking_date'     => sanitize_text_field( $_POST['booking_date'] ?? '' ),
            'booking_time'     => sanitize_text_field( $_POST['booking_time'] ?? '' ),
            'notes'            => sanitize_textarea_field( $_POST['notes'] ?? '' ),
            'payment_intent_id'=> sanitize_text_field( $_POST['payment_intent_id'] ?? '' ),
        ) );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'message' => $result->get_error_message() ) );
        }

        $booking = self::get_booking( $result );

        wp_send_json_success( array(
            'message'        => __( 'Booking created successfully! You will receive a confirmation email.', 'labeng' ),
            'booking_id'     => $result,
            'payment_status' => $booking ? $booking->payment_status : 'unpaid',
        ) );
    }

    public static function ajax_get_slots() {
        check_ajax_referer( 'lab_nonce', 'nonce' );

        $business_id = absint( $_POST['business_id'] ?? 0 );
        $date        = sanitize_text_field( $_POST['date'] ?? '' );

        if ( ! $business_id || ! $date ) {
            wp_send_json_error( array( 'message' => __( 'Business and date required.', 'labeng' ) ) );
        }

        $slots = Lab_Availability::get_available_slots( $business_id, $date );

        wp_send_json_success( array( 'slots' => $slots ) );
    }

    public static function ajax_update_booking_status() {
        check_ajax_referer( 'lab_nonce', 'nonce' );

        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array( 'message' => __( 'Not logged in.', 'labeng' ) ) );
        }

        $booking_id = absint( $_POST['booking_id'] ?? 0 );
        $new_status = sanitize_text_field( $_POST['new_status'] ?? '' );

        $result = self::update_status( $booking_id, $new_status, get_current_user_id() );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'message' => $result->get_error_message() ) );
        }

        wp_send_json_success( array( 'message' => __( 'Booking status updated.', 'labeng' ) ) );
    }

    public static function ajax_cancel_booking() {
        check_ajax_referer( 'lab_nonce', 'nonce' );

        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array( 'message' => __( 'Not logged in.', 'labeng' ) ) );
        }

        $booking_id = absint( $_POST['booking_id'] ?? 0 );
        $result = self::update_status( $booking_id, 'cancelled', get_current_user_id() );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'message' => $result->get_error_message() ) );
        }

        wp_send_json_success( array( 'message' => __( 'Booking cancelled.', 'labeng' ) ) );
    }

    /**
     * Create a Stripe PaymentIntent and return the client_secret to the frontend.
     */
    public static function ajax_create_payment_intent() {
        check_ajax_referer( 'lab_nonce', 'nonce' );

        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array( 'message' => __( 'You must be logged in.', 'labeng' ) ) );
        }

        $stripe_secret = get_option( 'lab_stripe_secret', '' );
        if ( ! $stripe_secret ) {
            wp_send_json_error( array( 'message' => __( 'Payment gateway not configured. Please contact the business to arrange payment.', 'labeng' ) ) );
        }

        $amount   = floatval( $_POST['amount'] ?? 0 );
        $currency = sanitize_text_field( $_POST['currency'] ?? get_option( 'lab_currency', 'GBP' ) );

        if ( $amount <= 0 ) {
            wp_send_json_error( array( 'message' => __( 'Invalid payment amount.', 'labeng' ) ) );
        }

        $response = wp_remote_post( 'https://api.stripe.com/v1/payment_intents', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $stripe_secret,
                'Content-Type'  => 'application/x-www-form-urlencoded',
            ),
            'body' => array(
                'amount'                        => (string) intval( $amount * 100 ),
                'currency'                      => strtolower( $currency ),
                'automatic_payment_methods[enabled]' => 'true',
            ),
            'timeout' => 15,
        ) );

        if ( is_wp_error( $response ) ) {
            wp_send_json_error( array( 'message' => __( 'Payment service unavailable. Please try again.', 'labeng' ) ) );
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( ! empty( $body['error'] ) ) {
            wp_send_json_error( array( 'message' => $body['error']['message'] ?? __( 'Payment error.', 'labeng' ) ) );
        }

        wp_send_json_success( array(
            'client_secret'     => $body['client_secret'],
            'payment_intent_id' => $body['id'],
        ) );
    }

    /**
     * Verify a Stripe PaymentIntent is actually paid before recording the booking.
     *
     * @param string $intent_id
     * @param float  $expected_amount
     * @return true|WP_Error
     */
    private static function verify_stripe_payment( $intent_id, $expected_amount ) {
        $stripe_secret = get_option( 'lab_stripe_secret', '' );
        if ( ! $stripe_secret ) {
            return true; // No gateway — skip verification
        }

        $response = wp_remote_get( 'https://api.stripe.com/v1/payment_intents/' . urlencode( $intent_id ), array(
            'headers' => array( 'Authorization' => 'Bearer ' . $stripe_secret ),
            'timeout' => 15,
        ) );

        if ( is_wp_error( $response ) ) {
            return new WP_Error( 'stripe_unreachable', __( 'Could not verify payment. Please try again.', 'labeng' ) );
        }

        $intent = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( empty( $intent['status'] ) || $intent['status'] !== 'succeeded' ) {
            return new WP_Error( 'payment_not_completed', __( 'Payment was not completed successfully.', 'labeng' ) );
        }

        /* Verify the amount matches (within 1 cent tolerance for floating point) */
        $paid_amount = intval( $intent['amount'] ) / 100;
        if ( abs( $paid_amount - floatval( $expected_amount ) ) > 0.01 ) {
            return new WP_Error( 'payment_amount_mismatch', __( 'Payment amount does not match the service price.', 'labeng' ) );
        }

        return true;
    }
}
