<?php
if ( ! defined( 'ABSPATH' ) ) exit;

global $wpdb;
$table = $wpdb->prefix . 'lab_bookings';
$cs    = get_option( 'lab_currency_symbol', '£' );

/* Today's bookings */
$today = current_time( 'Y-m-d' );
$today_count = $wpdb->get_var( $wpdb->prepare(
    "SELECT COUNT(*) FROM {$table} WHERE business_id = %d AND booking_date = %s",
    $business_id, $today
) );

/* This week's bookings */
$week_start = date( 'Y-m-d', strtotime( 'monday this week', strtotime( $today ) ) );
$week_end   = date( 'Y-m-d', strtotime( 'sunday this week', strtotime( $today ) ) );
$week_count = $wpdb->get_var( $wpdb->prepare(
    "SELECT COUNT(*) FROM {$table} WHERE business_id = %d AND booking_date BETWEEN %s AND %s",
    $business_id, $week_start, $week_end
) );

/* Revenue, commission and net earnings (paid + completed bookings) */
$earnings         = Lab_Bookings::get_earnings( $business_id );
$total_revenue    = $earnings['revenue'];
$total_commission = $earnings['commission'];
$net_earnings     = $earnings['net'];
$comm_eff         = Lab_Commissions::get_effective( $business_id );
$comm_label   = $comm_eff['type'] === 'percentage'
    ? $comm_eff['value'] . '%'
    : $cs . number_format( $comm_eff['value'], 2 );

/* Average rating */
$avg_rating = floatval( get_post_meta( $business_id, '_lab_rating_avg', true ) );

/* Recent bookings */
$recent = $wpdb->get_results( $wpdb->prepare(
    "SELECT * FROM {$table} WHERE business_id = %d ORDER BY created_at DESC LIMIT 5",
    $business_id
) );
?>

<h2 class="lab-section-title"><?php esc_html_e( 'Overview', 'labeng' ); ?></h2>

<div class="lab-stats-grid">
    <div class="lab-stat-card">
        <span class="lab-stat-card__label"><?php esc_html_e( "Today's Bookings", 'labeng' ); ?></span>
        <span class="lab-stat-card__value"><?php echo esc_html( $today_count ); ?></span>
    </div>
    <div class="lab-stat-card">
        <span class="lab-stat-card__label"><?php esc_html_e( "This Week's Bookings", 'labeng' ); ?></span>
        <span class="lab-stat-card__value"><?php echo esc_html( $week_count ); ?></span>
    </div>
    <div class="lab-stat-card">
        <span class="lab-stat-card__label"><?php esc_html_e( 'Total Revenue', 'labeng' ); ?></span>
        <span class="lab-stat-card__value"><?php echo esc_html( $cs . number_format( $total_revenue, 2 ) ); ?></span>
    </div>
    <div class="lab-stat-card">
        <span class="lab-stat-card__label"><?php esc_html_e( 'Average Rating', 'labeng' ); ?></span>
        <span class="lab-stat-card__value"><?php echo esc_html( $avg_rating > 0 ? number_format( $avg_rating, 1 ) . ' ★' : '—' ); ?></span>
    </div>
    <div class="lab-stat-card lab-stat-card--accent">
        <span class="lab-stat-card__label"><?php esc_html_e( 'Net Earnings', 'labeng' ); ?></span>
        <span class="lab-stat-card__value"><?php echo esc_html( $cs . number_format( $net_earnings, 2 ) ); ?></span>
        <span class="lab-stat-card__hint"><?php esc_html_e( 'After platform commission', 'labeng' ); ?></span>
    </div>
    <div class="lab-stat-card">
        <span class="lab-stat-card__label"><?php esc_html_e( 'Commission Owed', 'labeng' ); ?></span>
        <span class="lab-stat-card__value"><?php echo esc_html( $cs . number_format( $total_commission, 2 ) ); ?></span>
        <span class="lab-stat-card__hint"><?php echo esc_html( sprintf( __( 'Rate: %s', 'labeng' ), $comm_label ) ); ?></span>
    </div>
</div>

<?php if ( ! empty( $recent ) ) : ?>
<h3 class="lab-section-subtitle"><?php esc_html_e( 'Recent Bookings', 'labeng' ); ?></h3>
<div class="lab-table-wrap">
    <table class="lab-table">
        <thead>
            <tr>
                <th><?php esc_html_e( 'Customer', 'labeng' ); ?></th>
                <th><?php esc_html_e( 'Service', 'labeng' ); ?></th>
                <th><?php esc_html_e( 'Date', 'labeng' ); ?></th>
                <th><?php esc_html_e( 'Time', 'labeng' ); ?></th>
                <th><?php esc_html_e( 'Status', 'labeng' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ( $recent as $b ) :
                $customer = get_userdata( $b->customer_id );
            ?>
            <tr>
                <td><?php echo esc_html( $customer ? $customer->display_name : 'Guest' ); ?></td>
                <td><?php echo esc_html( $b->service_name ); ?></td>
                <td><?php echo esc_html( date( 'M j, Y', strtotime( $b->booking_date ) ) ); ?></td>
                <td><?php echo esc_html( date( 'g:i A', strtotime( $b->booking_time ) ) ); ?></td>
                <td><span class="lab-badge lab-badge--<?php echo esc_attr( $b->status ); ?>"><?php echo esc_html( ucfirst( $b->status ) ); ?></span></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php else : ?>
    <div class="lab-empty-state">
        <p><?php esc_html_e( 'No bookings yet. Once customers start booking your services, they will appear here.', 'labeng' ); ?></p>
    </div>
<?php endif; ?>

<div class="lab-quick-links">
    <h3 class="lab-section-subtitle"><?php esc_html_e( 'Quick Actions', 'labeng' ); ?></h3>
    <div class="lab-quick-links__grid">
        <a href="#my-business" class="lab-quick-link" data-tab="my-business">🏢 <?php esc_html_e( 'Edit Profile', 'labeng' ); ?></a>
        <a href="#services" class="lab-quick-link" data-tab="services">🛠️ <?php esc_html_e( 'Manage Services', 'labeng' ); ?></a>
        <a href="#availability" class="lab-quick-link" data-tab="availability">🕐 <?php esc_html_e( 'Set Hours', 'labeng' ); ?></a>
        <a href="#deals" class="lab-quick-link" data-tab="deals">🏷️ <?php esc_html_e( 'Create Deal', 'labeng' ); ?></a>
    </div>
</div>
