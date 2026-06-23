<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$partner_categories = get_terms( array( 'taxonomy' => 'lab_category', 'hide_empty' => false ) );

labeng_get_header();
?>

<div class="lab-partner-page">
    <div class="lab-partner-split">

        <div class="lab-partner-left">
            <h1 class="lab-partner-title">Partner with <span class="lab-script">LaBeng</span></h1>
            <p class="lab-partner-subtitle">Want to get your business in front of more customers? Let's work together.</p>

            <ul class="lab-partner-benefits">
                <li>
                    <svg viewBox="0 0 24 24" width="32" height="32" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    <span>Increase visibility</span>
                </li>
                <li>
                    <svg viewBox="0 0 24 24" width="32" height="32" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    <span>Reach more customers</span>
                </li>
                <li>
                    <svg viewBox="0 0 24 24" width="32" height="32" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
                    <span>Grow your business</span>
                </li>
            </ul>

            <div class="lab-partner-cta-box">
                <strong>Have Questions?</strong>
                <p>We're here to help - send us a message and we'll get back to you</p>
            </div>
        </div>

        <div class="lab-partner-right">
            <div class="lab-partner-card">
                <h2>Get in touch</h2>
                <p class="lab-partner-card__sub">Have a question before signing up? Send us a message and we'll get back to you.</p>

                <div id="lab-partner-msg" class="lab-msg" style="display:none;"></div>

                <form id="lab-partner-form" class="lab-form" novalidate>
                    <div class="lab-form-row lab-form-row--2col">
                        <div class="lab-field">
                            <label>Your Name</label>
                            <input type="text" name="name" placeholder="e.g. John Smith" required />
                        </div>
                        <div class="lab-field">
                            <label>Your Email</label>
                            <input type="email" name="email" placeholder="e.g. admin@cars4u.com" required />
                        </div>
                    </div>
                    <div class="lab-form-row lab-form-row--2col">
                        <div class="lab-field">
                            <label>Your Business Name</label>
                            <input type="text" name="business_name" placeholder="e.g. Cars4U" />
                        </div>
                        <div class="lab-field">
                            <label>Category <span class="lab-field-optional">(optional)</span></label>
                            <select name="category">
                                <option value="">Select a category</option>
                                <?php if ( ! is_wp_error( $partner_categories ) && ! empty( $partner_categories ) ) : ?>
                                    <?php foreach ( $partner_categories as $cat ) : ?>
                                        <option value="<?php echo esc_attr( $cat->slug ); ?>"><?php echo esc_html( $cat->name ); ?></option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>
                    <div class="lab-field">
                        <label>Message</label>
                        <textarea name="message" rows="4" placeholder="Tell us about your business and how we can help" required></textarea>
                    </div>
                    <button type="submit" class="lab-btn lab-btn--primary lab-btn--full">Submit</button>
                </form>
            </div>
        </div>

    </div>
</div>

<?php
labeng_get_footer();
