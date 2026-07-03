# WP Compare Engine

A production-ready WordPress comparison engine plugin similar to Versus.com. Compares WordPress Custom Post Types with full ACF Pro integration.

## Features

- **SEO-Friendly URLs**: `/compare/item-a-vs-item-b-vs-item-c`
- **ACF Pro Integration**: Automatically compares all shared custom fields
- **LocalStorage Based**: No database tables, no data duplication
- **GenerateBlocks Compatible**: Design your compare pages with GenerateBlocks
- **Responsive Design**: Works on desktop, tablet, and mobile
- **Accessibility Ready**: WCAG AA compliant, keyboard navigation, ARIA labels
- **SEO Optimized**: Schema markup, Rank Math & Yoast SEO support
- **Extensible**: Hooks and filters for customization

## Requirements

- WordPress 5.8+
- PHP 7.4+
- ACF Pro (for custom field comparisons)
- GeneratePress Theme (recommended)
- GenerateBlocks (recommended)

## Installation

1. Upload the `wp-compare-engine` folder to `/wp-content/plugins/`
2. Activate the plugin through WordPress admin
3. Configure settings under **Compare Engine** menu
4. Add compare checkboxes to your archive templates

## Usage

### Adding Compare Checkboxes to Archive Pages

Add this code to your archive template (e.g., `archive.php` or in a GenerateBlocks hook):

```php
<?php if (class_exists('WP_Compare_Engine')) : ?>
    <button class="wp-compare-checkbox" 
            data-slug="<?php echo get_post_field('post_name'); ?>"
            data-title="<?php the_title_attribute(); ?>"
            data-permalink="<?php the_permalink(); ?>">
        <input type="checkbox">
        <span class="wp-compare-checkbox-label"><?php _e('Compare', 'wp-compare-engine'); ?></span>
    </button>
<?php endif; ?>
```

### Creating a Compare Page

1. Create a new WordPress Page
2. Set the page slug to `compare` (or your configured slug)
3. Add the shortcode `[wp_compare_table]` where you want the comparison to appear
4. Design the rest of the page using GenerateBlocks

The plugin will automatically handle the URL rewriting for:
- `/compare/iphone-16-vs-galaxy-s25`
- `/compare/macbook-air-vs-dell-xps-vs-surface-pro`

### Shortcodes

#### `[wp_compare_table]`
Outputs the comparison table for items in the current URL.

#### `[wp_compare_selector]`
Displays the floating compare bar with selected items.

## Template Override

Copy templates to your theme folder for customization:

```
your-theme/wp-compare/
├── compare.php
└── components/
    ├── header.php
    ├── table-header.php
    └── selector.php
```

## Hooks & Filters

### Actions

```php
// Before compare table renders
do_action('wp_compare_before_table', $posts, $slugs);

// After compare table renders
do_action('wp_compare_after_table', $posts, $slugs);

// Before/after header
do_action('wp_compare_before_header', $posts, $slugs);
do_action('wp_compare_after_header', $posts, $slugs);

// Before request handling
do_action('wp_compare_before_request', $posts, $slugs);

// Schema output
do_action('wp_compare_schema', $posts, $slugs);
```

### Filters

```php
// Filter cell content
apply_filters('wp_compare_cell_content', $content, $field_name, $value, $post, $field_info);

// Filter field value before rendering
apply_filters('wp_compare_field_value', $value, $type, $post_id);

// Filter template paths
apply_filters('wp_compare_template_path', $template, $slug, $name, $args);
apply_filters('wp_compare_full_template_path', $template, $template_name, $args);
```

## JavaScript API

Access the compare functionality from your custom JavaScript:

```javascript
// Add item to compare
wpCompare.add('product-slug');

// Remove item
wpCompare.remove('product-slug');

// Clear all
wpCompare.clear();

// Get current list
const list = wpCompare.getList();

// Open search modal
wpCompare.openSearch();

// Refresh the bar
wpCompare.refresh();
```

## Settings

Configure the plugin under **Compare Engine** in WordPress admin:

- **Compare Slug**: URL base (default: `compare`)
- **Maximum Items**: Max items to compare (default: 3)
- **Minimum Items**: Min items required (default: 2)
- **Enable Sticky Bar**: Show floating compare bar
- **Enable Difference Highlighting**: Highlight different values
- **Enable Caching**: Cache field definitions
- **Allowed Post Types**: Select which post types support comparison

## Supported ACF Field Types

- Text, Textarea, Number, Email, URL
- True/False, Select, Checkbox, Radio
- Date Picker, Time Picker, Date/Time Picker
- Image, Gallery, File
- Relationship, Post Object, Taxonomy
- Link, Color Picker, Range
- Group, Repeater
- Google Map, User
- WYSIWYG

## Performance

- No custom database tables
- Uses existing WordPress posts and ACF fields
- LocalStorage for client-side persistence
- Efficient WP_Query usage
- Lazy loading images
- Cached field group lookups

## Security

- Nonce verification for all AJAX requests
- Capability checks for admin functions
- Input sanitization and output escaping
- Prepared statements where applicable
- No direct file access

## Browser Support

- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)
- Mobile browsers

## Changelog

### 1.0.0
- Initial release
- Core comparison functionality
- ACF Pro integration
- SEO-friendly URLs
- Responsive design
- Accessibility features
- Schema markup
- REST API endpoint

## License

GPL v2 or later

## Credits

Developed for use with GeneratePress, GenerateBlocks, and ACF Pro.
