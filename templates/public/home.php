<?php
if ( ! defined( 'ABSPATH' ) ) exit;

include LABENG_PATH . 'templates/global/header.php';
?>

<div class="lab-hero">
    <div class="lab-hero__content">
        <h1 class="lab-hero__logo">LaBeng</h1>
        <p class="lab-hero__tagline">Where Great Businesses Get Discovered</p>
    </div>
    
    <div class="lab-hero__carousel-wrap">
        <div class="lab-carousel" id="lab-home-carousel">
            <div class="lab-carousel__track">
                <!-- Example Carousel Items to match the PDF -->
                <div class="lab-carousel__slide">
                    <img src="https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=600&q=90" alt="Interior Design" />
                </div>
                <div class="lab-carousel__slide">
                    <img src="https://images.unsplash.com/photo-1503736334956-4c8f8e92946d?auto=format&fit=crop&w=600&q=90" alt="Luxury Cars" />
                </div>
                <div class="lab-carousel__slide lab-carousel__slide--active">
                    <div class="lab-carousel__card lab-carousel__card--split">
                        <div class="lab-carousel__card-text">
                            <h2>FREEDOM<br>TO EXPLORE</h2>
                            <p style="margin-bottom:2rem; font-size:1.1rem;">Quality cars. Great prices.<br>Unforgettable journeys.</p>
                            <ul class="lab-carousel__features" style="list-style:none; padding:0; margin:0; display:flex; flex-direction:column; gap:1.5rem;">
                                <li style="display:flex; gap:1rem; align-items:flex-start;">
                                    <div class="icon" style="font-size:1.5rem;">📅</div>
                                    <div style="font-size:0.95rem;"><strong>EASY BOOKING</strong><br><span style="color:#666;">Quick, simple and secure.</span></div>
                                </li>
                                <li style="display:flex; gap:1rem; align-items:flex-start;">
                                    <div class="icon" style="font-size:1.5rem;">🛡️</div>
                                    <div style="font-size:0.95rem;"><strong>FULLY INSURED</strong><br><span style="color:#666;">Your safety is our priority.</span></div>
                                </li>
                            </ul>
                        </div>
                        <div class="lab-carousel__card-img">
                            <img src="https://images.unsplash.com/photo-1544636331-e26879cd4d9b?auto=format&fit=crop&w=800&q=90" alt="Car" style="width:100%; height:100%; object-fit:cover;" />
                        </div>
                    </div>
                </div>
                <div class="lab-carousel__slide">
                    <img src="https://images.unsplash.com/photo-1560066984-138dadb4c035?auto=format&fit=crop&w=600&q=90" alt="Beauty & Salon" />
                </div>
                <div class="lab-carousel__slide">
                    <img src="https://images.unsplash.com/photo-1571902943202-507ec2618e8f?auto=format&fit=crop&w=600&q=90" alt="Fitness & Gym" />
                </div>
            </div>
        </div>
    </div>

    <?php
    $home_cats = get_terms( array(
        'taxonomy'   => 'lab_category',
        'hide_empty' => false,
        'number'     => 4,
        'orderby'    => 'count',
        'order'      => 'DESC',
    ) );
    $lab_cat_fallbacks = array(
        'https://images.unsplash.com/photo-1618219908412-a29a1bb7b86e?auto=format&fit=crop&w=500&q=90',
        'https://images.unsplash.com/photo-1503736334956-4c8f8e92946d?auto=format&fit=crop&w=500&q=90',
        'https://images.unsplash.com/photo-1560066984-138dadb4c035?auto=format&fit=crop&w=500&q=90',
        'https://images.unsplash.com/photo-1571902943202-507ec2618e8f?auto=format&fit=crop&w=500&q=90',
    );
    if ( ! is_wp_error( $home_cats ) && ! empty( $home_cats ) ) : ?>
    <div class="lab-categories">
        <h2 class="lab-section-title">Explore Categories</h2>
        <div class="lab-categories-grid">
            <?php foreach ( $home_cats as $i => $cat ) :
                $img_id  = get_term_meta( $cat->term_id, 'lab_cat_image_id', true );
                $img_url = $img_id ? wp_get_attachment_image_url( $img_id, 'large' ) : '';
                if ( ! $img_url ) {
                    $img_url = $lab_cat_fallbacks[ $i % count( $lab_cat_fallbacks ) ];
                }
            ?>
            <a href="<?php echo esc_url( home_url( '/businesses/?cat=' . $cat->slug ) ); ?>" class="lab-category-card">
                <img src="<?php echo esc_url( $img_url ); ?>" alt="<?php echo esc_attr( $cat->name ); ?>" />
                <h3><?php echo esc_html( $cat->name ); ?></h3>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <div class="lab-hero__action" style="max-width: 600px; margin: 0 auto; width: 100%; box-sizing: border-box; padding: 0 1rem;">
        <h3 style="margin-bottom: 1rem; color: #fff; font-size: 1.5rem; font-weight: 500;">Find local businesses near you</h3>
        <form action="<?php echo esc_url( home_url( '/' ) ); ?>" method="GET" class="lab-hero__search-form">
            <input type="hidden" name="post_type" value="lab_business" />
            <input type="text" name="postcode" class="lab-hero__search-input" placeholder="Enter your postcode (e.g. SW1A 2AA)" required>
            <button type="submit" class="lab-btn lab-btn--primary lab-hero__search-btn">Discover</button>
        </form>
    </div>
</div>

<?php
include LABENG_PATH . 'templates/global/footer.php';

