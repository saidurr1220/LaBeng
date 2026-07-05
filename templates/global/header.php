<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$current_url = home_url( $_SERVER['REQUEST_URI'] );
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title><?php wp_title( '|', true, 'right' ); bloginfo( 'name' ); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Great+Vibes&family=Inter:wght@400;500;600;700&family=Sora:wght@400;600;700;800&display=swap" rel="stylesheet">
    <?php wp_head(); ?>
    <style>
    /* Critical mobile header alignment & padding fixes to prevent overlap & gaps */
    @media (max-width: 768px) {
        .lab-explore-container,
        .lab-deals-container,
        .lab-partner-page,
        .lab-single-container,
        .lab-auth-page {
            padding-top: 8rem !important;
        }
    }
    @media screen and (max-width: 600px) {
        body.admin-bar .lab-global-header {
            top: 0 !important;
        }
        body.admin-bar .lab-global-header__nav {
            top: -46px !important;
            height: calc(100dvh + 46px) !important;
        }
    }
    </style>
</head>
<body <?php body_class( 'labeng-dark-mode' ); ?>>
    <div class="lab-transition-overlay"></div>
    <noscript>
        <style>
            .lab-transition-overlay { display: none !important; }
        </style>
    </noscript>
    <script>
    setTimeout(function() {
        var overlay = document.querySelector('.lab-transition-overlay');
        if (overlay) {
            overlay.classList.add('is-loaded');
            setTimeout(function() {
                overlay.style.display = 'none';
            }, 300);
        }
    }, 1500);
    </script>
<?php
$header_class = 'lab-global-header';
if ( ! is_front_page() && ! is_page( 'labeng-home' ) ) {
    $header_class .= ' lab-global-header--scrolled';
} else {
    $header_class .= ' lab-global-header--home';
}
?>
    <header class="<?php echo esc_attr( $header_class ); ?>">
        <div class="lab-global-header__inner">
            <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="lab-global-header__logo">
                    <span class="blue-text">LA</span><span>BENG</span>
            </a>
            
            <button class="lab-hamburger" id="lab-hamburger" aria-label="Toggle navigation" aria-expanded="false">
                <span></span>
                <span></span>
                <span></span>
            </button>

            <nav class="lab-global-header__nav" id="lab-main-nav">
                <div class="lab-nav-links">
                    <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="<?php echo trailingslashit( home_url( '/' ) ) === trailingslashit( $current_url ) ? 'active' : ''; ?>">Home</a>
                    <a href="<?php echo esc_url( home_url( '/businesses/' ) ); ?>" class="<?php echo strpos( $current_url, '/businesses/' ) !== false ? 'active' : ''; ?>">Discover</a>
                    <a href="<?php echo esc_url( home_url( '/deals/' ) ); ?>" class="<?php echo strpos( $current_url, '/deals/' ) !== false ? 'active' : ''; ?>">Deals</a>
                    <a href="<?php echo esc_url( home_url( '/contact-us/' ) ); ?>" class="<?php echo strpos( $current_url, '/contact-us/' ) !== false ? 'active' : ''; ?>">Contact</a>
                    <?php if ( is_user_logged_in() ) : ?>
                        <?php
                        $user = wp_get_current_user();
                        if ( in_array( 'business_owner', $user->roles, true ) ) : ?>
                            <a href="<?php echo esc_url( home_url( '/business-dashboard/' ) ); ?>" class="<?php echo strpos( $current_url, '/business-dashboard/' ) !== false ? 'active' : ''; ?>">Insights</a>
                        <?php else : ?>
                            <a href="<?php echo esc_url( home_url( '/customer-dashboard/' ) ); ?>" class="<?php echo strpos( $current_url, '/customer-dashboard/' ) !== false ? 'active' : ''; ?>">Profile</a>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <div class="lab-nav-auth">
                    <?php
                    $is_biz_owner = is_user_logged_in() && in_array( 'business_owner', wp_get_current_user()->roles, true );
                    if ( ! $is_biz_owner ) : ?>
                    <a href="<?php echo esc_url( home_url( '/partner/' ) ); ?>" class="lab-global-header__btn lab-global-header__btn--biz">For Business</a>
                    <?php endif; ?>
                    <?php if ( is_user_logged_in() ) : ?>
                        <a href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>" class="lab-global-header__btn lab-global-header__btn--logout">Log out</a>
                    <?php else : ?>
                        <a href="<?php echo esc_url( home_url( '/login/' ) ); ?>" class="lab-global-header__btn">Log in</a>
                    <?php endif; ?>
                </div>
            </nav>
        </div>
    </header>
    <main class="lab-global-main">
