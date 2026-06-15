<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$reviews = Lab_Reviews::get_reviews( $business_id );
?>

<h2 class="lab-section-title"><?php esc_html_e( 'Reviews', 'labeng' ); ?></h2>

<?php if ( ! empty( $reviews ) ) : ?>
<div class="lab-reviews-list">
    <?php foreach ( $reviews as $review ) :
        $rating = intval( get_comment_meta( $review->comment_ID, 'lab_rating', true ) );
    ?>
    <div class="lab-review-card">
        <div class="lab-review-card__header">
            <div class="lab-review-card__author"><?php echo esc_html( $review->comment_author ); ?></div>
            <div class="lab-review-card__rating"><?php echo Lab_Reviews::render_stars( $rating, true ); ?></div>
        </div>
        <div class="lab-review-card__text"><?php echo esc_html( $review->comment_content ); ?></div>
        <div class="lab-review-card__date"><?php echo esc_html( date( 'M j, Y', strtotime( $review->comment_date ) ) ); ?></div>
    </div>
    <?php endforeach; ?>
</div>
<?php else : ?>
    <div class="lab-empty-state">
        <p><?php esc_html_e( 'No reviews yet. Reviews will appear here once customers leave feedback after completing their bookings.', 'labeng' ); ?></p>
    </div>
<?php endif; ?>
