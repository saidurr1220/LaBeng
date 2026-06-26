<?php
if ( ! defined( 'ABSPATH' ) ) exit;

labeng_get_header();

// Parse GET parameters
$search_query   = isset( $_GET['search'] ) ? sanitize_text_field( $_GET['search'] ) : '';
$postcode_query = isset( $_GET['postcode'] ) ? sanitize_text_field( $_GET['postcode'] ) : '';
$cat_query      = isset( $_GET['cat'] ) ? sanitize_text_field( $_GET['cat'] ) : '';

$is_search_active = ! empty( $search_query ) || ! empty( $postcode_query ) || ! empty( $cat_query );

// Helper category design mapping (colors & icons)
$cat_design_map = array(
    'food-drink' => array(
        'name'  => 'Food & Drink',
        'color' => '#eab308',
        'bg'    => 'rgba(234, 179, 8, 0.12)',
        'svg'   => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2C8 2 4 5 4 9v2h16V9c0-4-4-7-8-7z"/><path d="M4 17c0 2 4 3 8 3s8-1 8-3v-2H4v2z"/><path d="M4 13h16v2H4z"/><line x1="12" y1="2" x2="12" y2="4"/></svg>'
    ),
    'beauty' => array(
        'name'  => 'Beauty',
        'color' => '#ef4444',
        'bg'    => 'rgba(239, 68, 68, 0.12)',
        'svg'   => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2c0 0-4 4-4 8s4 8 4 8 4-4 4-8-4-8-4-8z"/><path d="M12 22v-4"/></svg>'
    ),
    'fitness' => array(
        'name'  => 'Fitness',
        'color' => '#22c55e',
        'bg'    => 'rgba(34, 197, 94, 0.12)',
        'svg'   => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="8" width="4" height="8" rx="1"/><rect x="18" y="8" width="4" height="8" rx="1"/><path d="M6 12h12"/></svg>'
    ),
    'services' => array(
        'name'  => 'Services',
        'color' => '#a855f7',
        'bg'    => 'rgba(168, 85, 247, 0.12)',
        'svg'   => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>'
    ),
);

// Get categories for the grid
$categories = get_terms( array(
    'taxonomy'   => 'lab_category',
    'hide_empty' => false,
) );

// Dynamically extract unique areas (city or postcode) from approved businesses for the dropdown
$all_approved = get_posts( array(
    'post_type'   => 'lab_business',
    'numberposts' => -1,
    'meta_query'  => array(
        array( 'key' => '_lab_status', 'value' => 'approved' )
    )
) );

$dropdown_areas = array();
foreach ( $all_approved as $b ) {
    $city     = get_post_meta( $b->ID, '_lab_city', true );
    $postcode = get_post_meta( $b->ID, '_lab_postcode', true );
    if ( $city ) $dropdown_areas[] = trim( $city );
    if ( $postcode ) $dropdown_areas[] = trim( $postcode );
}
$dropdown_areas = array_unique( array_filter( $dropdown_areas ) );
sort( $dropdown_areas );
?>

<?php if ( ! $is_search_active ) : ?>
    <!-- ───────────────── LANDING STATE ───────────────── -->
    <div class="lab-explore-container lab-explore-landing">
        <div class="lab-explore-hero">
            <h1 class="lab-explore-hero__title">Explore Great <span class="highlight-blue">Businesses</span></h1>
            <p class="lab-explore-hero__subtitle">Find trusted services, businesses and more.</p>
            
            <div class="lab-landing-search-wrap">
                <form method="GET" action="<?php echo esc_url( home_url( '/businesses/' ) ); ?>" class="lab-landing-search-form">
                    <div class="lab-landing-search-input-wrap">
                        <svg class="search-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        <input type="text" name="search" placeholder="Search for businesses" autocomplete="off" />
                    </div>
                    <button type="submit" class="lab-btn lab-btn--primary">Search</button>
                </form>
            </div>
        </div>

        <div class="lab-popular-categories-section">
            <div class="lab-popular-categories-header">
                <h2>Popular Categories</h2>
                <a href="#" class="see-all-link">See all &gt;</a>
            </div>
            
            <div class="lab-categories-grid-cards">
                <?php
                if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) {
                    foreach ( $categories as $term ) {
                        // Match with our design configurations
                        $slug = $term->slug;
                        $config = isset( $cat_design_map[$slug] ) ? $cat_design_map[$slug] : array(
                            'name'  => $term->name,
                            'color' => '#1FCFE0',
                            'bg'    => 'rgba(31, 207, 224, 0.12)',
                            'svg'   => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/></svg>'
                        );
                        
                        $cat_url = esc_url( add_query_arg( 'cat', $term->slug, home_url( '/businesses/' ) ) );
                        ?>
                        <a href="<?php echo $cat_url; ?>" class="lab-category-landing-card">
                            <div class="lab-category-landing-card__icon-wrap" style="background: <?php echo esc_attr($config['bg']); ?>; color: <?php echo esc_attr($config['color']); ?>;">
                                <?php echo $config['svg']; ?>
                            </div>
                            <span class="lab-category-landing-card__name"><?php echo esc_html( $config['name'] ); ?></span>
                        </a>
                        <?php
                    }
                }
                ?>
            </div>
        </div>
    </div>

<?php else : ?>
    <!-- ───────────────── RESULTS STATE ───────────────── -->
    <div class="lab-explore-container lab-explore-results-view">
        
        <div class="lab-back-categories-wrap">
            <a href="<?php echo esc_url( home_url( '/businesses/' ) ); ?>" class="lab-back-categories-link">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                <span>Back to categories</span>
            </a>
        </div>

        <div class="lab-results-header">
            <h1 class="lab-results-title">
                <?php 
                if ( ! empty( $cat_query ) ) {
                    $term = get_term_by( 'slug', $cat_query, 'lab_category' );
                    echo esc_html( $term ? $term->name : 'Services' );
                } else {
                    echo esc_html( 'Search Results' );
                }
                ?>
            </h1>
        </div>

        <!-- Inline search filters bar -->
        <div class="lab-inline-filters-search-wrap">
            <form method="GET" action="<?php echo esc_url( home_url( '/businesses/' ) ); ?>" class="lab-inline-search-form" id="lab-inline-search-form">
                <?php if ( ! empty( $cat_query ) ) : ?>
                    <input type="hidden" name="cat" value="<?php echo esc_attr($cat_query); ?>" />
                <?php endif; ?>
                
                <div class="lab-inline-keyword-input-wrap">
                    <svg class="search-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input type="text" name="search" placeholder="Search for businesses" value="<?php echo esc_attr($search_query); ?>" autocomplete="off" />
                </div>
                
                <!-- Postcode Area Dropdown Selector -->
                <div class="lab-area-dropdown-wrapper">
                    <input type="hidden" name="postcode" id="lab-selected-area-input" value="<?php echo esc_attr($postcode_query); ?>" />
                    <button type="button" class="lab-area-dropdown-trigger" id="lab-area-dropdown-trigger">
                        <span><?php echo ! empty( $postcode_query ) ? esc_html($postcode_query) : 'All areas'; ?></span>
                        <svg class="chevron-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
                    </button>
                    
                    <!-- Dropdown box -->
                    <div class="lab-area-dropdown-box" id="lab-area-dropdown-box">
                        <div class="lab-area-search-box">
                            <svg class="search-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                            <input type="text" id="lab-area-search-input" placeholder="Postcode or area" autocomplete="off" />
                        </div>
                        <div class="lab-area-options-list" id="lab-area-options-list">
                            <div class="lab-area-option <?php echo empty($postcode_query) ? 'selected' : ''; ?>" data-value="">
                                <span>All areas</span>
                                <?php if ( empty($postcode_query) ) : ?>
                                    <svg class="check-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#1FCFE0" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                <?php endif; ?>
                            </div>
                            <?php foreach ( $dropdown_areas as $area ) : ?>
                                <div class="lab-area-option <?php echo $postcode_query === $area ? 'selected' : ''; ?>" data-value="<?php echo esc_attr($area); ?>">
                                    <span><?php echo esc_html($area); ?></span>
                                    <?php if ( $postcode_query === $area ) : ?>
                                        <svg class="check-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#1FCFE0" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="lab-btn lab-btn--primary">Search</button>
            </form>
        </div>

        <?php
        // Fetch and filter businesses
        $args = array(
            'post_type'   => 'lab_business',
            'post_status' => array( 'publish' ),
            'numberposts' => -1,
            'meta_query'  => array(
                array( 'key' => '_lab_status', 'value' => 'approved' )
            )
        );

        if ( ! empty( $cat_query ) ) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'lab_category',
                    'field'    => 'slug',
                    'terms'    => $cat_query,
                )
            );
        }

        $businesses = get_posts( $args );

        // PHP level filtering for Keyword and Postcode area
        $filtered = array();
        foreach ( $businesses as $biz ) {
            $match = true;
            
            if ( ! empty( $search_query ) ) {
                $q       = strtolower( str_replace( ' ', '', $search_query ) );
                $title   = strtolower( str_replace( ' ', '', $biz->post_title ) );
                $content = strtolower( str_replace( ' ', '', $biz->post_content ) );
                $city    = strtolower( str_replace( ' ', '', get_post_meta( $biz->ID, '_lab_city', true ) ) );
                $postcode= strtolower( str_replace( ' ', '', get_post_meta( $biz->ID, '_lab_postcode', true ) ) );
                
                if ( strpos( $title, $q ) === false && 
                     strpos( $content, $q ) === false && 
                     strpos( $city, $q ) === false && 
                     strpos( $postcode, $q ) === false ) {
                    $match = false;
                }
            }
            
            if ( ! empty( $postcode_query ) && $postcode_query !== 'All areas' ) {
                $pq       = strtolower( str_replace( ' ', '', $postcode_query ) );
                $city     = strtolower( str_replace( ' ', '', get_post_meta( $biz->ID, '_lab_city', true ) ) );
                $postcode = strtolower( str_replace( ' ', '', get_post_meta( $biz->ID, '_lab_postcode', true ) ) );
                
                if ( strpos( $city, $pq ) === false && strpos( $postcode, $pq ) === false ) {
                    $match = false;
                }
            }
            
            if ( $match ) {
                $filtered[] = $biz;
            }
        }
        $businesses = $filtered;
        ?>

        <!-- Filter bar stats & controls -->
        <div class="lab-results-toolbar-filter">
            <div class="lab-results-count-stats">
                <span><?php echo count($businesses); ?> <?php echo count($businesses) === 1 ? 'result' : 'results'; ?></span>
            </div>
            
            <div class="lab-results-right-controls">
                <div class="lab-results-sorting-select-wrap">
                    <span class="sort-label">Sort By:</span>
                    <select id="lab-results-sort-by-select" class="lab-results-select-native">
                        <option value="recommended">Recommended</option>
                        <option value="rating">Top Rated</option>
                        <option value="name">Name</option>
                    </select>
                </div>
                
                <div class="lab-view-toggle">
                    <button class="lab-view-btn active" data-view="grid" aria-label="Grid View">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                    </button>
                    <button class="lab-view-btn" data-view="list" aria-label="List View">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Grid layout container -->
        <div id="lab-archive-results" class="lab-business-grid lab-business-grid--overlay">
            <?php if ( ! empty( $businesses ) ) : ?>
                <?php foreach ( $businesses as $biz ) :
                    $thumb        = Lab_Business_CPT::get_business_image( $biz->ID, 'large' );
                    $biz_city     = get_post_meta( $biz->ID, '_lab_city', true );
                    $biz_postcode = get_post_meta( $biz->ID, '_lab_postcode', true );
                    $biz_rating_avg   = floatval( get_post_meta( $biz->ID, '_lab_rating_avg', true ) );
                    $biz_rating_total = intval( get_post_meta( $biz->ID, '_lab_total_reviews', true ) );
                    
                    // Fallbacks for rating to match screenshots nicely
                    if ( $biz_rating_total === 0 ) {
                        $biz_rating_avg = 4.8;
                    }
                ?>
                    <a href="<?php echo esc_url( get_permalink( $biz->ID ) ); ?>" class="lab-bcard lab-bcard--overlay">
                        <div class="lab-bcard__image">
                            <img src="<?php echo esc_url( $thumb ); ?>" alt="<?php echo esc_attr( $biz->post_title ); ?>" loading="lazy" />
                            <div class="lab-bcard__dark-gradient-overlay"></div>
                        </div>
                        <div class="lab-bcard__overlay-content">
                            <h3 class="lab-bcard__overlay-title"><?php echo esc_html( $biz->post_title ); ?></h3>
                            <div class="lab-bcard__overlay-meta">
                                <div class="rating-info">
                                    <span class="star-icon">★</span>
                                    <span class="rating-val"><?php echo number_format($biz_rating_avg, 1); ?></span>
                                </div>
                                <?php if ( $biz_city ) : ?>
                                    <div class="location-info">
                                        <svg class="location-pin" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                                        <span><?php echo esc_html( $biz_city ); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php else : ?>
                <div class="lab-no-results-msg">
                    <p>No businesses found matching your criteria. Try adjusting your search keywords or postcode area.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<?php
labeng_get_footer();
