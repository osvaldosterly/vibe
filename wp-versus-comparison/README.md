# WP Versus Comparison Plugin

A lightweight WordPress plugin that creates a Versus.com-style product comparison experience using your existing posts and Advanced Custom Fields (ACF).

## Features

- **Simple URL Structure**: Compare any two items with URLs like `yoursite.com/item-1-vs-item-2`
- **ACF Integration**: Automatically displays all ACF fields in a comparison table
- **Custom Post Type Support**: Works with any post type (posts, pages, custom post types)
- **Responsive Design**: Mobile-friendly comparison layout
- **Print-Friendly**: Optimized for printing comparisons
- **Share Functionality**: Easy URL copying for sharing comparisons
- **No Custom Database**: Uses default WordPress posts and ACF fields

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- [Advanced Custom Fields (ACF)](https://www.advancedcustomfields.com/) plugin

## Installation

1. Upload the `wp-versus-comparison` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Ensure ACF is installed and activated
4. Create or edit posts with ACF fields for specifications

## Usage

### Creating a Comparison URL

To compare two items, simply construct a URL with their slugs:

```
https://yoursite.com/[post-slug-1]/vs/[post-slug-2]
```

**Examples:**
- `https://yoursite.com/iphone-15-pro-vs-samsung-galaxy-s24`
- `https://yoursite.com/macbook-pro-vs-dell-xps-15`

### Generating Comparison Links Programmatically

Use the helper function in your theme or other plugins:

```php
// Generate comparison URL
$comparison_url = wpc_get_comparison_url($post_id_1, $post_id_2);
echo '<a href="' . esc_url($comparison_url) . '">Compare these items</a>';
```

### Adding ACF Fields

1. Install and activate ACF
2. Create field groups for your post types
3. Add fields like:
   - `screen_size` (Text or Number)
   - `battery_life` (Text)
   - `weight` (Text)
   - `price` (Number)
   - `color_options` (Select or Checkbox)
   - Any other specifications you want to compare

The plugin will automatically display all ACF fields in the comparison table.

## Template Customization

### Override the Default Template

Copy `templates/comparison-default.php` to your theme directory as:
- `single-wpvc-comparison.php`, or
- `wpvc-comparison.php`

Then customize it according to your needs.

### CSS Customization

The plugin loads its own stylesheet. To override styles:

1. Add custom CSS in your theme's `style.css`
2. Or dequeue the plugin's CSS and add your own:

```php
function my_custom_wpvc_styles() {
    wp_dequeue_style('wpvc-style');
    wp_enqueue_style('my-wpvc-style', get_stylesheet_directory_uri() . '/css/my-wpvc-custom.css');
}
add_action('wp_enqueue_scripts', 'my_custom_wpvc_styles', 20);
```

## Helper Functions

### `wpc_get_comparison_url($post_id_1, $post_id_2)`

Generates a comparison URL for two posts.

**Parameters:**
- `$post_id_1` (int): First post ID
- `$post_id_2` (int): Second post ID

**Returns:** (string) Full comparison URL

### `wpc_get_item_fields($post_id)`

Retrieves all ACF fields for a post.

**Parameters:**
- `$post_id` (int): Post ID

**Returns:** (array) Associative array of ACF field keys and values

## Example: Add "Compare" Button to Product Listings

Add this to your theme's `functions.php`:

```php
function add_compare_button_to_loop() {
    global $post;
    $current_post_id = get_the_ID();
    
    // You need another post to compare with - this is just an example
    // In practice, you'd have a UI to select which item to compare
    echo '<a href="#" class="compare-button" data-post-id="' . $current_post_id . '">Compare</a>';
}
add_action('loop_end', 'add_compare_button_to_loop');
```

## Troubleshooting

### Comparison Page Shows 404

1. Go to **Settings > Permalinks** in WordPress admin
2. Click "Save Changes" to flush rewrite rules
3. Or deactivate and reactivate the plugin

### ACF Fields Not Showing

1. Ensure ACF plugin is installed and activated
2. Verify fields are assigned to the correct post type
3. Check that fields have values for the posts being compared

### URL Structure Not Working

Ensure your permalink structure is not set to "Plain". Go to **Settings > Permalinks** and choose any option other than "Plain".

## Future Enhancements

- [ ] Add "Compare" button UI on single post pages
- [ ] Multi-item comparison (3+ items)
- [ ] User voting/rating system
- [ ] Pros/Cons sections
- [ ] Affiliate link integration
- [ ] Comparison history/saved comparisons
- [ ] Admin settings for customization

## License

GPL v2 or later

## Support

For issues and feature requests, please open an issue on the GitHub repository.
