<?php
if ( ! defined( 'ABSPATH' ) ) exit;

labeng_get_header();

$business_id = get_the_ID();
$city = get_post_meta( $business_id, '_lab_city', true );
$postcode = get_post_meta( $business_id, '_lab_postcode', true );
$services_json = get_post_meta( $business_id, '_lab_services', true );
$services = json_decode( $services_json, true );
if ( ! is_array( $services ) ) $services = array();

$thumb = Lab_Business_CPT::get_business_image( $business_id, 'large' );

$rating_avg   = floatval( get_post_meta( $business_id, '_lab_rating_avg', true ) );
$rating_total = intval( get_post_meta( $business_id, '_lab_total_reviews', true ) );
?>

<div class="lab-single-container">
    <div class="lab-single-top">
        <a href="javascript:history.back()" class="lab-back-btn">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 18l-6-6 6-6"/></svg>
        </a>
        
        <div class="lab-single-hero">
            <div class="lab-single-hero__image">
                <img src="<?php echo esc_url( $thumb ); ?>" alt="<?php the_title_attribute(); ?>" />
            </div>
            <div class="lab-single-hero__info">
                <h1><?php the_title(); ?></h1>
                <?php if ( $city ) : ?>
                    <div class="lab-single-hero__location">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor" stroke="none"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
                        <?php echo esc_html( $city . ( $postcode ? ', ' . $postcode : '' ) ); ?>
                    </div>
                <?php endif; ?>
                <?php if ( $rating_total > 0 ) : ?>
                    <div class="lab-single-rating">
                        <?php echo Lab_Reviews::render_stars( $rating_avg ); ?>
                        <span class="lab-single-rating__value"><?php echo esc_html( number_format( $rating_avg, 1 ) ); ?></span>
                        <span><?php echo esc_html( sprintf( _n( '(%d review)', '(%d reviews)', $rating_total, 'labeng' ), $rating_total ) ); ?></span>
                    </div>
                <?php endif; ?>
                <a href="#lab-booking-inline" class="lab-btn lab-btn--primary lab-single-book-cta">Book an appointment</a>
            </div>
        </div>
    </div>

    <div class="lab-single-body">
        <div class="lab-single-section" style="margin-top: 0;">
            <h2 style="text-align: center;">About</h2>
            <div class="lab-single-content">
                <?php 
                $content = get_the_content();
                if ( ! empty( $content ) ) {
                    the_content(); 
                } else {
                    echo '<p>Welcome to our business! We pride ourselves on delivering top-notch services and unforgettable experiences to all our customers. Our dedicated team is here to ensure your needs are met with the highest standard of quality and care. Discover the difference with us today.</p>';
                }
                ?>
            </div>
        </div>

        <div class="lab-single-section">
            <h2 style="text-align: center;">Services</h2>
            <ul class="lab-single-services-list">
                <?php 
                if ( ! empty( $services ) ) :
                    foreach ( $services as $svc ) : ?>
                        <li><?php echo esc_html( $svc['name'] ); ?></li>
                    <?php endforeach; 
                else : ?>
                    <li>Standard Consultation</li>
                    <li>Premium Package</li>
                    <li>Express Service</li>
                <?php endif; ?>
            </ul>
        </div>

        <div class="lab-single-section">
            <h2 style="text-align: center;">Reviews</h2>
            <?php
            $reviews = Lab_Reviews::get_reviews( $business_id );
            if ( ! empty( $reviews ) ) :
            ?>
            <div class="lab-reviews-list">
                <?php foreach ( $reviews as $review ) :
                    $review_rating = intval( get_comment_meta( $review->comment_ID, 'lab_rating', true ) );
                ?>
                <div class="lab-review-card">
                    <div class="lab-review-card__header">
                        <div class="lab-review-card__author"><?php echo esc_html( $review->comment_author ); ?></div>
                        <div class="lab-review-card__rating"><?php echo Lab_Reviews::render_stars( $review_rating, true ); ?></div>
                    </div>
                    <div class="lab-review-card__text"><?php echo esc_html( $review->comment_content ); ?></div>
                    <div class="lab-review-card__date"><?php echo esc_html( date( 'M j, Y', strtotime( $review->comment_date ) ) ); ?></div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else : ?>
                <div class="lab-empty-state">
                    <p><?php esc_html_e( 'No reviews yet. Be the first to book and leave a review!', 'labeng' ); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Booking Flow Inline -->
<div class="lab-booking-inline" id="lab-booking-inline" style="margin-top: 3rem; padding-top: 3rem; border-top: 1px solid var(--lab-border);">
    <!-- Handled by JS -->
</div>

<script>
    window.labCurrentBusinessId = <?php echo absint( $business_id ); ?>;
    window.labCurrentServices = <?php echo wp_json_encode( $services ); ?>;
    window.labBusinessHours = <?php echo wp_json_encode( Lab_Availability::get_business_hours( $business_id ) ); ?>;
</script>

<?php
labeng_get_footer();
