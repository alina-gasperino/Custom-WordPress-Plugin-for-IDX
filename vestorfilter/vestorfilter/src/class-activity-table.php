<?php

namespace VestorFilter;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}


if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

add_action( 'agent_dashboard_active_tab__activity', [ 'VestorFilter\Activity_Table', 'tab_html' ], 10, 1 );


class Activity_Table extends \WP_List_Table {

	private $user_id, $subs = [], $searches = [];

	public function __construct(  $user_id  ) {

		$this->user_id = absint( $user_id );

		parent::__construct( [
			'singular' => __( 'Activity', 'vestorfilter' ),
			'plural'   => __( 'Activity', 'vestorfilter' ),
			'ajax'     => false,
		] );


	}

	public static function tab_html( $user ) {

		$activity_table = new Activity_Table( $user->ID );

		?>

		<div id="favstuff">
			<div id="activity-table" class="metabox-holder columns-2">

				<div id="search-body-content">

					<div class="meta-box-sortables ui-sortable">
						<form method="post">
							<?php

							$activity_table->prepare_items();
							$activity_table->display();

							?>
						</form>
					</div>
				</div>
			</div>
			<style>
				.search-query-list__filter .label::after {
					content: ': ';
				}
				.search-query-list__filter + .search-query-list__filter::before {
					content: '; ';
				}
			</style>
		</div>

		<?php

	}

	function get_columns() {
		$columns = [
			'time'   => __( 'Time', 'vestorfilter' ),
			'what' => __( 'Action', 'vestorfilter' ),
			'value'  => __( 'Activity', 'vestorfilter' ),
			'agent'  => __( 'Performed By', 'vestorfilter' ),
		];

		return $columns;
	}

	function column_name( $entry ) {

		$date = new \DateTime( '@' . $entry->log_time );
		$date->setTimezone( new \DateTimeZone( get_option('timezone_string' ) ) );

		$title = '<strong>' . $date->format( 'h:i:s a Y-m-d' ) . '</strong>';
		return $title;
	}

	function column_action( $action ) {

		switch( $action ) {
			case 'logged-in':
				return 'Log In';
			case 'registered':
				return 'Registered';
			case 'search-subscribed':
				return 'Subscribed to Search';
			case 'search-saved':
				return 'Saved a Search';
			case 'favorite-saved':
				return 'Saved a Property';
			default:
				return $action;
		}

	}

	function column_activity( $entry ) {

		switch( $entry->log_action ) {
			
			case 'favorite-saved':
				$property = Cache::get_property_by( 'ID', $entry->log_property );
				if ( empty( $property ) ) {
					return 'No longer available';
				}
				return sprintf( '<a href="%s">%s</a>', Property::make_page_url( $property[0]->MLSID, $property[0]->slug ), $property[0]->MLSID );
				break;

			case 'search-saved':
			case 'search-subscribed':
				if ( empty( $this->searches[ $entry->log_value ] ) ) {
					return 'No longer available';
				}
				extract( Blocks\SavedSearches::get_search_label_html( $this->searches[ $entry->log_value ] ) );

				return $label;

			default:
				return '';
		}


	}

	public function column_default( $entry, $column_name ) {

		switch( $column_name ) {
			case 'time':
				return $this->column_name( $entry );
				break;
			case 'what':
				return $this->column_action( $entry->log_action );
				break;
			case 'value':
				return $this->column_activity( $entry );
				break;
			case 'agent':
				if ( empty( $entry->performed_by ) || absint( $entry->performed_by ) === $this->user_id ) {
					return 'User';
				}

				$agent = get_user_by( 'id', $entry->performed_by );
				if ( empty( $agent ) ) {
					return 'Unknown agent';
				}
				return $agent->display_name;

				break;
		}

	}

	public function prepare_items() {

		$this->_column_headers = array( $this->get_columns(), [], [] );

		$entries = Log::get_user_entries( $this->user_id );

		$this->subs = get_user_meta( $this->user_id, '_query_subscriptions', true ) ?: [];
		$this->searches = Favorites::get_searches( $this->user_id );
		foreach( $this->searches as $hash => $query ) {
			$this->searches[$hash] = json_decode( $query );
		}

		$this->set_pagination_args( [
			'total_items' => count( $entries ),
			'per_page'    => count( $entries ),
		] );

		$this->items = $entries;
	}

}