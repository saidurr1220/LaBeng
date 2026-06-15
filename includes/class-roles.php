<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class Lab_Roles
 * Registers custom user roles and handles login redirect logic.
 */
class Lab_Roles {

    public static function init() {
        add_action( 'login_redirect', array( __CLASS__, 'login_redirect' ), 10, 3 );
        add_action( 'wp_logout',      array( __CLASS__, 'logout_redirect' ) );
    }

    /**
     * Register roles on activation.
     */
    public static function register_roles() {
        /* Customer role */
        remove_role( 'customer' );
        add_role( 'customer', __( 'Customer', 'labeng' ), array(
            'read'              => true,
            'lab_make_bookings' => true,
            'lab_write_reviews' => true,
        ) );

        /* Business Owner role */
        remove_role( 'business_owner' );
        add_role( 'business_owner', __( 'Business Owner', 'labeng' ), array(
            'read'                      => true,
            'upload_files'              => true,
            'lab_manage_own_business'    => true,
            'lab_manage_own_services'    => true,
            'lab_manage_own_deals'       => true,
            'lab_manage_own_availability'=> true,
            'lab_view_own_bookings'      => true,
        ) );
    }

    /**
     * Redirect users to their respective dashboards after login.
     */
    public static function login_redirect( $redirect_to, $requested, $user ) {
        if ( is_wp_error( $user ) || ! is_a( $user, 'WP_User' ) ) {
            return $redirect_to;
        }

        if ( in_array( 'administrator', $user->roles, true ) ) {
            return admin_url();
        }
        if ( in_array( 'business_owner', $user->roles, true ) ) {
            return home_url( '/business-dashboard/' );
        }
        if ( in_array( 'customer', $user->roles, true ) ) {
            return home_url( '/customer-dashboard/' );
        }

        return $redirect_to;
    }

    /**
     * Redirect to home on logout.
     */
    public static function logout_redirect() {
        wp_safe_redirect( home_url( '/login/' ) );
        exit;
    }
}
