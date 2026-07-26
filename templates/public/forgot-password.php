<?php
if ( ! defined( 'ABSPATH' ) ) exit;

if ( is_user_logged_in() ) {
    wp_safe_redirect( home_url( '/' ) );
    exit;
}

labeng_get_header();
?>

<div class="lab-auth-page">
    <div class="lab-auth-container">
        <h1 class="lab-auth-title">Forgot Password</h1>

        <div class="lab-auth-card">
            <div id="lab-forgot-msg" class="lab-msg" style="display:none;"></div>
            <form id="lab-forgot-form" class="lab-form" novalidate>
                <p style="color: #aaa; margin-bottom: 1.5rem; text-align: center; font-size: 0.95rem;">
                    <?php esc_html_e( 'Enter your email address and we will send you a link to reset your password.', 'labeng' ); ?>
                </p>
                <div class="lab-field">
                    <label for="lab-forgot-email"><?php esc_html_e( 'Email Address', 'labeng' ); ?></label>
                    <input type="email" id="lab-forgot-email" name="email" placeholder="janedoe@gmail.com" required />
                </div>
                <button type="submit" class="lab-btn lab-btn--primary lab-btn--full"><?php esc_html_e( 'Send Reset Link', 'labeng' ); ?></button>
            </form>
            <p class="lab-auth-footer">
                <a href="<?php echo esc_url( home_url( '/login/' ) ); ?>"><?php esc_html_e( 'Back to Login', 'labeng' ); ?></a>
            </p>
        </div>
    </div>
</div>

<?php
labeng_get_footer();
