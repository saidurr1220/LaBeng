<?php
if ( ! defined( 'ABSPATH' ) ) exit;

if ( isset( $_POST['lab_payment_nonce'] ) && wp_verify_nonce( $_POST['lab_payment_nonce'], 'lab_save_payment_settings' ) ) {
    update_option( 'lab_payment_mode',          sanitize_text_field( $_POST['lab_payment_mode'] ) );
    update_option( 'lab_stripe_publishable',    sanitize_text_field( $_POST['lab_stripe_publishable'] ) );
    update_option( 'lab_stripe_secret',         sanitize_text_field( $_POST['lab_stripe_secret'] ) );
    update_option( 'lab_stripe_webhook_secret', sanitize_text_field( $_POST['lab_stripe_webhook_secret'] ) );
    update_option( 'lab_paypal_client_id',      sanitize_text_field( $_POST['lab_paypal_client_id'] ) );
    update_option( 'lab_paypal_secret',         sanitize_text_field( $_POST['lab_paypal_secret'] ) );
    echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Payment settings saved.', 'labeng' ) . '</p></div>';
}

$mode            = get_option( 'lab_payment_mode', 'test' );
$stripe_pub      = get_option( 'lab_stripe_publishable', '' );
$stripe_sec      = get_option( 'lab_stripe_secret', '' );
$stripe_webhook  = get_option( 'lab_stripe_webhook_secret', '' );
$paypal_client   = get_option( 'lab_paypal_client_id', '' );
$paypal_secret   = get_option( 'lab_paypal_secret', '' );

/* Quick gateway status check */
$stripe_active = ! empty( $stripe_pub ) && ! empty( $stripe_sec );
$paypal_active = ! empty( $paypal_client ) && ! empty( $paypal_secret );

function lab_payment_status_badge( $active ) {
    if ( $active ) {
        echo '<span style="display:inline-flex;align-items:center;gap:5px;background:#0a2a0a;color:#4caf50;border:1px solid #1a6b1a;padding:3px 10px;border-radius:20px;font-size:12px;">&#10003; Active</span>';
    } else {
        echo '<span style="display:inline-flex;align-items:center;gap:5px;background:#2a1010;color:#f66;border:1px solid #6b1a1a;padding:3px 10px;border-radius:20px;font-size:12px;">&#9679; Not configured</span>';
    }
}
?>
<div class="wrap">
    <h1><?php esc_html_e( 'Payment Settings', 'labeng' ); ?></h1>
    <p style="color:#aaa;">Configure payment gateways so customers can pay securely at checkout.</p>

    <!-- Gateway Status -->
    <div style="display:flex;gap:16px;flex-wrap:wrap;margin:20px 0;">
        <div style="background:#1a1a2e;padding:16px 24px;border-radius:10px;min-width:200px;">
            <div style="font-size:12px;color:#888;margin-bottom:6px;">Stripe</div>
            <?php lab_payment_status_badge( $stripe_active ); ?>
        </div>
        <div style="background:#1a1a2e;padding:16px 24px;border-radius:10px;min-width:200px;">
            <div style="font-size:12px;color:#888;margin-bottom:6px;">PayPal</div>
            <?php lab_payment_status_badge( $paypal_active ); ?>
        </div>
        <div style="background:#1a1a2e;padding:16px 24px;border-radius:10px;min-width:200px;">
            <div style="font-size:12px;color:#888;margin-bottom:6px;">Current Mode</div>
            <?php if ( $mode === 'live' ) : ?>
                <span style="display:inline-flex;align-items:center;gap:5px;background:#0a2a0a;color:#4caf50;border:1px solid #1a6b1a;padding:3px 10px;border-radius:20px;font-size:12px;">&#9679; Live</span>
            <?php else : ?>
                <span style="display:inline-flex;align-items:center;gap:5px;background:#1a1a2e;color:#ffc107;border:1px solid #6b5400;padding:3px 10px;border-radius:20px;font-size:12px;">&#9679; Test</span>
            <?php endif; ?>
        </div>
    </div>

    <form method="post">
        <?php wp_nonce_field( 'lab_save_payment_settings', 'lab_payment_nonce' ); ?>

        <!-- Mode -->
        <h2><?php esc_html_e( 'Payment Mode', 'labeng' ); ?></h2>
        <p style="color:#aaa; margin-top:0;">Use <strong>Test</strong> mode while building. Switch to <strong>Live</strong> only when ready to accept real money.</p>
        <table class="form-table">
            <tr>
                <th scope="row"><?php esc_html_e( 'Mode', 'labeng' ); ?></th>
                <td>
                    <label style="margin-right:20px;">
                        <input type="radio" name="lab_payment_mode" value="test" <?php checked( $mode, 'test' ); ?> />
                        <?php esc_html_e( 'Test (sandbox)', 'labeng' ); ?>
                    </label>
                    <label>
                        <input type="radio" name="lab_payment_mode" value="live" <?php checked( $mode, 'live' ); ?> />
                        <?php esc_html_e( 'Live (real payments)', 'labeng' ); ?>
                    </label>
                    <p class="description"><?php esc_html_e( 'Use your test keys when in Test mode, and live keys when in Live mode.', 'labeng' ); ?></p>
                </td>
            </tr>
        </table>

        <!-- Stripe -->
        <h2>
            <svg style="vertical-align:middle;margin-right:6px;" width="20" height="20" viewBox="0 0 24 24" fill="#635bff"><path d="M13.976 9.15c-2.172-.806-3.356-1.426-3.356-2.409 0-.831.683-1.305 1.901-1.305 2.227 0 4.515.858 6.09 1.631l.89-5.494C18.252.975 15.697 0 12.165 0 9.667 0 7.589.654 6.104 1.872 4.56 3.147 3.757 4.992 3.757 7.218c0 4.039 2.467 5.76 6.476 7.219 2.585.92 3.445 1.574 3.445 2.583 0 .98-.84 1.545-2.354 1.545-1.875 0-4.965-.921-6.99-2.109l-.9 5.555C5.175 22.99 8.385 24 11.714 24c2.641 0 4.843-.624 6.328-1.813 1.664-1.305 2.525-3.236 2.525-5.732 0-4.128-2.524-5.851-6.591-7.305z"/></svg>
            <?php esc_html_e( 'Stripe', 'labeng' ); ?>
        </h2>
        <p style="color:#aaa;margin-top:0;">Get your keys from <a href="https://dashboard.stripe.com/apikeys" target="_blank" rel="noopener">dashboard.stripe.com/apikeys</a>.</p>
        <table class="form-table">
            <tr>
                <th scope="row"><label for="lab_stripe_publishable"><?php esc_html_e( 'Publishable Key', 'labeng' ); ?></label></th>
                <td>
                    <input type="text" id="lab_stripe_publishable" name="lab_stripe_publishable"
                           value="<?php echo esc_attr( $stripe_pub ); ?>" class="regular-text"
                           placeholder="pk_test_..." autocomplete="off" />
                    <p class="description"><?php esc_html_e( 'Starts with pk_test_ (test) or pk_live_ (live).', 'labeng' ); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="lab_stripe_secret"><?php esc_html_e( 'Secret Key', 'labeng' ); ?></label></th>
                <td>
                    <input type="password" id="lab_stripe_secret" name="lab_stripe_secret"
                           value="<?php echo esc_attr( $stripe_sec ); ?>" class="regular-text"
                           placeholder="sk_test_..." autocomplete="new-password" />
                    <p class="description"><?php esc_html_e( 'Starts with sk_test_ (test) or sk_live_ (live). Never share this key.', 'labeng' ); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="lab_stripe_webhook_secret"><?php esc_html_e( 'Webhook Secret', 'labeng' ); ?></label></th>
                <td>
                    <input type="password" id="lab_stripe_webhook_secret" name="lab_stripe_webhook_secret"
                           value="<?php echo esc_attr( $stripe_webhook ); ?>" class="regular-text"
                           placeholder="whsec_..." autocomplete="new-password" />
                    <p class="description">
                        <?php esc_html_e( 'From Stripe Dashboard → Developers → Webhooks. Used to verify webhook events are from Stripe.', 'labeng' ); ?><br>
                        <strong><?php esc_html_e( 'Required events to enable:', 'labeng' ); ?></strong> <code>checkout.session.completed</code>, <code>payment_intent.succeeded</code><br>
                        <?php
                        $webhook_url = admin_url( 'admin-ajax.php?action=lab_stripe_webhook' );
                        echo esc_html__( 'Your webhook URL: ', 'labeng' );
                        echo '<code>' . esc_html( $webhook_url ) . '</code>';
                        ?>
                    </p>
                </td>
            </tr>
        </table>

        <!-- PayPal -->
        <h2>
            <svg style="vertical-align:middle;margin-right:6px;" width="20" height="20" viewBox="0 0 24 24" fill="#003087"><path d="M7.076 21.337H2.47a.641.641 0 0 1-.633-.74L4.944.901C5.026.382 5.474 0 5.998 0h7.46c2.57 0 4.578.543 5.69 1.81 1.01 1.15 1.304 2.42 1.012 4.287-.023.143-.047.288-.077.437-.983 5.05-4.349 6.797-8.647 6.797h-2.19c-.524 0-.968.382-1.05.9l-1.12 7.106zm14.146-14.42a3.35 3.35 0 0 0-.607-.541c-.013.076-.026.175-.041.254-.59 3.025-2.566 6.492-8.487 6.492H9.898c-.524 0-.968.382-1.05.9l-1.504 9.533a.641.641 0 0 0 .633.74h3.834c.524 0 .968-.382 1.05-.9l.043-.274.84-5.332.054-.294a1.06 1.06 0 0 1 1.05-.9h.661c4.298 0 7.664-1.746 8.647-6.797.413-2.12.198-3.89-.914-5.13a4.15 4.15 0 0 0-.02-.02z"/></svg>
            <?php esc_html_e( 'PayPal', 'labeng' ); ?>
        </h2>
        <p style="color:#aaa;margin-top:0;">Get your credentials from <a href="https://developer.paypal.com/developer/applications" target="_blank" rel="noopener">developer.paypal.com</a>.</p>
        <table class="form-table">
            <tr>
                <th scope="row"><label for="lab_paypal_client_id"><?php esc_html_e( 'Client ID', 'labeng' ); ?></label></th>
                <td>
                    <input type="text" id="lab_paypal_client_id" name="lab_paypal_client_id"
                           value="<?php echo esc_attr( $paypal_client ); ?>" class="regular-text"
                           placeholder="AaBb..." autocomplete="off" />
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="lab_paypal_secret"><?php esc_html_e( 'Secret Key', 'labeng' ); ?></label></th>
                <td>
                    <input type="password" id="lab_paypal_secret" name="lab_paypal_secret"
                           value="<?php echo esc_attr( $paypal_secret ); ?>" class="regular-text"
                           autocomplete="new-password" />
                    <p class="description"><?php esc_html_e( 'Required for server-side order capture. Never expose this publicly.', 'labeng' ); ?></p>
                </td>
            </tr>
        </table>

        <?php submit_button( __( 'Save Payment Settings', 'labeng' ) ); ?>
    </form>

    <div style="background:#1a1a2e;border-radius:10px;padding:20px 24px;margin-top:30px;">
        <h3 style="margin-top:0;"><?php esc_html_e( 'How payments work', 'labeng' ); ?></h3>
        <ol style="color:#aaa;line-height:1.8;margin:0;padding-left:1.25rem;">
            <li><?php esc_html_e( 'Customer selects a service and time slot on the business page.', 'labeng' ); ?></li>
            <li><?php esc_html_e( 'A Stripe PaymentIntent is created server-side for the exact service price.', 'labeng' ); ?></li>
            <li><?php esc_html_e( 'The Stripe Payment Element renders in the checkout step — no card data ever touches your server.', 'labeng' ); ?></li>
            <li><?php esc_html_e( 'After Stripe confirms payment, the booking is saved and emails are sent to both the customer and the business.', 'labeng' ); ?></li>
            <li><?php esc_html_e( 'Commission is calculated and recorded automatically on the booking record.', 'labeng' ); ?></li>
        </ol>
    </div>

    <!-- Payment Logs -->
    <div style="margin-top:40px;">
        <h2><?php esc_html_e( 'Payment Logs', 'labeng' ); ?></h2>
        <p style="color:#aaa;"><?php esc_html_e( 'Recent payment webhook events and errors.', 'labeng' ); ?></p>
        <div style="background:#fff;border:1px solid #ccd0d4;border-radius:4px;padding:0;">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 150px;"><?php esc_html_e( 'Date / Time', 'labeng' ); ?></th>
                        <th style="width: 250px;"><?php esc_html_e( 'Event / Message', 'labeng' ); ?></th>
                        <th><?php esc_html_e( 'Data payload', 'labeng' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $logs = get_option( 'labeng_payment_logs', array() );
                    if ( empty( $logs ) ) {
                        echo '<tr><td colspan="3" style="padding:15px;text-align:center;color:#666;">' . esc_html__( 'No payment logs found yet. They will appear here when webhooks are received.', 'labeng' ) . '</td></tr>';
                    } else {
                        foreach ( $logs as $log ) {
                            echo '<tr>';
                            echo '<td>' . esc_html( $log['time'] ) . '</td>';
                            echo '<td><strong>' . esc_html( $log['message'] ) . '</strong></td>';
                            echo '<td><pre style="margin:0;font-size:11px;max-height:100px;overflow:auto;background:#f6f7f7;padding:8px;">' . esc_html( print_r( $log['data'], true ) ) . '</pre></td>';
                            echo '</tr>';
                        }
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
