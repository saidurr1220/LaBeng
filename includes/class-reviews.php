<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class Lab_Reviews
 * Uses WP native comments with extra meta (lab_rating, lab_booking_id).
 */
class Lab_Reviews {

    public static function init() {
        add_action( 'wp_ajax_lab_submit_review', array( __CLASS__, 'ajax_submit_review' ) );
    }

    /**
     * Submit a review for a business booking.
     *
     * @param int    $business_id
     * @param int    $customer_id
     * @param int    $rating       1-5
     * @param string $content
     * @param int    $booking_id
     * @return int|WP_Error Comment ID.
     */
    public static function submit_review( $business_id, $customer_id, $rating, $content, $booking_id ) {
        global $wpdb;

        /* 1. Verify booking exists and is completed */
        $booking = Lab_Bookings::get_booking( $booking_id );
        if ( ! $booking ) {
            return new WP_Error( 'no_booking', __( 'Booking not found.', 'labeng' ) );
        }
        if ( $booking->status !== 'completed' ) {
            return new WP_Error( 'not_completed', __( 'You can only review completed bookings.', 'labeng' ) );
        }

        /* 2. Verify booking belongs to customer */
        if ( absint( $booking->customer_id ) !== $customer_id ) {
            return new WP_Error( 'not_yours', __( 'This booking does not belong to you.', 'labeng' ) );
        }

        /* 3. Verify no existing review for this booking */
        $existing = get_comments( array(
            'post_id'    => $business_id,
            'meta_key'   => 'lab_booking_id',
            'meta_value' => $booking_id,
            'count'      => true,
        ) );
        if ( $existing > 0 ) {
            return new WP_Error( 'already_reviewed', __( 'You have already reviewed this booking.', 'labeng' ) );
        }

        /* 4. Insert comment */
        $user = get_userdata( $customer_id );
        $comment_id = wp_insert_comment( array(
            'comment_post_ID' => $business_id,
            'user_id'         => $customer_id,
            'comment_author'  => $user ? $user->display_name : 'Customer',
            'comment_author_email' => $user ? $user->user_email : '',
            'comment_content' => sanitize_textarea_field( $content ),
            'comment_approved'=> 1,
            'comment_type'    => 'review',
        ) );

        if ( ! $comment_id ) {
            return new WP_Error( 'insert_fail', __( 'Failed to save review.', 'labeng' ) );
        }

        /* 5. Save meta */
        update_comment_meta( $comment_id, 'lab_rating', absint( $rating ) );
        update_comment_meta( $comment_id, 'lab_booking_id', absint( $booking_id ) );

        /* 6. Recalculate rating */
        self::recalculate_rating( $business_id );

        return $comment_id;
    }

    /**
     * Recalculate average rating for a business.
     *
     * @param int $business_id
     */
    public static function recalculate_rating( $business_id ) {
        $comments = get_comments( array(
            'post_id' => $business_id,
            'status'  => 'approve',
            'type'    => 'review',
        ) );

        $total = 0;
        $count = 0;

        foreach ( $comments as $comment ) {
            $rating = intval( get_comment_meta( $comment->comment_ID, 'lab_rating', true ) );
            if ( $rating > 0 ) {
                $total += $rating;
                $count++;
            }
        }

        $avg = $count > 0 ? round( $total / $count, 2 ) : 0;

        update_post_meta( $business_id, '_lab_rating_avg', $avg );
        update_post_meta( $business_id, '_lab_total_reviews', $count );
    }

    /**
     * Get reviews for a business.
     *
     * @param int $business_id
     * @return array
     */
    public static function get_reviews( $business_id ) {
        return get_comments( array(
            'post_id' => $business_id,
            'status'  => 'approve',
            'type'    => 'review',
            'orderby' => 'comment_date',
            'order'   => 'DESC',
        ) );
    }

    /**
     * Get reviews left by a customer.
     *
     * @param int $customer_id
     * @return array
     */
    public static function get_customer_reviews( $customer_id ) {
        return get_comments( array(
            'user_id' => $customer_id,
            'status'  => 'approve',
            'type'    => 'review',
            'orderby' => 'comment_date',
            'order'   => 'DESC',
        ) );
    }

    /**
     * Check if customer already reviewed a booking.
     *
     * @param int $booking_id
     * @return bool
     */
    public static function has_reviewed_booking( $booking_id ) {
        $count = get_comments( array(
            'meta_key'   => 'lab_booking_id',
            'meta_value' => $booking_id,
            'count'      => true,
        ) );
        return $count > 0;
    }

    /**
     * Render star rating HTML.
     *
     * @param float $rating
     * @param bool  $small
     * @return string
     */
    public static function render_stars( $rating, $small = false ) {
        $class = $small ? 'lab-stars lab-stars--sm' : 'lab-stars';
        $html = '<span class="' . esc_attr( $class ) . '">';
        for ( $i = 1; $i <= 5; $i++ ) {
            if ( $i <= floor( $rating ) ) {
                $html .= '<span class="lab-star lab-star--full">★</span>';
            } elseif ( $i - 0.5 <= $rating ) {
                $html .= '<span class="lab-star lab-star--half">★</span>';
            } else {
                $html .= '<span class="lab-star lab-star--empty">★</span>';
            }
        }
        $html .= '</span>';
        return $html;
    }

    /**
     * AJAX: Submit review.
     */
    public static function ajax_submit_review() {
        check_ajax_referer( 'lab_nonce', 'nonce' );

        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array( 'message' => __( 'You must be logged in.', 'labeng' ) ) );
        }

        $business_id = absint( $_POST['business_id'] ?? 0 );
        $booking_id  = absint( $_POST['booking_id'] ?? 0 );
        $rating      = absint( $_POST['rating'] ?? 0 );
        $content     = sanitize_textarea_field( $_POST['review_text'] ?? '' );

        if ( $rating < 1 || $rating > 5 ) {
            wp_send_json_error( array( 'message' => __( 'Rating must be between 1 and 5.', 'labeng' ) ) );
        }
        if ( empty( $content ) ) {
            wp_send_json_error( array( 'message' => __( 'Review text is required.', 'labeng' ) ) );
        }

        $result = self::submit_review( $business_id, get_current_user_id(), $rating, $content, $booking_id );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'message' => $result->get_error_message() ) );
        }

        wp_send_json_success( array( 'message' => __( 'Review submitted. Thank you!', 'labeng' ) ) );
    }
}
