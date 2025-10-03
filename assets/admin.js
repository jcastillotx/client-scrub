/**
 * Brand Reputation Monitor Admin JavaScript
 */

jQuery(document).ready(function($) {
    
    // Handle client form submission
    $(document).on('submit', '#brm-client-form', function(e) {
        e.preventDefault();
        
        var formData = $(this).serialize();
        var isEdit = $('input[name="client_id"]').length > 0;
        var action = isEdit ? 'brm_update_client' : 'brm_save_client';
        
        // Show loading state
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
            console.log('AJAX Response:', response); // Debug log
            if (response.success) {
                // Show success message
                showNotice('Client saved successfully!', 'success');
                
                // Hide the form and navigate to clients list to ensure updated view
                $('#brm-form-section').hide();
                setTimeout(function() {
                    window.location.href = 'admin.php?page=brand-reputation-monitor';
                }, 800);
            } else {
                showNotice('Error: ' + response.data, 'error');
            }
        }).fail(function(xhr, status, error) {
            console.log('AJAX Error:', xhr, status, error); // Debug log
            showNotice('Network error. Please try again.', 'error');
        }).always(function() {
            $('#brm-client-form').removeClass('brm-loading');
        });
    });
    
    // Handle manual scan
    window.brmManualScan = function(clientId) {
        if (confirm('Run manual scan for this client? This may take a few minutes.')) {
            var $button = $('button[onclick*="brmManualScan"]');
            $button.prop('disabled', true).text('Scanning...');
            
            $.post(brm_ajax.ajax_url, {
                action: 'brm_manual_scan',
                client_id: clientId,
                nonce: brm_ajax.nonce
            }, function(response) {
                if (response.success) {
                    showNotice('Scan completed! Found ' + response.data.new_results + ' new results.', 'success');
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                } else {
                    showNotice('Error: ' + response.data, 'error');
                }
            }).fail(function() {
                showNotice('Network error. Please try again.', 'error');
            }).always(function() {
                $button.prop('disabled', false).text('Run Manual Scan');
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
                    // Optimistically remove the row from the table
                    if ($button && $button.length) {
                        $button.closest('tr').fadeOut(200, function() { $(this).remove(); });
                    }
                    // Navigate to ensure counters and other UI reflect changes
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
                // Show form section
                $('#brm-form-section').show();
                
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
                
                // Scroll to form
                $('html, body').animate({
                    scrollTop: $('#brm-form-section').offset().top - 100
                }, 500);
            } else {
                showNotice('Error loading client data: ' + response.data, 'error');
            }
        }).fail(function() {
            showNotice('Network error. Please try again.', 'error');
        });
    };
    
    // Handle cancel edit
    window.brmCancelEdit = function() {
        $('#brm-form-section').hide();
        $('#brm-client-form-container').empty();
    };
    
    // Handle show add client form
    window.brmShowAddClientForm = function() {
        $('#brm-form-section').show();
        $('#brm-client-form-container').html(getClientFormHtml());
        
        // Scroll to form
        $('html, body').animate({
            scrollTop: $('#brm-form-section').offset().top - 100
        }, 500);
    };
    
    // Handle view results
    window.brmViewResults = function(clientId) {
        window.location.href = 'admin.php?page=brm-results&client_id=' + clientId;
    };
    
    // Auto-refresh stats every 30 seconds
    if (typeof brm_ajax !== 'undefined' && $('.brm-stats-grid').length > 0) {
        setInterval(function() {
            refreshStats();
        }, 30000);
    }
    
    // Initialize tooltips
    $('[data-tooltip]').each(function() {
        var $this = $(this);
        var tooltip = $this.data('tooltip');
        
        $this.hover(
            function() {
                $('<div class="brm-tooltip">' + tooltip + '</div>')
                    .appendTo('body')
                    .fadeIn(200);
            },
            function() {
                $('.brm-tooltip').remove();
            }
        );
    });
    
    // Handle form validation
    $('#brm-client-form').on('blur', 'input[required], textarea[required]', function() {
        validateField($(this));
    });
    
    // Handle URL validation
    $('#client_website').on('blur', function() {
        var url = $(this).val();
        if (url && !isValidUrl(url)) {
            showFieldError($(this), 'Please enter a valid URL (e.g., https://example.com)');
        } else {
            clearFieldError($(this));
        }
    });
    
    // Handle email validation
    $('#client_email').on('blur', function() {
        var email = $(this).val();
        if (email && !isValidEmail(email)) {
            showFieldError($(this), 'Please enter a valid email address');
        } else {
            clearFieldError($(this));
        }
    });
    
    // Handle phone validation
    $('#client_phone').on('blur', function() {
        var phone = $(this).val();
        if (phone && !isValidPhone(phone)) {
            showFieldError($(this), 'Please enter a valid phone number');
        } else {
            clearFieldError($(this));
        }
    });
});

// Utility functions
function showNotice(message, type) {
    var noticeClass = type === 'success' ? 'notice-success' : 'notice-error';
    var $notice = $('<div class="notice ' + noticeClass + ' is-dismissible"><p>' + message + '</p></div>');
    
    $('.wrap h1').after($notice);
    
    // Auto-dismiss after 5 seconds
    setTimeout(function() {
        $notice.fadeOut(500, function() {
            $(this).remove();
        });
    }, 5000);
}

function refreshStats() {
    $.post(brm_ajax.ajax_url, {
        action: 'brm_get_stats',
        nonce: brm_ajax.nonce
    }, function(response) {
        if (response.success) {
            var s = response.data || {};
            $('.brm-stat-number[data-key="total_clients"]').text(s.total_clients ?? 0);
            $('.brm-stat-number[data-key="total_results"]').text(s.total_results ??  if (newValue !== undefined) {
                    $this.text(newValue);
                }
            });
        }
    });
}

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
    try {
        new URL(url);
        return true;
    } catch (e) {
        return false;
    }
}

function isValidEmail(email) {
    var re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

function isValidPhone(phone) {
    var re = /^[\+]?[1-9][\d]{0,15}$/;
    return re.test(phone.replace(/[\s\-\(\)]/g, ''));
}

// Add CSS for field errors
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