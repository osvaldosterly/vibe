<?php
/**
 * Query Handler Class
 * Handles custom query vars and 404 handling for compare pages
 */

namespace WP_Compare;

if (!defined('ABSPATH')) {
    exit;
}

class Query_Handler {

    private $plugin;

    public function __construct($plugin) {
        $this->plugin = $plugin;
        
        add_filter('query_vars', array($this, 'add_query_vars'));
        add_action('template_redirect', array($this, 'handle_compare_request'), 1);
        add_filter('the_title', array($this, 'filter_compare_page_title'), 10, 2);
        add_filter('document_title_parts', array($this, 'filter_document_title'));
    }

    /**
     * Add custom query vars
     */
    public function add_query_vars($vars) {
        $vars[] = 'wp_compare_slugs';
        return $vars;
    }

    /**
     * Handle compare page requests
     */
    public function handle_compare_request() {
        global $wp_query;

        if (!isset($wp_query->query_vars['wp_compare_slugs'])) {
            return;
        }

        $slugs_string = $wp_query->query_vars['wp_compare_slugs'];
        
        if (empty($slugs_string)) {
            $this->show_404();
            return;
        }

        $slugs = $this->plugin->parse_compare_slugs($slugs_string);
        $min_items = (int) $this->plugin->get_setting('min_items', 2);
        $max_items = (int) $this->plugin->get_setting('max_items', 3);

        // Validate item count
        if (count($slugs) < $min_items || count($slugs) > $max_items) {
            $this->show_404();
            return;
        }

        // Check for duplicates
        if (count($slugs) !== count(array_unique($slugs))) {
            $this->show_404();
            return;
        }

        $posts = $this->plugin->get_compare_posts($slugs);

        // Validate all posts found
        if (count($posts) !== count($slugs)) {
            $this->show_404();
            return;
        }

        // Store posts in global for template access
        $GLOBALS['wp_compare_posts'] = $posts;
        $GLOBALS['wp_compare_slugs'] = $slugs;

        // Set up proper post data
        global $post;
        $post = $posts[0];
        setup_postdata($post);

        /**
         * Action before compare page is rendered
         */
        do_action('wp_compare_before_request', $posts, $slugs);
    }

    /**
     * Show 404 page
     */
    private function show_404() {
        global $wp_query;
        $wp_query->set_404();
        status_header(404);
        nocache_headers();
    }

    /**
     * Filter page title for compare pages
     */
    public function filter_compare_page_title($title, $id = null) {
        global $wp_query;

        if (!isset($wp_query->query_vars['wp_compare_slugs'])) {
            return $title;
        }

        if (in_the_loop() && is_main_query()) {
            $slugs = $GLOBALS['wp_compare_slugs'] ?? array();
            
            if (!empty($slugs)) {
                $titles = array();
                
                foreach ($slugs as $slug) {
                    $post = get_page_by_path($slug, OBJECT, $this->plugin->get_setting('allowed_post_types', array('post', 'page')));
                    if ($post) {
                        $titles[] = get_the_title($post);
                    }
                }

                if (!empty($titles)) {
                    return implode(' vs ', $titles);
                }
            }
        }

        return $title;
    }

    /**
     * Filter document title
     */
    public function filter_document_title($title) {
        global $wp_query;

        if (!isset($wp_query->query_vars['wp_compare_slugs'])) {
            return $title;
        }

        $slugs = $GLOBALS['wp_compare_slugs'] ?? array();
        
        if (!empty($slugs)) {
            $titles = array();
            
            foreach ($slugs as $slug) {
                $post = get_page_by_path($slug, OBJECT, $this->plugin->get_setting('allowed_post_types', array('post', 'page')));
                if ($post) {
                    $titles[] = get_the_title($post);
                }
            }

            if (!empty($titles)) {
                $title['title'] = implode(' vs ', $titles) . ' - Comparison';
            }
        }

        return $title;
    }

    /**
     * Get SEO meta description
     */
    public function get_meta_description() {
        global $wp_query;

        if (!isset($wp_query->query_vars['wp_compare_slugs'])) {
            return '';
        }

        $slugs = $GLOBALS['wp_compare_slugs'] ?? array();
        $posts = $GLOBALS['wp_compare_posts'] ?? array();

        if (empty($posts)) {
            return '';
        }

        $descriptions = array();
        
        foreach ($posts as $post) {
            $excerpt = get_the_excerpt($post);
            if (!empty($excerpt)) {
                $descriptions[] = wp_trim_words($excerpt, 15);
            }
        }

        if (!empty($descriptions)) {
            return sprintf(
                __('Compare %s. Detailed comparison of features, specifications, and more.', 'wp-compare-engine'),
                implode(' vs ', array_map('get_the_title', $posts))
            );
        }

        return '';
    }
}
