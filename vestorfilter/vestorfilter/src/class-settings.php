<?php

namespace VestorFilter;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

class Settings extends \VestorFilter\Util\Singleton {

	/**
	 * A pointless bit of self-reference
	 *
	 * @var object
	 */
	public static $instance = null;

	public static $filters = [ 'status' => 'Listing Status' ];

	public function install() {

		// add_action( 'admin_init', array( $this, 'register_settings' ) );
		// add_action( 'admin_menu', array( $this, 'register_option_page' ) );

		add_filter( 'mb_settings_pages', [ $this, 'settings_page' ] );
		add_filter( 'rwmb_meta_boxes', [ $this, 'general_settings_meta' ] );

		add_shortcode( 'vestorsetting', [ $this, 'config_var' ] );

		add_action( 'wp_footer', [ $this, 'footer_scripts' ], 1 );
		add_action( 'wp_head', [ $this, 'analytics' ], 99 );
		add_action( 'wp_footer', [ $this, 'gtm_head' ], 1 );
		//add_action( 'wp_body_open', [ $this, 'gtm_body' ], 1 );

		add_action( 'mb_settings_page_submit_buttons', [ $this, 'hide_some_meta_boxes' ] );

	}

	public function register_settings() {

		add_option( 'vestorfilter_property_page', '' );
		register_setting( 'vestorfilter_settings', 'vestorfilter_property_page' );
		register_setting( 'vestorfilter_settings', 'vestorfilter_search_page' );

		foreach ( self::$filters as $filter => $label ) {
			register_setting(
				'vestorfilter_settings',
				"vestorfilter_{$filter}_options",
				array( 'sanitize_callback' => [ $this, 'sanitize_filter_options' ] )
			);
		}

	}

	public function config_var( $attrs, $content = '', $tag = null ) {

		if ( empty( $attrs['key'] ) ) {
			return '';
		}

		$value = get_option( 'vestorfilter' );

		if ( empty( $value ) ) {
			return false;
		}

		return $value[ 'config_' . sanitize_title( $attrs['key'] ) ] ?? '';

	}

	public function sanitize_filter_options( $values ) {

		$valpack = array();
		foreach ( $values as $key => $value ) {
			$valpack[] = $key;
		}

		return $valpack;

	}

	public static function get( $key ) {

		$value = get_option( 'vestorfilter' );

		if ( empty( $value ) ) {
			return false;
		}

		return $value[ $key ] ?? false;

	}

	public static function get_page_template( $key ) {

		$value = get_option( 'vestorfilter' );

		if ( empty( $value ) ) {
			return false;
		}

		return $value[ $key . '_page' ] ?? false;

	}

	public static function get_page_url( $key ) {

		$page_id = self::get_page_template( $key );

		if ( empty( $page_id ) ) {
			return false;
		}

		return get_permalink( $page_id );

	}

	public static function get_filter_options( $key ) {

		// $value = get_option( 'vestorfilter' );

		$value = rwmb_meta(
			'filters_' . $key,
			[ 'object_type' => 'setting' ],
			'vestorfilter'
		);

		return $value;

	}

	function footer_scripts() {

		$scripts = self::get( 'footer_scripts' );
		if ( ! empty( $scripts ) ) {
			echo $scripts;
		}

	}

	function analytics() {

		$id = self::get( 'analytics_id' );
		if ( ! empty( $id ) ) { ?>

			<script async src="https://www.googletagmanager.com/gtag/js?id=<?=esc_attr($id)?>"></script>
			<script>
				window.dataLayer = window.dataLayer || [];
				function gtag(){dataLayer.push(arguments);}
				gtag('js', new Date());

				gtag('config', '<?=esc_attr($id)?>');
			</script>

		<?php }

	}

	function gtm_head() {

		$id = self::get( 'gtm_id' );
		if ( empty( $id ) ) {
			return;
		}

		?>

		<script>

		document.addEventListener('DOMContentLoaded', () => {
			/** init gtm after 3500 seconds - this could be adjusted */
			setTimeout(initGTM, 3500);
		});
		document.addEventListener('scroll', initGTMOnEvent);
		document.addEventListener('mousemove', initGTMOnEvent);
		document.addEventListener('touchstart', initGTMOnEvent);
		
		function initGTMOnEvent (event) {
			initGTM();
			event.currentTarget.removeEventListener(event.type, initGTMOnEvent);
		}
		
		function initGTM () {
			if (window.gtmDidInit) {
				return false;
			}
			window.gtmDidInit = true;
			const script = document.createElement('script');
			script.type = 'text/javascript';
			script.async = true;
			script.onload = () => { dataLayer.push({ event: 'gtm.js', 'gtm.start': (new Date()).getTime(), 'gtm.uniqueEventId': 0 }); }
			script.src = 'https://www.googletagmanager.com/gtm.js?id=<?=esc_attr($id)?>';
		
			document.head.appendChild(script);
		}

		</script>

		<?php 
		
		/*

			<!-- Google Tag Manager -->
			<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
			new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
			j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
			'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
			})(window,document,'script','dataLayer','<?=esc_attr($id)?>');</script>
			<!-- End Google Tag Manager -->

		*/

	}

	function gtm_body() {

		$id = self::get( 'gtm_id' );
		if ( ! empty( $id ) ) { ?>

			<!-- Google Tag Manager (noscript) -->
			<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=<?=esc_attr($id)?>"
			height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
			<!-- End Google Tag Manager (noscript) -->

		<?php }

	}
	
	public static function get_aws( $what ) {

		$setting = self::get( 'aws_' . $what );
		if ( $setting ) {
			return $setting;
		}
		switch( $what ) {
			case 'url':
				return AWS_URL;
				break;
			case 'region':
				return AWS_REGION;
				break;
			case 'bucket':
				return AWS_BUCKET;
				break;
		}
		return null;

	}
	

	function general_settings_meta( $meta_boxes ) {

		$prefix = 'vestorfilter_';

		$pages        = get_posts(
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

		$status_filters = Cache::get_index_values( 'status', '`value` ASC' ) ?: [];
		$status_options = [];
		
		foreach ( $status_filters as $status ) {
			$status_options[ $status->ID ] = $status->value;
		}

		$lot_filters = Cache::get_index_values( 'lot-size' ) ?: [];
		$lot_filters = Data::sort_lot_options( $lot_filters );
		$lot_options = [];
		foreach ( $lot_filters as $lot ) {
			$lot_options[ $lot->ID ] = $lot->value;
		}

		$all_vf = Filters::get_all();
		$vf_fields = [];
		foreach ( $all_vf as $vf_key ) {

			$vf_fields[] = [
				'type' => 'text',
				'id'   => $vf_key,
				'name' => Filters::get_filter_name( $vf_key ),
			];

		}

		$location_options = [];

		$location_types_allowed = apply_filters( 'vestorfilter_allowed_default_locations', [ 'city', 'county', 'zip' ] );
		foreach( $location_types_allowed as $type ) {
			$locations = Location::get_all_data( $type );
			foreach( $locations as $locale ) {
				$location_options[ $locale->ID ] = $locale->value;
			}
		}


		$meta_boxes[] = [
			'title'          => esc_html__( 'Vestor Filter General Settings', 'vestorfilter' ),
			'id'             => 'vestorfilters_general',
			'settings_pages' => 'vestorfilter',
			'tab'            => 'general',
			'fields'         => [
				[
					'type' => 'heading',
					'name' => esc_html__( 'Site Configuration', 'vestorfilter' ),
				],[
					'type'    => 'text',
					'id'      => 'config_area',
					'name'    => esc_html__( 'Location/Area', 'vestorfilter' ),
					'desc' => esc_html__( 'Usually this is a the city or major metropolitan area people would search for to find homes in your sales area.', 'vestorfilter' ),
				],[
					'type'     => 'select_advanced',
					'id'       => 'default_location_id',
					'name'     => esc_html__( 'Default Location in Search', 'vestorfilter' ),
					'multiple' => true,
					'options'  => $location_options,
				],[
					'type'     => 'select',
					'id'       => 'default_results_view',
					'name'     => esc_html__( 'Default Search Results View', 'vestorfilter' ),
					'multiple' => true,
					'options'  => [
						'map' => 'Map',
						'list' => 'Grid',
					],
				],[
					'type' => 'divider',
				],[
					'type' => 'image_advanced',
					'id'   => 'header_logo',
					'name' => esc_html__( 'Header Logo', 'vestorfilter' ),
					'desc' => esc_html__( 'Shows to the left of the company logo in the header', 'vestorfilter' ),
					'limit' => 1,
					'max_file_uploads' => 1,
				],[
					'type' => 'image_advanced',
					'id'   => 'compliance_logo',
					'name' => esc_html__( 'Company Logo', 'vestorfilter' ),
					'desc' => esc_html__( 'Show in "Agent Advice" card and header', 'vestorfilter' ),
					'limit' => 1,
					'max_file_uploads' => 1,
				],[
					'type' => 'textarea',
					'id'   => 'compliance_text',
					'name' => esc_html__( 'Footer Text', 'vestorfilter' ),
				],[
					'type'              => 'textarea',
					'id'                => 'footer_scripts',
					'name'              => esc_html__( 'Scripts installed in the Footer', 'vestorfilter' ),
					'sanitize_callback' => 'none',
					'rows'              => 10,
					'cols'              => 100
				],[
					'type' => 'heading',
					'name' => esc_html__( 'Third-Party Integration', 'vestorfilter' ),
				],[
					'type'    => 'text',
					'id'      => 'gtm_id',
					'name'    => esc_html__( 'Google Tag Manager', 'vestorfilter' ),
				],[
					'type'    => 'text',
					'id'      => 'analytics_id',
					'name'    => esc_html__( 'Google Analytics ID', 'vestorfilter' ),
					'desc'    => esc_html__( 'If your Google Tag Manager includes analytics, do not enter the ID here', 'vestorfilter' ),
				],[
					'type'    => 'text',
					'id'      => 'mailchimp_api',
					'name'    => esc_html__( 'Mailchimp API Key', 'vestorfilter' ),
				],[
					'type'    => 'text',
					'id'      => 'mailchimp_listid',
					'name'    => esc_html__( 'Mailchimp Audience ID', 'vestorfilter' ),
					'desc'    => esc_html__( 'Assign these fields to dump new registrations into a Mailchimp list', 'vestorfilter' ),
				],[
					'type'    => 'text',
					'id'      => 'twilio_sms',
					'name'    => esc_html__( 'Twilio Phone Number', 'vestorfilter' ),
					'desc'    => esc_html__( '(Do not change this unless instructed by VestorFilter, or notifications will break)', 'vestorfilter' ),
				],[
					'type' => 'heading',
					'name' => esc_html__( 'AWS Settings', 'vestorfilter' ),
					'desc'    => esc_html__( '(Do not change this unless instructed by VestorFilter, or photos will stop working)', 'vestorfilter' ),
				],[
					'type'    => 'text',
					'id'      => 'aws_bucket',
					'name'    => esc_html__( 'AWS Bucket Name', 'vestorfilter' ),
				],[
					'type'    => 'text',
					'id'      => 'aws_url',
					'name'    => esc_html__( 'AWS URL', 'vestorfilter' ),
				],[
					'type'    => 'text',
					'id'      => 'aws_region',
					'name'    => esc_html__( 'AWS Region', 'vestorfilter' ),
				],
			],
		];

		$meta_boxes[] = [
			'title'          => esc_html__( 'Map Settings', 'vestorfilter' ),
			'id'             => 'vestorfilters_map',
			'settings_pages' => 'vestorfilter',
			'tab'            => 'maps',
			'fields'         => [
				[
					'type' => 'text',
					'id'   => 'map_bounds_ne',
					'name' => esc_html__( 'NE Boundary Coordinates', 'vestorfilter' ),
				],[
					'type' => 'text',
					'id'   => 'map_bounds_sw',
					'name' => esc_html__( 'SW Boundary Coordinates', 'vestorfilter' ),
				],[
					'type' => 'text',
					'id'   => 'map_states',
					'name' => esc_html__( 'States Allowed (abbrevation)', 'vestorfilter' ),
				],[
					'type' => 'text',
					'id'   => 'geocoding_api',
					'name' => esc_html__( 'Geocoding API Key', 'vestorfilter' ),
					'desc' => esc_html__( "Needed for either of the below two settings", 'vestorfilter' ),
				],[
					'type' => 'checkbox',
					'id'   => 'use_geocoding',
					'name' => esc_html__( 'Use Geocoding for cities', 'vestorfilter' ),
					'desc' => esc_html__( "Will send users to maps based on Google's location data, instead of the properties found.", 'vestorfilter' ),
				],[
					'type' => 'checkbox',
					'id'   => 'upgrade_empty_coords',
					'name' => esc_html__( 'Use geocoding for properties', 'vestorfilter' ),
					'desc' => esc_html__( "Looks up property coordinates based on MLS address instead of the latitude and longitude fields. Only use if your MLS is not providing complete coordinate data.", 'vestorfilter' ),
				],[
					'type' => 'heading',
					'name' => esc_html__( 'Default Location Coordinates', 'vestorfilter' ),
					'desc'    => esc_html__( 'This will override the default map center when a person goes to results without clicking on anything.', 'vestorfilter' ),
				],[
					'type' => 'divider',
				],[
					'type'    => 'text',
					'id'      => 'default_lat',
					'name'    => esc_html__( 'Latitude', 'vestorfilter' ),
				],[
					'type'    => 'text',
					'id'      => 'default_lon',
					'name'    => esc_html__( 'Longitude', 'vestorfilter' ),
				],[
					'type'    => 'text',
					'id'      => 'default_zoom',
					'name'    => esc_html__( 'Zoom', 'vestorfilter' ),
				],
			],
		];

		$meta_boxes[] = [
			'title'          => esc_html__( 'Email Settings', 'vestorfilter' ),
			'id'             => 'vestorfilters_emails',
			'settings_pages' => 'vestorfilter',
			'tab'            => 'emails',
			'fields'         => [
				[
					'type' => 'text',
					'id'   => 'email_from_email',
					'name' => esc_html__( 'Email From: Address', 'vestorfilter' ),
				],[
					'type' => 'text',
					'id'   => 'email_from_name',
					'name' => esc_html__( 'Email From: Name', 'vestorfilter' ),
				],[
					'type' => 'text',
					'id'   => 'email_replyto',
					'name' => esc_html__( 'Email Reply-To: Address', 'vestorfilter' ),
				],[
					'type' => 'textarea',
					'id'   => 'email_footer_text',
					'name' => esc_html__( 'Email Footer Text', 'vestorfilter' ),
				],[
					'type' => 'textarea',
					'id'   => 'email_footer_text_welcome',
					'name' => esc_html__( 'Welcome Email Footer', 'vestorfilter' ),
				],[
					'type' => 'textarea',
					'id'   => 'email_footer_address',
					'name' => esc_html__( 'Agency Address', 'vestorfilter' ),
				],
			],
		];

		$meta_boxes[] = [
			'title'          => esc_html__( 'SMS Settings', 'vestorfilter' ),
			'id'             => 'vestorfilters_sms',
			'settings_pages' => 'vestorfilter',
			'tab'            => 'sms',
			'fields'         => [
				[
					'type' => 'textarea',
					'id'   => 'onboard_sms_1',
					'name' => esc_html__( 'Onboard Message 1', 'vestorfilter' ),
					'desc' => 'Sent 3 minutes after registration or the following day when after 10pm.',
				],[
					'type' => 'textarea',
					'id'   => 'onboard_sms_2',
					'name' => esc_html__( 'Onboard Message 2', 'vestorfilter' ),
					'desc' => 'Sent 10 minutes after registration or the following day when after 10pm.',
				],[
					'type' => 'textarea',
					'id'   => 'onboard_sms_3',
					'name' => esc_html__( 'Onboard Message 3', 'vestorfilter' ),
					'desc' => 'Sent 2 days after registration.',
				],[
					'type' => 'textarea',
					'id'   => 'onboard_sms_4',
					'name' => esc_html__( 'Onboard Message 4', 'vestorfilter' ),
					'desc' => 'Sent 7 days after registration.',
				],[
					'type' => 'text',
					'id'   => 'onboard_forward_email',
					'name' => esc_html__( 'Forward Replies to (email)', 'vestorfilter' ),
				],[
					'type' => 'text',
					'id'   => 'onboard_sms_signature',
					'name' => esc_html__( 'SMS Message Signature', 'vestorfilter' ),
				],/*[
					'type' => 'text',
					'id'   => 'onboard_sms_from',
					'name' => esc_html__( 'SMS Message Reply-to', 'vestorfilter' ),
				],*/
			],
		];


		$meta_boxes[] = [
			'title'          => esc_html__( 'VestorFilter Filter Settings', 'vestorfilter' ),
			'id'             => 'vestorfilters_filters',
			'settings_pages' => 'vestorfilter',
			'tab'            => 'filters',
			//'visible'        => array( 'current_user_can("manage_options")', true ),
			'fields'         => [
				[
					'type'        => 'group',
					'id'          => 'filters_lot',
					'name'        => esc_html__( 'Available Lot Sizes', 'vestorfilter' ),
					'clone'       => true,
					'sort_clone'  => true,
					'collapsible' => true,
					'fields'      => [
						[
							'type' => 'text',
							'id'   => 'label',
							'name' => esc_html__( 'Lot Size Label', 'vestorfilter' ),
						],
						[
							'type' => 'text',
							'id'   => 'value',
							'name' => esc_html__( 'Lot Size Value', 'vestorfilter' ),
							'desc' => esc_html__( '(or, slug)', 'vestorfilter' ),
						],
						[
							'type' => 'text',
							'id'   => 'range',
							'name' => esc_html__( 'Actual Lot Size Range', 'vestorfilter' ),
							'desc' => esc_html__( 'format: MIN-MAX, example: 0-100', 'vestorfilter' ),
						],
						[
							'type'    => 'select',
							'id'      => 'terms',
							'name'    => esc_html__( 'Lot Categories in RETS', 'vestorfilter' ),
							'clone'   => true,
							'options' => $lot_options,
						],
					],
				],
				[
					'type'        => 'group',
					'id'          => 'filters_status',
					'name'        => esc_html__( 'Available Status Options', 'vestorfilter' ),
					'clone'       => true,
					'sort_clone'  => true,
					'collapsible' => true,
					'fields'      => [
						[
							'type' => 'text',
							'id'   => 'label',
							'name' => esc_html__( 'Status Label', 'vestorfilter' ),
						],
						[
							'type' => 'text',
							'id'   => 'value',
							'name' => esc_html__( 'Status Value', 'vestorfilter' ),
							'desc' => esc_html__( '(or, slug)', 'vestorfilter' ),
						],
						[
							'type'    => 'select',
							'id'      => 'terms',
							'name'    => esc_html__( 'Status Categories in RETS', 'vestorfilter' ),
							'clone'   => true,
							'options' => $status_options,
						],
					],
				],
				[
					'type'        => 'group',
					'id'          => 'filters_desc',
					'name'        => esc_html__( 'VestorFilter Descriptions', 'vestorfilter' ),
					'fields'      => $vf_fields,
				],
			],
		];

		$meta_boxes[] = [
			'title'          => esc_html__( 'Vestor Filter General Settings', 'vestorfilter' ),
			'id'             => 'vestorfilters_templates',
			'settings_pages' => 'vestorfilter',
			'tab'            => 'templates',
			'fields'         => [
				[
					'type' => 'heading',
					'name' => esc_html__( 'Templates', 'vestorfilter' ),
					'desc' => esc_html__( 'Assign Vestor Filter Functions to Existing Pages', 'vestorfilter' ),
				],
				[
					'type'    => 'select',
					'id'      => 'property_page',
					'name'    => esc_html__( 'Single Property', 'vestorfilter' ),
					'options' => $page_options,
				],
				[
					'type'    => 'select',
					'id'      => 'search_page',
					'name'    => esc_html__( 'Search Page', 'vestorfilter' ),
					'options' => $page_options,
				],
				[
					'type'    => 'select',
					'id'      => 'saved_page',
					'name'    => esc_html__( 'Favorites/Saved Properties Page', 'vestorfilter' ),
					'options' => $page_options,
				],
			]
		];

		$meta_boxes[] = [
			'title'          => esc_html__( 'VestorFilter Filter Settings', 'vestorfilter' ),
			'id'             => 'vestorfilters_tools',
			'settings_pages' => 'vestorfilter',
			'tab'            => 'tools',
			'fields'  => [
				[
					'type'       => 'button',
					'name'       => esc_html__( 'Download Lead Data', 'vestorfilter' ),
					'std'        => 'Export to CSV',
					'id'         => 'export',
					'attributes' => [
						'data-action' => 'export-data',
					]
				],
			],
		];

		$meta_boxes[] = [
			'title'          => esc_html__( 'Email Settings', 'vestorfilter' ),
			'id'             => 'vestorfilters_automation',
			'settings_pages' => 'vestorfilter',
			'tab'            => 'automation',
			'fields'         => [
				[
					'type' => 'checkbox',
					'id'   => 'automate_new',
					'name' => esc_html__( 'New Leads', 'vestorfilter' ),
					'desc' => esc_html__( 'Create New Tasks Automatically For New Leads', 'vestorfilter' )
						    . '<p style="margin-bottom:0">
								<em>Creates four follow up tasks for Agents when a user signs up on the site.</em><br>
								Follow up same day, next day, then on day four and day seven after initial registration.
								</p>',
					'std' => false,
				],[
					'type' => 'checkbox',
					'id'   => 'automate_hot',
					'name' => esc_html__( 'Hot Leads', 'vestorfilter' ),
					'desc' => esc_html__( 'Create Follow-Up Task When Lead Set to "Hot"', 'vestorfilter' )
					        . '<p style="margin-bottom:0"><em>Creates one follow-up task a week for 24 weeks after the lead has been tagged "Hot."</em></p>',
					'std' => true,
				],[
					'type' => 'checkbox',
					'id'   => 'automate_warm',
					'name' => esc_html__( 'Warm Leads', 'vestorfilter' ),
					'desc' => esc_html__( 'Create Follow-Up Task When Lead Set to "Warm"', 'vestorfilter' )
					       . '<p style="margin-bottom:0"><em>Creates one follow-up task a month for 24 months after the lead has been tagged "Warm."</em></p>',
					'std' => true,
				],[
					'type' => 'checkbox',
					'id'   => 'automate_cold',
					'name' => esc_html__( 'Cold Leads', 'vestorfilter' ),
					'desc' => esc_html__( 'Create Follow-Up Task When Lead Set to "Cold"', 'vestorfilter' )
					       . '<p style="margin-bottom:0"><em>Creates one follow-up task a year for 5 years after the lead has been tagged "Cold."</em></p>',
					'std' => true,
				],[
					'type' => 'checkbox',
					'id'   => 'automate_sold',
					'name' => esc_html__( 'Sold Leads', 'vestorfilter' ),
					'desc' => esc_html__( 'Create Follow-Up Task When Lead Set to "Sold"', 'vestorfilter' )
					       . '<p style="margin-bottom:0"><em>Creates one follow-up task a year for 5 years after the lead has been tagged "Sold."</em></p>',
					'std' => 'checked',
				],
			],
		];

		return $meta_boxes;

	}

	

	function hide_some_meta_boxes( $conditions ) {
		if ( current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<style>
			a[href="#tab-filters"] { display: none }
			a[href="#tab-templates"] { display: none }
		</style>
		<?php
	}
	
	function settings_page( $settings_pages ) {

		$settings_pages[] = array(
			//'parent'     => 'options-general.php',
			'id'         => 'vestorfilter',
			'menu_title' => 'Vestor Filter',
			'icon_url'   => 'dashicons-admin-multisite',
			'style'      => 'no-boxes',
			'columns'    => 1,
			'capability' => 'manage_vf_options',
			'position'   => 30,
			'tabs'       => array(
				'general'    => 'General Settings',
				'maps'       => 'Maps',
				'emails'     => 'Emails',
				'sms'        => 'SMS',
				'filters'    => 'Filters',
				'templates'  => 'Templates',
				'tools'      => 'Tools',
				'automation' => 'Auto Tasks',
			),
		);

		return $settings_pages;
	}
}

add_action( 'vestorfilter_installed', array( 'VestorFilter\Settings', 'init' ) );

/*
if ( ! empty( $rets ) ) {
	$classes = get_post_meta( $post_id, $prefix . 'classes' );
	$status = get_post_meta( $post_id, $prefix . 'status_field' ) ?: 'ListingStatus';
	$statuses = [];
	if ( ! empty( $classes ) ) {
		foreach( $classes as $class ) {
			$fields = $rets->GetTableMetadata( 'Property', $class );
			foreach ( $fields as $field ) {
				if ( ! empty( $field['LookupName'] ) ) {
					switch( $field['SystemName'] ) {
						case $status:
							$values = $rets->GetLookupValues( 'Property', $field['LookupName'] );
							foreach ( $values as $key => $value ) {
								$statuses[ $value['LongValue'] ] = $value['LongValue'];
							}
						break;
					}
				}
			}
		}
	}
}
*/
