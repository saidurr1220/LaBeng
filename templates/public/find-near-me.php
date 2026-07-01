<?php
/**
 * Find Businesses Near Me — postcode-only search (MVP).
 * Reuses the existing _lab_postcode / _lab_city location data.
 * No radius selector: user enters a postcode and businesses are ordered
 * nearest-first using a UK outward-code proximity heuristic (longest
 * matching postcode prefix), then by rating.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

labeng_get_header();

$postcode_query = isset( $_GET['postcode'] ) ? sanitize_text_field( $_GET['postcode'] ) : '';
$has_query      = $postcode_query !== '';

/* Normalise a postcode for comparison: uppercase, no spaces. */
function lab_norm_postcode( $pc ) {
    return strtoupper( str_replace( ' ', '', (string) $pc ) );
}
/* Length of the common leading run between two strings. */
function lab_prefix_match_len( $a, $b ) {
    $len = min( strlen( $a ), strlen( $b ) );
    $i = 0;
    while ( $i < $len && $a[ $i ] === $b[ $i ] ) { $i++; }
    return $i;
}

$results = array();
if ( $has_query ) {
    $q_norm = lab_norm_postcode( $postcode_query );

    $approved = get_posts( array(
        'post_type'   => 'lab_business',
        'numberposts' => -1,
        'post_status' => 'publish',
        'meta_query'  => array(
            array( 'key' => '_lab_status', 'value' => 'approved' ),
        ),
    ) );

    foreach ( $approved as $biz ) {
        $pc        = get_post_meta( $biz->ID, '_lab_postcode', true );
        $pc_norm   = lab_norm_postcode( $pc );
        $score     = $pc_norm ? lab_prefix_match_len( $q_norm, $pc_norm ) : 0;
        $rating    = floatval( get_post_meta( $biz->ID, '_lab_rating_avg', true ) );
        $results[] = array(
            'biz'    => $biz,
            'score'  => $score,
            'rating' => $rating,
        );
    }

    /* Nearest first: longest postcode-prefix match, then highest rating. */
    usort( $results, function( $a, $b ) {
        if ( $a['score'] !== $b['score'] ) return $b['score'] - $a['score'];
        if ( $a['rating'] == $b['rating'] ) return 0;
        return ( $a['rating'] < $b['rating'] ) ? 1 : -1;
    } );
}
?>

<div class="lab-explore-container lab-near-page">
    <div class="lab-near-hero">
        <h1 class="lab-near-hero__title">Find Businesses <span class="highlight-blue">Near Me</span></h1>
        <p class="lab-near-hero__subtitle">Enter your postcode and we'll show you the nearest businesses first.</p>

        <form method="GET" action="<?php echo esc_url( home_url( '/find-near-me/' ) ); ?>" class="lab-near-search-form">
            <div class="lab-near-search-input-wrap">
                <svg class="lab-near-pin" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                <input type="text" name="postcode" value="<?php echo esc_attr( $postcode_query ); ?>" placeholder="Enter your postcode or area to discover businesses near you." autocomplete="postal-code" required />
            </div>
            <button type="submit" class="lab-btn lab-btn--primary">Search</button>
        </form>
    </div>

    <?php if ( $has_query ) : ?>
        <div class="lab-near-results">
            <div class="lab-results-count-stats">
                <span><?php echo count( $results ); ?> <?php echo count( $results ) === 1 ? 'business' : 'businesses'; ?> near <strong><?php echo esc_html( strtoupper( $postcode_query ) ); ?></strong></span>
            </div>

            <?php if ( ! empty( $results ) ) : ?>
                <div class="lab-near-grid">
                    <?php foreach ( $results as $row ) :
                        $biz          = $row['biz'];
                        $thumb        = Lab_Business_CPT::get_business_image( $biz->ID, 'large' );
                        $biz_city     = get_post_meta( $biz->ID, '_lab_city', true );
                        $biz_postcode = get_post_meta( $biz->ID, '_lab_postcode', true );
                        $rating_total = intval( get_post_meta( $biz->ID, '_lab_total_reviews', true ) );
                        $rating_avg   = $rating_total > 0 ? floatval( get_post_meta( $biz->ID, '_lab_rating_avg', true ) ) : 4.8;
                        $terms        = get_the_terms( $biz->ID, 'lab_category' );
                        $cat          = ( ! empty( $terms ) && ! is_wp_error( $terms ) ) ? $terms[0]->name : 'Services';
                        $address      = trim( implode( ', ', array_filter( array( $biz_city, $biz_postcode ) ) ) );
                    ?>
                        <div class="lab-near-card">
                            <div class="lab-near-card__image">
                                <img src="<?php echo esc_url( $thumb ); ?>" alt="<?php echo esc_attr( $biz->post_title ); ?>" loading="lazy" />
                                <span class="lab-near-card__cat"><?php echo esc_html( strtoupper( $cat ) ); ?></span>
                            </div>
                            <div class="lab-near-card__body">
                                <h3 class="lab-near-card__title"><?php echo esc_html( $biz->post_title ); ?></h3>
                                <div class="lab-near-card__rating">
                                    <span class="star-icon">★</span>
                                    <span class="rating-val"><?php echo number_format( $rating_avg, 1 ); ?></span>
                                    <?php if ( $rating_total > 0 ) : ?>
                                        <span class="reviews-count">(<?php echo esc_html( $rating_total ); ?>)</span>
                                    <?php endif; ?>
                                </div>
                                <?php if ( $address ) : ?>
                                    <div class="lab-near-card__address">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                                        <span><?php echo esc_html( $address ); ?></span>
                                    </div>
                                <?php endif; ?>
                                <a href="<?php echo esc_url( get_permalink( $biz->ID ) ); ?>" class="lab-btn lab-btn--primary lab-near-card__btn">View Profile</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else : ?>
                <div class="lab-no-results-msg">
                    <p>No businesses found yet. Try a nearby postcode area.</p>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php
labeng_get_footer();
