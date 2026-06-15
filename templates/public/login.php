<?php
if ( ! defined( 'ABSPATH' ) ) exit;

if ( is_user_logged_in() ) {
    wp_safe_redirect( home_url( '/' ) );
    exit;
}

include LABENG_PATH . 'templates/global/header.php';
?>

<div class="lab-auth-page">
    <div class="lab-auth-container">
        <h1 class="lab-auth-title">Welcome Back</h1>

        <div class="lab-auth-card">
            <div id="lab-login-msg" class="lab-msg" style="display:none;"></div>
            <form id="lab-login-form" class="lab-form" novalidate>
                <div class="lab-field">
                    <label for="lab-login-email">Email</label>
                    <input type="email" id="lab-login-email" name="email" placeholder="Janedoe@gmail.com" required />
                </div>
                <div class="lab-field">
                    <label for="lab-login-pass">Password</label>
                    <input type="password" id="lab-login-pass" name="password" placeholder="***********" required />
                </div>
                
                <button type="submit" class="lab-btn lab-btn--primary lab-btn--full">Log in</button>
            </form>
            
            <p class="lab-auth-footer">
                New User? <a href="<?php echo esc_url( home_url( '/register/' ) ); ?>">Sign up</a>
            </p>
        </div>
    </div>
</div>

<?php
include LABENG_PATH . 'templates/global/footer.php';
