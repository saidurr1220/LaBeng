<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class Lab_Commissions
 * Calculates commissions from wp_lab_commissions table.
 */
class Lab_Commissions {

    public static function init() {
        /* No additional hooks needed — calculation is called from bookings */
    }

    /**
     * Calculate commission for a business on a given service price.
     *
     * @param int   $business_id
     * @param float $service_price
     * @return float
     */
    public static function calculate( $business_id, $service_price ) {
        $eff = self::get_effective( $business_id );

        $type  = $eff['type'];
        $value = floatval( $eff['value'] );

        if ( $type === 'percentage' ) {
            return round( ( $value / 100 ) * $service_price, 2 );
        }

        if ( $type === 'fixed' ) {
            return $value;
        }

        return 0.00;
    }

    /**
     * Get the platform default commission (used when a business has no override).
     *
     * @return array{type:string,value:float}
     */
    public static function get_default() {
        return array(
            'type'  => get_option( 'lab_default_commission_type', 'percentage' ),
            'value' => floatval( get_option( 'lab_default_commission_value', 10 ) ),
        );
    }

    /**
     * Get the effective commission for a business: its own row if set,
     * otherwise the platform default.
     *
     * @param int $business_id
     * @return array{type:string,value:float,is_default:bool}
     */
    public static function get_effective( $business_id ) {
        $row = self::get_commission( $business_id );
        if ( $row ) {
            return array(
                'type'       => $row->commission_type,
                'value'      => floatval( $row->commission_value ),
                'is_default' => false,
            );
        }
        $default = self::get_default();
        $default['is_default'] = true;
        return $default;
    }

    /**
     * Ensure a business has a commission row; create one from the
     * platform default if missing. Called on approval.
     *
     * @param int $business_id
     */
    public static function ensure_commission( $business_id ) {
        if ( self::get_commission( $business_id ) ) {
            return;
        }
        $default = self::get_default();
        self::set_commission( $business_id, $default['type'], $default['value'] );
    }

    /**
     * Get commission settings for a business.
     *
     * @param int $business_id
     * @return object|null
     */
    public static function get_commission( $business_id ) {
        global $wpdb;
        $table = $wpdb->prefix . 'lab_commissions';
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$table} WHERE business_id = %d",
            $business_id
        ) );
    }

    /**
     * Set commission for a business.
     *
     * @param int    $business_id
     * @param string $type  'percentage' or 'fixed'
     * @param float  $value
     */
    public static function set_commission( $business_id, $type, $value ) {
        global $wpdb;
        $table = $wpdb->prefix . 'lab_commissions';

        $exists = $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM {$table} WHERE business_id = %d",
            $business_id
        ) );

        if ( $exists ) {
            $wpdb->update(
                $table,
                array( 'commission_type' => $type, 'commission_value' => $value ),
                array( 'business_id' => $business_id ),
                array( '%s', '%f' ),
                array( '%d' )
            );
        } else {
            $wpdb->insert(
                $table,
                array( 'business_id' => $business_id, 'commission_type' => $type, 'commission_value' => $value ),
                array( '%d', '%s', '%f' )
            );
        }
    }
}
