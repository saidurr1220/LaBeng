<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$user        = wp_get_current_user();
$business    = get_posts( array(
    'post_type'   => 'lab_business',
    'meta_key'    => '_lab_owner_id',
    'meta_value'  => $user->ID,
    'numberposts' => 1,
    'post_status' => array( 'publish', 'draft', 'pending' ),
) );
$business    = ! empty( $business ) ? $business[0] : null;
$business_id = $business ? $business->ID : 0;
$biz_status  = $business ? get_post_meta( $business_id, '_lab_status', true ) : 'pending';

$nav_items = array(
    'overview'    => array( 'label' => 'Overview',     'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>' ),
    'my-business' => array( 'label' => 'My Business',  'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>' ),
    'services'    => array( 'label' => 'Services',     'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>' ),
    'deals'       => array( 'label' => 'Deals',        'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>' ),
    'availability'=> array( 'label' => 'Availability', 'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>' ),
    'bookings'    => array( 'label' => 'Bookings',     'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>' ),
    'reviews'     => array( 'label' => 'Reviews',      'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>' ),
    'statistics'  => array( 'label' => 'Statistics',   'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>' ),
);
?>
<div class="lab-dashboard" id="lab-business-dashboard" data-business-id="<?php echo esc_attr( $business_id ); ?>">

    <!-- Mobile top bar -->
    <div class="lab-dash-topbar">
        <button class="lab-dash-topbar__menu" id="lab-dash-hamburger" aria-label="Menu" aria-expanded="false">
            <span></span><span></span><span></span>
        </button>
        <span class="lab-dash-topbar__title">Business Dashboard</span>
        <span class="lab-dash-topbar__user"><?php echo esc_html( $user->display_name ); ?></span>
    </div>

    <!-- Sidebar overlay -->
    <div class="lab-sidebar-overlay" id="lab-sidebar-overlay"></div>

    <!-- Sidebar -->
    <aside class="lab-sidebar" id="lab-sidebar">
        <div class="lab-sidebar__brand">
            <span class="lab-sidebar__brand-name">LaBeng</span>
            <span class="lab-sidebar__brand-sub">Business Portal</span>
        </div>
        <nav class="lab-sidebar__nav">
            <?php foreach ( $nav_items as $key => $item ) : ?>
            <a href="#<?php echo esc_attr( $key ); ?>" class="lab-sidebar__link<?php echo $key === 'overview' ? ' lab-sidebar__link--active' : ''; ?>" data-tab="<?php echo esc_attr( $key ); ?>">
                <span class="lab-sidebar__icon"><?php echo $item['icon']; ?></span>
                <span><?php echo esc_html( $item['label'] ); ?></span>
            </a>
            <?php endforeach; ?>
        </nav>
        <div class="lab-sidebar__footer">
            <a href="<?php echo esc_url( wp_logout_url( home_url( '/login/' ) ) ); ?>" class="lab-sidebar__link lab-sidebar__link--logout">
                <span class="lab-sidebar__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg></span>
                <span><?php esc_html_e( 'Logout', 'labeng' ); ?></span>
            </a>
        </div>
    </aside>

    <!-- Main content -->
    <main class="lab-main">
        <header class="lab-main__header">
            <div>
                <h1 class="lab-main__title"><?php esc_html_e( 'Business Dashboard', 'labeng' ); ?></h1>
                <?php if ( $business ) : ?>
                <p class="lab-main__subtitle"><?php echo esc_html( $business->post_title ); ?></p>
                <?php endif; ?>
            </div>
            <div class="lab-main__user">
                <div class="lab-main__avatar"><?php echo esc_html( strtoupper( substr( $user->display_name, 0, 1 ) ) ); ?></div>
                <span><?php echo esc_html( $user->display_name ); ?></span>
            </div>
        </header>

        <?php if ( $biz_status === 'pending' ) : ?>
            <div class="lab-notice lab-notice--warning">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <p><?php esc_html_e( 'Your business listing is pending admin approval. Some features may be limited until approved.', 'labeng' ); ?></p>
            </div>
        <?php elseif ( $biz_status === 'suspended' ) : ?>
            <div class="lab-notice lab-notice--danger">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
                <p><?php esc_html_e( 'Your business listing has been suspended. Please contact support.', 'labeng' ); ?></p>
            </div>
        <?php endif; ?>

        <div class="lab-tab-content" id="lab-tab-overview"><?php include LABENG_PATH . 'templates/business/tab-overview.php'; ?></div>
        <div class="lab-tab-content" id="lab-tab-my-business" style="display:none;"><?php include LABENG_PATH . 'templates/business/tab-my-business.php'; ?></div>
        <div class="lab-tab-content" id="lab-tab-services" style="display:none;"><?php include LABENG_PATH . 'templates/business/tab-services.php'; ?></div>
        <div class="lab-tab-content" id="lab-tab-deals" style="display:none;"><?php include LABENG_PATH . 'templates/business/tab-deals.php'; ?></div>
        <div class="lab-tab-content" id="lab-tab-availability" style="display:none;"><?php include LABENG_PATH . 'templates/business/tab-availability.php'; ?></div>
        <div class="lab-tab-content" id="lab-tab-bookings" style="display:none;"><?php include LABENG_PATH . 'templates/business/tab-bookings.php'; ?></div>
        <div class="lab-tab-content" id="lab-tab-reviews" style="display:none;"><?php include LABENG_PATH . 'templates/business/tab-reviews.php'; ?></div>
        <div class="lab-tab-content" id="lab-tab-statistics" style="display:none;"><?php include LABENG_PATH . 'templates/business/tab-statistics.php'; ?></div>
    </main>
</div>
