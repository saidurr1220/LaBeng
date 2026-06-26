<?php
/**
 * Admin → Labeng → Branding
 * Upload the navbar logo/monogram and favicon from the WP media library.
 */
if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! current_user_can( 'manage_options' ) ) return;

/* Save */
if ( isset( $_POST['lab_branding_nonce'] ) && wp_verify_nonce( $_POST['lab_branding_nonce'], 'lab_save_branding' ) ) {
    $logo_id    = isset( $_POST['lab_logo_id'] ) ? absint( $_POST['lab_logo_id'] ) : 0;
    $favicon_id = isset( $_POST['lab_favicon_id'] ) ? absint( $_POST['lab_favicon_id'] ) : 0;

    $logo_id ? update_option( 'lab_logo_id', $logo_id ) : delete_option( 'lab_logo_id' );
    $favicon_id ? update_option( 'lab_favicon_id', $favicon_id ) : delete_option( 'lab_favicon_id' );

    echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Branding saved.', 'labeng' ) . '</p></div>';
}

$logo_id     = (int) get_option( 'lab_logo_id', 0 );
$favicon_id  = (int) get_option( 'lab_favicon_id', 0 );
$logo_url    = $logo_id ? wp_get_attachment_image_url( $logo_id, 'medium' ) : '';
$favicon_url = $favicon_id ? wp_get_attachment_image_url( $favicon_id, 'thumbnail' ) : '';

wp_enqueue_media();
?>
<div class="wrap">
    <h1><?php esc_html_e( 'Branding', 'labeng' ); ?></h1>
    <p class="description"><?php esc_html_e( 'Upload the logo/monogram shown in the site navbar and the browser favicon. PNG or SVG with a transparent background works best.', 'labeng' ); ?></p>

    <form method="post">
        <?php wp_nonce_field( 'lab_save_branding', 'lab_branding_nonce' ); ?>
        <table class="form-table" role="presentation">
            <tr class="lab-brand-field">
                <th scope="row"><label><?php esc_html_e( 'Navbar Logo / Monogram', 'labeng' ); ?></label></th>
                <td>
                    <div class="lab-brand-preview" style="margin-bottom:10px;min-height:64px;">
                        <?php if ( $logo_url ) : ?>
                            <img src="<?php echo esc_url( $logo_url ); ?>" style="max-height:64px;width:auto;background:#111;padding:8px;border-radius:8px;" />
                        <?php endif; ?>
                    </div>
                    <input type="hidden" name="lab_logo_id" class="lab-brand-id" value="<?php echo esc_attr( $logo_id ); ?>" />
                    <button type="button" class="button lab-brand-upload"><?php esc_html_e( 'Select Image', 'labeng' ); ?></button>
                    <button type="button" class="button lab-brand-remove" <?php echo $logo_id ? '' : 'style="display:none;"'; ?>><?php esc_html_e( 'Remove', 'labeng' ); ?></button>
                    <p class="description"><?php esc_html_e( 'Shown top-left in the site header. Replaces the text logo when set.', 'labeng' ); ?></p>
                </td>
            </tr>
            <tr class="lab-brand-field">
                <th scope="row"><label><?php esc_html_e( 'Favicon', 'labeng' ); ?></label></th>
                <td>
                    <div class="lab-brand-preview" style="margin-bottom:10px;min-height:48px;">
                        <?php if ( $favicon_url ) : ?>
                            <img src="<?php echo esc_url( $favicon_url ); ?>" style="max-height:48px;width:auto;background:#111;padding:6px;border-radius:8px;" />
                        <?php endif; ?>
                    </div>
                    <input type="hidden" name="lab_favicon_id" class="lab-brand-id" value="<?php echo esc_attr( $favicon_id ); ?>" />
                    <button type="button" class="button lab-brand-upload"><?php esc_html_e( 'Select Image', 'labeng' ); ?></button>
                    <button type="button" class="button lab-brand-remove" <?php echo $favicon_id ? '' : 'style="display:none;"'; ?>><?php esc_html_e( 'Remove', 'labeng' ); ?></button>
                    <p class="description"><?php esc_html_e( 'Square image (e.g. 512×512) recommended.', 'labeng' ); ?></p>
                </td>
            </tr>
        </table>
        <?php submit_button( __( 'Save Branding', 'labeng' ) ); ?>
    </form>
</div>

<script>
jQuery(function($){
    $(document).on('click', '.lab-brand-upload', function(e){
        e.preventDefault();
        var $row = $(this).closest('.lab-brand-field');
        var frame = wp.media({ title: 'Select Image', button: { text: 'Use this image' }, multiple: false });
        frame.on('select', function(){
            var att = frame.state().get('selection').first().toJSON();
            var url = (att.sizes && att.sizes.medium) ? att.sizes.medium.url : att.url;
            $row.find('.lab-brand-id').val(att.id);
            $row.find('.lab-brand-preview').html('<img src="'+url+'" style="max-height:64px;width:auto;background:#111;padding:8px;border-radius:8px;" />');
            $row.find('.lab-brand-remove').show();
        });
        frame.open();
    });
    $(document).on('click', '.lab-brand-remove', function(e){
        e.preventDefault();
        var $row = $(this).closest('.lab-brand-field');
        $row.find('.lab-brand-id').val('');
        $row.find('.lab-brand-preview').empty();
        $(this).hide();
    });
});
</script>
<?php
