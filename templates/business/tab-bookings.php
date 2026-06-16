<?php
if ( ! defined( 'ABSPATH' ) ) exit;

global $wpdb;
$table = $wpdb->prefix . 'lab_bookings';
$cs    = get_option( 'lab_currency_symbol', '£' );

/* Get all bookings for this business */
$bookings = $wpdb->get_results( $wpdb->prepare(
    "SELECT * FROM {$table} WHERE business_id = %d ORDER BY booking_date DESC, booking_time DESC",
    $business_id
) );
?>

<h2 class="lab-section-title"><?php esc_html_e( 'Bookings', 'labeng' ); ?></h2>

<div id="lab-bookings-msg" class="lab-msg" style="display:none;"></div>

<!-- Status filter -->
<div class="lab-filter-bar">
    <label for="lab-booking-filter"><?php esc_html_e( 'Filter by status:', 'labeng' ); ?></label>
    <select id="lab-booking-filter" class="lab-select">
        <option value="all"><?php esc_html_e( 'All', 'labeng' ); ?></option>
        <option value="pending"><?php esc_html_e( 'Pending', 'labeng' ); ?></option>
        <option value="confirmed"><?php esc_html_e( 'Confirmed', 'labeng' ); ?></option>
        <option value="completed"><?php esc_html_e( 'Completed', 'labeng' ); ?></option>
        <option value="cancelled"><?php esc_html_e( 'Cancelled', 'labeng' ); ?></option>
    </select>
</div>

<?php if ( ! empty( $bookings ) ) : ?>
<div class="lab-table-wrap">
    <table class="lab-table" id="lab-bookings-table">
        <thead>
            <tr>
                <th>#</th>
                <th><?php esc_html_e( 'Customer', 'labeng' ); ?></th>
                <th><?php esc_html_e( 'Service', 'labeng' ); ?></th>
                <th><?php esc_html_e( 'Date', 'labeng' ); ?></th>
                <th><?php esc_html_e( 'Time', 'labeng' ); ?></th>
                <th><?php esc_html_e( 'Status', 'labeng' ); ?></th>
                <th><?php esc_html_e( 'Payment', 'labeng' ); ?></th>
                <th><?php esc_html_e( 'Amount', 'labeng' ); ?></th>
                <th><?php esc_html_e( 'Actions', 'labeng' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ( $bookings as $b ) :
                $customer = get_userdata( $b->customer_id );
                $pay = $b->payment_status ? $b->payment_status : 'unpaid';
            ?>
            <tr class="lab-booking-row" data-status="<?php echo esc_attr( $b->status ); ?>" data-booking-id="<?php echo esc_attr( $b->id ); ?>">
                <td><?php echo esc_html( $b->id ); ?></td>
                <td><?php echo esc_html( $customer ? $customer->display_name : 'Guest' ); ?></td>
                <td><?php echo esc_html( $b->service_name ); ?></td>
                <td><?php echo esc_html( date( 'M j, Y', strtotime( $b->booking_date ) ) ); ?></td>
                <td><?php echo esc_html( date( 'g:i A', strtotime( $b->booking_time ) ) ); ?></td>
                <td><span class="lab-badge lab-badge--<?php echo esc_attr( $b->status ); ?>" id="lab-badge-<?php echo esc_attr( $b->id ); ?>"><?php echo esc_html( ucfirst( $b->status ) ); ?></span></td>
                <td><span class="lab-badge lab-badge--pay-<?php echo esc_attr( $pay ); ?>"><?php echo esc_html( ucfirst( $pay ) ); ?></span></td>
                <td><?php echo esc_html( $cs . number_format( $b->total_amount, 2 ) ); ?></td>
                <td class="lab-actions" id="lab-actions-<?php echo esc_attr( $b->id ); ?>">
                    <?php if ( $b->status === 'pending' ) : ?>
                        <button class="lab-btn lab-btn--sm lab-btn--success lab-status-btn" data-booking-id="<?php echo esc_attr( $b->id ); ?>" data-status="confirmed"><?php esc_html_e( 'Confirm', 'labeng' ); ?></button>
                        <button class="lab-btn lab-btn--sm lab-btn--danger lab-status-btn" data-booking-id="<?php echo esc_attr( $b->id ); ?>" data-status="cancelled"><?php esc_html_e( 'Cancel', 'labeng' ); ?></button>
                    <?php elseif ( $b->status === 'confirmed' ) : ?>
                        <button class="lab-btn lab-btn--sm lab-btn--success lab-status-btn" data-booking-id="<?php echo esc_attr( $b->id ); ?>" data-status="completed"><?php esc_html_e( 'Complete', 'labeng' ); ?></button>
                        <button class="lab-btn lab-btn--sm lab-btn--danger lab-status-btn" data-booking-id="<?php echo esc_attr( $b->id ); ?>" data-status="cancelled"><?php esc_html_e( 'Cancel', 'labeng' ); ?></button>
                    <?php else : ?>
                        <span class="lab-text-muted">—</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php else : ?>
    <div class="lab-empty-state">
        <p><?php esc_html_e( 'No bookings yet.', 'labeng' ); ?></p>
    </div>
<?php endif; ?>
