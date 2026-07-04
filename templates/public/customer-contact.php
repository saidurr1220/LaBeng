<?php
/**
 * Customer Contact page — customer enquiries.
 * Separate from the Partner/Business contact page (templates/public/partner.php).
 * Reuses the partner page styling for a matching design & responsive layout.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

labeng_get_header();
?>

<div class="lab-partner-page">
    <div class="lab-partner-split">

        <div class="lab-partner-left">
            <h1 class="lab-partner-title">Contact <span class="lab-script">LaBeng</span></h1>
            <p class="lab-partner-subtitle">Have an enquiry or need a hand?</p>

            <ul class="lab-partner-benefits">
                <li>
                    <svg viewBox="0 0 24 24" width="32" height="32" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
                    <span>Get quick answers</span>
                </li>
                <li>
                    <svg viewBox="0 0 24 24" width="32" height="32" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                    <span>Friendly support</span>
                </li>
                <li>
                    <svg viewBox="0 0 24 24" width="32" height="32" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                    <span>We reply by email</span>
                </li>
            </ul>

            <div class="lab-partner-cta-box">
                <strong>Need help with a booking?</strong>
                <p>Send us a message and we'll get back to you as soon as we can.</p>
            </div>
        </div>

        <div class="lab-partner-right">
            <div class="lab-partner-card">
                <h2>Send us a message</h2>
                <p class="lab-partner-card__sub">Fill in the form below and our team will be in touch.</p>

                <div id="lab-customer-contact-msg" class="lab-msg" style="display:none;"></div>

                <form id="lab-customer-contact-form" class="lab-form" novalidate>
                    <div class="lab-form-row lab-form-row--2col">
                        <div class="lab-field">
                            <label>Your Name</label>
                            <input type="text" name="name" placeholder="e.g. Jane Doe" required />
                        </div>
                        <div class="lab-field">
                            <label>Your Email</label>
                            <input type="email" name="email" placeholder="e.g. jane@example.com" required />
                        </div>
                    </div>
                    <div class="lab-field">
                        <label>Subject <span class="lab-field-optional">(optional)</span></label>
                        <input type="text" name="subject" placeholder="e.g. Question about a booking" />
                    </div>
                    <div class="lab-field">
                        <label>Message</label>
                        <textarea name="message" rows="4" placeholder="How can we help you?" required></textarea>
                    </div>
                    <button type="submit" class="lab-btn lab-btn--primary lab-btn--full">Submit</button>
                </form>
            </div>
        </div>

    </div>
</div>

<?php
labeng_get_footer();
