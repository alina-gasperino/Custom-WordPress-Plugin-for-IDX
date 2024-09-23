<?php

namespace VestorFilter;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}


if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

add_action( 'agent_dashboard_active_tab__forms', [ 'VestorFilter\Form_Table', 'tab_html' ], 1 );

class Form_Table extends \WP_List_Table {

	private $user_id;

	public function __construct(  $user_id  ) {

		$this->user_id = $user_id;

		parent::__construct( [
			'singular' => __( 'Entry', 'vestorfilter' ),
			'plural'   => __( 'Entries', 'vestorfilter' ),
			'ajax'     => false,
		] );

	}

	public static function tab_html( $user ) {

		$forms_table = new Form_Table( $user->ID );

		?>

		<div id="formstuff">
			<div id="form-body" class="metabox-holder columns-2">
			
				<div id="form-body-content">
				
					<div class="meta-box-sortables ui-sortable">
						<form method="post">
							<?php
							
							$forms_table->prepare_items();
							$forms_table->display(); 
							
							?>
						</form>
					</div>
				</div>
			</div>
			<br class="clear">
		</div>

		<?php

	}

	function get_columns() {
		$columns = [
			'name'    => __( 'Form Name', 'vestorfilter' ),
			'created' => __( 'Submitted', 'vestorfilter' ),
			
		];
	  
		return $columns;
	}

	function column_name( $item ) {
	  
		\FrmForm::maybe_get_form( $item->form_id );
		
		$title = '<strong>' . $item->form_id->name . '</strong>';
	  
		$actions = [
			'searches' => sprintf( 
				'<a href="%s">View</a>', 
				admin_url() . 'admin.php?page=formidable-entries&frm_action=show&id=' . $item->id,
			),
		];
	  
		return $title . $this->row_actions( $actions );
	}

	public function column_default( $item, $column_name ) {

		switch( $column_name ) {
			case 'name':
				return $this->column_name( $item );
				break;
			case 'created':
				return $item->created_at;
				break;
			
		}

	}

	public function prepare_items() {

		$this->_column_headers = array( $this->get_columns(), [], [] );
	  		
		$entries = $this->get_entries( $this->user_id );

		if ( !is_array( $entries ) && !is_countable( $entries ) ) {
			$entries = []; // Fallback to empty array if it's not countable
		}

		$this->set_pagination_args( [
			'total_items' => count( $entries ),
			'per_page'    => count( $entries ),
		] );
	  
		$this->items = $entries;
	}

	public function get_entries( $user_id ) {
		if ( class_exists( 'FrmEntry' ) ){
			$entries = \FrmEntry::getAll(
				[ 'it.user_id' => $user_id ],
				' ORDER BY it.created_at DESC',
				8
			);

			return $entries;
		}

	}

}