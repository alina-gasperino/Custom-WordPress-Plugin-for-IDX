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
require plugin_dir_path( __FILE__ ) . 'includes/class-preinstall.php';
require plugin_dir_path( __FILE__ ) . 'includes/page-templates-registration.php';
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




global $wpdb;
$wpdb->query( '
		CREATE TABLE IF NOT EXISTS `wp_vflog` (
			`ID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			`log_action` varchar(20) NOT NULL,
			`log_time` int(10) unsigned NOT NULL,
			`log_property` int(10) unsigned DEFAULT NULL,
			`log_value` varchar(50) DEFAULT NULL,
			`user_id` bigint(20) unsigned DEFAULT NULL,
			`performed_by` bigint(20) unsigned DEFAULT NULL,
			PRIMARY KEY (`ID`)
		) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;
	');

function my_idx_add_admin_menu() {
    add_menu_page(
        'Vestor Filter IDX Settings',
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
			<a href="?page=my_idx_settings&tab=mls_setup" class="nav-tab <?php echo (isset($_GET['tab']) && $_GET['tab'] == 'mls_setup') ? 'nav-tab-active' : ''; ?>">MLS Setup</a>
			<a href="?page=my_idx_settings&tab=maps" class="nav-tab <?php echo (isset($_GET['tab']) && $_GET['tab'] == 'maps') ? 'nav-tab-active' : ''; ?>">Maps</a>
			<a href="?page=my_idx_settings&tab=emails" class="nav-tab <?php echo (isset($_GET['tab']) && $_GET['tab'] == 'emails') ? 'nav-tab-active' : ''; ?>">Emails</a>
			<a href="?page=my_idx_settings&tab=sms" class="nav-tab <?php echo (isset($_GET['tab']) && $_GET['tab'] == 'sms') ? 'nav-tab-active' : ''; ?>">SMS</a>
			<a href="?page=my_idx_settings&tab=filters" class="nav-tab <?php echo (isset($_GET['tab']) && $_GET['tab'] == 'filters') ? 'nav-tab-active' : ''; ?>">Filters</a>
			<a href="?page=my_idx_settings&tab=templates" class="nav-tab <?php echo (isset($_GET['tab']) && $_GET['tab'] == 'templates') ? 'nav-tab-active' : ''; ?>">Templates</a>
			<a href="?page=my_idx_settings&tab=tools" class="nav-tab <?php echo (isset($_GET['tab']) && $_GET['tab'] == 'tools') ? 'nav-tab-active' : ''; ?>">Leads</a>
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
        elseif (isset($_GET['tab']) && $_GET['tab'] == 'mls_setup') {
            include 'settings/settings-mls_setup.php';
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
    add_settings_field('form_install', 'Install Forms', 'form_install_cb', 'my_idx_general', 'my_idx_general_section');
    add_settings_field('label', '', 'label_cb', 'my_idx_general', 'my_idx_general_section');
	add_settings_field('twilio_phone', 'Twilio Phone Number', 'twilio_phone_cb', 'my_idx_general', 'my_idx_general_section');
    add_settings_field('aws_bucket', 'AWS Bucket Name', 'aws_bucket_cb', 'my_idx_general', 'my_idx_general_section');
    add_settings_field('aws_url', 'AWS URL', 'aws_url_cb', 'my_idx_general', 'my_idx_general_section');
    add_settings_field('aws_region', 'AWS Region', 'aws_region_cb', 'my_idx_general', 'my_idx_general_section');
    add_settings_field('mc_key', 'Mailchimp API Key', 'mc_key_cb', 'my_idx_general', 'my_idx_general_section');
	add_settings_field('mc_aud_id', 'Mailchimp Audience ID', 'mc_aud_id_cb', 'my_idx_general', 'my_idx_general_section');
	add_settings_field('gtm', 'Google Tag Manager', 'gtm_cb', 'my_idx_general', 'my_idx_general_section');
	add_settings_field('ga', 'Google Analytics ID', 'ga_cb', 'my_idx_general', 'my_idx_general_section');	

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
    add_settings_field('available_status_options', 'Available Status Options', 'available_status_options_cb', 'my_idx_filters', 'my_idx_filters_section');
    add_settings_field('best_price_per_sqft', 'Best price per square foot', 'best_price_per_sqft_cb', 'my_idx_filters', 'my_idx_filters_section');
    add_settings_field('biggest_price_seven', 'Biggest price drop last 7 days', 'biggest_price_seven_cb', 'my_idx_filters', 'my_idx_filters_section');
    add_settings_field('best_price_per_acre', 'Best price per acre', 'best_price_per_acre_cb', 'my_idx_filters', 'my_idx_filters_section');
    add_settings_field('best_priced_condo', 'Best priced condo', 'best_priced_condo_cb', 'my_idx_filters', 'my_idx_filters_section');
    add_settings_field('best_price_per_unit', 'Best price per unit', 'best_price_per_unit_cb', 'my_idx_filters', 'my_idx_filters_section');
    add_settings_field('best_price_per_bedroom', 'Best price per bedroom', 'best_price_per_bedroom_cb', 'my_idx_filters', 'my_idx_filters_section');
    add_settings_field('extra_living', 'Extra Living Quarters', 'extra_living_cb', 'my_idx_filters', 'my_idx_filters_section');
    add_settings_field('newest_market', 'Newest on the market', 'newest_market_cb', 'my_idx_filters', 'my_idx_filters_section');
    add_settings_field('longest_market', 'Longest on the market', 'longest_market_cb', 'my_idx_filters', 'my_idx_filters_section');
    add_settings_field('smart_fixer_list', 'Smart Fixer List', 'smart_fixer_list_cb', 'my_idx_filters', 'my_idx_filters_section');
    add_settings_field('foreclosure_list', 'Foreclosure List', 'foreclosure_list_cb', 'my_idx_filters', 'my_idx_filters_section');
    add_settings_field('short_sale_list', 'Short Sale List', 'short_sale_list_cb', 'my_idx_filters', 'my_idx_filters_section');
    add_settings_field('auction_list', 'Auction List', 'auction_list_cb', 'my_idx_filters', 'my_idx_filters_section');

    //Templates settings
    register_setting('my_idx_templates', 'my_idx_options_templates', 'my_idx_sanitize_callback');
    add_settings_section('my_idx_templates_section', '', null, 'my_idx_templates');

    add_settings_field('single_property', 'Single Property', 'single_property_cb', 'my_idx_templates', 'my_idx_templates_section');
    add_settings_field('search_page', 'Search Page', 'search_page_cb', 'my_idx_templates', 'my_idx_templates_section');
    add_settings_field('saved_properties', 'Favorites/Saved Properties Page', 'saved_properties_cb', 'my_idx_templates', 'my_idx_templates_section');

    //Tools settings
    register_setting('my_idx_tools', 'my_idx_options_tools', 'my_idx_sanitize_callback');
    add_settings_section('my_idx_tools_section', '', null, 'my_idx_tools');

    add_settings_field('download_lead_data', 'Download Lead Data', 'download_lead_data_cb', 'my_idx_tools', 'my_idx_tools_section');

    // Auto Tasks settings
    register_setting('my_idx_autotask', 'my_idx_options_autotask', 'my_idx_sanitize_callback');
    add_settings_section('my_idx_autotask_section', '', null, 'my_idx_autotask');

    add_settings_field('new_leads', 'New Leads', 'new_leads_cb', 'my_idx_autotask', 'my_idx_autotask_section');
    add_settings_field('hot_leads', 'Hot Leads', 'hot_leads_cb', 'my_idx_autotask', 'my_idx_autotask_section');
    add_settings_field('warm_leads', 'Warm Leads', 'warm_leads_cb', 'my_idx_autotask', 'my_idx_autotask_section');
    add_settings_field('cold_leads', 'Cold Leads', 'cold_leads_cb', 'my_idx_autotask', 'my_idx_autotask_section');
    add_settings_field('sold_leads', 'Sold Leads', 'sold_leads_cb', 'my_idx_autotask', 'my_idx_autotask_section');
}
add_action('admin_init', 'my_idx_settings_init');

function location_area_cb() {
    $options = get_option('my_idx_options_general');
    echo '<input type="text" name="my_idx_options_general[location_area]" value="' . esc_attr($options['location_area']) . '">';
}

function location_search_cb() {
    $options = get_option('my_idx_options_general');
    $selected = $options['location_search'] ?? '';
    $locate_options = get_locations();
    ?>
    <select name="my_idx_options_general[location_search]">
        <?php foreach ($locate_options as $key => $locate) {?>
            <option value=<?php echo $key; ?> <?php selected($selected, $key); ?>><?php echo $locate; ?></option>
        <?php
        }
        ?>
    </select>
    <?php
}

function search_results_view_cb() {
    $options = get_option('my_idx_options_general');
    $selected = $options['search_results_view'] ?? '';
    ?>
    <select multiple="1" name="my_idx_options_general[search_results_view]">
        <option value="map" <?php selected($selected, 'map'); ?>>Map</option>
        <option value="list" <?php selected($selected, 'grid'); ?>>Grid</option>
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
function formidable_process_xml_upload() {
    // Check if the uploaded file is valid.
    if (empty($_FILES['formidable_xml_file']['tmp_name'])) {
        echo '<div class="error"><p>No file uploaded.</p></div>';
        return;
    }

    $file = $_FILES['formidable_xml_file'];

    // Check if Formidable is active and its classes are loaded.
    if (!class_exists('FrmXMLHelper')) {
        echo '<div class="error"><p>Formidable Forms plugin is not active.</p></div>';
        return;
    }

    // Ensure the file is an XML file.
    $file_type = wp_check_filetype($file['name']);
    if ($file_type['ext'] !== 'xml') {
        echo '<div class="error"><p>Only XML files are allowed.</p></div>';
        return;
    }

    // Read the XML file content.
    $xml_content = file_get_contents($file['tmp_name']);
    if (!$xml_content) {
        echo '<div class="error"><p>Unable to read the XML file.</p></div>';
        return;
    }

    // Load XML and use Formidable's import function.
    $xml = simplexml_load_string($xml_content);
    if (!$xml) {
        echo '<div class="error"><p>Invalid XML format.</p></div>';
        return;
    }

    // Import the XML file content into Formidable.
    if (class_exists('FrmXMLHelper')) {
        $result = FrmXMLHelper::import_xml($xml);
        if (is_wp_error($result)) {
            echo '<div class="error"><p>Error importing XML: ' . $result->get_error_message() . '</p></div>';
        } else {
            echo '<div class="updated"><p>Formidable form imported successfully.</p></div>';
        }
    }
}
function form_install_cb() {
    $plugin_slug = 'formidable';
    require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
    require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

    $plugin_status = install_plugin_install_status(array('slug' => $plugin_slug));

    if ($plugin_status['status'] == 'install') {
        echo '<a href="' . esc_url(wp_nonce_url(
            self_admin_url('update.php?action=install-plugin&plugin=' . $plugin_slug),
            'install-plugin_' . $plugin_slug
        )) . '" class="button button-primary">Install Formidable Forms</a>';
    } elseif ($plugin_status['status'] == 'update_available') {
        echo '<a href="' . esc_url(wp_nonce_url(
            self_admin_url('update.php?action=upgrade-plugin&plugin=' . $plugin_slug),
            'upgrade-plugin_' . $plugin_slug
        )) . '" class="button button-secondary">Update Formidable Forms</a>';
    } else {
        echo '<p>Formidable Forms is already installed and up to date. Upload Formidable XML File.</p>';
        if (isset($_POST['submit']) && isset($_FILES['formidable_xml_file'])) {
            formidable_process_xml_upload();
        }
        echo '
        <form method="post" enctype="multipart/form-data">
            <input type="file" name="formidable_xml_file" accept=".xml" required>
            <br><br>
            <input type="submit" name="submit" value="Upload and Import" class="button button-primary">
        </form>';
    }
}
function label_cb() {
    echo '<tr class="info_wrapper"><td colspan="2"><h4>Third-Party Integration (Optional)</h4><hr /></td></tr>';
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
function aws_bucket_cb() {
    $options = get_option('my_idx_options_general');
    echo '<input type="text" name="my_idx_options_general[aws_bucket]" value="' . esc_attr($options['aws_bucket']) . '">';
}
function aws_url_cb() {
    $options = get_option('my_idx_options_general');
    echo '<input type="text" name="my_idx_options_general[aws_url]" value="' . esc_attr($options['aws_url']) . '">';
}
function aws_region_cb() {
    $options = get_option('my_idx_options_general');
    echo '<input type="text" name="my_idx_options_general[aws_region]" value="' . esc_attr($options['aws_region']) . '">';
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
    echo '<p>Click <a href="https://developers.google.com/maps/documentation/javascript/get-api-key" target="_blank">here</a> to learn more about obtaining and adding your Google Maps account.</p>';
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
    $default = [
        [
            'label' => '0 to 2,999 SqFt',
            'value' => '2999sqft',
            'range' => '',
            'terms' => [4161]
        ]
    ];
    $lot_filters = get_index_values('lot-size') ?: []; // Replace with actual function to get lot sizes
    $lot_options = [];
    $lot_sizes = isset($options['available_lot_sizes']) && is_array($options['available_lot_sizes'])
                ? $options['available_lot_sizes']
                : $default;

    echo '<div id="available-lot-sizes-container">';
    
    foreach ($lot_sizes as $index => $size) {
        $num = $index + 1;
        echo '<div class="lot-size-field">';
        echo '<div class="label_wrapper">';
        echo '<h3>Entry ' . $num . '</h3>';
        echo '<a class="remove-lot-size">Remove</a>';
        echo '</div>';
        echo '<div class="input_wrapper">';
        echo '<label for="my_idx_options_filters[available_lot_sizes][' . $index . '][label]">Lot Size Label</label>';
        echo '<input type="text" id="my_idx_options_filters[available_lot_sizes][' . $index . '][label]" name="my_idx_options_filters[available_lot_sizes][' . $index . '][label]" value="' . esc_attr($size['label']) . '" placeholder="Size">';
        echo '</div>';
        echo '<div class="input_wrapper">';
        echo '<label for="my_idx_options_filters[available_lot_sizes][' . $index . '][value]">Lot Size Value</label>';
        echo '<input type="text" id="my_idx_options_filters[available_lot_sizes][' . $index . '][value]" name="my_idx_options_filters[available_lot_sizes][' . $index . '][value]" value="' . esc_attr($size['value']) . '" placeholder="Description">';
        echo '</div>';
        echo '<div class="input_wrapper">';
        echo '<label for="my_idx_options_filters[available_lot_sizes][' . $index . '][range]">Actual Lot Size Range</label>';
        echo '<input type="text" id="my_idx_options_filters[available_lot_sizes][' . $index . '][range]" name="my_idx_options_filters[available_lot_sizes][' . $index . '][range]" value="' . esc_attr($size['range']) . '" placeholder="Actual Lot Size Range">';
        echo '</div>';
        
        // Add Category field
        echo '<div class="categories-container input_wrapper">';
        echo '<label>Lot Categories in RETS</label>';
        echo '<div class="categories">';
        
        if (isset($size['terms']) && is_array($size['terms'])) {
            foreach ($size['terms'] as $catIndex => $catId) {
                echo '<div class="category-field">';
                echo '<select name="my_idx_options_filters[available_lot_sizes][' . $index . '][terms][' . $catIndex . ']">';
                
                foreach ($lot_filters as $filter) {
                    $selected = selected($filter->ID, $catId, false);
                    echo '<option value="' . esc_attr($filter->ID) . '" ' . $selected . '>' . esc_html($filter->value) . '</option>';
                }
                
                echo '</select>';
                echo '<a class="remove-category"><i class="fa fa-times-circle"></i></a>';
                echo '</div>';
            }
        } else {
            // If no categories are set, display a default empty select
            echo '<div class="category-field">';
            echo '<select name="my_idx_options_filters[available_lot_sizes][' . $index . '][terms][0]">';
            
            foreach ($lot_filters as $filter) {
                echo '<option value="' . esc_attr($filter->ID) . '">' . esc_html($filter->value) . '</option>';
            }
            
            echo '</select>';
            echo '<a class="remove-category"><i class="fa fa-times-circle"></i></a>';
            echo '</div>';
        }
        
        echo '</div>';
        echo '</div>';
        echo '<button type="button" class="add-category">Add Category</button>';
        echo '</div>';
    }
    
    echo '</div>';
    echo '<button type="button" id="add-lot-size">Add Lot Size</button>';
}

function available_status_options_cb() {
    $options = get_option('my_idx_options_filters');
    $default = [
        [
            'label' => 'Active',
            'value' => 'active',
            'terms' => [4145]
        ]
    ];
    $status_filters = get_index_values( 'status', '`value` ASC' ) ?: [];
    $status_options = isset($options['available_status_options']) && is_array($options['available_status_options']) 
                ? $options['available_status_options'] 
                : $default;
    echo '<div id="available-status-options-container">';    
    foreach ($status_options as $index => $size) {
        $num = $index + 1;
        echo '<div class="status-option-field">';
        echo '<div class="label_wrapper">';
        echo '<h3>Entry '.$num. '</h3>';
        echo '<a class="remove-status-option">Remove</a>';
        echo '</div>';
        echo '<div class="input_wrapper">';
        echo '<label for="my_idx_options_filters[available_status_options][' . $index . '][label]">Status Label</label>';
        echo '<input type="text" id="my_idx_options_filters[available_status_options][' . $index . '][label]" name="my_idx_options_filters[available_status_options][' . $index . '][label]" value="' . esc_attr($size['label']) . '" placeholder="Size">';
        echo '</div>';
        echo '<div class="input_wrapper">';
        echo '<label for="my_idx_options_filters[available_status_options][' . $index . '][value]">Status Value</label>';
        echo '<input type="text" id="my_idx_options_filters[available_status_options][' . $index . '][value]" name="my_idx_options_filters[available_status_options][' . $index . '][value]" value="' . esc_attr($size['value']) . '" placeholder="Description">';
        echo '</div>';
        // Add Category field
        echo '<div class="categories-container input_wrapper">';
        echo '<label for="my_idx_options_filters[categories]">Status Categories in RETS</label>';
        echo '<div class="categories">';        
        if (isset($size['terms']) && is_array($size['terms'])) {
            foreach ($size['terms'] as $catIndex => $catId) {
                echo '<div class="category-field">';
                echo '<select name="my_idx_options_filters[available_status_options][' . $index . '][terms][' . $catIndex . ']">';
                
                foreach ($status_filters as $filter) {
                    $selected = selected($filter->ID, $catId, false);
                    echo '<option value="' . esc_attr($filter->ID) . '" ' . $selected . '>' . esc_html($filter->value) . '</option>';
                }
                
                echo '</select>';
                echo '<a class="remove-category"><i class="fa fa-times-circle"></i></a>';
                echo '</div>';
            }
        } else {
            echo "empty";
            // If no categories are set, display a default empty select
            echo '<div class="category-field">';
            echo '<select name="my_idx_options_filters[available_status_options][' . $index . '][terms][0]">';
            
            foreach ($status_filters as $filter) {
                echo '<option value="' . esc_attr($filter->ID) . '">' . esc_html($filter->value) . '</option>';
            }
            
            echo '</select>';
            echo '<a class="remove-category"><i class="fa fa-times-circle"></i></a>';
            echo '</div>';
        }
        
        echo '</div>';
        echo '</div>';
        echo '<button type="button" class="add-category">Add Category</button>';
        echo '</div>';
    }
    
    echo '</div>';
    echo '<button type="button" id="add-status-option">Add Status Option</button>';
}
function best_price_per_sqft_cb() {
    $options = get_option('my_idx_options_filters');
    echo '<input type="text" name="my_idx_options_filters[best_price_per_sqft]" value="' . esc_attr($options['best_price_per_sqft']) . '">';
}
function biggest_price_seven_cb() {
    $options = get_option('my_idx_options_filters');
    echo '<input type="text" name="my_idx_options_filters[biggest_price_seven]" value="' . esc_attr($options['biggest_price_seven']) . '">';
}
function best_price_per_acre_cb() {
    $options = get_option('my_idx_options_filters');
    echo '<input type="text" name="my_idx_options_filters[best_price_per_acre]" value="' . esc_attr($options['best_price_per_acre']) . '">';
}
function best_priced_condo_cb() {
    $options = get_option('my_idx_options_filters');
    echo '<input type="text" name="my_idx_options_filters[best_priced_condo]" value="' . esc_attr($options['best_priced_condo']) . '">';
}
function best_price_per_unit_cb() {
    $options = get_option('my_idx_options_filters');
    echo '<input type="text" name="my_idx_options_filters[best_price_per_unit]" value="' . esc_attr($options['best_price_per_unit']) . '">';
}
function best_price_per_bedroom_cb() {
    $options = get_option('my_idx_options_filters');
    echo '<input type="text" name="my_idx_options_filters[best_price_per_bedroom]" value="' . esc_attr($options['best_price_per_bedroom']) . '">';
}
function extra_living_cb() {
    $options = get_option('my_idx_options_filters');
    echo '<input type="text" name="my_idx_options_filters[extra_living]" value="' . esc_attr($options['extra_living']) . '">';
}
function newest_market_cb() {
    $options = get_option('my_idx_options_filters');
    echo '<input type="text" name="my_idx_options_filters[newest_market]" value="' . esc_attr($options['newest_market']) . '">';
}
function longest_market_cb() {
    $options = get_option('my_idx_options_filters');
    echo '<input type="text" name="my_idx_options_filters[longest_market]" value="' . esc_attr($options['longest_market']) . '">';
}
function smart_fixer_list_cb() {
    $options = get_option('my_idx_options_filters');
    echo '<input type="text" name="my_idx_options_filters[smart_fixer_list]" value="' . esc_attr($options['smart_fixer_list']) . '">';
}
function foreclosure_list_cb() {
    $options = get_option('my_idx_options_filters');
    echo '<input type="text" name="my_idx_options_filters[foreclosure_list]" value="' . esc_attr($options['foreclosure_list']) . '">';
}
function short_sale_list_cb() {
    $options = get_option('my_idx_options_filters');
    echo '<input type="text" name="my_idx_options_filters[short_sale_list]" value="' . esc_attr($options['short_sale_list']) . '">';
}
function auction_list_cb() {
    $options = get_option('my_idx_options_filters');
    echo '<input type="text" name="my_idx_options_filters[auction_list]" value="' . esc_attr($options['auction_list']) . '">';
}

function single_property_cb() {
    $options = get_option('my_idx_options_templates');
    $selected = $options['single_property'] ?? '';
    $pages = get_posts(
        [
            'post_type'      => 'page',
            'nopaging'       => true,
            'posts_per_page' => -1,
            'orderby'        => 'post_title',
            'order'          => 'ASC',
            'parent'         => 0,
        ]
    );
    $page_options = [ '' => '' ];
    foreach ( $pages as $page ) {
        $page_options[ $page->ID ] = $page->post_title;
    }
    ?>
    <select name="my_idx_options_templates[single_property]">
        <?php
        foreach ($page_options as $key => $value) {
            echo '<option value="' . $key . '" ' . selected($selected, $key, false) . '>' . $value . '</option>';
        }
        ?>
    </select>
    <?php
}
function search_page_cb() {
    $options = get_option('my_idx_options_templates');
    $selected = $options['search_page'] ?? '';
    $pages = get_posts(
        [
            'post_type'      => 'page',
            'nopaging'       => true,
            'posts_per_page' => -1,
            'orderby'        => 'post_title',
            'order'          => 'ASC',
            'parent'         => 0,
        ]
    );
    $page_options = [ '' => '' ];
    foreach ( $pages as $page ) {
        $page_options[ $page->ID ] = $page->post_title;
    }
    ?>
    <select name="my_idx_options_templates[search_page]">
        <?php
        foreach ($page_options as $key => $value) {
            echo '<option value="' . $key . '" ' . selected($selected, $key, false) . '>' . $value . '</option>';
        }
        ?>
    </select>
    <?php
}
function saved_properties_cb() {
    $options = get_option('my_idx_options_templates');
    $selected = $options['saved_properties'] ?? '';
    $pages = get_posts(
        [
            'post_type'      => 'page',
            'nopaging'       => true,
            'posts_per_page' => -1,
            'orderby'        => 'post_title',
            'order'          => 'ASC',
            'parent'         => 0,
        ]
    );
    $page_options = [ '' => '' ];
    foreach ( $pages as $page ) {
        $page_options[ $page->ID ] = $page->post_title;
    }
    ?>
    <select name="my_idx_options_templates[saved_properties]">
        <?php
        foreach ($page_options as $key => $value) {
            echo '<option value="' . $key . '" ' . selected($selected, $key, false) . '>' . $value . '</option>';
        }
        ?>
    </select>
    <?php
}

function download_lead_data_cb() {
    echo '<button type="button" id="export" name="export" data-action="export-data" aria-labelledby="export-label">Export to CSV</button>';
}

function new_leads_cb() {
    $options = get_option('my_idx_options_autotask');
    $checked = !empty($options['new_leads']) ? 'checked' : '';
    ?>
    <input type="checkbox" id="new_leads" name="my_idx_options_autotask[new_leads]" value="1" <?php echo $checked; ?> />
    <label for="new_leads">Create New Tasks Automatically For New Leads</label>
    <p><i>Creates four follow up tasks for Agents when a user signs up on the site.</i></p>
    <p>Follow up same day, next day, then on day four and day seven after initial registration.</p>
    <?php
}
function hot_leads_cb() {
    $options = get_option('my_idx_options_autotask');
    $checked = !empty($options['hot_leads']) ? 'checked' : '';
    ?>
    <input type="checkbox" id="hot_leads" name="my_idx_options_autotask[hot_leads]" value="1" <?php echo $checked; ?> />
    <label for="hot_leads">Create Follow-Up Task When Lead Set to "Hot"</label>
    <p><i>Creates one follow-up task a week for 24 weeks after the lead has been tagged "Hot."</i></p>
    <?php
}
function warm_leads_cb() {
    $options = get_option('my_idx_options_autotask');
    $checked = !empty($options['warm_leads']) ? 'checked' : '';
    ?>
    <input type="checkbox" id="warm_leads" name="my_idx_options_autotask[warm_leads]" value="1" <?php echo $checked; ?> />
    <label for="warm_leads">Create Follow-Up Task When Lead Set to "Warm"</label>
    <p><i>Creates one follow-up task a month for 24 months after the lead has been tagged "Warm."</i></p>
    <?php
}
function cold_leads_cb() {
    $options = get_option('my_idx_options_autotask');
    $checked = !empty($options['cold_leads']) ? 'checked' : '';
    ?>
    <input type="checkbox" id="cold_leads" name="my_idx_options_autotask[cold_leads]" value="1" <?php echo $checked; ?> />
    <label for="cold_leads">Create Follow-Up Task When Lead Set to "Cold"</label>
    <p><i>Creates one follow-up task a year for 5 years after the lead has been tagged "Cold."</i></p>
    <?php
}
function sold_leads_cb() {
    $options = get_option('my_idx_options_autotask');
    $checked = !empty($options['sold_leads']) ? 'checked' : '';
    ?>
    <input type="checkbox" id="sold_leads" name="my_idx_options_autotask[sold_leads]" value="1" <?php echo $checked; ?> />
    <label for="sold_leads">Create Follow-Up Task When Lead Set to "Sold"</label>
    <p><i>Creates one follow-up task a year for 5 years after the lead has been tagged "Sold."</i></p>
    <?php
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

function custom_styles() {
	wp_enqueue_style('front', plugin_dir_url( __FILE__ ) . 'css/front.css', array(), wp_get_theme()->get('Version'));
    wp_enqueue_style('lightgallery', plugin_dir_url( __FILE__ ) . 'dist/vendor/lightgallery-2.2.1/css/lightgallery-bundle.css', array(), '2.2.1');
}
add_action('wp_enqueue_scripts', 'custom_styles');

function custom_scripts() {
    if ( defined( 'VF_IMG_URL' ) ) {
		$image_url = VF_IMG_URL;
	} else {
		$upload_dir = wp_upload_dir( 'vf' );
		$image_url = $upload_dir['url'];
	}
    wp_enqueue_script('main', plugin_dir_url( __FILE__ ) . 'js/vestortheme.min.js', array('jquery'), null, true);
    wp_add_inline_script(
		'main',
		'var vfDebugMode = ' . ( 'true' ) . ';',
		true
	);
    wp_add_inline_script(
        'main',
        'var vfLocationData = ' . json_encode( get_all_data() ),
        true
    );
    wp_add_inline_script(
		'main',
		'var apiTokens = ' . json_encode(
			[ 'google' => "AIzaSyDlrcQS4-85JSq7PKUIj4naArhdC6ff5uY" ]
		),
		true
	);
    wp_add_inline_script(
		'main',
		'var vfEndpoints = ' . json_encode( [
			'search'   => \VestorFilter\Search::get_search_query_endpoint(),
			'userMaps' => \VestorFilter\Search::get_user_maps_endpoint(),
			'exact'    => \VestorFilter\Search::get_exact_query_endpoint(),
			'location' => \VestorFilter\Location::get_location_query_endpoint(),
			'cache'    => \VestorFilter\Cache::get_endpoint(),
			'property' => \VestorFilter\Property::base_url(),
			'images'   => untrailingslashit( $image_url ),
		] ),
		true
	);
    wp_add_inline_script(
		'main',
		'var vfAllowLocationless = ' . ( \VestorFilter\Search::is_location_free_search_allowed() ? 'true' : 'false' ),
		true
	);
    wp_add_inline_script(
		'main',
		'var vfFormats = ' . json_encode( \VestorFilter\Filters::get_formats() ),
		true
	);
    wp_add_inline_script(
		'main',
		'var vfPaths = ' . json_encode( [
			'distUrl' => plugin_dir_url( __FILE__ ) . 'dist',
		] ),
		true
	);

    $sources = [];
    if ( defined( 'VF_ALLOWED_FEEDS' ) ) {
        foreach( VF_ALLOWED_FEEDS as $source_id ) {
            $source = new \VestorFilter\Source( $source_id );
            $logo = $source->get_compliance_logo();
            $text = $source->get_compliance_line( '{{ agency }}' );
            if ( $logo || $text ) {
                $sources[$source_id] = [
                    'logo' => $logo,
                    'text' => $text,
                ];
            }
        }
    }
    wp_add_inline_script(
		'main',
		'var vfSources = ' . json_encode( $sources ) . ';',
		true
	);
    // wp_enqueue_script('filter', plugin_dir_url( __FILE__ ) . 'js/filters.js', array('jquery'), null, true);
    // wp_enqueue_script('search', plugin_dir_url( __FILE__ ) . 'js/search.js', array('jquery'), null, true);
    wp_enqueue_script('js-cookie', plugin_dir_url( __FILE__ ) . 'dist/vendor/js.cookie.min.js', array(), '3.0.0-rc.1', true);
    wp_enqueue_script('lightgallery', plugin_dir_url( __FILE__ ) . 'dist/vendor/lightgallery-2.2.1/lightgallery.min.js', array(), '2.2.1', true);
    wp_enqueue_script('lightgallery-zoom', plugin_dir_url( __FILE__ ) . 'dist/vendor/lightgallery-2.2.1/plugins/zoom/lg-zoom.min.js', array( 'lightgallery' ), '2.2.1', true);
    wp_enqueue_script('lightgallery-thumbnails', plugin_dir_url( __FILE__ ) . 'dist/vendor/lightgallery-2.2.1/plugins/thumbnail/lg-thumbnail.min.js', array( 'lightgallery' ), '2.2.1', true);
}
add_action('wp_enqueue_scripts', 'custom_scripts');

function my_idx_sanitize_callback($input) {
    $sanitized = array();
	$sanitized['use_geocode_for_cities'] = !empty($input['use_geocode_for_cities']) ? 1 : 0;
	$sanitized['use_geocode_for_properties'] = !empty($input['use_geocode_for_properties']) ? 1 : 0;
    $sanitized['new_leads'] = !empty($input['new_leads']) ? 1 : 0;
    $sanitized['hot_leads'] = !empty($input['hot_leads']) ? 1 : 0;
    $sanitized['warm_leads'] = !empty($input['warm_leads']) ? 1 : 0;
    $sanitized['cold_leads'] = !empty($input['cold_leads']) ? 1 : 0;
    $sanitized['sold_leads'] = !empty($input['sold_leads']) ? 1 : 0;
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
    if (isset($input['aws_bucket'])) {
		$sanitized['aws_bucket'] = sanitize_text_field($input['aws_bucket']);
	}
    if (isset($input['aws_url'])) {
		$sanitized['aws_url'] = sanitize_text_field($input['aws_url']);
	}
    if (isset($input['aws_region'])) {
		$sanitized['aws_region'] = sanitize_text_field($input['aws_region']);
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
            $sanitized['available_lot_sizes'][$index]['label'] = sanitize_text_field($size['label']);
            $sanitized['available_lot_sizes'][$index]['value'] = sanitize_textarea_field($size['value']);
            $sanitized['available_lot_sizes'][$index]['range'] = sanitize_textarea_field($size['range']);

            // Sanitize categories
            if (isset($size['terms']) && is_array($size['terms'])) {
                $sanitized['available_lot_sizes'][$index]['terms'] = array_map('sanitize_text_field', $size['terms']);
            } else {
                $sanitized['available_lot_sizes'][$index]['terms'] = [];
            }
        }
    }
    if (isset($input['available_status_options']) && is_array($input['available_status_options'])) {
        foreach ($input['available_status_options'] as $index => $status) {
            $sanitized['available_status_options'][$index]['label'] = sanitize_text_field($status['label']);
            $sanitized['available_status_options'][$index]['value'] = sanitize_textarea_field($status['value']);
    
            // Sanitize categories
            if (isset($status['terms']) && is_array($status['terms'])) {
                $sanitized['available_status_options'][$index]['terms'] = array_map('sanitize_text_field', $status['terms']);
            } else {
                $sanitized['available_status_options'][$index]['terms'] = [];
            }
        }
    }
    if (isset($input['best_price_per_sqft'])) {
        $sanitized['best_price_per_sqft'] = sanitize_text_field($input['best_price_per_sqft']);
    }
    if (isset($input['biggest_price_seven'])) {
        $sanitized['biggest_price_seven'] = sanitize_text_field($input['biggest_price_seven']);
    }
    if (isset($input['best_price_per_acre'])) {
        $sanitized['best_price_per_acre'] = sanitize_text_field($input['best_price_per_acre']);
    }
    if (isset($input['best_priced_condo'])) {
        $sanitized['best_priced_condo'] = sanitize_text_field($input['best_priced_condo']);
    }
    if (isset($input['best_price_per_unit'])) {
        $sanitized['best_price_per_unit'] = sanitize_text_field($input['best_price_per_unit']);
    }
    if (isset($input['best_price_per_bedroom'])) {
        $sanitized['best_price_per_bedroom'] = sanitize_text_field($input['best_price_per_bedroom']);
    }
    if (isset($input['extra_living'])) {
        $sanitized['extra_living'] = sanitize_text_field($input['extra_living']);
    }
    if (isset($input['newest_market'])) {
        $sanitized['newest_market'] = sanitize_text_field($input['newest_market']);
    }
    if (isset($input['longest_market'])) {
        $sanitized['longest_market'] = sanitize_text_field($input['longest_market']);
    }
    if (isset($input['smart_fixer_list'])) {
        $sanitized['smart_fixer_list'] = sanitize_text_field($input['smart_fixer_list']);
    }
    if (isset($input['foreclosure_list'])) {
        $sanitized['foreclosure_list'] = sanitize_text_field($input['foreclosure_list']);
    }
    if (isset($input['short_sale_list'])) {
        $sanitized['short_sale_list'] = sanitize_text_field($input['short_sale_list']);
    }
    if (isset($input['auction_list'])) {
        $sanitized['auction_list'] = sanitize_text_field($input['auction_list']);
    }
    if (isset($input['single_property'])) {
        $sanitized['single_property'] = sanitize_text_field($input['single_property']);
    }
    if (isset($input['search_page'])) {
        $sanitized['search_page'] = sanitize_text_field($input['search_page']);
    }
    if (isset($input['saved_properties'])) {
        $sanitized['saved_properties'] = sanitize_text_field($input['saved_properties']);
    }
    return $sanitized;
}

function agent_custom_fields() {
    // Agent Properties meta box
    add_meta_box(
        'agentmeta type_agent',             // Unique ID
        'Agent Properties',             // Box title
        'agent_properties_fields_html', // Content callback
        'agent',                        // Post type
        'normal',                       // Context
        'high'                          // Priority
    );

    // Roster Information meta box
    add_meta_box(
        'agentmeta_roster type_agent',           // Unique ID
        'Roster Information',           // Box title
        'roster_information_fields_html', // Content callback
        'agent',                        // Post type
        'normal',                       // Context
        'high'                          // Priority
    );
}
add_action( 'add_meta_boxes', 'agent_custom_fields' );

function agent_properties_fields_html( $post ) {
    // Nonce for security
    wp_nonce_field( 'save_agent_properties', 'agent_properties_nonce' );

    // Retrieve saved data
    $show = get_post_meta( $post->ID, '_agent_show', true );
    $first_name = get_post_meta( $post->ID, '_agent_fname', true );
    $last_name  = get_post_meta( $post->ID, '_agent_lname', true );
    $phone      = get_post_meta( $post->ID, '_agent_phone', true );
    $email      = get_post_meta( $post->ID, '_agent_email', true );
    ?>

    <p>
        <label for="agent_card_show">Show Card On Property Results</label>
        <input type="checkbox" name="agent_card_show" id="agent_card_show" value="1" <?php checked( $show, 1 ); ?> />
    </p>
    <p>
        <label for="agent_first_name">First Name:</label>
        <input type="text" name="agent_first_name" id="agent_first_name" value="<?php echo esc_attr( $first_name ); ?>" />
    </p>
    <p>
        <label for="agent_last_name">Last Name:</label>
        <input type="text" name="agent_last_name" id="agent_last_name" value="<?php echo esc_attr( $last_name ); ?>" />
    </p>
    <p>
        <label for="agent_email">Email:</label>
        <input type="email" name="agent_email" id="agent_email" value="<?php echo esc_attr( $email ); ?>" />
    </p>
    <p>
        <label for="agent_phone">Phone Number:</label>
        <input type="text" name="agent_phone" id="agent_phone" value="<?php echo esc_attr( $phone ); ?>" />
    </p>

    <?php
}

function roster_information_fields_html( $post ) {
    // Nonce for security
    wp_nonce_field( 'save_roster_information', 'roster_information_nonce' );

    // Retrieve saved data
    $group = get_post_meta( $post->ID, '_agent_group', true );
    if ( empty( $group ) ) {
        $group = 'Secondary'; // Default value
    }
    $title      = get_post_meta( $post->ID, '_agent_line2', true );
    $tagline      = get_post_meta( $post->ID, '_agent_line3', true );
    $bio        = get_post_meta( $post->ID, '_agent_bio', true );
    $website_url   = get_post_meta( $post->ID, '_agent_url', true );
    $fb_url   = get_post_meta( $post->ID, '_agent_social_facebook', true );
    $twitter_url   = get_post_meta( $post->ID, '_agent_social_twitter', true );
    $linkedin_url   = get_post_meta( $post->ID, '_agent_social_linkedin', true );
    $insta_url   = get_post_meta( $post->ID, '_agent_social_instagram', true );
    $pinterest_url   = get_post_meta( $post->ID, '_agent_social_pinterest', true );
    $youtube_url   = get_post_meta( $post->ID, '_agent_social_youtube', true );
    ?>

    <p>
        <label>Group:</label><br>
        <input type="radio" name="agent_status" value="Primary" <?php checked( $group, 'Primary' ); ?> /> Primary<br>
        <input type="radio" name="agent_status" value="Secondary" <?php checked( $group, 'Secondary' ); ?> /> Secondary<br>
    </p>
    <p>
        <label for="agent_line2">Title Under Name:</label>
        <input type="text" name="agent_line2" id="agent_line2" value="<?php echo esc_attr( $title ); ?>" />
    </p>
    <p>
        <label for="agent_line3">Tagline Under Name:</label>
        <input type="text" name="agent_line3" id="agent_line3" value="<?php echo esc_attr( $tagline ); ?>" />
    </p>
    <p>
        <label for="agent_bio">Short Bio:</label>
        <?php
        // WYSIWYG Editor for Bio
        $settings = array(
            'textarea_name' => 'agent_bio',  // The name of the textarea for form submission
            'textarea_rows' => 8,
            'media_buttons' => false         // Hide the "Add Media" button
        );
        wp_editor( $bio, 'agent_bio_editor', $settings );
        ?>
    </p>
    <p>
        <label for="website_url">Website URL:</label>
        <input type="url" name="website_url" id="website_url" value="<?php echo esc_attr( $website_url ); ?>" />
    </p>
    <p>
        <label for="fb_url">Facebook URL:</label>
        <input type="url" name="fb_url" id="fb_url" value="<?php echo esc_attr( $fb_url ); ?>" />
    </p>
    <p>
        <label for="twitter_url">Twitter URL:</label>
        <input type="url" name="twitter_url" id="twitter_url" value="<?php echo esc_attr( $twitter_url ); ?>" />
    </p>
    <p>
        <label for="linkedin_url">LinkedIn URL:</label>
        <input type="url" name="linkedin_url" id="linkedin_url" value="<?php echo esc_attr( $linkedin_url ); ?>" />
    </p>
    <p>
        <label for="insta_url">Instagram URL:</label>
        <input type="url" name="insta_url" id="insta_url" value="<?php echo esc_attr( $insta_url ); ?>" />
    </p>
    <p>
        <label for="pinterest_url">Pinterest URL:</label>
        <input type="url" name="pinterest_url" id="pinterest_url" value="<?php echo esc_attr( $pinterest_url ); ?>" />
    </p>
    <p>
        <label for="youtube_url">Youtube URL:</label>
        <input type="url" name="youtube_url" id="youtube_url" value="<?php echo esc_attr( $youtube_url ); ?>" />
    </p>

    <?php
}

function save_agent_custom_fields( $post_id ) {
    // Check if nonce is valid
    if ( ! isset( $_POST['agent_properties_nonce'] ) || ! wp_verify_nonce( $_POST['agent_properties_nonce'], 'save_agent_properties' ) ) {
        return;
    }
    if ( ! isset( $_POST['roster_information_nonce'] ) || ! wp_verify_nonce( $_POST['roster_information_nonce'], 'save_roster_information' ) ) {
        return;
    }

    // Prevent autosave and bulk edits from altering data
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    if ( isset( $_POST['agent_card_show'] ) ) {
        update_post_meta( $post_id, '_agent_show', 1 ); // Save 1 if checked
    } else {
        update_post_meta( $post_id, '_agent_show', 0 ); // Save 0 if unchecked
    }

    // Save First Name field
    if ( isset( $_POST['agent_first_name'] ) ) {
        $first_name = sanitize_text_field( $_POST['agent_first_name'] );
        update_post_meta( $post_id, '_agent_fname', $first_name );
    }

    // Save Last Name field
    if ( isset( $_POST['agent_last_name'] ) ) {
        $last_name = sanitize_text_field( $_POST['agent_last_name'] );
        update_post_meta( $post_id, '_agent_lname', $last_name );
    }

    // Save Email field
    if ( isset( $_POST['agent_email'] ) ) {
        $email = sanitize_email( $_POST['agent_email'] );
        update_post_meta( $post_id, '_agent_email', $email );
    }

    // Save Phone Number field
    if ( isset( $_POST['agent_phone'] ) ) {
        $phone = sanitize_text_field( $_POST['agent_phone'] );
        update_post_meta( $post_id, '_agent_phone', $phone );
    }

    // Save Radio Button for Group
    if ( isset( $_POST['agent_status'] ) ) {
        $group = sanitize_text_field( $_POST['agent_status'] ); // Sanitize input
        update_post_meta( $post_id, '_agent_group', $group ); // Save sanitized group
    }

    // Save Title Under Name field
    if ( isset( $_POST['agent_line2'] ) ) {
        $title = sanitize_text_field( $_POST['agent_line2'] ); // Sanitize input
        update_post_meta( $post_id, '_agent_line2', $title ); // Save sanitized title
    }

    // Save Tagline Under Name field
    if ( isset( $_POST['agent_line3'] ) ) {
        $tagline = sanitize_text_field( $_POST['agent_line3'] ); // Sanitize input
        update_post_meta( $post_id, '_agent_line3', $tagline ); // Save sanitized tagline
    }

    // Save Short Bio field
    if ( isset( $_POST['agent_bio'] ) ) {
        $bio = wp_kses_post( $_POST['agent_bio'] ); // Sanitize and allow HTML
        update_post_meta( $post_id, '_agent_bio', $bio ); // Save sanitized bio
    }

    // Save Website URL field
    if ( isset( $_POST['website_url'] ) ) {
        $website_url = esc_url_raw( $_POST['website_url'] ); // Sanitize URL
        update_post_meta( $post_id, '_agent_url', $website_url ); // Save sanitized URL
    }

    // Save Facebook URL field
    if ( isset( $_POST['fb_url'] ) ) {
        $fb_url = esc_url_raw( $_POST['fb_url'] ); // Sanitize URL
        update_post_meta( $post_id, '_agent_social_facebook', $fb_url ); // Save sanitized URL
    }

    // Save Twitter URL field
    if ( isset( $_POST['twitter_url'] ) ) {
        $twitter_url = esc_url_raw( $_POST['twitter_url'] ); // Sanitize URL
        update_post_meta( $post_id, '_agent_social_twitter', $twitter_url ); // Save sanitized URL
    }

    // Save LinkedIn URL field
    if ( isset( $_POST['linkedin_url'] ) ) {
        $linkedin_url = esc_url_raw( $_POST['linkedin_url'] ); // Sanitize URL
        update_post_meta( $post_id, '_agent_social_linkedin', $linkedin_url ); // Save sanitized URL
    }

    // Save Instagram URL field
    if ( isset( $_POST['insta_url'] ) ) {
        $insta_url = esc_url_raw( $_POST['insta_url'] ); // Sanitize URL
        update_post_meta( $post_id, '_agent_social_instagram', $insta_url ); // Save sanitized URL
    }

    // Save Pinterest URL field
    if ( isset( $_POST['pinterest_url'] ) ) {
        $pinterest_url = esc_url_raw( $_POST['pinterest_url'] ); // Sanitize URL
        update_post_meta( $post_id, '_agent_social_pinterest', $pinterest_url ); // Save sanitized URL
    }

    // Save YouTube URL field
    if ( isset( $_POST['youtube_url'] ) ) {
        $youtube_url = esc_url_raw( $_POST['youtube_url'] ); // Sanitize URL
        update_post_meta( $post_id, '_agent_social_youtube', $youtube_url ); // Save sanitized URL
    }
}
add_action( 'save_post', 'save_agent_custom_fields' );
