<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$services_json = get_post_meta( $business_id, '_lab_services', true );
$services      = json_decode( $services_json, true );
if ( ! is_array( $services ) ) $services = array();
$cs = get_option( 'lab_currency_symbol', '£' );
?>

<h2 class="lab-section-title"><?php esc_html_e( 'Services', 'labeng' ); ?></h2>
<p class="lab-section-desc"><?php esc_html_e( 'Add the services you offer. Customers will see these on your business page and can book them directly.', 'labeng' ); ?></p>

<div id="lab-services-msg" class="lab-msg" style="display:none;"></div>

<div id="lab-services-list" class="lab-services-list">
    <?php if ( ! empty( $services ) ) : ?>
        <?php foreach ( $services as $index => $svc ) : ?>
        <div class="lab-service-row" data-index="<?php echo esc_attr( $index ); ?>">
            <div class="lab-field">
                <label><?php esc_html_e( 'Service Name', 'labeng' ); ?></label>
                <input type="text" name="svc_name[]" value="<?php echo esc_attr( $svc['name'] ); ?>" placeholder="<?php esc_attr_e( 'e.g. Airport Pickup', 'labeng' ); ?>" />
            </div>
            <div class="lab-field lab-field--sm">
                <label><?php echo esc_html( sprintf( __( 'Price (%s)', 'labeng' ), $cs ) ); ?></label>
                <input type="number" name="svc_price[]" value="<?php echo esc_attr( $svc['price'] ); ?>" step="0.01" min="0" />
            </div>
            <div class="lab-field lab-field--sm">
                <label><?php esc_html_e( 'Duration (min)', 'labeng' ); ?></label>
                <input type="number" name="svc_duration[]" value="<?php echo esc_attr( $svc['duration'] ); ?>" min="1" />
            </div>
            <button type="button" class="lab-btn lab-btn--danger lab-btn--icon lab-service-remove" title="<?php esc_attr_e( 'Remove', 'labeng' ); ?>">&times;</button>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<div class="lab-services-actions">
    <button type="button" id="lab-add-service" class="lab-btn lab-btn--outline"><?php esc_html_e( '+ Add Service', 'labeng' ); ?></button>
    <button type="button" id="lab-save-services" class="lab-btn lab-btn--primary"><?php esc_html_e( 'Save Services', 'labeng' ); ?></button>
</div>

<!-- Template for new service row -->
<template id="lab-service-row-template">
    <div class="lab-service-row">
        <div class="lab-field">
            <label><?php esc_html_e( 'Service Name', 'labeng' ); ?></label>
            <input type="text" name="svc_name[]" placeholder="<?php esc_attr_e( 'e.g. Airport Pickup', 'labeng' ); ?>" />
        </div>
        <div class="lab-field lab-field--sm">
            <label><?php echo esc_html( sprintf( __( 'Price (%s)', 'labeng' ), $cs ) ); ?></label>
            <input type="number" name="svc_price[]" step="0.01" min="0" />
        </div>
        <div class="lab-field lab-field--sm">
            <label><?php esc_html_e( 'Duration (min)', 'labeng' ); ?></label>
            <input type="number" name="svc_duration[]" min="1" />
        </div>
        <button type="button" class="lab-btn lab-btn--danger lab-btn--icon lab-service-remove">&times;</button>
    </div>
</template>
