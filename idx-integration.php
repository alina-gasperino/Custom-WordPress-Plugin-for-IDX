<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://portlandhomesforsale.com/
 * @since             1.0.0
 * @package           Idx_Integration
 *
 * @wordpress-plugin
 * Plugin Name:       IDX Integration
 * Plugin URI:        https://portlandhomesforsale.com/
 * Description:       WordPress plugin for custom IDX integration. It provides the all-in-one solution to integrate MLS, display, search, and filter properties.
 * Version:           1.0.0
 * Author:            Alina Gasperino
 * Author URI:        https://portlandhomesforsale.com//
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       idx-integration
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'IDX_INTEGRATION_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-idx-integration-activator.php
 */
function activate_idx_integration() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-idx-integration-activator.php';
	Idx_Integration_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-idx-integration-deactivator.php
 */
function deactivate_idx_integration() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-idx-integration-deactivator.php';
	Idx_Integration_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_idx_integration' );
register_deactivation_hook( __FILE__, 'deactivate_idx_integration' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-idx-integration.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_idx_integration() {

	$plugin = new Idx_Integration();
	$plugin->run();

}
run_idx_integration();

function my_idx_add_admin_menu() {
    add_menu_page(
        'IDX Settings',             // Page title
        'IDX Settings',             // Menu title
        'manage_options',             // Capability
        'my_idx_settings',          // Menu slug
        'my_idx_settings_page',     // Function to display the page
        'dashicons-admin-generic',    // Icon URL
        80                            // Position
    );
}
add_action('admin_menu', 'my_idx_add_admin_menu');

function my_idx_settings_page() {
    ?>
    <div class="wrap">
        <h1>IDX Settings</h1>
        <h2 class="nav-tab-wrapper">
            <a href="?page=my_idx_settings&tab=general" class="nav-tab <?php echo (isset($_GET['tab']) && $_GET['tab'] == 'general') ? 'nav-tab-active' : ''; ?>">General Settings</a>
			<a href="?page=my_idx_settings&tab=maps" class="nav-tab <?php echo (isset($_GET['tab']) && $_GET['tab'] == 'maps') ? 'nav-tab-active' : ''; ?>">Maps</a>
			<a href="?page=my_idx_settings&tab=emails" class="nav-tab <?php echo (isset($_GET['tab']) && $_GET['tab'] == 'emails') ? 'nav-tab-active' : ''; ?>">Emails</a>
			<a href="?page=my_idx_settings&tab=sms" class="nav-tab <?php echo (isset($_GET['tab']) && $_GET['tab'] == 'sms') ? 'nav-tab-active' : ''; ?>">SMS</a>
			<a href="?page=my_idx_settings&tab=filters" class="nav-tab <?php echo (isset($_GET['tab']) && $_GET['tab'] == 'filters') ? 'nav-tab-active' : ''; ?>">Filters</a>
			<a href="?page=my_idx_settings&tab=templates" class="nav-tab <?php echo (isset($_GET['tab']) && $_GET['tab'] == 'templates') ? 'nav-tab-active' : ''; ?>">Templates</a>
			<a href="?page=my_idx_settings&tab=tools" class="nav-tab <?php echo (isset($_GET['tab']) && $_GET['tab'] == 'tools') ? 'nav-tab-active' : ''; ?>">Tools</a>
            <a href="?page=my_idx_settings&tab=autotask" class="nav-tab <?php echo (isset($_GET['tab']) && $_GET['tab'] == 'autotask') ? 'nav-tab-active' : ''; ?>">Auto Tasks</a>
        </h2>

        <?php
        if (isset($_GET['tab']) && $_GET['tab'] == 'general') {
            include 'settings/settings-general.php';
        }
		elseif (isset($_GET['tab']) && $_GET['tab'] == 'maps') {
            include 'settings/settings-maps.php';
        }
		elseif (isset($_GET['tab']) && $_GET['tab'] == 'emails') {
            include 'settings/settings-emails.php';
        }
		elseif (isset($_GET['tab']) && $_GET['tab'] == 'sms') {
            include 'settings/settings-sms.php';
        }
		elseif (isset($_GET['tab']) && $_GET['tab'] == 'filters') {
            include 'settings/settings-filters.php';
        }
		elseif (isset($_GET['tab']) && $_GET['tab'] == 'templates') {
            include 'settings/settings-templates.php';
        }
		elseif (isset($_GET['tab']) && $_GET['tab'] == 'tools') {
            include 'settings/settings-tools.php';
        }
		else {
            include 'settings/settings-autotask.php';
        }
        ?>
    </div>
    <?php
}

function my_idx_settings_init() {
    // General settings
    register_setting('my_idx_general', 'my_idx_options_general', 'my_idx_sanitize_callback');
    add_settings_section('my_idx_general_section', 'General Settings', null, 'my_idx_general');

    add_settings_field('location_area', 'Location/Area', 'location_area_cb', 'my_idx_general', 'my_idx_general_section');
	add_settings_field('location_search', 'Default Location in Search', 'location_search_cb', 'my_idx_general', 'my_idx_general_section');
	add_settings_field('header_logo', 'Header Logo', 'header_logo_cb', 'my_idx_general', 'my_idx_general_section');

    // Map settings
    register_setting('my_idx_maps', 'my_idx_options_maps');
    add_settings_section('my_idx_maps_section', 'maps Settings', null, 'my_idx_maps');

    add_settings_field('my_idx_field_maps', 'maps Field', 'my_idx_field_maps_cb', 'my_idx_maps', 'my_idx_maps_section');
}
add_action('admin_init', 'my_idx_settings_init');

function location_area_cb() {
    $options = get_option('my_idx_options_general');
    echo '<input type="text" name="my_idx_options_general[location_area]" value="' . esc_attr($options['location_area']) . '">';
}

function location_search_cb() {
    $options = get_option('my_idx_options_general');
    $selected = $options['location_search'] ?? '';
    ?>
    <select name="my_idx_options_general[location_search]">
        <option value="option1" <?php selected($selected, 'option1'); ?>>Option 1</option>
        <option value="option2" <?php selected($selected, 'option2'); ?>>Option 2</option>
        <option value="option3" <?php selected($selected, 'option3'); ?>>Option 3</option>
    </select>
    <?php
}

function header_logo_cb() {
    $options = get_option('my_idx_options_general');
    $file_url = $options['header_logo'] ?? '';
    ?>
    <div class="file-upload-wrapper">
        <input type="hidden" id="header_logo" name="my_idx_options_general[header_logo]" value="<?php echo esc_url($file_url); ?>" />

        <!-- Image preview area -->
        <div id="image-preview" style="margin-bottom: 10px;">
            <?php if ($file_url): ?>
                <img src="<?php echo esc_url($file_url); ?>" style="max-width: 150px; display: block;" />
                <a href="#" class="remove-image">Remove</a>
            <?php else: ?>
                <img src="" style="max-width: 150px; display: none;" />
            <?php endif; ?>
        </div>

        <!-- Upload and Edit buttons -->
        <button type="button" class="button upload-button">Upload Image</button>
        <button type="button" class="button edit-button" style="display: <?php echo $file_url ? 'inline-block' : 'none'; ?>;">Edit</button>
    </div>
    <?php
}

// Enqueue the media uploader script
function my_theme_enqueue_media_uploader() {
    wp_enqueue_media();
    wp_enqueue_script('my-theme-media-uploader', plugin_dir_url( __FILE__ ) . 'js/media-uploader.js', array('jquery'), null, true);
}
add_action('admin_enqueue_scripts', 'my_theme_enqueue_media_uploader');

function my_idx_sanitize_callback($input) {
    $sanitized = array();
    if (isset($input['location_area'])) {
        $sanitized['location_area'] = sanitize_text_field($input['location_area']);
    }
    if (isset($input['location_search'])) {
        $sanitized['location_search'] = sanitize_text_field($input['location_search']);
    }
    if (isset($input['header_logo'])) {
        $sanitized['header_logo'] = esc_url_raw($input['header_logo']);
    }
    return $sanitized;
}

function my_idx_field_maps_cb() {
    $options = get_option('my_idx_options_maps');
    echo '<input type="text" name="my_idx_options_maps[my_idx_field_maps]" value="' . esc_attr($options['my_idx_field_maps']) . '">';
}
