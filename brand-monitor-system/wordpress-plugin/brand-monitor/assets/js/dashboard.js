jQuery(document).ready(function($) {
    setInterval(function() {
        refreshMentions();
    }, 60000);

    function refreshMentions() {
        $.ajax({
            url: brandMonitorAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'brand_monitor_get_mentions',
                nonce: brandMonitorAjax.nonce
            },
            success: function(response) {
                if (response.success) {
                    updateMentionsList(response.data);
                }
            }
        });
    }

    function updateMentionsList(mentions) {
        var html = '';

        if (!mentions || mentions.length === 0) {
            html = '<tr><td colspan="5">No mentions found</td></tr>';
        } else {
            mentions.forEach(function(mention) {
                html += '<tr>';
                html += '<td>' + escapeHtml(mention.source_type) + '</td>';
                html += '<td>' + escapeHtml(mention.title || '') + '</td>';
                html += '<td><span class="sentiment-badge sentiment-' + (mention.sentiment || 'neutral') + '">' + (mention.sentiment || 'neutral') + '</span></td>';
                html += '<td>' + formatDate(mention.discovered_at) + '</td>';
                html += '<td><a href="' + mention.source_url + '" target="_blank">View</a></td>';
                html += '</tr>';
            });
        }

        $('#mentions-list').html(html);
    }

    function escapeHtml(text) {
        if (!text) {
            return '';
        }
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    function formatDate(dateString) {
        if (!dateString) {
            return '';
        }
        var date = new Date(dateString);
        return date.toLocaleString();
    }
});
