<?php
/**
 * Main comparison template
 * 
 * @var array $posts Array of WP_Post objects
 * @var array $slugs Array of post slugs
 */

namespace WP_Compare;

if (!defined('ABSPATH')) {
    exit;
}

// Don't use namespace in templates that get included
// Reset to global scope for template variables
$posts = isset($posts) ? $posts : ($GLOBALS['wp_compare_posts'] ?? array());
$slugs = isset($slugs) ? $slugs : ($GLOBALS['wp_compare_slugs'] ?? array());

if (empty($posts)) {
    return;
}

$engine = new Compare_Engine($posts, $slugs);
$common_fields = $engine->get_common_fields();
$items = $engine->get_compare_items();
$enable_highlighting = (bool) get_option('wp_compare_settings', array())['enable_difference_highlighting'] ?? true;

?>
<div class="wp-compare-container" itemscope itemtype="https://schema.org/Product">
    
    <?php do_action('wp_compare_before_header', $posts, $slugs); ?>
    
    <!-- Comparison Header -->
    <div class="wp-compare-header">
        <?php include WP_COMPARE_PLUGIN_DIR . 'templates/components/header.php'; ?>
    </div>
    
    <?php do_action('wp_compare_after_header', $posts, $slugs); ?>
    
    <!-- Comparison Table -->
    <div class="wp-compare-table-wrapper">
        <table class="wp-compare-table" cellpadding="0" cellspacing="0">
            <?php include WP_COMPARE_PLUGIN_DIR . 'templates/components/table-header.php'; ?>
            <tbody class="wp-compare-tbody">
                <?php
                $current_group = '';
                
                foreach ($common_fields as $field_name => $field_info) :
                    // Check for group change
                    $field_group = $field_info['group_label'] ?? '';
                    
                    if (!empty($field_group) && $field_group !== $current_group) :
                        $current_group = $field_group;
                        ?>
                        <tr class="wp-compare-group-heading">
                            <td colspan="<?php echo count($posts) + 1; ?>">
                                <h3><?php echo esc_html($current_group); ?></h3>
                            </td>
                        </tr>
                        <?php
                    endif;
                    
                    // Get values for this field
                    $values = array();
                    foreach ($posts as $post) {
                        $values[] = $engine->get_field_value($post, $field_name, $field_info['type']);
                    }
                    
                    // Determine if values are different
                    $is_different = $enable_highlighting && $engine->values_are_different($values);
                    $row_class = $is_different ? 'compare-different' : 'compare-same';
                    ?>
                    
                    <tr class="wp-compare-row <?php echo esc_attr($row_class); ?>" data-field="<?php echo esc_attr($field_name); ?>">
                        <th class="wp-compare-feature-label">
                            <?php echo esc_html($field_info['label']); ?>
                        </th>
                        
                        <?php foreach ($values as $index => $value) : ?>
                            <td class="wp-compare-feature-value">
                                <?php 
                                /**
                                 * Filter individual cell content
                                 */
                                $cell_content = apply_filters(
                                    'wp_compare_cell_content',
                                    $engine->render_field_value($value, $field_info['type'], $posts[$index]->ID),
                                    $field_name,
                                    $value,
                                    $posts[$index],
                                    $field_info
                                );
                                echo $cell_content;
                                ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <?php do_action('wp_compare_after_table', $posts, $slugs); ?>
    
</div>

<?php
// Add schema markup
do_action('wp_compare_schema', $posts, $slugs);
