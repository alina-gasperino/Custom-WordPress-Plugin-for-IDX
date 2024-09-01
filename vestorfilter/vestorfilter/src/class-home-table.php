<?php

namespace VestorFilter;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}


if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

add_action( 'agent_dashboard_active_tab__homes', [ 'VestorFilter\Home_Table', 'tab_html' ], 10, 1 );


class Home_Table extends \WP_List_Table {

	private $user_id, $recommendations = [];

	public function __construct(  $user_id  ) {

		$this->user_id = $user_id;

		parent::__construct( [
			'singular' => __( 'Home', 'vestorfilter' ),
			'plural'   => __( 'Homes', 'vestorfilter' ),
			'ajax'     => false,
		] );


	}

	public static function tab_html( $user ) {

		$home_table = new Home_Table( $user->ID );

		?>

		<div id="favstuff">
			<div id="search-table" class="metabox-holder columns-2">
			
				<div id="search-body-content">
				
					<form class="add-favorite-wrapper" method="post">
						<?php wp_nonce_field( 'lead_management_' . $user->ID ); ?>
						<label for="add-favorite">Add home to user favorites:</label>
						<select name="new_favorite" id="add-favorite" class="add-favorite__select form-control" data-add-favorite></select>
						<button type="submit" class="add-favorite__save button button-secondary">Add</button>
					</form>
					
					<div class="meta-box-sortables ui-sortable">
						<form method="post">
							<?php
							
							$home_table->prepare_items();
							$home_table->display(); 
							
							?>
						</form>
					</div>
				</div>
			</div>
			<br class="clear">
			<style>
				.add-favorite__select {
					width: 100%;
					max-width: 400px;	
					height: 28px;
				}
				.wp-core-ui .add-favorite__save.button-secondary {
					height: 28px;
					min-height: 28px;
				}
				.add-favorite__select + .select2 {
					width: 100%;
					max-width: 400px;
					margin-right: 0.5em;
					align-self: stretch;
				}
				@media screen and (max-width:600px) {
					.add-favorite__select {
						width: calc( 100% - 80px );
						max-width: 100%;	
						height: 40px;
					}
					.add-favorite__select + .select2 {
						width: calc( 100% - 80px );
						max-width: 100%;
						height: 40px;
						margin-right: 10px;
						align-self: stretch;
					}
					.wp-core-ui .add-favorite__save.button-secondary {
						height: 40px;
						min-height: 40px;
						width: 70px;
					}
					.add-favorite__select + .select2 .select2-selection--single {
						height: 40px;
					}
					.add-favorite__select + .select2 .select2-selection--single .select2-selection__rendered {
						line-height: 38px;
					}
					.add-favorite__select + .select2 .select2-selection--single .select2-selection__arrow {
						height: 38px;
					}
				}
				.add-favorite-wrapper {
					width: 100%;
					display: flex;
					flex-wrap: wrap;
					align-items: center;
					margin-bottom: 0.5em;					
				}
				.add-favorite-wrapper label {
					margin-right: 0.5em;
				}
			</style>
			<script>
				jQuery('[data-add-favorite]').select2({
					minimumInputLength: 3,
					ajax: {
						url: '<?= Search::get_exact_query_endpoint() ?>',
						dataType: 'json',
						
						data: function ( params ) {
							var query;
							if ( ! params.term ) {
								return {
									for:   'mlsid',
									query: '',
								};
							}
							if ( params.term.length > 5 && ! isNaN( params.term ) ) {
								query = {
									for:   'mlsid',
									query: params.term
								}
							} else {
								query = {
									for:   'address',
									query: params.term
								}
							}
							return query;
						},
						processResults: function ( data ) {
							if ( data.length === 0 ) {
								return [];
							}
							var items = [];
							for( var i = 0; i < data.length; i ++ ) {
								items.push( { id: data[i].id, text: data[i].sublabel + ' (' + data[i].label + ')' } );
							}
							return { results: items };
						}
					}
				});
			</script>
		</div>

		<?php

	}

	function get_columns() {
		$columns = [
			'name'    => __( 'Home Address', 'vestorfilter' ),
			'mlsid'    => __( 'MLSID', 'vestorfilter' ),
			'status'    => __( 'Status', 'vestorfilter' ),
			'agent'    => __( 'Added By', 'vestorfilter' ),
			'log'    => __( 'Logged', 'vestorfilter' ),
		];
	  
		return $columns;
	}

	function column_name( $property ) {
	
		$title = '<strong>' . $property->get_address_string() . '</strong>';
	  
		$actions = [
			'searches' => sprintf( 
				'<a href="%s" target="_blank">View Property</a>', 
				$property->get_page_url(),
			),
		];
	  
		return $title . $this->row_actions( $actions );
	}

	public function column_default( $property, $column_name ) {

		switch( $column_name ) {
			case 'name':
				return $this->column_name( $property );
				break;
			case 'mlsid':
				return $property->MLSID();
				break;
			case 'status':
				$hidden = Cache::get_data_value( $property->ID(), 'hidden' );
				return ! empty( $hidden ) ? 'Unavailable' : '';
				break;
			case 'agent':
				if ( $friend = get_user_meta( $this->user_id, '_friend_favorite_' . $property->ID() ) ) {
					return 'Friend';
				} else if ( empty( $this->recommendations[ $property->ID() ] ) ) {
					return 'User';
				} 

				$agent = get_user_by( 'id', $this->recommendations[ $property->ID() ] );
				if ( empty( $agent ) ) {
					return 'Unknown agent';
				}				
				return $agent->display_name;
				
				break;
			case 'log':
				$log = Log::search( [ 'user_id' => $this->user_id, 'action' => 'favorite-saved', 'property' => $property->ID() ] );
				if ( ! empty( $log ) ) {
					$date = new \DateTime( '@' . $log[0]->log_time );
					$date->setTimezone( new \DateTimeZone( get_option('timezone_string' ) ) );
					return $date->format( 'h:i:s a, Y-m-d' );
				}
				return '';
				break;
		}

	}

	public function prepare_items() {

		$this->_column_headers = array( $this->get_columns(), [], [] );
	  		
		$entries = $this->get_entries( $this->user_id );

		$this->set_pagination_args( [
			'total_items' => count( $entries ),
			'per_page'    => count( $entries ),
		] );
	  
		$recommendations = get_user_meta( $this->user_id, '_agent_recommendation' );
		foreach( $recommendations as $rec ) {
			$this->recommendations[ $rec['property'] ] = $rec['agent'];
		}
		$this->items = $entries;
	}

	public function get_entries( $user_id ) {

		$entries = array_merge( Favorites::get_all( $user_id ), Favorites::get_friend_properties( $user_id ) );
		$loaded = [];
		foreach( $entries as $property_id ) {
			if ( ! $property_id ) {
				continue;
			}
			$property = new Property( $property_id, false );
			if ( ! is_null( $property->ID() ) ) {
				$loaded[] = $property;
			}
		}

		return $loaded;

	}

}