<?php
if ( ! defined( 'ABSPATH' ) ) exit;

global $wpdb;
$table = $wpdb->prefix . 'lab_bookings';
$cs    = get_option( 'lab_currency_symbol', '£' );

/* Filters */
$filter_status   = isset( $_GET['status'] ) ? sanitize_text_field( $_GET['status'] ) : '';
$filter_business = isset( $_GET['business_id'] ) ? absint( $_GET['business_id'] ) : 0;
$filter_from     = isset( $_GET['date_from'] ) ? sanitize_text_field( $_GET['date_from'] ) : '';
$filter_to       = isset( $_GET['date_to'] ) ? sanitize_text_field( $_GET['date_to'] ) : '';

$where = "WHERE 1=1";
$params = array();

if ( $filter_status ) {
    $where .= " AND b.status = %s";
    $params[] = $filter_status;
}
if ( $filter_business ) {
    $where .= " AND b.business_id = %d";
    $params[] = $filter_business;
}
if ( $filter_from ) {
    $where .= " AND b.booking_date >= %s";
    $params[] = $filter_from;
}
if ( $filter_to ) {
    $where .= " AND b.booking_date <= %s";
    $params[] = $filter_to;
}

$query = "SELECT b.* FROM {$table} b {$where} ORDER BY b.created_at DESC LIMIT 200";
if ( ! empty( $params ) ) {
    $bookings = $wpdb->get_results( $wpdb->prepare( $query, ...$params ) );
} else {
    $bookings = $wpdb->get_results( $query );
}

/* Get businesses for filter dropdown */
$businesses = get_posts( array( 'post_type' => 'lab_business', 'numberposts' => -1, 'post_status' => 'any' ) );
?>

<div class="wrap">
    <h1><?php esc_html_e( 'Bookings', 'labeng' ); ?></h1>

    <!-- Filters -->
    <form method="get" style="margin:20px 0;display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
        <input type="hidden" name="page" value="labeng-bookings" />
        <div>
            <label style="display:block;font-size:12px;margin-bottom:4px;"><?php esc_html_e( 'Status', 'labeng' ); ?></label>
            <select name="status">
                <option value=""><?php esc_html_e( 'All', 'labeng' ); ?></option>
                <option value="pending" <?php selected( $filter_status, 'pending' ); ?>><?php esc_html_e( 'Pending', 'labeng' ); ?></option>
                <option value="confirmed" <?php selected( $filter_status, 'confirmed' ); ?>><?php esc_html_e( 'Confirmed', 'labeng' ); ?></option>
                <option value="completed" <?php selected( $filter_status, 'completed' ); ?>><?php esc_html_e( 'Completed', 'labeng' ); ?></option>
                <option value="cancelled" <?php selected( $filter_status, 'cancelled' ); ?>><?php esc_html_e( 'Cancelled', 'labeng' ); ?></option>
            </select>
        </div>
        <div>
            <label style="display:block;font-size:12px;margin-bottom:4px;"><?php esc_html_e( 'Business', 'labeng' ); ?></label>
            <select name="business_id">
                <option value="0"><?php esc_html_e( 'All', 'labeng' ); ?></option>
                <?php foreach ( $businesses as $biz ) : ?>
                    <option value="<?php echo esc_attr( $biz->ID ); ?>" <?php selected( $filter_business, $biz->ID ); ?>><?php echo esc_html( $biz->post_title ); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label style="display:block;font-size:12px;margin-bottom:4px;"><?php esc_html_e( 'From', 'labeng' ); ?></label>
            <input type="date" name="date_from" value="<?php echo esc_attr( $filter_from ); ?>" />
        </div>
        <div>
            <label style="display:block;font-size:12px;margin-bottom:4px;"><?php esc_html_e( 'To', 'labeng' ); ?></label>
            <input type="date" name="date_to" value="<?php echo esc_attr( $filter_to ); ?>" />
        </div>
        <div>
            <?php submit_button( __( 'Filter', 'labeng' ), 'primary', '', false ); ?>
        </div>
    </form>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th style="width:50px;"><?php esc_html_e( 'ID', 'labeng' ); ?></th>
                <th><?php esc_html_e( 'Business', 'labeng' ); ?></th>
                <th><?php esc_html_e( 'Customer', 'labeng' ); ?></th>
                <th><?php esc_html_e( 'Service', 'labeng' ); ?></th>
                <th><?php esc_html_e( 'Date', 'labeng' ); ?></th>
                <th><?php esc_html_e( 'Time', 'labeng' ); ?></th>
                <th><?php esc_html_e( 'Status', 'labeng' ); ?></th>
                <th><?php esc_html_e( 'Payment', 'labeng' ); ?></th>
                <th><?php esc_html_e( 'Amount', 'labeng' ); ?></th>
                <th><?php esc_html_e( 'Commission', 'labeng' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if ( ! empty( $bookings ) ) : ?>
                <?php foreach ( $bookings as $b ) :
                    $biz = get_post( $b->business_id );
                    $cust = get_userdata( $b->customer_id );
                ?>
                <tr>
                    <td><?php echo esc_html( $b->id ); ?></td>
                    <td><?php echo esc_html( $biz ? $biz->post_title : '#' . $b->business_id ); ?></td>
                    <td><?php echo esc_html( $cust ? $cust->display_name : '#' . $b->customer_id ); ?></td>
                    <td><?php echo esc_html( $b->service_name ); ?></td>
                    <td><?php echo esc_html( date( 'M j, Y', strtotime( $b->booking_date ) ) ); ?></td>
                    <td><?php echo esc_html( date( 'g:i A', strtotime( $b->booking_time ) ) ); ?></td>
                    <td>
                        <?php
                        $colors = array( 'pending' => '#ffc107', 'confirmed' => '#1FCFE0', 'completed' => '#198754', 'cancelled' => '#dc3545' );
                        $c = isset( $colors[ $b->status ] ) ? $colors[ $b->status ] : '#888';
                        ?>
                        <span style="display:inline-block;padding:3px 10px;border-radius:12px;font-size:12px;font-weight:600;color:#fff;background:<?php echo esc_attr( $c ); ?>;"><?php echo esc_html( ucfirst( $b->status ) ); ?></span>
                    </td>
                    <td>
                        <?php
                        $pay_colors = array( 'paid' => '#198754', 'unpaid' => '#ffc107', 'free' => '#1FCFE0', 'refunded' => '#6c757d' );
                        $pc = isset( $pay_colors[ $b->payment_status ] ) ? $pay_colors[ $b->payment_status ] : '#888';
                        ?>
                        <span style="display:inline-block;padding:3px 10px;border-radius:12px;font-size:12px;font-weight:600;color:#fff;background:<?php echo esc_attr( $pc ); ?>;"><?php echo esc_html( ucfirst( $b->payment_status ) ); ?></span>
                    </td>
                    <td><?php echo esc_html( $cs . number_format( $b->total_amount, 2 ) ); ?></td>
                    <td><?php echo esc_html( $cs . number_format( $b->commission_due, 2 ) ); ?></td>
                </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr><td colspan="10"><?php esc_html_e( 'No bookings found.', 'labeng' ); ?></td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
