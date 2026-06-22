<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class Lab_Business_CPT
 * Registers lab_business CPT, lab_category taxonomy, admin columns, approve/suspend actions.
 */
class Lab_Business_CPT {

    public static function init() {
        add_action( 'init', array( __CLASS__, 'register_cpt' ) );
        add_action( 'init', array( __CLASS__, 'register_taxonomy' ) );

        /* Category image (term meta) */
        add_action( 'lab_category_add_form_fields',  array( __CLASS__, 'category_add_image_field' ) );
        add_action( 'lab_category_edit_form_fields', array( __CLASS__, 'category_edit_image_field' ), 10, 1 );
        add_action( 'created_lab_category', array( __CLASS__, 'save_category_image' ) );
        add_action( 'edited_lab_category',  array( __CLASS__, 'save_category_image' ) );
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_category_media' ) );

        /* Admin columns */
        add_filter( 'manage_lab_business_posts_columns',       array( __CLASS__, 'admin_columns' ) );
        add_action( 'manage_lab_business_posts_custom_column',  array( __CLASS__, 'admin_column_data' ), 10, 2 );
        add_filter( 'manage_edit-lab_business_sortable_columns', array( __CLASS__, 'sortable_columns' ) );

        /* Row actions (Approve / Suspend) */
        add_filter( 'post_row_actions', array( __CLASS__, 'row_actions' ), 10, 2 );
        add_action( 'admin_init',       array( __CLASS__, 'handle_row_action' ) );

        /* Commission meta box */
        add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ) );
        add_action( 'save_post_lab_business', array( __CLASS__, 'save_meta_boxes' ) );

        /* Admin notices */
        add_action( 'admin_notices', array( __CLASS__, 'admin_notices' ) );

        /* AJAX: Business owner actions */
        add_action( 'wp_ajax_lab_save_services',     array( __CLASS__, 'ajax_save_services' ) );
        add_action( 'wp_ajax_lab_save_booking_steps', array( __CLASS__, 'ajax_save_booking_steps' ) );
        add_action( 'wp_ajax_lab_update_business',   array( __CLASS__, 'ajax_update_business' ) );
        add_action( 'wp_ajax_lab_search_businesses',        array( __CLASS__, 'ajax_search_businesses' ) );
        add_action( 'wp_ajax_nopriv_lab_search_businesses', array( __CLASS__, 'ajax_search_businesses' ) );
    }

    /**
     * Category-appropriate fallback images (keyed by category slug).
     * Used when a business has no photo of its own — a salon never shows pizza.
     */
    public static function category_fallback_map() {
        return array(
            'beauty'     => 'https://images.unsplash.com/photo-1560066984-138dadb4c035?auto=format&fit=crop&w=800&q=85',
            'food-drink' => 'https://images.unsplash.com/photo-1574071318508-1cdbab80d002?auto=format&fit=crop&w=800&q=85',
            'fitness'    => 'https://images.unsplash.com/photo-1571902943202-507ec2618e8f?auto=format&fit=crop&w=800&q=85',
            'services'   => 'https://images.unsplash.com/photo-1503736334956-4c8f8e92946d?auto=format&fit=crop&w=800&q=85',
        );
    }

    /**
     * Resolve the best image URL for a business.
     * Priority: featured image → first gallery image → admin category image → category-keyed fallback.
     *
     * @param int    $business_id
     * @param string $size
     * @return string
     */
    public static function get_business_image( $business_id, $size = 'large' ) {
        /* 1. Featured image (this is what the gallery sets on save) */
        $url = get_the_post_thumbnail_url( $business_id, $size );
        if ( $url ) {
            return $url;
        }

        /* 1.5. Mock thumb fallback */
        $mock = get_post_meta( $business_id, '_lab_mock_thumb', true );
        if ( $mock ) {
            return $mock;
        }

        /* 2. First gallery image directly (in case featured was never synced) */
        $gallery = get_post_meta( $business_id, '_lab_gallery', true );
        if ( ! empty( $gallery ) ) {
            $ids = array_filter( explode( ',', $gallery ) );
            if ( ! empty( $ids ) ) {
                $g = wp_get_attachment_image_url( absint( reset( $ids ) ), $size );
                if ( $g ) {
                    return $g;
                }
            }
        }

        /* 3. Category — admin-set term image, then category-keyed fallback */
        $terms = wp_get_object_terms( $business_id, 'lab_category', array( 'fields' => 'all' ) );
        $map   = self::category_fallback_map();
        if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
            $term   = $terms[0];
            $img_id = get_term_meta( $term->term_id, 'lab_cat_image_id', true );
            if ( $img_id ) {
                $cu = wp_get_attachment_image_url( $img_id, $size );
                if ( $cu ) {
                    return $cu;
                }
            }
            if ( isset( $map[ $term->slug ] ) ) {
                return $map[ $term->slug ];
            }
        }

        /* 4. Generic fallback, stable per business so it never changes between loads */
        $generic = array_values( $map );
        return $generic[ $business_id % count( $generic ) ];
    }

    /**
     * AJAX: Save services as JSON.
     */
    public static function ajax_save_services() {
        check_ajax_referer( 'lab_nonce', 'nonce' );

        if ( ! is_user_logged_in() || ! current_user_can( 'lab_manage_own_services' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'labeng' ) ) );
        }

        $business_id = absint( $_POST['business_id'] ?? 0 );
        $owner_id    = get_post_meta( $business_id, '_lab_owner_id', true );

        if ( absint( $owner_id ) !== get_current_user_id() ) {
            wp_send_json_error( array( 'message' => __( 'Not your business.', 'labeng' ) ) );
        }

        $services_raw = $_POST['services'] ?? '[]';
        $services = json_decode( wp_unslash( $services_raw ), true );
        if ( ! is_array( $services ) ) $services = array();

        /* Sanitize each service */
        $clean = array();
        foreach ( $services as $svc ) {
            $clean[] = array(
                'name'     => sanitize_text_field( $svc['name'] ?? '' ),
                'price'    => floatval( $svc['price'] ?? 0 ),
                'duration' => absint( $svc['duration'] ?? 0 ),
            );
        }

        update_post_meta( $business_id, '_lab_services', wp_json_encode( $clean ) );

        wp_send_json_success( array( 'message' => __( 'Services saved successfully.', 'labeng' ) ) );
    }

    /**
     * AJAX: Save custom booking steps as JSON.
     */
    public static function ajax_save_booking_steps() {
        check_ajax_referer( 'lab_nonce', 'nonce' );

        if ( ! is_user_logged_in() || ! current_user_can( 'lab_manage_own_services' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'labeng' ) ) );
        }

        $business_id = absint( $_POST['business_id'] ?? 0 );
        $owner_id    = get_post_meta( $business_id, '_lab_owner_id', true );

        if ( absint( $owner_id ) !== get_current_user_id() ) {
            wp_send_json_error( array( 'message' => __( 'Not your business.', 'labeng' ) ) );
        }

        $steps_raw = $_POST['steps'] ?? '[]';
        $steps = json_decode( wp_unslash( $steps_raw ), true );
        if ( ! is_array( $steps ) ) $steps = array();

        /* Sanitize each step */
        $clean = array();
        foreach ( $steps as $step ) {
            $clean_options = array();
            if ( ! empty( $step['options'] ) && is_array( $step['options'] ) ) {
                foreach ( $step['options'] as $opt ) {
                    $clean_options[] = array(
                        'name'   => sanitize_text_field( $opt['name'] ?? '' ),
                        'price'  => floatval( $opt['price'] ?? 0 ),
                        'factor' => floatval( $opt['price'] ?? 0 ), // Keep factor and price synced for durations
                        'image'  => esc_url_raw( $opt['image'] ?? '' ),
                    );
                }
            }

            $clean[] = array(
                'title'   => sanitize_text_field( $step['title'] ?? '' ),
                'type'    => sanitize_key( $step['type'] ?? '' ),
                'options' => $clean_options,
            );
        }

        update_post_meta( $business_id, '_lab_booking_steps', wp_json_encode( $clean ) );

        wp_send_json_success( array( 'message' => __( 'Booking steps saved successfully.', 'labeng' ) ) );
    }

    /**
     * AJAX: Update business profile (My Business tab).
     */
    public static function ajax_update_business() {
        check_ajax_referer( 'lab_nonce', 'nonce' );

        if ( ! is_user_logged_in() || ! current_user_can( 'lab_manage_own_business' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'labeng' ) ) );
        }

        $business_id = absint( $_POST['business_id'] ?? 0 );
        $owner_id    = get_post_meta( $business_id, '_lab_owner_id', true );

        if ( absint( $owner_id ) !== get_current_user_id() ) {
            wp_send_json_error( array( 'message' => __( 'Not your business.', 'labeng' ) ) );
        }

        $biz_name    = sanitize_text_field( $_POST['business_name'] ?? '' );
        $description = sanitize_textarea_field( $_POST['description'] ?? '' );
        $phone       = sanitize_text_field( $_POST['phone'] ?? '' );
        $email       = sanitize_email( $_POST['email'] ?? '' );
        $address     = sanitize_textarea_field( $_POST['address'] ?? '' );
        $city        = sanitize_text_field( $_POST['city'] ?? '' );
        $category    = sanitize_text_field( $_POST['category'] ?? '' );
        $gallery     = sanitize_text_field( $_POST['gallery'] ?? '' );

        /* Update post */
        wp_update_post( array(
            'ID'           => $business_id,
            'post_title'   => $biz_name,
            'post_content' => $description,
        ) );

        /* Update meta */
        update_post_meta( $business_id, '_lab_phone', $phone );
        update_post_meta( $business_id, '_lab_email', $email );
        update_post_meta( $business_id, '_lab_address', $address );
        update_post_meta( $business_id, '_lab_city', $city );
        $postcode = sanitize_text_field( $_POST['postcode'] ?? '' );
        update_post_meta( $business_id, '_lab_postcode', $postcode );
        update_post_meta( $business_id, '_lab_gallery', $gallery );

        /* Sync first gallery image as featured image so all templates pick it up */
        if ( ! empty( $gallery ) ) {
            $ids = array_filter( explode( ',', $gallery ) );
            if ( ! empty( $ids ) ) {
                set_post_thumbnail( $business_id, absint( reset( $ids ) ) );
            }
        } else {
            delete_post_thumbnail( $business_id );
        }

        /* Update category taxonomy */
        if ( ! empty( $category ) ) {
            wp_set_object_terms( $business_id, $category, 'lab_category' );
        }

        wp_send_json_success( array( 'message' => __( 'Business profile updated.', 'labeng' ) ) );
    }

    /**
     * AJAX: Search businesses (archive page).
     */
    public static function ajax_search_businesses() {
        check_ajax_referer( 'lab_nonce', 'nonce' );

        $keyword  = sanitize_text_field( $_POST['keyword'] ?? '' );
        $city     = sanitize_text_field( $_POST['city'] ?? '' );
        $category = sanitize_text_field( $_POST['category'] ?? '' );

        $args = array(
            'post_type'   => 'lab_business',
            'post_status' => 'publish',
            'numberposts' => -1,
            'meta_query'  => array(
                array( 'key' => '_lab_status', 'value' => 'approved' ),
            ),
        );

        if ( ! empty( $keyword ) ) {
            $args['s'] = $keyword;
        }
        if ( ! empty( $city ) ) {
            $args['meta_query'][] = array( 'key' => '_lab_city', 'value' => $city );
        }
        if ( ! empty( $category ) ) {
            $args['tax_query'] = array(
                array( 'taxonomy' => 'lab_category', 'field' => 'slug', 'terms' => $category ),
            );
        }

        $businesses = get_posts( $args );
        $cs = get_option( 'lab_currency_symbol', '£' );

        ob_start();
        if ( ! empty( $businesses ) ) {
            foreach ( $businesses as $biz ) {
                $biz_city  = get_post_meta( $biz->ID, '_lab_city', true );
                $biz_avg   = floatval( get_post_meta( $biz->ID, '_lab_rating_avg', true ) );
                $biz_total = intval( get_post_meta( $biz->ID, '_lab_total_reviews', true ) );
                $biz_cats  = wp_get_object_terms( $biz->ID, 'lab_category' );
                $biz_cat   = ! empty( $biz_cats ) && ! is_wp_error( $biz_cats ) ? $biz_cats[0]->name : '';
                $thumb     = get_the_post_thumbnail_url( $biz->ID, 'medium' );
                ?>
                <a href="<?php echo esc_url( get_permalink( $biz->ID ) ); ?>" class="lab-business-card">
                    <div class="lab-business-card__image">
                        <?php if ( $thumb ) : ?>
                            <img src="<?php echo esc_url( $thumb ); ?>" alt="<?php echo esc_attr( $biz->post_title ); ?>" loading="lazy" />
                        <?php else : ?>
                            <div class="lab-business-card__placeholder">🏢</div>
                        <?php endif; ?>
                    </div>
                    <div class="lab-business-card__body">
                        <h3 class="lab-business-card__title"><?php echo esc_html( $biz->post_title ); ?></h3>
                        <div class="lab-business-card__meta">
                            <?php if ( $biz_cat ) : ?>
                                <span class="lab-badge lab-badge--category lab-badge--sm"><?php echo esc_html( $biz_cat ); ?></span>
                            <?php endif; ?>
                            <?php if ( $biz_city ) : ?>
                                <span class="lab-business-card__city">📍 <?php echo esc_html( $biz_city ); ?></span>
                            <?php endif; ?>
                        </div>
                        <?php if ( $biz_total > 0 ) : ?>
                            <div class="lab-business-card__rating">
                                <?php echo Lab_Reviews::render_stars( $biz_avg, true ); ?>
                                <span class="lab-business-card__review-count">(<?php echo esc_html( $biz_total ); ?>)</span>
                            </div>
                        <?php endif; ?>
                        <p class="lab-business-card__desc"><?php echo esc_html( wp_trim_words( $biz->post_content, 15, '…' ) ); ?></p>
                    </div>
                </a>
                <?php
            }
        } else {
            echo '<div class="lab-empty-state lab-empty-state--wide"><p>' . esc_html__( 'No businesses found.', 'labeng' ) . '</p></div>';
        }

        $html = ob_get_clean();
        wp_send_json_success( array( 'html' => $html ) );
    }

    /**
     * Register lab_business CPT.
     */
    public static function register_cpt() {
        $labels = array(
            'name'               => __( 'Businesses', 'labeng' ),
            'singular_name'      => __( 'Business', 'labeng' ),
            'add_new'            => __( 'Add New', 'labeng' ),
            'add_new_item'       => __( 'Add New Business', 'labeng' ),
            'edit_item'          => __( 'Edit Business', 'labeng' ),
            'new_item'           => __( 'New Business', 'labeng' ),
            'view_item'          => __( 'View Business', 'labeng' ),
            'search_items'       => __( 'Search Businesses', 'labeng' ),
            'not_found'          => __( 'No businesses found', 'labeng' ),
            'not_found_in_trash' => __( 'No businesses found in Trash', 'labeng' ),
            'all_items'          => __( 'All Businesses', 'labeng' ),
            'menu_name'          => __( 'Businesses', 'labeng' ),
        );

        register_post_type( 'lab_business', array(
            'labels'       => $labels,
            'public'       => true,
            'has_archive'  => true,
            'rewrite'      => array( 'slug' => 'businesses' ),
            'supports'     => array( 'title', 'editor', 'thumbnail' ),
            'menu_icon'    => 'dashicons-store',
            'menu_position'=> 50,
            'show_in_menu' => true,
            'show_in_rest' => false,
        ) );
    }

    /**
     * Register lab_category taxonomy.
     */
    public static function register_taxonomy() {
        $labels = array(
            'name'          => __( 'Business Categories', 'labeng' ),
            'singular_name' => __( 'Category', 'labeng' ),
            'search_items'  => __( 'Search Categories', 'labeng' ),
            'all_items'     => __( 'All Categories', 'labeng' ),
            'edit_item'     => __( 'Edit Category', 'labeng' ),
            'update_item'   => __( 'Update Category', 'labeng' ),
            'add_new_item'  => __( 'Add New Category', 'labeng' ),
            'new_item_name' => __( 'New Category Name', 'labeng' ),
            'menu_name'     => __( 'Categories', 'labeng' ),
        );

        register_taxonomy( 'lab_category', 'lab_business', array(
            'labels'       => $labels,
            'hierarchical' => true,
            'public'       => true,
            'show_admin_column' => true,
            'rewrite'      => array( 'slug' => 'business-category' ),
            'show_in_rest' => false,
        ) );
    }

    /* ──────────────────────────────────────────────────────────
     * Category Image (term meta `lab_cat_image_id`)
     * ────────────────────────────────────────────────────────── */

    /** Load WP media uploader on the category admin screens. */
    public static function enqueue_category_media( $hook ) {
        if ( ( $hook === 'edit-tags.php' || $hook === 'term.php' )
            && isset( $_GET['taxonomy'] ) && $_GET['taxonomy'] === 'lab_category' ) {
            wp_enqueue_media();
        }
    }

    /** Field on the "Add New Category" screen. */
    public static function category_add_image_field() {
        ?>
        <div class="form-field term-lab-cat-image-wrap">
            <label><?php esc_html_e( 'Category Image', 'labeng' ); ?></label>
            <div class="lab-cat-image-preview" style="margin-bottom:8px;"></div>
            <input type="hidden" name="lab_cat_image_id" class="lab-cat-image-id" value="" />
            <button type="button" class="button lab-cat-upload-btn"><?php esc_html_e( 'Select Image', 'labeng' ); ?></button>
            <button type="button" class="button lab-cat-remove-btn" style="display:none;"><?php esc_html_e( 'Remove', 'labeng' ); ?></button>
            <p class="description"><?php esc_html_e( 'Shown in the "Explore Categories" section on the home page.', 'labeng' ); ?></p>
        </div>
        <?php
        self::category_image_script();
    }

    /** Field on the "Edit Category" screen. */
    public static function category_edit_image_field( $term ) {
        $image_id = get_term_meta( $term->term_id, 'lab_cat_image_id', true );
        $preview  = $image_id ? wp_get_attachment_image( $image_id, 'thumbnail', false, array( 'style' => 'max-width:120px;height:auto;border-radius:6px;' ) ) : '';
        ?>
        <tr class="form-field term-lab-cat-image-wrap">
            <th scope="row"><label><?php esc_html_e( 'Category Image', 'labeng' ); ?></label></th>
            <td>
                <div class="lab-cat-image-preview" style="margin-bottom:8px;"><?php echo $preview; ?></div>
                <input type="hidden" name="lab_cat_image_id" class="lab-cat-image-id" value="<?php echo esc_attr( $image_id ); ?>" />
                <button type="button" class="button lab-cat-upload-btn"><?php esc_html_e( 'Select Image', 'labeng' ); ?></button>
                <button type="button" class="button lab-cat-remove-btn" <?php echo $image_id ? '' : 'style="display:none;"'; ?>><?php esc_html_e( 'Remove', 'labeng' ); ?></button>
                <p class="description"><?php esc_html_e( 'Shown in the "Explore Categories" section on the home page.', 'labeng' ); ?></p>
            </td>
        </tr>
        <?php
        self::category_image_script();
    }

    /** Save the selected image ID. */
    public static function save_category_image( $term_id ) {
        if ( ! isset( $_POST['lab_cat_image_id'] ) ) {
            return;
        }
        $image_id = absint( $_POST['lab_cat_image_id'] );
        if ( $image_id ) {
            update_term_meta( $term_id, 'lab_cat_image_id', $image_id );
        } else {
            delete_term_meta( $term_id, 'lab_cat_image_id' );
        }
    }

    /** Inline JS wiring the media uploader (printed once). */
    private static function category_image_script() {
        static $printed = false;
        if ( $printed ) {
            return;
        }
        $printed = true;
        ?>
        <script>
        jQuery(function($){
            $(document).on('click', '.lab-cat-upload-btn', function(e){
                e.preventDefault();
                var $wrap = $(this).closest('.term-lab-cat-image-wrap');
                var frame = wp.media({
                    title: '<?php echo esc_js( __( 'Select Category Image', 'labeng' ) ); ?>',
                    button: { text: '<?php echo esc_js( __( 'Use this image', 'labeng' ) ); ?>' },
                    multiple: false
                });
                frame.on('select', function(){
                    var att = frame.state().get('selection').first().toJSON();
                    var url = (att.sizes && att.sizes.thumbnail) ? att.sizes.thumbnail.url : att.url;
                    $wrap.find('.lab-cat-image-id').val(att.id);
                    $wrap.find('.lab-cat-image-preview').html('<img src="'+url+'" style="max-width:120px;height:auto;border-radius:6px;" />');
                    $wrap.find('.lab-cat-remove-btn').show();
                });
                frame.open();
            });
            $(document).on('click', '.lab-cat-remove-btn', function(e){
                e.preventDefault();
                var $wrap = $(this).closest('.term-lab-cat-image-wrap');
                $wrap.find('.lab-cat-image-id').val('');
                $wrap.find('.lab-cat-image-preview').empty();
                $(this).hide();
            });
        });
        </script>
        <?php
    }

    /**
     * Custom admin columns.
     */
    public static function admin_columns( $columns ) {
        $new = array();
        $new['cb']              = $columns['cb'];
        $new['title']           = $columns['title'];
        $new['lab_owner']       = __( 'Owner', 'labeng' );
        $new['lab_city']        = __( 'City', 'labeng' );
        $new['lab_status']      = __( 'Status', 'labeng' );
        $new['lab_commission']  = __( 'Commission', 'labeng' );
        $new['lab_rating']      = __( 'Rating', 'labeng' );
        $new['date']            = $columns['date'];
        return $new;
    }

    /**
     * Admin column data.
     */
    public static function admin_column_data( $column, $post_id ) {
        switch ( $column ) {
            case 'lab_owner':
                $owner_id = get_post_meta( $post_id, '_lab_owner_id', true );
                if ( $owner_id ) {
                    $user = get_userdata( $owner_id );
                    echo $user ? esc_html( $user->display_name ) : '—';
                } else {
                    echo '—';
                }
                break;

            case 'lab_city':
                echo esc_html( get_post_meta( $post_id, '_lab_city', true ) ?: '—' );
                break;

            case 'lab_status':
                $status = get_post_meta( $post_id, '_lab_status', true );
                $colors = array(
                    'pending'   => '#ffc107',
                    'approved'  => '#198754',
                    'suspended' => '#dc3545',
                );
                $color = isset( $colors[ $status ] ) ? $colors[ $status ] : '#888';
                echo '<span style="display:inline-block;padding:3px 10px;border-radius:12px;font-size:12px;font-weight:600;color:#fff;background:' . esc_attr( $color ) . ';">' . esc_html( ucfirst( $status ?: 'pending' ) ) . '</span>';
                break;

            case 'lab_commission':
                $eff = Lab_Commissions::get_effective( $post_id );
                if ( $eff['type'] === 'percentage' ) {
                    echo esc_html( $eff['value'] . '%' );
                } else {
                    echo esc_html( get_option( 'lab_currency_symbol', '£' ) . $eff['value'] );
                }
                if ( $eff['is_default'] ) {
                    echo ' <span style="color:#888;font-size:11px;">(' . esc_html__( 'default', 'labeng' ) . ')</span>';
                }
                break;

            case 'lab_rating':
                $avg   = floatval( get_post_meta( $post_id, '_lab_rating_avg', true ) );
                $total = intval( get_post_meta( $post_id, '_lab_total_reviews', true ) );
                if ( $total > 0 ) {
                    echo esc_html( number_format( $avg, 1 ) . ' ★ (' . $total . ')' );
                } else {
                    echo '—';
                }
                break;
        }
    }

    /**
     * Sortable columns.
     */
    public static function sortable_columns( $columns ) {
        $columns['lab_city']   = 'lab_city';
        $columns['lab_rating'] = 'lab_rating';
        return $columns;
    }

    /**
     * Add Approve / Suspend quick actions.
     */
    public static function row_actions( $actions, $post ) {
        if ( $post->post_type !== 'lab_business' ) return $actions;

        $status = get_post_meta( $post->ID, '_lab_status', true );

        if ( $status !== 'approved' ) {
            $approve_url = wp_nonce_url(
                admin_url( 'admin.php?action=lab_approve_business&post_id=' . $post->ID ),
                'lab_approve_' . $post->ID
            );
            $actions['lab_approve'] = '<a href="' . esc_url( $approve_url ) . '" style="color:#198754;font-weight:600;">' . __( 'Approve', 'labeng' ) . '</a>';
        }

        if ( $status !== 'suspended' ) {
            $suspend_url = wp_nonce_url(
                admin_url( 'admin.php?action=lab_suspend_business&post_id=' . $post->ID ),
                'lab_suspend_' . $post->ID
            );
            $actions['lab_suspend'] = '<a href="' . esc_url( $suspend_url ) . '" style="color:#dc3545;">' . __( 'Suspend', 'labeng' ) . '</a>';
        }

        return $actions;
    }

    /**
     * Handle approve/suspend actions.
     */
    public static function handle_row_action() {
        if ( isset( $_GET['action'] ) && $_GET['action'] === 'lab_approve_business' ) {
            $post_id = absint( $_GET['post_id'] ?? 0 );
            if ( ! wp_verify_nonce( $_GET['_wpnonce'], 'lab_approve_' . $post_id ) ) wp_die( 'Nonce failed' );
            if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Permission denied' );

            update_post_meta( $post_id, '_lab_status', 'approved' );
            wp_update_post( array( 'ID' => $post_id, 'post_status' => 'publish' ) );

            /* Upgrade user role */
            $owner_id = get_post_meta( $post_id, '_lab_owner_id', true );
            if ( $owner_id ) {
                $user = new WP_User( $owner_id );
                $user->set_role( 'business_owner' );
            }

            /* Assign default commission if none set */
            Lab_Commissions::ensure_commission( $post_id );

            /* Send approval email */
            Lab_Email::lab_email_business_approved( $post_id );

            wp_safe_redirect( admin_url( 'edit.php?post_type=lab_business&lab_action=approved' ) );
            exit;
        }

        if ( isset( $_GET['action'] ) && $_GET['action'] === 'lab_suspend_business' ) {
            $post_id = absint( $_GET['post_id'] ?? 0 );
            if ( ! wp_verify_nonce( $_GET['_wpnonce'], 'lab_suspend_' . $post_id ) ) wp_die( 'Nonce failed' );
            if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Permission denied' );

            update_post_meta( $post_id, '_lab_status', 'suspended' );
            wp_update_post( array( 'ID' => $post_id, 'post_status' => 'draft' ) );

            wp_safe_redirect( admin_url( 'edit.php?post_type=lab_business&lab_action=suspended' ) );
            exit;
        }
    }

    /**
     * Admin notices after approve/suspend.
     */
    public static function admin_notices() {
        if ( isset( $_GET['lab_action'] ) ) {
            $action = sanitize_text_field( $_GET['lab_action'] );
            if ( $action === 'approved' ) {
                echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Business approved successfully.', 'labeng' ) . '</p></div>';
            } elseif ( $action === 'suspended' ) {
                echo '<div class="notice notice-warning is-dismissible"><p>' . esc_html__( 'Business suspended.', 'labeng' ) . '</p></div>';
            }
        }
    }

    /**
     * Add commission meta box to business edit screen.
     */
    public static function add_meta_boxes() {
        add_meta_box(
            'lab_commission_box',
            __( 'Commission Settings', 'labeng' ),
            array( __CLASS__, 'render_commission_box' ),
            'lab_business',
            'side',
            'default'
        );

        add_meta_box(
            'lab_business_details_box',
            __( 'Business Details', 'labeng' ),
            array( __CLASS__, 'render_details_box' ),
            'lab_business',
            'normal',
            'high'
        );
    }

    /**
     * Render commission meta box.
     */
    public static function render_commission_box( $post ) {
        global $wpdb;
        $table = $wpdb->prefix . 'lab_commissions';
        $row   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE business_id = %d", $post->ID ) );
        $type  = $row ? $row->commission_type : 'percentage';
        $value = $row ? $row->commission_value : '';
        wp_nonce_field( 'lab_commission_save', 'lab_commission_nonce' );
        ?>
        <p>
            <label for="lab_commission_type"><?php esc_html_e( 'Type', 'labeng' ); ?></label><br />
            <select id="lab_commission_type" name="lab_commission_type" style="width:100%;">
                <option value="percentage" <?php selected( $type, 'percentage' ); ?>><?php esc_html_e( 'Percentage', 'labeng' ); ?></option>
                <option value="fixed" <?php selected( $type, 'fixed' ); ?>><?php esc_html_e( 'Fixed Amount', 'labeng' ); ?></option>
            </select>
        </p>
        <p>
            <label for="lab_commission_value"><?php esc_html_e( 'Value', 'labeng' ); ?></label><br />
            <input type="number" id="lab_commission_value" name="lab_commission_value" value="<?php echo esc_attr( $value ); ?>" step="0.01" min="0" style="width:100%;" />
        </p>
        <?php
    }

    /**
     * Render business details meta box.
     */
    public static function render_details_box( $post ) {
        $phone    = get_post_meta( $post->ID, '_lab_phone', true );
        $email    = get_post_meta( $post->ID, '_lab_email', true );
        $address  = get_post_meta( $post->ID, '_lab_address', true );
        $city     = get_post_meta( $post->ID, '_lab_city', true );
        $owner_id = get_post_meta( $post->ID, '_lab_owner_id', true );
        $status   = get_post_meta( $post->ID, '_lab_status', true );
        wp_nonce_field( 'lab_business_details_save', 'lab_business_details_nonce' );
        ?>
        <table class="form-table">
            <tr>
                <th><label for="lab_phone"><?php esc_html_e( 'Phone', 'labeng' ); ?></label></th>
                <td><input type="text" id="lab_phone" name="_lab_phone" value="<?php echo esc_attr( $phone ); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th><label for="lab_email"><?php esc_html_e( 'Email', 'labeng' ); ?></label></th>
                <td><input type="email" id="lab_email" name="_lab_email" value="<?php echo esc_attr( $email ); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th><label for="lab_address"><?php esc_html_e( 'Address', 'labeng' ); ?></label></th>
                <td><textarea id="lab_address" name="_lab_address" rows="3" class="large-text"><?php echo esc_textarea( $address ); ?></textarea></td>
            </tr>
            <tr>
                <th><label for="lab_city"><?php esc_html_e( 'City', 'labeng' ); ?></label></th>
                <td><input type="text" id="lab_city" name="_lab_city" value="<?php echo esc_attr( $city ); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th><label for="lab_postcode"><?php esc_html_e( 'Postcode', 'labeng' ); ?></label></th>
                <td>
                    <?php $postcode = get_post_meta( $post->ID, '_lab_postcode', true ); ?>
                    <input type="text" id="lab_postcode" name="_lab_postcode" value="<?php echo esc_attr( $postcode ); ?>" class="regular-text" />
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e( 'Status', 'labeng' ); ?></th>
                <td>
                    <select name="_lab_status">
                        <option value="pending" <?php selected( $status, 'pending' ); ?>><?php esc_html_e( 'Pending', 'labeng' ); ?></option>
                        <option value="approved" <?php selected( $status, 'approved' ); ?>><?php esc_html_e( 'Approved', 'labeng' ); ?></option>
                        <option value="suspended" <?php selected( $status, 'suspended' ); ?>><?php esc_html_e( 'Suspended', 'labeng' ); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e( 'Owner', 'labeng' ); ?></th>
                <td>
                    <?php
                    if ( $owner_id ) {
                        $user = get_userdata( $owner_id );
                        echo $user ? esc_html( $user->display_name . ' (' . $user->user_email . ')' ) : esc_html__( 'Unknown', 'labeng' );
                    } else {
                        esc_html_e( 'Not assigned', 'labeng' );
                    }
                    ?>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Save meta boxes on post save.
     */
    public static function save_meta_boxes( $post_id ) {
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
        if ( ! current_user_can( 'manage_options' ) ) return;

        /* Business details */
        if ( isset( $_POST['lab_business_details_nonce'] ) && wp_verify_nonce( $_POST['lab_business_details_nonce'], 'lab_business_details_save' ) ) {
            if ( isset( $_POST['_lab_phone'] ) )   update_post_meta( $post_id, '_lab_phone', sanitize_text_field( $_POST['_lab_phone'] ) );
            if ( isset( $_POST['_lab_email'] ) )   update_post_meta( $post_id, '_lab_email', sanitize_email( $_POST['_lab_email'] ) );
            if ( isset( $_POST['_lab_address'] ) ) update_post_meta( $post_id, '_lab_address', sanitize_textarea_field( $_POST['_lab_address'] ) );
            if ( isset( $_POST['_lab_city'] ) )    update_post_meta( $post_id, '_lab_city', sanitize_text_field( $_POST['_lab_city'] ) );
        }
        if ( isset( $_POST['_lab_postcode'] ) ) {
            update_post_meta( $post_id, '_lab_postcode', sanitize_text_field( $_POST['_lab_postcode'] ) );
        }
        if ( isset( $_POST['_lab_status'] ) ) {
            update_post_meta( $post_id, '_lab_status', sanitize_text_field( $_POST['_lab_status'] ) );
        }

        /* Commission */
        if ( isset( $_POST['lab_commission_nonce'] ) && wp_verify_nonce( $_POST['lab_commission_nonce'], 'lab_commission_save' ) ) {
            global $wpdb;
            $table = $wpdb->prefix . 'lab_commissions';
            $type  = sanitize_text_field( $_POST['lab_commission_type'] ?? 'percentage' );
            $value = floatval( $_POST['lab_commission_value'] ?? 0 );

            $exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE business_id = %d", $post_id ) );
            if ( $exists ) {
                $wpdb->update(
                    $table,
                    array( 'commission_type' => $type, 'commission_value' => $value ),
                    array( 'business_id' => $post_id ),
                    array( '%s', '%f' ),
                    array( '%d' )
                );
            } else {
                $wpdb->insert(
                    $table,
                    array( 'business_id' => $post_id, 'commission_type' => $type, 'commission_value' => $value ),
                    array( '%d', '%s', '%f' )
                );
            }
        }
    }
}
