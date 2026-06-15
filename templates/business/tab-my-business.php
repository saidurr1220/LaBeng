<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$biz_name    = $business ? $business->post_title : '';
$biz_desc    = $business ? $business->post_content : '';
$biz_phone   = get_post_meta( $business_id, '_lab_phone', true );
$biz_email   = get_post_meta( $business_id, '_lab_email', true );
$biz_address = get_post_meta( $business_id, '_lab_address', true );
$biz_city    = get_post_meta( $business_id, '_lab_city', true );
$biz_postcode= get_post_meta( $business_id, '_lab_postcode', true );
$biz_gallery = get_post_meta( $business_id, '_lab_gallery', true );
$gallery_ids = ! empty( $biz_gallery ) ? array_filter( explode( ',', $biz_gallery ) ) : array();

/* Get categories */
$categories = get_terms( array( 'taxonomy' => 'lab_category', 'hide_empty' => false ) );
$current_cats = wp_get_object_terms( $business_id, 'lab_category', array( 'fields' => 'slugs' ) );
$current_cat  = ! empty( $current_cats ) ? $current_cats[0] : '';
?>

<h2 class="lab-section-title"><?php esc_html_e( 'My Business', 'labeng' ); ?></h2>

<div id="lab-biz-edit-msg" class="lab-msg" style="display:none;"></div>

<form id="lab-biz-edit-form" class="lab-form">
    <div class="lab-form-row lab-form-row--2col">
        <div class="lab-field">
            <label for="lab-biz-edit-name"><?php esc_html_e( 'Business Name', 'labeng' ); ?></label>
            <input type="text" id="lab-biz-edit-name" name="business_name" value="<?php echo esc_attr( $biz_name ); ?>" required />
        </div>
        <div class="lab-field">
            <label for="lab-biz-edit-category"><?php esc_html_e( 'Category', 'labeng' ); ?></label>
            <select id="lab-biz-edit-category" name="category">
                <option value=""><?php esc_html_e( 'Select', 'labeng' ); ?></option>
                <?php if ( ! is_wp_error( $categories ) ) : ?>
                    <?php foreach ( $categories as $cat ) : ?>
                        <option value="<?php echo esc_attr( $cat->slug ); ?>" <?php selected( $current_cat, $cat->slug ); ?>><?php echo esc_html( $cat->name ); ?></option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
        </div>
    </div>

    <div class="lab-field">
        <label for="lab-biz-edit-desc"><?php esc_html_e( 'Description', 'labeng' ); ?></label>
        <textarea id="lab-biz-edit-desc" name="description" rows="5"><?php echo esc_textarea( $biz_desc ); ?></textarea>
    </div>

    <div class="lab-form-row lab-form-row--2col">
        <div class="lab-field">
            <label for="lab-biz-edit-phone"><?php esc_html_e( 'Phone', 'labeng' ); ?></label>
            <input type="tel" id="lab-biz-edit-phone" name="phone" value="<?php echo esc_attr( $biz_phone ); ?>" />
        </div>
        <div class="lab-field">
            <label for="lab-biz-edit-email"><?php esc_html_e( 'Email', 'labeng' ); ?></label>
            <input type="email" id="lab-biz-edit-email" name="email" value="<?php echo esc_attr( $biz_email ); ?>" />
        </div>
    </div>

    <div class="lab-field">
        <label for="lab-biz-edit-address"><?php esc_html_e( 'Address', 'labeng' ); ?></label>
        <textarea id="lab-biz-edit-address" name="address" rows="2"><?php echo esc_textarea( $biz_address ); ?></textarea>
    </div>

    <div class="lab-form-row lab-form-row--2col">
        <div class="lab-field">
            <label for="lab-biz-edit-city"><?php esc_html_e( 'City', 'labeng' ); ?></label>
            <input type="text" id="lab-biz-edit-city" name="city" value="<?php echo esc_attr( $biz_city ); ?>" />
        </div>
        <div class="lab-field">
            <label for="lab-biz-edit-postcode"><?php esc_html_e( 'Postcode', 'labeng' ); ?></label>
            <input type="text" id="lab-biz-edit-postcode" name="postcode" value="<?php echo esc_attr( $biz_postcode ); ?>" />
        </div>
    </div>

    <!-- Gallery -->
    <div class="lab-field">
        <label><?php esc_html_e( 'Gallery Images', 'labeng' ); ?></label>
        <div id="lab-gallery-preview" class="lab-gallery-preview">
            <?php foreach ( $gallery_ids as $img_id ) :
                $img_url = wp_get_attachment_image_url( $img_id, 'medium' );
                if ( $img_url ) :
            ?>
                <div class="lab-gallery-item" data-id="<?php echo esc_attr( $img_id ); ?>">
                    <img src="<?php echo esc_url( $img_url ); ?>" alt="" />
                    <button type="button" class="lab-gallery-remove" data-id="<?php echo esc_attr( $img_id ); ?>">&times;</button>
                </div>
            <?php endif; endforeach; ?>
        </div>
        <input type="hidden" id="lab-gallery-ids" name="gallery" value="<?php echo esc_attr( $biz_gallery ); ?>" />
        <button type="button" id="lab-gallery-upload" class="lab-btn lab-btn--outline"><?php esc_html_e( 'Add Images', 'labeng' ); ?></button>
    </div>

    <button type="submit" class="lab-btn lab-btn--primary"><?php esc_html_e( 'Save Changes', 'labeng' ); ?></button>
</form>
