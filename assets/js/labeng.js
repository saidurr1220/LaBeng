/* ================================================================
   LABENG — Main JavaScript
   Vanilla JS + jQuery for AJAX
   ================================================================ */

(function($) {
    'use strict';

    if (typeof labVars === 'undefined') return;

    var ajaxurl = labVars.ajaxurl;
    var nonce   = labVars.nonce;

    if ($('.lab-dashboard').length > 0) {
        $('body').addClass('lab-in-dashboard');

        /* Measure the real admin-bar height instead of guessing 32/46px —
           hosting setups can render it at a different height than WP core defaults. */
        var syncAdminBarHeight = function() {
            var $bar = $('#wpadminbar');
            var h = ($bar.length && $bar.is(':visible')) ? $bar.outerHeight() : 0;
            document.documentElement.style.setProperty('--lab-adminbar-h', h + 'px');
        };
        syncAdminBarHeight();
        $(window).on('resize', syncAdminBarHeight);
    }

    /* ── Hamburger Mobile Menu ───────────────────────────────── */
    var $hamburger = $('#lab-hamburger');
    var $nav       = $('#lab-main-nav');

    function labCloseNav() {
        $hamburger.removeClass('open').attr('aria-expanded', false);
        $nav.removeClass('lab-nav-open');
        $('body').removeClass('lab-nav-locked');
    }

    $hamburger.on('click', function() {
        var isOpen = $nav.hasClass('lab-nav-open');
        $hamburger.toggleClass('open', !isOpen).attr('aria-expanded', !isOpen);
        $nav.toggleClass('lab-nav-open', !isOpen);
        $('body').toggleClass('lab-nav-locked', !isOpen);
    });

    // Close menu when a nav link is tapped
    $nav.on('click', 'a', function() {
        labCloseNav();
    });

    // Close menu when clicking outside
    $(document).on('click', function(e) {
        if ($nav.hasClass('lab-nav-open') && !$(e.target).closest('.lab-global-header').length) {
            labCloseNav();
        }
    });

    /* ── Utility: Show Message ────────────────────────────────── */
    function showMsg(selector, message, type) {
        var $el = $(selector);
        $el.removeClass('success error')
           .addClass(type === 'success' ? 'success' : 'error')
           .html(message)
           .slideDown(200);
        setTimeout(function() { $el.slideUp(300); }, 5000);
    }

    /* ── 1. Dashboard Tab Switching ───────────────────────────── */
    $(document).on('click', '.lab-sidebar__link[data-tab], .lab-quick-link[data-tab]', function(e) {
        e.preventDefault();
        var tab = $(this).data('tab');

        /* Update sidebar active state */
        $('.lab-sidebar__link').removeClass('lab-sidebar__link--active');
        $('.lab-sidebar__link[data-tab="' + tab + '"]').addClass('lab-sidebar__link--active');

        /* Show/hide tab content */
        $('.lab-tab-content').hide();
        $('#lab-tab-' + tab).css('display', 'block').hide().fadeIn(200);

        /* Close mobile sidebar */
        labDashSidebarClose();
    });

    /* ── Mobile Sidebar Toggle ────────────────────────────────── */
    function labDashSidebarOpen() {
        $('#lab-sidebar').addClass('lab-sidebar--open');
        $('#lab-sidebar-overlay').addClass('active');
        $('body').css('overflow', 'hidden');
    }
    function labDashSidebarClose() {
        $('#lab-sidebar').removeClass('lab-sidebar--open');
        $('#lab-sidebar-overlay').removeClass('active');
        $('body').css('overflow', '');
    }
    $(document).on('click', '#lab-dash-hamburger', labDashSidebarOpen);
    $(document).on('click', '#lab-sidebar-overlay, #lab-sidebar-close', labDashSidebarClose);

    /* ── 2. Services Tab: Add/Remove/Save ─────────────────────── */
    $(document).on('click', '#lab-add-service', function() {
        var template = document.getElementById('lab-service-row-template');
        if (template) {
            var clone = template.content.cloneNode(true);
            $('#lab-services-list').append(clone);
        }
    });

    $(document).on('click', '.lab-service-remove', function() {
        $(this).closest('.lab-service-row').fadeOut(200, function() { $(this).remove(); });
    });

    $(document).on('click', '#lab-save-services', function() {
        var $btn = $(this);
        $btn.prop('disabled', true).text('Saving...');

        var services = [];
        $('#lab-services-list .lab-service-row').each(function() {
            var name     = $(this).find('input[name="svc_name[]"]').val();
            var price    = $(this).find('input[name="svc_price[]"]').val();
            var duration = $(this).find('input[name="svc_duration[]"]').val();
            if (name && price) {
                services.push({
                    name: name,
                    price: parseFloat(price) || 0,
                    duration: parseInt(duration) || 0
                });
            }
        });

        $.post(ajaxurl, {
            action: 'lab_save_services',
            nonce: nonce,
            business_id: labVars.business_id,
            services: JSON.stringify(services)
        }, function(res) {
            $btn.prop('disabled', false).text('Save Services');
            if (res.success) {
                showMsg('#lab-services-msg', res.data.message, 'success');
            } else {
                showMsg('#lab-services-msg', res.data.message, 'error');
            }
        });
    });

    /* ── 3. Availability: Quick-fill buttons ────────────────────── */
    $(document).on('click', '[data-qf]', function() {
        var qf = $(this).data('qf');
        var weekdays = ['monday','tuesday','wednesday','thursday','friday'];
        var allDays  = weekdays.concat(['saturday','sunday']);
        function setRow(day, open, close) {
            var $row = $('[data-day="' + day + '"]').closest('.lab-avail-row');
            var $chk = $row.find('.lab-avail-check');
            var isOpen = open !== '';
            $chk.prop('checked', isOpen);
            $row.find('.lab-avail-time').prop('disabled', !isOpen);
            $row.find('input[name="' + day + '_open"]').val(open);
            $row.find('input[name="' + day + '_close"]').val(close);
            var $st = $row.find('.lab-avail-status');
            $st.removeClass('lab-avail-status--open lab-avail-status--closed')
               .addClass(isOpen ? 'lab-avail-status--open' : 'lab-avail-status--closed')
               .text(isOpen ? 'Open' : 'Closed');
        }
        if (qf === 'weekdays') {
            weekdays.forEach(function(d) { setRow(d, '09:00', '17:00'); });
            ['saturday','sunday'].forEach(function(d) { setRow(d, '', ''); });
        } else if (qf === 'everyday') {
            allDays.forEach(function(d) { setRow(d, '09:00', '18:00'); });
        } else if (qf === 'clearall') {
            allDays.forEach(function(d) { setRow(d, '', ''); });
        }
    });

    /* ── 3b. Availability: Checkbox Toggle ──────────────────────── */
    $(document).on('change', '.lab-avail-check', function() {
        var day = $(this).data('day');
        var isChecked = $(this).is(':checked');
        var $row = $(this).closest('.lab-avail-row');
        $row.find('.lab-avail-time').prop('disabled', !isChecked);
        var $status = $row.find('.lab-avail-status');
        if (isChecked) {
            $status.removeClass('lab-avail-status--closed').addClass('lab-avail-status--open').text('Open');
        } else {
            $status.removeClass('lab-avail-status--open').addClass('lab-avail-status--closed').text('Closed');
        }
    });

    /* Save availability */
    $(document).on('submit', '#lab-availability-form', function(e) {
        e.preventDefault();
        var $btn = $(this).find('button[type="submit"]');
        $btn.prop('disabled', true).text('Saving...');

        var data = {
            action: 'lab_save_availability',
            nonce: nonce,
            business_id: labVars.business_id
        };

        var days = ['monday','tuesday','wednesday','thursday','friday','saturday','sunday'];
        days.forEach(function(day) {
            var $row = $('[data-day="' + day + '"]').closest('.lab-avail-row');
            data[day + '_open_check'] = $row.find('.lab-avail-check').is(':checked') ? '1' : '0';
            data[day + '_open']  = $row.find('input[name="' + day + '_open"]').val();
            data[day + '_close'] = $row.find('input[name="' + day + '_close"]').val();
        });

        $.post(ajaxurl, data, function(res) {
            $btn.prop('disabled', false).text('Save Availability');
            if (res.success) {
                showMsg('#lab-avail-msg', res.data.message, 'success');
            } else {
                showMsg('#lab-avail-msg', res.data.message, 'error');
            }
        });
    });

    /* ── 4. Booking Form: Date Picker Closed Days ─────────────── */
    $(document).on('change', '#lab-book-date', function() {
        var $form = $(this).closest('form');
        var businessId = $form.data('business-id');
        var date = $(this).val();
        var closedDays = $form.data('closed-days') || [];

        /* Check if selected day is closed */
        var d = new Date(date + 'T00:00:00');
        var dayOfWeek = d.getDay();
        if (closedDays.indexOf(dayOfWeek) !== -1) {
            showMsg('#lab-booking-msg', 'The business is closed on this day. Please select another date.', 'error');
            $('#lab-book-time').html('<option value="">Business is closed</option>');
            return;
        }

        /* 5. Fetch time slots via AJAX */
        var $timeSelect = $('#lab-book-time');
        $timeSelect.html('<option value="">Loading...</option>');

        $.post(ajaxurl, {
            action: 'lab_get_slots',
            nonce: nonce,
            business_id: businessId,
            date: date
        }, function(res) {
            if (res.success && res.data.slots.length > 0) {
                var html = '<option value="">Select a time</option>';
                res.data.slots.forEach(function(slot) {
                    /* Format time for display */
                    var parts = slot.split(':');
                    var h = parseInt(parts[0]);
                    var m = parts[1];
                    var ampm = h >= 12 ? 'PM' : 'AM';
                    var h12 = h % 12 || 12;
                    html += '<option value="' + slot + '">' + h12 + ':' + m + ' ' + ampm + '</option>';
                });
                $timeSelect.html(html);
            } else {
                $timeSelect.html('<option value="">No slots available</option>');
            }
        });
    });

    /* ── Service select → update price ────────────────────────── */
    $(document).on('change', '#lab-book-service', function() {
        var price = $(this).find(':selected').data('price') || '';
        $('#lab-book-price').val(price);
    });

    /* ── Login gate for booking flow ─────────────────────────── */
    $(document).on('click', '#lab-book-with-us-btn', function(e) {
        if (!labVars.user_id || parseInt(labVars.user_id) === 0) {
            window.location.href = labVars.login_url + '?redirect=' + encodeURIComponent(window.location.href);
            return;
        }
    });

    /* ── "Book This" buttons on single page ───────────────────── */
    $(document).on('click', '.lab-book-service-btn', function(e) {
        e.preventDefault();
        var name = $(this).data('service-name');
        var price = $(this).data('service-price');
        $('#lab-book-service').val(name);
        $('#lab-book-price').val(price);
        /* Scroll to booking form */
        $('html, body').animate({ scrollTop: $('#booking').offset().top - 80 }, 400);
    });

    /* ── 6. Booking Submission ─────────────────────────────────── */
    $(document).on('submit', '#lab-booking-form', function(e) {
        e.preventDefault();
        var $btn = $(this).find('button[type="submit"]');
        $btn.prop('disabled', true).text('Booking...');

        $.post(ajaxurl, {
            action: 'lab_create_booking',
            nonce: nonce,
            business_id: $(this).data('business-id'),
            service_name: $('#lab-book-service').val(),
            service_price: $('#lab-book-price').val(),
            booking_date: $('#lab-book-date').val(),
            booking_time: $('#lab-book-time').val(),
            notes: $('#lab-book-notes').val()
        }, function(res) {
            $btn.prop('disabled', false).text('Book Now');
            if (res.success) {
                showMsg('#lab-booking-msg', res.data.message, 'success');
                $('#lab-booking-form')[0].reset();
                $('#lab-book-time').html('<option value="">Select a date first</option>');
            } else {
                showMsg('#lab-booking-msg', res.data.message, 'error');
            }
        });
    });

    /* ── 7. Business Dashboard: Status Change ─────────────────── */
    $(document).on('click', '.lab-status-btn', function() {
        var bookingId = $(this).data('booking-id');
        var newStatus = $(this).data('status');
        var label = newStatus.charAt(0).toUpperCase() + newStatus.slice(1);

        if (!confirm('Are you sure you want to mark this booking as ' + label + '?')) return;

        var $row = $(this).closest('tr');

        $.post(ajaxurl, {
            action: 'lab_update_booking_status',
            nonce: nonce,
            booking_id: bookingId,
            new_status: newStatus
        }, function(res) {
            if (res.success) {
                /* Update badge */
                var $badge = $('#lab-badge-' + bookingId);
                $badge.attr('class', 'lab-badge lab-badge--' + newStatus).text(label);

                /* Update row data attribute */
                $row.data('status', newStatus).attr('data-status', newStatus);

                /* Update actions */
                var $actions = $('#lab-actions-' + bookingId);
                if (newStatus === 'confirmed') {
                    $actions.html(
                        '<button class="lab-btn lab-btn--sm lab-btn--success lab-status-btn" data-booking-id="' + bookingId + '" data-status="completed">Complete</button>' +
                        '<button class="lab-btn lab-btn--sm lab-btn--danger lab-status-btn" data-booking-id="' + bookingId + '" data-status="cancelled">Cancel</button>'
                    );
                } else {
                    $actions.html('<span class="lab-text-muted">—</span>');
                }

                showMsg('#lab-bookings-msg', res.data.message, 'success');
            } else {
                showMsg('#lab-bookings-msg', res.data.message, 'error');
            }
        });
    });

    /* ── Booking Status Filter ────────────────────────────────── */
    $(document).on('change', '#lab-booking-filter', function() {
        var status = $(this).val();
        if (status === 'all') {
            $('.lab-booking-row').show();
        } else {
            $('.lab-booking-row').hide();
            $('.lab-booking-row[data-status="' + status + '"]').show();
        }
    });

    /* ── Customer: Cancel Booking ─────────────────────────────── */
    $(document).on('click', '.lab-cancel-booking-btn', function() {
        if (!confirm('Are you sure you want to cancel this booking?')) return;

        var bookingId = $(this).data('booking-id');
        var $btn = $(this);
        $btn.prop('disabled', true);

        $.post(ajaxurl, {
            action: 'lab_cancel_booking',
            nonce: nonce,
            booking_id: bookingId
        }, function(res) {
            if (res.success) {
                var $row = $btn.closest('tr');
                $row.find('.lab-badge').attr('class', 'lab-badge lab-badge--cancelled').text('Cancelled');
                $btn.replaceWith('<span class="lab-text-muted">—</span>');
                showMsg('#lab-cust-bookings-msg', res.data.message, 'success');
            } else {
                $btn.prop('disabled', false);
                showMsg('#lab-cust-bookings-msg', res.data.message, 'error');
            }
        });
    });

    /* ── 8. Review Modal ──────────────────────────────────────── */
    $(document).on('click', '.lab-leave-review-btn', function() {
        var bookingId   = $(this).data('booking-id');
        var businessId  = $(this).data('business-id');
        var bizName     = $(this).data('business-name');
        var serviceName = $(this).data('service-name');

        $('#lab-review-booking-id').val(bookingId);
        $('#lab-review-business-id').val(businessId);
        $('#lab-review-modal-info').text('Review for: ' + bizName + ' — ' + serviceName);
        $('#lab-review-rating').val(0);
        $('#lab-review-text').val('');
        $('.lab-star-pick').removeClass('active');
        $('#lab-review-modal').fadeIn(200);
    });

    $(document).on('click', '#lab-review-modal-close, .lab-modal__overlay', function() {
        $('#lab-review-modal').fadeOut(200);
    });

    /* ── 9. Star Rating Widget ────────────────────────────────── */
    $(document).on('click', '.lab-star-pick', function() {
        var rating = $(this).data('rating');
        $('#lab-review-rating').val(rating);
        $('.lab-star-pick').each(function() {
            if ($(this).data('rating') <= rating) {
                $(this).addClass('active');
            } else {
                $(this).removeClass('active');
            }
        });
    });

    $(document).on('mouseenter', '.lab-star-pick', function() {
        var rating = $(this).data('rating');
        $('.lab-star-pick').each(function() {
            if ($(this).data('rating') <= rating) {
                $(this).css('color', '#ffc107');
            } else {
                $(this).css('color', '');
            }
        });
    });

    $(document).on('mouseleave', '.lab-star-picker', function() {
        var current = parseInt($('#lab-review-rating').val());
        $('.lab-star-pick').each(function() {
            $(this).css('color', '');
            if ($(this).data('rating') <= current) {
                $(this).addClass('active');
            }
        });
    });

    /* Submit review */
    $(document).on('submit', '#lab-review-form', function(e) {
        e.preventDefault();
        var $btn = $(this).find('button[type="submit"]');
        $btn.prop('disabled', true).text('Submitting...');

        $.post(ajaxurl, {
            action: 'lab_submit_review',
            nonce: nonce,
            booking_id: $('#lab-review-booking-id').val(),
            business_id: $('#lab-review-business-id').val(),
            rating: $('#lab-review-rating').val(),
            review_text: $('#lab-review-text').val()
        }, function(res) {
            $btn.prop('disabled', false).text('Submit Review');
            if (res.success) {
                showMsg('#lab-review-msg', res.data.message, 'success');
                setTimeout(function() {
                    $('#lab-review-modal').fadeOut(200);
                    /* Update the button to "Reviewed" */
                    var bookingId = $('#lab-review-booking-id').val();
                    $('[data-booking-id="' + bookingId + '"] .lab-leave-review-btn')
                        .replaceWith('<span class="lab-text-muted">Reviewed</span>');
                }, 1500);
            } else {
                showMsg('#lab-review-msg', res.data.message, 'error');
            }
        });
    });

    /* ── 10. Business Archive: AJAX Search ────────────────────── */
    $(document).on('submit', '#lab-search-form', function(e) {
        e.preventDefault();
        var $grid = $('#lab-business-results');
        $grid.html('<div class="lab-empty-state lab-empty-state--wide"><p>Searching...</p></div>');

        $.post(ajaxurl, {
            action: 'lab_search_businesses',
            nonce: nonce,
            keyword: $('#lab-search-keyword').val(),
            city: $('#lab-search-city').val(),
            category: $('#lab-search-category').val()
        }, function(res) {
            if (res.success) {
                $grid.html(res.data.html);
            } else {
                $grid.html('<div class="lab-empty-state lab-empty-state--wide"><p>No businesses found.</p></div>');
            }
        });
    });

    /* ── 11. Gallery: WP Media Library ────────────────────────── */
    var labMediaFrame;
    $(document).on('click', '#lab-gallery-upload', function(e) {
        e.preventDefault();

        if (labMediaFrame) {
            labMediaFrame.open();
            return;
        }

        labMediaFrame = wp.media({
            title: 'Select Gallery Images',
            button: { text: 'Add to Gallery' },
            multiple: true
        });

        labMediaFrame.on('select', function() {
            var attachments = labMediaFrame.state().get('selection').toJSON();
            var currentIds = $('#lab-gallery-ids').val();
            var ids = currentIds ? currentIds.split(',').filter(Boolean) : [];

            attachments.forEach(function(att) {
                ids.push(att.id);
                var url = att.sizes && att.sizes.medium ? att.sizes.medium.url : att.url;
                var html = '<div class="lab-gallery-item" data-id="' + att.id + '">' +
                           '<img src="' + url + '" alt="" />' +
                           '<button type="button" class="lab-gallery-remove" data-id="' + att.id + '">&times;</button>' +
                           '</div>';
                $('#lab-gallery-preview').append(html);
            });

            $('#lab-gallery-ids').val(ids.join(','));
        });

        labMediaFrame.open();
    });

    /* ── 12. Gallery: Remove Image ────────────────────────────── */
    $(document).on('click', '.lab-gallery-remove', function(e) {
        e.preventDefault();
        var removeId = $(this).data('id').toString();
        $(this).closest('.lab-gallery-item').fadeOut(200, function() { $(this).remove(); });

        var ids = $('#lab-gallery-ids').val().split(',').filter(function(id) {
            return id !== removeId;
        });
        $('#lab-gallery-ids').val(ids.join(','));
    });

    /* ── Auth Forms ────────────────────────────────────────────── */

    /* Customer Registration */
    $(document).on('submit', '#lab-register-form', function(e) {
        e.preventDefault();
        var $btn = $(this).find('button[type="submit"]');
        $btn.prop('disabled', true).text('Creating account...');

        $.post(ajaxurl, {
            action: 'lab_register_customer',
            nonce: nonce,
            first_name: $('#lab-reg-fname').val(),
            last_name: $('#lab-reg-lname').val(),
            email: $('#lab-reg-email').val(),
            password: $('#lab-reg-pass').val(),
            password_confirm: $('#lab-reg-pass2').val()
        }, function(res) {
            $btn.prop('disabled', false).text('Create Account');
            if (res.success) {
                showMsg('#lab-register-msg', res.data.message, 'success');
                if (res.data.redirect) {
                    setTimeout(function() { window.location.href = res.data.redirect; }, 1000);
                }
            } else {
                showMsg('#lab-register-msg', res.data.message, 'error');
            }
        });
    });

    /* Login */
    $(document).on('submit', '#lab-login-form', function(e) {
        e.preventDefault();
        var $btn = $(this).find('button[type="submit"]');
        $btn.prop('disabled', true).text('Logging in...');

        $.post(ajaxurl, {
            action: 'lab_login',
            nonce: nonce,
            email: $('#lab-login-email').val(),
            password: $('#lab-login-pass').val()
        }, function(res) {
            $btn.prop('disabled', false).text('Login');
            if (res.success) {
                showMsg('#lab-login-msg', res.data.message, 'success');
                if (res.data.redirect) {
                    setTimeout(function() { window.location.href = res.data.redirect; }, 1000);
                }
            } else {
                showMsg('#lab-login-msg', res.data.message, 'error');
            }
        });
    });

    /* Business Registration */
    $(document).on('submit', '#lab-biz-register-form', function(e) {
        e.preventDefault();
        var $btn = $(this).find('button[type="submit"]');
        $btn.prop('disabled', true).text('Submitting...');

        $.post(ajaxurl, {
            action: 'lab_register_business',
            nonce: nonce,
            business_name: $('#lab-biz-name').val(),
            owner_name: $('#lab-biz-owner').val(),
            email: $('#lab-biz-email').val(),
            phone: $('#lab-biz-phone').val(),
            password: $('#lab-biz-pass').val(),
            password_confirm: $('#lab-biz-pass2').val(),
            city: $('#lab-biz-city').val(),
            postcode: $('#lab-biz-postcode').val(),
            category: $('#lab-biz-category').val(),
            description: $('#lab-biz-desc').val()
        }, function(res) {
            $btn.prop('disabled', false).text('Submit Application');
            if (res.success) {
                showMsg('#lab-biz-register-msg', res.data.message, 'success');
                $('#lab-biz-register-form')[0].reset();
            } else {
                showMsg('#lab-biz-register-msg', res.data.message, 'error');
            }
        });
    });

    /* Partner Inquiry (contact form on /partner/) */
    $(document).on('submit', '#lab-partner-form', function(e) {
        e.preventDefault();
        var $form = $(this);
        var $btn = $form.find('button[type="submit"]');
        $btn.prop('disabled', true).text('Sending...');

        $.post(ajaxurl, {
            action: 'lab_partner_inquiry',
            nonce: nonce,
            name: $form.find('[name="name"]').val(),
            email: $form.find('[name="email"]').val(),
            business_name: $form.find('[name="business_name"]').val(),
            category: $form.find('[name="category"]').val(),
            message: $form.find('[name="message"]').val()
        }, function(res) {
            $btn.prop('disabled', false).text('Send Message');
            if (res.success) {
                showMsg('#lab-partner-msg', res.data.message, 'success');
                $form[0].reset();
            } else {
                showMsg('#lab-partner-msg', res.data.message, 'error');
            }
        });
    });

    /* Customer Inquiry (contact form on /contact-us/) */
    $(document).on('submit', '#lab-customer-contact-form', function(e) {
        e.preventDefault();
        var $form = $(this);
        var $btn = $form.find('button[type="submit"]');
        $btn.prop('disabled', true).text('Sending...');

        $.post(ajaxurl, {
            action: 'lab_customer_inquiry',
            nonce: nonce,
            name: $form.find('[name="name"]').val(),
            email: $form.find('[name="email"]').val(),
            subject: $form.find('[name="subject"]').val(),
            message: $form.find('[name="message"]').val()
        }, function(res) {
            $btn.prop('disabled', false).text('Submit');
            if (res.success) {
                showMsg('#lab-customer-contact-msg', res.data.message, 'success');
                $form[0].reset();
            } else {
                showMsg('#lab-customer-contact-msg', res.data.message, 'error');
            }
        });
    });

    /* ── Business Profile Edit (My Business tab) ──────────────── */
    $(document).on('submit', '#lab-biz-edit-form', function(e) {
        e.preventDefault();
        var $btn = $(this).find('button[type="submit"]');
        $btn.prop('disabled', true).text('Saving...');

        $.post(ajaxurl, {
            action: 'lab_update_business',
            nonce: nonce,
            business_id: labVars.business_id,
            business_name: $('#lab-biz-edit-name').val(),
            description: $('#lab-biz-edit-desc').val(),
            phone: $('#lab-biz-edit-phone').val(),
            email: $('#lab-biz-edit-email').val(),
            address: $('#lab-biz-edit-address').val(),
            city: $('#lab-biz-edit-city').val(),
            postcode: $('#lab-biz-edit-postcode').val(),
            category: $('#lab-biz-edit-category').val(),
            gallery: $('#lab-gallery-ids').val()
        }, function(res) {
            $btn.prop('disabled', false).text('Save Changes');
            if (res.success) {
                showMsg('#lab-biz-edit-msg', res.data.message, 'success');
            } else {
                showMsg('#lab-biz-edit-msg', res.data.message, 'error');
            }
        });
    });

    /* ── Deals: Create ────────────────────────────────────────── */
    $(document).on('submit', '#lab-add-deal-form', function(e) {
        e.preventDefault();
        var $btn = $(this).find('button[type="submit"]');
        $btn.prop('disabled', true).text('Creating...');

        $.post(ajaxurl, {
            action: 'lab_create_deal',
            nonce: nonce,
            business_id: labVars.business_id,
            title: $('#lab-deal-title').val(),
            discount: $('#lab-deal-discount').val(),
            valid_until: $('#lab-deal-valid').val(),
            description: $('#lab-deal-desc').val()
        }, function(res) {
            $btn.prop('disabled', false).text('Create Deal');
            if (res.success) {
                showMsg('#lab-deals-msg', res.data.message, 'success');
                $('#lab-add-deal-form')[0].reset();
                /* Reload page to show new deal */
                setTimeout(function() { location.reload(); }, 1000);
            } else {
                showMsg('#lab-deals-msg', res.data.message, 'error');
            }
        });
    });

    /* Deals: Delete */
    $(document).on('click', '.lab-delete-deal', function() {
        if (!confirm('Are you sure you want to delete this deal?')) return;

        var dealId = $(this).data('deal-id');
        var $row = $(this).closest('tr');

        $.post(ajaxurl, {
            action: 'lab_delete_deal',
            nonce: nonce,
            deal_id: dealId
        }, function(res) {
            if (res.success) {
                $row.fadeOut(200, function() { $(this).remove(); });
                showMsg('#lab-deals-msg', res.data.message, 'success');
            } else {
                showMsg('#lab-deals-msg', res.data.message, 'error');
            }
        });
    });

    /* ── Customer Profile Save ────────────────────────────────── */
    $(document).on('submit', '#lab-profile-form', function(e) {
        e.preventDefault();
        var $btn = $(this).find('button[type="submit"]');
        $btn.prop('disabled', true).text('Saving...');

        $.post(ajaxurl, {
            action: 'lab_update_profile',
            nonce: nonce,
            first_name: $('#lab-prof-fname').val(),
            last_name: $('#lab-prof-lname').val(),
            email: $('#lab-prof-email').val(),
            password: $('#lab-prof-pass').val(),
            password_confirm: $('#lab-prof-pass2').val()
        }, function(res) {
            $btn.prop('disabled', false).text('Save Profile');
            if (res.success) {
                showMsg('#lab-profile-msg', res.data.message, 'success');
            } else {
                showMsg('#lab-profile-msg', res.data.message, 'error');
            }
        });
    });

    /* ── Booking Flow (Inline, multi-step) ─────────────────────── */
    /* ── Booking Steps Tab Editor (Dashboard) ────────────────────── */
    // Add Step
    $(document).on('click', '#lab-add-booking-step', function() {
        var template = document.getElementById('lab-booking-step-template');
        if (template) {
            var clone = template.content.cloneNode(true);
            var $card = $(clone).appendTo('#lab-booking-steps-list');
            $card.find('.lab-step-type-select').trigger('change');
        }
    });

    // Remove Step
    $(document).on('click', '.lab-step-remove', function() {
        $(this).closest('.lab-booking-step-card').fadeOut(200, function() { $(this).remove(); });
    });

    // Toggle step options based on step type
    $(document).on('change', '.lab-step-type-select', function() {
        var type = $(this).val();
        var showOpts = ['vehicles', 'services', 'duration'].indexOf(type) !== -1;
        var $card = $(this).closest('.lab-booking-step-card');
        $card.find('.lab-booking-step-card__options').toggle(showOpts);
    });

    // Add Option Row
    $(document).on('click', '.lab-add-step-option', function() {
        var template = document.getElementById('lab-booking-step-option-template');
        if (template) {
            var clone = template.content.cloneNode(true);
            $(this).closest('.lab-booking-step-card').find('.lab-step-options-list').append(clone);
        }
    });

    // Remove Option Row
    $(document).on('click', '.lab-opt-remove', function() {
        $(this).closest('.lab-step-option-row').remove();
    });

    // Upload Option Image
    $(document).on('click', '.lab-opt-image-upload', function(e) {
        e.preventDefault();
        var $btn = $(this);
        var $preview = $btn.siblings('.opt-image-preview');
        var $input = $btn.siblings('.opt-image-val');

        var frame = wp.media({
            title: 'Select Option Image',
            button: { text: 'Select Image' },
            multiple: false
        });

        frame.on('select', function() {
            var attachment = frame.state().get('selection').first().toJSON();
            var url = attachment.sizes && attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url;
            $input.val(attachment.url);
            $preview.html('<img src="' + url + '" style="max-height: 40px; border-radius: 4px;" />');
        });

        frame.open();
    });

    // Save Booking Steps
    $(document).on('click', '#lab-save-booking-steps', function() {
        var $btn = $(this);
        $btn.prop('disabled', true).text('Saving...');

        var steps = [];
        $('#lab-booking-steps-list .lab-booking-step-card').each(function() {
            var title = $(this).find('input[name="step_title[]"]').val();
            var type  = $(this).find('select[name="step_type[]"]').val();
            var options = [];

            if (['vehicles', 'services', 'duration'].indexOf(type) !== -1) {
                $(this).find('.lab-step-option-row').each(function() {
                    var optName = $(this).find('.opt-name').val();
                    var optPrice = parseFloat($(this).find('.opt-price').val()) || 0;
                    var optImg = $(this).find('.opt-image-val').val() || '';
                    if (optName) {
                        options.push({
                            name: optName,
                            price: optPrice,
                            factor: optPrice,
                            image: optImg
                        });
                    }
                });
            }

            if (title && type) {
                steps.push({
                    title: title,
                    type: type,
                    options: options
                });
            }
        });

        $.post(ajaxurl, {
            action: 'lab_save_booking_steps',
            nonce: nonce,
            business_id: labVars.business_id,
            steps: JSON.stringify(steps)
        }, function(res) {
            $btn.prop('disabled', false).text('Save Booking Steps');
            if (res.success) {
                showMsg('#lab-booking-steps-msg', res.data.message, 'success');
            } else {
                showMsg('#lab-booking-steps-msg', res.data.message, 'error');
            }
        });
    });

    /* ── Dynamic Booking Flow (Frontend) ────────────────────────── */
    var defaultSteps = [
        { type: 'services', title: 'Choose a service' },
        { type: 'datetime', title: 'Select date & time' },
        { type: 'details',  title: 'Your details' },
        { type: 'payment',  title: 'Review & pay' }
    ];

    window.labBookingFlowSteps = window.labBookingSteps || defaultSteps;

    function labBfGoToStep(n) {
        window.labBfStep = n;
        var totalSteps = window.labBookingFlowSteps.length;
        
        var currentStepObj = window.labBookingFlowSteps[n - 1];
        if (currentStepObj) {
            if (currentStepObj.type === 'datetime') labBfRenderCalendar();
            if (currentStepObj.type === 'payment') labBfPreparePayment();
        }

        for (var i = 1; i <= totalSteps; i++) {
            $('#lab-bf-step-' + i).toggle(i === n);
        }

        $('.lab-bf-step-dot').each(function () {
            var s = parseInt($(this).data('step'), 10);
            $(this).toggleClass('lab-bf-step-dot--active', s === n)
                   .toggleClass('lab-bf-step-dot--done', s < n);
        });

        var $anchor = $('#lab-booking-inline');
        if ($anchor.length) {
            $('html, body').animate({ scrollTop: $anchor.offset().top - 90 }, 300);
        }
    }

    function labBfRenderCalendar() {
        var dateHtml = '';
        var today = new Date();
        var monthNames = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
        $('#lab-calendar-month-title').text(monthNames[today.getMonth()] + ' ' + today.getFullYear());
        var currentDayIndex = today.getDay() === 0 ? 6 : today.getDay() - 1;
        for (var p = 0; p < currentDayIndex; p++) { dateHtml += '<div></div>'; }
        for (var i = 0; i < 14; i++) {
            var d = new Date(today);
            d.setDate(today.getDate() + i);
            var yyyy = d.getFullYear();
            var mm = String(d.getMonth() + 1).padStart(2, '0');
            var dd = String(d.getDate()).padStart(2, '0');
            var dateStr = yyyy + '-' + mm + '-' + dd;
            dateHtml += '<button type="button" class="lab-date-btn" data-date="' + dateStr + '">' + d.getDate() + '</button>';
        }
        $('#lab-bf-mock-dates').html(dateHtml);
    }

    function labBfPreparePayment() {
        var cs = labVars.currency_symbol || '£';
        var vehiclePrice = parseFloat(window.labBfData.vehicle_price || 0);
        var servicePrice = parseFloat(window.labBfData.service_price || 0);
        var durationFactor = parseFloat(window.labBfData.duration_factor || 1);
        
        var basePrice = vehiclePrice + servicePrice;
        var totalPrice = basePrice * durationFactor;
        
        window.labBfData.calculated_total = totalPrice;

        $('#lab-pay-service-name').text(window.labBfData.service_name || '—');
        
        if (window.labBfData.vehicle_name) {
            $('#lab-pay-vehicle-row').show();
            $('#lab-pay-vehicle-name').text(window.labBfData.vehicle_name);
        } else {
            $('#lab-pay-vehicle-row').hide();
        }

        if (window.labBfData.duration_name) {
            $('#lab-pay-duration-row').show();
            $('#lab-pay-duration-name').text(window.labBfData.duration_name);
        } else {
            $('#lab-pay-duration-row').hide();
        }

        var dateLabel = window.labBfData.dateLabel || window.labBfData.date || '—';
        $('#lab-pay-datetime').text(dateLabel + ' · ' + (window.labBfData.time || '—'));
        $('#lab-pay-total').text(cs + totalPrice.toFixed(2));
        
        window.labStripe = null;
        window.labStripeElements = null;
        labInitPayment(totalPrice);
    }

    if ($('#lab-booking-inline').length > 0 && window.labCurrentBusinessId) {
        var loggedIn = labVars.user_id && parseInt(labVars.user_id, 10) !== 0;

        if (!loggedIn) {
            $('#lab-booking-inline').html(
                '<div class="lab-bf-container">' +
                  '<div class="lab-bf-login-gate">' +
                    '<h2>Ready to book?</h2>' +
                    '<p>Log in or create an account to book an appointment with this business.</p>' +
                    '<a class="lab-btn lab-btn--primary" href="' + labVars.login_url + '?redirect=' + encodeURIComponent(window.location.href) + '">Log in to continue</a>' +
                  '</div>' +
                '</div>'
            );
        } else {
            var stepper = '<div class="lab-bf-steps">';
            window.labBookingFlowSteps.forEach(function (step, i) {
                stepper += '<div class="lab-bf-step-dot" data-step="' + (i + 1) + '">' +
                    '<span class="lab-bf-step-num">' + (i + 1) + '</span>' +
                    '<span class="lab-bf-step-label">' + step.title + '</span>' +
                '</div>';
            });
            stepper += '</div>';

            var cs = labVars.currency_symbol || '£';

            var stepsHtml = '';
            window.labBookingFlowSteps.forEach(function (step, i) {
                var stepNum = i + 1;
                var backBtn = stepNum > 1 ? '<button type="button" class="lab-btn lab-bf-back" data-back="' + (stepNum - 1) + '">&larr; Back</button>' : '';
                var content = '';

                if (step.type === 'details') {
                    content =
                        '<form id="lab-bf-form-details" class="lab-form" data-step="' + stepNum + '">' +
                            '<div class="lab-form-row lab-form-row--2col">' +
                                '<div class="lab-field"><label>Full Name</label><input type="text" id="lab-bf-name" required /></div>' +
                                '<div class="lab-field"><label>Email Address</label><input type="email" id="lab-bf-email" required /></div>' +
                            '</div>' +
                            '<div class="lab-field"><label>Mobile Number</label><input type="tel" id="lab-bf-phone" placeholder="+44" required /></div>' +
                            '<div class="lab-field"><label>Notes (optional)</label><textarea id="lab-bf-notes" rows="3" placeholder="Anything the business should know?"></textarea></div>' +
                            '<div class="lab-bf-actions lab-bf-actions--split">' +
                                backBtn +
                                '<button type="submit" class="lab-btn lab-btn--primary">Continue</button>' +
                            '</div>' +
                        '</form>';
                } else if (step.type === 'vehicles') {
                    var vehiclesHtml = '';
                    if (step.options && step.options.length > 0) {
                        step.options.forEach(function(opt) {
                            var imgHtml = opt.image ? '<div class="lab-vehicle-card__img"><img src="' + opt.image + '" alt="' + opt.name + '"/></div>' : '';
                            vehiclesHtml += '<button type="button" class="lab-bf-vehicle-card" data-name="' + opt.name + '" data-price="' + opt.price + '">' +
                                imgHtml +
                                '<div class="lab-vehicle-card__info">' +
                                    '<span class="lab-vehicle-card__name">' + opt.name + '</span>' +
                                    '<span class="lab-vehicle-card__price">' + cs + parseFloat(opt.price).toFixed(2) + '/day</span>' +
                                '</div>' +
                            '</button>';
                        });
                    }
                    content =
                        '<div class="lab-bf-vehicles-grid">' + vehiclesHtml + '</div>' +
                        '<div class="lab-bf-actions lab-bf-actions--split">' +
                            backBtn +
                            '<button type="button" class="lab-btn lab-btn--primary lab-bf-next" data-next="' + (stepNum + 1) + '" id="lab-bf-next-' + stepNum + '" disabled>Continue</button>' +
                        '</div>';
                } else if (step.type === 'services') {
                    var servicesHtml = '';
                    var servicesToRender = step.options && step.options.length > 0 ? step.options : window.labCurrentServices;
                    if (!servicesToRender || servicesToRender.length === 0) {
                        servicesToRender = [
                            { name: 'Standard Consultation', price: 50 },
                            { name: 'Premium Package', price: 150 },
                            { name: 'Express Service', price: 75 }
                        ];
                    }
                    servicesToRender.forEach(function (s) {
                        servicesHtml += '<button type="button" class="lab-bf-svc-card" data-name="' + s.name + '" data-price="' + s.price + '">' +
                            '<span class="lab-bf-svc-name">' + s.name + '</span>' +
                            '<span class="lab-bf-svc-price">' + (parseFloat(s.price) > 0 ? cs + parseFloat(s.price).toFixed(2) : 'Free') + '</span>' +
                        '</button>';
                    });
                    content =
                        '<div class="lab-bf-services-grid">' + servicesHtml + '</div>' +
                        '<div class="lab-bf-actions lab-bf-actions--split">' +
                            backBtn +
                            '<button type="button" class="lab-btn lab-btn--primary lab-bf-next" data-next="' + (stepNum + 1) + '" id="lab-bf-next-' + stepNum + '" disabled>Continue</button>' +
                        '</div>';
                } else if (step.type === 'duration') {
                    var durationsHtml = '';
                    if (step.options && step.options.length > 0) {
                        step.options.forEach(function(opt) {
                            durationsHtml += '<button type="button" class="lab-bf-duration-btn" data-name="' + opt.name + '" data-factor="' + opt.price + '">' +
                                opt.name +
                            '</button>';
                        });
                    }
                    content =
                        '<div class="lab-bf-durations-list">' + durationsHtml + '</div>' +
                        '<div class="lab-bf-actions lab-bf-actions--split">' +
                            backBtn +
                            '<button type="button" class="lab-btn lab-btn--primary lab-bf-next" data-next="' + (stepNum + 1) + '" id="lab-bf-next-' + stepNum + '" disabled>Continue</button>' +
                        '</div>';
                } else if (step.type === 'datetime') {
                    content =
                        '<div class="lab-calendar-layout">' +
                            '<div class="lab-calendar-dates">' +
                                '<div class="lab-cal-head"><h4 id="lab-calendar-month-title"></h4></div>' +
                                '<div class="lab-cal-dow"><div>Mon</div><div>Tue</div><div>Wed</div><div>Thu</div><div>Fri</div><div>Sat</div><div>Sun</div></div>' +
                                '<div class="lab-cal-grid" id="lab-bf-mock-dates"></div>' +
                            '</div>' +
                            '<div class="lab-calendar-times" id="lab-bf-time-slots-container">' +
                                '<div class="lab-bf-slots-empty">Please select a date first.</div>' +
                            '</div>' +
                        '</div>' +
                        '<div class="lab-bf-actions lab-bf-actions--split">' +
                            backBtn +
                            '<button type="button" class="lab-btn lab-btn--primary lab-bf-next" data-next="' + (stepNum + 1) + '" id="lab-bf-next-' + stepNum + '" disabled>Continue</button>' +
                        '</div>';
                } else if (step.type === 'payment') {
                    content =
                        '<div class="lab-bf-summary">' +
                            '<div class="lab-bf-summary-row"><span>Service</span><strong id="lab-pay-service-name">—</strong></div>' +
                            '<div id="lab-pay-vehicle-row" class="lab-bf-summary-row" style="display:none;"><span>Vehicle</span><strong id="lab-pay-vehicle-name">—</strong></div>' +
                            '<div id="lab-pay-duration-row" class="lab-bf-summary-row" style="display:none;"><span>Duration</span><strong id="lab-pay-duration-name">—</strong></div>' +
                            '<div class="lab-bf-summary-row"><span>When</span><strong id="lab-pay-datetime">—</strong></div>' +
                            '<div class="lab-bf-summary-row lab-bf-summary-row--total"><span>Total</span><strong id="lab-pay-total">' + cs + '0.00</strong></div>' +
                        '</div>' +
                        '<div id="lab-stripe-payment-element"></div>' +
                        '<div id="lab-paypal-button-container"></div>' +
                        '<div id="lab-bf-final-msg" class="lab-msg" style="display:none;"></div>' +
                        '<div class="lab-bf-actions lab-bf-actions--split">' +
                            backBtn +
                            '<button type="button" class="lab-btn lab-btn--primary" id="lab-bf-submit">Pay &amp; Confirm</button>' +
                        '</div>';
                }

                stepsHtml +=
                    '<div id="lab-bf-step-' + stepNum + '" class="lab-bf-step" style="display:none;">' +
                        '<div class="lab-bf-card">' +
                            content +
                        '</div>' +
                    '</div>';
            });

            var html =
                '<div class="lab-bf-container">' +
                    '<h2 class="lab-bf-title">Book your appointment</h2>' +
                    stepper +
                    stepsHtml +
                '</div>';

            $('#lab-booking-inline').html(html);

            /* Prefill details from account */
            $('#lab-bf-name').val(labVars.user_name || '');
            $('#lab-bf-email').val(labVars.user_email || '');

            window.labBfData = {};
            labBfGoToStep(1);
        }
    }

    /* Select vehicle */
    $(document).on('click', '.lab-bf-vehicle-card', function () {
        $('.lab-bf-vehicle-card').removeClass('selected');
        $(this).addClass('selected');
        window.labBfData = window.labBfData || {};
        window.labBfData.vehicle_name = $(this).data('name');
        window.labBfData.vehicle_price = parseFloat($(this).data('price')) || 0;

        var stepId = $(this).closest('.lab-bf-step').attr('id');
        var stepNum = stepId.replace('lab-bf-step-', '');
        $('#lab-bf-next-' + stepNum).prop('disabled', false);
    });

    /* Select duration factor */
    $(document).on('click', '.lab-bf-duration-btn', function () {
        $('.lab-bf-duration-btn').removeClass('selected');
        $(this).addClass('selected');
        window.labBfData = window.labBfData || {};
        window.labBfData.duration_name = $(this).text().trim();
        window.labBfData.duration_factor = parseFloat($(this).data('factor')) || 1;

        var stepId = $(this).closest('.lab-bf-step').attr('id');
        var stepNum = stepId.replace('lab-bf-step-', '');
        $('#lab-bf-next-' + stepNum).prop('disabled', false);
    });

    /* Select service */
    $(document).on('click', '.lab-bf-svc-card', function () {
        $('.lab-bf-svc-card').removeClass('selected');
        $(this).addClass('selected');
        window.labBfData = window.labBfData || {};
        window.labBfData.service_name = $(this).data('name');
        window.labBfData.service_price = parseFloat($(this).data('price')) || 0;

        var stepId = $(this).closest('.lab-bf-step').attr('id');
        var stepNum = stepId.replace('lab-bf-step-', '');
        $('#lab-bf-next-' + stepNum).prop('disabled', false);
    });

    /* Submit details form step */
    $(document).on('submit', '#lab-bf-form-details', function (e) {
        e.preventDefault();
        window.labBfData = window.labBfData || {};
        window.labBfData.name = $('#lab-bf-name').val();
        window.labBfData.email = $('#lab-bf-email').val();
        window.labBfData.phone = $('#lab-bf-phone').val();
        window.labBfData.notes = $('#lab-bf-notes').val();

        var stepNum = parseInt($(this).data('step'), 10);
        labBfGoToStep(stepNum + 1);
    });

    /* Generic Next / Back navigation */
    $(document).on('click', '.lab-bf-next', function () {
        labBfGoToStep(parseInt($(this).data('next'), 10));
    });
    $(document).on('click', '.lab-bf-back', function () {
        labBfGoToStep(parseInt($(this).data('back'), 10));
    });

    /* Select date (step 2) */
    $(document).on('click', '.lab-date-btn', function () {
        $('.lab-date-btn').removeClass('lab-date-btn--selected');
        $(this).addClass('lab-date-btn--selected');
        window.labBfData = window.labBfData || {};
        var dateStr = $(this).data('date');
        window.labBfData.date = dateStr;

        var dObj = new Date(dateStr);
        var monthNamesShort = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
        window.labBfData.dateLabel = $(this).text() + ' ' + monthNamesShort[dObj.getMonth()];

        var dayIndex = dObj.getDay();
        var daysMap = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
        var dayStr = daysMap[dayIndex];
        var $container = $('#lab-bf-time-slots-container');

        function slotGroup(title, btns) {
            return '<div class="lab-bf-slot-group"><h5>' + title + '</h5><div class="lab-bf-slot-list">' + btns + '</div></div>';
        }

        if (window.labBusinessHours && window.labBusinessHours[dayStr]) {
            var hours = window.labBusinessHours[dayStr];
            if (hours.open !== '' && hours.close !== '') {
                var startParts = hours.open.split(':');
                var endParts = hours.close.split(':');
                var startMins = parseInt(startParts[0], 10) * 60 + parseInt(startParts[1], 10);
                var endMins = parseInt(endParts[0], 10) * 60 + parseInt(endParts[1], 10);
                var morningHtml = '', afternoonHtml = '', eveningHtml = '';
                for (var m = startMins; m < endMins; m += 30) {
                    var h = Math.floor(m / 60), mn = m % 60;
                    var timeStr = (h < 10 ? '0' + h : h) + ':' + (mn === 0 ? '00' : '30');
                    var btnHtml = '<button type="button" class="lab-time-btn">' + timeStr + '</button>';
                    if (h < 12) morningHtml += btnHtml;
                    else if (h < 17) afternoonHtml += btnHtml;
                    else eveningHtml += btnHtml;
                }
                var fullHtml = '';
                if (morningHtml) fullHtml += slotGroup('Morning', morningHtml);
                if (afternoonHtml) fullHtml += slotGroup('Afternoon', afternoonHtml);
                if (eveningHtml) fullHtml += slotGroup('Evening', eveningHtml);
                $container.html(fullHtml || '<div class="lab-bf-slots-empty">No slots available.</div>');
            } else {
                $container.html('<div class="lab-bf-slots-empty lab-bf-slots-empty--closed">Closed on this day.</div>');
            }
        } else {
            var morningHtml = '<button type="button" class="lab-time-btn">09:00</button><button type="button" class="lab-time-btn">10:00</button><button type="button" class="lab-time-btn">11:00</button>';
            var afternoonHtml = '<button type="button" class="lab-time-btn">13:00</button><button type="button" class="lab-time-btn">14:00</button><button type="button" class="lab-time-btn">15:00</button>';
            var fullHtml = slotGroup('Morning', morningHtml) + slotGroup('Afternoon', afternoonHtml);
            $container.html(fullHtml);
        }
        window.labBfData.time = null;
        var stepId = $(this).closest('.lab-bf-step').attr('id');
        var stepNum = stepId.replace('lab-bf-step-', '');
        $('#lab-bf-next-' + stepNum).prop('disabled', true);
    });

    /* Select time */
    $(document).on('click', '.lab-time-btn:not(.lab-time-btn--disabled)', function () {
        $('.lab-time-btn').removeClass('lab-time-btn--selected');
        $(this).addClass('lab-time-btn--selected');
        window.labBfData = window.labBfData || {};
        window.labBfData.time = $(this).text();

        var stepId = $(this).closest('.lab-bf-step').attr('id');
        var stepNum = stepId.replace('lab-bf-step-', '');
        $('#lab-bf-next-' + stepNum).prop('disabled', false);
    });

    /* Homepage Carousel — 5-card coverflow (restored previous behaviour, #4) */
    var $slides = $('.lab-carousel__slide');
    if ($slides.length > 0) {
        var total = $slides.length;
        var activeIndex = total >= 5 ? 2 : 0;
        var slideInterval;

        function updateCarousel() {
            $slides.removeClass('lab-carousel__slide--active lab-carousel__slide--prev lab-carousel__slide--next lab-carousel__slide--prev2 lab-carousel__slide--next2');

            $slides.each(function(index) {
                var diff = index - activeIndex;
                if (diff < 0) diff += total;

                if (diff === 0) $(this).addClass('lab-carousel__slide--active');
                else if (diff === 1) $(this).addClass('lab-carousel__slide--next');
                else if (diff === 2) $(this).addClass('lab-carousel__slide--next2');
                else if (diff === total - 2) $(this).addClass('lab-carousel__slide--prev2');
                else if (diff === total - 1) $(this).addClass('lab-carousel__slide--prev');
            });
        }

        function startAuto() {
            clearInterval(slideInterval);
            slideInterval = setInterval(function() {
                activeIndex = (activeIndex + 1) % total;
                updateCarousel();
            }, 5000);
        }

        updateCarousel();
        startAuto();

        // Arrow controls
        $(document).on('click', '.lab-carousel__nav-btn--prev', function(e) {
            e.preventDefault();
            activeIndex = (activeIndex - 1 + total) % total;
            updateCarousel();
            startAuto();
        });
        $(document).on('click', '.lab-carousel__nav-btn--next', function(e) {
            e.preventDefault();
            activeIndex = (activeIndex + 1) % total;
            updateCarousel();
            startAuto();
        });

        // Click a side card to focus it
        $slides.on('click', function() {
            var idx = $(this).index();
            if (idx !== activeIndex) {
                activeIndex = idx;
                updateCarousel();
                startAuto();
            }
        });
    }

    /* Redesigned Discover Postcode/Area Dropdown Logic */
    // Toggle dropdown trigger
    $(document).on('click', '#lab-area-dropdown-trigger', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $('#lab-area-dropdown-box').toggleClass('open');
    });

    // Close on click outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.lab-area-dropdown-wrapper').length) {
            $('#lab-area-dropdown-box').removeClass('open');
        }
    });

    // Option search live filter
    $(document).on('input', '#lab-area-search-input', function() {
        var val = $(this).val().toLowerCase().replace(/\s+/g, '');
        $('.lab-area-option').each(function() {
            var text = $(this).text().toLowerCase().replace(/\s+/g, '');
            if (text.indexOf(val) !== -1 || $(this).data('value') === '') {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });

    // Option selection triggers form submit
    $(document).on('click', '.lab-area-option', function() {
        var val = $(this).data('value');
        var label = $(this).find('span').text();
        
        $('#lab-selected-area-input').val(val);
        $('#lab-area-dropdown-trigger span').text(label);
        
        $('.lab-area-option').removeClass('selected').find('.check-icon').remove();
        $(this).addClass('selected');
        
        if (val !== '') {
            $(this).append('<svg class="check-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#18697F" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>');
        }
        
        $('#lab-area-dropdown-box').removeClass('open');
        $('#lab-inline-search-form').submit();
    });

    /* Grid / List View Toggle */
    $(document).on('click', '.lab-view-btn', function() {
        var view = $(this).data('view'); // 'grid' or 'list'
        $('.lab-view-btn').removeClass('active');
        $(this).addClass('active');

        var $grid = $('#lab-archive-results');
        if (view === 'list') {
            $grid.removeClass('lab-business-grid--large').addClass('lab-business-list');
            gsap.fromTo($grid.find('.lab-bcard'), { opacity: 0, x: -20 }, { opacity: 1, x: 0, duration: 0.4, stagger: 0.05 });
        } else {
            $grid.removeClass('lab-business-list').addClass('lab-business-grid--large');
            gsap.fromTo($grid.find('.lab-bcard'), { opacity: 0, scale: 0.95 }, { opacity: 1, scale: 1, duration: 0.4, stagger: 0.05 });
        }
    });

    /* Final Submit */
    $(document).on('click', '#lab-bf-submit', function() {
        var $btn  = $(this);
        var price = parseFloat((window.labBfData && window.labBfData.service_price) || 0);
        var stripeConfigured = (typeof labPaymentVars !== 'undefined' && labPaymentVars.stripe_pub_key);

        /* Paid service with an online gateway: a real payment MUST happen.
           Never silently create an unpaid booking just because the payment
           form has not finished loading (e.g. user clicked too fast, or
           stepped Back then forward, resetting the Stripe elements). */
        if (price > 0 && stripeConfigured) {
            if (!window.labStripe || !window.labStripeElements) {
                showMsg('#lab-bf-final-msg', 'The payment form is still loading. Please wait a moment and try again.', 'error');
                return;
            }

            $btn.prop('disabled', true).text('Processing…');
            window.labStripe.confirmPayment({
                elements: window.labStripeElements,
                redirect: 'if_required',
                confirmParams: {
                    return_url: labVars.home_url + '/customer-dashboard/'
                }
            }).then(function(result) {
                if (result.error) {
                    $btn.prop('disabled', false).text('Pay & Confirm');
                    showMsg('#lab-bf-final-msg', result.error.message, 'error');
                } else if (result.paymentIntent && result.paymentIntent.status === 'succeeded') {
                    labFinalizeBooking(result.paymentIntent.id, $btn);
                } else {
                    $btn.prop('disabled', false).text('Pay & Confirm');
                    showMsg('#lab-bf-final-msg', 'Payment was not completed. Please try again.', 'error');
                }
            });
            return;
        }

        /* Free service, or a paid service with no online gateway configured
           (offline / arrange-with-business). Create the booking — the success
           message reflects the real payment state returned by the server. */
        $btn.prop('disabled', true).text('Processing…');
        labFinalizeBooking('', $btn);
    });

    /* ── Payment Helpers ─────────────────────────────────────── */

    function labFinalizeBooking(paymentIntentId, $btn) {
        $.post(ajaxurl, {
            action: 'lab_create_booking',
            nonce: nonce,
            business_id: window.labCurrentBusinessId,
            service_name: window.labBfData.service_name,
            service_price: window.labBfData.service_price,
            booking_date: window.labBfData.date,
            booking_time: window.labBfData.time,
            payment_intent_id: paymentIntentId || '',
            notes: (window.labBfData.notes || '') + ' | Name: ' + (window.labBfData.name || '') + ' | Phone: ' + (window.labBfData.phone || '')
        }, function(res) {
            if ($btn) $btn.prop('disabled', false).text('Pay & Confirm');
            if (res.success) {
                var paid = (res.data.payment_status === 'paid' || res.data.payment_status === 'free');
                var msg  = paid
                    ? '✓ Payment received — booking confirmed! Taking you to your dashboard…'
                    : '✓ Booking requested. Payment is still outstanding — the business will contact you to arrange it. Taking you to your dashboard…';
                showMsg('#lab-bf-final-msg', msg, 'success');
                setTimeout(function() {
                    window.location.href = labVars.home_url + '/customer-dashboard/';
                }, 2000);
            } else {
                showMsg('#lab-bf-final-msg', res.data.message || 'An error occurred. Please try again.', 'error');
            }
        });
    }

    function labInitPayment(amount) {
        var $stripeEl = $('#lab-stripe-payment-element');
        var $paypalEl = $('#lab-paypal-button-container');

        /* Free service */
        if (parseFloat(amount) <= 0) {
            $stripeEl.html(
                '<div style="background:#0a2a0a; border:1px solid #1a6b1a; border-radius:8px; padding:1.5rem; text-align:center; color:#4caf50;">' +
                '<strong>This service is free — no payment required.</strong>' +
                '</div>'
            );
            return;
        }

        /* Stripe */
        if (typeof labPaymentVars !== 'undefined' && labPaymentVars.stripe_pub_key) {
            $stripeEl.html('<p style="color:#aaa; text-align:center; padding:1.5rem 0;">Loading payment form…</p>');

            $.post(ajaxurl, {
                action: 'lab_create_payment_intent',
                nonce: nonce,
                amount: amount,
                currency: labVars.currency || 'GBP'
            }, function(res) {
                if (!res.success) {
                    $stripeEl.html(
                        '<p style="color:#dc3545; text-align:center; padding:1rem 0;">' +
                        (res.data && res.data.message ? res.data.message : 'Payment unavailable.') +
                        '</p>'
                    );
                    return;
                }

                var stripe = Stripe(labPaymentVars.stripe_pub_key);
                var elements = stripe.elements({
                    clientSecret: res.data.client_secret,
                    appearance: {
                        theme: 'night',
                        variables: { colorPrimary: '#18697F', borderRadius: '8px' }
                    }
                });
                var paymentElement = elements.create('payment', {
                    defaultValues: {
                        billingDetails: {
                            name: window.labBfData && window.labBfData.name ? window.labBfData.name : '',
                            email: window.labBfData && window.labBfData.email ? window.labBfData.email : '',
                            phone: window.labBfData && window.labBfData.phone ? window.labBfData.phone : ''
                        }
                    }
                });
                $stripeEl.empty();
                paymentElement.mount('#lab-stripe-payment-element');

                window.labStripe = stripe;
                window.labStripeElements = elements;
            });
        } else {
            /* No Stripe key — show offline notice */
            $stripeEl.html(
                '<div style="background:#1a1a2e; border:1px solid #333; border-radius:8px; padding:1.5rem; text-align:center; color:#aaa;">' +
                '<p style="margin:0 0 0.5rem;">No online payment gateway is configured.</p>' +
                '<p style="margin:0; font-size:0.85rem;">Your booking will be confirmed and the business will contact you to arrange payment.</p>' +
                '</div>'
            );
        }

        /* PayPal */
        if (typeof labPaymentVars !== 'undefined' && labPaymentVars.paypal_client_id && typeof paypal !== 'undefined') {
            $paypalEl.show();
            paypal.Buttons({
                style: { layout: 'horizontal', color: 'gold', shape: 'rect', label: 'pay' },
                createOrder: function(data, actions) {
                    return actions.order.create({
                        purchase_units: [{ amount: { value: parseFloat(amount).toFixed(2) } }]
                    });
                },
                onApprove: function(data, actions) {
                    return actions.order.capture().then(function(details) {
                        labFinalizeBooking('paypal_' + details.id, null);
                        showMsg('#lab-bf-final-msg', '✓ PayPal payment approved! Confirming booking…', 'success');
                        $('#lab-bf-submit').prop('disabled', true);
                    });
                },
                onError: function() {
                    showMsg('#lab-bf-final-msg', 'PayPal payment failed. Please try again or use a card.', 'error');
                }
            }).render('#lab-paypal-button-container');
        }
    }

    /* ── 13. Simplified Header Scroll Handler ────────────────────── */
    var $header = $('.lab-global-header');
    if ($header.length > 0) {
        $(window).on('scroll', function() {
            var sy = window.scrollY || window.pageYOffset;
            if (sy > 60) {
                $header.addClass('lab-global-header--scrolled');
            } else {
                if ($header.hasClass('lab-global-header--home')) {
                    $header.removeClass('lab-global-header--scrolled');
                }
            }
        });
    }

    /* ── Invoice Modal Handlers ────────────────────────────────── */
    $(document).on('click', '.lab-view-invoice-btn', function(e) {
        e.preventDefault();
        var $btn = $(this);
        
        // Extract data attributes
        var number        = $btn.data('invoice-number');
        var date          = $btn.data('invoice-date');
        var custName      = $btn.data('cust-name');
        var custEmail     = $btn.data('cust-email');
        var bizName       = $btn.data('biz-name');
        var bizAddress    = $btn.data('biz-address');
        var serviceName   = $btn.data('service-name');
        var servicePrice  = $btn.data('service-price');
        var totalAmount   = $btn.data('total-amount');
        var paymentMethod = $btn.data('payment-method');
        var paymentStatus = $btn.data('payment-status');

        // Inject into modal elements
        $('#lab-invoice-modal-number').text(number);
        $('#lab-invoice-modal-date').text(date);
        $('#lab-invoice-modal-cust-name').text(custName);
        $('#lab-invoice-modal-cust-email').text(custEmail);
        $('#lab-invoice-modal-biz-name').text(bizName);
        $('#lab-invoice-modal-biz-address').text(bizAddress);
        $('#lab-invoice-modal-service-name').text(serviceName);
        $('#lab-invoice-modal-service-price').text(servicePrice);
        $('#lab-invoice-modal-service-total').text(totalAmount);
        $('#lab-invoice-modal-total').text(totalAmount);
        $('#lab-invoice-modal-payment-method').text(paymentMethod);
        
        var $statusEl = $('#lab-invoice-modal-payment-status');
        $statusEl.text(paymentStatus)
            .removeClass('lab-invoice-status--paid lab-invoice-status--unpaid lab-invoice-status--cancelled');
        if (paymentStatus.toLowerCase() === 'paid') {
            $statusEl.addClass('lab-invoice-status--paid');
        } else if (paymentStatus.toLowerCase() === 'unpaid') {
            $statusEl.addClass('lab-invoice-status--unpaid');
        } else {
            $statusEl.addClass('lab-invoice-status--cancelled');
        }

        // Show modal
        $('#lab-invoice-modal').fadeIn(200);
    });

    $(document).on('click', '#lab-invoice-modal-close, .lab-modal__overlay', function(e) {
        e.preventDefault();
        $('#lab-invoice-modal').fadeOut(200);
    });

    $(document).on('click', '#lab-invoice-print-btn', function(e) {
        e.preventDefault();
        window.print();
    });

    /* ── Custom Select (cross-browser selected-value display fix) ── */
    function labInitCustomSelect($sel) {
        if ($sel.data('lab-csel')) return;
        $sel.data('lab-csel', true);

        var $wrap    = $('<div class="lab-csel"></div>');
        var $trigger = $('<button type="button" class="lab-csel__trigger"></button>');
        var $list    = $('<ul class="lab-csel__list"></ul>');

        $sel.find('option').each(function() {
            var $o  = $(this);
            var $li = $('<li class="lab-csel__opt"></li>')
                .text($o.text())
                .attr('data-val', $o.val());
            if (!$o.val()) $li.addClass('lab-csel__opt--ph');
            $list.append($li);
        });

        var $init = $sel.find('option:selected');
        if ($init.val()) {
            $trigger.text($init.text());
            $list.find('.lab-csel__opt').filter(function() { return $(this).data('val') === $init.val(); }).addClass('selected');
        } else {
            $trigger.text($sel.find('option:first').text()).addClass('lab-csel__trigger--empty');
        }

        $sel.after($wrap.append($trigger, $list));
        $sel.hide();

        $trigger.on('click', function(e) {
            e.stopPropagation();
            var opening = !$wrap.hasClass('open');
            $('.lab-csel.open').removeClass('open');
            if (opening) $wrap.addClass('open');
        });

        $trigger.on('keydown', function(e) {
            if (e.key === ' ' || e.key === 'Enter') {
                e.preventDefault();
                $trigger.trigger('click');
            }
            if (e.key === 'Escape') {
                $wrap.removeClass('open');
            }
        });

        $list.on('click', '.lab-csel__opt', function() {
            var val  = $(this).data('val');
            var text = $(this).text();
            $sel.val(val).trigger('change');
            $trigger.text(text);
            $list.find('.lab-csel__opt').removeClass('selected');
            if (val) {
                $(this).addClass('selected');
                $trigger.removeClass('lab-csel__trigger--empty');
            } else {
                $trigger.addClass('lab-csel__trigger--empty');
            }
            $wrap.removeClass('open');
        });

        $sel.closest('form').on('reset.labcsel', function() {
            $trigger.text($sel.find('option:first').text()).addClass('lab-csel__trigger--empty');
            $list.find('.lab-csel__opt').removeClass('selected');
        });
    }

    $(document).on('click', function(e) {
        if (!$(e.target).closest('.lab-csel').length) {
            $('.lab-csel.open').removeClass('open');
        }
    });

    $('.lab-field select').each(function() {
        labInitCustomSelect($(this));
    });

    /* ── Responsive Postcode Placeholder ── */
    function updatePostcodePlaceholder() {
        var $input = $('.lab-hero__search-input');
        if ($input.length === 0) return;
        
        if (window.innerWidth < 480) {
            $input.attr('placeholder', 'e.g. SW1A 2AA');
        } else if (window.innerWidth < 768) {
            $input.attr('placeholder', 'Enter postcode (e.g. SW1A 2AA)');
        } else {
            $input.attr('placeholder', 'Enter your postcode (e.g. SW1A 2AA)');
        }
    }
    
    $(window).on('resize', updatePostcodePlaceholder);
    updatePostcodePlaceholder();

    /* ── Invoice Modal Handlers ────────────────────────────────── */
    $(document).on('click', '.lab-view-invoice-btn', function(e) {
        e.preventDefault();
        var $btn = $(this);
        
        // Extract data attributes
        var number        = $btn.data('invoice-number');
        var date          = $btn.data('invoice-date');
        var custName      = $btn.data('cust-name');
        var custEmail     = $btn.data('cust-email');
        var bizName       = $btn.data('biz-name');
        var bizAddress    = $btn.data('biz-address');
        var serviceName   = $btn.data('service-name');
        var servicePrice  = $btn.data('service-price');
        var totalAmount   = $btn.data('total-amount');
        var paymentMethod = $btn.data('payment-method');
        var paymentStatus = $btn.data('payment-status');

        // Inject into modal elements
        $('#lab-invoice-modal-number').text(number);
        $('#lab-invoice-modal-date').text(date);
        $('#lab-invoice-modal-cust-name').text(custName);
        $('#lab-invoice-modal-cust-email').text(custEmail);
        $('#lab-invoice-modal-biz-name').text(bizName);
        $('#lab-invoice-modal-biz-address').text(bizAddress);
        $('#lab-invoice-modal-service-name').text(serviceName);
        $('#lab-invoice-modal-service-price').text(servicePrice);
        $('#lab-invoice-modal-service-total').text(totalAmount);
        $('#lab-invoice-modal-total').text(totalAmount);
        $('#lab-invoice-modal-payment-method').text(paymentMethod);
        
        var $statusEl = $('#lab-invoice-modal-payment-status');
        $statusEl.text(paymentStatus);
        if (paymentStatus.toLowerCase() === 'paid') {
            $statusEl.css('color', '#198754');
        } else if (paymentStatus.toLowerCase() === 'unpaid') {
            $statusEl.css('color', '#f79e1b');
        } else {
            $statusEl.css('color', '#dc3545');
        }

        // Show modal
        $('#lab-invoice-modal').fadeIn(200);
    });

    $(document).on('click', '#lab-invoice-modal-close, .lab-modal__overlay', function(e) {
        e.preventDefault();
        $('#lab-invoice-modal').fadeOut(200);
    });

    $(document).on('click', '#lab-invoice-print-btn', function(e) {
        e.preventDefault();
        window.print();
    });

    /* ── Popular Categories More Button Toggler ────────────────── */
    $(document).on('click', '.lab-cat-more button', function(e) {
        e.preventDefault();
        var $btn = $(this);
        var $hiddenCards = $('.lab-cat-card--hidden');
        if ($hiddenCards.is(':visible')) {
            $hiddenCards.fadeOut(300);
            $btn.text('More');
        } else {
            $hiddenCards.fadeIn(300);
            $btn.text('Less');
        }
    });

    /* ── Page Transitions (Normal Fade Transition) ──────────────── */
    $(function() {
        var $overlay = $('.lab-transition-overlay');
        if ($overlay.length > 0) {
            $overlay.addClass('is-loaded');
            setTimeout(function() {
                $overlay.css('display', 'none');
            }, 300);
        }
    });

    window.addEventListener('pageshow', function(event) {
        var $overlay = $('.lab-transition-overlay');
        if ($overlay.length > 0) {
            $overlay.css('display', 'block');
            $overlay.removeClass('is-loaded');
            // Force reflow
            $overlay[0].offsetHeight;
            $overlay.addClass('is-loaded');
            setTimeout(function() {
                $overlay.css('display', 'none');
            }, 300);
        }
    });

    // Intercept internal links for exit animation
    $(document).on('click', 'a', function(e) {
        var href = $(this).attr('href');

        // Skip anchors, javascript, mailto, tel, target=_blank, or custom elements
        if (!href || 
            href.startsWith('#') || 
            href.startsWith('javascript:') || 
            href.startsWith('mailto:') || 
            href.startsWith('tel:') || 
            $(this).attr('target') === '_blank' ||
            $(this).hasClass('no-transition') ||
            $(this).closest('.no-transition').length > 0) {
            return;
        }

        // Skip cmd/ctrl/shift clicks (open in new tab/window)
        if (e.metaKey || e.ctrlKey || e.shiftKey || e.which === 2) {
            return;
        }

        var isInternal = false;
        var homeUrl = labVars.home_url || window.location.origin;

        // Check if URL is local/internal
        if (href.indexOf('/') === 0 || 
            href.indexOf(window.location.hostname) !== -1 || 
            href.indexOf(homeUrl) !== -1) {
            isInternal = true;
        }

        if (isInternal) {
            var $overlay = $('.lab-transition-overlay');
            if ($overlay.length > 0) {
                e.preventDefault();
                
                // If on homepage and header is not scrolled, trigger transition behavior
                var $header = $('.lab-global-header');
                if ($header.hasClass('lab-global-header--home') && !$header.hasClass('lab-global-header--scrolled')) {
                    $header.addClass('lab-global-header--scrolled');
                }

                $overlay.css('display', 'block');
                // Force reflow
                $overlay[0].offsetHeight;
                $overlay.removeClass('is-loaded');

                setTimeout(function() {
                    window.location.href = href;
                }, 300);
            }
        }
    });

    /* Password visibility toggle (registration + login + business signup) */
    $(function() {
        var eyeOpen = '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
        var eyeOff  = '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>';

        $('.lab-auth-page input[type="password"]').each(function() {
            var $input = $(this);
            if ($input.parent().hasClass('lab-pass-field')) return;
            $input.wrap('<div class="lab-pass-field"></div>');
            var $btn = $('<button type="button" class="lab-pass-toggle" aria-label="Show password" tabindex="-1"></button>').html(eyeOff);
            $input.after($btn);
            $btn.on('click', function() {
                var show = $input.attr('type') === 'password';
                $input.attr('type', show ? 'text' : 'password');
                $btn.html(show ? eyeOpen : eyeOff);
                $btn.attr('aria-label', show ? 'Hide password' : 'Show password');
            });
        });
    });

})(jQuery);
