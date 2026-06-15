<?php
if ( ! defined( 'ABSPATH' ) ) exit;

if ( is_user_logged_in() ) {
    wp_safe_redirect( home_url( '/' ) );
    exit;
}

$is_business = is_page( 'business-register' );

include LABENG_PATH . 'templates/global/header.php';
?>

<div class="lab-auth-page">
    <div class="lab-auth-container <?php echo $is_business ? 'lab-auth-container--wide' : ''; ?>">
        <h1 class="lab-auth-title"><?php echo $is_business ? 'Join LaBeng' : 'Get Started'; ?></h1>
        
        <div class="lab-auth-card">
            <?php if ( $is_business ) : 
                $categories = get_terms( array( 'taxonomy' => 'lab_category', 'hide_empty' => false ) );
            ?>
                <div id="lab-biz-register-msg" class="lab-msg" style="display:none;"></div>
                <form id="lab-biz-register-form" class="lab-form" novalidate>
                    <div class="lab-form-row lab-form-row--2col">
                        <div class="lab-field">
                            <label>Business Name</label>
                            <input type="text" name="business_name" id="lab-biz-name" required />
                        </div>
                        <div class="lab-field">
                            <label>Owner Full Name</label>
                            <input type="text" name="owner_name" id="lab-biz-owner" required />
                        </div>
                    </div>
                    <div class="lab-form-row lab-form-row--2col">
                        <div class="lab-field">
                            <label>Email Address</label>
                            <input type="email" name="email" id="lab-biz-email" required />
                        </div>
                        <div class="lab-field">
                            <label>Phone Number</label>
                            <input type="tel" name="phone" id="lab-biz-phone" required />
                        </div>
                    </div>
                    <div class="lab-form-row lab-form-row--2col">
                        <div class="lab-field">
                            <label>Password</label>
                            <input type="password" name="password" id="lab-biz-pass" required />
                        </div>
                        <div class="lab-field">
                            <label>Confirm Password</label>
                            <input type="password" name="password_confirm" id="lab-biz-pass2" required />
                        </div>
                    </div>
                    <div class="lab-form-row lab-form-row--2col">
                        <div class="lab-field">
                            <label>City</label>
                            <input type="text" name="city" id="lab-biz-city" required />
                        </div>
                        <div class="lab-field">
                            <label>Postcode</label>
                            <input type="text" name="postcode" id="lab-biz-postcode" required placeholder="e.g. NQ 4AB" />
                        </div>
                    </div>
                    <div class="lab-field">
                        <label>Category</label>
                        <select name="category" id="lab-biz-category" required>
                            <option value="">Select Category</option>
                            <?php if ( ! is_wp_error( $categories ) && ! empty( $categories ) ) : ?>
                                <?php foreach ( $categories as $cat ) : ?>
                                    <option value="<?php echo esc_attr( $cat->slug ); ?>"><?php echo esc_html( $cat->name ); ?></option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="lab-field">
                        <label>Short Description</label>
                        <textarea name="description" id="lab-biz-desc" rows="3" required></textarea>
                    </div>
                    <button type="submit" class="lab-btn lab-btn--primary lab-btn--full">Submit Application</button>
                </form>
            <?php else : ?>
                <div id="lab-register-msg" class="lab-msg" style="display:none;"></div>
                <form id="lab-register-form" class="lab-form" novalidate>
                    <div class="lab-form-row lab-form-row--2col">
                        <div class="lab-field">
                            <label>First Name</label>
                            <input type="text" name="first_name" id="lab-reg-fname" required />
                        </div>
                        <div class="lab-field">
                            <label>Last Name</label>
                            <input type="text" name="last_name" id="lab-reg-lname" required />
                        </div>
                    </div>
                    <div class="lab-field">
                        <label>Email Address</label>
                        <input type="email" name="email" id="lab-reg-email" required />
                    </div>
                    <div class="lab-form-row lab-form-row--2col">
                        <div class="lab-field">
                            <label>Password</label>
                            <input type="password" name="password" id="lab-reg-pass" required />
                        </div>
                        <div class="lab-field">
                            <label>Confirm Password</label>
                            <input type="password" name="password_confirm" id="lab-reg-pass2" required />
                        </div>
                    </div>
                    <button type="submit" class="lab-btn lab-btn--primary lab-btn--full">Sign up</button>
                </form>
            <?php endif; ?>
            
            <p class="lab-auth-footer">
                Already have an account? <a href="<?php echo esc_url( home_url( '/login/' ) ); ?>">Log in</a>
            </p>
        </div>
    </div>
</div>

<?php
include LABENG_PATH . 'templates/global/footer.php';
