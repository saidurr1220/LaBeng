<?php
if ( ! defined( 'ABSPATH' ) ) exit;

labeng_get_header();

/* Get categories for the Popular Categories grid */
$categories = get_terms( array(
    'taxonomy'   => 'lab_category',
    'hide_empty' => false,
) );
?>
<?php
$postcode_query = isset( $_GET['postcode'] ) ? sanitize_text_field( $_GET['postcode'] ) : '';

if ( empty( $postcode_query ) ) :
?>
    <div class="lab-postcode-gateway">
        <h1 class="lab-postcode-title">Discover Businesses<br>Near You</h1>
        <div class="lab-postcode-card">
            <h2>We’ll show you businesses that are local to you</h2>
            <form action="<?php echo esc_url( home_url( '/' ) ); ?>" method="GET" class="lab-form">
                <input type="hidden" name="post_type" value="lab_business" />
                <div class="lab-field">
                    <label>Enter your postcode:</label>
                    <input type="text" name="postcode" placeholder="NQ 4AB" required />
                </div>
                <button type="submit" class="lab-btn lab-btn--primary lab-btn--full">Enter</button>
            </form>
        </div>
    </div>
<?php else : ?>
    <div class="lab-explore-container">
        <div class="lab-explore-header">
            <h1 class="lab-explore-title">Explore</h1>
            <p class="lab-explore-subtitle">Businesses near <?php echo esc_html( $postcode_query ); ?></p>
        </div>

        <div class="lab-search-bar-wrap">
            <form method="GET" action="<?php echo esc_url( home_url( '/' ) ); ?>" class="lab-search-bar">
                <input type="hidden" name="post_type" value="lab_business" />
                <input type="text" name="postcode" id="lab-archive-search-input" placeholder="Search by postcode, name, or area" value="<?php echo esc_attr( $postcode_query ); ?>" />
                <button type="submit" id="lab-archive-search-btn" class="lab-btn lab-btn--primary">Search</button>
            </form>
        </div>

        <div class="lab-explore-filters">
            <div class="lab-explore-location">
                <span class="lab-badge lab-badge--dark"><?php echo esc_html( $postcode_query ); ?> <a href="<?php echo esc_url( home_url('/businesses/') ); ?>" style="color:inherit;text-decoration:none;"><span class="close-icon">&times;</span></a></span>
            </div>
            <div class="lab-explore-controls">
                <div class="lab-view-toggle">
                    <button class="lab-view-btn active" data-view="grid">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                    </button>
                    <button class="lab-view-btn" data-view="list">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
                    </button>
                </div>
            </div>
        </div>

        <div class="lab-popular-categories">
            <h2>Popular Categories</h2>
            <div class="lab-category-grid">
                <?php
                if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) {
                    $index = 0;
                    foreach ( $categories as $term ) {
                        $index++;
                        $hidden_class = ( $index > 4 ) ? 'lab-cat-card--hidden' : '';
                        $display_style = ( $index > 4 ) ? ' style="display:none;"' : '';
                        
                        // Check if admin has uploaded a custom category image/icon
                        $img_id = get_term_meta( $term->term_id, 'lab_cat_image_id', true );
                        $icon = '';
                        if ( $img_id ) {
                            $img_url = wp_get_attachment_image_url( $img_id, 'thumbnail' );
                            if ( $img_url ) {
                                $icon = '<img src="' . esc_url( $img_url ) . '" width="40" height="40" style="object-fit:contain; border-radius:4px;" alt="' . esc_attr( $term->name ) . '" />';
                            }
                        }
                        
                        // Fallback to SVGs if no custom icon uploaded
                        if ( ! $icon ) {
                            $icon = '<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>';
                            if ( $term->slug === 'food-drink' ) {
                                $icon = '<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2C8 2 4 5 4 9v2h16V9c0-4-4-7-8-7z"/><path d="M4 17c0 2 4 3 8 3s8-1 8-3v-2H4v2z"/><path d="M4 13h16v2H4z"/></svg>';
                            } elseif ( $term->slug === 'beauty' ) {
                                $icon = '<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2c0 0-4 4-4 8s4 8 4 8 4-4 4-8-4-8-4-8z"/><path d="M12 22v-4"/></svg>';
                            } elseif ( $term->slug === 'fitness' ) {
                                $icon = '<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="8" width="4" height="8" rx="1"/><rect x="18" y="8" width="4" height="8" rx="1"/><path d="M6 12h12"/></svg>';
                            }
                        }
                        
                        $cat_url = esc_url( add_query_arg( 'cat', $term->slug, home_url( '/businesses/' ) ) );
                        echo '<a href="' . $cat_url . '" class="lab-cat-card ' . esc_attr($hidden_class) . '" data-cat="' . esc_attr( $term->slug ) . '"' . $display_style . '>';
                        echo '<div class="lab-cat-icon">' . $icon . '</div>';
                        echo '<span>' . esc_html( $term->name ) . '</span>';
                        echo '</a>';
                    }
                }
                ?>
            </div>
            <?php if ( ! empty( $categories ) && count( $categories ) > 4 ) : ?>
                <div class="lab-cat-more">
                    <button class="lab-btn lab-btn--white"><?php esc_html_e( 'More', 'labeng' ); ?></button>
                </div>
            <?php endif; ?>
        </div>

        <div id="lab-archive-results" class="lab-business-grid lab-business-grid--large">
            <?php
            $args = array(
                'post_type'   => 'lab_business',
                'post_status' => array( 'publish', 'draft' ),
                'numberposts' => -1,
                'meta_query'  => array(
                    array( 'key' => '_lab_status', 'value' => 'approved' )
                )
            );
            $cat_query = isset( $_GET['cat'] ) ? sanitize_text_field( $_GET['cat'] ) : '';
            if ( $cat_query ) {
                $args['tax_query'] = array(
                    array(
                        'taxonomy' => 'lab_category',
                        'field'    => 'slug',
                        'terms'    => $cat_query,
                    )
                );
            }
            $businesses = get_posts( $args );

            // Filter in PHP for Name, Area, or Postcode
            if ( $postcode_query ) {
                $q = strtolower( str_replace( ' ', '', $postcode_query ) );
                $filtered = array();
                foreach ( $businesses as $biz ) {
                    $title = strtolower( str_replace( ' ', '', $biz->post_title ) );
                    $postcode = strtolower( str_replace( ' ', '', get_post_meta( $biz->ID, '_lab_postcode', true ) ) );
                    $city = strtolower( str_replace( ' ', '', get_post_meta( $biz->ID, '_lab_city', true ) ) );
                    
                    if ( strpos( $title, $q ) !== false || strpos( $postcode, $q ) !== false || strpos( $city, $q ) !== false ) {
                        $filtered[] = $biz;
                    }
                }
                $businesses = $filtered;
            }

            ?>
            <?php if ( ! empty( $businesses ) ) : ?>
                <?php foreach ( $businesses as $biz ) :
                    $thumb = Lab_Business_CPT::get_business_image( $biz->ID, 'large' );
                    $biz_city     = get_post_meta( $biz->ID, '_lab_city', true );
                    $biz_postcode = get_post_meta( $biz->ID, '_lab_postcode', true );
                    $biz_rating_avg   = floatval( get_post_meta( $biz->ID, '_lab_rating_avg', true ) );
                    $biz_rating_total = intval( get_post_meta( $biz->ID, '_lab_total_reviews', true ) );
                ?>
                    <a href="<?php echo esc_url( get_permalink( $biz->ID ) ); ?>" class="lab-bcard">
                        <div class="lab-bcard__image">
                            <img src="<?php echo esc_url( $thumb ); ?>" alt="<?php echo esc_attr( $biz->post_title ); ?>" loading="lazy" />
                        </div>
                        <div class="lab-bcard__title" style="padding-bottom:0; margin-bottom:0.25rem;"><?php echo esc_html( $biz->post_title ); ?></div>
                        <?php if ( $biz_rating_total > 0 ) : ?>
                            <div class="lab-bcard__rating">
                                <?php echo Lab_Reviews::render_stars( $biz_rating_avg, true ); ?>
                                <span>(<?php echo esc_html( $biz_rating_total ); ?>)</span>
                            </div>
                        <?php endif; ?>
                        <div style="font-size: 0.85rem; color: #888; padding: 0 1rem 1rem 1rem; display: flex; align-items: center; gap: 4px;">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink: 0; color: #3b82f6;"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                            <span><?php echo esc_html( $biz_city . ( $biz_postcode ? ', ' . $biz_postcode : '' ) ); ?></span>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php else : ?>
                <p>No businesses found<?php echo $postcode_query ? ' near ' . esc_html( $postcode_query ) : ''; ?>.</p>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<?php
labeng_get_footer(); ?>
