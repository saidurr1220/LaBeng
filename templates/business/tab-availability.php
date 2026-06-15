<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$days  = Lab_Availability::$days;
$hours = Lab_Availability::get_business_hours( $business_id );
?>

<h2 class="lab-section-title"><?php esc_html_e( 'Availability', 'labeng' ); ?></h2>
<p class="lab-section-desc"><?php esc_html_e( 'Set your opening hours for each day of the week. Uncheck a day to mark it as closed.', 'labeng' ); ?></p>

<div id="lab-avail-msg" class="lab-msg" style="display:none;"></div>

<div class="lab-avail-quickfill">
    <button type="button" data-qf="weekdays">Mon–Fri 9–5</button>
    <button type="button" data-qf="everyday">Every Day 9–6</button>
    <button type="button" data-qf="clearall">Clear All</button>
</div>

<form id="lab-availability-form" class="lab-form">
    <div class="lab-availability-grid">
        <?php foreach ( $days as $day ) :
            $open   = $hours[ $day ]['open'];
            $close  = $hours[ $day ]['close'];
            $is_open = ! empty( $open );
        ?>
        <div class="lab-avail-row">
            <div class="lab-avail-row__day">
                <label class="lab-checkbox">
                    <input type="checkbox"
                           name="<?php echo esc_attr( $day ); ?>_open_check"
                           value="1"
                           class="lab-avail-check"
                           data-day="<?php echo esc_attr( $day ); ?>"
                           <?php checked( $is_open ); ?> />
                    <span class="lab-checkbox__label"><?php echo esc_html( ucfirst( $day ) ); ?></span>
                </label>
            </div>
            <div class="lab-avail-row__times">
                <div class="lab-field lab-field--sm">
                    <label><?php esc_html_e( 'Open', 'labeng' ); ?></label>
                    <input type="time" name="<?php echo esc_attr( $day ); ?>_open"
                           value="<?php echo esc_attr( $open ); ?>"
                           <?php echo ! $is_open ? 'disabled' : ''; ?>
                           class="lab-avail-time" />
                </div>
                <div class="lab-field lab-field--sm">
                    <label><?php esc_html_e( 'Close', 'labeng' ); ?></label>
                    <input type="time" name="<?php echo esc_attr( $day ); ?>_close"
                           value="<?php echo esc_attr( $close ); ?>"
                           <?php echo ! $is_open ? 'disabled' : ''; ?>
                           class="lab-avail-time" />
                </div>
            </div>
            <div class="lab-avail-row__status">
                <span class="lab-avail-status <?php echo $is_open ? 'lab-avail-status--open' : 'lab-avail-status--closed'; ?>">
                    <?php echo $is_open ? esc_html__( 'Open', 'labeng' ) : esc_html__( 'Closed', 'labeng' ); ?>
                </span>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <button type="submit" class="lab-btn lab-btn--primary" style="margin-top:20px;"><?php esc_html_e( 'Save Availability', 'labeng' ); ?></button>
</form>
