<?php

namespace VestorFilter;

use Aws\S3\S3Client;
use Aws\Exception\AwsException;
use Aws\S3\ObjectUploader;


if ( ! defined( 'ABSPATH' ) ) {
	die();
}

class Sources extends \VestorFilter\Util\Singleton {

	/**
	 * A pointless bit of self-reference
	 *
	 * @var object
	 */
	public static $instance = null;

	public static $rets = null;

	public function install() {

		if ( ! defined( 'VF_DB_NAME' ) ) {
			$this->setup_post_type();
			add_filter( 'rwmb_meta_boxes', array( $this, 'setup_meta_boxes' ) );
		}

		//add_action( 'rest_api_init', array( $this, 'init_rest' ) );

		add_action( 'save_post_source', array( $this, 'clear_source_cache' ) );

		add_action( 'rest_api_init', array( $this, 'init_rest' ) );

		add_action( 'save_post_source', [ $this, 'create_db_tables' ], 20, 2 );


	}

	private function setup_post_type() {

		$labels = array(
			'name'                  => _x( 'Sources', 'Post type general name', 'vestorfilter' ),
			'singular_name'         => _x( 'Source', 'Post type singular name', 'vestorfilter' ),
			'menu_name'             => _x( 'Sources', 'Admin Menu text', 'vestorfilter' ),
			'name_admin_bar'        => _x( 'Source', 'Add New on Toolbar', 'vestorfilter' ),
			'add_new'               => __( 'Add New', 'vestorfilter' ),
			'add_new_item'          => __( 'Add New Source', 'vestorfilter' ),
			'new_item'              => __( 'New Source', 'vestorfilter' ),
			'edit_item'             => __( 'Edit Source', 'vestorfilter' ),
			'view_item'             => __( 'View Source', 'vestorfilter' ),
			'all_items'             => __( 'All Sources', 'vestorfilter' ),
			'search_items'          => __( 'Search Sources', 'vestorfilter' ),
			'parent_item_colon'     => __( 'Parent Sources:', 'vestorfilter' ),
			'not_found'             => __( 'No sources found.', 'vestorfilter' ),
			'not_found_in_trash'    => __( 'No sources found in Trash.', 'vestorfilter' ),
			'featured_image'        => _x( 'Source Cover Image', 'Overrides the “Featured Image” phrase for this post type. Added in 4.3', 'vestorfilter' ),
			'set_featured_image'    => _x( 'Set cover image', 'Overrides the “Set featured image” phrase for this post type. Added in 4.3', 'vestorfilter' ),
			'remove_featured_image' => _x( 'Remove cover image', 'Overrides the “Remove featured image” phrase for this post type. Added in 4.3', 'vestorfilter' ),
			'use_featured_image'    => _x( 'Use as cover image', 'Overrides the “Use as featured image” phrase for this post type. Added in 4.3', 'vestorfilter' ),
			'archives'              => _x( 'Source archives', 'The post type archive label used in nav menus. Default “Post Archives”. Added in 4.4', 'vestorfilter' ),
			'insert_into_item'      => _x( 'Insert into source', 'Overrides the “Insert into post”/”Insert into page” phrase (used when inserting media into a post). Added in 4.4', 'vestorfilter' ),
			'uploaded_to_this_item' => _x( 'Uploaded to this source', 'Overrides the “Uploaded to this post”/”Uploaded to this page” phrase (used when viewing media attached to a post). Added in 4.4', 'vestorfilter' ),
			'filter_items_list'     => _x( 'Filter sources list', 'Screen reader text for the filter links heading on the post type listing screen. Default “Filter posts list”/”Filter pages list”. Added in 4.4', 'vestorfilter' ),
			'items_list_navigation' => _x( 'Sources list navigation', 'Screen reader text for the pagination heading on the post type listing screen. Default “Posts list navigation”/”Pages list navigation”. Added in 4.4', 'vestorfilter' ),
			'items_list'            => _x( 'Sources list', 'Screen reader text for the items list heading on the post type listing screen. Default “Posts list”/”Pages list”. Added in 4.4', 'vestorfilter' ),
		);

		$args = array(
			'labels'             => $labels,
			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'query_var'          => false,
			'rewrite'            => false,
			'capability_type'    => 'post',
			'capabilities'       => array(
				'edit_post'          => 'manage_options',
				'read_post'          => 'manage_options',
				'delete_post'        => 'manage_options',
				'edit_posts'         => 'manage_options',
				'edit_others_posts'  => 'manage_options',
				'delete_posts'       => 'manage_options',
				'publish_posts'      => 'manage_options',
				'read_private_posts' => 'manage_options',
			),
			'has_archive'        => true,
			'hierarchical'       => false,
			'menu_position'      => null,
			'supports'           => array( 'title', 'excerpt' ),
		);

		register_post_type( 'source', $args );

	}

	public function init_rest() {

		register_rest_route( 'vestorfilter/v1', '/source/last-updated', array(
			'methods'             => 'GET',
			'callback'            => array( '\VestorFilter\Sources', 'get_last_updated' ),
			'permission_callback' => '__return_true',
		) );

	}

	public function create_db_tables( $post_id, $post ) {

		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return;
		}

		$exists = Source::get_object( $post_id );
		if ( $exists || empty( $post ) || empty( $post->post_name ) || $post->post_status !== 'publish' ) {
			return;
		}

		$slug = preg_replace( '/[^a-z]/', '', $post->post_name );

		global $vfdb;
		$vfdb->insert( Cache::$source_table_name, [
			'slug'    => $slug,
			'post_id' => $post_id,
			'name'    => substr( $post->post_title, 0, 100 ),
		] );

		$vfdb->query( '
			CREATE TABLE IF NOT EXISTS `wp_propertycache_meta_' . $slug . '` (
			`ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
			`property_id` int(10) unsigned DEFAULT NULL,
			`key` varchar(100) NOT NULL,
			`value` varchar(1200) DEFAULT NULL,
			`short` varchar(30) DEFAULT NULL,
			PRIMARY KEY (`ID`) USING BTREE,
			KEY `key` (`key`) USING BTREE,
			KEY `meta_property` (`property_id`) USING BTREE,
			FULLTEXT KEY `value` (`value`),
			CONSTRAINT `wp_propertycache_meta_' . $slug . '_ibfk_1` FOREIGN KEY (`property_id`) REFERENCES `wp_propertycache` (`ID`)
			) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT
		');

		$vfdb->query( '
			CREATE TABLE IF NOT EXISTS `wp_propertycache_photo_' . $slug . '` (
			`ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
			`property_id` int(10) unsigned NOT NULL,
			`url` varchar(255) DEFAULT NULL,
			`thumbnail` varchar(255) DEFAULT NULL,
			`tiny` varchar(50) DEFAULT NULL,
			`order` tinyint(3) unsigned DEFAULT NULL,
			`caption` varchar(103) DEFAULT NULL,
			`caption_short` varchar(50) DEFAULT NULL,
			PRIMARY KEY (`ID`) USING BTREE,
			KEY `photo_property` (`property_id`) USING BTREE,
			CONSTRAINT `wp_propertycache_photo_' . $slug . '_ibfk_1` FOREIGN KEY (`property_id`) REFERENCES `wp_propertycache` (`ID`)
			) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT
		');

	}

	public static function get_last_updated( $request = [] ) {

		global $vfdb;

		if ( empty( $request ) || empty( $request['source'] ) ) {

			$query = 'SELECT `last_updated` FROM ' . Cache::$source_table_name . ' ORDER BY last_updated DESC LIMIT 0,1';

		} else {

			$query = $vfdb->prepare( 
				'SELECT `last_updated` FROM ' . Cache::$source_table_name . ' WHERE post_id = %d',
				$request['source'],
			);

		}

		$date = $vfdb->get_var( $query );

		if ( ! empty( $date ) ) {

			return [ 'date' => get_date_from_gmt( "@$date", 'm/d/Y h:i a' ) ];

		}

		return new \WP_Error( 'error', 'An error occurred', [ 'status' => 500 ] );

	}

	public function clear_source_cache( $post_id ) {

		wp_cache_delete( 'compliance_line__' . $post_id, 'vestorfilter' );
		wp_cache_delete( 'compliance_text__' . $post_id, 'vestorfilter' );
		
	}

	public function setup_meta_boxes( $meta_boxes ) {

		$post_id = filter_input( INPUT_GET, 'post', FILTER_SANITIZE_NUMBER_INT );
		if ( empty( $post_id ) ) {
			$post_id = filter_input( INPUT_POST, 'post_ID', FILTER_SANITIZE_NUMBER_INT );
		}
		if ( ! empty( $post_id ) ) {

			$post = get_post( $post_id );

			if ( $post->post_type === 'source' ) {

				$source = new Source( $post_id );
				$source->connect();
				
				if ( $source->rets ) {

					//$system = $rets->GetSystemMetadata();
					//$resources = $system->getResources();
					$classes = $source->rets->GetClassesMetadata('Property');
					//$classes = $classes->toArray();
					$class_options = array();
					foreach( $classes as $class ) {
						$class_options[ $class[ 'ClassName' ] ] = $class[ 'VisibleName' ];
					}

				} else {

					$class_options = array( 'WebAPI' => 'Web API' );

				}
				
			}
			
		}
		

		$prefix = '_datasource_';

		$meta_boxes[] = array(
			'id'         => 'datasource',
			'title'      => esc_html__( 'Data Source Properties', 'vestorfilter' ),
			'post_types' => array( 'source' ),
			'context'    => 'normal',
			'priority'   => 'high',
			'autosave'   => 'false',
			'fields'     => array(
				array(
					'id'   => $prefix . 'api_url',
					'type' => 'url',
					'name' => esc_html__( 'RESO API URL', 'vestorfilter' ),
					'class'  => 'fullwidth',
				),
				array(
					'id'   => $prefix . 'api_token',
					'type' => 'text',
					'name' => esc_html__( 'API Token', 'vestorfilter' ),
				),
				array(
					'id'   => $prefix . 'api_query',
					'type' => 'text',
					'name' => esc_html__( 'Required Query Parameters', 'vestorfilter' ),
				),
				array(
					'id'   => $prefix . 'api_query_deleted',
					'type' => 'text',
					'name' => esc_html__( 'Query Deleted Properties', 'vestorfilter' ),
				),
				array(
					'type' => 'divider',
				),
				array(
					'id'   => $prefix . 'url',
					'type' => 'url',
					'name' => esc_html__( 'RETS URL', 'vestorfilter' ),
					'class'  => 'fullwidth',
				),
				array(
					'id'   => $prefix . 'username',
					'type' => 'text',
					'name' => esc_html__( 'Username', 'vestorfilter' ),
				),
				array(
					'id'   => $prefix . 'password',
					'type' => 'text',
					'name' => esc_html__( 'Password', 'vestorfilter' ),
				),
				array(
					'id'   => $prefix . 'version',
					'type' => 'text',
					'name' => esc_html__( 'RETS Version', 'vestorfilter' ),
				),
				array(
					'id'   => $prefix . 'useragent_postfix',
					'type' => 'text',
					'name' => esc_html__( 'User Agent', 'vestorfilter' ),
				),
				array(
					'type'    => 'image',
					'id'      => $prefix . 'compliance_logo',
					'name'    => esc_html__( 'Compliance Logo', 'vestorfilter' ),
					'desc'    => esc_html__( 'This logo will be placed on each property', 'vestorfilter' ),
				),
			),
		);

		$meta_boxes[] = array(
			'id'         => 'rets_fields',
			'title'      => esc_html__( 'Feed Configuration', 'vestorfilter' ),
			'post_types' => array( 'source' ),
			'context'    => 'normal',
			'priority'   => 'high',
			'autosave'   => 'false',
			'fields'     => array(
				/*array(
					'id'     => $prefix . 'property_classes',
					'type'   => 'text',
					'name'   => esc_html__( 'Property Classes', 'vestorfilter' ),
					'desc'   => esc_html__( 'Separate multiple classes with a comma', 'vestorfilter' ),
					'class'  => 'fullwidth',
				),*/
				array(
					'type'    => 'checkbox_list',
					'id'      => $prefix . 'classes',
					'name'    => esc_html__( 'Show properties from', 'vestorfilter' ), 
					'options' => $class_options ?? array(),
				),
				array(
					'id'     => $prefix . 'property_datemodified',
					'type'   => 'text',
					'name'   => esc_html__( 'Modification Timestamp', 'vestorfilter' ),
					'placeholder' => 'DateTimeModified ',
				),
				array(
					'id'     => $prefix . 'status_change_date',
					'type'   => 'text',
					'name'   => esc_html__( 'Status Change Timestamp', 'vestorfilter' ),
					'placeholder' => 'DateTimeModified',
				),
				array(
					'id'     => $prefix . 'use_tz',
					'type'   => 'checkbox',
					'name'   => esc_html__( 'Use Timezone In Query', 'vestorfilter' ),
				),
				array(
					'id'     => $prefix . 'property_photomodified',
					'type'   => 'text',
					'name'   => esc_html__( 'Photos Modified Timestamp', 'vestorfilter' ),
					'placeholder' => 'PhotoDateTimeModified',
				),
				array(
					'id'     => $prefix . 'property_photocount',
					'type'   => 'text',
					'name'   => esc_html__( 'Photo Count', 'vestorfilter' ),
					'placeholder' => 'PhotosCount',
				),
				array(
					'id'     => $prefix . 'property_photo_resource',
					'type'   => 'text',
					'name'   => esc_html__( 'Photo Resource Type', 'vestorfilter' ),
					'placeholder' => 'Photo',
				),
				array(
					'id'     => $prefix . 'property_photo_download_all',
					'type'   => 'checkbox',
					'name'   => esc_html__( 'Download All Photos', 'vestorfilter' ),
				),
				array(
					'id'          => $prefix . 'primary_id',
					'type'        => 'text',
					'name'        => esc_html__( 'MLS Listing ID', 'vestorfilter' ),
					'placeholder' => 'ListingID',
				),
				array(
					'id'          => $prefix . 'deletion_index',
					'type'        => 'text',
					'name'        => esc_html__( 'Primary Key / Delete Response Index', 'vestorfilter' ),
					'placeholder' => 'primary_key',
				),
				array(
					'type'        => 'text',
					'id'          => $prefix . 'alt_listing_id',
					'name'        => esc_html__( 'Internal Listing ID', 'vestorfilter' ),
					'placeholder' => '',
					'desc'        => esc_html__( 'Leave blank if same as above', 'vestorfilter' ),
				),
				array(
					'type'        => 'text',
					'id'          => $prefix . 'status',
					'name'        => esc_html__( 'Listing Status Field in RETS', 'vestorfilter' ),
					'placeholder' => esc_html__( 'ListingStatus', 'vestorfilter' ),
				),
				array(
					'type'        => 'text',
					'id'          => $prefix . 'status_active',
					'name'        => esc_html__( 'Active Status Value', 'vestorfilter' ),
					'placeholder' => esc_html__( 'ACT', 'vestorfilter' ),
				),
				array(
					'type'        => 'text',
					'id'          => $prefix . 'query_inactive',
					'name'        => esc_html__( 'Inactive Query', 'vestorfilter' ),
					'placeholder' => esc_html__( '', 'vestorfilter' ),
					'desc'        => esc_html__( 'For Web API only', 'vestorfilter' ),

				),
				array(
					'type'        => 'text',
					'id'          => $prefix . 'maximum_count',
					'name'        => esc_html__( 'Maximum Page Size', 'vestorfilter' ),
					'placeholder' => esc_html__( '2000', 'vestorfilter' ),

				),
				array(
					'id'          => $prefix . 'compliance_line',
					'type'        => 'text',
					'name'        => esc_html__( 'Compliance Text (Short)', 'vestorfilter' ),
					'desc'        => esc_html__( 'Use {{NAME}} to output the name of the office', 'vestorfilter' ),
				),
				array(
					'id'          => $prefix . 'compliance_line_under_photo',
					'type'        => 'text',
					'name'        => esc_html__( 'Compliance Text Under Photo', 'vestorfilter' ),
					'desc'        => esc_html__( 'Use {{NAME}} to output the name of the office', 'vestorfilter' ),
				),
				/*array(
					'id'     => $prefix . 'compliance_alt',
					'type'   => 'checkbox',
					'name'   => esc_html__( 'Show Compliance Text Under Photo', 'vestorfilter' ),
				),*/
				array(
					'id'          => $prefix . 'compliance_fulltext',
					'type'        => 'textarea',
					'name'        => esc_html__( 'Compliance Text (Full)', 'vestorfilter' ),
					'desc'        => esc_html__( 'Use {{NAME}} to output the name of the office and {{DATE}} to echo the modification date', 'vestorfilter' ),
				),
				array(
					'id'          => $prefix . 'post_process',
					'type'        => 'textarea',
					'name'        => esc_html__( 'Post Processing Rules', 'vestorfilter' ),
					'desc'        => esc_html__( '', 'vestorfilter' ),
				),
			),
		);

		$meta_boxes[] = array(
			'id'         => 'rets_oh',
			'title'      => esc_html__( 'Open House Data', 'vestorfilter' ),
			'post_types' => array( 'source' ),
			'context'    => 'normal',
			'priority'   => 'high',
			'autosave'   => 'false',
			'fields'     => array(
				
				array(
					'id'          => $prefix . 'oh_object',
					'type'        => 'text',
					'name'        => esc_html__( 'Open House Object', 'vestorfilter' ),
					'placeholder' => 'OpenHouseBrokerTour',
				),
				array(
					'id'          => $prefix . 'oh_class',
					'type'        => 'text',
					'name'        => esc_html__( 'Open House Class', 'vestorfilter' ),
					'placeholder' => 'OpenHouseBrokerTourDD',
				),
				array(
					'id'          => $prefix . 'oh_listid',
					'type'        => 'text',
					'name'        => esc_html__( 'Listing ID', 'vestorfilter' ),
					'placeholder' => 'OpenHouseBrokerTourDD',
				),
				array(
					'id'          => $prefix . 'oh_prefix',
					'type'        => 'text',
					'name'        => esc_html__( 'Field Prefix', 'vestorfilter' ),
					'placeholder' => 'OpenHouse',
				),
				array(
					'id'          => $prefix . 'oh_date',
					'type'        => 'text',
					'name'        => esc_html__( 'Date of Open House', 'vestorfilter' ),
					'placeholder' => 'OpenHouseDate',
				),
				array(
					'id'          => $prefix . 'oh_time_start',
					'type'        => 'text',
					'name'        => esc_html__( 'Start Time', 'vestorfilter' ),
					'placeholder' => 'OpenHouseStart',
				),
				array(
					'id'          => $prefix . 'oh_time_end',
					'type'        => 'text',
					'name'        => esc_html__( 'End Time', 'vestorfilter' ),
					'placeholder' => 'OpenHouseEnd',
				),
			),
		);

		$data = Property::get_available_fields();
		$rets_fields = [
			
			array(
				'id'          => $prefix . 'field_listing_office',
				'type'        => 'text',
				'name'        => esc_html__( 'Listing Office Field', 'vestorfilter' ),
				'placeholder' => 'ListOfficeName',
			),
			array(
				'id'          => $prefix . 'alt_address_line1',
				'type'        => 'text',
				'name'        => esc_html__( 'Street Address Fields', 'vestorfilter' ),
				'placeholder' => '',
				'desc'        => 'Use for RETS providers that do not have a dedicated street address field',
			),
			array(
				'id'          => $prefix . 'alt_address_unit',
				'type'        => 'text',
				'name'        => esc_html__( 'Unit Number', 'vestorfilter' ),
				'placeholder' => '',
				'desc'        => 'If applicable',
			),
		];
		foreach( $data as $key => $field ) {

			$rets_fields[] = [
				'id'          => $prefix . 'field_' . $key,
				'type'        => 'text',
				'name'        => $field['label'] ?? ucfirst( $key ),
				'placeholder' => $field['key'] ?? '',
			];

		}

		$meta_boxes[] = array(
			'id'         => 'rets_map',
			'title'      => esc_html__( 'RETS Fields', 'vestorfilter' ),
			'post_types' => array( 'source' ),
			'context'    => 'normal',
			'autosave'   => 'false',
			'fields'     => $rets_fields,
		);

		$meta_boxes[] = array(
			'id'         => 'rets_mf',
			'title'      => esc_html__( 'Multi-Family Data', 'vestorfilter' ),
			'post_types' => array( 'source' ),
			'context'    => 'normal',
			'priority'   => 'high',
			'autosave'   => 'false',
			'fields'     => array(
				[
					'id'          => $prefix . 'mf_class',
					'type'        => 'text',
					'name'        => esc_html__( 'Multi-family Class', 'vestorfilter' ),
					'placeholder' => '',
				],
				[
					'type'        => 'group',
					'id'          => $prefix . 'mf_fields',
					'name'        => esc_html__( 'Multi-family Unit Fields', 'vestorfilter' ),
					'clone'       => true,
					'collapsible' => true,
					'fields'      => [
						array(
							'id'          => $prefix . 'unitno',
							'type'        => 'text',
							'name'        => esc_html__( 'Unit Number', 'vestorfilter' ),
							'placeholder' => '',
						),
						array(
							'id'          => $prefix . 'beds',
							'type'        => 'text',
							'name'        => esc_html__( 'Bedrooms', 'vestorfilter' ),
							'placeholder' => '',
						),
						array(
							'id'          => $prefix . 'baths',
							'type'        => 'text',
							'name'        => esc_html__( 'Baths', 'vestorfilter' ),
							'placeholder' => '',
						),
						array(
							'id'          => $prefix . 'rent',
							'type'        => 'text',
							'name'        => esc_html__( 'Rent', 'vestorfilter' ),
							'placeholder' => '',
						),
						array(
							'id'          => $prefix . 'sqft',
							'type'        => 'text',
							'name'        => esc_html__( 'Sq Ft', 'vestorfilter' ),
							'placeholder' => '',
						),
						array(
							'id'          => $prefix . 'total',
							'type'        => 'text',
							'name'        => esc_html__( 'Units of Type', 'vestorfilter' ),
							'placeholder' => '',
						)
					],
				],
				
				/*array(
					'id'          => $prefix . 'mf_object',
					'type'        => 'text',
					'name'        => esc_html__( 'Unit Object', 'vestorfilter' ),
					'placeholder' => 'Unit',
				),
				array(
					'id'          => $prefix . 'mf_class',
					'type'        => 'text',
					'name'        => esc_html__( 'Unit Class', 'vestorfilter' ),
					'placeholder' => 'Unit',
				),
				array(
					'id'          => $prefix . 'mf_listid',
					'type'        => 'text',
					'name'        => esc_html__( 'Listing ID', 'vestorfilter' ),
					'placeholder' => 'ListingID',
				),
				array(
					'id'          => $prefix . 'mf_unitno',
					'type'        => 'text',
					'name'        => esc_html__( 'Unit Number', 'vestorfilter' ),
					'placeholder' => '',
				),
				array(
					'id'          => $prefix . 'mf_beds',
					'type'        => 'text',
					'name'        => esc_html__( 'Bedrooms', 'vestorfilter' ),
					'placeholder' => '',
				),
				array(
					'id'          => $prefix . 'mf_baths',
					'type'        => 'text',
					'name'        => esc_html__( 'Baths', 'vestorfilter' ),
					'placeholder' => '',
				),
				array(
					'id'          => $prefix . 'mf_rent',
					'type'        => 'text',
					'name'        => esc_html__( 'Rent', 'vestorfilter' ),
					'placeholder' => '',
				),
				array(
					'id'          => $prefix . 'mf_sqft',
					'type'        => 'text',
					'name'        => esc_html__( 'Sq Ft', 'vestorfilter' ),
					'placeholder' => '',
				),
				array(
					'id'          => $prefix . 'mf_total',
					'type'        => 'text',
					'name'        => esc_html__( 'Units of Type', 'vestorfilter' ),
					'placeholder' => '',
				),*/
			),
		);

		/*$meta_boxes[] = array(
			'id'         => 'datasync',
			'title'      => esc_html__( 'Data Synchronization', 'vestorfilter' ),
			'post_types' => array( 'source' ),
			'context'    => 'side',
			'autosave'   => 'false',
			'fields'     => array(),
		);*/

		$meta_boxes[] = [
			'title'      => esc_html__( 'Filter Options', 'vestorfilter' ),
			'id'         => 'source_filters',
			'post_types' => ['source'],
			'context'    => 'normal',
			'fields'     => [
				[
					'type'        => 'text',
					'id'          => $prefix . 'type_field',
					'name'        => esc_html__( 'Property Type Field in RETS', 'vestorfilter' ),
					'placeholder' => esc_html__( 'PropertyType', 'vestorfilter' ),
				],[
					'type'        => 'text',
					'id'          => $prefix . 'category_field',
					'name'        => esc_html__( 'Property Category Field in RETS', 'vestorfilter' ),
					'placeholder' => esc_html__( 'PropertyCategory', 'vestorfilter' ),
				],[
					'type'    => 'fieldset_text',
					'id'      => $prefix . 'status_conditions_sf',
					'name'    => esc_html__( 'Single Family ', 'vestorfilter' ),
					'desc'    => esc_html__( 'Enter RETS field conditions a property must meet to be part of the "Single family" status, split options with a comma', 'vestorfilter' ),
					'options' => [
						'categories' => 'Property Categories Allowed',
						'types'      => 'Property Types Allowed',						
						'query'      => 'RETS Query',
						//'rets_key'   => 'RETS key',
						//'rets_value' => 'RETS value',
						//'rets_compare' => 'Comparison Operator',
					],
				],[
					'type'    => 'fieldset_text',
					'id'      => $prefix . 'status_conditions_mf',
					'name'    => esc_html__( 'Multi Family', 'vestorfilter' ),
					'desc'    => esc_html__( 'Enter RETS field conditions a property must meet to be part of the "Multi-family" status, split options with a comma', 'vestorfilter' ),
					'options' => [
						'categories' => 'Property Categories Allowed',
						'types'      => 'Property Types Allowed',						
						'query'      => 'RETS Query',
						//'rets_key'   => 'RETS key',
						//'rets_value' => 'RETS value',
						//'rets_compare' => 'Comparison Operator',
					],
				],[
					'type'    => 'fieldset_text',
					'id'      => $prefix . 'status_conditions_condos',
					'name'    => esc_html__( 'Condos / Townhomes', 'vestorfilter' ),
					'desc'    => esc_html__( 'Enter RETS field conditions a property must meet to be part of the "Condos / Townhomes" status, split options with a comma', 'vestorfilter' ),
					'options' => [
						'categories' => 'Property Categories Allowed',
						'types'      => 'Property Types Allowed',						
						'query'      => 'RETS Query',
						//'rets_key'   => 'RETS key',
						//'rets_value' => 'RETS value',
						//'rets_compare' => 'Comparison Operator',
					],
				],[
					'type'    => 'fieldset_text',
					'id'      => $prefix . 'status_conditions_55',
					'name'    => esc_html__( '55+', 'vestorfilter' ),
					'desc'    => esc_html__( 'Enter RETS field conditions a property must meet to be part of the "55+" status, split options with a comma', 'vestorfilter' ),
					'options' => [
						'categories' => 'Property Categories Allowed',
						'types'      => 'Property Types Allowed',						
						'query'      => 'RETS Query',
						//'rets_key'   => 'RETS key',
						//'rets_value' => 'RETS value',
						//'rets_compare' => 'Comparison Operator',
					],
				],[
					'type'    => 'fieldset_text',
					'id'      => $prefix . 'status_conditions_land',
					'name'    => esc_html__( 'Land', 'vestorfilter' ),
					'desc'    => esc_html__( 'Enter RETS field conditions a property must meet to be part of the "Land" status, split options with a comma', 'vestorfilter' ),
					'options' => [
						'categories' => 'Property Categories Allowed',
						'types'      => 'Property Types Allowed',						
						'query'      => 'RETS Query',
						//'rets_key'   => 'RETS key',
						//'rets_value' => 'RETS value',
						//'rets_compare' => 'Comparison Operator',
					],
				],[
					'type'    => 'fieldset_text',
					'id'      => $prefix . 'status_conditions_commercial',
					'name'    => esc_html__( 'Commercial', 'vestorfilter' ),
					'desc'    => esc_html__( 'Enter RETS field conditions a property must meet to be part of the "Commercial" status, split options with a comma', 'vestorfilter' ),
					'options' => [
						'categories' => 'Property Categories Allowed',
						'types'      => 'Property Types Allowed',						
						'query'      => 'RETS Query',
						//'rets_key'   => 'RETS key',
						//'rets_value' => 'RETS value',
						//'rets_compare' => 'Comparison Operator',
					],
				],
			],
		];

		//add_action( 'rwmb_after_datasync', array( $this, 'sync_box_contents' ) );

		return $meta_boxes;

	}

	public function sync_box_contents( $box ) { ?>


		<p>Make sure your settings are saved on this page before proceeding to the following links:</p>
		<ul>
			<li><a href="<?php admin_url() ?>options-general.php?page=vestorfilter&action=import&source=<?php echo $box->object_id ?>">Manually sync data with RETS</a></li>
			<li><a href="<?php admin_url() ?>options-general.php?page=vestorfilter&action=process&source=<?php echo $box->object_id ?>">Process data </a></li>
		</ul>

	<?php }

	public static function get_total_properties( $source_id ) {

		global $vfdb;

		$query = $vfdb->prepare( 'SELECT COUNT(*) FROM ' . Cache::$prop_table_name . ' WHERE post_id = %d', $source_id );

		return $vfdb->get_var( $query );

	}

}

add_action( 'vestorfilter_installed', array( 'VestorFilter\Sources', 'init' ) );

