<?php
if ( ! defined( 'ABSPATH' ) ) exit;

global $wpdb;
$table = $wpdb->prefix . 'lab_commissions';
$cs    = get_option( 'lab_currency_symbol', '£' );

/* Handle save */
if ( isset( $_POST['lab_commission_save_nonce'] ) && wp_verify_nonce( $_POST['lab_commission_save_nonce'], 'lab_commission_bulk_save' ) ) {
    $biz_id = absint( $_POST['edit_business_id'] ?? 0 );
    $type   = sanitize_text_field( $_POST['edit_commission_type'] ?? 'percentage' );
    $value  = floatval( $_POST['edit_commission_value'] ?? 0 );

    if ( $biz_id ) {
        Lab_Commissions::set_commission( $biz_id, $type, $value );
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Commission updated.', 'labeng' ) . '</p></div>';
    }
}

/* Get all businesses with commission data */
$businesses = get_posts( array(
    'post_type'   => 'lab_business',
    'numberposts' => -1,
    'post_status' => 'any',
) );
?>

<div class="wrap">
    <h1><?php esc_html_e( 'Commission Settings', 'labeng' ); ?></h1>

    <?php
    $grand_revenue    = 0;
    $grand_commission = 0;
    $grand_net        = 0;
    ?>
    <table class="wp-list-table widefat fixed striped" style="margin-top:20px;">
        <thead>
            <tr>
                <th><?php esc_html_e( 'Business', 'labeng' ); ?></th>
                <th><?php esc_html_e( 'Rate', 'labeng' ); ?></th>
                <th><?php esc_html_e( 'Revenue', 'labeng' ); ?></th>
                <th><?php esc_html_e( 'Commission Earned', 'labeng' ); ?></th>
                <th><?php esc_html_e( 'Owner Payout', 'labeng' ); ?></th>
                <th><?php esc_html_e( 'Actions', 'labeng' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ( $businesses as $biz ) :
                $eff      = Lab_Commissions::get_effective( $biz->ID );
                $type     = $eff['type'];
                $value    = $eff['value'];
                $earn     = Lab_Bookings::get_earnings( $biz->ID );
                $grand_revenue    += $earn['revenue'];
                $grand_commission += $earn['commission'];
                $grand_net        += $earn['net'];
            ?>
            <tr>
                <td>
                    <a href="<?php echo esc_url( admin_url( 'post.php?post=' . $biz->ID . '&action=edit' ) ); ?>">
                        <?php echo esc_html( $biz->post_title ); ?>
                    </a>
                </td>
                <td>
                    <?php
                    echo $type === 'percentage' ? esc_html( $value . '%' ) : esc_html( $cs . number_format( floatval( $value ), 2 ) );
                    if ( $eff['is_default'] ) {
                        echo ' <span style="color:#888;font-size:11px;">(' . esc_html__( 'default', 'labeng' ) . ')</span>';
                    }
                    ?>
                </td>
                <td><?php echo esc_html( $cs . number_format( $earn['revenue'], 2 ) ); ?></td>
                <td><strong style="color:#2271b1;"><?php echo esc_html( $cs . number_format( $earn['commission'], 2 ) ); ?></strong></td>
                <td><?php echo esc_html( $cs . number_format( $earn['net'], 2 ) ); ?></td>
                <td>
                    <a href="<?php echo esc_url( admin_url( 'post.php?post=' . $biz->ID . '&action=edit' ) ); ?>" class="button button-small"><?php esc_html_e( 'Edit', 'labeng' ); ?></a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <th><?php esc_html_e( 'Totals', 'labeng' ); ?></th>
                <th></th>
                <th><?php echo esc_html( $cs . number_format( $grand_revenue, 2 ) ); ?></th>
                <th><strong style="color:#2271b1;"><?php echo esc_html( $cs . number_format( $grand_commission, 2 ) ); ?></strong></th>
                <th><?php echo esc_html( $cs . number_format( $grand_net, 2 ) ); ?></th>
                <th></th>
            </tr>
        </tfoot>
    </table>

    <hr style="margin:30px 0;" />

    <h2><?php esc_html_e( 'Quick Set Commission', 'labeng' ); ?></h2>
    <form method="post" style="max-width:500px;">
        <?php wp_nonce_field( 'lab_commission_bulk_save', 'lab_commission_save_nonce' ); ?>
        <table class="form-table">
            <tr>
                <th><label for="edit_business_id"><?php esc_html_e( 'Business', 'labeng' ); ?></label></th>
                <td>
                    <select id="edit_business_id" name="edit_business_id" required>
                        <option value=""><?php esc_html_e( 'Select', 'labeng' ); ?></option>
                        <?php foreach ( $businesses as $biz ) : ?>
                            <option value="<?php echo esc_attr( $biz->ID ); ?>"><?php echo esc_html( $biz->post_title ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="edit_commission_type"><?php esc_html_e( 'Type', 'labeng' ); ?></label></th>
                <td>
                    <select id="edit_commission_type" name="edit_commission_type">
                        <option value="percentage"><?php esc_html_e( 'Percentage', 'labeng' ); ?></option>
                        <option value="fixed"><?php esc_html_e( 'Fixed Amount', 'labeng' ); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="edit_commission_value"><?php esc_html_e( 'Value', 'labeng' ); ?></label></th>
                <td><input type="number" id="edit_commission_value" name="edit_commission_value" step="0.01" min="0" required /></td>
            </tr>
        </table>
        <?php submit_button( __( 'Save Commission', 'labeng' ) ); ?>
    </form>
</div>
