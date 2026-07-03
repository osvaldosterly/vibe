<?php
/**
 * Compare selector component (floating bar)
 * Displayed on archive pages when items are selected
 */

if (!defined('ABSPATH')) {
    exit;
}

$plugin = $GLOBALS['wp_compare_engine'];
$current_list = $plugin->get_compare_list();
$max_items = (int) $plugin->get_setting('max_items', 3);
$min_items = (int) $plugin->get_setting('min_items', 2);
$enable_sticky = (bool) $plugin->get_setting('enable_sticky_bar', true);

if (empty($current_list)) {
    return;
}

// Get post data for selected items
$posts = $plugin->get_compare_posts($current_list);

?>
<div class="wp-compare-bar <?php echo $enable_sticky ? 'wp-compare-sticky' : ''; ?>" 
     data-min-items="<?php echo esc_attr($min_items); ?>"
     data-max-items="<?php echo esc_attr($max_items); ?>">
    
    <div class="wp-compare-bar-container">
        <div class="wp-compare-bar-items">
            <?php foreach ($posts as $post) : ?>
                <div class="wp-compare-bar-item" data-slug="<?php echo esc_attr($post->post_name); ?>">
                    <div class="wp-compare-bar-item-thumbnail">
                        <?php if (has_post_thumbnail($post->ID)) : ?>
                            <?php echo get_the_post_thumbnail($post->ID, 'thumbnail'); ?>
                        <?php else : ?>
                            <span class="dashicons dashicons-format-image"></span>
                        <?php endif; ?>
                    </div>
                    <span class="wp-compare-bar-item-title"><?php echo esc_html(get_the_title($post)); ?></span>
                    <button class="wp-compare-bar-remove" 
                            data-slug="<?php echo esc_attr($post->post_name); ?>"
                            aria-label="<?php printf(__('Remove %s', 'wp-compare-engine'), esc_attr(get_the_title($post))); ?>">
                        <span class="dashicons dashicons-no-alt"></span>
                    </button>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="wp-compare-bar-actions">
            <span class="wp-compare-count">
                <?php 
                printf(
                    _n('%d item selected', '%d items selected', count($posts), 'wp-compare-engine'),
                    count($posts)
                ); 
                ?>
            </span>
            
            <?php if (count($posts) >= $min_items) : ?>
                <a href="<?php echo esc_url(home_url($plugin->get_setting('compare_slug', 'compare') . '/' . implode('-vs-', $current_list))); ?>" 
                   class="wp-compare-btn wp-compare-btn-primary">
                    <?php esc_html_e('Compare Now', 'wp-compare-engine'); ?>
                </a>
            <?php endif; ?>
            
            <button class="wp-compare-btn wp-compare-btn-secondary wp-compare-clear-all">
                <?php esc_html_e('Clear All', 'wp-compare-engine'); ?>
            </button>
        </div>
    </div>
</div>

<!-- Search Modal -->
<div class="wp-compare-modal" id="wp-compare-search-modal" aria-hidden="true" role="dialog" aria-modal="true">
    <div class="wp-compare-modal-overlay"></div>
    <div class="wp-compare-modal-content">
        <div class="wp-compare-modal-header">
            <h2><?php esc_html_e('Search Items to Compare', 'wp-compare-engine'); ?></h2>
            <button class="wp-compare-modal-close" aria-label="<?php esc_attr_e('Close search', 'wp-compare-engine'); ?>">
                <span class="dashicons dashicons-no"></span>
            </button>
        </div>
        
        <div class="wp-compare-search-box">
            <input type="search" 
                   class="wp-compare-search-input" 
                   placeholder="<?php esc_attr_e('Type to search...', 'wp-compare-engine'); ?>"
                   aria-label="<?php esc_attr_e('Search items', 'wp-compare-engine'); ?>"
                   autocomplete="off">
            <span class="wp-compare-search-spinner dashicons dashicons-admin-spin" style="display:none;"></span>
        </div>
        
        <div class="wp-compare-search-results" role="listbox">
            <p class="wp-compare-search-hint">
                <?php esc_html_e('Start typing to search for items...', 'wp-compare-engine'); ?>
            </p>
        </div>
        
        <div class="wp-compare-selected-info">
            <?php 
            printf(
                __('Selected: %d of %d maximum', 'wp-compare-engine'),
                count($posts),
                $max_items
            ); 
            ?>
        </div>
    </div>
</div>
