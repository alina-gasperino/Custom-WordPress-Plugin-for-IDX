<?php

namespace VestorFilter;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

class Plugin {

	/**
	 * A pointless bit of self-reference
	 *
	 * @var object
	 */
	private $instance = null;

	/**
	 * Used for cache busting JS and CSS.
	 *
	 * @var string
	 */
	public static $build_version = '2.0.1';

	/**
	 * Information about the plugin. Pulled from package.json if $use_node is set to `true`
	 *
	 * @var array
	 */
	private $package = null;

	public static $plugin_path = null;
	public static $plugin_uri = null;

	public static $debug_mode;

	public function __construct() {}

	public static function init() {

		self::$debug_mode = defined( 'WP_DEBUG_DISPLAY' ) && WP_DEBUG_DISPLAY;

		$self = apply_filters( 'vestorfilter_instance', new Plugin() );

		$self->install();

		do_action( 'vestorfilter_init', $self );

		self::$plugin_uri = trailingslashit( get_bloginfo( 'url' ) ) . 'wp-content/mu-plugins/vestorfilter';

		add_action(
			'wp_enqueue_scripts',
			function() {
				wp_register_script( 'select2', self::$plugin_uri . '/assets/vendor/select2/js/select2.min.js', array( 'jquery' ), '4.0.13', true );
				if ( current_user_can( 'use_dashboard' ) ) {
					wp_enqueue_style( 'select2', self::$plugin_uri . '/assets/vendor/select2/css/select2.min.css', array(), '4.0.13' );
				}
			}
		);

		add_action(
			'admin_enqueue_scripts',
			function() {
				wp_enqueue_style( 'vestorfilter-admin', self::$plugin_uri . '/assets/css/admin.css', array(), '20210514' );
				wp_enqueue_script( 'vestorfilter-admin', self::$plugin_uri . '/assets/css/admin.js', array(), '20210517', true );
				wp_enqueue_script( 'vestorfilter-leads', self::$plugin_uri . '/assets/js/leads.js', array(), '20210517', true );
				wp_enqueue_script( 'select2', self::$plugin_uri . '/assets/vendor/select2/js/select2.min.js', array( 'jquery' ), '4.0.13', false );
				wp_enqueue_style( 'select2', self::$plugin_uri . '/assets/vendor/select2/css/select2.min.css', array(), '4.0.13' );
			}
		);

        add_action('wp_ajax_remove_lead', function () {
            $id = $_POST['id'];

            if(empty($id)) wp_die('Empty user/lead id');

            $currentUser = get_current_user_id();
            $test = get_user_by('ID', $currentUser);
            echo '<pre>';
            print_r($test->allcaps);
            echo '</pre>';
            if($currentUser) {

                if(current_user_can('remove_users')) {


                    $user = get_user_by('ID', $id);

                    if ($user) {

                        $userRoles = $user->roles;

                        if ( in_array( 'subscriber', $userRoles, true ) ) {
                            require_once( ABSPATH . 'wp-admin/includes/user.php' );
                            $delete = wp_delete_user($user->data->ID);
                            if($delete) {
                                wp_die('User ' . $user->data->user_email . ' was removed from the system.');
                            } else {
                                wp_die('Something went wrong. Contact administrator.');
                            }
                        } else {
                            wp_die('You are able to delete the subscribers only.');
                        }

                    } else {
                        wp_die('User not found.');
                    }
                } else {
                    wp_die('You are not authorized to do this.');
                }
            } else {
                wp_die('You are not allowed to do this action.');
            }

        });

		return $self;

	}

	private function install() {

		if ( ! is_null( $this->instance ) ) {
			return $this->instance;
		}

		$this->instance = $this;

		self::setup_constants();

		self::$plugin_path = __DIR__;

		require_once __DIR__ . "/util/class-singleton.php";

		self::autoload_folder( __DIR__ . '/util' );
		self::autoload_folder( __DIR__ . '/blocks' );
		self::autoload_folder( __DIR__ . '/hooks' );
		self::autoload_folder( __DIR__ . '/src' );

		add_filter( 'vestorfilter_template_paths__vestorfilter', array( $this, 'add_template_folder' ) );

		do_action( 'vestorfilter_installed', $this );

	}

	public static function setup_search_rewrite() {

		$search_template = wp_cache_get( 'search_template', 'vestorfilter' );

		if ( empty( $search_template ) ) {
			$search_template = Settings::get_page_template( 'search' );

			if ( empty( $search_template ) ) {
				return;
			}
			$search_template = get_post( $search_template );
		}

		wp_cache_set( 'search_template', $search_template, 'vestorfilter' );

		//add_rewrite_tag( '%type%', '([a-z0-9-]*)' );
		//add_rewrite_tag( '%place%', '([a-z0-9-/]*)' );
		add_rewrite_rule( '^' . $search_template->post_name . '/(.*)', 'index.php?page_id=' . $search_template->ID, 'top' );

	}

	public static function setup_property_rewrite() {

		$property_template = wp_cache_get( 'property_template', 'vestorfilter' );

		if ( empty( $property_template ) ) {
			$property_template = Settings::get_page_template( 'property' );

			if ( empty( $property_template ) ) {
				return;
			}
			$property_template = get_post( $property_template );
		}

		wp_cache_set( 'property_template', $property_template, 'vestorfilter' );

		add_rewrite_tag( '%mlsid%', '([0-9]+)' );
		add_rewrite_rule( '^' . $property_template->post_name . '/([^/]*)/?', 'index.php?page_id=' . $property_template->ID . '&mlsid=$matches[1]', 'top' );

	}

	public static function setup_favorites_rewrite() {

		$saved_template = wp_cache_get( 'saved_template', 'vestorfilter' );

		if ( empty( $property_template ) ) {
			$saved_template = Settings::get_page_template( 'saved' );

			if ( empty( $saved_template ) ) {
				return;
			}
			$saved_template = get_post( $saved_template );
		}

		wp_cache_set( 'saved_template', $saved_template, 'vestorfilter' );

		add_rewrite_tag( '%user_slug%', '([a-z0-9-]*)' );
		add_rewrite_rule( '^' . $saved_template->post_name . '/([^/]*)/?', 'index.php?page_id=' . $saved_template->ID . '&user_slug=$matches[1]', 'top' );

	}

	public function add_template_folder( $paths ) {

		$paths[] = __DIR__ . '/templates';

		return $paths;

	}

	public static function setup_constants() {

		if ( ! defined( 'VF_IMG_URL' ) ) {
			$path = wp_upload_dir( 'vf' );
			$path = $path['url'];
			define( 'VF_IMG_URL', $path );
		}

	}

	public static function autoload_folder( $dir, $nofiles = false ) {

		$contained = scandir( $dir );

		foreach ( $contained as $file ) {

			if ( substr( $file, 0, 1 ) === '.' ) {
				continue;
			}

			if ( is_dir( $dir . '/' . $file ) && is_file( "$dir/$file/$file.php" ) ) {
				require_once "$dir/$file/$file.php";
				continue;
			}

			if ( $nofiles || ! is_file( $dir . '/' . $file ) ) {
				continue;
			}

			if ( pathinfo( $dir . '/' . $file, PATHINFO_EXTENSION ) !== 'php' ) {
				continue;
			}

			require_once $dir . '/' . $file;
		}

	}

}

add_action( 'plugins_loaded', array( 'VestorFilter\Plugin', 'init' ) );
add_action( 'init', array( 'VestorFilter\Plugin', 'setup_search_rewrite' ), 10, 0 );
add_action( 'init', array( 'VestorFilter\Plugin', 'setup_property_rewrite' ), 10, 0 );
add_action( 'init', array( 'VestorFilter\Plugin', 'setup_favorites_rewrite' ), 10, 0 );

