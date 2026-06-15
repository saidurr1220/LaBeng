<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$reviews = Lab_Reviews::get_customer_reviews( get_current_user_id() );
?>

<h2 class="lab-section-title"><?php esc_html_e( 'My Reviews', 'labeng' ); ?></h2>

<?php if ( ! empty( $reviews ) ) : ?>
<div class="lab-reviews-list">
    <?php foreach ( $reviews as $review ) :
        $rating     = intval( get_comment_meta( $review->comment_ID, 'lab_rating', true ) );
        $booking_id = intval( get_comment_meta( $review->comment_ID, 'lab_booking_id', true ) );
        $business   = get_post( $review->comment_post_ID );
        $booking    = Lab_Bookings::get_booking( $booking_id );
    ?>
    <div class="lab-review-card">
        <div class="lab-review-card__header">
            <div class="lab-review-card__business"><?php echo esc_html( $business ? $business->post_title : '—' ); ?></div>
            <div class="lab-review-card__rating"><?php echo Lab_Reviews::render_stars( $rating, true ); ?></div>
        </div>
        <div class="lab-review-card__text"><?php echo esc_html( $review->comment_content ); ?></div>
        <div class="lab-review-card__meta">
            <span><?php echo esc_html( date( 'M j, Y', strtotime( $review->comment_date ) ) ); ?></span>
            <?php if ( $booking ) : ?>
                <span class="lab-text-muted"> · <?php echo esc_html( sprintf( __( 'Booking: %s on %s', 'labeng' ), $booking->service_name, date( 'M j', strtotime( $booking->booking_date ) ) ) ); ?></span>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php else : ?>
    <div class="lab-empty-state">
        <p><?php esc_html_e( "You haven't left any reviews yet.", 'labeng' ); ?></p>
    </div>
<?php endif; ?>
