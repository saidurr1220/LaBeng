<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$user = wp_get_current_user();
?>

<h2 class="lab-section-title"><?php esc_html_e( 'My Profile', 'labeng' ); ?></h2>

<div id="lab-profile-msg" class="lab-msg" style="display:none;"></div>

<form id="lab-profile-form" class="lab-form">
    <div class="lab-form-row lab-form-row--2col">
        <div class="lab-field">
            <label for="lab-prof-fname"><?php esc_html_e( 'First Name', 'labeng' ); ?></label>
            <input type="text" id="lab-prof-fname" name="first_name" value="<?php echo esc_attr( $user->first_name ); ?>" required />
        </div>
        <div class="lab-field">
            <label for="lab-prof-lname"><?php esc_html_e( 'Last Name', 'labeng' ); ?></label>
            <input type="text" id="lab-prof-lname" name="last_name" value="<?php echo esc_attr( $user->last_name ); ?>" required />
        </div>
    </div>
    <div class="lab-field">
        <label for="lab-prof-email"><?php esc_html_e( 'Email Address', 'labeng' ); ?></label>
        <input type="email" id="lab-prof-email" name="email" value="<?php echo esc_attr( $user->user_email ); ?>" required />
    </div>
    <div class="lab-form-row lab-form-row--2col">
        <div class="lab-field">
            <label for="lab-prof-pass"><?php esc_html_e( 'New Password', 'labeng' ); ?></label>
            <input type="password" id="lab-prof-pass" name="password" placeholder="<?php esc_attr_e( 'Leave blank to keep current', 'labeng' ); ?>" />
        </div>
        <div class="lab-field">
            <label for="lab-prof-pass2"><?php esc_html_e( 'Confirm New Password', 'labeng' ); ?></label>
            <input type="password" id="lab-prof-pass2" name="password_confirm" />
        </div>
    </div>
    <button type="submit" class="lab-btn lab-btn--primary"><?php esc_html_e( 'Save Profile', 'labeng' ); ?></button>
</form>
