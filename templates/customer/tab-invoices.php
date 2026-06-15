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

$user_info = get_userdata( $customer_id );
$display_name = $user_info ? $user_info->display_name : 'Valued Customer';
$user_email = $user_info ? $user_info->user_email : '';
?>

<h2 class="lab-section-title"><?php esc_html_e( 'My Invoices', 'labeng' ); ?></h2>

<?php if ( ! empty( $bookings ) ) : ?>
<div class="lab-table-wrap">
    <table class="lab-table">
        <thead>
            <tr>
                <th><?php esc_html_e( 'Invoice ID', 'labeng' ); ?></th>
                <th><?php esc_html_e( 'Business', 'labeng' ); ?></th>
                <th><?php esc_html_e( 'Service', 'labeng' ); ?></th>
                <th><?php esc_html_e( 'Booking Date', 'labeng' ); ?></th>
                <th><?php esc_html_e( 'Payment Status', 'labeng' ); ?></th>
                <th><?php esc_html_e( 'Amount', 'labeng' ); ?></th>
                <th><?php esc_html_e( 'Actions', 'labeng' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ( $bookings as $b ) :
                $business = get_post( $b->business_id );
                $postcode = get_post_meta( $b->business_id, '_lab_postcode', true );
                $city = get_post_meta( $b->business_id, '_lab_city', true );
                $invoice_num = 'INV-100' . $b->id;
                
                // Set payment status display
                $pay_status_label = ucfirst( $b->payment_status );
                if ( $b->payment_status === 'paid' ) {
                    $badge_class = 'lab-badge--completed';
                } elseif ( $b->payment_status === 'unpaid' ) {
                    $badge_class = 'lab-badge--pending';
                } else {
                    $badge_class = 'lab-badge--cancelled';
                }
            ?>
            <tr data-invoice-id="<?php echo esc_attr( $b->id ); ?>">
                <td><strong><?php echo esc_html( $invoice_num ); ?></strong></td>
                <td><?php echo esc_html( $business ? $business->post_title : '—' ); ?></td>
                <td><?php echo esc_html( $b->service_name ); ?></td>
                <td><?php echo esc_html( date( 'M j, Y', strtotime( $b->booking_date ) ) ); ?></td>
                <td><span class="lab-badge <?php echo esc_attr( $badge_class ); ?>"><?php echo esc_html( $pay_status_label ); ?></span></td>
                <td><?php echo esc_html( $cs . number_format( $b->total_amount, 2 ) ); ?></td>
                <td>
                    <button class="lab-btn lab-btn--sm lab-btn--primary lab-view-invoice-btn"
                            data-invoice-number="<?php echo esc_attr( $invoice_num ); ?>"
                            data-invoice-date="<?php echo esc_attr( date( 'F j, Y', strtotime( $b->created_at ) ) ); ?>"
                            data-cust-name="<?php echo esc_attr( $display_name ); ?>"
                            data-cust-email="<?php echo esc_attr( $user_email ); ?>"
                            data-biz-name="<?php echo esc_attr( $business ? $business->post_title : 'LaBeng Business' ); ?>"
                            data-biz-address="<?php echo esc_attr( ($city ? $city . ', ' : '') . $postcode ); ?>"
                            data-service-name="<?php echo esc_attr( $b->service_name ); ?>"
                            data-service-price="<?php echo esc_attr( $cs . number_format( $b->service_price, 2 ) ); ?>"
                            data-total-amount="<?php echo esc_attr( $cs . number_format( $b->total_amount, 2 ) ); ?>"
                            data-payment-method="<?php echo esc_attr( $b->payment_method ? ucfirst( $b->payment_method ) : 'Stripe Card' ); ?>"
                            data-payment-status="<?php echo esc_attr( $pay_status_label ); ?>"
                    >
                        <?php esc_html_e( 'View Invoice', 'labeng' ); ?>
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php else : ?>
    <div class="lab-empty-state">
        <p><?php esc_html_e( 'No invoices found.', 'labeng' ); ?></p>
    </div>
<?php endif; ?>

<!-- Invoice Pop-up Modal Container -->
<div id="lab-invoice-modal" class="lab-modal" style="display:none;">
    <div class="lab-modal__overlay"></div>
    <div class="lab-modal__content" id="lab-invoice-print-container" style="max-width:680px; padding:0; background:transparent; border:none;">
        <div class="lab-invoice-modal-card">

            <div class="lab-invoice-accent"></div>

            <div class="lab-invoice-modal-header">
                <div>
                    <span class="lab-invoice-modal-logo">LaBeng</span>
                    <p class="lab-invoice-modal-tagline"><?php esc_html_e( 'On-Demand Booking Network', 'labeng' ); ?></p>
                </div>
                <div class="lab-invoice-modal-header-right">
                    <div class="lab-invoice-modal-title"><?php esc_html_e( 'INVOICE', 'labeng' ); ?></div>
                    <div class="lab-invoice-modal-number" id="lab-invoice-modal-number">INV-10000</div>
                </div>
            </div>

            <div class="lab-invoice-meta">
                <div>
                    <span class="lab-invoice-label"><?php esc_html_e( 'Billed To', 'labeng' ); ?></span>
                    <p><strong id="lab-invoice-modal-cust-name">John Doe</strong></p>
                    <p id="lab-invoice-modal-cust-email">john@doe.com</p>
                </div>
                <div>
                    <span class="lab-invoice-label"><?php esc_html_e( 'From', 'labeng' ); ?></span>
                    <p><strong id="lab-invoice-modal-biz-name">Business Title</strong></p>
                    <p id="lab-invoice-modal-biz-address">City, Postcode</p>
                </div>
            </div>

            <div class="lab-invoice-info-row">
                <div>
                    <span class="lab-invoice-label"><?php esc_html_e( 'Invoice Date', 'labeng' ); ?></span>
                    <p id="lab-invoice-modal-date">June 14, 2026</p>
                </div>
                <div>
                    <span class="lab-invoice-label"><?php esc_html_e( 'Payment Method', 'labeng' ); ?></span>
                    <p id="lab-invoice-modal-payment-method">Stripe</p>
                </div>
                <div>
                    <span class="lab-invoice-label"><?php esc_html_e( 'Status', 'labeng' ); ?></span>
                    <p><span class="lab-invoice-status" id="lab-invoice-modal-payment-status">Paid</span></p>
                </div>
            </div>

            <div class="lab-invoice-items-wrap">
                <table class="lab-invoice-items">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Description', 'labeng' ); ?></th>
                            <th class="price-col" width="110"><?php esc_html_e( 'Unit Price', 'labeng' ); ?></th>
                            <th class="price-col" width="60"><?php esc_html_e( 'Qty', 'labeng' ); ?></th>
                            <th class="price-col" width="110"><?php esc_html_e( 'Total', 'labeng' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td id="lab-invoice-modal-service-name">Haircut</td>
                            <td class="price-col" id="lab-invoice-modal-service-price">£25.00</td>
                            <td class="price-col">1</td>
                            <td class="price-col" id="lab-invoice-modal-service-total">£25.00</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="lab-invoice-total-box">
                <span class="lab-invoice-total-label"><?php esc_html_e( 'Total Amount Due', 'labeng' ); ?></span>
                <span class="lab-invoice-total-amount" id="lab-invoice-modal-total">£25.00</span>
            </div>

            <div class="lab-invoice-footer">
                <p class="lab-invoice-thank-you"><?php esc_html_e( 'Thank you for booking with LaBeng', 'labeng' ); ?></p>
                <div class="lab-invoice-actions">
                    <button type="button" class="lab-btn lab-invoice-close-btn" id="lab-invoice-modal-close">
                        <?php esc_html_e( 'Close', 'labeng' ); ?>
                    </button>
                    <button type="button" class="lab-btn lab-btn--primary" id="lab-invoice-print-btn">
                        🖨️ <?php esc_html_e( 'Print', 'labeng' ); ?>
                    </button>
                </div>
            </div>

        </div>
    </div>
</div>
