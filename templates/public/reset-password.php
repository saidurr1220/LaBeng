<?php
if ( ! defined( 'ABSPATH' ) ) exit;

if ( is_user_logged_in() ) {
    wp_safe_redirect( home_url( '/' ) );
    exit;
}

$reset_key   = sanitize_text_field( $_GET['key'] ?? '' );
$reset_login = sanitize_text_field( $_GET['login'] ?? '' );

labeng_get_header();
?>

<div class="lab-auth-page">
    <div class="lab-auth-container">
        <h1 class="lab-auth-title"><?php esc_html_e( 'Reset Password', 'labeng' ); ?></h1>

        <div class="lab-auth-card">
            <?php if ( empty( $reset_key ) || empty( $reset_login ) ) : ?>
                <div class="lab-msg lab-msg--error" style="display:block;">
                    <p><?php esc_html_e( 'Invalid or expired password reset link.', 'labeng' ); ?></p>
                    <p style="margin-top: 0.75rem;"><a href="<?php echo esc_url( home_url( '/forgot-password/' ) ); ?>" style="color: #5FE0EC;"><?php esc_html_e( 'Request a new reset link', 'labeng' ); ?></a></p>
                </div>
            <?php else :
                $user = check_password_reset_key( $reset_key, $reset_login );
                if ( is_wp_error( $user ) ) : ?>
                    <div class="lab-msg lab-msg--error" style="display:block;">
                        <p><?php esc_html_e( 'This password reset link is invalid or has expired.', 'labeng' ); ?></p>
                        <p style="margin-top: 0.75rem;"><a href="<?php echo esc_url( home_url( '/forgot-password/' ) ); ?>" style="color: #5FE0EC;"><?php esc_html_e( 'Request a new reset link', 'labeng' ); ?></a></p>
                    </div>
                <?php else : ?>
                    <div id="lab-reset-msg" class="lab-msg" style="display:none;"></div>
                    <form id="lab-reset-form" class="lab-form" novalidate>
                        <input type="hidden" id="lab-reset-key" value="<?php echo esc_attr( $reset_key ); ?>" />
                        <input type="hidden" id="lab-reset-login" value="<?php echo esc_attr( $reset_login ); ?>" />

                        <div class="lab-field">
                            <label for="lab-reset-pass"><?php esc_html_e( 'New Password', 'labeng' ); ?></label>
                            <input type="password" id="lab-reset-pass" name="password" placeholder="***********" required />
                        </div>
                        <div class="lab-field">
                            <label for="lab-reset-pass-confirm"><?php esc_html_e( 'Confirm New Password', 'labeng' ); ?></label>
                            <input type="password" id="lab-reset-pass-confirm" name="password_confirm" placeholder="***********" required />
                        </div>

                        <button type="submit" class="lab-btn lab-btn--primary lab-btn--full"><?php esc_html_e( 'Reset Password', 'labeng' ); ?></button>
                    </form>
                <?php endif; ?>
            <?php endif; ?>

            <p class="lab-auth-footer">
                <a href="<?php echo esc_url( home_url( '/login/' ) ); ?>"><?php esc_html_e( 'Back to Login', 'labeng' ); ?></a>
            </p>
        </div>
    </div>
</div>

<?php
labeng_get_footer();
