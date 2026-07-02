# WP Product Compare Plugin

A lightweight WordPress plugin that enables product comparisons similar to Versus.com. It uses your existing Custom Post Types and Advanced Custom Fields (ACF) to generate side-by-side comparison pages.

## Features

- **Clean URL Structure**: Uses the format `/slug1+vs+slug2` (e.g., `iphone-15+vs+samsung-s24`).
- **ACF Integration**: Automatically detects and displays all ACF fields associated with your posts in a comparison table.
- **Custom Post Type Support**: Works with any CPT (configurable in the main plugin file).
- **Responsive Design**: Mobile-friendly comparison cards and tables.
- **No Custom Database**: Uses standard WordPress posts and post meta.

## Installation

1. Upload the `wp-product-compare` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. **Important**: Go to **Settings > Permalinks** and click "Save Changes" to flush rewrite rules.

## Configuration

Open `wp-product-compare.php` and change the `$post_type` variable to match your Custom Post Type slug:

```php
private $post_type = 'product'; // Change to 'phone', 'laptop', etc.
```

## Usage

### 1. Generate Comparison Links

Use the helper function in your theme templates (e.g., inside `single-product.php` or archive loops):

```php
<?php
// Get IDs of two products you want to compare
$id_1 = 101; 
$id_2 = 102;

// Output the comparison URL
$compare_url = wpc_get_compare_url($id_1, $id_2);
echo '<a href="' . esc_url($compare_url) . '">Compare These Two</a>';
?>
```

### 2. Accessing the Comparison Page

Simply visit the URL directly in your browser:
`https://your-site.com/product-slug-1+vs+product-slug-2`

### 3. Customizing the Template

The plugin looks for a template file in this order:
1. `your-theme/comparison-template.php` (Override here to customize design)
2. `wp-product-compare/templates/comparison-template.php` (Default fallback)

Copy the default template to your theme folder to make customizations without losing updates.

## How It Works

1. **Rewrite Rule**: The plugin registers a regex rule `^([^/]+)\+vs\+([^/]+)/?$` to capture the two slugs.
2. **Query Vars**: It maps these slugs to custom query vars `compare_item_1` and `compare_item_2`.
3. **Template Loading**: On `template_redirect`, it fetches the posts by slug and loads the comparison template.
4. **ACF Loop**: The template automatically loops through all ACF fields present on either post and displays them side-by-side.

## Requirements

- WordPress 5.0+
- PHP 7.4+
- Advanced Custom Fields (ACF) plugin (recommended for best field formatting, though it has a fallback to raw meta).
- Pretty Permalinks enabled (not Plain structure).

## License

GPL v2 or later
