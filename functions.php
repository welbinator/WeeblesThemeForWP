<?php
/**
 * WP Rig functions and definitions
 *
 * This file must be parseable by PHP 5.2.
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package wp_rig
 */

define( 'WP_RIG_MINIMUM_WP_VERSION', '5.4' );
define( 'WP_RIG_MINIMUM_PHP_VERSION', '8.0' );

// Bail if requirements are not met.
if ( version_compare( $GLOBALS['wp_version'], WP_RIG_MINIMUM_WP_VERSION, '<' ) || version_compare( phpversion(), WP_RIG_MINIMUM_PHP_VERSION, '<' ) ) {
	require get_template_directory() . '/inc/back-compat.php';
	return;
}

// Include WordPress shims.
require get_template_directory() . '/inc/wordpress-shims.php';

// Setup autoloader (via Composer or custom).
if ( file_exists( get_template_directory() . '/vendor/autoload.php' ) ) {
	require get_template_directory() . '/vendor/autoload.php';
} else {
	/**
	 * Custom autoloader function for theme classes.
	 *
	 * @access private
	 *
	 * @param string $class_name Class name to load.
	 * @return bool True if the class was loaded, false otherwise.
	 */
	function _wp_rig_autoload( $class_name ) {
		$namespace = 'WP_Rig\WP_Rig';

		if ( strpos( $class_name, $namespace . '\\' ) !== 0 ) {
			return false;
		}

		$parts = explode( '\\', substr( $class_name, strlen( $namespace . '\\' ) ) );

		$path = get_template_directory() . '/inc';
		foreach ( $parts as $part ) {
			$path .= '/' . $part;
		}
		$path .= '.php';

		if ( ! file_exists( $path ) ) {
			return false;
		}

		require_once $path;

		return true;
	}
	spl_autoload_register( '_wp_rig_autoload' );
}

// Load the `wp_rig()` entry point function.
require get_template_directory() . '/inc/functions.php';

// Add custom WP CLI commands.
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once get_template_directory() . '/wp-cli/wp-rig-commands.php';
}

// Initialize the theme.
call_user_func( 'WP_Rig\WP_Rig\wp_rig' );

// Enqueues media uploader
function weebles_enqueue_media_uploader($hook) {
    if ('post.php' === $hook || 'post-new.php' === $hook) {
        wp_enqueue_media();
    }
}
add_action('admin_enqueue_scripts', 'weebles_enqueue_media_uploader');

/**
 * Adds the Weebles Theme Settings meta box to all public post types.
 */
function weebles_add_meta_box() {
    $post_types = get_post_types(['public' => true]);

    foreach ($post_types as $post_type) {
        add_meta_box(
            'weebles_theme_settings',
            __('Weebles Theme Settings', 'weebles'),
            'weebles_meta_box_callback',
            $post_type,
            'normal',
            'default'
        );
    }
}
add_action('add_meta_boxes', 'weebles_add_meta_box');

/**
 * Callback function to render the Weebles Theme Settings meta box.
 *
 * @param WP_Post $post The post object.
 */
function weebles_meta_box_callback($post) {
    // Add a nonce field for security.
    wp_nonce_field('weebles_meta_box_nonce', 'weebles_meta_box_nonce_field');

    // Retrieve saved meta values.
    $hide_title = get_post_meta($post->ID, '_weebles_hide_title', true);
    $layout = get_post_meta($post->ID, '_weebles_layout', true);
    $meta_title = get_post_meta($post->ID, '_weebles_meta_title', true);
    $meta_description = get_post_meta($post->ID, '_weebles_meta_description', true);
    $meta_og_image = get_post_meta($post->ID, '_weebles_meta_og_image', true);

    // Page/Post Settings Section
    echo '<h3>' . __('Page/Post Settings', 'weebles') . '</h3>';

    echo '<p>';
    echo '<label>';
    echo '<input type="checkbox" name="weebles_hide_title" value="1" ' . checked($hide_title, '1', false) . ' />';
    echo __('Hide Page Title', 'weebles');
    echo '</label>';
    echo '</p>';

    echo '<p>';
    echo '<label for="weebles_layout">' . __('Choose Layout:', 'weebles') . '</label>';
    echo '<select name="weebles_layout" id="weebles_layout">';
    echo '<option value="narrow"' . selected($layout, 'narrow', false) . '>' . __('Narrow', 'weebles') . '</option>';
    echo '<option value="boxed"' . selected($layout, 'boxed', false) . '>' . __('Boxed', 'weebles') . '</option>';
    echo '<option value="full"' . selected($layout, 'full', false) . '>' . __('Full Width', 'weebles') . '</option>';
    echo '</select>';
    echo '</p>';

    // SEO Settings Section
    echo '<h3>' . __('SEO Settings', 'weebles') . '</h3>';

    echo '<p>';
    echo '<label for="weebles_meta_title">' . __('Custom Meta Title:', 'weebles') . '</label>';
    echo '<input type="text" name="weebles_meta_title" id="weebles_meta_title" value="' . esc_attr($meta_title) . '" style="width: 100%;" />';
    echo '</p>';

    echo '<p>';
    echo '<label for="weebles_meta_description">' . __('Custom Meta Description:', 'weebles') . '</label>';
    echo '<textarea name="weebles_meta_description" id="weebles_meta_description" style="width: 100%; height: 4em;">' . esc_textarea($meta_description) . '</textarea>';
    echo '</p>';

    echo '<div class="og-image-uploader">';
    echo '<label for="weebles_meta_og_image">' . __('Custom Open Graph Image:', 'weebles') . '</label>';
    echo '<input type="hidden" name="weebles_meta_og_image" id="weebles_meta_og_image" value="' . esc_url($meta_og_image) . '" />';
    echo '<button type="button" class="button" id="weebles_upload_image_button">' . __('Upload Image', 'weebles') . '</button>';
    echo '<img src="' . esc_url($meta_og_image) . '" id="weebles_meta_og_image_preview" style="max-width: 100%; margin-top: 1em; ' . (empty($meta_og_image) ? 'display:none;' : '') . '" />';
    echo '</div>';
}




/**
 * Saves the Weebles Theme Settings meta box data.
 *
 * @param int $post_id The ID of the current post.
 */
function weebles_save_meta_box_data($post_id) {
    // Verify the nonce for security.
    if (!isset($_POST['weebles_meta_box_nonce_field']) ||
        !wp_verify_nonce($_POST['weebles_meta_box_nonce_field'], 'weebles_meta_box_nonce')) {
        return;
    }

    // Check for autosave and user permissions.
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    // Save the "Hide Title" setting.
    $hide_title = isset($_POST['weebles_hide_title']) ? '1' : '';
    update_post_meta($post_id, '_weebles_hide_title', $hide_title);

    // Save the "Layout" setting.
    if (isset($_POST['weebles_layout'])) {
        $layout = sanitize_text_field($_POST['weebles_layout']);
        update_post_meta($post_id, '_weebles_layout', $layout);
    }

    // Save the "Custom Meta Title" setting.
    if (isset($_POST['weebles_meta_title'])) {
        $meta_title = sanitize_text_field($_POST['weebles_meta_title']);
        update_post_meta($post_id, '_weebles_meta_title', $meta_title);
    }

    // Save the "Custom Meta Description" setting.
    if (isset($_POST['weebles_meta_description'])) {
        $meta_description = sanitize_textarea_field($_POST['weebles_meta_description']);
        update_post_meta($post_id, '_weebles_meta_description', $meta_description);
    }

	// Save the OG image setting.
	if (isset($_POST['weebles_meta_og_image'])) {
		$meta_og_image = esc_url_raw($_POST['weebles_meta_og_image']);
		update_post_meta($post_id, '_weebles_meta_og_image', $meta_og_image);
	}
}


add_action('save_post', 'weebles_save_meta_box_data');

/**
 * Filters the content to conditionally hide the page title.
 *
 * @param string $content The post content.
 * @return string The filtered content.
 */
function weebles_hide_title($content) {
    if (is_singular()) {
        global $post;
        $hide_title = get_post_meta($post->ID, '_weebles_hide_title', true);

        if ($hide_title === '1') {
            // Add a style to hide the title.
            $content = '<style>.entry-title { display: none; }</style>' . $content;
        }
    }

    return $content;
}
add_filter('the_content', 'weebles_hide_title');

function weebles_add_body_class($classes) {
    if (is_singular()) {
        global $post;
        $layout = get_post_meta($post->ID, '_weebles_layout', true);

        if ($layout && $layout !== 'narrow') {
            $classes[] = 'layout-' . $layout;
        }
    }

    return $classes;
}
add_filter('body_class', 'weebles_add_body_class');

/**
 * Outputs SEO, Open Graph, and Twitter meta tags in the <head>.
 */
function weebles_output_meta_tags() {
    if (is_singular()) {
        global $post;

        // Get custom meta values.
        $meta_title = get_post_meta($post->ID, '_weebles_meta_title', true) ?: get_the_title($post);
        $meta_description = get_post_meta($post->ID, '_weebles_meta_description', true) ?: get_bloginfo('description');
        $meta_image = get_post_meta($post->ID, '_weebles_meta_og_image', true) ?: get_the_post_thumbnail_url($post->ID, 'full') ?: '';
        $meta_url = get_permalink($post);

        // SEO Meta Tags.
        if (!empty($meta_title)) {
            echo '<title>' . esc_html($meta_title) . '</title>' . PHP_EOL;
        }
        if (!empty($meta_description)) {
            echo '<meta name="description" content="' . esc_attr($meta_description) . '">' . PHP_EOL;
        }

        // Open Graph Tags.
        echo '<meta property="og:title" content="' . esc_attr($meta_title) . '">' . PHP_EOL;
        echo '<meta property="og:description" content="' . esc_attr($meta_description) . '">' . PHP_EOL;
        echo '<meta property="og:url" content="' . esc_url($meta_url) . '">' . PHP_EOL;
        echo '<meta property="og:type" content="article">' . PHP_EOL;
        if (!empty($meta_image)) {
            echo '<meta property="og:image" content="' . esc_url($meta_image) . '">' . PHP_EOL;
        }

        // Twitter Tags.
        echo '<meta name="twitter:card" content="summary_large_image">' . PHP_EOL;
        echo '<meta name="twitter:title" content="' . esc_attr($meta_title) . '">' . PHP_EOL;
        echo '<meta name="twitter:description" content="' . esc_attr($meta_description) . '">' . PHP_EOL;
        if (!empty($meta_image)) {
            echo '<meta name="twitter:image" content="' . esc_url($meta_image) . '">' . PHP_EOL;
        }
    }
}
add_action('wp_head', 'weebles_output_meta_tags');

/**
 * Adds the Custom Code meta box below the existing one for all public post types.
 */
function weebles_add_custom_code_meta_box() {
    $post_types = get_post_types(['public' => true]);

    foreach ($post_types as $post_type) {
        add_meta_box(
            'weebles_custom_code',
            __('Custom Code', 'weebles'),
            'weebles_custom_code_meta_box_callback',
            $post_type,
            'normal',
            'default'
        );
    }
}
add_action('add_meta_boxes', 'weebles_add_custom_code_meta_box');

/**
 * Callback function to render the Custom Code meta box.
 *
 * @param WP_Post $post The post object.
 */
function weebles_custom_code_meta_box_callback($post) {
    // Add a nonce field for security.
    wp_nonce_field('weebles_custom_code_nonce', 'weebles_custom_code_nonce_field');

    // Retrieve saved meta values.
    $header_code = get_post_meta($post->ID, '_weebles_header_code', true);
    $footer_code = get_post_meta($post->ID, '_weebles_footer_code', true);

    echo '<p>' . __('Add custom code to the header or footer for this post/page.', 'weebles') . '</p>';

    echo '<p>';
    echo '<label for="weebles_header_code">' . __('Header Code:', 'weebles') . '</label>';
    echo '<textarea name="weebles_header_code" id="weebles_header_code" style="width: 100%; height: 6em;">' . esc_textarea($header_code) . '</textarea>';
    echo '</p>';

    echo '<p>';
    echo '<label for="weebles_footer_code">' . __('Footer Code:', 'weebles') . '</label>';
    echo '<textarea name="weebles_footer_code" id="weebles_footer_code" style="width: 100%; height: 6em;">' . esc_textarea($footer_code) . '</textarea>';
    echo '</p>';
}

/**
 * Saves the Custom Code meta box data.
 *
 * @param int $post_id The ID of the current post.
 */
function weebles_save_custom_code_meta_box_data($post_id) {
    // Verify the nonce for security.
    if (!isset($_POST['weebles_custom_code_nonce_field']) ||
        !wp_verify_nonce($_POST['weebles_custom_code_nonce_field'], 'weebles_custom_code_nonce')) {
        return;
    }

    // Check for autosave and user permissions.
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    // Allow specific tags, including <script>.
    $allowed_tags = [
        'script' => [
            'type' => true,
        ],
    ];

    // Save the "Header Code" setting.
    if (isset($_POST['weebles_header_code'])) {
        $header_code = wp_kses($_POST['weebles_header_code'], $allowed_tags);
        update_post_meta($post_id, '_weebles_header_code', $header_code);
    }

    // Save the "Footer Code" setting.
    if (isset($_POST['weebles_footer_code'])) {
        $footer_code = wp_kses($_POST['weebles_footer_code'], $allowed_tags);
        update_post_meta($post_id, '_weebles_footer_code', $footer_code);
    }
}

add_action('save_post', 'weebles_save_custom_code_meta_box_data');

/**
 * Outputs custom header and footer code.
 */
function weebles_output_custom_code() {
    if (is_singular()) {
        global $post;

        // Get the custom header code.
        $header_code = get_post_meta($post->ID, '_weebles_header_code', true);
        if (!empty($header_code)) {
            echo $header_code; // Already sanitized when saving.
        }
    }
}
add_action('wp_head', 'weebles_output_custom_code');

function weebles_output_custom_footer_code() {
    if (is_singular()) {
        global $post;

        // Get the custom footer code.
        $footer_code = get_post_meta($post->ID, '_weebles_footer_code', true);
        if (!empty($footer_code)) {
            echo $footer_code; // Already sanitized when saving.
        }
    }
}
add_action('wp_footer', 'weebles_output_custom_footer_code');

