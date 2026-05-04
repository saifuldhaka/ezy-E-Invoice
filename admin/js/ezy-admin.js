/* ezy E Invoice – Admin JS */
(function($) {
    'use strict';

    var cfg = window.ezyeinInvoice || {};
    var currency    = cfg.currency    || 'RM';
    var taxRate     = parseFloat(cfg.taxRate)  || 0;
    var taxLabel    = cfg.taxLabel    || 'Tax';
    var scRate      = parseFloat(cfg.scRate)   || 0;
    var scLabel     = cfg.scLabel     || 'Service Charge';
    var taxEnabled  = !!cfg.taxEnabled;
    var scEnabled   = !!cfg.scEnabled;
    var rowCounter  = 0;

    // ── INIT ─────────────────────────────────────────────────────────────────
    $(document).ready(function() {
        // Modal open
        $(document).on('click', '.ezy-btn-add', function(e) {
            e.preventDefault();
            var modal = $('#' + $(this).data('modal'));
            modal.find('form')[0].reset();
            modal.find('input[name="id"]').val(0);
            modal.find('.ezy-form-msg').text('');
            modal.find('h2').first().text( $(this).data('modal') === 'client-modal' ? 'Add Client' : 'Add Product' );
            openModal(modal);
        });

        // Modal close
        $(document).on('click', '.ezy-modal-close, .ezy-modal-overlay', function() {
            closeModal( $(this).closest('.ezy-modal') );
        });

        // ── CLIENT AUTOCOMPLETE ───────────────────────────────────────────────
        var clientInput = $('#client-search-input');
        if (clientInput.length) {
            clientInput.autocomplete({
                source: function(req, res) {
                    $.getJSON(cfg.ajaxUrl, {
                        action: 'ezyein_search_clients',
                        nonce:  cfg.nonce,
                        q:      req.term
                    }, function(data) {
                        if (data.success) res(data.data);
                        else res([]);
                    });
                },
                minLength: 0,
                select: function(e, ui) {
                    $('#client_id').val(ui.item.id);
                    loadClientInfo(ui.item.id);
                    return true;
                },
                focus: function(e, ui) {
                    clientInput.val(ui.item.label);
                    return false;
                }
            });
            clientInput.on('focus', function() {
                if (!clientInput.val()) clientInput.autocomplete('search', '');
            });
            clientInput.on('input', function() {
                if (!$(this).val()) {
                    $('#client_id').val('');
                    $('#client-info').hide();
                }
            });
        }

        function loadClientInfo(id) {
            $.getJSON(cfg.ajaxUrl, { action: 'ezyein_get_client', nonce: cfg.nonce, id: id }, function(data) {
                if (!data.success) return;
                var c = data.data;
                var html = '<div class="ezy-client-card">';
                html += '<strong>' + escHtml(c.contact_name) + '</strong>';
                if (c.company_name) html += '<div>' + escHtml(c.company_name) + '</div>';
                html += '<div>' + escHtml(c.email) + '</div>';
                if (c.phone) html += '<div>' + escHtml(c.phone) + '</div>';
                if (c.address_line1) html += '<div>' + escHtml(c.address_line1) + '</div>';
                html += '</div>';
                $('#client-info').html(html).show();
            });
        }

        // ── INVOICE ITEMS ─────────────────────────────────────────────────────
        var itemsBody = $('#items-body');
        if (itemsBody.length) {
            addItemRow(); // start with one row
            $('#add-item-btn').on('click', addItemRow);
            itemsBody.on('click', '.remove-item-row', function() {
                if (itemsBody.find('tr').length > 1) {
                    $(this).closest('tr').remove();
                    reIndexRows();
                    recalculate();
                }
            });
            itemsBody.on('input change', '.item-price, .item-qty', function() { recalcRowTotal($(this).closest('tr')); recalculate(); });
        }

        function addItemRow() {
            rowCounter++;
            var ri = rowCounter;
            var row = $(
                '<tr class="ezy-item-row" data-ri="' + ri + '">' +
                '<td class="col-num row-num"></td>' +
                '<td class="col-product">' +
                  '<input type="text" class="item-product-search ezy-product-autocomplete" placeholder="Search product…" autocomplete="off" />' +
                  '<input type="hidden" class="item-product-id" />' +
                '</td>' +
                '<td class="col-desc"><input type="text" class="item-desc" placeholder="Description…" /></td>' +
                '<td class="col-price"><input type="number" class="item-price" value="0.00" min="0" step="0.01" /></td>' +
                '<td class="col-qty"><input type="number" class="item-qty" value="1" min="0.01" step="0.01" /></td>' +
                '<td class="col-total"><span class="item-line-total">' + currency + ' 0.00</span></td>' +
                '<td class="col-action"><button type="button" class="remove-item-row" title="Remove">&times;</button></td>' +
                '</tr>'
            );
            itemsBody.append(row);
            reIndexRows();
            attachProductAutocomplete(row.find('.item-product-search'));
        }

        function attachProductAutocomplete(input) {
            input.autocomplete({
                source: function(req, res) {
                    $.getJSON(cfg.ajaxUrl, {
                        action: 'ezyein_search_products',
                        nonce:  cfg.nonce,
                        q:      req.term
                    }, function(data) {
                        if (data.success) res(data.data);
                        else res([]);
                    });
                },
                minLength: 0,
                select: function(e, ui) {
                    var row = $(this).closest('tr');
                    row.find('.item-product-id').val(ui.item.id);
                    row.find('.item-desc').val(ui.item.description || '');
                    row.find('.item-price').val(parseFloat(ui.item.price || 0).toFixed(2));
                    recalcRowTotal(row);
                    recalculate();
                    return true;
                },
                focus: function(e, ui) { $(this).val(ui.item.label); return false; }
            });
            input.on('focus', function() { if (!input.val()) input.autocomplete('search', ''); });
        }

        function reIndexRows() {
            itemsBody.find('tr').each(function(i) { $(this).find('.row-num').text(i + 1); });
        }

        function recalcRowTotal(row) {
            var price = parseFloat(row.find('.item-price').val()) || 0;
            var qty   = parseFloat(row.find('.item-qty').val())   || 0;
            var total = price * qty;
            row.find('.item-line-total').text(currency + ' ' + total.toFixed(2));
            return total;
        }

        function recalculate() {
            var subtotal = 0;
            itemsBody.find('tr').each(function() {
                var p = parseFloat($(this).find('.item-price').val()) || 0;
                var q = parseFloat($(this).find('.item-qty').val())   || 0;
                subtotal += p * q;
            });

            var taxAmt = taxEnabled ? subtotal * taxRate / 100 : 0;
            var scAmt  = scEnabled  ? subtotal * scRate  / 100 : 0;
            var disc   = parseFloat($('#discount-amount').val()) || 0;
            var total  = subtotal + taxAmt + scAmt - disc;

            $('#display-subtotal').text(currency + ' ' + subtotal.toFixed(2));
            $('#display-tax').text(currency + ' '  + taxAmt.toFixed(2));
            $('#display-sc').text(currency + ' '   + scAmt.toFixed(2));
            $('#display-discount').text('-' + currency + ' ' + disc.toFixed(2));
            $('#display-total').html('<strong>' + currency + ' ' + total.toFixed(2) + '</strong>');

            $('#hid-subtotal').val(subtotal.toFixed(2));
            $('#hid-tax-amt').val(taxAmt.toFixed(2));
            $('#hid-sc-amt').val(scAmt.toFixed(2));
            $('#hid-total').val(total.toFixed(2));
            $('#hid-tax-en').val(taxEnabled ? '1' : '0');
            $('#hid-sc-en').val(scEnabled   ? '1' : '0');
        }

        // Tax & SC toggles
        $('#tax-toggle').on('change', function() {
            taxEnabled = $(this).is(':checked');
            $('#tax-row').toggle(taxEnabled);
            recalculate();
        });
        $('#sc-toggle').on('change', function() {
            scEnabled = $(this).is(':checked');
            $('#sc-row').toggle(scEnabled);
            recalculate();
        });
        $('#discount-amount').on('input', function() {
            var d = parseFloat($(this).val()) || 0;
            $('#discount-row').toggle(d > 0);
            recalculate();
        });

        // ── SUBMIT INVOICE ────────────────────────────────────────────────────
        $('#ezy-create-send-btn').on('click', function() {
            var clientId = $('#client_id').val();
            if (!clientId) { showCreateError('Please select a client.'); return; }

            var items = [];
            var hasItems = false;
            itemsBody.find('tr').each(function() {
                var name  = $(this).find('.item-product-search').val().trim();
                var price = parseFloat($(this).find('.item-price').val()) || 0;
                var qty   = parseFloat($(this).find('.item-qty').val()) || 0;
                if (name || price > 0) {
                    hasItems = true;
                    items.push({
                        product_id:       $(this).find('.item-product-id').val() || '',
                        item_name:        name || 'Item',
                        item_description: $(this).find('.item-desc').val() || '',
                        unit_price:       price,
                        quantity:         qty || 1
                    });
                }
            });
            if (!hasItems || items.length === 0) { showCreateError('Please add at least one item.'); return; }

            var total = parseFloat($('#hid-total').val()) || 0;
            if (total <= 0) { showCreateError('Invoice total must be greater than zero.'); return; }

            hideCreateError();
            var btn = $(this);
            btn.prop('disabled', true);
            $('#create-spinner').addClass('is-active');

            var postData = {
                action:                   'ezyein_create_invoice',
                nonce:                    cfg.nonce,
                client_id:                clientId,
                invoice_number:           $('#invoice_number').val(),
                issue_date:               $('#issue_date').val(),
                due_date:                 $('#due_date').val(),
                notes:                    $('#invoice-notes').val(),
                subtotal:                 $('#hid-subtotal').val(),
                tax_rate:                 $('#hid-tax-rate').val(),
                tax_amount:               $('#hid-tax-amt').val(),
                tax_enabled:              $('#hid-tax-en').val(),
                service_charge_rate:      $('#hid-sc-rate').val(),
                service_charge_amount:    $('#hid-sc-amt').val(),
                service_charge_enabled:   $('#hid-sc-en').val(),
                discount_amount:          parseFloat($('#discount-amount').val() || 0),
                total:                    $('#hid-total').val(),
                items:                    JSON.stringify(items)
            };

            $.post(cfg.ajaxUrl, postData, function(data) {
                btn.prop('disabled', false);
                $('#create-spinner').removeClass('is-active');
                if (data.success) {
                    $('#ezy-success-msg').text(data.data.message);
                    $('#ezy-view-invoice-link').attr('href', data.data.view_url);
                    $('#ezy-create-success').slideDown();
                    $('html, body').animate({ scrollTop: 0 }, 400);
                } else {
                    showCreateError(data.data || 'An error occurred. Please try again.');
                }
            }).fail(function() {
                btn.prop('disabled', false);
                $('#create-spinner').removeClass('is-active');
                showCreateError('Network error. Please try again.');
            });
        });

        function showCreateError(msg) {
            $('#create-error').text(msg).show();
            $('html, body').animate({ scrollTop: $('#create-error').offset().top - 80 }, 300);
        }
        function hideCreateError() { $('#create-error').hide(); }

        // ── CLIENT CRUD ───────────────────────────────────────────────────────
        $('#client-form').on('submit', function(e) {
            e.preventDefault();
            var form = $(this);
            var btn  = form.find('#save-client-btn');
            btn.prop('disabled', true);
            form.find('.ezy-spinner').addClass('is-active');
            form.find('.ezy-form-msg').text('');
            $.post(cfg.ajaxUrl, form.serialize() + '&action=ezyein_save_client&nonce=' + cfg.nonce, function(data) {
                btn.prop('disabled', false);
                form.find('.ezy-spinner').removeClass('is-active');
                if (data.success) {
                    form.find('.ezy-form-msg').text(data.data.message).css('color','#2c9f2c');
                    setTimeout(function() { location.reload(); }, 800);
                } else {
                    form.find('.ezy-form-msg').text(data.data || 'Error saving client.').css('color','#d63638');
                }
            });
        });

        $(document).on('click', '.ezy-edit-client', function() {
            var id = $(this).data('id');
            $.getJSON(cfg.ajaxUrl, { action: 'ezyein_get_client', nonce: cfg.nonce, id: id }, function(data) {
                if (!data.success) return;
                var c = data.data;
                $('#client_id').val(c.id);
                $('#cf_contact_name').val(c.contact_name);
                $('#cf_company_name').val(c.company_name);
                $('#cf_email').val(c.email);
                $('#cf_phone').val(c.phone);
                $('#cf_address_line1').val(c.address_line1);
                $('#cf_address_line2').val(c.address_line2);
                $('#cf_city').val(c.city);
                $('#cf_state_province').val(c.state_province);
                $('#cf_country').val(c.country);
                $('#cf_postal_code').val(c.postal_code);
                $('#cf_tax_number').val(c.tax_number);
                $('#cf_notes').val(c.notes);
                $('#client-modal-title').text('Edit Client');
                openModal($('#client-modal'));
            });
        });

        $(document).on('click', '.ezy-delete-client', function() {
            var id   = $(this).data('id');
            var name = $(this).data('name');
            if (!confirm('Delete client "' + name + '"? This cannot be undone.')) return;
            $.post(cfg.ajaxUrl, { action: 'ezyein_delete_client', nonce: cfg.nonce, id: id }, function(data) {
                if (data.success) location.reload();
                else alert(data.data || 'Error deleting client.');
            });
        });

        // ── PRODUCT CRUD ──────────────────────────────────────────────────────
        $('#product-form').on('submit', function(e) {
            e.preventDefault();
            var form = $(this);
            var btn  = form.find('#save-product-btn');
            btn.prop('disabled', true);
            form.find('.ezy-spinner').addClass('is-active');
            form.find('.ezy-form-msg').text('');
            $.post(cfg.ajaxUrl, form.serialize() + '&action=ezyein_save_product&nonce=' + cfg.nonce, function(data) {
                btn.prop('disabled', false);
                form.find('.ezy-spinner').removeClass('is-active');
                if (data.success) {
                    form.find('.ezy-form-msg').text(data.data.message).css('color','#2c9f2c');
                    setTimeout(function() { location.reload(); }, 800);
                } else {
                    form.find('.ezy-form-msg').text(data.data || 'Error saving product.').css('color','#d63638');
                }
            });
        });

        $(document).on('click', '.ezy-edit-product', function() {
            var id = $(this).data('id');
            $.getJSON(cfg.ajaxUrl, { action: 'ezyein_get_product', nonce: cfg.nonce, id: id }, function(data) {
                if (!data.success) return;
                var p = data.data;
                $('#product_id').val(p.id);
                $('#pf_name').val(p.name);
                $('#pf_sku').val(p.sku);
                $('#pf_unit').val(p.unit);
                $('#pf_price').val(p.price);
                $('#pf_description').val(p.description);
                if ($('#pf_wc_product_id').length) $('#pf_wc_product_id').val(p.wc_product_id || '');
                $('#product-modal-title').text('Edit Product');
                openModal($('#product-modal'));
            });
        });

        $(document).on('click', '.ezy-delete-product', function() {
            var id   = $(this).data('id');
            var name = $(this).data('name');
            if (!confirm('Delete product "' + name + '"?')) return;
            $.post(cfg.ajaxUrl, { action: 'ezyein_delete_product', nonce: cfg.nonce, id: id }, function(data) {
                if (data.success) location.reload();
                else alert(data.data || 'Error deleting product.');
            });
        });

        // WC Sync
        $('#sync-wc-btn').on('click', function() {
            var btn = $(this);
            btn.prop('disabled', true).text('Syncing…');
            $.post(cfg.ajaxUrl, { action: 'ezyein_sync_wc_products', nonce: cfg.nonce }, function(data) {
                btn.prop('disabled', false).html('<span class="dashicons dashicons-update"></span> Sync from WooCommerce');
                if (data.success) { alert(data.data.message); location.reload(); }
                else alert(data.data || 'Sync failed.');
            });
        });

        // ── INVOICE ACTIONS ───────────────────────────────────────────────────
        $(document).on('click', '.ezy-resend-invoice', function() {
            var id = $(this).data('id');
            if (!confirm('Resend invoice email to client?')) return;
            var btn = $(this).prop('disabled', true);
            $.post(cfg.ajaxUrl, { action: 'ezyein_resend_invoice', nonce: cfg.nonce, id: id }, function(data) {
                btn.prop('disabled', false);
                showActionMsg(data.success ? data.data.message : (data.data || 'Error.'), data.success);
                if (!data.success) alert(data.data || 'Failed to send email.');
            });
        });

        $(document).on('click', '.ezy-mark-paid', function() {
            var id = $(this).data('id');
            if (!confirm('Mark this invoice as paid?')) return;
            $.post(cfg.ajaxUrl, { action: 'ezyein_mark_paid', nonce: cfg.nonce, id: id }, function(data) {
                if (data.success) location.reload();
                else alert(data.data || 'Error.');
            });
        });

        $(document).on('click', '.ezy-delete-invoice', function() {
            var id  = $(this).data('id');
            var num = $(this).data('number');
            if (!confirm('Delete invoice ' + num + '? This cannot be undone.')) return;
            $.post(cfg.ajaxUrl, { action: 'ezyein_delete_invoice', nonce: cfg.nonce, id: id }, function(data) {
                if (data.success) {
                    if (cfg.invoicesUrl) window.location = cfg.invoicesUrl;
                    else location.reload();
                } else alert(data.data || 'Error deleting invoice.');
            });
        });

        function showActionMsg(msg, success) {
            var el = $('#ezy-action-msg');
            if (!el.length) return;
            el.removeClass('notice-success notice-error')
              .addClass(success ? 'notice-success' : 'notice-error')
              .html('<p>' + escHtml(msg) + '</p>').show();
        }

        // ── MODAL HELPERS ─────────────────────────────────────────────────────
        function openModal(modal) {
            if (!$('.ezy-modal-overlay').length) $('<div class="ezy-modal-overlay"></div>').appendTo('body');
            modal.show();
            $('body').addClass('ezy-modal-open');
        }

        function closeModal(modal) {
            if (modal.length) modal.hide();
            else $('.ezy-modal').hide();
            $('.ezy-modal-overlay').remove();
            $('body').removeClass('ezy-modal-open');
        }

        $(document).on('keydown', function(e) { if (e.key === 'Escape') closeModal(); });

        function escHtml(str) {
            return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
        }
    });

})(jQuery);
