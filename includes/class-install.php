<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class Lab_Install
 * Handles plugin activation: creates database tables and default pages.
 */
class Lab_Install {

    /**
     * Run on plugin activation.
     */
    public static function activate() {
        self::create_tables();
        self::create_pages();
        Lab_Roles::register_roles();
        /* Set flag — flush happens on next init after CPTs are registered */
        update_option( 'labeng_flush_rewrites', 1 );
        flush_rewrite_rules();
    }

    /**
     * Run on plugin deactivation.
     */
    public static function deactivate() {
        flush_rewrite_rules();
    }

    /**
     * Create custom database tables using dbDelta.
     */
    public static function create_tables() {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();

        $bookings_table     = $wpdb->prefix . 'lab_bookings';
        $commissions_table  = $wpdb->prefix . 'lab_commissions';
        $email_logs_table   = $wpdb->prefix . 'lab_email_logs';

        $sql = "CREATE TABLE {$bookings_table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            business_id BIGINT UNSIGNED NOT NULL,
            customer_id BIGINT UNSIGNED NOT NULL,
            service_name VARCHAR(255) NOT NULL,
            service_price DECIMAL(10,2) NOT NULL,
            booking_date DATE NOT NULL,
            booking_time TIME NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            total_amount DECIMAL(10,2) NOT NULL,
            commission_due DECIMAL(10,2) DEFAULT 0.00,
            payment_status VARCHAR(20) NOT NULL DEFAULT 'unpaid',
            payment_intent_id VARCHAR(255) DEFAULT NULL,
            payment_method VARCHAR(50) DEFAULT NULL,
            notes TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY business_id (business_id),
            KEY customer_id (customer_id),
            KEY status (status),
            KEY payment_status (payment_status),
            KEY booking_date (booking_date)
        ) {$charset};

        CREATE TABLE {$commissions_table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            business_id BIGINT UNSIGNED NOT NULL,
            commission_type VARCHAR(20) NOT NULL DEFAULT 'percentage',
            commission_value DECIMAL(10,2) DEFAULT 0.00,
            PRIMARY KEY (id),
            UNIQUE KEY business_id (business_id)
        ) {$charset};

        CREATE TABLE {$email_logs_table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            recipient VARCHAR(255) NOT NULL,
            subject VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'sent',
            sent_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) {$charset};";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );

        update_option( 'labeng_db_version', LABENG_VERSION );
    }

    /**
     * Create default pages with shortcodes.
     */
    private static function create_pages() {
        $pages = array(
            array(
                'title'     => 'Labeng Home',
                'slug'      => 'labeng-home',
                'shortcode' => '',
            ),
            array(
                'title'     => 'Register',
                'slug'      => 'register',
                'shortcode' => '',
            ),
            array(
                'title'     => 'Login',
                'slug'      => 'login',
                'shortcode' => '',
            ),
            array(
                'title'     => 'Customer Dashboard',
                'slug'      => 'customer-dashboard',
                'shortcode' => '',
            ),
            array(
                'title'     => 'Business Register',
                'slug'      => 'business-register',
                'shortcode' => '',
            ),
            array(
                'title'     => 'Business Dashboard',
                'slug'      => 'business-dashboard',
                'shortcode' => '',
            ),
            array(
                'title'     => 'Partner With Us',
                'slug'      => 'partner',
                'shortcode' => '',
            ),
        );

        $home_id = 0;

        foreach ( $pages as $page ) {
            $exists = get_page_by_path( $page['slug'] );
            if ( ! $exists ) {
                $post_id = wp_insert_post( array(
                    'post_title'   => $page['title'],
                    'post_name'    => $page['slug'],
                    'post_content' => $page['shortcode'],
                    'post_status'  => 'publish',
                    'post_type'    => 'page',
                    'post_author'  => 1,
                ) );
                if ( $page['slug'] === 'labeng-home' ) {
                    $home_id = $post_id;
                }
            } else {
                if ( $page['slug'] === 'labeng-home' ) {
                    $home_id = $exists->ID;
                }
            }
        }

        /* Set home page as front page */
        if ( $home_id ) {
            update_option( 'show_on_front', 'page' );
            update_option( 'page_on_front', $home_id );
        }
    }
}
