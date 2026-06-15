<?php
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! is_user_logged_in() || current_user_can( 'lab_manage_own_business' ) ) {
    wp_safe_redirect( home_url( '/login/' ) );
    exit;
}

include LABENG_PATH . 'templates/global/header.php';
?>

<div class="lab-dashboard-wrapper">
    <?php include LABENG_PATH . 'templates/customer/dashboard.php'; ?>
</div>

<?php
include LABENG_PATH . 'templates/global/footer.php';
