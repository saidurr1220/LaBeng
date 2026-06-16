<?php
/**
 * Plugin Name: Labeng Core
 * Plugin URI:  https://labeng.com
 * Description: 3-sided SaaS marketplace — Customers discover & book, Business Owners self-manage listings, Admin manages the platform.
 * Version:     1.2.7
 * Author:      Md. Saidur Rahman
 * Author URI:  https://saidur-it.vercel.app
 * Text Domain: labeng
 * Domain Path: /languages
 * License:     GPLv2 or later
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/* ── Constants ─────────────────────────────────────────────── */
define( 'LABENG_VERSION', '1.2.7' );
define( 'LABENG_PATH',    plugin_dir_path( __FILE__ ) );
define( 'LABENG_URL',     plugin_dir_url( __FILE__ ) );
define( 'LABENG_BASENAME', plugin_basename( __FILE__ ) );

/* ── Includes ──────────────────────────────────────────────── */
require_once LABENG_PATH . 'includes/class-install.php';
require_once LABENG_PATH . 'includes/class-roles.php';
require_once LABENG_PATH . 'includes/class-auth.php';
require_once LABENG_PATH . 'includes/class-business-cpt.php';
require_once LABENG_PATH . 'includes/class-deals-cpt.php';
require_once LABENG_PATH . 'includes/class-availability.php';
require_once LABENG_PATH . 'includes/class-bookings.php';
require_once LABENG_PATH . 'includes/class-commissions.php';
require_once LABENG_PATH . 'includes/class-reviews.php';
require_once LABENG_PATH . 'includes/class-email.php';
require_once LABENG_PATH . 'includes/class-shortcodes.php';

/* ── Activation / Deactivation ─────────────────────────────── */
register_activation_hook( __FILE__, array( 'Lab_Install', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Lab_Install', 'deactivate' ) );

/* ── Deferred Permalink Flush (runs after CPTs register on first init) ── */
add_action( 'init', 'labeng_maybe_flush_rewrites', 99 );
function labeng_maybe_flush_rewrites() {
    if ( get_option( 'labeng_flush_rewrites' ) ) {
        delete_option( 'labeng_flush_rewrites' );
        flush_rewrite_rules();
    }
}

/* ── Init Classes ──────────────────────────────────────────── */
add_action( 'init', 'labeng_core_init' );
function labeng_core_init() {
    if ( ! get_option( 'labeng_hours_fixed_v3' ) ) {
        $posts = get_posts( array( 'post_type' => 'lab_business', 'numberposts' => -1 ) );
        $days = array('monday','tuesday','wednesday','thursday','friday');
        foreach ( $posts as $p ) {
            foreach($days as $d) {
                update_post_meta( $p->ID, '_lab_avail_' . $d . '_open', '09:00' );
                update_post_meta( $p->ID, '_lab_avail_' . $d . '_close', '17:00' );
            }
        }
        update_option( 'labeng_hours_fixed_v3', 1 );
    }
}

function lab_log_payment( $message, $data = array() ) {
    $logs = get_option( 'labeng_payment_logs', array() );
    if ( ! is_array( $logs ) ) {
        $logs = array();
    }
    array_unshift( $logs, array(
        'time'    => current_time( 'mysql' ),
        'message' => $message,
        'data'    => $data,
    ) );
    // Keep only last 100 logs
    if ( count( $logs ) > 100 ) {
        $logs = array_slice( $logs, 0, 100 );
    }
    update_option( 'labeng_payment_logs', $logs );
}

// Check if WooCommerce is active
Lab_Roles::init();
Lab_Auth::init();
Lab_Business_CPT::init();
Lab_Deals_CPT::init();
Lab_Availability::init();
Lab_Bookings::init();
Lab_Commissions::init();
Lab_Reviews::init();
Lab_Email::init();
Lab_Shortcodes::init();

/* ── Enqueue Assets ────────────────────────────────────────── */
add_action( 'wp_enqueue_scripts', 'labeng_enqueue_assets' );
function labeng_enqueue_assets() {
    wp_enqueue_style(
        'labeng-fonts',
        'https://fonts.googleapis.com/css2?family=Great+Vibes&family=Inter:wght@400;500;600;700&display=swap',
        array(),
        null
    );

    $css_path = LABENG_PATH . 'assets/css/labeng.css';
    $css_ver  = file_exists( $css_path ) ? filemtime( $css_path ) : LABENG_VERSION;
    wp_enqueue_style(
        'labeng-css',
        LABENG_URL . 'assets/css/labeng.css',
        array(),
        $css_ver
    );

    $business_id = 0;
    if ( is_user_logged_in() ) {
        $user_id = get_current_user_id();
        $biz = get_posts( array(
            'post_type'  => 'lab_business',
            'meta_key'   => '_lab_owner_id',
            'meta_value' => $user_id,
            'numberposts' => 1,
            'post_status' => array( 'publish', 'draft', 'pending' ),
            'fields'     => 'ids',
        ) );
        if ( ! empty( $biz ) ) {
            $business_id = $biz[0];
        }
    }

    $user = wp_get_current_user();
    $roles = $user->roles;
    $role  = ! empty( $roles ) ? $roles[0] : '';

    $js_path = LABENG_PATH . 'assets/js/labeng.js';
    $js_ver  = file_exists( $js_path ) ? filemtime( $js_path ) : LABENG_VERSION;
    wp_enqueue_script( 'gsap', 'https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js', array(), '3.12.2', true );
    wp_enqueue_script( 'labeng-js', LABENG_URL . 'assets/js/labeng.js', array( 'jquery', 'gsap' ), $js_ver, true );

    $current_user = wp_get_current_user();
    wp_localize_script( 'labeng-js', 'labVars', array(
        'ajaxurl'        => admin_url( 'admin-ajax.php' ),
        'nonce'          => wp_create_nonce( 'lab_nonce' ),
        'user_id'        => get_current_user_id(),
        'user_role'      => $role,
        'user_name'      => $current_user->exists() ? $current_user->display_name : '',
        'user_email'     => $current_user->exists() ? $current_user->user_email : '',
        'business_id'    => $business_id,
        'currency_symbol'=> get_option( 'lab_currency_symbol', '£' ),
        'currency'       => get_option( 'lab_currency', 'GBP' ),
        'plugin_url'     => LABENG_URL,
        'home_url'       => home_url(),
        'login_url'      => home_url( '/login/' ),
    ) );

    /* Enqueue Stripe.js when key is configured */
    $stripe_pub    = get_option( 'lab_stripe_publishable', '' );
    $paypal_client = get_option( 'lab_paypal_client_id', '' );
    if ( $stripe_pub ) {
        wp_enqueue_script( 'stripe-js', 'https://js.stripe.com/v3/', array(), null, true );
    }
    if ( $paypal_client ) {
        $currency = strtolower( get_option( 'lab_currency', 'GBP' ) );
        wp_enqueue_script( 'paypal-sdk', 'https://www.paypal.com/sdk/js?client-id=' . esc_attr( $paypal_client ) . '&currency=' . esc_attr( $currency ), array(), null, true );
    }
    wp_localize_script( 'labeng-js', 'labPaymentVars', array(
        'stripe_pub_key'   => $stripe_pub,
        'paypal_client_id' => $paypal_client,
    ) );

    /* Enqueue WP Media for business owners */
    if ( is_user_logged_in() && current_user_can( 'upload_files' ) ) {
        wp_enqueue_media();
    }
}

/* ── Template Loader ───────────────────────────────────────── */
add_filter( 'template_include', 'labeng_template_include', 99 );

/**
 * Locate plugin templates, allowing child/parent theme overrides.
 * Checks active_theme/labeng/{$template_name} first before falling back to plugin templates.
 */
function lab_locate_template( $template_name ) {
    $theme_template = locate_template( array( 'labeng/' . $template_name ) );
    if ( $theme_template ) {
        return $theme_template;
    }
    $plugin_template = LABENG_PATH . 'templates/' . $template_name;
    if ( file_exists( $plugin_template ) ) {
        return $plugin_template;
    }
    return '';
}

/**
 * Load the global header template, checking active theme overrides first.
 */
function labeng_get_header() {
    $header = lab_locate_template( 'global/header.php' );
    if ( $header ) {
        include $header;
    }
}

/**
 * Load the global footer template, checking active theme overrides first.
 */
function labeng_get_footer() {
    $footer = lab_locate_template( 'global/footer.php' );
    if ( $footer ) {
        include $footer;
    }
}

function labeng_template_include( $template ) {
    /* 1. Home Page */
    if ( is_front_page() || is_page( 'labeng-home' ) ) {
        $custom = lab_locate_template( 'public/home.php' );
        if ( $custom ) return $custom;
    }
    
    /* 2. Login Page */
    if ( is_page( 'login' ) ) {
        $custom = lab_locate_template( 'public/login.php' );
        if ( $custom ) return $custom;
    }
    
    /* 3. Register Page */
    if ( is_page( 'register' ) || is_page( 'business-register' ) ) {
        $custom = lab_locate_template( 'public/register.php' );
        if ( $custom ) return $custom;
    }
    
    /* 4. Partner Page */
    if ( is_page( 'partner' ) || is_page( 'partner-with-us' ) ) {
        $custom = lab_locate_template( 'public/partner.php' );
        if ( $custom ) return $custom;
    }
    
    /* 5. Business Dashboard */
    if ( is_page( 'business-dashboard' ) ) {
        $custom = lab_locate_template( 'business/dashboard-page.php' );
        if ( $custom ) return $custom;
    }
    
    /* 6. Customer Dashboard */
    if ( is_page( 'customer-dashboard' ) ) {
        $custom = lab_locate_template( 'customer/dashboard-page.php' );
        if ( $custom ) return $custom;
    }

    /* 7. Single Business */
    if ( is_singular( 'lab_business' ) ) {
        $custom = lab_locate_template( 'public/business-single.php' );
        if ( $custom ) return $custom;
    }

    /* 8. Archives */
    if ( is_post_type_archive( 'lab_business' ) ) {
        $custom = lab_locate_template( 'public/business-archive.php' );
        if ( $custom ) return $custom;
    }
    if ( is_post_type_archive( 'lab_deal' ) ) {
        $custom = lab_locate_template( 'public/deal-archive.php' );
        if ( $custom ) return $custom;
    }

    return $template;
}
add_action( 'admin_init', 'lab_insert_demo_data_once' );
function lab_insert_demo_data_once() {
    if ( get_transient( 'lab_demo_imported' ) ) {
        return;
    }

    $mock_businesses = [
        [
            'title' => 'The Grooming Lounge',
            'cat' => 'beauty',
            'city' => 'London',
            'postcode' => 'NQ 4AB',
            'thumb' => 'https://images.unsplash.com/photo-1503951914875-452162b0f3f1?auto=format&fit=crop&w=600&q=90',
            'services' => [
                ['name' => 'Haircut', 'duration' => '30', 'price' => '25.00'],
                ['name' => 'Beard Trim', 'duration' => '15', 'price' => '15.00']
            ]
        ],
        [
            'title' => 'Luigi\'s Pizza',
            'cat' => 'food-drink',
            'city' => 'Manchester',
            'postcode' => 'NQ 4AB',
            'thumb' => 'https://images.unsplash.com/photo-1574071318508-1cdbab80d002?auto=format&fit=crop&w=600&q=90',
            'services' => [
                ['name' => 'Table Reservation', 'duration' => '120', 'price' => '0.00'],
            ]
        ],
        [
            'title' => 'IronWorks Gym',
            'cat' => 'fitness',
            'city' => 'London',
            'postcode' => 'NQ 4AB',
            'thumb' => 'https://images.unsplash.com/photo-1571902943202-507ec2618e8f?auto=format&fit=crop&w=600&q=90',
            'services' => [
                ['name' => 'Personal Training', 'duration' => '60', 'price' => '50.00']
            ]
        ],
        [
            'title' => 'Elite Car Hire',
            'cat' => 'services',
            'city' => 'Birmingham',
            'postcode' => 'NQ 4AB',
            'thumb' => 'https://images.unsplash.com/photo-1503736334956-4c8f8e92946d?auto=format&fit=crop&w=600&q=90',
            'services' => [
                ['name' => 'Daily Rental', 'duration' => '1440', 'price' => '120.00']
            ]
        ]
    ];

    foreach ($mock_businesses as $biz) {
        $exists = get_page_by_title($biz['title'], OBJECT, 'lab_business');
        if ($exists) continue;

        $post_id = wp_insert_post([
            'post_title' => $biz['title'],
            'post_type' => 'lab_business',
            'post_status' => 'publish'
        ]);

        if (!is_wp_error($post_id)) {
            update_post_meta($post_id, '_lab_status', 'approved');
            update_post_meta($post_id, '_lab_city', $biz['city']);
            update_post_meta($post_id, '_lab_postcode', $biz['postcode']);
            update_post_meta($post_id, '_lab_services', wp_json_encode($biz['services']));
            
            // Note: downloading side-loaded featured image programmatically is tricky in a brief script.
            // We will just store the URL in a meta field and update the template if it has no featured image.
            update_post_meta($post_id, '_lab_mock_thumb', $biz['thumb']);
            
            wp_set_object_terms($post_id, $biz['cat'], 'lab_category');
        }
    }
    set_transient( 'lab_demo_imported', true, YEAR_IN_SECONDS );
}

add_action( 'admin_init', 'labeng_check_db_upgrade' );
function labeng_check_db_upgrade() {
    global $wpdb;
    $needs_upgrade = get_option( 'labeng_db_version' ) !== LABENG_VERSION;
    $missing_email_table = $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}lab_email_logs'" ) !== "{$wpdb->prefix}lab_email_logs";
    if ( $needs_upgrade || $missing_email_table ) {
        Lab_Install::activate();
    }
}

/* ── Admin Menu ────────────────────────────────────────────── */
add_action( 'admin_menu', 'labeng_admin_menu' );
function labeng_admin_menu() {
    add_menu_page(
        __( 'Labeng', 'labeng' ),
        __( 'Labeng', 'labeng' ),
        'manage_options',
        'labeng-dashboard',
        'labeng_admin_dashboard_page',
        'dashicons-store',
        25
    );

    add_submenu_page(
        'labeng-dashboard',
        __( 'Dashboard', 'labeng' ),
        __( 'Dashboard', 'labeng' ),
        'manage_options',
        'labeng-dashboard',
        'labeng_admin_dashboard_page'
    );

    add_submenu_page(
        'labeng-dashboard',
        __( 'Bookings', 'labeng' ),
        __( 'Bookings', 'labeng' ),
        'manage_options',
        'labeng-bookings',
        'labeng_admin_bookings_page'
    );

    add_submenu_page(
        'labeng-dashboard',
        __( 'Commissions', 'labeng' ),
        __( 'Commissions', 'labeng' ),
        'manage_options',
        'labeng-commissions',
        'labeng_admin_commissions_page'
    );

    add_submenu_page(
        'labeng-dashboard',
        __( 'Settings', 'labeng' ),
        __( 'Settings', 'labeng' ),
        'manage_options',
        'labeng-settings',
        'labeng_admin_settings_page'
    );

    add_submenu_page(
        'labeng-dashboard',
        __( 'Payment Settings', 'labeng' ),
        __( 'Payment Settings', 'labeng' ),
        'manage_options',
        'labeng-payment-settings',
        'labeng_admin_payment_settings_page'
    );

    add_submenu_page(
        'labeng-dashboard',
        __( 'Email Logs', 'labeng' ),
        __( 'Email Logs', 'labeng' ),
        'manage_options',
        'labeng-email-logs',
        'labeng_admin_email_logs_page'
    );
}

function labeng_admin_payment_settings_page() {
    require_once LABENG_PATH . 'admin/payment-settings.php';
}

function labeng_admin_email_logs_page() {
    require_once LABENG_PATH . 'admin/email-logs.php';
}

function labeng_admin_dashboard_page() {
    global $wpdb;
    $bookings_table = $wpdb->prefix . 'lab_bookings';
    $total_businesses = wp_count_posts( 'lab_business' )->publish;
    $pending_businesses = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = '_lab_status' AND meta_value = 'pending'" );
    $total_bookings = $wpdb->get_var( "SELECT COUNT(*) FROM {$bookings_table}" );
    $earnings         = Lab_Bookings::get_earnings();
    $total_revenue    = $earnings['revenue'];
    $total_commission = $earnings['commission'];
    $total_payouts    = $earnings['net'];
    $cs = get_option( 'lab_currency_symbol', '£' );

    $current_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'overview';

    // Handle Admin Quick actions inside custom dashboard
    if ( isset( $_GET['lab_admin_action'] ) && current_user_can( 'manage_options' ) ) {
        $action = sanitize_text_field( $_GET['lab_admin_action'] );
        $post_id = absint( $_GET['post_id'] ?? 0 );
        if ( $post_id > 0 ) {
            if ( $action === 'approve' ) {
                check_admin_referer( 'lab_approve_dashboard_' . $post_id );
                update_post_meta( $post_id, '_lab_status', 'approved' );
                wp_update_post( array( 'ID' => $post_id, 'post_status' => 'publish' ) );
                $owner_id = get_post_meta( $post_id, '_lab_owner_id', true );
                if ( $owner_id ) {
                    $user = new WP_User( $owner_id );
                    $user->set_role( 'business_owner' );
                }
                Lab_Commissions::ensure_commission( $post_id );
                Lab_Email::lab_email_business_approved( $post_id );
                echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Business approved successfully.', 'labeng' ) . '</p></div>';
            } elseif ( $action === 'suspend' ) {
                check_admin_referer( 'lab_suspend_dashboard_' . $post_id );
                update_post_meta( $post_id, '_lab_status', 'suspended' );
                wp_update_post( array( 'ID' => $post_id, 'post_status' => 'draft' ) );
                echo '<div class="notice notice-warning is-dismissible"><p>' . esc_html__( 'Business suspended.', 'labeng' ) . '</p></div>';
            }
        }
    }
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Labeng Admin Management', 'labeng' ); ?></h1>
        
        <h2 class="nav-tab-wrapper" style="margin-bottom:20px;">
            <a href="?page=labeng-dashboard&tab=overview" class="nav-tab <?php echo $current_tab === 'overview' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Overview', 'labeng' ); ?></a>
            <a href="?page=labeng-dashboard&tab=registrations" class="nav-tab <?php echo $current_tab === 'registrations' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Pending Registrations', 'labeng' ); ?> (<?php echo esc_html($pending_businesses); ?>)</a>
            <a href="?page=labeng-dashboard&tab=members" class="nav-tab <?php echo $current_tab === 'members' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Registered Members', 'labeng' ); ?></a>
            <a href="?page=labeng-dashboard&tab=listings" class="nav-tab <?php echo $current_tab === 'listings' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'All Listings', 'labeng' ); ?></a>
        </h2>

        <?php if ( $current_tab === 'overview' ) : ?>
            <div style="display:flex;gap:20px;flex-wrap:wrap;margin-top:20px;">
                <div style="background:#1a1a2e;color:#fff;padding:24px 32px;border-radius:12px;min-width:200px;">
                    <div style="font-size:13px;color:#888;">Active Businesses</div>
                    <div style="font-size:32px;font-weight:600;"><?php echo esc_html( $total_businesses ); ?></div>
                </div>
                <div style="background:#1a1a2e;color:#fff;padding:24px 32px;border-radius:12px;min-width:200px;">
                    <div style="font-size:13px;color:#888;">Pending Approval</div>
                    <div style="font-size:32px;font-weight:600;"><?php echo esc_html( $pending_businesses ); ?></div>
                </div>
                <div style="background:#1a1a2e;color:#fff;padding:24px 32px;border-radius:12px;min-width:200px;">
                    <div style="font-size:13px;color:#888;">Total Bookings</div>
                    <div style="font-size:32px;font-weight:600;"><?php echo esc_html( $total_bookings ); ?></div>
                </div>
                <div style="background:#1a1a2e;color:#fff;padding:24px 32px;border-radius:12px;min-width:200px;">
                    <div style="font-size:13px;color:#888;">Total Revenue</div>
                    <div style="font-size:32px;font-weight:600;"><?php echo esc_html( $cs . number_format( $total_revenue, 2 ) ); ?></div>
                    <div style="font-size:11px;color:#666;margin-top:4px;">Paid &amp; completed bookings</div>
                </div>
                <div style="background:linear-gradient(135deg,#16314d 0%,#0f1f33 100%);color:#fff;padding:24px 32px;border-radius:12px;min-width:200px;">
                    <div style="font-size:13px;color:#9ec5fe;">Platform Commission</div>
                    <div style="font-size:32px;font-weight:600;color:#4d94ff;"><?php echo esc_html( $cs . number_format( $total_commission, 2 ) ); ?></div>
                    <div style="font-size:11px;color:#6b8fb5;margin-top:4px;">Your earnings</div>
                </div>
                <div style="background:#1a1a2e;color:#fff;padding:24px 32px;border-radius:12px;min-width:200px;">
                    <div style="font-size:13px;color:#888;">Business Payouts</div>
                    <div style="font-size:32px;font-weight:600;"><?php echo esc_html( $cs . number_format( $total_payouts, 2 ) ); ?></div>
                    <div style="font-size:11px;color:#666;margin-top:4px;">Owed to owners (net)</div>
                </div>
            </div>

        <?php elseif ( $current_tab === 'registrations' ) : ?>
            <h2><?php esc_html_e( 'Pending Business Registrations', 'labeng' ); ?></h2>
            <?php
            $pending_query = new WP_Query( array(
                'post_type' => 'lab_business',
                'post_status' => array( 'publish', 'draft', 'pending' ),
                'meta_query' => array(
                    array(
                        'key' => '_lab_status',
                        'value' => 'pending',
                    )
                ),
                'posts_per_page' => -1,
            ) );
            if ( $pending_query->have_posts() ) : ?>
                <table class="wp-list-table widefat fixed striped table-view-list">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Business Name', 'labeng' ); ?></th>
                            <th><?php esc_html_e( 'Category', 'labeng' ); ?></th>
                            <th><?php esc_html_e( 'Owner Details', 'labeng' ); ?></th>
                            <th><?php esc_html_e( 'Location', 'labeng' ); ?></th>
                            <th><?php esc_html_e( 'Date Registered', 'labeng' ); ?></th>
                            <th><?php esc_html_e( 'Actions', 'labeng' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ( $pending_query->have_posts() ) : $pending_query->the_post();
                            $post_id = get_the_ID();
                            $phone = get_post_meta( $post_id, '_lab_phone', true );
                            $email = get_post_meta( $post_id, '_lab_email', true );
                            $city = get_post_meta( $post_id, '_lab_city', true );
                            $postcode = get_post_meta( $post_id, '_lab_postcode', true );
                            
                            $cats = get_the_terms( $post_id, 'lab_category' );
                            $cat_name = $cats && ! is_wp_error( $cats ) ? $cats[0]->name : '—';
                            
                            $owner_id = get_post_meta( $post_id, '_lab_owner_id', true );
                            $owner = get_userdata( $owner_id );
                            $owner_name = $owner ? $owner->display_name : '—';

                            $approve_url = wp_nonce_url(
                                admin_url( 'admin.php?page=labeng-dashboard&tab=registrations&lab_admin_action=approve&post_id=' . $post_id ),
                                'lab_approve_dashboard_' . $post_id
                            );
                            $suspend_url = wp_nonce_url(
                                admin_url( 'admin.php?page=labeng-dashboard&tab=registrations&lab_admin_action=suspend&post_id=' . $post_id ),
                                'lab_suspend_dashboard_' . $post_id
                            );
                        ?>
                            <tr>
                                <td><strong><a href="<?php echo esc_url( get_edit_post_link( $post_id ) ); ?>"><?php the_title(); ?></a></strong></td>
                                <td><?php echo esc_html( $cat_name ); ?></td>
                                <td>
                                    <div><strong>Name:</strong> <?php echo esc_html( $owner_name ); ?></div>
                                    <div><strong>Email:</strong> <?php echo esc_html( $email ); ?></div>
                                    <div><strong>Phone:</strong> <?php echo esc_html( $phone ); ?></div>
                                </td>
                                <td><?php echo esc_html( $city . ' (' . $postcode . ')' ); ?></td>
                                <td><?php echo esc_html( get_the_date() ); ?></td>
                                <td>
                                    <a href="<?php echo esc_url( $approve_url ); ?>" class="button button-primary" style="background:#198754; border-color:#198754;"><?php esc_html_e( 'Approve', 'labeng' ); ?></a>
                                    <a href="<?php echo esc_url( $suspend_url ); ?>" class="button button-link-delete" style="color:#dc3545; margin-left:10px;"><?php esc_html_e( 'Reject', 'labeng' ); ?></a>
                                </td>
                            </tr>
                        <?php endwhile; wp_reset_postdata(); ?>
                    </tbody>
                </table>
            <?php else : ?>
                <p><?php esc_html_e( 'No pending registrations at the moment.', 'labeng' ); ?></p>
            <?php endif; ?>

        <?php elseif ( $current_tab === 'members' ) : ?>
            <h2><?php esc_html_e( 'Registered Members Directory', 'labeng' ); ?></h2>
            <?php
            $members_query = new WP_User_Query( array(
                'role__in' => array( 'customer', 'business_owner', 'subscriber' ),
                'orderby' => 'registered',
                'order' => 'DESC',
            ) );
            $members = $members_query->get_results();
            if ( ! empty( $members ) ) : ?>
                <table class="wp-list-table widefat fixed striped table-view-list">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Username', 'labeng' ); ?></th>
                            <th><?php esc_html_e( 'Name', 'labeng' ); ?></th>
                            <th><?php esc_html_e( 'Email', 'labeng' ); ?></th>
                            <th><?php esc_html_e( 'Platform Role', 'labeng' ); ?></th>
                            <th><?php esc_html_e( 'Active Listings', 'labeng' ); ?></th>
                            <th><?php esc_html_e( 'Joined Date', 'labeng' ); ?></th>
                            <th><?php esc_html_e( 'Actions', 'labeng' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $members as $u ) : 
                            $listings = get_posts( array(
                                'post_type' => 'lab_business',
                                'author' => $u->ID,
                                'post_status' => 'any',
                                'numberposts' => -1,
                            ) );
                            $listings_count = count( $listings );
                            
                            $role_labels = array(
                                'customer' => 'Customer / Client',
                                'business_owner' => 'Business Owner (Approved)',
                                'subscriber' => 'Subscriber (Pending Partner)'
                            );
                            $role_slug = ! empty( $u->roles ) ? $u->roles[0] : '—';
                            $role_display = isset( $role_labels[ $role_slug ] ) ? $role_labels[ $role_slug ] : ucfirst( $role_slug );
                        ?>
                            <tr>
                                <td><strong><?php echo esc_html( $u->user_login ); ?></strong></td>
                                <td><?php echo esc_html( $u->first_name . ' ' . $u->last_name ); ?></td>
                                <td><a href="mailto:<?php echo esc_attr( $u->user_email ); ?>"><?php echo esc_html( $u->user_email ); ?></a></td>
                                <td>
                                    <span style="font-weight:600; color:<?php echo $role_slug === 'business_owner' ? '#198754' : ($role_slug === 'subscriber' ? '#f79e1b' : '#0d6efd'); ?>;">
                                        <?php echo esc_html( $role_display ); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ( $listings_count > 0 ) : ?>
                                        <a href="?page=labeng-dashboard&tab=listings&owner_filter=<?php echo esc_attr($u->ID); ?>" class="button button-small"><?php echo esc_html( $listings_count ); ?> Listings</a>
                                    <?php else : ?>
                                        <span class="description">—</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html( date( 'F j, Y', strtotime( $u->user_registered ) ) ); ?></td>
                                <td>
                                    <a href="<?php echo esc_url( get_edit_user_link( $u->ID ) ); ?>" class="button button-secondary"><?php esc_html_e( 'Edit Profile', 'labeng' ); ?></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <p><?php esc_html_e( 'No members found.', 'labeng' ); ?></p>
            <?php endif; ?>

        <?php elseif ( $current_tab === 'listings' ) : ?>
            <h2><?php esc_html_e( 'All Marketplace Listings', 'labeng' ); ?></h2>
            <?php
            $args = array(
                'post_type' => 'lab_business',
                'post_status' => 'any',
                'posts_per_page' => -1,
            );
            if ( isset( $_GET['owner_filter'] ) ) {
                $args['author'] = absint( $_GET['owner_filter'] );
            }
            $listings_query = new WP_Query( $args );
            if ( $listings_query->have_posts() ) : ?>
                <table class="wp-list-table widefat fixed striped table-view-list">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Business Name', 'labeng' ); ?></th>
                            <th><?php esc_html_e( 'Category', 'labeng' ); ?></th>
                            <th><?php esc_html_e( 'Owner / Author', 'labeng' ); ?></th>
                            <th><?php esc_html_e( 'City', 'labeng' ); ?></th>
                            <th><?php esc_html_e( 'Postcode', 'labeng' ); ?></th>
                            <th><?php esc_html_e( 'Approval Status', 'labeng' ); ?></th>
                            <th><?php esc_html_e( 'Actions', 'labeng' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ( $listings_query->have_posts() ) : $listings_query->the_post();
                            $post_id = get_the_ID();
                            $city = get_post_meta( $post_id, '_lab_city', true );
                            $postcode = get_post_meta( $post_id, '_lab_postcode', true );
                            $status = get_post_meta( $post_id, '_lab_status', true ) ?: 'pending';
                            
                            $cats = get_the_terms( $post_id, 'lab_category' );
                            $cat_name = $cats && ! is_wp_error( $cats ) ? $cats[0]->name : '—';
                            
                            $owner_id = get_post_meta( $post_id, '_lab_owner_id', true );
                            $owner = get_userdata( $owner_id );
                            $owner_name = $owner ? $owner->display_name : '—';

                            $approve_url = wp_nonce_url(
                                admin_url( 'admin.php?page=labeng-dashboard&tab=listings&lab_admin_action=approve&post_id=' . $post_id ),
                                'lab_approve_dashboard_' . $post_id
                            );
                            $suspend_url = wp_nonce_url(
                                admin_url( 'admin.php?page=labeng-dashboard&tab=listings&lab_admin_action=suspend&post_id=' . $post_id ),
                                'lab_suspend_dashboard_' . $post_id
                            );
                        ?>
                            <tr>
                                <td><strong><a href="<?php echo esc_url( get_edit_post_link( $post_id ) ); ?>"><?php the_title(); ?></a></strong></td>
                                <td><?php echo esc_html( $cat_name ); ?></td>
                                <td><?php echo esc_html( $owner_name ); ?></td>
                                <td><?php echo esc_html( $city ); ?></td>
                                <td><?php echo esc_html( $postcode ); ?></td>
                                <td>
                                    <span class="lab-status-badge" style="
                                        display:inline-block;
                                        padding:4px 10px;
                                        border-radius:20px;
                                        font-size:11px;
                                        font-weight:600;
                                        background:<?php echo $status === 'approved' ? 'rgba(25,135,84,0.15)' : ($status === 'suspended' ? 'rgba(220,53,69,0.15)' : 'rgba(247,158,27,0.15)'); ?>;
                                        color:<?php echo $status === 'approved' ? '#198754' : ($status === 'suspended' ? '#dc3545' : '#f79e1b'); ?>;
                                    ">
                                        <?php echo esc_html( ucfirst($status) ); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="<?php echo esc_url( get_edit_post_link( $post_id ) ); ?>" class="button button-small"><?php esc_html_e( 'Edit CPT', 'labeng' ); ?></a>
                                    <?php if ( $status !== 'approved' ) : ?>
                                        <a href="<?php echo esc_url( $approve_url ); ?>" class="button button-small" style="color:#198754; margin-left:5px; font-weight:600;"><?php esc_html_e( 'Approve', 'labeng' ); ?></a>
                                    <?php endif; ?>
                                    <?php if ( $status !== 'suspended' ) : ?>
                                        <a href="<?php echo esc_url( $suspend_url ); ?>" class="button button-small" style="color:#dc3545; margin-left:5px;"><?php esc_html_e( 'Suspend', 'labeng' ); ?></a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; wp_reset_postdata(); ?>
                    </tbody>
                </table>
            <?php else : ?>
                <p><?php esc_html_e( 'No business listings found.', 'labeng' ); ?></p>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <?php
}

function labeng_admin_bookings_page() {
    require_once LABENG_PATH . 'admin/bookings-page.php';
}

function labeng_admin_commissions_page() {
    require_once LABENG_PATH . 'admin/commissions-page.php';
}

/* ── Settings Page (Currency) ──────────────────────────────── */
function labeng_admin_settings_page() {
    if ( isset( $_POST['lab_settings_nonce'] ) && wp_verify_nonce( $_POST['lab_settings_nonce'], 'lab_save_settings' ) ) {
        update_option( 'lab_currency', sanitize_text_field( $_POST['lab_currency'] ) );
        update_option( 'lab_currency_symbol', sanitize_text_field( $_POST['lab_currency_symbol'] ) );
        update_option( 'lab_social_facebook', sanitize_text_field( $_POST['lab_social_facebook'] ) );
        update_option( 'lab_social_twitter', sanitize_text_field( $_POST['lab_social_twitter'] ) );
        update_option( 'lab_social_instagram', sanitize_text_field( $_POST['lab_social_instagram'] ) );
        update_option( 'lab_social_linkedin', sanitize_text_field( $_POST['lab_social_linkedin'] ) );
        update_option( 'lab_default_commission_type', sanitize_text_field( $_POST['lab_default_commission_type'] ?? 'percentage' ) );
        update_option( 'lab_default_commission_value', floatval( $_POST['lab_default_commission_value'] ?? 10 ) );
        echo '<div class="notice notice-success"><p>' . esc_html__( 'Settings saved.', 'labeng' ) . '</p></div>';
    }
    $currency        = get_option( 'lab_currency', 'GBP' );
    $currency_symbol = get_option( 'lab_currency_symbol', '£' );
    $facebook        = get_option( 'lab_social_facebook', '' );
    $twitter         = get_option( 'lab_social_twitter', '' );
    $instagram       = get_option( 'lab_social_instagram', '' );
    $linkedin        = get_option( 'lab_social_linkedin', '' );
    $def_comm_type   = get_option( 'lab_default_commission_type', 'percentage' );
    $def_comm_value  = get_option( 'lab_default_commission_value', 10 );
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Labeng Settings', 'labeng' ); ?></h1>
        <form method="post">
            <?php wp_nonce_field( 'lab_save_settings', 'lab_settings_nonce' ); ?>
            <h2>General Settings</h2>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="lab_currency"><?php esc_html_e( 'Currency Code', 'labeng' ); ?></label></th>
                    <td><input type="text" id="lab_currency" name="lab_currency" value="<?php echo esc_attr( $currency ); ?>" class="regular-text" placeholder="GBP" /></td>
                </tr>
                <tr>
                    <th scope="row"><label for="lab_currency_symbol"><?php esc_html_e( 'Currency Symbol', 'labeng' ); ?></label></th>
                    <td><input type="text" id="lab_currency_symbol" name="lab_currency_symbol" value="<?php echo esc_attr( $currency_symbol ); ?>" class="regular-text" placeholder="£" /></td>
                </tr>
            </table>

            <h2><?php esc_html_e( 'Default Commission', 'labeng' ); ?></h2>
            <p class="description" style="margin-bottom:12px;"><?php esc_html_e( 'Applied automatically to every business on approval, unless a custom commission is set for that business.', 'labeng' ); ?></p>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="lab_default_commission_type"><?php esc_html_e( 'Commission Type', 'labeng' ); ?></label></th>
                    <td>
                        <select id="lab_default_commission_type" name="lab_default_commission_type">
                            <option value="percentage" <?php selected( $def_comm_type, 'percentage' ); ?>><?php esc_html_e( 'Percentage (%)', 'labeng' ); ?></option>
                            <option value="fixed" <?php selected( $def_comm_type, 'fixed' ); ?>><?php esc_html_e( 'Fixed Amount', 'labeng' ); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="lab_default_commission_value"><?php esc_html_e( 'Commission Value', 'labeng' ); ?></label></th>
                    <td>
                        <input type="number" id="lab_default_commission_value" name="lab_default_commission_value" value="<?php echo esc_attr( $def_comm_value ); ?>" step="0.01" min="0" class="regular-text" />
                        <p class="description"><?php esc_html_e( 'e.g. 10 = 10% of each completed booking, or a flat fee per booking if Fixed is selected.', 'labeng' ); ?></p>
                    </td>
                </tr>
            </table>

            <h2>Social Media Links</h2>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="lab_social_facebook"><?php esc_html_e( 'Facebook URL', 'labeng' ); ?></label></th>
                    <td><input type="url" id="lab_social_facebook" name="lab_social_facebook" value="<?php echo esc_attr( $facebook ); ?>" class="regular-text" placeholder="https://facebook.com/..." /></td>
                </tr>
                <tr>
                    <th scope="row"><label for="lab_social_twitter"><?php esc_html_e( 'Twitter / X URL', 'labeng' ); ?></label></th>
                    <td><input type="url" id="lab_social_twitter" name="lab_social_twitter" value="<?php echo esc_attr( $twitter ); ?>" class="regular-text" placeholder="https://x.com/..." /></td>
                </tr>
                <tr>
                    <th scope="row"><label for="lab_social_instagram"><?php esc_html_e( 'Instagram URL', 'labeng' ); ?></label></th>
                    <td><input type="url" id="lab_social_instagram" name="lab_social_instagram" value="<?php echo esc_attr( $instagram ); ?>" class="regular-text" placeholder="https://instagram.com/..." /></td>
                </tr>
                <tr>
                    <th scope="row"><label for="lab_social_linkedin"><?php esc_html_e( 'LinkedIn URL', 'labeng' ); ?></label></th>
                    <td><input type="url" id="lab_social_linkedin" name="lab_social_linkedin" value="<?php echo esc_attr( $linkedin ); ?>" class="regular-text" placeholder="https://linkedin.com/in/..." /></td>
                </tr>
            </table>
            <?php submit_button( __( 'Save Settings', 'labeng' ) ); ?>
        </form>
    </div>
    <?php
}

/* ── Admin Enqueue ─────────────────────────────────────────── */
add_action( 'admin_enqueue_scripts', 'labeng_admin_enqueue' );
function labeng_admin_enqueue( $hook ) {
    // Only load minimal styles if really needed, but avoid global overrides.
}
