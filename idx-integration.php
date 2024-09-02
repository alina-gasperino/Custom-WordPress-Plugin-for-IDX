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
 * Plugin Name:       Vestor Filter IDX Integration
 * Plugin URI:        https://vestorfilter.com/
 * Description:       WordPress plugin for custom IDX integration. It provides the all-in-one solution to integrate MLS, display, search, and filter properties.
 * Version:           1.0.0
 * Author:            Alina Gasperino
 * Author URI:        https://vestorfilter.com/support/
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
require plugin_dir_path( __FILE__ ) . 'includes/class-idx-agent-dashboard.php';
require plugin_dir_path( __FILE__ ) . 'vestorfilter/index.php';

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
        'Vestor Filter IDX Settings',             // Page title
        'Vestor Filter IDX Settings',             // Menu title
        'manage_options',             // Capability
        'my_idx_settings',          // Menu slug
        'my_idx_settings_page',     // Function to display the page
        'dashicons-admin-multisite',    // Icon URL
        80                            // Position
    );
}
add_action('admin_menu', 'my_idx_add_admin_menu');

function my_idx_settings_page() {
    ?>
    <div class="wrap idx_settings">
        <h1>Vestor Filter IDX Settings</h1>
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
        if (isset($_GET['tab']) && $_GET['tab'] == 'autotask') {
            include 'settings/settings-autotask.php';
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
            include 'settings/settings-general.php';
        }
        ?>
    </div>
    <?php
}

function my_idx_settings_init() {
    // General settings
    register_setting('my_idx_general', 'my_idx_options_general', 'my_idx_sanitize_callback');
    add_settings_section('my_idx_general_section', '', null, 'my_idx_general');

    add_settings_field('location_area', 'Location/Area', 'location_area_cb', 'my_idx_general', 'my_idx_general_section');
	add_settings_field('location_search', 'Default Location in Search', 'location_search_cb', 'my_idx_general', 'my_idx_general_section');
	add_settings_field('search_results_view', 'Default Search Results View', 'search_results_view_cb', 'my_idx_general', 'my_idx_general_section');
	add_settings_field('header_logo', 'Header Logo', 'header_logo_cb', 'my_idx_general', 'my_idx_general_section');
	add_settings_field('company_logo', 'Company Logo', 'company_logo_cb', 'my_idx_general', 'my_idx_general_section');
	add_settings_field('footer_text', 'Footer Text', 'footer_text_cb', 'my_idx_general', 'my_idx_general_section');
	add_settings_field('gtm', 'Google Tag Manager', 'gtm_cb', 'my_idx_general', 'my_idx_general_section');
	add_settings_field('ga', 'Google Analytics ID', 'ga_cb', 'my_idx_general', 'my_idx_general_section');
	add_settings_field('mc_key', 'Mailchimp API Key', 'mc_key_cb', 'my_idx_general', 'my_idx_general_section');
	add_settings_field('mc_aud_id', 'Mailchimp Audience ID', 'mc_aud_id_cb', 'my_idx_general', 'my_idx_general_section');
	add_settings_field('twilio_phone', 'Twilio Phone Number', 'twilio_phone_cb', 'my_idx_general', 'my_idx_general_section');

    // Map settings
    register_setting('my_idx_maps', 'my_idx_options_maps', 'my_idx_sanitize_callback');
    add_settings_section('my_idx_maps_section', '', null, 'my_idx_maps');

    add_settings_field('ne_boundary', 'NE Boundary Coordinates', 'ne_boundary_cb', 'my_idx_maps', 'my_idx_maps_section');
	add_settings_field('sw_boundary', 'SW Boundary Coordinates', 'sw_boundary_cb', 'my_idx_maps', 'my_idx_maps_section');
	add_settings_field('states_allowed', 'States Allowed (abbrevation)', 'states_allowed_cb', 'my_idx_maps', 'my_idx_maps_section');
	add_settings_field('geocoding_api', 'Geocoding API Key', 'geocoding_api_cb', 'my_idx_maps', 'my_idx_maps_section');
	add_settings_field('use_geocode_for_cities', 'Use Geocoding for cities', 'use_geocode_for_cities_cb', 'my_idx_maps', 'my_idx_maps_section');
	add_settings_field('use_geocode_for_properties', 'Use geocoding for properties', 'use_geocode_for_properties_cb', 'my_idx_maps', 'my_idx_maps_section');
	add_settings_field('latitude', 'Latitude', 'latitude_cb', 'my_idx_maps', 'my_idx_maps_section');
	add_settings_field('longitude', 'Longitude', 'longitude_cb', 'my_idx_maps', 'my_idx_maps_section');
	add_settings_field('zoom', 'Zoom', 'zoom_cb', 'my_idx_maps', 'my_idx_maps_section');

    // Email settings
    register_setting('my_idx_emails', 'my_idx_options_emails', 'my_idx_sanitize_callback');
    add_settings_section('my_idx_emails_section', '', null, 'my_idx_emails');

    add_settings_field('from_email', 'Email From: Address', 'from_email_cb', 'my_idx_emails', 'my_idx_emails_section');
    add_settings_field('from_name', 'Email From: Name', 'from_name_cb', 'my_idx_emails', 'my_idx_emails_section');
    add_settings_field('reply_email', 'Email Reply-To: Address', 'reply_email_cb', 'my_idx_emails', 'my_idx_emails_section');
    add_settings_field('email_footer', 'Email Footer Text', 'email_footer_cb', 'my_idx_emails', 'my_idx_emails_section');
    add_settings_field('welcome_footer', 'Welcome Email Footer', 'welcome_footer_cb', 'my_idx_emails', 'my_idx_emails_section');
    add_settings_field('agency_address', 'Agency Address', 'agency_address_cb', 'my_idx_emails', 'my_idx_emails_section');

    //SMS settings
    register_setting('my_idx_sms', 'my_idx_options_sms', 'my_idx_sanitize_callback');
    add_settings_section('my_idx_sms_section', '', null, 'my_idx_sms');

    add_settings_field('message_1', 'Onboard Message 1', 'message_1_cb', 'my_idx_sms', 'my_idx_sms_section');
    add_settings_field('message_2', 'Onboard Message 2', 'message_2_cb', 'my_idx_sms', 'my_idx_sms_section');
    add_settings_field('message_3', 'Onboard Message 3', 'message_3_cb', 'my_idx_sms', 'my_idx_sms_section');
    add_settings_field('message_4', 'Onboard Message 4', 'message_4_cb', 'my_idx_sms', 'my_idx_sms_section');
    add_settings_field('forward_email', 'Forward Replies to (email)', 'forward_email_cb', 'my_idx_sms', 'my_idx_sms_section');
    add_settings_field('sms_signature', 'SMS Message Signature', 'sms_signature_cb', 'my_idx_sms', 'my_idx_sms_section');

    //Filters settings
    register_setting('my_idx_filters', 'my_idx_options_filters', 'my_idx_sanitize_callback');
    add_settings_section('my_idx_filters_section', '', null, 'my_idx_filters');

    add_settings_field('available_lot_sizes', 'Available Lot Sizes', 'available_lot_size_cb', 'my_idx_filters', 'my_idx_filters_section');
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

function search_results_view_cb() {
    $options = get_option('my_idx_options_general');
    $selected = $options['search_results_view'] ?? '';
    ?>
    <select multiple="1" name="my_idx_options_general[search_results_view]">
        <option value="map" <?php selected($selected, 'map'); ?>>Map</option>
        <option value="grid" <?php selected($selected, 'grid'); ?>>Grid</option>
    </select>
    <?php
}

function company_logo_cb() {
    $options = get_option('my_idx_options_general');
    $file_url = $options['company_logo'] ?? '';
    ?>
    <div class="file-upload-wrapper">
        <input type="hidden" id="company_logo" name="my_idx_options_general[company_logo]" value="<?php echo esc_url($file_url); ?>" />

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

        <button type="button" class="button upload-button">Upload Image</button>
        <button type="button" class="button edit-button" style="display: <?php echo $file_url ? 'inline-block' : 'none'; ?>;">Edit</button>
    </div>
    <?php
}

function footer_text_cb() {
    $options = get_option('my_idx_options_general');
	$textarea_value = $options['footer_text'] ?? '';
	?>
	<textarea id="footer_text" name="my_idx_options_general[footer_text]" rows="5" cols="50"style="width: 100%;"><?php echo esc_textarea($textarea_value); ?></textarea>
	<?php
}

function gtm_cb() {
    $options = get_option('my_idx_options_general');
    echo '<input type="text" name="my_idx_options_general[gtm]" value="' . esc_attr($options['gtm']) . '">';
}

function ga_cb() {
    $options = get_option('my_idx_options_general');
    echo '<input type="text" name="my_idx_options_general[ga]" value="' . esc_attr($options['ga']) . '">';
}

function mc_key_cb() {
    $options = get_option('my_idx_options_general');
    echo '<input type="text" name="my_idx_options_general[mc_key]" value="' . esc_attr($options['mc_key']) . '">';
}

function mc_aud_id_cb() {
    $options = get_option('my_idx_options_general');
    echo '<input type="text" name="my_idx_options_general[mc_aud_id]" value="' . esc_attr($options['mc_aud_id']) . '">';
}

function twilio_phone_cb() {
    $options = get_option('my_idx_options_general');
    echo '<input type="text" name="my_idx_options_general[twilio_phone]" value="' . esc_attr($options['twilio_phone']) . '">';
}

function ne_boundary_cb() {
    $options = get_option('my_idx_options_maps');
    echo '<input type="text" name="my_idx_options_maps[ne_boundary]" value="' . esc_attr($options['ne_boundary']) . '">';
}

function sw_boundary_cb() {
    $options = get_option('my_idx_options_maps');
    echo '<input type="text" name="my_idx_options_maps[sw_boundary]" value="' . esc_attr($options['sw_boundary']) . '">';
}

function states_allowed_cb() {
    $options = get_option('my_idx_options_maps');
    echo '<input type="text" name="my_idx_options_maps[states_allowed]" value="' . esc_attr($options['states_allowed']) . '">';
}

function geocoding_api_cb() {
    $options = get_option('my_idx_options_maps');
    echo '<input type="text" name="my_idx_options_maps[geocoding_api]" value="' . esc_attr($options['geocoding_api']) . '">';
}

function use_geocode_for_cities_cb() {
    $options = get_option('my_idx_options_maps');
    $checked = !empty($options['use_geocode_for_cities']) ? 'checked' : '';
    ?>
    <input type="checkbox" id="use_geocode_for_cities" name="my_idx_options_maps[use_geocode_for_cities]" value="1" <?php echo $checked; ?> />
    <label for="use_geocode_for_cities">Will send users to maps based on Google's location data, instead of the properties found.</label>
    <?php
}

function use_geocode_for_properties_cb() {
    $options = get_option('my_idx_options_maps');
    $checked = !empty($options['use_geocode_for_properties']) ? 'checked' : '';
    ?>
    <input type="checkbox" id="use_geocode_for_properties" name="my_idx_options_maps[use_geocode_for_properties]" value="1" <?php echo $checked; ?> />
    <label for="use_geocode_for_properties"> Looks up property coordinates based on MLS address instead of the latitude and longitude fields. Only use if your MLS is not providing complete coordinate data.</label>
    <?php
}

function latitude_cb() {
    $options = get_option('my_idx_options_maps');
    echo '<input type="text" name="my_idx_options_maps[latitude]" value="' . esc_attr($options['latitude']) . '">';
}

function longitude_cb() {
    $options = get_option('my_idx_options_maps');
    echo '<input type="text" name="my_idx_options_maps[longitude]" value="' . esc_attr($options['longitude']) . '">';
}

function zoom_cb() {
    $options = get_option('my_idx_options_maps');
    echo '<input type="text" name="my_idx_options_maps[zoom]" value="' . esc_attr($options['zoom']) . '">';
}

function from_email_cb() {
    $options = get_option('my_idx_options_emails');
    echo '<input type="text" name="my_idx_options_emails[from_email]" value="' . esc_attr($options['from_email']) . '">';
}

function from_name_cb() {
    $options = get_option('my_idx_options_emails');
    echo '<input type="text" name="my_idx_options_emails[from_name]" value="' . esc_attr($options['from_name']) . '">';
}

function reply_email_cb() {
    $options = get_option('my_idx_options_emails');
    echo '<input type="text" name="my_idx_options_emails[reply_email]" value="' . esc_attr($options['reply_email']) . '">';
}

function email_footer_cb() {
    $options = get_option('my_idx_options_emails');
	$textarea_value = $options['email_footer'] ?? '';
	?>
	<textarea id="email_footer" name="my_idx_options_emails[email_footer]" rows="5" cols="50"style="width: 100%;"><?php echo esc_textarea($textarea_value); ?></textarea>
	<?php
}

function welcome_footer_cb() {
    $options = get_option('my_idx_options_emails');
	$textarea_value = $options['welcome_footer'] ?? '';
	?>
	<textarea id="welcome_footer" name="my_idx_options_emails[welcome_footer]" rows="5" cols="50"style="width: 100%;"><?php echo esc_textarea($textarea_value); ?></textarea>
	<?php
}

function agency_address_cb() {
    $options = get_option('my_idx_options_emails');
	$textarea_value = $options['agency_address'] ?? '';
	?>
	<textarea id="agency_address" name="my_idx_options_emails[agency_address]" rows="5" cols="50"style="width: 100%;"><?php echo esc_textarea($textarea_value); ?></textarea>
	<?php
}

function message_1_cb() {
    $options = get_option('my_idx_options_sms');
	$textarea_value = $options['message_1'] ?? '';
	?>
	<textarea id="message_1" name="my_idx_options_sms[message_1]" rows="5" cols="50"style="width: 100%;"><?php echo esc_textarea($textarea_value); ?></textarea>
	<p>Sent 3 minutes after registration or the following day when after 10pm.</p>
    <?php
}
function message_2_cb() {
    $options = get_option('my_idx_options_sms');
	$textarea_value = $options['message_2'] ?? '';
	?>
	<textarea id="message_2" name="my_idx_options_sms[message_2]" rows="5" cols="50"style="width: 100%;"><?php echo esc_textarea($textarea_value); ?></textarea>
	<P>Sent 10 minutes after registration or the following day when after 10pm.</P>
    <?php
}
function message_3_cb() {
    $options = get_option('my_idx_options_sms');
	$textarea_value = $options['message_3'] ?? '';
	?>
	<textarea id="message_3" name="my_idx_options_sms[message_3]" rows="5" cols="50"style="width: 100%;"><?php echo esc_textarea($textarea_value); ?></textarea>
	<p>Sent 2 days after registration.</p>
    <?php
}
function message_4_cb() {
    $options = get_option('my_idx_options_sms');
	$textarea_value = $options['message_4'] ?? '';
	?>
	<textarea id="message_4" name="my_idx_options_sms[message_4]" rows="5" cols="50"style="width: 100%;"><?php echo esc_textarea($textarea_value); ?></textarea>
	<p>Sent 7 days after registration.</p>
    <?php
}
function forward_email_cb() {
    $options = get_option('my_idx_options_sms');
    echo '<input type="text" name="my_idx_options_sms[forward_email]" value="' . esc_attr($options['forward_email']) . '">';
}
function sms_signature_cb() {
    $options = get_option('my_idx_options_sms');
    echo '<input type="text" name="my_idx_options_sms[sms_signature]" value="' . esc_attr($options['sms_signature']) . '">';
}

function available_lot_size_cb() {
    $options = get_option('my_idx_options_filters');
    $lot_sizes = isset($options['available_lot_sizes']) && is_array($options['available_lot_sizes']) 
                ? $options['available_lot_sizes'] 
                : array();

    echo '<div id="available-lot-sizes-container">';
    foreach ($lot_sizes as $index => $size) {
        $num = $index + 1;
        echo '<div class="lot-size-field">';
        echo '<div class="label_wrapper">';
        echo '<h3>Entry '.$num. '</h3>';
        echo '<a class="remove-lot-size">Remove</a>';
        echo '</div>';
        echo '<div class="input_wrapper">';
        echo '<label for="my_idx_options_filters[available_lot_sizes][' . $index . '][size]">Lot Size Label</label>';
        echo '<input type="text" id="my_idx_options_filters[available_lot_sizes][' . $index . '][size]" name="my_idx_options_filters[available_lot_sizes][' . $index . '][size]" value="' . esc_attr($size['size']) . '" placeholder="Size">';
        echo '</div>';
        echo '<div class="input_wrapper">';
        echo '<label for="my_idx_options_filters[available_lot_sizes][' . $index . '][description]">Lot Size Value</label>';
        echo '<input type="text" id="my_idx_options_filters[available_lot_sizes][' . $index . '][description]" name="my_idx_options_filters[available_lot_sizes][' . $index . '][description]" value="' . esc_attr($size['description']) . '" placeholder="Description">';
        echo '</div>';
        echo '<div class="input_wrapper">';
        echo '<label for="my_idx_options_filters[available_lot_sizes][' . $index . '][range]">Actual Lot Size Range</label>';
        echo '<input type="text" id="my_idx_options_filters[available_lot_sizes][' . $index . '][range]" name="my_idx_options_filters[available_lot_sizes][' . $index . '][range]" value="' . esc_attr($size['range']) . '" placeholder="Actual Lot Size Range">';
        echo '</div>';
        // Add Category field
        echo '<div class="categories-container input_wrapper">';
        echo '<label for="my_idx_options_filters[categories]">Lot Categories in RETS</label>';
        echo '<div class="categories">';
        if (isset($size['categories']) && is_array($size['categories'])) {
            foreach ($size['categories'] as $cat_index => $category) {
                echo '<div class="category-field">';
                echo '<input type="text" name="my_idx_options_filters[available_lot_sizes][' . $index . '][categories][' . $cat_index . ']" value="' . esc_attr($category) . '" placeholder="Category">';
                echo '<a class="remove-category"><i class="fa fa-times-circle"></i></a>';
                echo '</div>';
            }
        }
        echo '</div>';
        echo '</div>';
        echo '<button type="button" class="add-category">Add Category</button>';
        echo '</div>';
    }
    echo '</div>';
    echo '<button type="button" id="add-lot-size">Add Lot Size</button>';
}

// Enqueue the media uploader script
function idx_media_uploader() {
    wp_enqueue_media();
    wp_enqueue_script('idx-media-uploader', plugin_dir_url( __FILE__ ) . 'js/media-uploader.js', array('jquery'), null, true);
    wp_enqueue_script('repeatable-filters', plugin_dir_url( __FILE__ ) . 'js/repeatable-filters.js', array('jquery'), null, true);
}
add_action('admin_enqueue_scripts', 'idx_media_uploader');

function idx_styles() {
	wp_enqueue_style('style', plugin_dir_url( __FILE__ ) . 'css/dashboard-style.css', array(), 1.0);
    wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css', array(), 1.0);
}
add_action('admin_enqueue_scripts', 'idx_styles');

function my_idx_sanitize_callback($input) {
    $sanitized = array();
	$sanitized['use_geocode_for_cities'] = !empty($input['use_geocode_for_cities']) ? 1 : 0;
	$sanitized['use_geocode_for_properties'] = !empty($input['use_geocode_for_properties']) ? 1 : 0;
    if (isset($input['location_area'])) {
        $sanitized['location_area'] = sanitize_text_field($input['location_area']);
    }
    if (isset($input['location_search'])) {
        $sanitized['location_search'] = sanitize_text_field($input['location_search']);
    }
	if (isset($input['search_results_view'])) {
        $sanitized['search_results_view'] = sanitize_text_field($input['search_results_view']);
    }
    if (isset($input['header_logo'])) {
        $sanitized['header_logo'] = esc_url_raw($input['header_logo']);
    }
	if (isset($input['company_logo'])) {
        $sanitized['company_logo'] = esc_url_raw($input['company_logo']);
    }
	if (isset($input['footer_text'])) {
        $sanitized['footer_text'] = sanitize_textarea_field($input['footer_text']);
    }
	if (isset($input['gtm'])) {
        $sanitized['gtm'] = sanitize_text_field($input['gtm']);
    }
	if (isset($input['ga'])) {
		$sanitized['ga'] = sanitize_text_field($input['ga']);
	}
	if (isset($input['mc_key'])) {
		$sanitized['mc_key'] = sanitize_text_field($input['mc_key']);
	}
	if (isset($input['mc_aud_id'])) {
		$sanitized['mc_aud_id'] = sanitize_text_field($input['mc_aud_id']);
	}
	if (isset($input['twilio_phone'])) {
		$sanitized['twilio_phone'] = sanitize_text_field($input['twilio_phone']);
	}
	if (isset($input['ne_boundary'])) {
		$sanitized['ne_boundary'] = sanitize_text_field($input['ne_boundary']);
	}
	if (isset($input['sw_boundary'])) {
		$sanitized['sw_boundary'] = sanitize_text_field($input['sw_boundary']);
	}
	if (isset($input['states_allowed'])) {
		$sanitized['states_allowed'] = sanitize_text_field($input['states_allowed']);
	}
	if (isset($input['geocoding_api'])) {
		$sanitized['geocoding_api'] = sanitize_text_field($input['geocoding_api']);
	}
	if (isset($input['zoom'])) {
		$sanitized['zoom'] = sanitize_text_field($input['zoom']);
	}
	if (isset($input['latitude'])) {
		$sanitized['latitude'] = sanitize_text_field($input['latitude']);
	}
	if (isset($input['longitude'])) {
		$sanitized['longitude'] = sanitize_text_field($input['longitude']);
	}
    if (isset($input['from_email'])) {
        $sanitized['from_email'] = sanitize_text_field($input['from_email']);
    }
    if (isset($input['from_name'])) {
        $sanitized['from_name'] = sanitize_text_field($input['from_name']);
    }
    if (isset($input['reply_email'])) {
        $sanitized['reply_email'] = sanitize_text_field($input['reply_email']);
    }
    if (isset($input['email_footer'])) {
        $sanitized['email_footer'] = sanitize_textarea_field($input['email_footer']);
    }
    if (isset($input['welcome_footer'])) {
        $sanitized['welcome_footer'] = sanitize_textarea_field($input['welcome_footer']);
    }
    if (isset($input['agency_address'])) {
        $sanitized['agency_address'] = sanitize_textarea_field($input['agency_address']);
    }
    if (isset($input['message_1'])) {
        $sanitized['message_1'] = sanitize_textarea_field($input['message_1']);
    }
    if (isset($input['message_2'])) {
        $sanitized['message_2'] = sanitize_textarea_field($input['message_2']);
    }
    if (isset($input['message_3'])) {
        $sanitized['message_3'] = sanitize_textarea_field($input['message_3']);
    }
    if (isset($input['message_4'])) {
        $sanitized['message_4'] = sanitize_textarea_field($input['message_4']);
    }
    if (isset($input['forward_email'])) {
        $sanitized['forward_email'] = sanitize_text_field($input['forward_email']);
    }
    if (isset($input['sms_signature'])) {
        $sanitized['sms_signature'] = sanitize_text_field($input['sms_signature']);
    }
    if (isset($input['available_lot_sizes']) && is_array($input['available_lot_sizes'])) {
        foreach ($input['available_lot_sizes'] as $index => $size) {
            $sanitized['available_lot_sizes'][$index]['size'] = sanitize_text_field($size['size']);
            $sanitized['available_lot_sizes'][$index]['description'] = sanitize_textarea_field($size['description']);

            // Sanitize categories
            if (isset($size['categories']) && is_array($size['categories'])) {
                $sanitized['available_lot_sizes'][$index]['categories'] = array_map('sanitize_text_field', $size['categories']);
            } else {
                $sanitized['available_lot_sizes'][$index]['categories'] = array();
            }
        }
    }
    return $sanitized;
}
