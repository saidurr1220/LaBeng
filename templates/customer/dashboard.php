<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$user = wp_get_current_user();
?>
<div class="lab-dashboard" id="lab-customer-dashboard">

    <!-- Mobile top bar -->
    <div class="lab-dash-topbar">
        <button class="lab-dash-topbar__menu" id="lab-dash-hamburger" aria-label="Menu" aria-expanded="false">
            <span></span><span></span><span></span>
        </button>
        <span class="lab-dash-topbar__title">Customer Dashboard</span>
        <span class="lab-dash-topbar__user"><?php echo esc_html( $user->display_name ); ?></span>
    </div>

    <!-- Sidebar overlay -->
    <div class="lab-sidebar-overlay" id="lab-sidebar-overlay"></div>

    <!-- Sidebar -->
    <aside class="lab-sidebar" id="lab-sidebar">
        <div class="lab-sidebar__brand">
            <span class="lab-sidebar__brand-name">LaBeng</span>
            <span class="lab-sidebar__brand-sub">Customer Portal</span>
        </div>
        <nav class="lab-sidebar__nav">
            <a href="<?php echo esc_url( home_url( '/businesses/' ) ); ?>" class="lab-sidebar__link lab-sidebar__link--browse">
                <span class="lab-sidebar__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg></span>
                <span><?php esc_html_e( 'Browse Businesses', 'labeng' ); ?></span>
            </a>
            <a href="#bookings" class="lab-sidebar__link lab-sidebar__link--active" data-tab="bookings">
                <span class="lab-sidebar__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></span>
                <span><?php esc_html_e( 'My Bookings', 'labeng' ); ?></span>
            </a>
            <a href="#invoices" class="lab-sidebar__link" data-tab="invoices">
                <span class="lab-sidebar__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg></span>
                <span><?php esc_html_e( 'My Invoices', 'labeng' ); ?></span>
            </a>
            <a href="#profile" class="lab-sidebar__link" data-tab="profile">
                <span class="lab-sidebar__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></span>
                <span><?php esc_html_e( 'My Profile', 'labeng' ); ?></span>
            </a>
            <a href="#reviews" class="lab-sidebar__link" data-tab="reviews">
                <span class="lab-sidebar__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg></span>
                <span><?php esc_html_e( 'My Reviews', 'labeng' ); ?></span>
            </a>
        </nav>
        <div class="lab-sidebar__footer">
            <a href="<?php echo esc_url( wp_logout_url( home_url( '/login/' ) ) ); ?>" class="lab-sidebar__link lab-sidebar__link--logout">
                <span class="lab-sidebar__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg></span>
                <span><?php esc_html_e( 'Logout', 'labeng' ); ?></span>
            </a>
        </div>
    </aside>

    <!-- Main content -->
    <main class="lab-main">
        <header class="lab-main__header">
            <h1 class="lab-main__title"><?php esc_html_e( 'My Dashboard', 'labeng' ); ?></h1>
            <div class="lab-main__user">
                <?php echo esc_html( $user->display_name ); ?>
            </div>
        </header>

        <div class="lab-tab-content" id="lab-tab-bookings"><?php include LABENG_PATH . 'templates/customer/tab-bookings.php'; ?></div>
        <div class="lab-tab-content" id="lab-tab-invoices" style="display:none;"><?php include LABENG_PATH . 'templates/customer/tab-invoices.php'; ?></div>
        <div class="lab-tab-content" id="lab-tab-profile" style="display:none;"><?php include LABENG_PATH . 'templates/customer/tab-profile.php'; ?></div>
        <div class="lab-tab-content" id="lab-tab-reviews" style="display:none;"><?php include LABENG_PATH . 'templates/customer/tab-reviews.php'; ?></div>
    </main>
</div>
