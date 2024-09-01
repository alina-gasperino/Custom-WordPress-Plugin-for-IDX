<?php

namespace VestorFilter;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

class Export extends \VestorFilter\Util\Singleton {

	/**
	 * A pointless bit of self-reference
	 *
	 * @var object
	 */
	public static $instance = null;

	public function install() {

		if ( is_admin() && current_user_can( 'manage_options' ) ) {
			add_action( 'rwmb_enqueue_scripts', [ $this, 'export_data_script' ] );
			add_action( 'admin_init', [ $this, 'maybe_export_data' ] );
		}

	}

	public function export_data_script() {

		wp_register_script( 'vf-export-data', Plugin::$plugin_uri . '/assets/js/export.js', array( 'jquery' ), '20210315', true );
		wp_localize_script(
			'vf-export-data',
			'vfExportData',
			[
				'url' => add_query_arg( 'export-lead-data', wp_create_nonce( 'export-lead-data' ), admin_url( 'admin.php' ) ),
			]
		);
		wp_enqueue_script( 'vf-export-data' );

	}

	public function maybe_export_data() {

		if ( ! isset( $_GET['export-lead-data'] ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$nonce = $_GET['export-lead-data'];
		if ( ! wp_verify_nonce( $nonce, 'export-lead-data' ) ) {
			return;
		}

		self::generate_data();

	}

	public function generate_data() {

		$role = 'subscriber';

		$args = [ 'number' => -1, ];
		if ( ! empty( $role ) ) {
			$args['role'] = $role;
		}

		$rows     = [];
		$metadata = [];
		$headers  = [];

		$users = get_users( $args );
		foreach( $users as $user ) {
			$rows[ $user->ID ]     = $user->to_array();
			$metadata[ $user->ID ] = get_user_meta( $user->ID );
		}

		header("Content-type: text/csv");
		header("Content-Disposition: attachment; filename=vestorfilter-export.csv");
		header("Pragma: no-cache");
		header("Expires: 0");

		$out = fopen('php://output', 'w');

		$headers = [
			'meta:first_name'      => 'First Name',
			'meta:last_name'       => 'Last Name',
			'row:user_email'       => 'Email',
			'meta:phone'           => 'Phone',
			'meta:goal'            => 'Goal',
			'user:_assigned_agent' => 'Agent',
			'meta:_lead_tag'       => 'Tag',
			'row:user_registered'  => 'Registered (UTC)',
			'time:_last_contact'   => 'Contacted (UTC)',
			'meta:_user_notes'     => 'Notes',
			'callback:saved_homes' => 'Saved Homes (MLS ID)',
		];

		fputcsv( $out, $headers );

		$now = new \DateTime( "now", new \DateTimeZone( get_option('timezone_string') ) );

		foreach( $rows as $user_id => $user ) {
			$meta = $metadata[ $user_id ];
			$data = [];
			foreach( $headers as $header => $label ) {
				list( $type, $key ) = explode( ':', $header );
				$value = '';
				switch( $type ) {
					case "row":
						$value = $user[ $key ] ?? '';
						break;
					case "meta":
						if ( empty( $meta[ $key ] ) ) {
							$value = '';
						} else {
							$value = reset( $meta[ $key ] );
						}
						break;
					case "time":
						if ( empty( $meta[ $key ] ) ) {
							$value = '';
						} else {
							$value = date( 'm/d/Y H:i', $meta[$key][0]  );
						}
						break;
					case "user":
						if ( empty( $meta[ $key ] ) ) {
							$value = '';
						} else {
							$agent = get_user_by( 'id', $meta[$key][0] );
							if ( $agent ) {
								$value = $agent->display_name;
							}
						}
						break;
					case "callback":
						$value = call_user_func( [ $this, $key ], $user, $meta );
						break;
				}
				$data[] = $value;
			}

			fputcsv( $out, $data );

		}

		fclose( $out );
		exit;

	}

	public function saved_homes( $user, $meta ) {

		if ( empty( $meta['_favorite_property'] ) ) {
			return '';
		}

		$homes = [];
		foreach( $meta['_favorite_property'] as $property_id ) {
			if ( empty( $property_id ) ) {
				continue;
			}
			$property = Cache::get_property_by( 'ID', $property_id );
			if ( ! empty( $property ) ) {
				$homes[] = $property[0]->MLSID;
			}
		}

		return implode( ', ', $homes );

	}

}

add_action( 'vestorfilter_installed', array( 'VestorFilter\Export', 'init' ) );
