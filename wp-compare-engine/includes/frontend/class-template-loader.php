<?php
/**
 * Template Loader Class
 * Handles template loading with theme override support
 */

namespace WP_Compare;

if (!defined('ABSPATH')) {
    exit;
}

class Template_Loader {

    private $template_path = 'wp-compare/';
    private $default_path = '';

    public function __construct() {
        $this->default_path = WP_COMPARE_PLUGIN_DIR . 'templates/';
    }

    /**
     * Get template part
     * 
     * @param string $slug Template slug
     * @param string $name Optional template name
     * @param array $args Arguments to pass to template
     */
    public function get_template_part($slug, $name = '', $args = array()) {
        $template = '';

        // Look in theme first
        if ($name) {
            $template = locate_template(array(
                $this->template_path . "{$slug}-{$name}.php",
                $this->template_path . "{$slug}/{$name}.php",
            ));
        }

        if (!$template && $name && file_exists($this->default_path . "components/{$slug}-{$name}.php")) {
            $template = $this->default_path . "components/{$slug}-{$name}.php";
        }

        if (!$template) {
            $template = locate_template(array(
                $this->template_path . "{$slug}.php",
            ));
        }

        if (!$template && file_exists($this->default_path . "{$slug}.php")) {
            $template = $this->default_path . "{$slug}.php";
        }

        if (!$template && file_exists($this->default_path . "components/{$slug}.php")) {
            $template = $this->default_path . "components/{$slug}.php";
        }

        /**
         * Filter the template path
         */
        $template = apply_filters('wp_compare_template_path', $template, $slug, $name, $args);

        if (file_exists($template)) {
            extract($args);
            include $template;
        }
    }

    /**
     * Get full template
     * 
     * @param string $template_name Template name
     * @param array $args Arguments to pass to template
     */
    public function get_template($template_name, $args = array()) {
        $template = locate_template(array(
            $this->template_path . $template_name,
        ));

        if (!$template && file_exists($this->default_path . $template_name)) {
            $template = $this->default_path . $template_name;
        }

        /**
         * Filter the template path
         */
        $template = apply_filters('wp_compare_full_template_path', $template, $template_name, $args);

        if (file_exists($template)) {
            extract($args);
            include $template;
        }
    }
}
