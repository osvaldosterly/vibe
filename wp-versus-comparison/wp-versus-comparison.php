<?php
/**
 * Plugin Name: WP Versus Comparison
 * Description: A lightweight comparison plugin similar to Versus.com. Uses default posts and Advanced Custom Fields (ACF) for product data. Supports "item-1-vs-item-2" URL structure.
 * Version: 1.0.0
 * Author: Your Name
 * Text Domain: wp-versus-comparison
 * Requires Plugins: advanced-custom-fields
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

define('WPVC_VERSION', '1.0.0');
define('WPVC_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WPVC_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Main Plugin Class
 */
class WP_Versus_Comparison {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->init_hooks();
    }

    private function init_hooks() {
        add_action('init', array($this, 'register_rewrite_rules'));
        add_filter('query_vars', array($this, 'add_query_vars'));
        add_action('template_redirect', array($this, 'handle_comparison_template'));
        add_filter('single_template', array($this, 'load_comparison_template'), 10, 1);
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
    }

    /**
     * Register custom rewrite rules for "item-1-vs-item-2" URLs
     */
    public function register_rewrite_rules() {
        // Matches: any-post-slug-vs-another-post-slug
        add_rewrite_rule(
            '^([^/]+)/vs/([^/]+)/?$',
            'index.php?wpc_item_1=$matches[1]&wpc_item_2=$matches[2]',
            'top'
        );

        // Flush rewrite rules on plugin activation (handled separately)
    }

    /**
     * Add custom query variables
     */
    public function add_query_vars($vars) {
        $vars[] = 'wpc_item_1';
        $vars[] = 'wpc_item_2';
        return $vars;
    }

    /**
     * Handle comparison template redirect
     */
    public function handle_comparison_template() {
        $item_1_slug = get_query_var('wpc_item_1');
        $item_2_slug = get_query_var('wpc_item_2');

        if (!$item_1_slug || !$item_2_slug) {
            return;
        }

        // Get posts by slug
        $item_1 = get_page_by_path($item_1_slug, OBJECT, get_post_types());
        $item_2 = get_page_by_path($item_2_slug, OBJECT, get_post_types());

        if (!$item_1 || !$item_2) {
            // One or both items not found, redirect to 404
            global $wp_query;
            $wp_query->set_404();
            status_header(404);
            include(get_query_template('404'));
            exit;
        }

        // Store comparison data in global context for template use
        global $wpc_comparison_data;
        $wpc_comparison_data = array(
            'item_1' => $item_1,
            'item_2' => $item_2,
            'is_comparison' => true
        );

        // Load comparison template
        $template = locate_template(array('single-wpvc-comparison.php', 'wpvc-comparison.php'));
        
        if (!$template) {
            // Fallback to plugin's default template
            $template = WPVC_PLUGIN_DIR . 'templates/comparison-default.php';
        }

        include($template);
        exit;
    }

    /**
     * Enqueue frontend assets
     */
    public function enqueue_assets() {
        wp_enqueue_style(
            'wpvc-style',
            WPVC_PLUGIN_URL . 'assets/css/wpvc-style.css',
            array(),
            WPVC_VERSION
        );

        wp_enqueue_script(
            'wpvc-script',
            WPVC_PLUGIN_URL . 'assets/js/wpvc-script.js',
            array('jquery'),
            WPVC_VERSION,
            true
        );

        wp_localize_script('wpvc-script', 'wpvc_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wpvc_nonce')
        ));
    }

    /**
     * Activate plugin: flush rewrite rules
     */
    public static function activate() {
        $instance = self::get_instance();
        $instance->register_rewrite_rules();
        flush_rewrite_rules();
    }

    /**
     * Deactivate plugin: flush rewrite rules
     */
    public static function deactivate() {
        flush_rewrite_rules();
    }
}

// Initialize plugin
$wp_vc_plugin = WP_Versus_Comparison::get_instance();

// Activation/Deactivation hooks
register_activation_hook(__FILE__, array('WP_Versus_Comparison', 'activate'));
register_deactivation_hook(__FILE__, array('WP_Versus_Comparison', 'deactivate'));

/**
 * Helper function to generate comparison URL
 * Usage: wpc_get_comparison_url($post_id_1, $post_id_2)
 */
function wpc_get_comparison_url($post_id_1, $post_id_2) {
    $post_1 = get_post($post_id_1);
    $post_2 = get_post($post_id_2);

    if (!$post_1 || !$post_2) {
        return '';
    }

    $slug_1 = $post_1->post_name;
    $slug_2 = $post_2->post_name;

    return home_url("/{$slug_1}/vs/{$slug_2}");
}

/**
 * Helper function to get all ACF fields for a post in a structured way
 */
function wpc_get_item_fields($post_id) {
    if (!function_exists('get_fields')) {
        return array();
    }

    $fields = get_fields($post_id);
    
    // Filter out system fields and keep only ACF fields
    $acf_fields = array();
    if (is_array($fields)) {
        foreach ($fields as $key => $value) {
            // Skip WordPress default fields
            if (in_array($key, array('post_title', 'post_content', 'post_excerpt', 'post_date'))) {
                continue;
            }
            $acf_fields[$key] = $value;
        }
    }

    return $acf_fields;
}
