<?php
/**
 * Schema Markup Handler
 * Adds structured data for SEO
 */

namespace WP_Compare;

if (!defined('ABSPATH')) {
    exit;
}

class Schema_Handler {

    /**
     * Add schema markup action
     */
    public function __construct() {
        add_action('wp_compare_schema', array($this, 'output_schema'), 10, 2);
        add_filter('rank_math/json_ld', array($this, 'rank_math_schema'), 10, 2);
        add_filter('wpseo_json_ld', array($this, 'yoast_schema'));
    }

    /**
     * Output schema markup on compare pages
     */
    public function output_schema($posts, $slugs) {
        if (!is_array($posts) || empty($posts)) {
            return;
        }

        $schema = $this->build_comparison_schema($posts);

        if ($schema) {
            echo "\n<!-- WP Compare Engine Schema -->\n";
            echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . '</script>' . "\n";
        }
    }

    /**
     * Build comparison schema
     */
    private function build_comparison_schema($posts) {
        if (count($posts) < 2) {
            return array();
        }

        $items = array();

        foreach ($posts as $post) {
            $item = array(
                '@type' => 'Product',
                'name' => get_the_title($post),
                'description' => get_the_excerpt($post),
                'url' => get_permalink($post),
            );

            // Add image
            if (has_post_thumbnail($post->ID)) {
                $image_id = get_post_thumbnail_id($post->ID);
                $image_data = wp_get_attachment_image_src($image_id, 'full');
                if ($image_data) {
                    $item['image'] = $image_data[0];
                }
            }

            // Try to get price from ACF if available
            $price = get_field('price', $post->ID);
            if ($price) {
                $item['offers'] = array(
                    '@type' => 'Offer',
                    'price' => floatval($price),
                    'priceCurrency' => get_field('currency', $post->ID) ?: 'USD',
                    'availability' => 'https://schema.org/InStock',
                );
            }

            // Add brand if available
            $brand = get_field('brand', $post->ID);
            if ($brand) {
                $item['brand'] = array(
                    '@type' => 'Brand',
                    'name' => $brand,
                );
            }

            $items[] = $item;
        }

        return array(
            '@context' => 'https://schema.org',
            '@type' => 'ItemList',
            'name' => sprintf(
                __('Comparison: %s', 'wp-compare-engine'),
                implode(' vs ', array_map('get_the_title', $posts))
            ),
            'numberOfItems' => count($posts),
            'itemListElement' => array_values($items),
        );
    }

    /**
     * Add schema for Rank Math SEO
     */
    public function rank_math_schema($graph, $context) {
        global $wp_query;

        if (!isset($wp_query->query_vars['wp_compare_slugs'])) {
            return $graph;
        }

        $posts = $GLOBALS['wp_compare_posts'] ?? array();

        if (!empty($posts)) {
            $comparison_schema = $this->build_comparison_schema($posts);
            
            if (!empty($comparison_schema)) {
                $graph[] = $comparison_schema;
            }
        }

        return $graph;
    }

    /**
     * Add schema for Yoast SEO
     */
    public function yoast_schema($data) {
        global $wp_query;

        if (!isset($wp_query->query_vars['wp_compare_slugs'])) {
            return $data;
        }

        $posts = $GLOBALS['wp_compare_posts'] ?? array();

        if (!empty($posts)) {
            $comparison_schema = $this->build_comparison_schema($posts);
            
            if (!empty($comparison_schema)) {
                if (isset($data['@graph'])) {
                    $data['@graph'][] = $comparison_schema;
                } else {
                    $data = array(
                        '@graph' => array($comparison_schema),
                    );
                }
            }
        }

        return $data;
    }

    /**
     * Get meta description for compare pages
     */
    public function get_meta_description($posts) {
        if (!is_array($posts) || empty($posts)) {
            return '';
        }

        $titles = array_map('get_the_title', $posts);
        
        return sprintf(
            __('Compare %s side by side. Detailed comparison of features, specifications, prices, and more to help you make the best decision.', 'wp-compare-engine'),
            implode(' vs ', $titles)
        );
    }
}
