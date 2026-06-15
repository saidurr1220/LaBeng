<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class Lab_Deals_CPT
 * Registers lab_deal CPT and auto-expires deals past their valid_until date.
 */
class Lab_Deals_CPT {

    public static function init() {
        add_action( 'init', array( __CLASS__, 'register_cpt' ) );

        /* Auto-expire deals daily */
        add_action( 'wp',   array( __CLASS__, 'schedule_expiry' ) );
        add_action( 'lab_expire_deals_event', array( __CLASS__, 'expire_deals' ) );

        /* AJAX: create deal */
        add_action( 'wp_ajax_lab_create_deal',  array( __CLASS__, 'ajax_create_deal' ) );
        add_action( 'wp_ajax_lab_update_deal',  array( __CLASS__, 'ajax_update_deal' ) );
        add_action( 'wp_ajax_lab_delete_deal',  array( __CLASS__, 'ajax_delete_deal' ) );
    }

    /**
     * Register lab_deal CPT.
     */
    public static function register_cpt() {
        $labels = array(
            'name'               => __( 'Deals', 'labeng' ),
            'singular_name'      => __( 'Deal', 'labeng' ),
            'add_new'            => __( 'Add New', 'labeng' ),
            'add_new_item'       => __( 'Add New Deal', 'labeng' ),
            'edit_item'          => __( 'Edit Deal', 'labeng' ),
            'new_item'           => __( 'New Deal', 'labeng' ),
            'view_item'          => __( 'View Deal', 'labeng' ),
            'search_items'       => __( 'Search Deals', 'labeng' ),
            'not_found'          => __( 'No deals found', 'labeng' ),
            'not_found_in_trash' => __( 'No deals found in Trash', 'labeng' ),
            'all_items'          => __( 'All Deals', 'labeng' ),
            'menu_name'          => __( 'Deals', 'labeng' ),
        );

        register_post_type( 'lab_deal', array(
            'labels'       => $labels,
            'public'       => true,
            'has_archive'  => true,
            'rewrite'      => array( 'slug' => 'deals' ),
            'supports'     => array( 'title', 'editor', 'thumbnail' ),
            'menu_icon'    => 'dashicons-tickets-alt',
            'menu_position'=> 51,
            'show_in_menu' => true,
            'show_in_rest' => false,
        ) );
    }

    /**
     * Schedule daily deal expiry check.
     */
    public static function schedule_expiry() {
        if ( ! wp_next_scheduled( 'lab_expire_deals_event' ) ) {
            wp_schedule_event( time(), 'daily', 'lab_expire_deals_event' );
        }
    }

    /**
     * Expire deals past their valid_until date.
     */
    public static function expire_deals() {
        $deals = get_posts( array(
            'post_type'   => 'lab_deal',
            'numberposts' => -1,
            'meta_query'  => array(
                array(
                    'key'     => '_lab_deal_status',
                    'value'   => 'active',
                ),
                array(
                    'key'     => '_lab_deal_valid_until',
                    'value'   => current_time( 'Y-m-d' ),
                    'compare' => '<',
                    'type'    => 'DATE',
                ),
            ),
        ) );

        foreach ( $deals as $deal ) {
            update_post_meta( $deal->ID, '_lab_deal_status', 'expired' );
        }
    }

    /**
     * AJAX: Create deal.
     */
    public static function ajax_create_deal() {
        check_ajax_referer( 'lab_nonce', 'nonce' );

        if ( ! is_user_logged_in() || ! current_user_can( 'lab_manage_own_deals' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'labeng' ) ) );
        }

        $business_id = absint( $_POST['business_id'] ?? 0 );
        $title       = sanitize_text_field( $_POST['title'] ?? '' );
        $discount    = sanitize_text_field( $_POST['discount'] ?? '' );
        $valid_until = sanitize_text_field( $_POST['valid_until'] ?? '' );
        $description = sanitize_textarea_field( $_POST['description'] ?? '' );

        /* Verify ownership */
        $owner_id = get_post_meta( $business_id, '_lab_owner_id', true );
        if ( absint( $owner_id ) !== get_current_user_id() ) {
            wp_send_json_error( array( 'message' => __( 'Not your business.', 'labeng' ) ) );
        }

        if ( empty( $title ) || empty( $discount ) || empty( $valid_until ) ) {
            wp_send_json_error( array( 'message' => __( 'Title, discount, and valid until date are required.', 'labeng' ) ) );
        }

        $post_id = wp_insert_post( array(
            'post_title'   => $title,
            'post_content' => $description,
            'post_status'  => 'publish',
            'post_type'    => 'lab_deal',
            'post_author'  => get_current_user_id(),
        ) );

        if ( is_wp_error( $post_id ) ) {
            wp_send_json_error( array( 'message' => __( 'Failed to create deal.', 'labeng' ) ) );
        }

        update_post_meta( $post_id, '_lab_deal_business_id', $business_id );
        update_post_meta( $post_id, '_lab_deal_discount', $discount );
        update_post_meta( $post_id, '_lab_deal_valid_until', $valid_until );
        update_post_meta( $post_id, '_lab_deal_status', 'active' );

        wp_send_json_success( array(
            'message' => __( 'Deal created successfully.', 'labeng' ),
            'deal_id' => $post_id,
        ) );
    }

    /**
     * AJAX: Update deal.
     */
    public static function ajax_update_deal() {
        check_ajax_referer( 'lab_nonce', 'nonce' );

        if ( ! is_user_logged_in() || ! current_user_can( 'lab_manage_own_deals' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'labeng' ) ) );
        }

        $deal_id     = absint( $_POST['deal_id'] ?? 0 );
        $title       = sanitize_text_field( $_POST['title'] ?? '' );
        $discount    = sanitize_text_field( $_POST['discount'] ?? '' );
        $valid_until = sanitize_text_field( $_POST['valid_until'] ?? '' );
        $description = sanitize_textarea_field( $_POST['description'] ?? '' );

        /* Verify ownership */
        $business_id = get_post_meta( $deal_id, '_lab_deal_business_id', true );
        $owner_id    = get_post_meta( $business_id, '_lab_owner_id', true );
        if ( absint( $owner_id ) !== get_current_user_id() ) {
            wp_send_json_error( array( 'message' => __( 'Not your deal.', 'labeng' ) ) );
        }

        wp_update_post( array(
            'ID'           => $deal_id,
            'post_title'   => $title,
            'post_content' => $description,
        ) );

        if ( ! empty( $discount ) )    update_post_meta( $deal_id, '_lab_deal_discount', $discount );
        if ( ! empty( $valid_until ) )  update_post_meta( $deal_id, '_lab_deal_valid_until', $valid_until );

        /* Check if expired */
        if ( ! empty( $valid_until ) && $valid_until < current_time( 'Y-m-d' ) ) {
            update_post_meta( $deal_id, '_lab_deal_status', 'expired' );
        } else {
            update_post_meta( $deal_id, '_lab_deal_status', 'active' );
        }

        wp_send_json_success( array( 'message' => __( 'Deal updated.', 'labeng' ) ) );
    }

    /**
     * AJAX: Delete deal.
     */
    public static function ajax_delete_deal() {
        check_ajax_referer( 'lab_nonce', 'nonce' );

        if ( ! is_user_logged_in() || ! current_user_can( 'lab_manage_own_deals' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'labeng' ) ) );
        }

        $deal_id = absint( $_POST['deal_id'] ?? 0 );

        /* Verify ownership */
        $business_id = get_post_meta( $deal_id, '_lab_deal_business_id', true );
        $owner_id    = get_post_meta( $business_id, '_lab_owner_id', true );
        if ( absint( $owner_id ) !== get_current_user_id() ) {
            wp_send_json_error( array( 'message' => __( 'Not your deal.', 'labeng' ) ) );
        }

        wp_delete_post( $deal_id, true );

        wp_send_json_success( array( 'message' => __( 'Deal deleted.', 'labeng' ) ) );
    }
}
