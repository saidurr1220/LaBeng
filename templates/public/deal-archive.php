<?php
if ( ! defined( 'ABSPATH' ) ) exit;

labeng_get_header();
?>

<div class="lab-deals-container">
    <div class="lab-explore-header">
        <h1 class="lab-explore-title">Deals</h1>
        <p class="lab-explore-subtitle">Explore our deals of the week</p>
    </div>

    <div class="lab-deals-carousel-wrap">
        <!-- Main Highlight Deal -->
        <div class="lab-deal-highlight">
            <img src="https://images.unsplash.com/photo-1503736334956-4c8f8e92946d?auto=format&fit=crop&w=1200&q=90" alt="Luxury Cars" class="lab-deal-highlight__bg" />
            <div class="lab-deal-highlight__content">
                <h2>25% off Luxury Cars</h2>
                <a href="<?php echo esc_url( home_url( '/businesses/?cat=services' ) ); ?>" class="lab-btn lab-btn--primary">Get Deal</a>
            </div>
        </div>
    </div>

    <div class="lab-deals-grid">
        <?php
        $args = array(
            'post_type'   => 'lab_deal',
            'post_status' => 'publish',
            'numberposts' => -1,
        );
        $deals = get_posts( $args );

        if ( ! empty( $deals ) ) {
            foreach ( $deals as $deal ) {
                $biz_id = get_post_meta( $deal->ID, '_lab_deal_business_id', true );
                $biz = get_post( $biz_id );
                $biz_title = $biz ? $biz->post_title : 'Unknown Business';
                $old = get_post_meta( $deal->ID, '_lab_deal_old_price', true );
                $new = get_post_meta( $deal->ID, '_lab_deal_new_price', true );
                $cs  = get_option( 'lab_currency_symbol', '£' );
                $thumb = get_the_post_thumbnail_url( $deal->ID, 'large' );
                if ( ! $thumb && $biz_id ) {
                    $thumb = Lab_Business_CPT::get_business_image( $biz_id, 'large' );
                }
                ?>
                <a href="<?php echo esc_url( get_permalink( $biz_id ) ); ?>" style="text-decoration:none; color:inherit; display:block;">
                    <div class="lab-deal-card">
                        <div class="lab-deal-card__image">
                            <img src="<?php echo esc_url( $thumb ); ?>" alt="<?php echo esc_attr( $biz_title ); ?>" loading="lazy" />
                        </div>
                        <div class="lab-deal-card__body">
                            <h3><?php echo esc_html( $biz_title ); ?></h3>
                            <p class="lab-deal-card__desc"><?php echo esc_html( $deal->post_title ); ?></p>
                            <div class="lab-deal-card__prices">
                                <span class="new-price"><?php echo esc_html( $cs . $new ); ?></span>
                                <?php if ( $old ) : ?>
                                    <span class="old-price"><?php echo esc_html( $cs . $old ); ?></span>
                                <?php endif; ?>
                            </div>
                            <span class="lab-btn lab-btn--primary lab-btn--full">Get Deal</span>
                        </div>
                    </div>
                </a>
                <?php
            }
        } else {
            echo '<p>No active deals right now.</p>';
        }
        ?>
    </div>
    
    <div class="lab-deals-more">
        <a href="<?php echo esc_url( home_url( '/businesses/' ) ); ?>" class="lab-btn lab-btn--white">Explore All Businesses</a>
    </div>
</div>

<?php
labeng_get_footer(); ?>
