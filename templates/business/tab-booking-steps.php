<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$steps_json = get_post_meta( $business_id, '_lab_booking_steps', true );
$steps      = json_decode( $steps_json, true );
if ( ! is_array( $steps ) ) $steps = array();
?>

<h2 class="lab-section-title"><?php esc_html_e( 'Booking Steps', 'labeng' ); ?></h2>
<p class="lab-section-desc">
    <?php esc_html_e( 'Configure a custom multi-step booking experience for your clients. Leave empty to use the default 4-step flow.', 'labeng' ); ?>
</p>

<div id="lab-booking-steps-msg" class="lab-msg" style="display:none;"></div>

<div id="lab-booking-steps-list" class="lab-booking-steps-list">
    <?php if ( ! empty( $steps ) ) : ?>
        <?php foreach ( $steps as $index => $step ) : 
            $type = $step['type'] ?? 'services';
            $show_opts = in_array( $type, array( 'vehicles', 'services', 'duration' ), true );
        ?>
        <div class="lab-booking-step-card" data-index="<?php echo esc_attr( $index ); ?>">
            <div class="lab-booking-step-card__header">
                <div class="lab-field">
                    <label><?php esc_html_e( 'Step Title', 'labeng' ); ?></label>
                    <input type="text" name="step_title[]" value="<?php echo esc_attr( $step['title'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'e.g. Choose a Vehicle', 'labeng' ); ?>" required />
                </div>
                <div class="lab-field">
                    <label><?php esc_html_e( 'Step Type', 'labeng' ); ?></label>
                    <select name="step_type[]" class="lab-step-type-select">
                        <option value="details" <?php selected( $type, 'details' ); ?>><?php esc_html_e( 'Your Details (Form)', 'labeng' ); ?></option>
                        <option value="vehicles" <?php selected( $type, 'vehicles' ); ?>><?php esc_html_e( 'Select a Vehicle (Grid)', 'labeng' ); ?></option>
                        <option value="services" <?php selected( $type, 'services' ); ?>><?php esc_html_e( 'Select a Service (Grid)', 'labeng' ); ?></option>
                        <option value="duration" <?php selected( $type, 'duration' ); ?>><?php esc_html_e( 'Duration Selector (Pills)', 'labeng' ); ?></option>
                        <option value="datetime" <?php selected( $type, 'datetime' ); ?>><?php esc_html_e( 'Select Date & Time (Calendar)', 'labeng' ); ?></option>
                        <option value="payment" <?php selected( $type, 'payment' ); ?>><?php esc_html_e( 'Review & Pay (Gateway)', 'labeng' ); ?></option>
                    </select>
                </div>
                <button type="button" class="lab-btn lab-btn--danger lab-btn--icon lab-step-remove" title="<?php esc_attr_e( 'Remove Step', 'labeng' ); ?>">&times;</button>
            </div>

            <div class="lab-booking-step-card__options" style="display: <?php echo $show_opts ? 'block' : 'none'; ?>;">
                <h4><?php esc_html_e( 'Step Options', 'labeng' ); ?></h4>
                <div class="lab-step-options-list">
                    <?php if ( ! empty( $step['options'] ) ) : ?>
                        <?php foreach ( $step['options'] as $opt_idx => $opt ) : ?>
                        <div class="lab-step-option-row">
                            <div class="lab-field">
                                <label><?php esc_html_e( 'Option Name', 'labeng' ); ?></label>
                                <input type="text" class="opt-name" value="<?php echo esc_attr( $opt['name'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'e.g. Mercedes A-Class', 'labeng' ); ?>" />
                            </div>
                            <div class="lab-field lab-field--sm">
                                <label><?php esc_html_e( 'Price/Factor', 'labeng' ); ?></label>
                                <input type="number" class="opt-price" value="<?php echo esc_attr( $opt['price'] ?? $opt['factor'] ?? 0 ); ?>" step="any" min="0" />
                            </div>
                            <div class="lab-field opt-image-uploader">
                                <label><?php esc_html_e( 'Image', 'labeng' ); ?></label>
                                <div class="opt-image-preview">
                                    <?php if ( ! empty( $opt['image'] ) ) : ?>
                                        <img src="<?php echo esc_url( $opt['image'] ); ?>" style="max-height: 40px; border-radius: 4px;" />
                                    <?php endif; ?>
                                </div>
                                <input type="hidden" class="opt-image-val" value="<?php echo esc_url( $opt['image'] ?? '' ); ?>" />
                                <button type="button" class="lab-btn lab-btn--sm lab-btn--outline lab-opt-image-upload"><?php esc_html_e( 'Select', 'labeng' ); ?></button>
                            </div>
                            <button type="button" class="lab-btn lab-btn--danger lab-btn--icon lab-opt-remove" title="<?php esc_attr_e( 'Remove Option', 'labeng' ); ?>">&times;</button>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <button type="button" class="lab-btn lab-btn--sm lab-btn--outline lab-add-step-option"><?php esc_html_e( '+ Add Option', 'labeng' ); ?></button>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<div class="lab-booking-steps-actions">
    <button type="button" id="lab-add-booking-step" class="lab-btn lab-btn--outline"><?php esc_html_e( '+ Add Booking Step', 'labeng' ); ?></button>
    <button type="button" id="lab-save-booking-steps" class="lab-btn lab-btn--primary"><?php esc_html_e( 'Save Booking Steps', 'labeng' ); ?></button>
</div>

<!-- Step Row Template -->
<template id="lab-booking-step-template">
    <div class="lab-booking-step-card">
        <div class="lab-booking-step-card__header">
            <div class="lab-field">
                <label><?php esc_html_e( 'Step Title', 'labeng' ); ?></label>
                <input type="text" name="step_title[]" placeholder="<?php esc_attr_e( 'e.g. Choose a Vehicle', 'labeng' ); ?>" required />
            </div>
            <div class="lab-field">
                <label><?php esc_html_e( 'Step Type', 'labeng' ); ?></label>
                <select name="step_type[]" class="lab-step-type-select">
                    <option value="details"><?php esc_html_e( 'Your Details (Form)', 'labeng' ); ?></option>
                    <option value="vehicles"><?php esc_html_e( 'Select a Vehicle (Grid)', 'labeng' ); ?></option>
                    <option value="services" selected><?php esc_html_e( 'Select a Service (Grid)', 'labeng' ); ?></option>
                    <option value="duration"><?php esc_html_e( 'Duration Selector (Pills)', 'labeng' ); ?></option>
                    <option value="datetime"><?php esc_html_e( 'Select Date & Time (Calendar)', 'labeng' ); ?></option>
                    <option value="payment"><?php esc_html_e( 'Review & Pay (Gateway)', 'labeng' ); ?></option>
                </select>
            </div>
            <button type="button" class="lab-btn lab-btn--danger lab-btn--icon lab-step-remove">&times;</button>
        </div>

        <div class="lab-booking-step-card__options" style="display: block;">
            <h4><?php esc_html_e( 'Step Options', 'labeng' ); ?></h4>
            <div class="lab-step-options-list"></div>
            <button type="button" class="lab-btn lab-btn--sm lab-btn--outline lab-add-step-option"><?php esc_html_e( '+ Add Option', 'labeng' ); ?></button>
        </div>
    </div>
</template>

<!-- Step Option Template -->
<template id="lab-booking-step-option-template">
    <div class="lab-step-option-row">
        <div class="lab-field">
            <label><?php esc_html_e( 'Option Name', 'labeng' ); ?></label>
            <input type="text" class="opt-name" placeholder="<?php esc_attr_e( 'e.g. Mercedes A-Class', 'labeng' ); ?>" />
        </div>
        <div class="lab-field lab-field--sm">
            <label><?php esc_html_e( 'Price/Factor', 'labeng' ); ?></label>
            <input type="number" class="opt-price" value="0" step="any" min="0" />
        </div>
        <div class="lab-field opt-image-uploader">
            <label><?php esc_html_e( 'Image', 'labeng' ); ?></label>
            <div class="opt-image-preview"></div>
            <input type="hidden" class="opt-image-val" value="" />
            <button type="button" class="lab-btn lab-btn--sm lab-btn--outline lab-opt-image-upload"><?php esc_html_e( 'Select', 'labeng' ); ?></button>
        </div>
        <button type="button" class="lab-btn lab-btn--danger lab-btn--icon lab-opt-remove">&times;</button>
    </div>
</template>
