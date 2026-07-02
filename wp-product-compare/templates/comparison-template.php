<?php
/**
 * Template: Comparison Page
 * 
 * This template is loaded when a URL like /item-1+vs+item-2 is accessed.
 * It expects global $wp_compare_data containing 'item_1' and 'item_2' WP_Post objects.
 * 
 * It automatically loops through ACF fields to generate the comparison table.
 */

if (!defined('ABSPATH')) {
    exit;
}

// Ensure data exists
if (empty($wp_compare_data) || !isset($wp_compare_data['item_1']) || !isset($wp_compare_data['item_2'])) {
    return;
}

$item_1 = $wp_compare_data['item_1'];
$item_2 = $wp_compare_data['item_2'];

// Enqueue styles for this template
wp_enqueue_style('wpc-style');
wp_enqueue_script('wpc-script');

// Get all ACF field groups associated with these post types to determine which fields to show
// Alternatively, we can just fetch all ACF values for both posts and merge keys
$acf_fields_1 = function_exists('get_fields') ? get_fields($item_1->ID) : array();
$acf_fields_2 = function_exists('get_fields') ? get_fields($item_2->ID) : array();

// Merge keys to ensure we show fields even if one item is missing them
$all_field_keys = array_unique(array_merge(
    is_array($acf_fields_1) ? array_keys($acf_fields_1) : [],
    is_array($acf_fields_2) ? array_keys($acf_fields_2) : []
));

// Filter out internal ACF keys if necessary (usually starts with underscore)
$all_field_keys = array_filter($all_field_keys, function($key) {
    return strpos($key, '_') !== 0;
});

?>

<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo esc_html($item_1->post_title); ?> vs <?php echo esc_html($item_2->post_title); ?> - Comparison</title>
    <?php wp_head(); ?>
    <style>
        /* Inline critical CSS for layout stability */
        .wpc-container { max-width: 1200px; margin: 40px auto; padding: 0 20px; font-family: sans-serif; }
        .wpc-header { text-align: center; margin-bottom: 40px; }
        .wpc-vs-badge { background: #ff4757; color: #fff; padding: 5px 15px; border-radius: 20px; font-weight: bold; margin: 0 10px; }
        
        .wpc-cards { display: flex; gap: 20px; justify-content: center; margin-bottom: 40px; flex-wrap: wrap; }
        .wpc-card { flex: 1; min-width: 280px; max-width: 450px; text-align: center; border: 1px solid #eee; border-radius: 8px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .wpc-card img { max-width: 100%; height: auto; max-height: 200px; object-fit: contain; margin-bottom: 15px; }
        .wpc-card h2 { font-size: 1.5rem; margin: 10px 0; }
        .wpc-btn-compare { display: inline-block; margin-top: 10px; text-decoration: none; color: #666; font-size: 0.9rem; }
        
        .wpc-table-wrapper { overflow-x: auto; }
        .wpc-table { width: 100%; border-collapse: collapse; min-width: 600px; }
        .wpc-table th, .wpc-table td { padding: 15px; border-bottom: 1px solid #eee; text-align: left; }
        .wpc-table th { width: 30%; background: #f9f9f9; font-weight: 600; color: #333; }
        .wpc-table td { width: 35%; }
        .wpc-table tr:nth-child(even) { background-color: #fcfcfc; }
        .wpc-table tr:hover { background-color: #f1f1f1; }
        
        @media (max-width: 768px) {
            .wpc-cards { flex-direction: column; align-items: center; }
            .wpc-card { width: 100%; max-width: 100%; }
        }
    </style>
</head>
<body <?php body_class(); ?>>

<div class="wpc-container">
    
    <div class="wpc-header">
        <h1>Comparison</h1>
        <p><?php echo esc_html($item_1->post_title); ?> <span class="wpc-vs-badge">VS</span> <?php echo esc_html($item_2->post_title); ?></p>
    </div>

    <!-- Top Cards Summary -->
    <div class="wpc-cards">
        <div class="wpc-card">
            <?php 
            // Try to get featured image or first ACF image field
            $thumb_1 = get_the_post_thumbnail_url($item_1->ID, 'medium');
            if (!$thumb_1 && !empty($acf_fields_1)) {
                foreach($acf_fields_1 as $val) {
                    if (is_array($val) && isset($val['url'])) { $thumb_1 = $val['url']; break; }
                }
            }
            if ($thumb_1): ?>
                <img src="<?php echo esc_url($thumb_1); ?>" alt="<?php echo esc_attr($item_1->post_title); ?>">
            <?php endif; ?>
            <h2><?php echo esc_html($item_1->post_title); ?></h2>
            <div class="wpc-summary"><?php echo wp_trim_words($item_1->post_content, 15); ?></div>
            <a href="<?php echo get_permalink($item_1->ID); ?>" class="wpc-btn-compare">View Details &rarr;</a>
        </div>

        <div class="wpc-card">
            <?php 
            $thumb_2 = get_the_post_thumbnail_url($item_2->ID, 'medium');
            if (!$thumb_2 && !empty($acf_fields_2)) {
                foreach($acf_fields_2 as $val) {
                    if (is_array($val) && isset($val['url'])) { $thumb_2 = $val['url']; break; }
                }
            }
            if ($thumb_2): ?>
                <img src="<?php echo esc_url($thumb_2); ?>" alt="<?php echo esc_attr($item_2->post_title); ?>">
            <?php endif; ?>
            <h2><?php echo esc_html($item_2->post_title); ?></h2>
            <div class="wpc-summary"><?php echo wp_trim_words($item_2->post_content, 15); ?></div>
            <a href="<?php echo get_permalink($item_2->ID); ?>" class="wpc-btn-compare">View Details &rarr;</a>
        </div>
    </div>

    <!-- Specification Table -->
    <div class="wpc-table-wrapper">
        <table class="wpc-table">
            <thead>
                <tr>
                    <th>Specification</th>
                    <th><?php echo esc_html($item_1->post_title); ?></th>
                    <th><?php echo esc_html($item_2->post_title); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($all_field_keys)) : ?>
                    <tr>
                        <td colspan="3" style="text-align:center;">No specifications found. Please add ACF fields to your posts.</td>
                    </tr>
                <?php else : ?>
                    <?php foreach ($all_field_keys as $field_key) : 
                        // Get formatted values using ACF functions if available
                        $val_1 = '';
                        $val_2 = '';
                        
                        if (function_exists('get_field')) {
                            $val_1 = get_field($field_key, $item_1->ID);
                            $val_2 = get_field($field_key, $item_2->ID);
                        } else {
                            // Fallback to raw meta if ACF pro/functions not loaded
                            $val_1 = get_post_meta($item_1->ID, $field_key, true);
                            $val_2 = get_post_meta($item_2->ID, $field_key, true);
                        }

                        // Format arrays (e.g., select boxes, repeaters simple output)
                        if (is_array($val_1)) $val_1 = implode(', ', $val_1);
                        if (is_array($val_2)) $val_2 = implode(', ', $val_2);
                        
                        // Skip if both are empty
                        if (empty($val_1) && empty($val_2)) continue;
                    ?>
                        <tr>
                            <td><?php echo esc_html(ucwords(str_replace('_', ' ', $field_key))); ?></td>
                            <td><?php echo esc_html($val_1 ?: '<span style="color:#ccc">-</span>'); ?></td>
                            <td><?php echo esc_html($val_2 ?: '<span style="color:#ccc">-</span>'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div style="text-align:center; margin-top:40px;">
        <a href="<?php echo home_url('/'); ?>" style="text-decoration:none; color:#555;">&larr; Back to Home</a>
    </div>

</div>

<?php wp_footer(); ?>
</body>
</html>
