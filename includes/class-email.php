<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class Lab_Email
 * All platform emails — dark-themed HTML emails via wp_mail().
 */
class Lab_Email {

    public static function init() {
        add_filter( 'wp_mail_content_type', array( __CLASS__, 'set_html_content_type' ) );
    }

    /**
     * Set content type to HTML.
     */
    public static function set_html_content_type() {
        return 'text/html';
    }

    /**
     * Wraps wp_mail and logs it to the database.
     */
    private static function send( $to, $subject, $message ) {
        global $wpdb;
        $status = wp_mail( $to, $subject, $message ) ? 'sent' : 'failed';
        $wpdb->insert(
            $wpdb->prefix . 'lab_email_logs',
            array(
                'recipient' => $to,
                'subject'   => $subject,
                'message'   => $message,
                'status'    => $status,
                'sent_at'   => current_time( 'mysql' )
            ),
            array( '%s', '%s', '%s', '%s', '%s' )
        );
        return $status === 'sent';
    }

    /**
     * Base email template wrapper.
     */
    private static function wrap( $title, $body ) {
        $site_name = get_bloginfo( 'name' );
        $year      = date( 'Y' );
        return '<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="color-scheme" content="dark">
<link href="https://fonts.googleapis.com/css2?family=Great+Vibes&display=swap" rel="stylesheet">
</head>
<body style="margin:0;padding:0;background:#0a0a0b;font-family:\'Segoe UI\',Roboto,Arial,Helvetica,sans-serif;-webkit-font-smoothing:antialiased;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#0a0a0b;padding:40px 16px;">
<tr><td align="center">
<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#141417;border:1px solid rgba(255,255,255,0.08);border-radius:18px;overflow:hidden;">
    <tr><td style="height:4px;background:linear-gradient(90deg,#1FCFE0 0%,#5FE0EC 100%);font-size:0;line-height:0;">&nbsp;</td></tr>
    <tr><td align="center" style="padding:34px 32px 6px;">
        <div style="font-family:\'Great Vibes\',\'Brush Script MT\',cursive;font-size:48px;line-height:1;color:#ffffff;">LaBeng</div>
        <div style="color:#6b6b7a;font-size:11px;letter-spacing:2px;text-transform:uppercase;margin-top:8px;">On-Demand Booking Network</div>
    </td></tr>
    <tr><td style="padding:20px 36px 0;">
        <h2 style="color:#ffffff;margin:0;font-size:21px;font-weight:600;text-align:center;">' . esc_html( $title ) . '</h2>
    </td></tr>
    <tr><td style="padding:18px 36px 36px;">
        <div style="color:#c7c7cf;font-size:15px;line-height:1.7;">' . $body . '</div>
    </td></tr>
    <tr><td style="padding:22px 32px;background:#0e0e10;border-top:1px solid rgba(255,255,255,0.06);text-align:center;">
        <p style="margin:0 0 4px;color:#7a7a85;font-size:12px;">This email was sent by ' . esc_html( $site_name ) . '.</p>
        <p style="margin:0;color:#4a4a52;font-size:11px;">&copy; ' . $year . ' ' . esc_html( $site_name ) . '. All rights reserved.</p>
    </td></tr>
</table>
</td></tr>
</table>
</body>
</html>';
    }

    /**
     * Helper to build a clean, aligned label/value detail row.
     */
    private static function row( $label, $value ) {
        return '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-bottom:1px solid rgba(255,255,255,0.06);">'
             . '<tr>'
             . '<td style="padding:11px 0;color:#8a8a93;font-size:13px;vertical-align:top;">' . esc_html( $label ) . '</td>'
             . '<td style="padding:11px 0;color:#ffffff;font-size:14px;font-weight:500;text-align:right;vertical-align:top;">' . esc_html( $value ) . '</td>'
             . '</tr>'
             . '</table>';
    }

    /**
     * 1. Booking confirmation to customer.
     */
    public static function lab_email_booking_confirmation( $booking_id ) {
        $booking = Lab_Bookings::get_booking( $booking_id );
        if ( ! $booking ) return;

        $customer = get_userdata( $booking->customer_id );
        $business = get_post( $booking->business_id );
        $cs       = get_option( 'lab_currency_symbol', '£' );
        $address  = get_post_meta( $booking->business_id, '_lab_address', true );

        $invoice_id = 'INV-100' . $booking_id;
        $service_name = $booking->service_name;
        $amount = number_format( $booking->total_amount, 2 );
        $pay_status = ucfirst( $booking->payment_status );
        $pay_method = $booking->payment_method ? ucfirst( $booking->payment_method ) : 'Card (Stripe)';

        $body = self::row( 'Booking ID', '#' . $booking_id )
              . self::row( 'Business', $business ? $business->post_title : '' )
              . self::row( 'Date', date( 'F j, Y', strtotime( $booking->booking_date ) ) )
              . self::row( 'Time', date( 'g:i A', strtotime( $booking->booking_time ) ) )
              . ( $address ? self::row( 'Address', $address ) : '' )
              . '<div style="margin:26px 0 16px; padding:22px; background:#1c1c22; border-radius:12px; border:1px solid rgba(255,255,255,0.08);">'
              . '<h3 style="color:#ffffff; margin:0 0 14px 0; border-bottom:1px solid rgba(255,255,255,0.08); padding-bottom:10px; font-size:13px; letter-spacing:1px; text-transform:uppercase;">Invoice ' . esc_html($invoice_id) . '</h3>'
              . '<table width="100%" cellpadding="0" cellspacing="0" style="color:#c7c7cf; font-size:14px; border-collapse:collapse;">'
              . '<thead>'
              . '<tr style="border-bottom:1px solid rgba(255,255,255,0.08);">'
              . '<th align="left" style="color:#8a8a93; font-weight:600; font-size:11px; text-transform:uppercase; letter-spacing:0.5px; padding-bottom:8px;">Description</th>'
              . '<th align="right" style="color:#8a8a93; font-weight:600; font-size:11px; text-transform:uppercase; letter-spacing:0.5px; padding-bottom:8px;">Qty</th>'
              . '<th align="right" style="color:#8a8a93; font-weight:600; font-size:11px; text-transform:uppercase; letter-spacing:0.5px; padding-bottom:8px;">Amount</th>'
              . '</tr>'
              . '</thead>'
              . '<tbody>'
              . '<tr style="border-bottom:1px solid rgba(255,255,255,0.05);">'
              . '<td align="left" style="padding:12px 0;color:#ffffff;">' . esc_html($service_name) . '</td>'
              . '<td align="right" style="padding:12px 0;">1</td>'
              . '<td align="right" style="padding:12px 0;color:#ffffff;">' . esc_html($cs . $amount) . '</td>'
              . '</tr>'
              . '<tr>'
              . '<td colspan="2" align="right" style="padding:14px 0 4px; font-weight:600; color:#ffffff;">Total ' . ( $booking->payment_status === 'paid' ? 'Paid' : 'Due' ) . '</td>'
              . '<td align="right" style="padding:14px 0 4px; color:#5FE0EC; font-weight:bold; font-size:17px;">' . esc_html($cs . $amount) . '</td>'
              . '</tr>'
              . '<tr>'
              . '<td colspan="2" align="right" style="padding:4px 0; font-weight:600;">Payment Status</td>'
              . '<td align="right" style="padding:4px 0; color:' . ( $booking->payment_status === 'paid' ? '#34d399' : '#f59e0b' ) . '; font-weight:bold;">' . esc_html($pay_status) . '</td>'
              . '</tr>'
              . '<tr>'
              . '<td colspan="2" align="right" style="padding:4px 0; font-weight:600;">Method</td>'
              . '<td align="right" style="padding:4px 0; color:#a1a1aa;">' . esc_html($pay_method) . '</td>'
              . '</tr>'
              . '</tbody>'
              . '</table>'
              . '</div>'
              . '<p style="margin:20px 0 0;color:#7a7a85;font-size:13px;text-align:center;">Thank you for your booking. A copy of this invoice is saved to your customer dashboard.</p>';

        $to      = $customer ? $customer->user_email : '';
        $subject = 'Invoice & Booking Confirmation – ' . ( $business ? $business->post_title : 'Labeng' );

        if ( $to ) {
            self::send( $to, $subject, self::wrap( 'Booking Confirmation & Invoice', $body ) );
        }
    }

    /**
     * 2. Booking notification to business owner.
     */
    public static function lab_email_booking_to_business( $booking_id ) {
        $booking = Lab_Bookings::get_booking( $booking_id );
        if ( ! $booking ) return;

        $customer = get_userdata( $booking->customer_id );
        $owner_id = get_post_meta( $booking->business_id, '_lab_owner_id', true );
        $owner    = get_userdata( $owner_id );
        $cs       = get_option( 'lab_currency_symbol', '£' );

        $body = self::row( 'Customer', $customer ? $customer->display_name : 'Guest' )
              . self::row( 'Service', $booking->service_name )
              . self::row( 'Date', date( 'F j, Y', strtotime( $booking->booking_date ) ) )
              . self::row( 'Time', date( 'g:i A', strtotime( $booking->booking_time ) ) )
              . self::row( 'Amount', $cs . number_format( $booking->total_amount, 2 ) )
              . ( $booking->notes ? self::row( 'Notes', $booking->notes ) : '' )
              . '<p style="margin:20px 0 0;"><a href="' . esc_url( home_url( '/business-dashboard/' ) ) . '" style="display:inline-block;padding:12px 24px;background:#1FCFE0;color:#ffffff;text-decoration:none;border-radius:8px;font-weight:600;">View in Dashboard</a></p>';

        $to      = $owner ? $owner->user_email : '';
        $subject = 'New Booking – ' . $booking->service_name;

        if ( $to ) {
            self::send( $to, $subject, self::wrap( 'New Booking Received', $body ) );
        }
    }

    /**
     * 3. Booking status change to customer.
     */
    public static function lab_email_booking_status_change( $booking_id, $new_status ) {
        $booking  = Lab_Bookings::get_booking( $booking_id );
        if ( ! $booking ) return;

        $customer = get_userdata( $booking->customer_id );
        $business = get_post( $booking->business_id );

        $status_labels = array(
            'confirmed' => 'Confirmed',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
        );
        $label = isset( $status_labels[ $new_status ] ) ? $status_labels[ $new_status ] : ucfirst( $new_status );

        $status_colors = array(
            'confirmed' => '#1FCFE0',
            'completed' => '#198754',
            'cancelled' => '#dc3545',
        );
        $color = isset( $status_colors[ $new_status ] ) ? $status_colors[ $new_status ] : '#888888';

        $body = '<p style="margin:0 0 16px;"><span style="display:inline-block;padding:6px 16px;background:' . esc_attr( $color ) . ';color:#fff;border-radius:20px;font-weight:600;font-size:14px;">' . esc_html( $label ) . '</span></p>'
              . self::row( 'Booking ID', '#' . $booking_id )
              . self::row( 'Business', $business ? $business->post_title : '' )
              . self::row( 'Service', $booking->service_name )
              . self::row( 'Date', date( 'F j, Y', strtotime( $booking->booking_date ) ) )
              . self::row( 'Time', date( 'g:i A', strtotime( $booking->booking_time ) ) );

        $to      = $customer ? $customer->user_email : '';
        $subject = 'Booking Update: ' . $label . ' – ' . ( $business ? $business->post_title : 'Labeng' );

        if ( $to ) {
            self::send( $to, $subject, self::wrap( 'Booking Status Update', $body ) );
        }
    }

    /**
     * 4. New business registration notification to admin.
     */
    public static function lab_email_business_pending_admin( $post_id, $biz_name, $owner_name, $email ) {
        $admin_email = get_option( 'admin_email' );
        $review_url  = admin_url( 'post.php?post=' . $post_id . '&action=edit' );

        $body = self::row( 'Business Name', $biz_name )
              . self::row( 'Owner', $owner_name )
              . self::row( 'Email', $email )
              . '<p style="margin:20px 0 0;"><a href="' . esc_url( $review_url ) . '" style="display:inline-block;padding:12px 24px;background:#1FCFE0;color:#ffffff;text-decoration:none;border-radius:8px;font-weight:600;">Review Application</a></p>';

        self::send( $admin_email, 'New Business Registration: ' . $biz_name, self::wrap( 'New Business Registration', $body ) );
    }

    /**
     * 5. Business approved notification to owner.
     */
    public static function lab_email_business_approved( $post_id ) {
        $owner_id = get_post_meta( $post_id, '_lab_owner_id', true );
        $owner    = get_userdata( $owner_id );
        $business = get_post( $post_id );

        if ( ! $owner || ! $business ) return;

        $body = '<p style="color:#cccccc;">Congratulations! Your business <strong style="color:#ffffff;">' . esc_html( $business->post_title ) . '</strong> has been approved and is now live on Labeng.</p>'
              . '<p style="color:#cccccc;">Here are some next steps to get the most out of your listing:</p>'
              . '<ul style="color:#cccccc;padding-left:20px;">'
              . '<li>Complete your business profile with photos and details</li>'
              . '<li>Add your services with pricing</li>'
              . '<li>Set your availability hours</li>'
              . '<li>Create deals to attract customers</li>'
              . '</ul>'
              . '<p style="margin:20px 0 0;"><a href="' . esc_url( home_url( '/business-dashboard/' ) ) . '" style="display:inline-block;padding:12px 24px;background:#198754;color:#ffffff;text-decoration:none;border-radius:8px;font-weight:600;">Go to Dashboard</a></p>';

        self::send( $owner->user_email, 'Your Labeng Listing is Live!', self::wrap( 'Your Listing is Live!', $body ) );
    }
}
