<?php
if ( ! defined( 'ABSPATH' ) ) exit;

global $wpdb;
$table = $wpdb->prefix . 'lab_bookings';
$cs    = get_option( 'lab_currency_symbol', '£' );

/* Booking counts by status */
$total     = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE business_id = %d", $business_id ) );
$confirmed = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE business_id = %d AND status = 'confirmed'", $business_id ) );
$cancelled = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE business_id = %d AND status = 'cancelled'", $business_id ) );
$completed = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE business_id = %d AND status = 'completed'", $business_id ) );

/* Revenue */
$revenue = $wpdb->get_var( $wpdb->prepare(
    "SELECT COALESCE(SUM(total_amount),0) FROM {$table} WHERE business_id = %d AND status = 'completed'",
    $business_id
) );

/* Commission due */
$commission = $wpdb->get_var( $wpdb->prepare(
    "SELECT COALESCE(SUM(commission_due),0) FROM {$table} WHERE business_id = %d AND status = 'completed'",
    $business_id
) );

/* Reviews */
$total_reviews = intval( get_post_meta( $business_id, '_lab_total_reviews', true ) );
$avg_rating    = floatval( get_post_meta( $business_id, '_lab_rating_avg', true ) );

/* Most booked service */
$most_booked = $wpdb->get_var( $wpdb->prepare(
    "SELECT service_name FROM {$table} WHERE business_id = %d GROUP BY service_name ORDER BY COUNT(*) DESC LIMIT 1",
    $business_id
) );
?>

<h2 class="lab-section-title"><?php esc_html_e( 'Statistics', 'labeng' ); ?></h2>

<div class="lab-stats-grid lab-stats-grid--3col">
    <div class="lab-stat-card">
        <span class="lab-stat-card__label"><?php esc_html_e( 'Total Bookings', 'labeng' ); ?></span>
        <span class="lab-stat-card__value"><?php echo esc_html( $total ); ?></span>
    </div>
    <div class="lab-stat-card">
        <span class="lab-stat-card__label"><?php esc_html_e( 'Confirmed', 'labeng' ); ?></span>
        <span class="lab-stat-card__value lab-text-accent"><?php echo esc_html( $confirmed ); ?></span>
    </div>
    <div class="lab-stat-card">
        <span class="lab-stat-card__label"><?php esc_html_e( 'Completed', 'labeng' ); ?></span>
        <span class="lab-stat-card__value lab-text-success"><?php echo esc_html( $completed ); ?></span>
    </div>
    <div class="lab-stat-card">
        <span class="lab-stat-card__label"><?php esc_html_e( 'Cancelled', 'labeng' ); ?></span>
        <span class="lab-stat-card__value lab-text-danger"><?php echo esc_html( $cancelled ); ?></span>
    </div>
    <div class="lab-stat-card">
        <span class="lab-stat-card__label"><?php esc_html_e( 'Total Revenue', 'labeng' ); ?></span>
        <span class="lab-stat-card__value"><?php echo esc_html( $cs . number_format( $revenue, 2 ) ); ?></span>
    </div>
    <div class="lab-stat-card">
        <span class="lab-stat-card__label"><?php esc_html_e( 'Commission Due', 'labeng' ); ?></span>
        <span class="lab-stat-card__value"><?php echo esc_html( $cs . number_format( $commission, 2 ) ); ?></span>
    </div>
    <div class="lab-stat-card">
        <span class="lab-stat-card__label"><?php esc_html_e( 'Total Reviews', 'labeng' ); ?></span>
        <span class="lab-stat-card__value"><?php echo esc_html( $total_reviews ); ?></span>
    </div>
    <div class="lab-stat-card">
        <span class="lab-stat-card__label"><?php esc_html_e( 'Average Rating', 'labeng' ); ?></span>
        <span class="lab-stat-card__value"><?php echo esc_html( $avg_rating > 0 ? number_format( $avg_rating, 1 ) . ' ★' : '—' ); ?></span>
    </div>
    <div class="lab-stat-card">
        <span class="lab-stat-card__label"><?php esc_html_e( 'Most Booked Service', 'labeng' ); ?></span>
        <span class="lab-stat-card__value lab-stat-card__value--sm"><?php echo esc_html( $most_booked ?: '—' ); ?></span>
    </div>
</div>
