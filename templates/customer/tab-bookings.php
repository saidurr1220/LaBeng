<?php
if ( ! defined( 'ABSPATH' ) ) exit;

global $wpdb;
$table       = $wpdb->prefix . 'lab_bookings';
$customer_id = get_current_user_id();
$cs          = get_option( 'lab_currency_symbol', '£' );

$bookings = $wpdb->get_results( $wpdb->prepare(
    "SELECT * FROM {$table} WHERE customer_id = %d ORDER BY booking_date DESC, booking_time DESC",
    $customer_id
) );
?>

<h2 class="lab-section-title"><?php esc_html_e( 'My Bookings', 'labeng' ); ?></h2>

<div id="lab-cust-bookings-msg" class="lab-msg" style="display:none;"></div>

<?php if ( ! empty( $bookings ) ) : ?>
<div class="lab-table-wrap">
    <table class="lab-table">
        <thead>
            <tr>
                <th><?php esc_html_e( 'Business', 'labeng' ); ?></th>
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
                $business = get_post( $b->business_id );
                $has_reviewed = Lab_Reviews::has_reviewed_booking( $b->id );
                $pay = $b->payment_status ? $b->payment_status : 'unpaid';
            ?>
            <tr data-booking-id="<?php echo esc_attr( $b->id ); ?>">
                <td><?php echo esc_html( $business ? $business->post_title : '—' ); ?></td>
                <td><?php echo esc_html( $b->service_name ); ?></td>
                <td><?php echo esc_html( date( 'M j, Y', strtotime( $b->booking_date ) ) ); ?></td>
                <td><?php echo esc_html( date( 'g:i A', strtotime( $b->booking_time ) ) ); ?></td>
                <td><span class="lab-badge lab-badge--<?php echo esc_attr( $b->status ); ?>"><?php echo esc_html( ucfirst( $b->status ) ); ?></span></td>
                <td><span class="lab-badge lab-badge--pay-<?php echo esc_attr( $pay ); ?>"><?php echo esc_html( ucfirst( $pay ) ); ?></span></td>
                <td><?php echo esc_html( $cs . number_format( $b->total_amount, 2 ) ); ?></td>
                <td>
                    <?php if ( $b->status === 'pending' ) : ?>
                        <button class="lab-btn lab-btn--sm lab-btn--danger lab-cancel-booking-btn" data-booking-id="<?php echo esc_attr( $b->id ); ?>"><?php esc_html_e( 'Cancel', 'labeng' ); ?></button>
                    <?php elseif ( $b->status === 'completed' && ! $has_reviewed ) : ?>
                        <button class="lab-btn lab-btn--sm lab-btn--primary lab-leave-review-btn"
                                data-booking-id="<?php echo esc_attr( $b->id ); ?>"
                                data-business-id="<?php echo esc_attr( $b->business_id ); ?>"
                                data-business-name="<?php echo esc_attr( $business ? $business->post_title : '' ); ?>"
                                data-service-name="<?php echo esc_attr( $b->service_name ); ?>"
                        ><?php esc_html_e( 'Leave Review', 'labeng' ); ?></button>
                    <?php elseif ( $b->status === 'completed' && $has_reviewed ) : ?>
                        <span class="lab-text-muted"><?php esc_html_e( 'Reviewed', 'labeng' ); ?></span>
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
        <p><?php esc_html_e( 'No bookings yet.', 'labeng' ); ?> <a href="<?php echo esc_url( home_url( '/businesses/' ) ); ?>"><?php esc_html_e( 'Browse businesses', 'labeng' ); ?></a> <?php esc_html_e( 'and book a service!', 'labeng' ); ?></p>
    </div>
<?php endif; ?>

<!-- Review Modal -->
<div id="lab-review-modal" class="lab-modal" style="display:none;">
    <div class="lab-modal__overlay"></div>
    <div class="lab-modal__content">
        <div class="lab-modal__header">
            <h3 class="lab-modal__title"><?php esc_html_e( 'Leave a Review', 'labeng' ); ?></h3>
            <button class="lab-modal__close" id="lab-review-modal-close">&times;</button>
        </div>
        <div class="lab-modal__body">
            <p id="lab-review-modal-info" class="lab-text-muted"></p>
            <div id="lab-review-msg" class="lab-msg" style="display:none;"></div>
            <form id="lab-review-form" class="lab-form">
                <input type="hidden" id="lab-review-booking-id" name="booking_id" />
                <input type="hidden" id="lab-review-business-id" name="business_id" />
                <div class="lab-field">
                    <label><?php esc_html_e( 'Rating', 'labeng' ); ?></label>
                    <div class="lab-star-picker" id="lab-star-picker">
                        <span class="lab-star-pick" data-rating="1">★</span>
                        <span class="lab-star-pick" data-rating="2">★</span>
                        <span class="lab-star-pick" data-rating="3">★</span>
                        <span class="lab-star-pick" data-rating="4">★</span>
                        <span class="lab-star-pick" data-rating="5">★</span>
                    </div>
                    <input type="hidden" id="lab-review-rating" name="rating" value="0" />
                </div>
                <div class="lab-field">
                    <label for="lab-review-text"><?php esc_html_e( 'Your Review', 'labeng' ); ?></label>
                    <textarea id="lab-review-text" name="review_text" rows="4" required></textarea>
                </div>
                <button type="submit" class="lab-btn lab-btn--primary lab-btn--full"><?php esc_html_e( 'Submit Review', 'labeng' ); ?></button>
            </form>
        </div>
    </div>
</div>
