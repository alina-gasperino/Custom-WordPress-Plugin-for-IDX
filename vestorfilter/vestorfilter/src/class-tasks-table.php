<?php

namespace VestorFilter;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}


if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

add_action( 'agent_dashboard_active_tab__tasks', [ 'VestorFilter\Tasks_Table', 'tab_html' ], 10, 1 );

class Tasks_Table extends \WP_List_Table {

	private $user_id, $my_agent;

	public function __construct(  $user_id  ) {

		$this->user_id = absint( $user_id );

		$this->my_agent = absint( get_user_meta( $user_id, '_assigned_agent', true ) );

		parent::__construct( [
			'singular' => __( 'Tasks', 'vestorfilter' ),
			'plural'   => __( 'Tasks', 'vestorfilter' ),
			'ajax'     => false,
		] );


	}

	public static function tab_html( $user ) {

		$tasks_table = new Tasks_Table( $user->ID );
		$tasks_table->prepare_items();

		if ( $tasks_table->is_empty() ) {

		}

		?>

		<div id="favstuff">
			<div id="activity-table" class="metabox-holder columns-2">

				<div id="search-body-content">
					<?php if ( $tasks_table->is_empty() ) : ?>
					<p><a class="button" href="<?= add_query_arg( 'generate-new-tasks', wp_create_nonce( 'generate-tasks-' . $user->ID ) ) ?>">Generate New Lead Tasks</a></p>
					
					<?php endif; ?>
					<div class="meta-box-sortables ui-sortable">
						<form method="post">
							<?php $tasks_table->display(); ?>
						</form>
					</div>

					<?php Calendar::new_task_form( $user->ID ) ?>
				</div>
			</div>
			<style>
				.overdue {
					color: red;
				}
				
				
			</style>
		</div>

		<?php

	}

	function get_columns() {
		$columns = [
			'task'      => __( 'Task', 'vestorfilter' ),
			'note'      => __( 'Notes', 'vestorfilter' ),
			'created'   => __( 'Created', 'vestorfilter' ),
			'due'       => __( 'Due', 'vestorfilter' ),
			'completed' => __( 'Completed', 'vestorfilter' ),
		];

		return $columns;
	}

	function column_name( $entry ) {

		$task = Calendar::get_label( $entry->task );
		
		return $task;
	}

	function column_time( $time ) {

		if ( empty( $time ) ) {
			return '';
		}

		$date = new \DateTime( '@' . $time );
		$date->setTimezone( new \DateTimeZone( get_option('timezone_string') ) );
		
		return $date->format( ' Y-m-d h:i a' );
	}

	public function column_default( $entry, $column_name ) {

		switch( $column_name ) {
			case 'task':
				return $this->column_name( $entry );
				break;
			case 'note':
				$return = Calendar::make_note_editor_form( $entry, get_current_user_id() === $this->my_agent );
				return $return;
				break;
			case 'created':
				$time = '';
				if ( ! empty( $entry->created ) ) {
					$time = $this->column_time( $entry->created );
				}
				if ( ! empty( $entry->created_by ) ) {
					if ( absint( $entry->created_by ) === $this->user_id ) {
						$time .= '<br>Automatically Generated';
					} else {
						$agent = get_user_by( 'id', $entry->created_by );
						$time .= '<br>by ' . $agent->display_name;
					}
				}
				return $time;
				break;
			case 'due':
				$due = $this->column_time( $entry->due );
				if ( $entry->due < time() ) {
					$due = '<span class="overdue">' . $due . '</span>';
				}
				return $due;
				break;
			case 'completed':
				$completed = $this->column_time( $entry->completed );
				if ( empty( $completed ) ) {
					return sprintf( 
						'<a class="button" href="%s">Mark Complete</a>',
						add_query_arg( [
							'complete-task' => wp_create_nonce( 'complete-task-' . $entry->ID ),
							'task' => $entry->ID,
						] )
					);
				} else {
					return $completed;
				}
				break;
		}

	}

	public function prepare_items() {

		$this->_column_headers = array( $this->get_columns(), [], [] );

		$entries = Calendar::get_user_tasks( $this->user_id );

		$this->set_pagination_args( [
			'total_items' => count( $entries ),
			'per_page'    => count( $entries ),
		] );

		$this->items = $entries;
	}

	public function is_empty() {
		return empty( $this->items );
	}

}