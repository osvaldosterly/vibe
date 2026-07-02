<?php
/**
 * Default Comparison Template
 * 
 * This template displays the comparison between two items.
 * Variables available: $wpc_comparison_data['item_1'], $wpc_comparison_data['item_2']
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpc_comparison_data;

if (empty($wpc_comparison_data) || !$wpc_comparison_data['is_comparison']) {
    return;
}

$item_1 = $wpc_comparison_data['item_1'];
$item_2 = $wpc_comparison_data['item_2'];

// Get ACF fields for both items
$fields_1 = function_exists('wpc_get_item_fields') ? wpc_get_item_fields($item_1->ID) : array();
$fields_2 = function_exists('wpc_get_item_fields') ? wpc_get_item_fields($item_2->ID) : array();

// Get all unique field keys from both items
$all_fields = array_unique(array_merge(array_keys($fields_1), array_keys($fields_2)));

// Get post type labels
$post_type_1 = get_post_type_object($item_1->post_type);
$post_type_2 = get_post_type_object($item_2->post_type);

?>

<div class="wpvc-comparison-container">
    
    <!-- Comparison Header -->
    <div class="wpvc-comparison-header">
        <h1 class="wpvc-comparison-title">
            <?php echo esc_html($item_1->post_title); ?> vs <?php echo esc_html($item_2->post_title); ?>
        </h1>
        
        <?php if ($item_1->post_type === $item_2->post_type): ?>
            <p class="wpvc-comparison-subtitle">
                Comparing two <?php echo esc_html(strtolower($post_type_1->labels->singular_name)); ?>
            </p>
        <?php else: ?>
            <p class="wpvc-comparison-subtitle">
                Cross-category comparison
            </p>
        <?php endif; ?>
    </div>

    <!-- Main Comparison Cards -->
    <div class="wpvc-items-overview">
        <div class="wpvc-item-card wpvc-item-1">
            <div class="wpvc-item-image">
                <?php if (has_post_thumbnail($item_1->ID)): ?>
                    <?php echo get_the_post_thumbnail($item_1->ID, 'large'); ?>
                <?php else: ?>
                    <div class="wpvc-placeholder-image">No Image</div>
                <?php endif; ?>
            </div>
            <h2 class="wpvc-item-name">
                <a href="<?php echo get_permalink($item_1->ID); ?>">
                    <?php echo esc_html($item_1->post_title); ?>
                </a>
            </h2>
            <div class="wpvc-item-summary">
                <?php echo esc_html(wp_trim_words($item_1->post_content, 20)); ?>
            </div>
            <a href="<?php echo get_permalink($item_1->ID); ?>" class="wpvc-button">View Details</a>
        </div>

        <div class="wpvc-vs-badge">VS</div>

        <div class="wpvc-item-card wpvc-item-2">
            <div class="wpvc-item-image">
                <?php if (has_post_thumbnail($item_2->ID)): ?>
                    <?php echo get_the_post_thumbnail($item_2->ID, 'large'); ?>
                <?php else: ?>
                    <div class="wpvc-placeholder-image">No Image</div>
                <?php endif; ?>
            </div>
            <h2 class="wpvc-item-name">
                <a href="<?php echo get_permalink($item_2->ID); ?>">
                    <?php echo esc_html($item_2->post_title); ?>
                </a>
            </h2>
            <div class="wpvc-item-summary">
                <?php echo esc_html(wp_trim_words($item_2->post_content, 20)); ?>
            </div>
            <a href="<?php echo get_permalink($item_2->ID); ?>" class="wpvc-button">View Details</a>
        </div>
    </div>

    <!-- Detailed Specifications Table -->
    <div class="wpvc-specifications">
        <h3 class="wpvc-section-title">Detailed Specifications</h3>
        
        <table class="wpvc-comparison-table">
            <thead>
                <tr>
                    <th class="wpvc-spec-label">Specification</th>
                    <th class="wpvc-spec-value"><?php echo esc_html($item_1->post_title); ?></th>
                    <th class="wpvc-spec-value"><?php echo esc_html($item_2->post_title); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($all_fields)): ?>
                    <tr>
                        <td colspan="3" class="wpvc-no-specs">
                            No custom specifications found. Add ACF fields to your posts to see comparisons.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($all_fields as $field_key): ?>
                        <?php
                        $value_1 = isset($fields_1[$field_key]) ? $fields_1[$field_key] : '';
                        $value_2 = isset($fields_2[$field_key]) ? $fields_2[$field_key] : '';
                        
                        // Skip empty values if both are empty
                        if (empty($value_1) && empty($value_2)) {
                            continue;
                        }
                        
                        // Format field label (convert snake_case to Title Case)
                        $field_label = ucwords(str_replace('_', ' ', $field_key));
                        ?>
                        <tr class="wpvc-spec-row <?php echo ($value_1 !== $value_2) ? 'wpvc-different' : 'wpvc-same'; ?>">
                            <td class="wpvc-spec-label"><?php echo esc_html($field_label); ?></td>
                            <td class="wpvc-spec-value wpvc-value-1">
                                <?php echo esc_html(is_array($value_1) ? implode(', ', $value_1) : $value_1); ?>
                            </td>
                            <td class="wpvc-spec-value wpvc-value-2">
                                <?php echo esc_html(is_array($value_2) ? implode(', ', $value_2) : $value_2); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Action Buttons -->
    <div class="wpvc-actions">
        <a href="<?php echo wpc_get_comparison_url($item_2->ID, $item_1->ID); ?>" class="wpvc-button-secondary">
            Swap Items
        </a>
        <a href="javascript:window.print();" class="wpvc-button-secondary">
            Print Comparison
        </a>
    </div>

    <!-- Share Section -->
    <div class="wpvc-share">
        <h4>Share this comparison</h4>
        <div class="wpvc-share-url">
            <input type="text" value="<?php echo esc_url(wpc_get_comparison_url($item_1->ID, $item_2->ID)); ?>" readonly onclick="this.select();">
            <button class="wpvc-copy-btn" onclick="navigator.clipboard.writeText(this.previousElementSibling.value); alert('URL copied!');">
                Copy URL
            </button>
        </div>
    </div>

</div>

<?php
// Reset global query if needed
wp_reset_postdata();
