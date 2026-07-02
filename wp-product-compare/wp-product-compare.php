<?php
/**
 * Plugin Name: WP Product Compare (Versus Style)
 * Description: A lightweight comparison plugin using Custom Post Types and ACF. URL Format: /slug1+vs+slug2
 * Version: 1.1
 * Author: Your Name
 * Text Domain: wp-product-compare
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_Product_Compare {

    // CHANGE THIS to match your Custom Post Type slug (e.g., 'phone', 'laptop', 'product')
    private $post_type = 'product'; 

    public function __construct() {
        // Register Rewrite Rules
        add_action('init', array($this, 'add_rewrite_rules'));
        
        // Handle Query Vars
        add_filter('query_vars', array($this, 'register_query_vars'));
        
        // Template Redirect for Comparison Page
        add_action('template_redirect', array($this, 'handle_comparison_template'));
        
        // Enqueue Assets
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
    }

    /**
     * Add custom rewrite rule to catch slug1+vs+slug2
     * Matches: ^([^/]+)\+vs\+([^/]+)/?$
     * Example: iphone-15+vs+samsung-s24
     */
    public function add_rewrite_rules() {
        add_rewrite_rule(
            '^([^/]+)\+vs\+([^/]+)/?$',
            'index.php?wp_compare=1&compare_item_1=$matches[1]&compare_item_2=$matches[2]',
            'top'
        );
        
        // Flush rules once on activation (handled externally usually, but good for dev)
        // flush_rewrite_rules(); 
    }

    /**
     * Register custom query variables
     */
    public function register_query_vars($vars) {
        $vars[] = 'wp_compare';
        $vars[] = 'compare_item_1';
        $vars[] = 'compare_item_2';
        return $vars;
    }

    /**
     * Intercept request and load comparison template
     */
    public function handle_comparison_template() {
        $is_compare = get_query_var('wp_compare');
        $slug_1 = get_query_var('compare_item_1');
        $slug_2 = get_query_var('compare_item_2');

        if ($is_compare && $slug_1 && $slug_2) {
            
            // Resolve slugs to posts based on the CPT
            $post_1 = $this->get_post_by_slug($slug_1);
            $post_2 = $this->get_post_by_slug($slug_2);

            if ($post_1 && $post_2) {
                // Load a specific template file or render directly
                // We look for 'comparison-template.php' in the theme root first, then fallback to plugin
                $template = locate_template('comparison-template.php');
                
                if (!$template) {
                    $template = plugin_dir_path(__FILE__) . 'templates/comparison-template.php';
                }

                // Pass data to template via global
                global $wp_compare_data;
                $wp_compare_data = array(
                    'item_1' => $post_1,
                    'item_2' => $post_2
                );

                include $template;
                exit; // Important: Stop further WordPress execution
            } else {
                // If one or both items not found, trigger 404
                global $wp_query;
                $wp_query->set_404();
                status_header(404);
                include get_query_template('404');
                exit;
            }
        }
    }

    /**
     * Helper to get post by slug from the specific CPT
     */
    private function get_post_by_slug($slug) {
        $args = array(
            'name' => $slug,
            'post_type' => $this->post_type,
            'post_status' => 'publish',
            'numberposts' => 1
        );
        $posts = get_posts($args);
        
        if ($posts) {
            return $posts[0];
        }
        return null;
    }

    /**
     * Enqueue CSS/JS for the comparison view
     */
    public function enqueue_assets() {
        wp_register_style('wpc-style', plugin_dir_url(__FILE__) . 'assets/css/compare.css', array(), '1.1');
        wp_register_script('wpc-script', plugin_dir_url(__FILE__) . 'assets/js/compare.js', array('jquery'), '1.1', true);
    }

    /**
     * Helper function to generate the correct comparison URL
     * Usage: echo wpc_get_compare_url($post_id_1, $post_id_2);
     */
    public static function get_compare_url($id1, $id2) {
        $post1 = get_post($id1);
        $post2 = get_post($id2);

        if (!$post1 || !$post2) return '';

        // Construct: slug1+vs+slug2
        $url = home_url('/' . $post1->post_name . '+vs+' . $post2->post_name);
        
        return $url;
    }
}

// Initialize Plugin
global $wp_product_compare_instance;
$wp_product_compare_instance = new WP_Product_Compare();

/**
 * Template Tag for easy URL generation in themes
 */
function wpc_get_compare_url($id1, $id2) {
    return WP_Product_Compare::get_compare_url($id1, $id2);
}
