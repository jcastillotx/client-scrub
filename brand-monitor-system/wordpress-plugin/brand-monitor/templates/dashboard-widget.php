<?php if (isset($mentions['data']) && !empty($mentions['data'])): ?>
    <ul>
        <?php foreach ($mentions['data'] as $mention): ?>
            <li>
                <strong><?php echo esc_html($mention['source_type']); ?>:</strong>
                <a href="<?php echo esc_url($mention['source_url']); ?>" target="_blank"><?php echo esc_html($mention['title']); ?></a>
            </li>
        <?php endforeach; ?>
    </ul>
<?php else: ?>
    <p><?php esc_html_e('No mentions available.', 'brand-monitor'); ?></p>
<?php endif; ?>
