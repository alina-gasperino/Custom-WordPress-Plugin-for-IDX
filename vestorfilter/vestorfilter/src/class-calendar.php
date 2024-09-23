<?php

namespace VestorFilter;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

class Calendar extends \VestorFilter\Util\Singleton {

	/**
	 * A pointless bit of self-reference
	 *
	 * @var object
	 */
	public static $instance = null;

	private static $agents = [];

	private static $filters = [];

	private static $labels = [];

	private static $table_name = 'vftasks';

	public function install() {

		global $wpdb;

		self::$table_name = $wpdb->prefix . self::$table_name;

		$this->set_labels();

		add_action( 'admin_menu', [ $this, 'add_page' ] );

		add_filter('wp_nav_menu_items', function ( $items, $args ) {
			if ( ! current_user_can( 'use_dashboard' ) ) {
				return $items;
			}
			if( $args->theme_location === 'site-navigation' ) {
				$items .= '<li class="menu-item mobile-only">'
					    . sprintf( '<a class="nav-link" href="%s">My Tasks</a>', admin_url( 'admin.php?page=calendar' ) )
					    . '</li>';
			}
			return $items;
		}, 10, 2);

		if ( ! get_option( '_tasks_table_version' ) ) {
			$this->install_table();
		} elseif ( absint( get_option( '_tasks_table_version' ) ) < 2 ) {
			$this->add_notes_column();
		}

		add_action( 'admin_init', [ $this, 'maybe_generate_new_tasks' ] );
		add_action( 'admin_init', [ $this, 'maybe_complete_task' ] );
		add_action( 'admin_init', [ $this, 'maybe_create_task' ] );
		add_action( 'admin_init', [ $this, 'maybe_save_note' ] );

		add_action( 'vestorfilter_user_created', [ $this, 'maybe_setup_new_user_tasks' ] );

	}

	private function install_table() {

		update_option( '_tasks_table_version', '1' );

		global $wpdb;

		$wpdb->query( '

		CREATE TABLE IF NOT EXISTS `' . self::$table_name . '` (
			`ID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			`task` varchar(50) NOT NULL,
			`user_id` bigint(20) unsigned DEFAULT NULL,
			`due` bigint(20) unsigned DEFAULT NULL,
			`created` bigint(20) unsigned DEFAULT NULL,
			`created_by` bigint(20) unsigned DEFAULT NULL,
			`completed` bigint(20) unsigned DEFAULT NULL,
			PRIMARY KEY (`ID`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

		');

	}

	private function add_notes_column() {

		update_option( '_tasks_table_version', '2' );

		global $wpdb;

		$wpdb->query( '

		ALTER TABLE `' . self::$table_name . '`
			ADD `notes` TEXT DEFAULT NULL;

		');

	}

	public function add_page() {

		add_menu_page(
			__( 'My Tasks & Calendar', 'vestorfilter' ),
			'My Tasks',
			'use_dashboard',
			'calendar',
			array( $this, 'admin_page' ),
			'dashicons-calendar',
			2
		);

	}

	public function admin_page() {

		$tz = new \DateTimeZone( 'UTC' );

		$lead_table = new Leads_Table( true );
		$lead_table->prepare_items( get_current_user_id() );
		$my_users = $lead_table->get_user_ids();

		if ( $my_users ) {

			$from = new \DateTime( '12:00 am', $tz );
			$to = new \DateTime( '23:59:59', $tz );

			if ( current_user_can( 'manage_agents' ) ) {

				$today = self::query_tasks( [
					'due'   => [ 'from' => $from->getTimestamp(), 'to' => $to->getTimestamp() ],
				] );

				$overdue = self::query_tasks( [
					'due'       => [ 'from' => 0, 'to' => $from->getTimestamp() ],
					'completed' => false,
				] );

			} else {

				$today = self::query_tasks( [
					'users' => $my_users,
					'due'   => [ 'from' => $from->getTimestamp(), 'to' => $to->getTimestamp() ],
				] );

				$overdue = self::query_tasks( [
					'users'      => $my_users,
					'due'       => [ 'from' => 0, 'to' => $from->getTimestamp() ],
					'completed' => false,
				] );

			}

		}

		?>
		<div class='wrap'>

			<h2>Today</h2>
			<?php if ( empty( $overdue ) && empty( $today ) ) : ?>
			<div class="notice notice-info"><p>You don't have any tasks assigned for today.</p></div>
			<?php else : ?>
				<table cellspacing="0" border="0" class="task-table">
					<tbody>
						<?php foreach( [ $overdue, $today ] as $tasks ) : ?>
							<?php foreach( $tasks as $task ) : ?>
							<?php 
							
							$user = get_user_by( 'id', $task->user_id ); 
							if ( $user ) {
								$agent = $user->get('_assigned_agent');
								if ( $agent ) {
									$agent = get_user_by( 'id', $agent );
								}
							}
							
							?>
							<?php $due = new \DateTime( '@' . $task->due ); $due->setTimezone( $tz ); ?>
							<tr <?php if ( $task->completed ) echo 'class="completed"' ?>>
								<td class="name"><?= self::get_label( $task->task ) ?>
								<?php if ( current_user_can( 'manage_agents' ) && $agent ) : ?>
								<small>Agent: <?= $agent->display_name ?></small>
								<?php endif; ?>
								</td>
								<td class="column-note">
									<?php echo self::make_note_editor_form( $task ); ?>
								</td>
								<td>Due <?= $due->format( 'Y-m-d h:i a' ) ?></td>
								<td><?php
									$user = get_user_by( 'id', $task->user_id );
									if ( $user ) :
										$name = trim( $user->first_name . ' ' . $user->last_name );
										if ( empty( $name ) ) {
											$name = $user->display_name;
										}
										printf( '<a href="%s">%s</a>',
											admin_url( 'admin.php?page=leads&user=' . $task->user_id ),
											$name,
										);
										?>
										<br><a target="_blank" href="mailto:<?= esc_attr( $user->user_email ) ?>"><?= esc_html( $user->user_email ) ?></a>
										<?php $phone = get_user_meta( $user->ID, 'phone', true ); if ( $phone ) : ?>
											<br><a target="_blank" href="tel:+1<?= esc_attr( $phone ) ?>"><?= esc_html( $phone ) ?></a>
										<?php endif; ?>
									<?php else : ?>
									No user
									<?php endif ?>
								</td>
								<td class='action'>
								<?php if ( $task->completed ) : ?>
									<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path class="inside" d="M10.933 13.519L8.707 11.293 7.293 12.707 11.067 16.481 16.769 9.641 15.231 8.359z"></path><path d="M19,3H5C3.897,3,3,3.897,3,5v14c0,1.103,0.897,2,2,2h14c1.103,0,2-0.897,2-2V5C21,3.897,20.103,3,19,3z M5,19V5h14 l0.002,14H5z"></path></svg>
								<?php else : ?>
									<a href="<?= add_query_arg( [
										'complete-task' => wp_create_nonce( 'complete-task-' . $task->ID ),
										'task' => $task->ID,
									] ); ?>"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path class="inside" d="M10.933 13.519L8.707 11.293 7.293 12.707 11.067 16.481 16.769 9.641 15.231 8.359z"></path><path d="M19,3H5C3.897,3,3,3.897,3,5v14c0,1.103,0.897,2,2,2h14c1.103,0,2-0.897,2-2V5C21,3.897,20.103,3,19,3z M5,19V5h14 l0.002,14H5z"></path></svg>
									</a>
								<?php endif; ?>
								</td>
							</tr>
							<?php endforeach; ?>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>

			<?php if ( isset( $_GET['task_success' ] ) ) : ?>
				<div class="notice notice-success"><p>Task created successfully.</p></div>
			<?php elseif ( isset( $_GET['task_error' ] ) ) : ?>
				<div class="notice notice-error"><p>There was an error creating your task.</p></div>
			<?php endif; ?>

			<?php self::new_task_form(); ?>

			<div id="poststuff" class="lead-table">
				<div class="metabox-holder columns-2">
					<div id="post-body-content">
						<div class="meta-box-sortables ui-sortable">
							<form method="post" class='calendar-lead-table'>
								<h3>Upcoming Tasks</h3>
								<?php



								//$lead_table->search_box( 'Search', 'search' );
								$lead_table->display();

								?>
							</form>
						</div>
					</div>
				</div>
				<br class="clear">
			</div>
			<style>
				.task-table {
					border-collapse: separate;
  					border-spacing: 0 5px;
					width: 100%;
				}
				.task-table tr {
					background: white;
					box-shadow: 0 2px 3px rgba(0,0,0,0.1);
				}
				@media (max-width:800px) {
					.task-table tr {
						display: flex;
						flex-direction: column;
						align-items: stretch;
						text-align: left;
						position: relative;
						margin-bottom: 5px;
					}
					.task-table tr td.name {
						width: calc( 100% - 50px );
					}
					.task-table tr td.action {
						padding: 0;
					}
					.task-table tr td.action a,
					.task-table tr td.action > svg {
						padding: 10px;
						display: block;
						position: absolute;
						top: 0;
						right: 0;
					}
				}
				.task-table tr.completed {
					opacity: 0.5;
				}
				.task-table tr td {
					padding: 10px;
				}
				.task-table tr td.name {
					font-size: 1.2em;
				}
				.task-table tr td.name small {
					font-size: 0.8em;
					display: block;
				}
				.task-table tr.completed td.name {
					text-decoration: line-through;
				}
				.task-table tr td.action {
					width: 44px;
				}
				.task-table tr td.action svg {
					width: 24px;
					height: 24px;
					fill: black;
				}
				.task-table tr td.action svg .inside {
					fill: black;
					opacity: 0;
				}
				.task-table tr td.action a:hover svg .inside {
					opacity: 1;
				}
				.task-table tr.completed td.action svg .inside {
					opacity: 1;
					fill: green;
				}
			</style>

		</div>
		<?php

	}

	public static function create_task( $task, $user_id = null, $due = null ) {

		global $wpdb;

		if ( ! empty( $due ) && ! is_numeric( $due ) && is_object( $due ) && $due instanceof \DateTime ) {
			$due = $due->getTimestamp();
		}

		$data = [
			'task'       => substr( $task, 0, 50 ),
			'created'    => time(),
			'created_by' => get_current_user_id() ?: null,
		];
		$prepare = [
			'%s',
			'%d',
			null
		];
		if ( ! empty( $user_id ) ) {
			$data['user_id'] = $user_id;
			$prepare[] = '%d';
		}
		if ( ! empty( $due ) ) {
			$data['due'] = $due;
			$prepare[] = '%d';
		}

		$wpdb->insert( self::$table_name, $data, $prepare );

	}

	private function set_labels() {

		self::$labels = [
			'new-lead-followup-1' => 'Call Lead - First Time',
			'new-lead-followup-2' => 'Call Lead - Second Time',
			'new-lead-followup-3' => 'Call Lead - Third Time',
			'new-lead-followup-4' => 'Call Lead - Fourth Time',
			'new-lead-contact'    => 'Follow-up With Lead',
		];

	}

	public static function get_label( $task ) {
		return self::$labels[ $task ] ?? ucwords( str_replace( ['_','-'], ' ', $task ) );
	}

	public function maybe_setup_new_user_tasks( $user ) {

		update_user_meta( $user->ID, '_lead_tag', 'new' );
		if ( Settings::get( 'automate_new' ) ) {
			Calendar::generate_new_user_tasks( $user->ID );
		}

	}

	public static function generate_new_user_tasks( $user_id ) {

		self::reset_user_tasks( $user_id );

		$now = new \DateTime( '@' . time() );
		$now->setTimezone( new \DateTimeZone( 'UTC' ) );

		self::create_task( 'new-lead-followup-1', $user_id, $now );

		$now->setTime( 10, 0 );
		
		self::create_task( 'new-lead-followup-2', $user_id, $now->modify( '+1 day' ) );
		self::create_task( 'new-lead-followup-3', $user_id, $now->modify( '+2 days' ) );
		self::create_task( 'new-lead-followup-4', $user_id, $now->modify( '+3 days' ) );

	}

	public function maybe_generate_new_tasks() {

		if ( isset( $_GET['page'] ) && $_GET['page'] === 'leads' && isset( $_GET['user'] ) && isset( $_GET['generate-new-tasks'] ) ) {

			$nonce = $_GET['generate-new-tasks'];
			if ( wp_verify_nonce( $nonce, 'generate-tasks-' . $_GET['user'] ) ) {

				self::generate_new_user_tasks( $_GET['user'] );

			}

			wp_safe_redirect( remove_query_arg( 'generate-new-tasks' ) );

		}

	}

	public function maybe_complete_task() {

		if ( isset( $_GET['complete-task'] ) && isset( $_GET['task'] ) ) {

			$task = absint( $_GET['task'] );
			$nonce = $_GET['complete-task'];
			if ( $task &&  wp_verify_nonce( $nonce, 'complete-task-' . $task ) ) {

				self::complete_task( $task );

			}

			wp_safe_redirect( remove_query_arg( [ 'complete-task', 'task' ] ) );

		}

	}

	public function maybe_save_note() {

		if ( isset( $_POST['edit_note'] ) && isset( $_POST['task'] ) ) {

			global $wpdb;

			$task = absint( $_POST['task'] );
			$nonce = $_POST['edit_note'];
			if ( $task &&  wp_verify_nonce( $nonce, 'edit-note-' . $task ) ) {

				$notes = filter_input( INPUT_POST, 'notes', FILTER_SANITIZE_STRING );
				$wpdb->update( self::$table_name, [ 'notes' => $notes ], [ 'ID' => $task ], [ '%s' ], [ '%d' ] );
				exit;

			}



		}

	}

	public static function make_note_editor_form( $entry, $edit_notes = true ) {

		$return = '<div class="note-text">' . str_replace( "\n",'<br>',$entry->notes ) . '</div>';
		$return .= '<div class="row-actions">';
		$return .= '<button type="button" data-action="open-task-notes">View</button>';
		if ( current_user_can( 'manage_agents' ) || $edit_notes ) {
			$return .= '<button type="button" data-action="open-task-editor">Edit Note</button></div>';
			$return .= '<div aria-hidden="true" class="note-editor">';
			$return .= '<textarea data-task="' . $entry->ID . '" data-nonce="' . wp_create_nonce( 'edit-note-' . $entry->ID ) . '" class="form-control note-editor" rows="4">' . $entry->notes . '</textarea>';
			$return .= '<button type="button" class="button" data-action="cancel-task-notes">Cancel</button>';
			$return .= '<button type="button" class="button button-primary" data-action="save-task-notes">Save</button>';
			$return .= '</div>';
		} else {
			$return .= '</div>';
		}

		return $return;

	}

	public function maybe_create_task() {

		if ( isset( $_POST['create_task'] ) ) {

			$url = remove_query_arg( ['task_error', 'task_success'], $_SERVER['REQUEST_URI'] );

			$nonce = $_POST['create_task'];
			$user_id = absint( $_POST['user_id'] ?? 0 );

			if ( empty( $user_id ) ) {
				$url = add_query_arg( 'task_error', 'no_user', $url );
				wp_safe_redirect( $url );
				exit;
			}

			$nonce_check = 'create-task-' . $user_id;
			if ( isset( $_POST['is_global'] ) ) {
				$nonce_check = 'create-task-' . get_current_user_id();
			}

			if ( $user_id && wp_verify_nonce( $nonce, $nonce_check ) ) {

				$name = filter_input( INPUT_POST, 'task', FILTER_SANITIZE_STRING );
				$date = filter_input( INPUT_POST, 'date', FILTER_SANITIZE_STRING );
				$time = filter_input( INPUT_POST, 'time', FILTER_SANITIZE_STRING );

				if ( empty( $name ) ) {
					$url = add_query_arg( 'task_error', 'name', $url );
				} elseif ( empty( $date ) || empty( $time ) ) {
					$url = add_query_arg( 'task_error', 'datetime', $url );
				} else {

					try {
						$datetime = new \DateTime( $date . ' ' . $time, new \DateTimeZone( get_option('timezone_string') ) );
						self::create_task( $name, $user_id, $datetime );
						$url = add_query_arg( 'task_success', 'yay', $url );
					} catch( \Exception $e ) {
						$url = add_query_arg( 'task_error', 'datetime', $url );
					}
				}

			}

			wp_safe_redirect( $url );
			exit;

		}

	}

	public static function new_task_form( $user_id = null ) {

		if ( empty( $user_id ) ) {

			$user_args = [
				'role'     => 'subscriber',
			];
			if ( ! current_user_can( 'manage_agents' ) ) {
				$user_args['meta_query'] = [
					[
						'key' => '_assigned_agent',
						'value' => get_current_user_id()
					],
					[
						'key' => '_lead_tag',
						'value' => 'ignore',
						'compare' => '!='
					],
				];
				$show_agents = false;
			} else {
				$user_args['meta_key'] = '_lead_tag';
				$user_args['meta_value'] = 'ignore';
				$user_args['meta_compare'] = '!=';
			}
			$users = get_users( $user_args );

			if ( current_user_can( 'manage_agents' ) ) {
				$agents = [];
				foreach( $users as $key => &$user ) {
					$agent = absint( $user->get('_assigned_agent') );
					if ( empty( $agent ) ) {
						unset( $users[$key] );
						continue;
					}
					$user->agent = $agent;
					$agents[ $agent ] = true;
				}
				/*usort( $users, function( $a, $b ) {
					if ( $a->agent === $b->agent ) {
						return 0;
					}
					return ($a->agent < $b->agent) ? -1 : 1;
				} );*/
				$show_agents = true;
			}
		}

		?>

		<form action="" method="POST" class="new-task">

			<?php if ( empty( $user_id ) ) : ?>
			<input type="hidden" name="is_global" value="true">
			<input type="hidden" name="create_task" value="<?= wp_create_nonce( 'create-task-' . get_current_user_id() ) ?>">
			<?php if ( $show_agents ) : ?>
			<select id="agent_select">
				<option value="">-- Select an agent --</option>
				<?php foreach( $agents as $user_id => $null ) :  ?>
				<?php $agent = get_user_by( 'id', $user_id ); if ( $agent ) : ?>
				<option value="<?= esc_attr( $agent->ID ) ?>"><?= esc_html( $agent->display_name ) ?></option>
				<?php endif; endforeach; ?>
			</select>
			<?php endif; ?>
			<select name="user_id">
			<?php if ( $show_agents ) : ?>
			<option value="">-- Select a lead --</option>
			<?php else : ?>
			<option value="">-- Select a lead --</option>
			<?php endif; ?>
			<?php

			$agent = 0;
			foreach( $users as $user ) {
				/*if ( $show_agents ) {
					$user_agent = $user->agent;
					if ( $user_agent !== $agent ) {
						$agent_user = get_user_by( 'id', $user_agent );
						if ( $agent_user ) {
							echo '</optgroup><optgroup label="' . $agent_user->display_name . '">';
						} else {
							echo '</optgroup><optgroup label="(Agent Deleted)">';
						}
						$agent = $user_agent;
					}
				}*/
				$name = trim( $user->first_name . ' ' . $user->last_name );
				if ( empty( $name ) ) {
					$name = $user->display_name;
				}
				$user_agent = $user->agent;
				echo '<option data-agent="' . $user_agent . '" value="' . $user->ID . '">' . $name . '</option>';
			}

			/*if ( $show_agents ) {
				echo '</optgroup>';
			}*/
			?>
			</select>

			<?php else : ?>
			<input type="hidden" name="create_task" value="<?= wp_create_nonce( 'create-task-' . $user_id ) ?>">
			<input type="hidden" name="user_id" value="<?= $user_id ?>">
			<?php endif; ?>

			
			<input type="date" name="date" value="<?= date( 'Y-m-d', strtotime( 'tomorrow' ) ) ?>" class="form-control">
			<input type="time" name="time" value="12:00:00" class="form-control">
			<input type="text" maxlength="50" name="task" value="" class="form-control" placeholder="Name the task">
			<button type="submit" class="button primary">Create New Task</button>
		</form>

		<?php

	}

	public static function generate_lead_tasks( $user_id, $level ) {

		$existing = self::query_tasks( [ 'user' => $user_id, 'task' => 'new-lead-contact', 'completed' => false ] );
		foreach( $existing as $task ) {
			if ( empty( $task->notes ) ) {
				self::delete_task( $task->ID );
			}
		}

		$on = Settings::get( 'automate_' . $level );
		if ( ! $on ) {
			return;
		}

		$now = new \DateTime( '@' . time() );
		$now->setTimezone( new \DateTimeZone( get_option('timezone_string') ) );

		switch( $level ) {
			case 'sold':
			case 'cold':
				for( $i = 0; $i < 5; $i ++ ) {
					$now->modify( '+1 year' );
					self::create_task( 'new-lead-contact', $user_id, $now );
				}
				break;
			case 'warm':
				for( $i = 0; $i < 24; $i ++ ) {
					$now->modify( '+1 month' );
					self::create_task( 'new-lead-contact', $user_id, $now );
				}
				break;
			case 'hot':
				for( $i = 0; $i < 24; $i ++ ) {
					$now->modify( '+7 days' );
					self::create_task( 'new-lead-contact', $user_id, $now );
				}
				break;
			default:
				return;
		}

	}

	public static function reset_user_tasks( $user_id, $task = null ) {

		global $wpdb;

		$query = $wpdb->prepare( 'DELETE FROM ' . self::$table_name . ' WHERE `user_id` = %d', $user_id );
		if ( ! empty( $task ) ) {
			$query .= $wpdb->prepare( ' AND `task` = %s', $task );
		}

		$wpdb->query( $query );

	}

	public static function complete_task( $task_id ) {

		global $wpdb;

		$wpdb->update( self::$table_name, [ 'completed' => time() ], [ 'ID' => $task_id ], ['%d'], ['%d'] );

	}

	public static function delete_task( $task_id ) {

		global $wpdb;

		$query = $wpdb->prepare( 'DELETE FROM ' . self::$table_name . ' WHERE `ID` = %d', $task_id );

		$wpdb->query( $query );

	}

	public static function get_user_tasks( $user_id, $status = null ) {

		global $wpdb;

		$query = $wpdb->prepare( 'SELECT * FROM ' . self::$table_name . ' WHERE user_id = %d', $user_id );
		if ( ! empty( $status ) ) {
			if ( $status === 'completed' ) {
				$query .= ' AND `completed` IS NOT NULL ORDER BY `completed` ASC';
			} elseif ( $status === 'open' ) {
				$query .= ' AND `completed` IS NULL ORDER BY `due` ASC';
			} elseif ( $status === 'overdue' ) {
				$query .= $wpdb->prepare( ' AND `completd` IS NULL AND `due` < %d ORDER BY `due` ASC', time() );
			}
		} else {
			$query .= ' ORDER BY `due` ASC';
		}


		return $wpdb->get_results( $query );

	}

	public static function query_tasks( $columns = [] ) {

		global $wpdb;

		$query = 'SELECT * FROM ' . self::$table_name;

		$where = [];
		foreach( $columns as $key => $values ) {
			switch( $key ) {
				case "task":
					$where[] = $wpdb->prepare( '`task` = %s', $values );
					break;
				case "users":
					$where[] = '`user_id` IN (' . implode( ',', $values ) . ')';
					break;
				case "user":
					$where[] = $wpdb->prepare( '`user_id` = %d', $values );
					break;
				case "due":
				case "completed":
				case "created":
					if ( is_bool( $values ) ) {
						$where[] = sprintf( "`$key` %s NULL", $values ? 'IS NOT' : 'IS' );
					} elseif ( is_array( $values ) ) {
						$where[] = $wpdb->prepare( "`$key` >= %d AND `$key` <= %d", $values['from'], $values['to'] );
					}
					$order = "ORDER BY `$key` ASC";
					break;
			}
		}
		if ( ! empty( $where ) ) {
			$query .= ' WHERE ' . implode( ' AND ', $where );
		}
		if ( ! empty( $order ) ) {
			$query .= ' ' . $order;
		}
		//var_dump( $query );

		return $wpdb->get_results( $query );

	}

}

add_action( 'vestorfilter_installed', array( 'VestorFilter\Calendar', 'init' ) );
