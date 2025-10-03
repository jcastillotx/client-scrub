/**
 * Brand Reputation Monitor Admin JavaScript
 */

jQuery(document).ready(function($) {
    // Handle client form submission
    $(document).on('submit', '#brm-client-form', function(e) {
        e.preventDefault();

        var isEdit = $('input[name="client_id"]').length > 0;
        var action = isEdit ? 'brm_update_client' : 'brm_save_client';

        $(this).addClass('brm-loading');

        $.post(brm_ajax.ajax_url, {
            action: action,
            nonce: brm_ajax.nonce,
            name: $('#client_name').val(),
            address: $('#client_address').val(),
            website: $('#client_website').val(),
            phone: $('#client_phone').val(),
            email: $('#client_email').val(),
            keywords: $('#client_keywords').val(),
            client_id: $('input[name="client_id"]').val()
        }, function(response) {
            if (response.success) {
                showNotice('Client saved successfully!', 'success');
                $('#brm-form-section').hide();
                setTimeout(function() {
                    window.location.href = 'admin.php?page=brand-reputation-monitor';
                }, 800);
            } else {
                showNotice('Error: ' + response.data, 'error');
            }
        }).fail(function(xhr, status, error) {
            showNotice('Network error. Please try again.', 'error');
            console.error('AJAX Error:', xhr, status, error);
        }).always(function() {
            $('#brm-client-form').removeClass('brm-loading');
        });
    });

    // Handle manual scan
    window.brmManualScan = function(clientId) {
        if (confirm('Run manual scan for this client? This may take a few minutes.')) {
            var $button = $('#brm-manual-scan-btn');
            if (!$button.length) {
                $button = $('button[onclick*="brmManualScan"]');
            }
            var originalText = $button.text();
            $button.prop('disabled', true).text('Scanning...');

            var progressNotice = showNoticePersistent('Manual scan in progress...', 'info');

            $.post(brm_ajax.ajax_url, {
                action: 'brm_manual_scan',
                client_id: clientId,
                nonce: brm_ajax.nonce
            }, function(response) {
                if (progressNotice) { progressNotice.remove(); }

                if (response.success) {
                    showNotice('Scan completed! Found ' + response.data.new_results + ' new results.', 'success');
                    setTimeout(function() {
                        window.location.href = 'admin.php?page=brm-results&client_id=' + clientId;
                    }, 1200);
                } else {
                    showNotice('Error: ' + response.data, 'error');
                }
            }).fail(function(xhr, status, error) {
                if (progressNotice) { progressNotice.remove(); }
                showNotice('Network error. Please try again.', 'error');
                console.error('Manual Scan Error:', xhr, status, error);
            }).always(function() {
                $button.prop('disabled', false).text(originalText);
            });
        }
    };

    // Handle client deletion
    window.brmDeleteClient = function(clientId) {
        if (confirm('Are you sure you want to delete this client? This action cannot be undone.')) {
            var $button = $('button[onclick*="brmDeleteClient(' + clientId + ')"]');
            var originalText = $button.text();
            $button.prop('disabled', true).text('Deleting...');

            $.post(brm_ajax.ajax_url, {
                action: 'brm_delete_client',
                client_id: clientId,
                nonce: brm_ajax.nonce
            }, function(response) {
                if (response.success) {
                    showNotice('Client deleted successfully!', 'success');
                    if ($button && $button.length) {
                        $button.closest('tr').fadeOut(200, function() { $(this).remove(); });
                    }
                    setTimeout(function() {
                        window.location.href = 'admin.php?page=brand-reputation-monitor';
                    }, 800);
                } else {
                    showNotice('Error: ' + response.data, 'error');
                }
            }).fail(function(xhr, status, error) {
                showNotice('Network error. Please try again.', 'error');
                console.error('Delete Error:', xhr, status, error);
            }).always(function() {
                $button.prop('disabled', false).text(originalText);
            });
        }
    };

    // Handle client editing
    window.brmEditClient = function(clientId) {
        $.post(brm_ajax.ajax_url, {
            action: 'brm_get_client',
            client_id: clientId,
            nonce: brm_ajax.nonce
        }, function(response) {
            if (response.success) {
                $('#brm-form-section').show();

                // Render form if not present
                if (!$('#brm-client-form').length) {
                    $('#brm-client-form-container').html(getClientFormHtml());
                }

                // Populate form with client data
                $('#client_name').val(response.data.name);
                $('#client_address').val(response.data.address);
                $('#client_website').val(response.data.website);
                $('#client_phone').val(response.data.phone);
                $('#client_email').val(response.data.email);
                $('#client_keywords').val(response.data.keywords);

                // Add client ID to form
                if ($('input[name="client_id"]').length === 0) {
                    $('#brm-client-form').prepend('<input type="hidden" name="client_id" value="' + clientId + '">');
                } else {
                    $('input[name="client_id"]').val(clientId);
                }

                $('html, body').animate({
                    scrollTop: $('#brm-form-section').offset().top - 100
                }, 500);
            } else {
                showNotice('Error loading client data: ' + response.data, 'error');
            }
        }).fail(function(xhr, status, error) {
            showNotice('Network error. Please try again.', 'error');
            console.error('Edit Error:', xhr, status, error);
        });
    };

    // Cancel edit
    window.brmCancelEdit = function() {
        $('#brm-form-section').hide();
        $('#brm-client-form-container').empty();
    };

    // Show add client form
    window.brmShowAddClientForm = function() {
        $('#brm-form-section').show();
        $('#brm-client-form-container').html(getClientFormHtml());

        $('html, body').animate({
            scrollTop: $('#brm-form-section').offset().top - 100
        }, 500);
    };

    // View results
    window.brmViewResults = function(clientId) {
        window.location.href = 'admin.php?page=brm-results&client_id=' + clientId;
    };

    // Delete a monitoring result
    window.brmDeleteResult = function(resultId) {
        if (!confirm('Delete this result?')) {
            return;
        }
        var $button = jQuery('button[onclick*="brmDeleteResult(' + resultId + ')"]');
        var originalText = $button.text();
        $button.prop('disabled', true).text('Deleting...');

        jQuery.post(brm_ajax.ajax_url, {
            action: 'brm_delete_result',
            result_id: resultId,
            nonce: brm_ajax.nonce
        }, function(response) {
            if (response.success) {
                showNotice('Result deleted successfully!', 'success');
                // Remove the row from the table
                var $row = jQuery('tr[data-result-id="' + resultId + '"]');
                if ($row.length) {
                    $row.fadeOut(200, function() { jQuery(this).remove(); });
                }
            } else {
                showNotice('Error: ' + response.data, 'error');
            }
        }).fail(function(xhr, status, error) {
            showNotice('Network error. Please try again.', 'error');
            console.error('Delete Result Error:', xhr, status, error);
        }).always(function() {
            $button.prop('disabled', false).text(originalText);
        });
    };

    // Auto-refresh stats every 30 seconds
    if (typeof brm_ajax !== 'undefined' && $('.brm-stats-grid').length > 0) {
        setInterval(function() {
            refreshStats();
        }, 30000);
    }

    // Basic validation hooks
    $('#brm-client-form').on('blur', 'input[required], textarea[required]', function() {
        validateField($(this));
    });
});

// Toast utilities
function showNotice(message, type) {
    var noticeClass;
    switch (type) {
        case 'success': noticeClass = 'notice-success'; break;
        case 'error': noticeClass = 'notice-error'; break;
        case 'warning': noticeClass = 'notice-warning'; break;
        default: noticeClass = 'notice-info';
    }
    var $notice = jQuery('<div class="notice ' + noticeClass + ' is-dismissible"><p>' + message + '</p></div>');

    jQuery('.wrap h1').after($notice);

    setTimeout(function() {
        $notice.fadeOut(500, function() { jQuery(this).remove(); });
    }, 5000);
}

function showNoticePersistent(message, type) {
    var noticeClass;
    switch (type) {
        case 'success': noticeClass = 'notice-success'; break;
        case 'error': noticeClass = 'notice-error'; break;
        case 'warning': noticeClass = 'notice-warning'; break;
        default: noticeClass = 'notice-info';
    }
    var $notice = jQuery('<div class="notice ' + noticeClass + '"><p>' + message + '</p></div>');
    jQuery('.wrap h1').after($notice);
    return $notice;
}

// Stats refresh
function refreshStats() {
    jQuery.post(brm_ajax.ajax_url, {
        action: 'brm_get_stats',
        nonce: brm_ajax.nonce
    }, function(response) {
        if (response.success) {
            var s = response.data || {};
            jQuery('.brm-stat-number[data-key="total_clients"]').text(s.total_clients ?? 0);
            jQuery('.brm-stat-number[data-key="total_results"]').text(s.total_results ?? 0);
            jQuery('.brm-stat-number[data-key="recent_results"]').text(s.recent_results ?? 0);
        }
    });
}

// Client form HTML
function getClientFormHtml() {
    return `
        <form id="brm-client-form" class="brm-form">
            <div class="brm-form-group">
                <label for="client_name">Client Name *</label>
                <input type="text" id="client_name" name="name" required>
            </div>

            <div class="brm-form-group">
                <label for="client_address">Address</label>
                <textarea id="client_address" name="address" rows="3"></textarea>
            </div>

            <div class="brm-form-group">
                <label for="client_website">Website</label>
                <input type="url" id="client_website" name="website" placeholder="https://example.com">
            </div>

            <div class="brm-form-group">
                <label for="client_phone">Phone Number</label>
                <input type="tel" id="client_phone" name="phone">
            </div>

            <div class="brm-form-group">
                <label for="client_email">Email</label>
                <input type="email" id="client_email" name="email">
            </div>

            <div class="brm-form-group">
                <label for="client_keywords">Keywords for Monitoring *</label>
                <textarea id="client_keywords" name="keywords" rows="3" required placeholder="Enter keywords separated by commas (e.g., company name, brand, products)"></textarea>
                <small>Enter keywords that should be monitored for mentions, separated by commas.</small>
            </div>

            <div class="brm-form-actions">
                <button type="submit" class="button button-primary">Add Client</button>
                <button type="button" class="button" onclick="brmCancelEdit()">Cancel</button>
            </div>
        </form>
    `;
}

// Validation helpers
function validateField($field) {
    var value = $field.val().trim();
    var isRequired = $field.prop('required');

    if (isRequired && !value) {
        showFieldError($field, 'This field is required');
        return false;
    } else {
        clearFieldError($field);
        return true;
    }
}

function showFieldError($field, message) {
    clearFieldError($field);
    $field.addClass('error');
    $field.after('<div class="brm-field-error">' + message + '</div>');
}

function clearFieldError($field) {
    $field.removeClass('error');
    $field.siblings('.brm-field-error').remove();
}

function isValidUrl(url) {
    try { new URL(url); return true; } catch (e) { return false; }
}

function isValidEmail(email) {
    var re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

function isValidPhone(phone) {
    var re = /^[\+]?[1-9][\d]{0,15}$/;
    return re.test(phone.replace(/[\s\-\(\)]/g, ''));
}

// Add CSS for field errors and tooltips
jQuery(document).ready(function($) {
    $('<style>')
        .prop('type', 'text/css')
        .html(`
            .brm-field-error {
                color: #d63638;
                font-size: 12px;
                margin-top: 5px;
                display: block;
            }

            .brm-form-group input.error,
            .brm-form-group textarea.error {
                border-color: #d63638;
                box-shadow: 0 0 0 1px #d63638;
            }

            .brm-tooltip {
                position: absolute;
                background: #1d2327;
                color: #fff;
                padding: 8px 12px;
                border-radius: 4px;
                font-size: 12px;
                z-index: 9999;
                pointer-events: none;
                box-shadow: 0 2px 8px rgba(0,0,0,0.2);
            }

            .brm-tooltip::after {
                content: '';
                position: absolute;
                top: 100%;
                left: 50%;
                margin-left: -5px;
                border: 5px solid transparent;
                border-top-color: #1d2327;
            }
        `)
        .appendTo('head');
});