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
    register_setting('my_idx_general', 'my_idx_options_general');
    add_settings_section('my_idx_general_section', 'General Settings', null, 'my_idx_general');

    add_settings_field('my_idx_field_example', 'Example Field', 'my_idx_field_example_cb', 'my_idx_general', 'my_idx_general_section');

    // Advanced settings
    register_setting('my_idx_advanced', 'my_idx_options_advanced');
    add_settings_section('my_idx_advanced_section', 'Advanced Settings', null, 'my_idx_advanced');

    add_settings_field('my_idx_field_advanced', 'Advanced Field', 'my_idx_field_advanced_cb', 'my_idx_advanced', 'my_idx_advanced_section');
}
add_action('admin_init', 'my_idx_settings_init');

function my_idx_field_example_cb() {
    $options = get_option('my_idx_options_general');
    echo '<input type="text" name="my_idx_options_general[my_idx_field_example]" value="' . esc_attr($options['my_idx_field_example']) . '">';
}

function my_idx_field_advanced_cb() {
    $options = get_option('my_idx_options_advanced');
    echo '<input type="text" name="my_idx_options_advanced[my_idx_field_advanced]" value="' . esc_attr($options['my_idx_field_advanced']) . '">';
}
