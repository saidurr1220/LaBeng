<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$steps_json = get_post_meta( $business_id, '_lab_booking_steps', true );
$steps      = json_decode( $steps_json, true );
if ( ! is_array( $steps ) ) $steps = array();

if ( ! function_exists( 'lab_booking_step_type_hint' ) ) :
/**
 * Plain-language description of what the customer sees for each step type —
 * shown as live helper text under the "Step Type" dropdown so a non-technical
 * owner knows what they're building without guessing from the type name.
 */
function lab_booking_step_type_hint( $type ) {
    $hints = array(
        'details'  => __( "The customer will see a simple form to enter their name, phone number and any notes for you.", 'labeng' ),
        'vehicles' => __( "The customer will see a grid of photo cards to pick from — use this for choosing between physical items, e.g. a specific car.", 'labeng' ),
        'services' => __( "The customer will see a grid of cards to pick one service from, each showing its name and price.", 'labeng' ),
        'duration' => __( "The customer will see a row of buttons to pick how long they need (e.g. 24 hours, 48 hours). Each option multiplies the price instead of adding to it.", 'labeng' ),
        'datetime' => __( "The customer will see your calendar and available time slots to pick a date and time.", 'labeng' ),
        'payment'  => __( "The customer will see a summary of their booking and pay (if the service isn't free). This is normally your last step.", 'labeng' ),
    );
    return $hints[ $type ] ?? '';
}
endif;

if ( ! function_exists( 'lab_booking_step_options_hint' ) ) :
/**
 * What "Step Options" means for this type, and what the price field does —
 * shown above the options list so "add an option" isn't a mystery term.
 */
function lab_booking_step_options_hint( $type ) {
    $hints = array(
        'vehicles' => __( 'Each row below is one choice the customer can tap (e.g. one specific vehicle). The price is added to their total when they pick it.', 'labeng' ),
        'services' => __( 'Each row below is one choice the customer can tap (e.g. one specific service). The price is added to their total when they pick it.', 'labeng' ),
        'duration' => __( 'Each row below is one choice the customer can tap (e.g. "24 hours"). The number multiplies the price instead of adding to it — use 1 for no change, 2 to double it, and so on.', 'labeng' ),
    );
    return $hints[ $type ] ?? '';
}
endif;

if ( ! function_exists( 'lab_booking_step_price_label' ) ) :
/** Label for the numeric field — "Price" adds to the total; "Multiplier" scales it. */
function lab_booking_step_price_label( $type ) {
    return $type === 'duration' ? __( 'Multiplier', 'labeng' ) : __( 'Price', 'labeng' );
}
endif;
?>

<h2 class="lab-section-title"><?php esc_html_e( 'Booking Steps', 'labeng' ); ?></h2>

<div class="lab-notice lab-notice--info">
    <p>
        <?php esc_html_e( 'A "step" is one screen your customer walks through when booking — e.g. "pick a service," then "pick a date," then "pay." Steps run in the order shown below, top to bottom, and new steps are added to the end. Removing a step here does not affect any existing bookings.', 'labeng' ); ?>
        <br /><br />
        <strong><?php esc_html_e( "If you don't add any steps, customers automatically get this simple 4-step flow:", 'labeng' ); ?></strong>
        <?php esc_html_e( 'Choose a service → Select date & time → Your details → Review & pay.', 'labeng' ); ?>
    </p>
</div>

<div id="lab-booking-steps-msg" class="lab-msg" style="display:none;"></div>

<div id="lab-booking-steps-list" class="lab-booking-steps-list">
    <?php if ( ! empty( $steps ) ) : ?>
        <?php foreach ( $steps as $index => $step ) : 
            $type = $step['type'] ?? 'services';
            $show_opts = in_array( $type, array( 'vehicles', 'services', 'duration' ), true );
        ?>
        <div class="lab-booking-step-card" data-index="<?php echo esc_attr( $index ); ?>">
            <div class="lab-booking-step-card__eyebrow">
                <span class="lab-step-number"><?php echo esc_html( $index + 1 ); ?></span>
                <span><?php esc_html_e( 'This is what the customer sees at this point in the booking.', 'labeng' ); ?></span>
            </div>
            <div class="lab-booking-step-card__header">
                <div class="lab-field">
                    <label><?php esc_html_e( 'Step Title', 'labeng' ); ?></label>
                    <input type="text" name="step_title[]" value="<?php echo esc_attr( $step['title'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'e.g. Choose a Vehicle', 'labeng' ); ?>" required />
                    <p class="lab-field-hint"><?php esc_html_e( 'Shown to the customer as the heading of this screen.', 'labeng' ); ?></p>
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
                    <p class="lab-field-hint lab-step-type-hint"><?php echo esc_html( lab_booking_step_type_hint( $type ) ); ?></p>
                </div>
                <button type="button" class="lab-btn lab-btn--danger lab-btn--icon lab-step-remove" title="<?php esc_attr_e( 'Remove Step', 'labeng' ); ?>">&times;</button>
            </div>

            <div class="lab-booking-step-card__options" style="display: <?php echo $show_opts ? 'block' : 'none'; ?>;">
                <h4><?php esc_html_e( 'Step Options', 'labeng' ); ?></h4>
                <p class="lab-field-hint lab-step-options-hint"><?php echo esc_html( lab_booking_step_options_hint( $type ) ); ?></p>
                <div class="lab-step-options-list">
                    <?php if ( ! empty( $step['options'] ) ) : ?>
                        <?php foreach ( $step['options'] as $opt_idx => $opt ) : ?>
                        <div class="lab-step-option-row">
                            <div class="lab-field">
                                <label><?php esc_html_e( 'Option Name', 'labeng' ); ?></label>
                                <input type="text" class="opt-name" value="<?php echo esc_attr( $opt['name'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'e.g. Mercedes A-Class', 'labeng' ); ?>" />
                            </div>
                            <div class="lab-field lab-field--sm">
                                <label class="opt-price-label"><?php echo esc_html( lab_booking_step_price_label( $type ) ); ?></label>
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
<p class="lab-field-hint" style="margin-top: 0.75rem;"><?php esc_html_e( 'Customers will see this exact configuration the next time they start a booking with you.', 'labeng' ); ?></p>

<!-- Step Row Template -->
<template id="lab-booking-step-template">
    <div class="lab-booking-step-card">
        <div class="lab-booking-step-card__eyebrow">
            <span class="lab-step-number"></span>
            <span><?php esc_html_e( 'This is what the customer sees at this point in the booking.', 'labeng' ); ?></span>
        </div>
        <div class="lab-booking-step-card__header">
            <div class="lab-field">
                <label><?php esc_html_e( 'Step Title', 'labeng' ); ?></label>
                <input type="text" name="step_title[]" placeholder="<?php esc_attr_e( 'e.g. Choose a Vehicle', 'labeng' ); ?>" required />
                <p class="lab-field-hint"><?php esc_html_e( 'Shown to the customer as the heading of this screen.', 'labeng' ); ?></p>
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
                <p class="lab-field-hint lab-step-type-hint"><?php echo esc_html( lab_booking_step_type_hint( 'services' ) ); ?></p>
            </div>
            <button type="button" class="lab-btn lab-btn--danger lab-btn--icon lab-step-remove">&times;</button>
        </div>

        <div class="lab-booking-step-card__options" style="display: block;">
            <h4><?php esc_html_e( 'Step Options', 'labeng' ); ?></h4>
            <p class="lab-field-hint lab-step-options-hint"><?php echo esc_html( lab_booking_step_options_hint( 'services' ) ); ?></p>
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
            <label class="opt-price-label"><?php echo esc_html( lab_booking_step_price_label( 'services' ) ); ?></label>
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
