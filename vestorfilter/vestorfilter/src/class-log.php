<?php

namespace VestorFilter;


if ( ! defined( 'ABSPATH' ) ) {
	die();
}

class Log extends \VestorFilter\Util\Singleton {

	/**
	 * A pointless bit of self-reference
	 *
	 * @var object
	 */
	public static $instance = null;

	public static $rets = null;

	public static $table_name = 'vflog';

	public function install() {

		global $wpdb;

		self::$table_name = $wpdb->prefix . self::$table_name;

	}

	public static function search( $params ) {

		global $wpdb;

		$query = [
			'log_action'   => sanitize_title( $params['action'] ?? '' ) ?: null,
			'user_id'      => absint( $params['user'] ?? 0 ) ?: null,
			'log_property' => absint( $params['property'] ?? 0 ) ?: null,
			'log_value'    => $params['value'] ?? null,
		];

		$where = [];
		foreach( $query as $key => $value ) {
			if ( ! is_null( $value ) ) {
				$where[] = $wpdb->prepare( is_int( $value ) ? "$key = %d" : "$key = %s", $value );
			}
		}

		if ( empty( $where ) ) {
			return false;
		}

		$where_string = implode( ' AND ', $where );
		$table_name = self::$table_name;
		$query = "SELECT * FROM {$table_name} WHERE {$where_string}";

		$query .= ' ORDER BY log_time ' . ( $params['order'] ?? 'DESC' === 'DESC' ? 'DESC' : 'ASC' );

		if ( ! empty( $params['limit'] ) ) {
			$query .= ' LIMIT ' . absint( $params['offset'] ?? 0 ) . ',' . absint( $params['limit'] );
		}

		return $wpdb->get_results( $query );

	}

	public static function get_user_entries( $user_id ) {

		return self::search( [ 'user' => $user_id ] );

	}

	public static function add( $params ) {

		$query = [
			'log_action'   => sanitize_title( $params['action'] ?? '' ),
			'log_value'    => $params['value'] ?? null,
			'user_id'      => absint( $params['user'] ?? 0 ) ?: null,
			'performed_by' => isset( $params['performed_by'] ) && is_null( $params['performed_by'] )
							? null 
							: ( absint( $params['performed_by'] ?? 0 ) ?: get_current_user_id() ),
			'log_property' => absint( $params['property'] ?? 0 ) ?: null,
			'log_time'     => absint( $params['time'] ?? time() ),
		];

		if ( strlen( $query['log_value'] ) > 50 ) {
			$query['log_value'] = substr( $query['log_value'], 0, 50 );
		}

		global $wpdb;
		if ( ! $wpdb->insert( 
			self::$table_name, 
			$query,
			[
				'%s',
				is_null( $query['log_value'] ) ? null : '%s',
				is_null( $query['user_id'] ) ? null : '%d',
				is_null( $query['performed_by'] ) ? null : '%d',
				is_null( $query['log_property'] ) ? null : '%d',
				'%d',
			]
		) ) {
			return false;
		}

		return $wpdb->insert_id;

	}

}

add_action( 'vestorfilter_installed', array( 'VestorFilter\Log', 'init' ) );

