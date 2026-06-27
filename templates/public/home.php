<?php
if ( ! defined( 'ABSPATH' ) ) exit;

labeng_get_header();

$args = array(
    'post_type'   => 'lab_business',
    'post_status' => array( 'publish' ),
    'numberposts' => 5,
    'meta_query'  => array(
        array( 'key' => '_lab_status', 'value' => 'approved' )
    )
);
$carousel_items = get_posts( $args );

// Fallback mock items if there are less than 5 approved businesses
if ( count( $carousel_items ) < 5 ) {
    $fallback_mocks = [
        [
            'title' => 'Labeng Rentals',
            'cat' => 'AUTOMOTIVE',
            'desc' => 'Luxury rental vehicles, available same day',
            'rating' => '4.9',
            'reviews' => '5 reviews',
            'image' => 'https://images.unsplash.com/photo-1503736334956-4c8f8e92946d?auto=format&fit=crop&w=800&q=90',
            'url' => home_url( '/businesses/' )
        ],
        [
            'title' => 'Smokey Barbers',
            'cat' => 'BEAUTY',
            'desc' => 'Premium cuts and grooming experience',
            'rating' => '4.7',
            'reviews' => '12 reviews',
            'image' => 'https://images.unsplash.com/photo-1503951914875-452162b0f3f1?auto=format&fit=crop&w=800&q=90',
            'url' => home_url( '/businesses/' )
        ],
        [
            'title' => 'Luigi\'s Pizza',
            'cat' => 'FOOD & DRINK',
            'desc' => 'Authentic stone baked neapolitan pizza',
            'rating' => '4.8',
            'reviews' => '8 reviews',
            'image' => 'https://images.unsplash.com/photo-1574071318508-1cdbab80d002?auto=format&fit=crop&w=800&q=90',
            'url' => home_url( '/businesses/' )
        ],
        [
            'title' => 'IronWorks Gym',
            'cat' => 'FITNESS',
            'desc' => 'Modern fitness equipment and personal trainers',
            'rating' => '4.6',
            'reviews' => '15 reviews',
            'image' => 'https://images.unsplash.com/photo-1571902943202-507ec2618e8f?auto=format&fit=crop&w=800&q=90',
            'url' => home_url( '/businesses/' )
        ],
        [
            'title' => 'The Grooming Lounge',
            'cat' => 'BEAUTY',
            'desc' => 'Relaxing environment and expert hair styling',
            'rating' => '4.9',
            'reviews' => '20 reviews',
            'image' => 'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=800&q=90',
            'url' => home_url( '/businesses/' )
        ]
    ];

    reset($fallback_mocks);
    while ( count( $carousel_items ) < 5 ) {
        $mock = current( $fallback_mocks );
        if ( ! $mock ) {
            reset( $fallback_mocks );
            $mock = current( $fallback_mocks );
        }
        $carousel_items[] = (object) [
            'ID' => 0,
            'post_title' => $mock['title'],
            'is_mock' => true,
            'cat' => $mock['cat'],
            'desc' => $mock['desc'],
            'rating' => $mock['rating'],
            'reviews' => $mock['reviews'],
            'image' => $mock['image'],
            'url' => $mock['url']
        ];
        next( $fallback_mocks );
    }
}
?>

<div class="lab-hero">
    <div class="lab-hero__content">
        <h1 class="lab-hero__title">Discover and Elevate<br>Exciting Businesses</h1>
        <p class="lab-hero__subtitle">Food, services, fitness and more</p>
    </div>

    <div class="lab-hero__cta-wrap">
        <a href="<?php echo esc_url( home_url( '/businesses/' ) ); ?>" class="lab-btn lab-btn--primary lab-btn--discover">Discover Businesses</a>
    </div>

    <div class="lab-hero__carousel-wrap">
        <button class="lab-carousel__nav-btn lab-carousel__nav-btn--prev" aria-label="Previous">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
        </button>
        
        <div class="lab-carousel" id="lab-home-carousel">
            <div class="lab-carousel__track">
                <?php foreach ( $carousel_items as $index => $item ) :
                    $is_mock = isset( $item->is_mock ) && $item->is_mock;
                    if ( ! $is_mock ) {
                        $title = $item->post_title;
                        $url = get_permalink( $item->ID );
                        $image = Lab_Business_CPT::get_business_image( $item->ID, 'large' );
                        
                        $city = get_post_meta( $item->ID, '_lab_city', true );
                        $rating_avg = floatval( get_post_meta( $item->ID, '_lab_rating_avg', true ) );
                        $rating_total = intval( get_post_meta( $item->ID, '_lab_total_reviews', true ) );
                        $rating = $rating_total > 0 ? number_format( $rating_avg, 1 ) : '4.9';
                        $reviews = $rating_total > 0 ? sprintf( _n( '%d review', '%d reviews', $rating_total, 'labeng' ), $rating_total ) : '5 reviews';
                        
                        $terms = get_the_terms( $item->ID, 'lab_category' );
                        $cat = ! empty( $terms ) && ! is_wp_error( $terms ) ? strtoupper( $terms[0]->name ) : 'SERVICES';
                        $desc = get_post_meta( $item->ID, '_lab_tagline', true );
                        if ( ! $desc ) {
                            $desc = wp_trim_words( get_post_field( 'post_content', $item->ID ), 8 );
                        }
                        if ( ! $desc ) {
                            $desc = 'Discover our local services and special deals.';
                        }
                    } else {
                        $title = $item->post_title;
                        $url = $item->url;
                        $image = $item->image;
                        $cat = $item->cat;
                        $desc = $item->desc;
                        $rating = $item->rating;
                        $reviews = $item->reviews;
                    }
                ?>
                    <div class="lab-carousel__slide">
                        <div class="lab-carousel__card-container">
                            <div class="lab-carousel__card-image-wrap">
                                <span class="lab-carousel__card-badge">Featured</span>
                                <img src="<?php echo esc_url( $image ); ?>" alt="<?php echo esc_attr( $title ); ?>" />
                            </div>
                            <div class="lab-carousel__card-footer">
                                <div class="lab-carousel__card-info">
                                    <span class="lab-carousel__card-cat"><?php echo esc_html( $cat ); ?></span>
                                    <h3 class="lab-carousel__card-title"><?php echo esc_html( $title ); ?></h3>
                                    <p class="lab-carousel__card-desc"><?php echo esc_html( $desc ); ?></p>
                                    <div class="lab-carousel__card-rating">
                                        <span class="star-icon">★</span>
                                        <span class="rating-val"><?php echo esc_html( $rating ); ?></span>
                                        <span class="reviews-count">(<?php echo esc_html( $reviews ); ?>)</span>
                                    </div>
                                </div>
                                <div class="lab-carousel__card-action">
                                    <a href="<?php echo esc_url( $url ); ?>" class="lab-btn lab-btn--view">
                                        <span>View</span>
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <button class="lab-carousel__nav-btn lab-carousel__nav-btn--next" aria-label="Next">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
        </button>
    </div>
</div>

<?php
labeng_get_footer();
