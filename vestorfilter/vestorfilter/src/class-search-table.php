<?php

namespace VestorFilter;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}


if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

add_action( 'agent_dashboard_active_tab__saved', [ 'VestorFilter\Search_Table', 'tab_html' ], 1 );

class Search_Table extends \WP_List_Table {

	private $user_id, $subs;

	public function __construct(  $user_id  ) {

		$this->user_id = $user_id;

		parent::__construct( [
			'singular' => __( 'Query', 'vestorfilter' ),
			'plural'   => __( 'Queries', 'vestorfilter' ),
			'ajax'     => false,
		] );


	}

	public static function tab_html( $user ) {

		$search_table = new Search_Table( $user->ID );

		?>

		<div id="searchstuff">
			<div id="home-table" class="metabox-holder columns-2">

				<div id="home-body-content">

					<form class="add-search-wrapper" method="post">
						<?php wp_nonce_field( 'lead_management_' . $user->ID ); ?>
						<label for="add-search">Paste a search URL:</label>
						<input name="new_search" id="add-search" class="add-search__input form-control" data-add-search>
						<button type="submit" class="add-search__save button button-secondary">Save</button>
					</form>

					<div class="meta-box-sortables ui-sortable">
						<form method="post">
							<?php

							$search_table->prepare_items();
							$search_table->display();

							?>
						</form>
					</div>
				</div>
			</div>
			<br class="clear">
			<style>
				.add-search-wrapper {
					display: flex;
					align-items: center;
					margin-bottom: 0.5em;
					flex-wrap: wrap;
				}
				.add-search-wrapper label {
					margin-right: 0.5em;
				}
				.add-search__input {
					width: 100%;
					max-width: 400px;
					height: 28px;
				}
				.wp-core-ui .add-search__save.button-secondary {
					height: 28px;
					min-height: 28px;
				}
				.search-query-list__filter .label::after {
					content: ': ';
				}
				.search-query-list__filter + .search-query-list__filter::before {
					content: '; ';
				}
				tr.is-editing .subscription-label,
				tr:not(.is-editing) select {
					display: none;
				}
				tr.is-editing .query-name,
				tr:not(.is-editing) input {
					display: none;
				}
				tr.is-editing button[data-edit-row],
				tr.is-editing button[data-delete-row],
				tr:not(.is-editing) button[data-save-row] {
					display: none;
				}
				th.column-edit {
					width: 120px;
				}
				.name.column-name input {
					width: 100%;
					height: 28px;
				}
				@media screen and (max-width:600px) {
					input.add-search__input {
						width: calc( 100% - 90px );
						max-width: 100%;
						height: 40px;
						margin-right: 10px;
					}
					.wp-core-ui .add-search__save.button-secondary {
						height: 40px;
						min-height: 40px;
						width: 78px;
						margin: 0;
					}
				}
			</style>
			<script>
			jQuery( function($) {
				$('button[data-edit-row]').on("click", function(e) {
					$(e.currentTarget).closest('tr').addClass('is-editing');
				});
				$('button[data-delete-row]').on("click", function(e) {
					var $this = $(e.currentTarget);
					var $parentRow = $this.closest('tr');
					
					$.post('<?=$_SERVER['REQUEST_URI']?>',{
						delete: true,
						hash: $this.data('delete-row'),
						user: $this.data('user'),
						_wpnonce: $this.data('nonce')
					});

					$parentRow.slideUp();
				});
				$('button[data-save-row]').on("click", function(e) {
					var $this = $(e.currentTarget);
					var $parentRow = $this.closest('tr');
					var $nameLabel = $parentRow.find('.query-name');
					var $nameInput = $nameLabel.next('input');
					if ( $nameInput.val().length > 0 ) {
						$nameLabel.html( $nameInput.val() );
					} else {
						$nameLabel.html( 'Saved Search Query' );
					}
					var $subLabel = $parentRow.find('.subscription-label');
					var $subInput = $subLabel.next('select');
					if ( $subInput.val() !== '' ) {
						$subLabel.html( $subInput.find( 'option[value="' + $subInput.val() + '"]' ).html() );
					}

					$.post('<?=$_SERVER['REQUEST_URI']?>',{
						subscription: $subInput.val(),
						search_name: $nameInput.val(),
						hash: $this.data('save-row'),
						user: $this.data('user'),
						_wpnonce: $this.data('nonce')
					})

					$parentRow.removeClass('is-editing');
				});
			} );
		</script>
		</div>

		<?php

	}

	function get_columns() {
		$columns = [
			'name'  => __( 'Query Name', 'vestorfilter' ),
			'query' => __( 'Search Query', 'vestorfilter' ),
			'freq'  => __( 'Subscription', 'vestorfilter' ),
			'agent'  => __( 'Added by', 'vestorfilter' ),
			'log'    => __( 'Log', 'vestorfilter' ),
			'edit'  => __( 'Actions', 'vestorfilter' ),

		];

		return $columns;
	}

	function query_column( $item ) {

		extract( Blocks\SavedSearches::get_search_label_html( $item['query'] ) );

		$title = '<strong>' . $label . '</strong>';

		$actions = [
			'searches' => sprintf(
				'<a href="%s" target="_blank">View Results</a>',
				$url,
			),
		];

		return $title . $this->row_actions( $actions );
	}

	function column_name( $item ) {

		$query = $item['query'];
		if ( ! empty( $query->name ) ) {
			$html = '<span class="query-name">' . $query->name . '</span>';
		} elseif ( ! empty( $query->dynamic ) ) {
			$html = '<span class="query-name">Dynamic Smart Search</span>';
		} else {
			$html = '<span class="query-name">Saved Search Query</span>';
		}

		$html .= sprintf(
			'<input placeholder="Enter a name for this search" class="form-control" data-nonce="%s" data-user="%d" data-hash="%s" value="%s" data-update-query-name>',
			wp_create_nonce( 'lead_management_' . $this->user_id ),
			$this->user_id,
			$item['hash'],
			esc_attr( $query->name ?? '' )
		);

		return $html;

	}

	public function column_default( $item, $column_name ) {

		switch( $column_name ) {
			case 'name':
				return $this->column_name( $item );
				break;
			case 'query':
				return $this->query_column( $item );
				break;
			case 'agent':
				if ( empty( $item['added_by'] ) || is_wp_error( $item['added_by'] ) ) {
					return 'User';
				}
				return $item['added_by']->display_name;
				break;
			case 'edit':
				//if ( empty( $item['added_by'] ) || is_wp_error( $item['added_by'] ) ) {
				//	return;
				//}
				return sprintf(
					'<button type="button" class="button button-secondary" data-edit-row>Edit</button>
					 <button type="button" class="button button-secondary" data-delete-row="%1$s" data-nonce="%3$s">Delete</button>
					 <button type="button" class="button button-primary" data-save-row="%1$s" data-user="%2$s" data-nonce="%3$s">Save</button>',
					 $item['hash'],
					 $this->user_id,
					 wp_create_nonce( 'lead_management_' . $this->user_id )
				);
				break;
			case 'freq':
				$hash = $item['hash'];
				if ( ! isset( $this->subs[ $hash ] ) ) {
					$freq = 'Never';
				} else {
					switch( $this->subs[ $hash ] ) {
						case '0':
							$freq = 'immediate';
						break;
						case '1':
							$freq = 'daily';
						break;
						case '7':
							$freq = 'weekly';
						break;
						case '30':
							$freq = 'monthly';
						break;
						case 'never':
						default:
							$freq = 'never';
						break;
					}
				}
				$html = '<span class="subscription-label">' . ucwords( $freq ) . '</span>';

				$html .= sprintf(
					'<select data-nonce="%s" data-user="%d" data-hash="%s" data-update-subscription>',
					wp_create_nonce( 'lead_management_' . $this->user_id ),
					$this->user_id,
					$hash
				);
				$html .= '<option value="">(No change)</option>';
				foreach ( self::subscriptions_allowed() as $value => $label ) {
					$html .= sprintf( '<option value="%s" %s>%s</option>', $value, selected( strtolower( $label ), $freq, false ), $label );
				}
				$html .= '</select>';
				return $html;
				break;
			case 'log':
				$log = Log::search( [ 'user_id' => $this->user_id, 'action' => 'search-saved', 'value' => $item['hash'] ] );
				if ( ! empty( $log ) ) {
					$date = new \DateTime( '@' . $log[0]->log_time );
					$date->setTimezone( new \DateTimeZone( get_option('timezone_string' ) ) );
					return $date->format( 'h:i:s a, Y-m-d' );
				}
				return '';
				break;
		}

	}

	public function subscriptions_allowed() {

		return [
			'never' => 'Never',
//			'0'  => 'Immediate',
			'1'  => 'Daily',
			'7'  => 'Weekly',
			'30' => 'Monthly',
		];

	}

	public function prepare_items() {

		$this->_column_headers = array( $this->get_columns(), [], [] );

		$entries = $this->get_entries( $this->user_id ) ?: [];

		$this->set_pagination_args( [
			'total_items' => count( $entries ),
			'per_page'    => count( $entries ),
		] );

		$this->subs = get_user_meta( $this->user_id, '_query_subscriptions', true ) ?: [];


		$this->items = $entries;
	}

	public function get_entries( $user_id ) {

		$entries = Favorites::get_searches( $user_id );
		$indexed = [];

		foreach ( $entries as $hash => $query ) {
			$this_query = [
				'hash'  => $hash,
				'query' => json_decode( $query ),
			];

			if ( ! empty( $this_query['query']->added_by ) ) {
				$this_query[ 'added_by' ] = get_user_by( 'id', $this_query['query']->added_by );
			}
			$indexed[] = $this_query;
		}



		return $indexed;

	}

}