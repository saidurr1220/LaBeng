<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class Lab_Auth
 * Registration forms (customer + business), login form, AJAX handlers.
 */
class Lab_Auth {

    public static function init() {
        /* AJAX: customer registration */
        add_action( 'wp_ajax_nopriv_lab_register_customer', array( __CLASS__, 'ajax_register_customer' ) );

        /* AJAX: business registration */
        add_action( 'wp_ajax_nopriv_lab_register_business', array( __CLASS__, 'ajax_register_business' ) );

        /* AJAX: login */
        add_action( 'wp_ajax_nopriv_lab_login', array( __CLASS__, 'ajax_login' ) );

        /* AJAX: partner inquiry (business contact form) */
        add_action( 'wp_ajax_nopriv_lab_partner_inquiry', array( __CLASS__, 'ajax_partner_inquiry' ) );
        add_action( 'wp_ajax_lab_partner_inquiry',        array( __CLASS__, 'ajax_partner_inquiry' ) );

        /* AJAX: customer inquiry (customer contact form) */
        add_action( 'wp_ajax_nopriv_lab_customer_inquiry', array( __CLASS__, 'ajax_customer_inquiry' ) );
        add_action( 'wp_ajax_lab_customer_inquiry',        array( __CLASS__, 'ajax_customer_inquiry' ) );

        /* AJAX: update profile */
        add_action( 'wp_ajax_lab_update_profile', array( __CLASS__, 'ajax_update_profile' ) );

        /* Hide admin bar for non-admins */
        add_filter( 'show_admin_bar', array( __CLASS__, 'hide_admin_bar' ) );

        /* Prevent non-admins from accessing wp-admin */
        add_action( 'admin_init', array( __CLASS__, 'block_wp_admin' ) );
    }

    /**
     * Hide admin bar for customers and business owners.
     */
    public static function hide_admin_bar( $show ) {
        if ( current_user_can( 'manage_options' ) ) return $show;
        return false;
    }

    /**
     * Block wp-admin access for non-admins.
     */
    public static function block_wp_admin() {
        if ( wp_doing_ajax() ) return;
        if ( ! current_user_can( 'manage_options' ) && is_user_logged_in() ) {
            $user = wp_get_current_user();
            if ( in_array( 'business_owner', $user->roles, true ) ) {
                wp_safe_redirect( home_url( '/business-dashboard/' ) );
                exit;
            }
            if ( in_array( 'customer', $user->roles, true ) ) {
                wp_safe_redirect( home_url( '/customer-dashboard/' ) );
                exit;
            }
        }
    }

    /* ──────────────────────────────────────────────────────────
     * Shortcode: [lab_register_form]
     * ────────────────────────────────────────────────────────── */
    public static function render_register_form() {
        if ( is_user_logged_in() ) {
            return '<div class="lab-notice">' . esc_html__( 'You are already logged in.', 'labeng' ) . '</div>';
        }
        ob_start();
        ?>
        <div class="lab-auth-wrap">
            <div class="lab-auth-card">
                <h2 class="lab-auth-title"><?php esc_html_e( 'Create Your Account', 'labeng' ); ?></h2>
                <p class="lab-auth-subtitle"><?php esc_html_e( 'Join Labeng to discover and book services.', 'labeng' ); ?></p>
                <div id="lab-register-msg" class="lab-msg" style="display:none;"></div>
                <form id="lab-register-form" class="lab-form" novalidate>
                    <div class="lab-form-row lab-form-row--2col">
                        <div class="lab-field">
                            <label for="lab-reg-fname"><?php esc_html_e( 'First Name', 'labeng' ); ?></label>
                            <input type="text" id="lab-reg-fname" name="first_name" required />
                        </div>
                        <div class="lab-field">
                            <label for="lab-reg-lname"><?php esc_html_e( 'Last Name', 'labeng' ); ?></label>
                            <input type="text" id="lab-reg-lname" name="last_name" required />
                        </div>
                    </div>
                    <div class="lab-field">
                        <label for="lab-reg-email"><?php esc_html_e( 'Email Address', 'labeng' ); ?></label>
                        <input type="email" id="lab-reg-email" name="email" required />
                    </div>
                    <div class="lab-form-row lab-form-row--2col">
                        <div class="lab-field">
                            <label for="lab-reg-pass"><?php esc_html_e( 'Password', 'labeng' ); ?></label>
                            <input type="password" id="lab-reg-pass" name="password" required />
                        </div>
                        <div class="lab-field">
                            <label for="lab-reg-pass2"><?php esc_html_e( 'Confirm Password', 'labeng' ); ?></label>
                            <input type="password" id="lab-reg-pass2" name="password_confirm" required />
                        </div>
                    </div>
                    <button type="submit" class="lab-btn lab-btn--primary lab-btn--full"><?php esc_html_e( 'Create Account', 'labeng' ); ?></button>
                </form>
                <p class="lab-auth-footer">
                    <?php esc_html_e( 'Already have an account?', 'labeng' ); ?>
                    <a href="<?php echo esc_url( home_url( '/login/' ) ); ?>"><?php esc_html_e( 'Login', 'labeng' ); ?></a>
                </p>
                <p class="lab-auth-footer">
                    <?php esc_html_e( 'Are you a business?', 'labeng' ); ?>
                    <a href="<?php echo esc_url( home_url( '/business-register/' ) ); ?>"><?php esc_html_e( 'Register as Business', 'labeng' ); ?></a>
                </p>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /* ──────────────────────────────────────────────────────────
     * Shortcode: [lab_login_form]
     * ────────────────────────────────────────────────────────── */
    public static function render_login_form() {
        if ( is_user_logged_in() ) {
            return '<div class="lab-notice">' . esc_html__( 'You are already logged in.', 'labeng' ) . '</div>';
        }
        ob_start();
        ?>
        <div class="lab-auth-wrap">
            <div class="lab-auth-card">
                <h2 class="lab-auth-title"><?php esc_html_e( 'Welcome Back', 'labeng' ); ?></h2>
                <p class="lab-auth-subtitle"><?php esc_html_e( 'Log in to your Labeng account.', 'labeng' ); ?></p>
                <div id="lab-login-msg" class="lab-msg" style="display:none;"></div>
                <form id="lab-login-form" class="lab-form" novalidate>
                    <div class="lab-field">
                        <label for="lab-login-email"><?php esc_html_e( 'Email Address', 'labeng' ); ?></label>
                        <input type="email" id="lab-login-email" name="email" required />
                    </div>
                    <div class="lab-field">
                        <label for="lab-login-pass"><?php esc_html_e( 'Password', 'labeng' ); ?></label>
                        <input type="password" id="lab-login-pass" name="password" required />
                    </div>
                    <button type="submit" class="lab-btn lab-btn--primary lab-btn--full"><?php esc_html_e( 'Login', 'labeng' ); ?></button>
                </form>
                <p class="lab-auth-footer">
                    <?php esc_html_e( "Don't have an account?", 'labeng' ); ?>
                    <a href="<?php echo esc_url( home_url( '/register/' ) ); ?>"><?php esc_html_e( 'Register', 'labeng' ); ?></a>
                </p>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /* ──────────────────────────────────────────────────────────
     * Shortcode: [lab_register_business_form]
     * ────────────────────────────────────────────────────────── */
    public static function render_register_business_form() {
        if ( is_user_logged_in() ) {
            return '<div class="lab-notice">' . esc_html__( 'You are already logged in. Please logout first to register a new business.', 'labeng' ) . '</div>';
        }

        /* Get categories from taxonomy */
        $categories = get_terms( array(
            'taxonomy'   => 'lab_category',
            'hide_empty' => false,
        ) );

        ob_start();
        ?>
        <div class="lab-auth-wrap">
            <div class="lab-auth-card lab-auth-card--wide">
                <h2 class="lab-auth-title"><?php esc_html_e( 'Register Your Business', 'labeng' ); ?></h2>
                <p class="lab-auth-subtitle"><?php esc_html_e( 'Join Labeng and reach more customers. Complete the form below to get started.', 'labeng' ); ?></p>
                <div id="lab-biz-register-msg" class="lab-msg" style="display:none;"></div>
                <form id="lab-biz-register-form" class="lab-form" novalidate>
                    <div class="lab-form-row lab-form-row--2col">
                        <div class="lab-field">
                            <label for="lab-biz-name"><?php esc_html_e( 'Business Name', 'labeng' ); ?></label>
                            <input type="text" id="lab-biz-name" name="business_name" required />
                        </div>
                        <div class="lab-field">
                            <label for="lab-biz-owner"><?php esc_html_e( 'Owner Full Name', 'labeng' ); ?></label>
                            <input type="text" id="lab-biz-owner" name="owner_name" required />
                        </div>
                    </div>
                    <div class="lab-form-row lab-form-row--2col">
                        <div class="lab-field">
                            <label for="lab-biz-email"><?php esc_html_e( 'Email Address', 'labeng' ); ?></label>
                            <input type="email" id="lab-biz-email" name="email" required />
                        </div>
                        <div class="lab-field">
                            <label for="lab-biz-phone"><?php esc_html_e( 'Phone Number', 'labeng' ); ?></label>
                            <input type="tel" id="lab-biz-phone" name="phone" required />
                        </div>
                    </div>
                    <div class="lab-form-row lab-form-row--2col">
                        <div class="lab-field">
                            <label for="lab-biz-pass"><?php esc_html_e( 'Password', 'labeng' ); ?></label>
                            <input type="password" id="lab-biz-pass" name="password" required />
                        </div>
                        <div class="lab-field">
                            <label for="lab-biz-pass2"><?php esc_html_e( 'Confirm Password', 'labeng' ); ?></label>
                            <input type="password" id="lab-biz-pass2" name="password_confirm" required />
                        </div>
                    </div>
                    <div class="lab-form-row lab-form-row--2col">
                        <div class="lab-field">
                            <label for="lab-biz-city"><?php esc_html_e( 'City', 'labeng' ); ?></label>
                            <input type="text" id="lab-biz-city" name="city" required />
                        </div>
                        <div class="lab-field">
                            <label for="lab-biz-postcode"><?php esc_html_e( 'Postcode', 'labeng' ); ?></label>
                            <input type="text" id="lab-biz-postcode" name="postcode" required />
                        </div>
                    </div>
                    <div class="lab-form-row lab-form-row--2col">
                        <div class="lab-field">
                            <label for="lab-biz-category"><?php esc_html_e( 'Category', 'labeng' ); ?></label>
                            <select id="lab-biz-category" name="category" required>
                                <option value=""><?php esc_html_e( 'Select Category', 'labeng' ); ?></option>
                                <?php if ( ! is_wp_error( $categories ) && ! empty( $categories ) ) : ?>
                                    <?php foreach ( $categories as $cat ) : ?>
                                        <option value="<?php echo esc_attr( $cat->slug ); ?>"><?php echo esc_html( $cat->name ); ?></option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>
                    <div class="lab-field">
                        <label for="lab-biz-desc"><?php esc_html_e( 'Short Description', 'labeng' ); ?></label>
                        <textarea id="lab-biz-desc" name="description" rows="4" required></textarea>
                    </div>
                    <button type="submit" class="lab-btn lab-btn--primary lab-btn--full"><?php esc_html_e( 'Submit Application', 'labeng' ); ?></button>
                </form>
                <p class="lab-auth-footer">
                    <?php esc_html_e( 'Already have an account?', 'labeng' ); ?>
                    <a href="<?php echo esc_url( home_url( '/login/' ) ); ?>"><?php esc_html_e( 'Login', 'labeng' ); ?></a>
                </p>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /* ──────────────────────────────────────────────────────────
     * AJAX: Customer Registration
     * ────────────────────────────────────────────────────────── */
    public static function ajax_register_customer() {
        check_ajax_referer( 'lab_nonce', 'nonce' );

        $first_name = sanitize_text_field( $_POST['first_name'] ?? '' );
        $last_name  = sanitize_text_field( $_POST['last_name'] ?? '' );
        $email      = sanitize_email( $_POST['email'] ?? '' );
        $password   = $_POST['password'] ?? '';
        $password2  = $_POST['password_confirm'] ?? '';

        if ( empty( $first_name ) || empty( $last_name ) || empty( $email ) || empty( $password ) ) {
            wp_send_json_error( array( 'message' => __( 'All fields are required.', 'labeng' ) ) );
        }
        if ( ! is_email( $email ) ) {
            wp_send_json_error( array( 'message' => __( 'Invalid email address.', 'labeng' ) ) );
        }
        if ( $password !== $password2 ) {
            wp_send_json_error( array( 'message' => __( 'Passwords do not match.', 'labeng' ) ) );
        }
        if ( strlen( $password ) < 6 ) {
            wp_send_json_error( array( 'message' => __( 'Password must be at least 6 characters.', 'labeng' ) ) );
        }
        if ( email_exists( $email ) ) {
            wp_send_json_error( array( 'message' => __( 'An account with this email already exists.', 'labeng' ) ) );
        }

        $username = sanitize_user( strtolower( $first_name . '.' . $last_name ) );
        $base_username = $username;
        $i = 1;
        while ( username_exists( $username ) ) {
            $username = $base_username . $i;
            $i++;
        }

        $user_id = wp_create_user( $username, $password, $email );
        if ( is_wp_error( $user_id ) ) {
            wp_send_json_error( array( 'message' => $user_id->get_error_message() ) );
        }

        wp_update_user( array(
            'ID'         => $user_id,
            'first_name' => $first_name,
            'last_name'  => $last_name,
            'role'       => 'customer',
        ) );

        /* Auto-login */
        wp_set_current_user( $user_id );
        wp_set_auth_cookie( $user_id, true );

        wp_send_json_success( array(
            'message'  => __( 'Account created successfully! Redirecting…', 'labeng' ),
            'redirect' => home_url( '/customer-dashboard/' ),
        ) );
    }

    /* ──────────────────────────────────────────────────────────
     * AJAX: Business Registration
     * ────────────────────────────────────────────────────────── */
    public static function ajax_register_business() {
        check_ajax_referer( 'lab_nonce', 'nonce' );

        $biz_name    = sanitize_text_field( $_POST['business_name'] ?? '' );
        $owner_name  = sanitize_text_field( $_POST['owner_name'] ?? '' );
        $email       = sanitize_email( $_POST['email'] ?? '' );
        $phone       = sanitize_text_field( $_POST['phone'] ?? '' );
        $password    = $_POST['password'] ?? '';
        $password2   = $_POST['password_confirm'] ?? '';
        $city        = sanitize_text_field( $_POST['city'] ?? '' );
        $postcode    = sanitize_text_field( $_POST['postcode'] ?? '' );
        $category    = sanitize_text_field( $_POST['category'] ?? '' );
        $description = sanitize_textarea_field( $_POST['description'] ?? '' );

        /* Validation */
        if ( empty( $biz_name ) || empty( $owner_name ) || empty( $email ) || empty( $password ) || empty( $city ) || empty( $postcode ) ) {
            wp_send_json_error( array( 'message' => __( 'All required fields must be filled.', 'labeng' ) ) );
        }
        if ( ! is_email( $email ) ) {
            wp_send_json_error( array( 'message' => __( 'Invalid email address.', 'labeng' ) ) );
        }
        if ( $password !== $password2 ) {
            wp_send_json_error( array( 'message' => __( 'Passwords do not match.', 'labeng' ) ) );
        }
        if ( strlen( $password ) < 6 ) {
            wp_send_json_error( array( 'message' => __( 'Password must be at least 6 characters.', 'labeng' ) ) );
        }
        if ( email_exists( $email ) ) {
            wp_send_json_error( array( 'message' => __( 'An account with this email already exists.', 'labeng' ) ) );
        }

        /* Create WP user (subscriber initially, pending approval) */
        $name_parts = explode( ' ', $owner_name, 2 );
        $first_name = $name_parts[0];
        $last_name  = isset( $name_parts[1] ) ? $name_parts[1] : '';

        $username = sanitize_user( strtolower( str_replace( ' ', '.', $owner_name ) ) );
        $base_username = $username;
        $i = 1;
        while ( username_exists( $username ) ) {
            $username = $base_username . $i;
            $i++;
        }

        $user_id = wp_create_user( $username, $password, $email );
        if ( is_wp_error( $user_id ) ) {
            wp_send_json_error( array( 'message' => $user_id->get_error_message() ) );
        }

        wp_update_user( array(
            'ID'         => $user_id,
            'first_name' => $first_name,
            'last_name'  => $last_name,
            'role'       => 'subscriber', /* Pending approval */
        ) );

        /* Create lab_business post (draft, pending approval) */
        $post_id = wp_insert_post( array(
            'post_title'   => $biz_name,
            'post_content' => $description,
            'post_status'  => 'draft',
            'post_type'    => 'lab_business',
            'post_author'  => $user_id,
        ) );

        if ( is_wp_error( $post_id ) ) {
            wp_send_json_error( array( 'message' => __( 'Failed to create business listing.', 'labeng' ) ) );
        }

        /* Store meta */
        update_post_meta( $post_id, '_lab_owner_id', $user_id );
        update_post_meta( $post_id, '_lab_phone', $phone );
        update_post_meta( $post_id, '_lab_email', $email );
        update_post_meta( $post_id, '_lab_city', $city );
        update_post_meta( $post_id, '_lab_postcode', $postcode );
        update_post_meta( $post_id, '_lab_status', 'pending' );
        update_post_meta( $post_id, '_lab_gallery', '' );
        update_post_meta( $post_id, '_lab_rating_avg', 0 );
        update_post_meta( $post_id, '_lab_total_reviews', 0 );
        update_post_meta( $post_id, '_lab_services', '[]' );

        /* Set category taxonomy */
        if ( ! empty( $category ) ) {
            wp_set_object_terms( $post_id, $category, 'lab_category' );
        }

        /* Email admin about new registration */
        Lab_Email::lab_email_business_pending_admin( $post_id, $biz_name, $owner_name, $email );

        wp_send_json_success( array(
            'message' => __( 'Your application has been submitted and is under review. We will email you once approved.', 'labeng' ),
        ) );
    }

    /* ──────────────────────────────────────────────────────────
     * AJAX: Partner Inquiry (contact form on /partner/)
     * ────────────────────────────────────────────────────────── */
    public static function ajax_partner_inquiry() {
        check_ajax_referer( 'lab_nonce', 'nonce' );

        $name     = sanitize_text_field( $_POST['name'] ?? '' );
        $email    = sanitize_email( $_POST['email'] ?? '' );
        $biz_name = sanitize_text_field( $_POST['business_name'] ?? '' );
        $category = sanitize_text_field( $_POST['category'] ?? '' );
        $message  = sanitize_textarea_field( $_POST['message'] ?? '' );

        if ( empty( $name ) || empty( $email ) || empty( $message ) ) {
            wp_send_json_error( array( 'message' => __( 'Please fill in your name, email and message.', 'labeng' ) ) );
        }
        if ( ! is_email( $email ) ) {
            wp_send_json_error( array( 'message' => __( 'Please enter a valid email address.', 'labeng' ) ) );
        }

        /* Build admin email */
        $admin_email = get_option( 'admin_email' );
        $subject     = sprintf( __( 'New partner inquiry from %s', 'labeng' ), $name );
        $body  = "You have received a new partner inquiry via the LaBeng website.\n\n";
        $body .= "Name: {$name}\n";
        $body .= "Email: {$email}\n";
        if ( $biz_name ) $body .= "Business: {$biz_name}\n";
        if ( $category ) $body .= "Category: {$category}\n";
        $body .= "\nMessage:\n{$message}\n";

        $headers = array( 'Reply-To: ' . $name . ' <' . $email . '>' );
        wp_mail( $admin_email, $subject, $body, $headers );

        /* Keep a record of inquiries for the admin */
        $inquiries = get_option( 'lab_partner_inquiries', array() );
        if ( ! is_array( $inquiries ) ) {
            $inquiries = array();
        }
        array_unshift( $inquiries, array(
            'time'          => current_time( 'mysql' ),
            'name'          => $name,
            'email'         => $email,
            'business_name' => $biz_name,
            'category'      => $category,
            'message'       => $message,
        ) );
        if ( count( $inquiries ) > 200 ) {
            $inquiries = array_slice( $inquiries, 0, 200 );
        }
        update_option( 'lab_partner_inquiries', $inquiries );

        wp_send_json_success( array(
            'message' => __( 'Thanks for reaching out! Our team will get back to you shortly.', 'labeng' ),
        ) );
    }

    /* ──────────────────────────────────────────────────────────
     * AJAX: Customer inquiry (customer contact form)
     * ────────────────────────────────────────────────────────── */
    public static function ajax_customer_inquiry() {
        check_ajax_referer( 'lab_nonce', 'nonce' );

        $name    = sanitize_text_field( $_POST['name'] ?? '' );
        $email   = sanitize_email( $_POST['email'] ?? '' );
        $subject = sanitize_text_field( $_POST['subject'] ?? '' );
        $message = sanitize_textarea_field( $_POST['message'] ?? '' );

        if ( empty( $name ) || empty( $email ) || empty( $message ) ) {
            wp_send_json_error( array( 'message' => __( 'Please fill in your name, email and message.', 'labeng' ) ) );
        }
        if ( ! is_email( $email ) ) {
            wp_send_json_error( array( 'message' => __( 'Please enter a valid email address.', 'labeng' ) ) );
        }

        /* Build admin email */
        $admin_email = get_option( 'admin_email' );
        $subj_line   = $subject
            ? sprintf( __( 'Customer enquiry: %s', 'labeng' ), $subject )
            : sprintf( __( 'New customer enquiry from %s', 'labeng' ), $name );
        $body  = "You have received a new customer enquiry via the LaBeng website.\n\n";
        $body .= "Name: {$name}\n";
        $body .= "Email: {$email}\n";
        if ( $subject ) $body .= "Subject: {$subject}\n";
        $body .= "\nMessage:\n{$message}\n";

        $headers = array( 'Reply-To: ' . $name . ' <' . $email . '>' );
        wp_mail( $admin_email, $subj_line, $body, $headers );

        /* Keep a record of customer enquiries for the admin */
        $inquiries = get_option( 'lab_customer_inquiries', array() );
        if ( ! is_array( $inquiries ) ) {
            $inquiries = array();
        }
        array_unshift( $inquiries, array(
            'time'    => current_time( 'mysql' ),
            'name'    => $name,
            'email'   => $email,
            'subject' => $subject,
            'message' => $message,
        ) );
        if ( count( $inquiries ) > 200 ) {
            $inquiries = array_slice( $inquiries, 0, 200 );
        }
        update_option( 'lab_customer_inquiries', $inquiries );

        wp_send_json_success( array(
            'message' => __( 'Thanks for reaching out! Our team will get back to you shortly.', 'labeng' ),
        ) );
    }

    /* ──────────────────────────────────────────────────────────
     * AJAX: Login
     * ────────────────────────────────────────────────────────── */
    public static function ajax_login() {
        check_ajax_referer( 'lab_nonce', 'nonce' );

        $email    = sanitize_email( $_POST['email'] ?? '' );
        $password = $_POST['password'] ?? '';

        if ( empty( $email ) || empty( $password ) ) {
            wp_send_json_error( array( 'message' => __( 'Email and password are required.', 'labeng' ) ) );
        }

        /* Find user by email */
        $user = get_user_by( 'email', $email );
        if ( ! $user ) {
            wp_send_json_error( array( 'message' => __( 'Invalid email or password.', 'labeng' ) ) );
        }

        $creds = array(
            'user_login'    => $user->user_login,
            'user_password' => $password,
            'remember'      => true,
        );

        $login = wp_signon( $creds, is_ssl() );
        if ( is_wp_error( $login ) ) {
            wp_send_json_error( array( 'message' => __( 'Invalid email or password.', 'labeng' ) ) );
        }

        /* Determine redirect */
        $redirect = home_url( '/' );
        if ( in_array( 'administrator', $login->roles, true ) ) {
            $redirect = admin_url();
        } elseif ( in_array( 'business_owner', $login->roles, true ) ) {
            $redirect = home_url( '/business-dashboard/' );
        } elseif ( in_array( 'customer', $login->roles, true ) ) {
            $redirect = home_url( '/customer-dashboard/' );
        }

        wp_send_json_success( array(
            'message'  => __( 'Login successful! Redirecting…', 'labeng' ),
            'redirect' => $redirect,
        ) );
    }

    /* ──────────────────────────────────────────────────────────
     * AJAX: Update Profile (Customer Dashboard)
     * ────────────────────────────────────────────────────────── */
    public static function ajax_update_profile() {
        check_ajax_referer( 'lab_nonce', 'nonce' );

        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array( 'message' => __( 'Not logged in.', 'labeng' ) ) );
        }

        $user_id    = get_current_user_id();
        $first_name = sanitize_text_field( $_POST['first_name'] ?? '' );
        $last_name  = sanitize_text_field( $_POST['last_name'] ?? '' );
        $email      = sanitize_email( $_POST['email'] ?? '' );
        $password   = $_POST['password'] ?? '';
        $password2  = $_POST['password_confirm'] ?? '';

        if ( empty( $first_name ) || empty( $last_name ) || empty( $email ) ) {
            wp_send_json_error( array( 'message' => __( 'Name and email are required.', 'labeng' ) ) );
        }

        /* Check if email changed and is taken */
        $current_user = get_userdata( $user_id );
        if ( $email !== $current_user->user_email && email_exists( $email ) ) {
            wp_send_json_error( array( 'message' => __( 'This email is already in use.', 'labeng' ) ) );
        }

        $update_data = array(
            'ID'         => $user_id,
            'first_name' => $first_name,
            'last_name'  => $last_name,
            'user_email' => $email,
            'display_name' => $first_name . ' ' . $last_name,
        );

        /* Password change */
        if ( ! empty( $password ) ) {
            if ( $password !== $password2 ) {
                wp_send_json_error( array( 'message' => __( 'Passwords do not match.', 'labeng' ) ) );
            }
            if ( strlen( $password ) < 6 ) {
                wp_send_json_error( array( 'message' => __( 'Password must be at least 6 characters.', 'labeng' ) ) );
            }
            $update_data['user_pass'] = $password;
        }

        $result = wp_update_user( $update_data );
        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'message' => $result->get_error_message() ) );
        }

        wp_send_json_success( array( 'message' => __( 'Profile updated successfully.', 'labeng' ) ) );
    }
}
