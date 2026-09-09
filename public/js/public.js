(function ($) {
    'use strict';

    var cfg = window.dlab_public || {};
    var updateTimer = null;

    function i18n(key, fallback) {
        return (cfg.i18n && cfg.i18n[key]) ? cfg.i18n[key] : fallback;
    }

    function post(action, data, callback) {
        data = data || {};
        data.action = action;
        data.nonce = cfg.nonce;
        $.post(cfg.ajax_url, data).done(function (res) {
            callback(res && res.success ? res.data : null, res && !res.success ? res.data : null);
        }).fail(function () {
            callback(null, { message: i18n('error', 'Něco se pokazilo. Zkuste to znovu.') });
        });
    }

    function applyPayload(data) {
        if (!data) {
            return;
        }
        if (typeof data.count !== 'undefined') {
            cfg.count = data.count;
            updateCount(data.count);
        }
        if (data.in_pass) {
            cfg.in_pass = data.in_pass;
            syncPassActions(data.in_pass);
        }
        if (data.pass_url) {
            cfg.pass_url = data.pass_url;
        }
        if (typeof data.html === 'string' && $('[data-dlab-pass-body]').length) {
            $('[data-dlab-pass-body]').html(data.html);
            initQuantitySpinners();
            toggleContinue(data.count);
        }
    }

    function updateCount(count) {
        var label = i18n('pass_count', 'Pass (%d)').replace('%d', String(count));
        $('[data-dlab-basket-count]').each(function () {
            this.setAttribute('data-dlab-basket-count', String(count));
            this.textContent = label;
        });
    }

    function toggleContinue(count) {
        var $pass = $('[data-dlab-pass]');
        if (!$pass.length) {
            return;
        }
        var $continue = $pass.find('.dlab-pass__continue');
        if (count > 0) {
            if (!$continue.length) {
                var listing = cfg.listing_url || '';
                var checkout = cfg.checkout_url || '';
                var $p = $('<div class="wp-block-buttons is-layout-flex wp-block-buttons-is-layout-flex dlab-pass__continue"></div>');
                $p.append($('<a>', {
                    class: 'wp-block-button__link has-dd-black-color has-dd-white-background-color has-text-color has-background has-link-color btn dlab-btn dlab-btn--ghost',
                    href: listing,
                    text: i18n('add_workshop', 'Přidat další workshop')
                }));
                $p.append($('<a>', {
                    class: 'wp-block-button__link has-dd-white-color has-dd-black-background-color has-text-color has-background has-link-color btn dlab-btn',
                    href: checkout,
                    text: i18n('reserve', 'Rezervovat')
                }));
                $pass.find('[data-dlab-pass-body]').after($p);
            }
        } else {
            $continue.remove();
        }
    }

    function btnClass(extra) {
        return ('btn dlab-btn dlab-btn--pass ' + (extra || '')).replace(/\s+/g, ' ').trim();
    }

    function syncPassActions(inPass) {
        var ids = {};
        (inPass || []).forEach(function (id) {
            ids[String(id)] = true;
        });
        $('[data-dlab-pass-action]').each(function () {
            var $wrap = $(this);
            var postId = String($wrap.data('dlab-pass-action'));
            var extra = $wrap.attr('data-dlab-class') || '';
            var open = $wrap.attr('data-dlab-open') === '1';
            var full = $wrap.attr('data-dlab-full') === '1';
            var classes = btnClass(extra);
            if (ids[postId]) {
                $wrap.html(
                    '<a class="' + classes + ' is-in-pass" href="' + (cfg.pass_url || '#') + '">' +
                    i18n('in_pass', 'V passu') +
                    '</a>'
                );
            } else {
                $wrap.html(
                    '<button type="button" class="' + classes + '" data-dlab-add="' + postId + '"' +
                    ((!open || full) ? ' disabled' : '') + '>' +
                    i18n('add_to_pass', 'Přidat do passu') +
                    '</button>'
                );
            }
        });
    }

    function showNotice(message, isError) {
        var $notice = $('[data-dlab-pass-notice]');
        if (!$notice.length) {
            if (message) {
                window.alert(message);
            }
            return;
        }
        if (!message) {
            $notice.attr('hidden', true).text('');
            return;
        }
        $notice
            .toggleClass('is-error', !!isError)
            .text(message)
            .removeAttr('hidden');
    }

    function collectLines() {
        var lines = [];
        $('.dlab-pass-line').each(function () {
            var $line = $(this);
            var postId = parseInt($line.data('post-id'), 10);
            if (!postId) {
                return;
            }
            var services = [];
            $line.find('.dlab-service-cb:checked').each(function () {
                services.push($(this).val());
            });
            lines.push({ post_id: postId, services: services });
        });
        return lines;
    }

    function sendUpdate() {
        var spots = parseInt($('#dlab-pass-spots').val(), 10) || 1;
        post('dlab_update_pass', {
            spots: spots,
            lines: JSON.stringify(collectLines())
        }, function (data, err) {
            if (err) {
                showNotice(err.message || i18n('error', 'Něco se pokazilo. Zkuste to znovu.'), true);
                applyPayload(err);
                return;
            }
            showNotice('');
            applyPayload(data);
        });
    }

    function scheduleUpdate() {
        clearTimeout(updateTimer);
        updateTimer = setTimeout(sendUpdate, 350);
    }

    function initQuantitySpinners() {
        document.querySelectorAll('.dlab-quantity').forEach(function (spinner) {
            if (spinner.getAttribute('data-dlab-quantity-ready')) {
                return;
            }
            spinner.setAttribute('data-dlab-quantity-ready', '1');

            var input = spinner.querySelector('input[type="number"]');
            var btnUp = spinner.querySelector('.dlab-quantity-up');
            var btnDown = spinner.querySelector('.dlab-quantity-down');
            if (!input || !btnUp || !btnDown) {
                return;
            }

            var min = parseFloat(input.getAttribute('min'));
            var max = parseFloat(input.getAttribute('max'));
            if (isNaN(min)) {
                min = 1;
            }
            if (isNaN(max)) {
                max = Infinity;
            }

            function setValue(newVal) {
                input.value = newVal;
                input.dispatchEvent(new Event('change', { bubbles: true }));
            }

            btnUp.addEventListener('click', function () {
                var oldValue = parseFloat(input.value);
                if (isNaN(oldValue)) {
                    oldValue = min;
                }
                setValue(oldValue >= max ? oldValue : oldValue + 1);
            });

            btnDown.addEventListener('click', function () {
                var oldValue = parseFloat(input.value);
                if (isNaN(oldValue)) {
                    oldValue = min;
                }
                setValue(oldValue <= min ? oldValue : oldValue - 1);
            });

            input.addEventListener('change', function () {
                var val = parseFloat(input.value);
                if (isNaN(val) || val < min) {
                    input.value = min;
                } else if (val > max) {
                    input.value = max;
                }
            });
        });
    }

    $(document).on('click', '[data-dlab-add]', function (e) {
        e.preventDefault();
        e.stopPropagation();
        if (this.disabled) {
            return;
        }
        var $btn = $(this);
        var postId = parseInt($btn.data('dlab-add') || $btn.closest('[data-dlab-pass-action]').data('dlab-pass-action'), 10);
        if (!postId) {
            return;
        }
        $btn.prop('disabled', true);
        post('dlab_add_to_pass', { post_id: postId }, function (data, err) {
            if (err) {
                $btn.prop('disabled', false);
                window.alert(err.message || i18n('error', 'Něco se pokazilo. Zkuste to znovu.'));
                return;
            }
            applyPayload(data);
        });
    });

    $(document).on('click', '[data-dlab-remove]', function (e) {
        e.preventDefault();
        var postId = parseInt($(this).data('dlab-remove'), 10);
        if (!postId) {
            return;
        }
        post('dlab_remove_from_pass', { post_id: postId }, function (data, err) {
            if (err) {
                showNotice(err.message || i18n('error', 'Něco se pokazilo. Zkuste to znovu.'), true);
                return;
            }
            showNotice('');
            applyPayload(data);
        });
    });

    $(document).on('change', '#dlab-pass-spots, .dlab-service-cb', function () {
        scheduleUpdate();
    });

    $(document).on('submit', '#dlab-checkout-form', function (e) {
        e.preventDefault();
        var $form = $(this);
        var $btn = $('#dlab-checkout-submit').prop('disabled', true);
        var $notice = $('[data-dlab-checkout-notice]');
        var attendees = [];
        $form.find('input[name="attendees[]"]').each(function () {
            attendees.push($(this).val());
        });

        post('dlab_process_checkout', {
            contact_name: $form.find('[name="contact_name"]').val(),
            contact_email: $form.find('[name="contact_email"]').val(),
            contact_phone: $form.find('[name="contact_phone"]').val(),
            attendees: JSON.stringify(attendees),
            agree_terms: $form.find('[name="agree_terms"]').is(':checked') ? 1 : 0,
            agree_gdpr: $form.find('[name="agree_gdpr"]').is(':checked') ? 1 : 0
        }, function (data, err) {
            $btn.prop('disabled', false);
            if (err) {
                var message = (err && err.message) ? err.message : i18n('error', 'Něco se pokazilo. Zkuste to znovu.');
                if ($notice.length) {
                    $notice.addClass('is-error').text(message).removeAttr('hidden');
                } else {
                    window.alert(message);
                }
                return;
            }
            if (data && data.redirect) {
                window.location.href = data.redirect;
            }
        });
    });

    function fallbackCopy(text, done) {
        var ta = document.createElement('textarea');
        ta.value = text;
        ta.setAttribute('readonly', '');
        ta.style.position = 'absolute';
        ta.style.left = '-9999px';
        document.body.appendChild(ta);
        ta.select();
        try {
            document.execCommand('copy');
            done();
        } catch (err) {
            window.alert(text);
        }
        document.body.removeChild(ta);
    }

    $(document).on('click', '.dlab-copy-payment', function () {
        var $btn = $(this);
        var text = $btn.attr('data-copy') || '';
        var copied = $btn.attr('data-copied') || i18n('copied', 'Zkopírováno');
        var original = $btn.text();

        if (!text) {
            return;
        }

        function done() {
            $btn.text(copied);
            window.setTimeout(function () {
                $btn.text(original);
            }, 2000);
        }

        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(done).catch(function () {
                fallbackCopy(text, done);
            });
            return;
        }

        fallbackCopy(text, done);
    });

    $(function () {
        initQuantitySpinners();
        if (cfg.in_pass) {
            syncPassActions(cfg.in_pass);
        }
        if (typeof cfg.count !== 'undefined') {
            updateCount(cfg.count);
        }
    });
})(jQuery);
