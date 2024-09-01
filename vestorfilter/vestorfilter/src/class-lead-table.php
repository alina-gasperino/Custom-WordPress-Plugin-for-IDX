<?php

namespace VestorFilter;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}


if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Leads_Table extends \WP_List_Table {

	private $is_agent_table = false, $users = [];

	public function __construct( $agent_table = false ) {

		parent::__construct( [
			'singular' => __( 'Lead', 'vestorfilter' ),
			'plural'   => __( 'Leads', 'vestorfilter' ),
			'ajax'     => false,
		] );

		$this->is_agent_table = $agent_table;

	}

	function get_columns() {
		$columns = [
			'name'    => __( 'Name', 'vestorfilter' ),
			'phone'   => __( 'Phone', 'vestorfilter' ),
			'email'   => __( 'Email', 'vestorfilter' ),
			'goal'    => __( 'Goal', 'vestorfilter' ),
			'agent'   => __( 'Agent', 'vestorfilter' ),
			'created' => __( 'Registered', 'vestorfilter' ),
			'login'   => __( 'Last Login', 'vestorfilter' ),
			'contact' => __( 'Contact Attempt', 'vestorfilter' ),
			'tag'     => __( 'Tag', 'vestorfilter' ),
		];

		if ( $this->is_agent_table ) {
			if ( ! current_user_can( 'manage_agents' ) ) {
				unset( $columns['agent'] );
			}
			unset( $columns['created'] );
			unset( $columns['login'] );
			unset( $columns['tag'] );
			$columns['tasks'] = __( 'Tasks', 'vestorfilter' );
			
		}
	  
		return $columns;
	}

	function get_sortable_columns() {

		$sortable_columns = $this->get_columns();

		unset( $sortable_columns['name'] );
		unset( $sortable_columns['lname'] );
		unset( $sortable_columns['phone'] );
		unset( $sortable_columns['email'] );

		foreach( $sortable_columns as $key => $value ) {
			$sortable_columns[ $key ] = [ $key, 'asc' ];
		}

		return $sortable_columns;
	}

	function column_name( $item ) {
	
		$name = trim( $item->first_name . ' ' . $item->last_name );
		if ( empty( $name ) ) {
			$name = $item->display_name;
		}

		$title = '<strong>' . $name . '</strong>';
	  
		$actions = [
			'searches' => sprintf( '<a href="%s">Collaborate</a>', add_query_arg( [ 'page' => 'leads', 'user' => $item->ID ], admin_url( 'admin.php' ) ) ),
			'favorites' => sprintf( '<a target="_blank" href="%s">See Public Page</a>', Favorites::get_favorites_url( $item->ID )),
			'remove' => sprintf( '<a class="remove-lead" style="color: red;" data-id="%s">Delete lead</a>', $item->ID, )
			//'forms'    => sprintf( '<a href="%s">Form Entries</a>', add_query_arg( 'forms', $item->ID ) );
		];
	  
		return $title . $this->row_actions( $actions );
	}

	public function column_default( $item, $column_name ) {

		switch( $column_name ) {
			case 'fname':
				return $this->column_name( $item );
				break;
			case 'lname':
				return $item->last_name;
				break;
			case 'phone':
				$phone = get_user_meta( $item->ID, 'phone', true );
				return empty( $phone ) ? '' :
					sprintf( '<a href="tel:%d">%s</a>', $phone, esc_html( $phone ) );
				break;
			case 'email':
				return sprintf( '<a target="_blank" href="mailto:%s">%s</a>', $item->user_email, esc_html( $item->user_email ) );
				break;
			case 'goal':
				return get_user_meta( $item->ID, 'goal', true );
				break;
			case 'agent':
				$agent = get_user_meta( $item->ID, '_assigned_agent', true );
				if ( ! empty( $agent ) ) {
					$agent = get_user_by( 'id', $agent );
					if ( $agent ) {
						return $agent->display_name;
					}
				}
				$actions = [
					'assign' => sprintf( 
						'<a href="%s">Assign to me</a>', 
						add_query_arg( [ 
							'_wpnonce' => wp_create_nonce( 'lead_management_' . $item->ID ), 
							'assign' => $item->ID, 
							'to' => 'self' 
						] ) 
					),
					//'forms'    => sprintf( '<a href="%s">Form Entries</a>', add_query_arg( 'forms', $item->ID ) );
				];
				return 'Unassigned' . $this->row_actions( $actions );;
				break;
			case 'created':
				return get_date_from_gmt( $item->user_registered, 'm/d/Y h:i a' );
				break;
			case 'tasks':
				$tasks = $item->tasks;
				$return = '<a href="' . admin_url( 'admin.php?page=leads&user=' . $item->ID . '&tab=tasks' ) . '">'
				. ( count( $tasks ) > 0 ? 'Next: ' . Calendar::get_label( $tasks[0]->task ) . '<br>' : '' )
				. count( $tasks ) . __( ' open tasks', 'vestorfilter' )
				. '</a>';
				return $return;
				break;
			case 'login':
				
				return $item->login_time;
				break;
			case 'contact':
				$date = get_user_meta( $item->ID, '_last_contact', true );
				if ( ! empty( $date ) ) {
					$date = get_date_from_gmt( date( 'm/d/Y h:i a', $date ), 'm/d/Y h:i a' );
				}
				$actions = [
					'set' => sprintf( 
						'<a href="%s">Set to now</a>', 
						add_query_arg( [ 
							'_wpnonce' => wp_create_nonce( 'lead_management_' . $item->ID ), 
							'assign'   => $item->ID,
							'contact'  => time(), 
						] ),
					),
					//'forms'    => sprintf( '<a href="%s">Form Entries</a>', add_query_arg( 'forms', $item->ID ) );
				];
				return $date . $this->row_actions( $actions );;
				break;
			case 'tag':
				$html = '';
				$tag = get_user_meta( $item->ID, '_lead_tag', true ) ?: '';
				
				$html = '<span class="agent-tag ' . sanitize_title( $tag ) . '">' . ucwords( $tag ) . '</span>';
				
				if ( ! $this->is_agent_table ) {
					$html .= sprintf( 
						'<select data-nonce="%s" data-user="%d" data-agent-tag>',
						wp_create_nonce( 'lead_management_' . $item->ID ), 
						$item->ID
					);
					$html .= '<option value=""></option>';
					foreach ( self::tags_allowed() as $tagopt ) {
						$html .= sprintf( '<option value="%s" %s>%s</option>', $tagopt, selected( $tag, $tagopt, false ), ucwords( $tagopt ) );
					}
					$html .= '</select>';
					
				}
				return $html;

				break;
		}

	}

	public static function tags_allowed() {
		return ['new','hot','warm','cold','ignore','sold'];
	}

	public function prepare_items( $user = null ) {

		$this->_column_headers = array( $this->get_columns(), [], $this->get_sortable_columns() );
	  
		/** Process bulk action */
		//$this->process_bulk_action();

		if ( current_user_can( 'manage_agents' ) ) {
			$user = null;
		}

		$per_page     = 20;
		$current_page = $this->get_pagenum() ?: 1;

		$leads = $this->get_leads( $_GET['orderby'] ?? '', $_GET['order'] ?? '', $_REQUEST['s'] ?? '', $user );

		$total_items  = count( $leads );


		if ( $this->is_agent_table ) {
			$per_page = $total_items;
		}

		$this->set_pagination_args( [
			'total_items' => $total_items,
			'per_page'    => $per_page,
		] );
	  
		if ( ! $this->is_agent_table ) {
			$this->items = array_slice( $leads, ( $current_page - 1 ) * $per_page, $per_page );
		} else {
			$this->items = $leads;
		}

	}

	public function get_user_ids() {
		return $this->users;
	}

	public function get_leads( $sortby = '', $sort = '', $search = '', $assigned_to = null ) {

		$search_query = filter_var( $search, FILTER_SANITIZE_STRING );
		if ( ! empty( $search_query ) ) {
			$search_query = explode( ' ', strtolower( $search_query ) );
			foreach( $search_query as &$keyword ) {
				$boom = explode( ':', $keyword, 2 );
				if ( count( $boom ) === 1 ) {
					$keyword = [ 'field' => 'all', 'query' => $boom[0] ];
				} else {
					$keyword = [ 'field' => $boom[0], 'query' => $boom[1] ];
				}
			}
		}

		$args = [
			'role__in' => [ 'subscriber' ],
			'orderby'  => 'registered',
			'order'    => 'DESC',
			'number'   => -1,
		];
		switch( $sortby ) {
			case 'goal':
				$manual_sort = 'goal';
			break;
			case 'contact':
				$manual_sort = '_last_contact';
			break;
			case 'tag':
				$manual_sort = 'tag';
			break;
			case 'agent':
				$manual_sort = 'agent';
			break;
			case 'login':
				$manual_sort = 'login';
			break;
			case 'created':
			default:
				$args['orderby'] = 'registered';
				$args['order']   = ( $sort === 'asc' ) ? 'ASC' : 'DESC';
				break;
		}
		if ( ! empty( $assigned_to ) ) {
			$args['meta_key']   = '_assigned_agent';
			$args['meta_value'] = $assigned_to;
		}

		$tz = new \DateTimeZone( 'UTC' );

		$users = new \WP_User_Query( $args );
		$all = $users->get_results();
		foreach( $all as $key => &$user ) {
			$this->users[] = $user->ID;

			if ( $this->is_agent_table ) {
				$user->tasks = Calendar::get_user_tasks( $user->ID, 'open' );
				if ( count( $user->tasks ) === 0 ) {
					unset( $all[$key] );
				}
			}

			$last_login = Log::search( [ 'action' => 'logged-in', 'user' => $user->ID ] );
			if ( ! empty( $last_login ) ) {
				$date = new \DateTime( '@' . $last_login[0]->log_time );
				$date->setTimezone( $tz );
				$user->login_time = $date->format( 'Y-m-d h:i:s a' );
				if ( ! empty( $manual_sort ) && $manual_sort === 'login' ) {
					$user->sort_field = $last_login[0]->log_time;
				}
			}
		}

		if ( $sortby ) {
			$sort = ( $sort === 'desc' ) ? 'DESC' : 'ASC';
		}
		
		if ( ! empty( $manual_sort ) || ! empty( $search_query ) ) {

			foreach( $all as $key => &$user ) {

				if ( ! empty( $search_query ) ) {
					if ( ! self::does_user_match( $user, $search_query ) ) {
						unset( $all[$key] );
						continue;
					}
				}

				if ( ! empty( $manual_sort ) ) {

					switch ( $manual_sort ) {

						case 'agent':
							$agent_id = get_user_meta( $user->ID, '_assigned_agent', true );
							if ( empty( $agent_id ) ) {
								$user->sort_field = '';
							}
							$agent = get_user_by( 'id', $agent_id );
							if ( empty( $agent ) || is_wp_error( $agent ) ) {
								$user->sort_field = '';
							} else {
								$user->sort_field = $agent->last_name ?: $agent->display_name;
							}
						break;

						case 'tag': 
							$tag = get_user_meta( $user->ID, '_lead_tag', true );
							if ( empty( $tag ) ) {
								$user->sort_field = '';
							} else {
								$position = array_search( $tag, self::tags_allowed() );
								if ( $position === false ) {
									$user->sort_field = '';
								} else {
									$user->sort_field = $position + 1;
								}
							}
						break;

						case 'login': break;

						case 'goal':
						case '_last_contact':
						default:
							$user->sort_field = get_user_meta( $user->ID, $manual_sort, true );
						break;

					}

				}
			}

			if ( ! empty( $manual_sort ) ) {
			
				usort( $all, function ( $a, $b ) use ( $sortby, $sort ) {

					if ( empty( $a->sort_field ) && $a->sort_field !== $b->sort_field ) {
						return 1;
					} else if ( empty( $b->sort_field ) ) {
						return -1;
					}

					return $sort === 'ASC' 
						? strcmp( $a->sort_field, $b->sort_field ) : strcmp( $b->sort_field, $a->sort_field );
		
				} );

			}
		}

		

		return $all;

	}

	public static function does_user_match( $user, $query ) {

		foreach( $query as $keyset ) {
			$value = '';
			if ( $keyset['field'] === 'all' ) {
				$fields = [ 
					$user->first_name . ' ' . $user->last_name,
					get_user_meta( $user->ID, 'phone', true ),
					$user->user_email,
					get_user_meta( $user->ID, '_lead_tag', true ),
				];
				$agent_id = get_user_meta( $user->ID, '_assigned_agent', true );
				if ( $agent_id ) {
					$agent = get_user_by( 'id', $agent_id );
					if ( ! empty( $agent ) && ! is_wp_error( $agent ) ) {
						$fields[] = $agent->first_name . ' ' . $agent->last_name;
					}
				}
				$matched = false;
				foreach( $fields as $value ) {
					if ( stripos( $value, $keyset['query'] ) !== false ) {
						$matched = true;
						break;
					}
				}
				if ( ! $matched ) {
					return false;
				} else {
					continue;
				}
			}
			if ( $keyset['field'] === 'phone' ) {
				$value = get_user_meta( $user->ID, 'phone', true );
			}
			if ( $keyset['field'] === 'tag' ) {
				$value = get_user_meta( $user->ID, '_lead_tag', true );
			}
			if ( $keyset['field'] === 'name' ) {
				$value = $user->first_name . ' ' . $user->last_name;
			}
			if ( $keyset['field'] === 'email' ) {
				$value = $user->user_email;
			}
			if ( $keyset['field'] === 'agent' ) {
				$agent_id = get_user_meta( $user->ID, '_assigned_agent', true );
				if ( ! $agent_id ) {
					return false;
				}
				$agent = get_user_by( 'id', $agent_id );
				if ( empty( $agent ) || is_wp_error( $agent ) ) {
					return false;
				}
				$value = $agent->first_name . ' ' . $agent->last_name;
			}
			if ( stripos( $value, $keyset['query'] ) === false ) {
				return false;
			}
		}
		return true;
	}
	

}

