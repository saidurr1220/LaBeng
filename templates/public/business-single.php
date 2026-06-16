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
