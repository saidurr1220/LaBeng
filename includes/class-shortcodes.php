<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class Lab_Shortcodes
 * Central registration of all shortcodes.
 */
class Lab_Shortcodes {

    public static function init() {
        add_shortcode( 'lab_register_form',          array( 'Lab_Auth', 'render_register_form' ) );
        add_shortcode( 'lab_login_form',             array( 'Lab_Auth', 'render_login_form' ) );
        add_shortcode( 'lab_register_business_form', array( 'Lab_Auth', 'render_register_business_form' ) );
        add_shortcode( 'lab_customer_dashboard',     array( __CLASS__, 'render_customer_dashboard' ) );
        add_shortcode( 'lab_business_dashboard',     array( __CLASS__, 'render_business_dashboard' ) );
    }

    /**
     * Customer Dashboard shortcode.
     */
    public static function render_customer_dashboard() {
        if ( ! is_user_logged_in() ) {
            return '<div class="lab-notice lab-notice--warning"><p>' . esc_html__( 'Please', 'labeng' ) . ' <a href="' . esc_url( home_url( '/login/' ) ) . '">' . esc_html__( 'login', 'labeng' ) . '</a> ' . esc_html__( 'to access your dashboard.', 'labeng' ) . '</p></div>';
        }

        $user = wp_get_current_user();
        if ( ! in_array( 'customer', $user->roles, true ) && ! in_array( 'administrator', $user->roles, true ) ) {
            return '<div class="lab-notice lab-notice--warning"><p>' . esc_html__( 'This dashboard is for customers only.', 'labeng' ) . '</p></div>';
        }

        ob_start();
        include LABENG_PATH . 'templates/customer/dashboard.php';
        return ob_get_clean();
    }

    /**
     * Business Dashboard shortcode.
     */
    public static function render_business_dashboard() {
        if ( ! is_user_logged_in() ) {
            return '<div class="lab-notice lab-notice--warning"><p>' . esc_html__( 'Please', 'labeng' ) . ' <a href="' . esc_url( home_url( '/login/' ) ) . '">' . esc_html__( 'login', 'labeng' ) . '</a> ' . esc_html__( 'to access your dashboard.', 'labeng' ) . '</p></div>';
        }

        $user = wp_get_current_user();
        if ( ! in_array( 'business_owner', $user->roles, true ) && ! in_array( 'administrator', $user->roles, true ) ) {
            return '<div class="lab-notice lab-notice--warning"><p>' . esc_html__( 'This dashboard is for business owners only.', 'labeng' ) . '</p></div>';
        }

        ob_start();
        include LABENG_PATH . 'templates/business/dashboard.php';
        return ob_get_clean();
    }
}
