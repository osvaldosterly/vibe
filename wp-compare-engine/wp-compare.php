<?php
/**
 * Plugin Name: WP Compare Engine
 * Plugin URI: https://example.com/wp-compare-engine
 * Description: A production-ready comparison engine for WordPress Custom Post Types with ACF Pro integration. Similar to Versus.com.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-compare-engine
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('WP_COMPARE_VERSION', '1.0.0');
define('WP_COMPARE_PLUGIN_FILE', __FILE__);
define('WP_COMPARE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WP_COMPARE_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WP_COMPARE_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Autoloader for WP Compare Engine
 */
spl_autoload_register(function ($class) {
    $prefix = 'WP_Compare\\';
    $base_dir = WP_COMPARE_PLUGIN_DIR . 'includes/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', strtolower(str_replace('_', '-', $relative_class))) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

/**
 * Main Plugin Class
 */
final class WP_Compare_Engine {

    private static $instance = null;
    private $settings = array();

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->init_hooks();
        $this->load_settings();
    }

    private function init_hooks() {
        register_activation_hook(WP_COMPARE_PLUGIN_FILE, array($this, 'activate'));
        register_deactivation_hook(WP_COMPARE_PLUGIN_FILE, array($this, 'deactivate'));

        add_action('init', array($this, 'init'), 0);
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        
        // REST API
        add_action('rest_api_init', array($this, 'register_rest_routes'));
        
        // Shortcodes
        add_shortcode('wp_compare_table', array($this, 'render_compare_table'));
        add_shortcode('wp_compare_selector', array($this, 'render_compare_selector'));
        
        // AJAX handlers
        add_action('wp_ajax_wp_compare_search', array($this, 'ajax_search'));
        add_action('wp_ajax_nopriv_wp_compare_search', array($this, 'ajax_search'));
        
        add_action('wp_ajax_wp_compare_add', array($this, 'ajax_add_item'));
        add_action('wp_ajax_nopriv_wp_compare_add', array($this, 'ajax_add_item'));
        
        add_action('wp_ajax_wp_compare_remove', array($this, 'ajax_remove_item'));
        add_action('wp_ajax_nopriv_wp_compare_remove', array($this, 'ajax_remove_item'));
        
        add_action('wp_ajax_wp_compare_clear', array($this, 'ajax_clear_all'));
        add_action('wp_ajax_nopriv_wp_compare_clear', array($this, 'ajax_clear_all'));
        
        add_action('wp_ajax_wp_compare_get_item', array($this, 'ajax_get_item'));
        add_action('wp_ajax_nopriv_wp_compare_get_item', array($this, 'ajax_get_item'));
        
        // Initialize frontend classes
        add_action('wp_loaded', array($this, 'init_frontend_classes'));
    }

    public function activate() {
        $this->register_rewrite_rules();
        flush_rewrite_rules(false);
        
        // Default settings
        $default_settings = array(
            'compare_slug' => 'compare',
            'max_items' => 3,
            'min_items' => 2,
            'enable_sticky_bar' => true,
            'enable_difference_highlighting' => true,
            'enable_caching' => true,
            'allowed_post_types' => array('post', 'page'),
        );
        
        if (!get_option('wp_compare_settings')) {
            add_option('wp_compare_settings', $default_settings);
        }
    }

    public function deactivate() {
        flush_rewrite_rules(false);
    }

    public function init() {
        $this->register_rewrite_rules();
        $this->load_textdomain();
    }

    public function register_rewrite_rules() {
        $slug = $this->get_setting('compare_slug', 'compare');
        
        add_rewrite_rule(
            '^' . $slug . '/(.*)/?$',
            'index.php?wp_compare_slugs=$matches[1]',
            'top'
        );
        
        add_rewrite_tag('%wp_compare_slugs%', '(.+)');
    }

    public function load_settings() {
        $this->settings = get_option('wp_compare_settings', array());
    }

    public function get_setting($key, $default = '') {
        return isset($this->settings[$key]) ? $this->settings[$key] : $default;
    }

    public function load_textdomain() {
        load_plugin_textdomain('wp-compare-engine', false, dirname(WP_COMPARE_PLUGIN_BASENAME) . '/languages');
    }

    public function enqueue_assets() {
        wp_enqueue_style(
            'wp-compare-engine',
            WP_COMPARE_PLUGIN_URL . 'assets/css/wP-compare-engine.css',
            array(),
            WP_COMPARE_VERSION
        );

        wp_enqueue_script(
            'wp-compare-engine',
            WP_COMPARE_PLUGIN_URL . 'assets/js/wp-compare-engine.js',
            array(),
            WP_COMPARE_VERSION,
            true
        );

        wp_localize_script('wp-compare-engine', 'wpCompareData', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wp_compare_nonce'),
            'compareSlug' => $this->get_setting('compare_slug', 'compare'),
            'maxItems' => (int) $this->get_setting('max_items', 3),
            'minItems' => (int) $this->get_setting('min_items', 2),
            'strings' => array(
                'compare' => __('Compare', 'wp-compare-engine'),
                'compared' => __('Compared', 'wp-compare-engine'),
                'remove' => __('Remove', 'wp-compare-engine'),
                'clearAll' => __('Clear All', 'wp-compare-engine'),
                'searchPlaceholder' => __('Search items to compare...', 'wp-compare-engine'),
                'maxReached' => sprintf(__('Maximum %d items allowed', 'wp-compare-engine'), $this->get_setting('max_items', 3)),
            ),
        ));
    }

    public function add_admin_menu() {
        add_menu_page(
            __('WP Compare Engine', 'wp-compare-engine'),
            __('Compare Engine', 'wp-compare-engine'),
            'manage_options',
            'wp-compare-engine',
            array($this, 'render_admin_page'),
            'dashicons-table-col-after',
            30
        );
    }

    public function register_settings() {
        register_setting('wp_compare_settings_group', 'wp_compare_settings', array(
            'sanitize_callback' => array($this, 'sanitize_settings'),
        ));

        add_settings_section(
            'wp_compare_general_section',
            __('General Settings', 'wp-compare-engine'),
            null,
            'wp-compare-engine'
        );

        add_settings_field(
            'compare_slug',
            __('Compare Slug', 'wp-compare-engine'),
            array($this, 'render_slug_field'),
            'wp-compare-engine',
            'wp_compare_general_section'
        );

        add_settings_field(
            'max_items',
            __('Maximum Items', 'wp-compare-engine'),
            array($this, 'render_max_items_field'),
            'wp-compare-engine',
            'wp_compare_general_section'
        );

        add_settings_field(
            'min_items',
            __('Minimum Items', 'wp-compare-engine'),
            array($this, 'render_min_items_field'),
            'wp-compare-engine',
            'wp_compare_general_section'
        );

        add_settings_field(
            'enable_sticky_bar',
            __('Enable Sticky Bar', 'wp-compare-engine'),
            array($this, 'render_checkbox_field'),
            'wp-compare-engine',
            'wp_compare_general_section',
            array('field' => 'enable_sticky_bar')
        );

        add_settings_field(
            'enable_difference_highlighting',
            __('Enable Difference Highlighting', 'wp-compare-engine'),
            array($this, 'render_checkbox_field'),
            'wp-compare-engine',
            'wp_compare_general_section',
            array('field' => 'enable_difference_highlighting')
        );

        add_settings_field(
            'enable_caching',
            __('Enable Caching', 'wp-compare-engine'),
            array($this, 'render_checkbox_field'),
            'wp-compare-engine',
            'wp_compare_general_section',
            array('field' => 'enable_caching')
        );

        add_settings_field(
            'allowed_post_types',
            __('Allowed Post Types', 'wp-compare-engine'),
            array($this, 'render_post_types_field'),
            'wp-compare-engine',
            'wp_compare_general_section'
        );
    }

    public function render_slug_field() {
        $value = $this->get_setting('compare_slug', 'compare');
        echo '<input type="text" name="wp_compare_settings[compare_slug]" value="' . esc_attr($value) . '" class="regular-text">';
        echo '<p class="description">' . __('Default: compare. URL will be: example.com/compare/item-a-vs-item-b', 'wp-compare-engine') . '</p>';
    }

    public function render_max_items_field() {
        $value = $this->get_setting('max_items', 3);
        echo '<input type="number" name="wp_compare_settings[max_items]" value="' . esc_attr($value) . '" min="2" max="10" class="small-text">';
    }

    public function render_min_items_field() {
        $value = $this->get_setting('min_items', 2);
        echo '<input type="number" name="wp_compare_settings[min_items]" value="' . esc_attr($value) . '" min="2" max="5" class="small-text">';
    }

    public function render_checkbox_field($args) {
        $field = $args['field'];
        $value = $this->get_setting($field, true);
        echo '<label><input type="checkbox" name="wp_compare_settings[' . esc_attr($field) . ']" value="1" ' . checked($value, 1, false) . '> ' . __('Enabled', 'wp-compare-engine') . '</label>';
    }

    public function render_post_types_field() {
        $allowed = $this->get_setting('allowed_post_types', array('post', 'page'));
        $post_types = get_post_types(array('public' => true), 'objects');
        
        foreach ($post_types as $post_type) {
            $checked = in_array($post_type->name, $allowed) ? 'checked' : '';
            echo '<label style="display:block;"><input type="checkbox" name="wp_compare_settings[allowed_post_types][]" value="' . esc_attr($post_type->name) . '" ' . $checked . '> ' . esc_html($post_type->label) . '</label>';
        }
    }

    public function sanitize_settings($input) {
        $sanitized = array();
        
        $sanitized['compare_slug'] = sanitize_title($input['compare_slug']);
        $sanitized['max_items'] = absint($input['max_items']);
        $sanitized['min_items'] = absint($input['min_items']);
        $sanitized['enable_sticky_bar'] = isset($input['enable_sticky_bar']) ? 1 : 0;
        $sanitized['enable_difference_highlighting'] = isset($input['enable_difference_highlighting']) ? 1 : 0;
        $sanitized['enable_caching'] = isset($input['enable_caching']) ? 1 : 0;
        
        if (isset($input['allowed_post_types']) && is_array($input['allowed_post_types'])) {
            $sanitized['allowed_post_types'] = array_map('sanitize_key', $input['allowed_post_types']);
        } else {
            $sanitized['allowed_post_types'] = array();
        }
        
        return $sanitized;
    }

    public function render_admin_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('wp_compare_settings_group');
                do_settings_sections('wp-compare-engine');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function register_rest_routes() {
        register_rest_route('wp-compare/v1', '/search', array(
            'methods' => 'GET',
            'callback' => array($this, 'rest_search'),
            'permission_callback' => '__return_true',
            'args' => array(
                's' => array(
                    'required' => true,
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'post_type' => array(
                    'sanitize_callback' => 'sanitize_key',
                ),
            ),
        ));
    }

    public function rest_search($request) {
        $search_term = $request->get_param('s');
        $post_type = $request->get_param('post_type');
        $allowed_types = $this->get_setting('allowed_post_types', array('post', 'page'));
        
        if ($post_type && !in_array($post_type, $allowed_types)) {
            $post_type = $allowed_types;
        } elseif (!$post_type) {
            $post_type = $allowed_types;
        } else {
            $post_type = array($post_type);
        }

        $args = array(
            's' => $search_term,
            'post_type' => $post_type,
            'posts_per_page' => 20,
            'post_status' => 'publish',
        );

        $query = new WP_Query($args);
        $results = array();

        foreach ($query->posts as $post) {
            $results[] = array(
                'ID' => $post->ID,
                'title' => get_the_title($post),
                'slug' => $post->post_name,
                'permalink' => get_permalink($post),
                'post_type' => $post->post_type,
                'thumbnail' => get_the_post_thumbnail_url($post, 'thumbnail'),
            );
        }

        return rest_ensure_response($results);
    }

    public function ajax_search() {
        check_ajax_referer('wp_compare_nonce', 'nonce');
        
        $search_term = isset($_POST['s']) ? sanitize_text_field($_POST['s']) : '';
        $post_type = isset($_POST['post_type']) ? sanitize_key($_POST['post_type']) : '';
        
        $allowed_types = $this->get_setting('allowed_post_types', array('post', 'page'));
        
        if ($post_type && !in_array($post_type, $allowed_types)) {
            $post_type = $allowed_types;
        } elseif (!$post_type) {
            $post_type = $allowed_types;
        } else {
            $post_type = array($post_type);
        }

        $args = array(
            's' => $search_term,
            'post_type' => $post_type,
            'posts_per_page' => 20,
            'post_status' => 'publish',
        );

        $query = new WP_Query($args);
        $results = array();

        foreach ($query->posts as $post) {
            $results[] = array(
                'ID' => $post->ID,
                'title' => get_the_title($post),
                'slug' => $post->post_name,
                'permalink' => get_permalink($post),
                'post_type' => $post->post_type,
                'thumbnail' => get_the_post_thumbnail_url($post, 'thumbnail'),
            );
        }

        wp_send_json_success($results);
    }

    public function ajax_add_item() {
        check_ajax_referer('wp_compare_nonce', 'nonce');
        
        $slug = isset($_POST['slug']) ? sanitize_title($_POST['slug']) : '';
        
        if (empty($slug)) {
            wp_send_json_error(array('message' => __('Invalid slug', 'wp-compare-engine')));
        }

        $current = $this->get_compare_list();
        $max_items = (int) $this->get_setting('max_items', 3);

        if (count($current) >= $max_items && !in_array($slug, $current)) {
            wp_send_json_error(array('message' => sprintf(__('Maximum %d items allowed', 'wp-compare-engine'), $max_items)));
        }

        if (!in_array($slug, $current)) {
            $current[] = $slug;
            $this->save_compare_list($current);
        }

        wp_send_json_success(array('list' => $current));
    }

    public function ajax_remove_item() {
        check_ajax_referer('wp_compare_nonce', 'nonce');
        
        $slug = isset($_POST['slug']) ? sanitize_title($_POST['slug']) : '';
        
        if (empty($slug)) {
            wp_send_json_error(array('message' => __('Invalid slug', 'wp-compare-engine')));
        }

        $current = $this->get_compare_list();
        $current = array_diff($current, array($slug));
        $this->save_compare_list(array_values($current));

        wp_send_json_success(array('list' => array_values($current)));
    }

    public function ajax_clear_all() {
        check_ajax_referer('wp_compare_nonce', 'nonce');
        
        $this->save_compare_list(array());
        wp_send_json_success(array('list' => array()));
    }

    public function ajax_get_item() {
        check_ajax_referer('wp_compare_nonce', 'nonce');
        
        $slug = isset($_GET['slug']) ? sanitize_title($_GET['slug']) : '';
        
        if (empty($slug)) {
            wp_send_json_error(array('message' => __('Invalid slug', 'wp-compare-engine')));
        }

        $allowed_types = $this->get_setting('allowed_post_types', array('post', 'page'));
        $post = get_page_by_path($slug, OBJECT, $allowed_types);

        if (!$post) {
            wp_send_json_error(array('message' => __('Post not found', 'wp-compare-engine')));
        }

        wp_send_json_success(array(
            'ID' => $post->ID,
            'slug' => $post->post_name,
            'title' => get_the_title($post),
            'permalink' => get_permalink($post),
            'thumbnail' => get_the_post_thumbnail_url($post, 'thumbnail'),
        ));
    }

    public function get_compare_list() {
        if (isset($_COOKIE['wp_compare_list'])) {
            return json_decode(stripslashes($_COOKIE['wp_compare_list']), true);
        }
        return array();
    }

    public function save_compare_list($list) {
        setcookie('wp_compare_list', json_encode($list), time() + (30 * DAY_IN_SECONDS), COOKIEPATH, COOKIE_DOMAIN, is_ssl());
    }

    public function parse_compare_slugs($slugs_string) {
        $slugs = explode('-vs-', $slugs_string);
        $slugs = array_map('sanitize_title', $slugs);
        $slugs = array_filter($slugs);
        $slugs = array_unique($slugs);
        
        return array_values($slugs);
    }

    public function get_compare_posts($slugs) {
        $allowed_types = $this->get_setting('allowed_post_types', array('post', 'page'));
        
        $args = array(
            'post_name__in' => $slugs,
            'post_type' => $allowed_types,
            'post_status' => 'publish',
            'posts_per_page' => count($slugs),
        );

        $query = new WP_Query($args);
        return $query->posts;
    }

    public function render_compare_table($atts) {
        global $wp_query;
        
        $slugs_string = isset($wp_query->query_vars['wp_compare_slugs']) ? $wp_query->query_vars['wp_compare_slugs'] : '';
        
        if (empty($slugs_string)) {
            return '';
        }

        $slugs = $this->parse_compare_slugs($slugs_string);
        $min_items = (int) $this->get_setting('min_items', 2);
        $max_items = (int) $this->get_setting('max_items', 3);

        if (count($slugs) < $min_items || count($slugs) > $max_items) {
            return '';
        }

        $posts = $this->get_compare_posts($slugs);

        if (count($posts) !== count($slugs)) {
            return '';
        }

        ob_start();
        
        /**
         * Hook before compare table rendering
         * @param array $posts Array of WP_Post objects
         * @param array $slugs Array of post slugs
         */
        do_action('wp_compare_before_table', $posts, $slugs);

        include WP_COMPARE_PLUGIN_DIR . 'templates/compare.php';

        /**
         * Hook after compare table rendering
         * @param array $posts Array of WP_Post objects
         * @param array $slugs Array of post slugs
         */
        do_action('wp_compare_after_table', $posts, $slugs);

        return ob_get_clean();
    }

    public function render_compare_selector($atts) {
        ob_start();
        include WP_COMPARE_PLUGIN_DIR . 'templates/components/selector.php';
        return ob_get_clean();
    }

    /**
     * Render the Compare Checkbox for a post
     * 
     * @param int|null $post_id Optional. Post ID. Defaults to global post.
     */
    public function render_checkbox( $post_id = null ) {
        if ( ! $post_id ) {
            $post_id = get_the_ID();
        }

        if ( ! $post_id ) {
            return;
        }

        $settings = $this->get_setting('allowed_post_types', array('post', 'page'));
        $post_type = get_post_type( $post_id );

        // Check if post type is allowed
        if ( ! in_array( $post_type, $settings, true ) ) {
            return;
        }

        $post       = get_post( $post_id );
        $slug       = $post->post_name;
        $title      = get_the_title( $post_id );
        $thumb_id   = get_post_thumbnail_id( $post_id );
        $thumb_url  = $thumb_id ? wp_get_attachment_image_url( $thumb_id, 'thumbnail' ) : '';
        
        ?>
        <button type="button" 
                class="wp-compare-toggle" 
                data-slug="<?php echo esc_attr( $slug ); ?>" 
                data-post-id="<?php echo esc_attr( $post_id ); ?>"
                data-title="<?php echo esc_attr( $title ); ?>"
                data-thumbnail="<?php echo esc_url( $thumb_url ); ?>"
                aria-label="<?php printf( esc_attr__( 'Add %s to comparison', 'wp-compare-engine' ), $title ); ?>">
            
            <span class="wp-compare-text-default">
                <svg class="icon-checkbox" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                </svg>
                <?php esc_html_e( 'Compare', 'wp-compare-engine' ); ?>
            </span>
            
            <span class="wp-compare-text-active" style="display:none;">
                <svg class="icon-check" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
                <?php esc_html_e( 'Compared', 'wp-compare-engine' ); ?>
            </span>
        </button>
        <?php
    }

    /**
     * Initialize frontend classes
     */
    public function init_frontend_classes() {
        // Load frontend class files manually since autoloader naming might not match
        require_once WP_COMPARE_PLUGIN_DIR . 'includes/frontend/class-compare-engine.php';
        require_once WP_COMPARE_PLUGIN_DIR . 'includes/frontend/class-query-handler.php';
        require_once WP_COMPARE_PLUGIN_DIR . 'includes/frontend/class-schema-handler.php';
        require_once WP_COMPARE_PLUGIN_DIR . 'includes/frontend/class-template-loader.php';
        
        if (class_exists('WP_Compare\\Query_Handler')) {
            new WP_Compare\Query_Handler($this);
        }
        if (class_exists('WP_Compare\\Schema_Handler')) {
            new WP_Compare\Schema_Handler();
        }
        if (class_exists('WP_Compare\\Template_Loader')) {
            new WP_Compare\Template_Loader();
        }
    }

    /**
     * Get all ACF fields for a post
     */
    public function get_acf_fields($post_id) {
        if (!function_exists('get_field_object')) {
            return array();
        }

        $fields = array();
        
        // Get field groups for this post
        $field_groups = acf_get_field_groups(array(
            'post_id' => $post_id,
        ));

        foreach ($field_groups as $group) {
            $group_fields = acf_get_fields($group['ID']);
            
            if ($group_fields) {
                foreach ($group_fields as $field) {
                    $value = get_field($field['name'], $post_id);
                    
                    if ($value !== null && $value !== '') {
                        $fields[$field['name']] = array(
                            'label' => $field['label'],
                            'name' => $field['name'],
                            'type' => $field['type'],
                            'value' => $value,
                            'parent' => isset($field['parent']) ? $field['parent'] : 0,
                        );
                    }
                }
            }
        }

        return $fields;
    }
}

// Initialize plugin
function wp_compare_engine_init() {
    return WP_Compare_Engine::get_instance();
}

$GLOBALS['wp_compare_engine'] = wp_compare_engine_init();

/**
 * Helper Function: Render Compare Checkbox
 * 
 * Usage: <?php wp_compare_render_checkbox(); ?>
 * 
 * @param int|null $post_id Optional. Post ID. Defaults to global post.
 */
function wp_compare_render_checkbox( $post_id = null ) {
    if ( isset( $GLOBALS['wp_compare_engine'] ) && method_exists( $GLOBALS['wp_compare_engine'], 'render_checkbox' ) ) {
        $GLOBALS['wp_compare_engine']->render_checkbox( $post_id );
    }
}

/**
 * Helper Function: Get Settings
 * 
 * @return array
 */
function wp_compare_get_settings() {
    if ( isset( $GLOBALS['wp_compare_engine'] ) ) {
        return $GLOBALS['wp_compare_engine']->get_setting('allowed_post_types', array('post', 'page'));
    }
    return array('post', 'page');
}

/**
 * Helper Function: Is Compare Page
 * 
 * @return bool
 */
function wp_compare_is_compare_page() {
    global $wp;
    if ( ! isset( $wp->query_vars['wp_compare_slugs'] ) ) {
        return false;
    }
    return true;
}
