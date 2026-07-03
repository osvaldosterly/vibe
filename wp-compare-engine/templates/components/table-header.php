<?php
/**
 * Comparison table header component
 * Shows item thumbnails, titles, and remove buttons
 */

if (!defined('ABSPATH')) {
    exit;
}

$items = $engine->get_compare_items();
$max_items = (int) ($GLOBALS['wp_compare_engine']->get_setting('max_items', 3));

?>
<thead class="wp-compare-thead">
    <tr>
        <th class="wp-compare-feature-column">
            <?php esc_html_e('Features', 'wp-compare-engine'); ?>
        </th>
        <?php foreach ($items as $index => $item) : ?>
            <th class="wp-compare-item-header">
                <div class="wp-compare-item-thumbnail">
                    <?php if (!empty($item['thumbnail'])) : ?>
                        <a href="<?php echo esc_url($item['permalink']); ?>">
                            <?php echo $item['thumbnail']; ?>
                        </a>
                    <?php else : ?>
                        <div class="wp-compare-no-image">
                            <span class="dashicons dashicons-format-image"></span>
                        </div>
                    <?php endif; ?>
                    
                    <button 
                        class="wp-compare-remove-btn" 
                        data-slug="<?php echo esc_attr($item['slug']); ?>"
                        aria-label="<?php printf(__('Remove %s from comparison', 'wp-compare-engine'), esc_attr($item['title'])); ?>"
                    >
                        <span class="dashicons dashicons-no-alt"></span>
                    </button>
                </div>
                
                <h2 class="wp-compare-item-title">
                    <a href="<?php echo esc_url($item['permalink']); ?>">
                        <?php echo esc_html($item['title']); ?>
                    </a>
                </h2>
                
                <?php if (!empty($item['excerpt'])) : ?>
                    <div class="wp-compare-item-excerpt">
                        <?php echo wp_kses_post(wp_trim_words($item['excerpt'], 15)); ?>
                    </div>
                <?php endif; ?>
                
                <a href="<?php echo esc_url($item['permalink']); ?>" class="wp-compare-view-btn">
                    <?php esc_html_e('View Details', 'wp-compare-engine'); ?>
                </a>
            </th>
        <?php endforeach; ?>
        
        <?php
        // Add empty columns if less than max items for consistent layout
        $empty_cols = $max_items - count($items);
        for ($i = 0; $i < $empty_cols; $i++) :
        ?>
            <th class="wp-compare-item-header wp-compare-empty">
                <div class="wp-compare-add-placeholder">
                    <span class="dashicons dashicons-plus-alt"></span>
                    <p><?php esc_html_e('Add another item', 'wp-compare-engine'); ?></p>
                </div>
            </th>
        <?php endfor; ?>
    </tr>
</thead>
