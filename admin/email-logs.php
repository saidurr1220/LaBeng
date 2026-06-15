<?php
if ( ! defined( 'ABSPATH' ) ) exit;

global $wpdb;
$table_name = $wpdb->prefix . 'lab_email_logs';

// Clear logs action
if ( isset( $_POST['lab_clear_logs_nonce'] ) && wp_verify_nonce( $_POST['lab_clear_logs_nonce'], 'lab_clear_email_logs' ) ) {
    $wpdb->query( "TRUNCATE TABLE {$table_name}" );
    echo '<div class="notice notice-success"><p>' . esc_html__( 'Email logs cleared.', 'labeng' ) . '</p></div>';
}

$logs = $wpdb->get_results( "SELECT * FROM {$table_name} ORDER BY sent_at DESC LIMIT 100" );
?>
<div class="wrap">
    <h1 class="wp-heading-inline"><?php esc_html_e( 'Email Logs', 'labeng' ); ?></h1>
    <form method="post" style="display:inline-block; margin-left: 10px;">
        <?php wp_nonce_field( 'lab_clear_email_logs', 'lab_clear_logs_nonce' ); ?>
        <button type="submit" class="page-title-action" onclick="return confirm('Are you sure you want to clear all logs?');"><?php esc_html_e( 'Clear Logs', 'labeng' ); ?></button>
    </form>
    <hr class="wp-header-end">
    
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th scope="col" class="manage-column column-date">Date</th>
                <th scope="col" class="manage-column column-recipient">Recipient</th>
                <th scope="col" class="manage-column column-subject">Subject</th>
                <th scope="col" class="manage-column column-status">Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if ( empty( $logs ) ) : ?>
                <tr>
                    <td colspan="4">No emails logged yet.</td>
                </tr>
            <?php else : ?>
                <?php foreach ( $logs as $log ) : ?>
                    <tr>
                        <td><?php echo esc_html( $log->sent_at ); ?></td>
                        <td><?php echo esc_html( $log->recipient ); ?></td>
                        <td><?php echo esc_html( $log->subject ); ?></td>
                        <td>
                            <?php if ( $log->status === 'sent' ) : ?>
                                <span style="color: green; font-weight: bold;">Sent</span>
                            <?php else : ?>
                                <span style="color: red; font-weight: bold;">Failed</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
