<?php
/**
 * Compare Engine Class
 * Handles the core comparison logic
 */

namespace WP_Compare;

if (!defined('ABSPATH')) {
    exit;
}

class Compare_Engine {

    private $posts = array();
    private $slugs = array();
    private $common_fields = array();
    private $settings = array();

    public function __construct($posts, $slugs) {
        $this->posts = $posts;
        $this->slugs = $slugs;
        $this->settings = get_option('wp_compare_settings', array());
    }

    /**
     * Get all common fields across compared posts
     */
    public function get_common_fields() {
        if (!empty($this->common_fields)) {
            return $this->common_fields;
        }

        if (!function_exists('acf_get_field_groups')) {
            return array();
        }

        $all_fields = array();

        foreach ($this->posts as $post) {
            $field_groups = acf_get_field_groups(array(
                'post_id' => $post->ID,
            ));

            $post_fields = array();

            foreach ($field_groups as $group) {
                $fields = acf_get_fields($group['ID']);
                
                if ($fields) {
                    foreach ($fields as $field) {
                        $value = get_field($field['name'], $post->ID);
                        
                        if ($value !== null && $value !== '') {
                            $post_fields[$field['name']] = array(
                                'label' => $field['label'],
                                'name' => $field['name'],
                                'type' => $field['type'],
                                'parent' => isset($field['parent']) ? $field['parent'] : 0,
                                'group_label' => $this->get_parent_group_label($field, $group),
                            );
                        }
                    }
                }
            }

            $all_fields[] = $post_fields;
        }

        // Find common fields (fields that exist in all posts)
        if (empty($all_fields)) {
            return array();
        }

        $common = $all_fields[0];

        for ($i = 1; $i < count($all_fields); $i++) {
            foreach ($common as $key => $field) {
                if (!isset($all_fields[$i][$key])) {
                    unset($common[$key]);
                }
            }
        }

        // Always include basic post data
        $common['post_title'] = array(
            'label' => __('Title', 'wp-compare-engine'),
            'name' => 'post_title',
            'type' => 'text',
            'parent' => 0,
            'group_label' => '',
        );

        $common['post_excerpt'] = array(
            'label' => __('Excerpt', 'wp-compare-engine'),
            'name' => 'post_excerpt',
            'type' => 'textarea',
            'parent' => 0,
            'group_label' => '',
        );

        $common['featured_image'] = array(
            'label' => __('Featured Image', 'wp-compare-engine'),
            'name' => 'featured_image',
            'type' => 'image',
            'parent' => 0,
            'group_label' => '',
        );

        // Sort by group then by label
        uasort($common, array($this, 'sort_fields_by_group'));

        $this->common_fields = $common;
        return $this->common_fields;
    }

    /**
     * Get parent group label for a field
     */
    private function get_parent_group_label($field, $main_group) {
        if (empty($field['parent'])) {
            return '';
        }

        $parent = acf_get_field($field['parent']);
        
        if ($parent && $parent['type'] === 'group') {
            return $parent['label'];
        }

        return '';
    }

    /**
     * Sort fields by group
     */
    private function sort_fields_by_group($a, $b) {
        if ($a['group_label'] === $b['group_label']) {
            return strcmp($a['label'], $b['label']);
        }
        return strcmp($a['group_label'], $b['group_label']);
    }

    /**
     * Get field value for a specific post
     */
    public function get_field_value($post, $field_name, $field_type) {
        if ($field_name === 'post_title') {
            return get_the_title($post);
        }

        if ($field_name === 'post_excerpt') {
            return get_the_excerpt($post);
        }

        if ($field_name === 'featured_image') {
            return get_the_post_thumbnail($post->ID, 'medium');
        }

        if (!function_exists('get_field')) {
            return get_post_meta($post->ID, $field_name, true);
        }

        return get_field($field_name, $post->ID);
    }

    /**
     * Render field value based on type
     */
    public function render_field_value($value, $type, $post_id = null) {
        /**
         * Filter field value before rendering
         */
        $value = apply_filters('wp_compare_field_value', $value, $type, $post_id);

        switch ($type) {
            case 'true_false':
                return $value ? '<span class="compare-yes">✔ ' . __('Yes', 'wp-compare-engine') . '</span>' : '<span class="compare-no">✖ ' . __('No', 'wp-compare-engine') . '</span>';

            case 'image':
                if (is_array($value) && !empty($value['url'])) {
                    return '<a href="' . esc_url($value['url']) . '" class="compare-image-link" target="_blank" rel="noopener">' .
                           '<img src="' . esc_url($value['sizes']['thumbnail'] ?? $value['url']) . '" alt="' . esc_attr($value['alt'] ?? '') . '" loading="lazy">' .
                           '</a>';
                }
                return '—';

            case 'gallery':
                if (is_array($value) && !empty($value)) {
                    $output = '<div class="compare-gallery">';
                    foreach (array_slice($value, 0, 4) as $image) {
                        $output .= '<a href="' . esc_url($image['url']) . '" class="compare-gallery-item" target="_blank" rel="noopener">';
                        $output .= '<img src="' . esc_url($image['sizes']['thumbnail'] ?? $image['url']) . '" alt="' . esc_attr($image['alt'] ?? '') . '" loading="lazy">';
                        $output .= '</a>';
                    }
                    if (count($value) > 4) {
                        $output .= '<span class="compare-gallery-more">+' . (count($value) - 4) . '</span>';
                    }
                    $output .= '</div>';
                    return $output;
                }
                return '—';

            case 'file':
                if (is_array($value) && !empty($value['url'])) {
                    return '<a href="' . esc_url($value['url']) . '" class="compare-file-link" target="_blank" rel="noopener">' .
                           esc_html($value['filename']) .
                           '</a>';
                }
                return '—';

            case 'relationship':
            case 'post_object':
                if (is_array($value) && !empty($value)) {
                    $links = array();
                    foreach ($value as $post) {
                        $links[] = '<a href="' . esc_url(get_permalink($post)) . '">' . esc_html(get_the_title($post)) . '</a>';
                    }
                    return implode(', ', $links);
                } elseif (is_object($value)) {
                    return '<a href="' . esc_url(get_permalink($value)) . '">' . esc_html(get_the_title($value)) . '</a>';
                }
                return '—';

            case 'taxonomy':
            case 'select':
            case 'checkbox':
            case 'radio':
                if (is_array($value)) {
                    return esc_html(implode(', ', $value));
                }
                return $value !== '' && $value !== null ? esc_html($value) : '—';

            case 'date_picker':
            case 'time_picker':
            case 'date_time_picker':
                if (!empty($value)) {
                    return esc_html(date_i18n(get_option('date_format'), strtotime($value)));
                }
                return '—';

            case 'color_picker':
                if (!empty($value)) {
                    return '<span class="compare-color" style="background-color: ' . esc_attr($value) . ';" title="' . esc_attr($value) . '"></span> ' . esc_html($value);
                }
                return '—';

            case 'range':
            case 'number':
                return $value !== '' && $value !== null ? esc_html($value) : '—';

            case 'url':
            case 'link':
                if (is_array($value) && !empty($value['url'])) {
                    return '<a href="' . esc_url($value['url']) . '" target="_blank" rel="noopener">' .
                           esc_html($value['title'] ?? $value['url']) .
                           '</a>';
                } elseif (!empty($value)) {
                    return '<a href="' . esc_url($value) . '" target="_blank" rel="noopener">' . esc_html($value) . '</a>';
                }
                return '—';

            case 'google_map':
                if (is_array($value) && !empty($value['address'])) {
                    return esc_html($value['address']);
                }
                return '—';

            case 'user':
                if (is_array($value)) {
                    $users = array();
                    foreach ($value as $user_id) {
                        $user = get_userdata($user_id);
                        if ($user) {
                            $users[] = esc_html($user->display_name);
                        }
                    }
                    return implode(', ', $users);
                } elseif (!empty($value)) {
                    $user = get_userdata($value);
                    if ($user) {
                        return esc_html($user->display_name);
                    }
                }
                return '—';

            case 'group':
                if (is_array($value) && !empty($value)) {
                    $output = '<ul class="compare-group-fields">';
                    foreach ($value as $sub_key => $sub_value) {
                        $output .= '<li><strong>' . esc_html(ucwords(str_replace('_', ' ', $sub_key))) . ':</strong> ' . esc_html($sub_value) . '</li>';
                    }
                    $output .= '</ul>';
                    return $output;
                }
                return '—';

            case 'repeater':
                if (is_array($value) && !empty($value)) {
                    $output = '<div class="compare-repeater">';
                    
                    // Check if we have field info to get sub-field labels
                    $first_row = reset($value);
                    
                    if (is_array($first_row)) {
                        $output .= '<table class="compare-repeater-table">';
                        $output .= '<thead><tr>';
                        foreach (array_keys($first_row) as $sub_key) {
                            $output .= '<th>' . esc_html(ucwords(str_replace('_', ' ', $sub_key))) . '</th>';
                        }
                        $output .= '</tr></thead>';
                        $output .= '<tbody>';
                        
                        foreach ($value as $row) {
                            $output .= '<tr>';
                            foreach ($row as $cell) {
                                $output .= '<td>' . esc_html($cell) . '</td>';
                            }
                            $output .= '</tr>';
                        }
                        
                        $output .= '</tbody></table>';
                    } else {
                        $output .= '<ul>';
                        foreach ($value as $item) {
                            $output .= '<li>' . esc_html($item) . '</li>';
                        }
                        $output .= '</ul>';
                    }
                    
                    $output .= '</div>';
                    return $output;
                }
                return '—';

            case 'wysiwyg':
            case 'textarea':
            case 'text':
            default:
                if ($value !== '' && $value !== null) {
                    return wp_kses_post($value);
                }
                return '—';
        }
    }

    /**
     * Check if values are different across posts
     */
    public function values_are_different($values) {
        if (count($values) < 2) {
            return false;
        }

        $first = $values[0];
        
        foreach ($values as $value) {
            if ($this->normalize_value($value) !== $this->normalize_value($first)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Normalize value for comparison
     */
    private function normalize_value($value) {
        if (is_array($value)) {
            sort($value);
            return json_encode($value);
        }
        return (string) $value;
    }

    /**
     * Get taxonomy terms for a post
     */
    public function get_post_taxonomies($post_id) {
        $taxonomies = get_taxonomies(array('public' => true), 'objects');
        $result = array();

        foreach ($taxonomies as $taxonomy) {
            $terms = get_the_terms($post_id, $taxonomy->name);
            
            if ($terms && !is_wp_error($terms)) {
                $term_names = wp_list_pluck($terms, 'name');
                $result[$taxonomy->label] = implode(', ', $term_names);
            }
        }

        return $result;
    }

    /**
     * Get compared items for display
     */
    public function get_compare_items() {
        $items = array();

        foreach ($this->posts as $post) {
            $items[] = array(
                'ID' => $post->ID,
                'title' => get_the_title($post),
                'slug' => $post->post_name,
                'permalink' => get_permalink($post),
                'thumbnail' => get_the_post_thumbnail($post->ID, 'thumbnail'),
                'excerpt' => get_the_excerpt($post),
            );
        }

        return $items;
    }
}
