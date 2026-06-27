<?php
/**
 * Admin -> Labeng -> Email / SMTP
 * Configure outgoing mail via SMTP so wp_mail() reliably delivers.
 */
if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! current_user_can( 'manage_options' ) ) return;

/* Save */
if ( isset( $_POST['lab_smtp_nonce'] ) && wp_verify_nonce( $_POST['lab_smtp_nonce'], 'lab_save_smtp' ) ) {
    $fields = array(
        'lab_smtp_host'     => 'sanitize_text_field',
        'lab_smtp_port'     => 'absint',
        'lab_smtp_user'     => 'sanitize_email',
        'lab_smtp_from'     => 'sanitize_email',
        'lab_smtp_fromname' => 'sanitize_text_field',
        'lab_smtp_enc'      => 'sanitize_text_field',
    );
    foreach ( $fields as $key => $cb ) {
        if ( isset( $_POST[ $key ] ) ) {
            update_option( $key, call_user_func( $cb, $_POST[ $key ] ) );
        }
    }
    /* Password: only update if a new one was typed */
    if ( ! empty( $_POST['lab_smtp_pass'] ) ) {
        update_option( 'lab_smtp_pass', sanitize_text_field( $_POST['lab_smtp_pass'] ) );
    }
    update_option( 'lab_smtp_enabled', isset( $_POST['lab_smtp_enabled'] ) ? '1' : '0' );

    echo '<div class="notice notice-success is-dismissible"><p>SMTP settings saved.</p></div>';
}

$enabled   = get_option( 'lab_smtp_enabled', '0' );
$host      = get_option( 'lab_smtp_host',     'smtp.hostinger.com' );
$port      = get_option( 'lab_smtp_port',     465 );
$enc       = get_option( 'lab_smtp_enc',      'ssl' );
$user      = get_option( 'lab_smtp_user',     '' );
$from      = get_option( 'lab_smtp_from',     get_option( 'admin_email' ) );
$fromname  = get_option( 'lab_smtp_fromname', get_bloginfo( 'name' ) );
$has_pass  = (bool) get_option( 'lab_smtp_pass', '' );

/* Send test email */
$test_msg = '';
if ( isset( $_POST['lab_smtp_test'] ) && check_admin_referer( 'lab_save_smtp', 'lab_smtp_nonce' ) ) {
    $result = wp_mail( get_option( 'admin_email' ), 'Labeng SMTP Test', '<p>SMTP is working correctly.</p>' );
    $test_msg = $result
        ? '<div class="notice notice-success is-dismissible"><p>Test email sent to <strong>' . esc_html( get_option( 'admin_email' ) ) . '</strong>.</p></div>'
        : '<div class="notice notice-error is-dismissible"><p>Test email failed. Check your SMTP credentials and server logs.</p></div>';
}
echo wp_kses_post( $test_msg );
?>
<div class="wrap">
    <h1>Email / SMTP Settings</h1>
    <p class="description">Configure outgoing email so booking confirmations, notifications and contact forms deliver reliably. On Hostinger, use <code>smtp.hostinger.com</code> with port <code>465 / SSL</code> and your cPanel email address &amp; password.</p>

    <form method="post">
        <?php wp_nonce_field( 'lab_save_smtp', 'lab_smtp_nonce' ); ?>
        <table class="form-table" role="presentation">
            <tr>
                <th scope="row">Enable SMTP</th>
                <td>
                    <label>
                        <input type="checkbox" name="lab_smtp_enabled" value="1" <?php checked( $enabled, '1' ); ?> />
                        Use SMTP instead of PHP mail()
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="lab_smtp_host">SMTP Host</label></th>
                <td>
                    <input type="text" id="lab_smtp_host" name="lab_smtp_host" class="regular-text" value="<?php echo esc_attr( $host ); ?>" placeholder="smtp.hostinger.com" />
                    <p class="description">Hostinger: <code>smtp.hostinger.com</code></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="lab_smtp_port">Port</label></th>
                <td>
                    <input type="number" id="lab_smtp_port" name="lab_smtp_port" class="small-text" value="<?php echo esc_attr( $port ); ?>" />
                    <p class="description">465 (SSL) · 587 (TLS/STARTTLS)</p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="lab_smtp_enc">Encryption</label></th>
                <td>
                    <select id="lab_smtp_enc" name="lab_smtp_enc">
                        <option value="ssl"  <?php selected( $enc, 'ssl' ); ?>>SSL</option>
                        <option value="tls"  <?php selected( $enc, 'tls' ); ?>>TLS / STARTTLS</option>
                        <option value=""     <?php selected( $enc, '' );    ?>>None</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="lab_smtp_user">Username</label></th>
                <td>
                    <input type="email" id="lab_smtp_user" name="lab_smtp_user" class="regular-text" value="<?php echo esc_attr( $user ); ?>" placeholder="noreply@labeng.tecnotia.com" />
                    <p class="description">Usually the full email address used to authenticate.</p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="lab_smtp_pass">Password</label></th>
                <td>
                    <input type="password" id="lab_smtp_pass" name="lab_smtp_pass" class="regular-text" value="" placeholder="<?php echo $has_pass ? '(saved — leave blank to keep)' : ''; ?>" autocomplete="new-password" />
                    <?php if ( $has_pass ) : ?>
                        <p class="description" style="color:#46b450;">A password is saved. Leave blank to keep the current one.</p>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="lab_smtp_from">From Email</label></th>
                <td>
                    <input type="email" id="lab_smtp_from" name="lab_smtp_from" class="regular-text" value="<?php echo esc_attr( $from ); ?>" />
                    <p class="description">The <em>From</em> address that appears in emails. Must match (or be authorised by) your sending domain.</p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="lab_smtp_fromname">From Name</label></th>
                <td>
                    <input type="text" id="lab_smtp_fromname" name="lab_smtp_fromname" class="regular-text" value="<?php echo esc_attr( $fromname ); ?>" />
                </td>
            </tr>
        </table>

        <p class="submit">
            <?php submit_button( 'Save SMTP Settings', 'primary', 'submit', false ); ?>
            &nbsp;
            <button type="submit" name="lab_smtp_test" value="1" class="button button-secondary">Send Test Email</button>
        </p>
    </form>
</div>
