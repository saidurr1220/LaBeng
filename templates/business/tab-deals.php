<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/* Get deals for this business */
$deals = get_posts( array(
    'post_type'   => 'lab_deal',
    'numberposts' => -1,
    'meta_key'    => '_lab_deal_business_id',
    'meta_value'  => $business_id,
    'post_status' => array( 'publish', 'draft' ),
) );
?>

<h2 class="lab-section-title"><?php esc_html_e( 'Deals & Promotions', 'labeng' ); ?></h2>

<div id="lab-deals-msg" class="lab-msg" style="display:none;"></div>

<!-- Existing deals -->
<?php if ( ! empty( $deals ) ) : ?>
<div class="lab-table-wrap">
    <table class="lab-table" id="lab-deals-table">
        <thead>
            <tr>
                <th><?php esc_html_e( 'Title', 'labeng' ); ?></th>
                <th><?php esc_html_e( 'Discount', 'labeng' ); ?></th>
                <th><?php esc_html_e( 'Valid Until', 'labeng' ); ?></th>
                <th><?php esc_html_e( 'Status', 'labeng' ); ?></th>
                <th><?php esc_html_e( 'Actions', 'labeng' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ( $deals as $deal ) :
                $discount    = get_post_meta( $deal->ID, '_lab_deal_discount', true );
                $valid_until = get_post_meta( $deal->ID, '_lab_deal_valid_until', true );
                $deal_status = get_post_meta( $deal->ID, '_lab_deal_status', true );
            ?>
            <tr id="lab-deal-row-<?php echo esc_attr( $deal->ID ); ?>">
                <td><?php echo esc_html( $deal->post_title ); ?></td>
                <td><?php echo esc_html( $discount ); ?></td>
                <td><?php echo esc_html( $valid_until ? date( 'M j, Y', strtotime( $valid_until ) ) : '—' ); ?></td>
                <td><span class="lab-badge lab-badge--<?php echo $deal_status === 'active' ? 'confirmed' : 'cancelled'; ?>"><?php echo esc_html( ucfirst( $deal_status ) ); ?></span></td>
                <td>
                    <button type="button" class="lab-btn lab-btn--sm lab-btn--danger lab-delete-deal" data-deal-id="<?php echo esc_attr( $deal->ID ); ?>"><?php esc_html_e( 'Delete', 'labeng' ); ?></button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php else : ?>
    <div class="lab-empty-state">
        <p><?php esc_html_e( 'No deals yet. Create one below to attract more customers!', 'labeng' ); ?></p>
    </div>
<?php endif; ?>

<!-- Add deal form -->
<div class="lab-card lab-card--form" style="margin-top:24px;">
    <h3 class="lab-card__title"><?php esc_html_e( 'Add New Deal', 'labeng' ); ?></h3>
    <form id="lab-add-deal-form" class="lab-form">
        <div class="lab-form-row lab-form-row--2col">
            <div class="lab-field">
                <label for="lab-deal-title"><?php esc_html_e( 'Deal Title', 'labeng' ); ?></label>
                <input type="text" id="lab-deal-title" name="title" placeholder="<?php esc_attr_e( 'e.g. Summer Special', 'labeng' ); ?>" required />
            </div>
            <div class="lab-field">
                <label for="lab-deal-discount"><?php esc_html_e( 'Discount Description', 'labeng' ); ?></label>
                <input type="text" id="lab-deal-discount" name="discount" placeholder="<?php esc_attr_e( 'e.g. 20% off weekends', 'labeng' ); ?>" required />
            </div>
        </div>
        <div class="lab-form-row lab-form-row--2col">
            <div class="lab-field">
                <label for="lab-deal-valid"><?php esc_html_e( 'Valid Until', 'labeng' ); ?></label>
                <input type="date" id="lab-deal-valid" name="valid_until" required />
            </div>
            <div class="lab-field" style="display:flex;align-items:flex-end;">
                <button type="submit" class="lab-btn lab-btn--primary"><?php esc_html_e( 'Create Deal', 'labeng' ); ?></button>
            </div>
        </div>
        <div class="lab-field">
            <label for="lab-deal-desc"><?php esc_html_e( 'Description (optional)', 'labeng' ); ?></label>
            <textarea id="lab-deal-desc" name="description" rows="3"></textarea>
        </div>
    </form>
</div>
