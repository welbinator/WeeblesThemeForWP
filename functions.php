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

    // Render the "Hide Title" checkbox.
    echo '<p>';
    echo '<label>';
    echo '<input type="checkbox" name="weebles_hide_title" value="1" ' . checked($hide_title, '1', false) . ' />';
    echo __('Hide Page Title', 'weebles');
    echo '</label>';
    echo '</p>';

    // Render the "Layout" dropdown.
    echo '<p>';
    echo '<label for="weebles_layout">' . __('Choose Layout:', 'weebles') . '</label>';
    echo '<select name="weebles_layout" id="weebles_layout">';
    echo '<option value="narrow"' . selected($layout, 'narrow', false) . '>' . __('Narrow', 'weebles') . '</option>';
    echo '<option value="boxed"' . selected($layout, 'boxed', false) . '>' . __('Boxed', 'weebles') . '</option>';
    echo '<option value="full"' . selected($layout, 'full', false) . '>' . __('Full Width', 'weebles') . '</option>';
    echo '</select>';
    echo '</p>';
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
