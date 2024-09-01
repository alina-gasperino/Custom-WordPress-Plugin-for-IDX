<?php

namespace VestorFilter;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

class Admin extends \VestorFilter\Util\Singleton {

	/**
	 * A pointless bit of self-reference
	 *
	 * @var object
	 */
	public static $instance = null;

	private static $agents = [];

	private static $filters = [];

	public function install() {

		$role_version = get_option( 'vf_agent_roles' );

		if ( $role_version < 26 ) {

			update_option( 'vf_agent_roles', 26 );

			$caps = get_role( 'contributor' )->capabilities;
			add_role( 'agent', 'Agent', $caps );
			add_role( 'manager', 'Manager', $caps );
			add_role( 'site-manager', 'Site Manager', $caps );

			$role = get_role( 'administrator' );
			$role->add_cap( 'use_dashboard', true );
			$role->add_cap( 'see_leads', true );
			$role->add_cap( 'tag_leads', true );
			$role->add_cap( 'manage_vf_options', true );
			$role->add_cap( 'manage_agents', true );

		}

		if ( $role_version < 27) {

			update_option( 'vf_agent_roles', 27 );

			$role = get_role( 'agent' );
			$role->add_cap( 'use_dashboard', true );
			$role->add_cap( 'see_leads', true );
			$role->add_cap( 'frm_view_forms', true );
			$role->add_cap( 'frm_view_entries', true );

			$role = get_role( 'manager' );
			$role->add_cap( 'use_dashboard', true );
			$role->add_cap( 'see_leads', true );
			$role->add_cap( 'frm_view_forms', true );
			$role->add_cap( 'frm_view_entries', true );
			$role->add_cap( 'tag_leads', true );
			$role->add_cap( 'manage_agents', true );
            $role->add_cap( 'remove_users', true );
            $role->add_cap( 'edit_users', true );
            $role->add_cap( 'create_users', true );



            $role = get_role( 'site-manager' );
			$role->add_cap( 'use_dashboard', true );
			$role->add_cap( 'see_leads', true );
			$role->add_cap( 'frm_view_forms', true );
			$role->add_cap( 'frm_view_entries', true );
			$role->add_cap( 'tag_leads', true );
			$role->add_cap( 'manage_agents', true );
			$role->add_cap( 'manage_vf_options', true );
			$role->add_cap( 'list_users', true );
			$role->add_cap( 'edit_users', true );
			$role->add_cap( 'promote_users', true );
			$role->add_cap( 'remove_users', true );
			$role->add_cap( 'create_users', true );

		}

		
		if ( $role_version < 20220103 ) {

			update_option( 'vf_agent_roles', 20220103 );
			$role = get_role( 'site-manager' );

			$role->add_cap( 'delete_others_posts', true );
			$role->add_cap( 'delete_posts', true );
			$role->add_cap( 'delete_private_posts', true );
			$role->add_cap( 'delete_published_posts', true );

			$role->add_cap( 'edit_others_posts', true );
			$role->add_cap( 'edit_posts', true );
			$role->add_cap( 'edit_private_posts', true );
			$role->add_cap( 'edit_published_posts', true );

			$role->add_cap( 'publish_posts', true );

		}

		add_action( 'admin_menu', [ $this, 'remove_comments' ] );
		
		add_action( 'rwmb_meta_boxes', [ $this, 'page_permissions_metabox' ] );
		add_action( 'pre_get_posts', [ $this, 'lock_down_pages' ] );
		add_action( 'save_post_page', [ $this, 'save_new_page' ] );
		add_action( 'admin_bar_menu', [ $this, 'hide_edit_page_link' ], 999 );

		add_filter( 'editable_roles', [ $this, 'remove_hidden_roles' ], 99 );
		add_filter( 'user_has_cap', [ $this, 'can_user_edit_user' ], 20, 4 );
		add_filter( 'user_has_cap', [ $this, 'can_user_add_user' ], 20, 4 );


		add_filter( 'users_list_table_query_args', [ $this, 'hide_disallowed_users' ], 99 );
		add_filter( "views_users", [ $this, 'replace_role_links' ], 99 );
	}

	function remove_comments() {
		if ( ! current_user_can( 'manage_options' ) ) {
			remove_menu_page( 'edit-comments.php' );
			remove_menu_page( 'tools.php' );
		}
	}

	

	function hide_edit_page_link( $wp_admin_bar ) {
		if ( current_user_can( 'manage_options' ) ) {
			return;
		}
		$queried_object = get_queried_object();
		if ( ! empty( $queried_object->post_type ) && $queried_object->post_type === 'page' ) {
			$has_permission = absint( get_post_meta( $queried_object->ID, '_vf_management_allowed', true ) );
			if ( ! $has_permission ) {
				$wp_admin_bar->remove_node( 'edit' );
			}
		}
	}

	function save_new_page( $post_id ) {

		if ( current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( current_user_can( 'edit_pages' ) ) {
			update_post_meta( $post_id, '_vf_management_allowed', '1' );
		}

	}

	function lock_down_pages( $query ) {

		if ( current_user_can( 'manage_options' ) || ! is_admin() || ! $query->is_main_query() || $query->get('post_type') != 'page' ) {
			return $query;
		}

		$query->set( 'meta_key', '_vf_management_allowed' );
		$query->set( 'meta_value', '1' );

		return $query;
	}

	function page_permissions_metabox( $meta_boxes ) {

		if ( ! current_user_can( 'manage_options' ) ) {
			return $meta_boxes;
		}

		$meta_boxes[] = [
			'title'      => esc_html__( 'Page Permissions', 'vestorfilter' ),
			'id'         => 'page_permissions',
			'post_types' => ['page'],
			'context'    => 'side',
			
			'fields'     => [
				[
					'type' => 'checkbox',
					'name' => esc_html__( 'Permissions', 'online-generator' ),
					'id'   => '_vf_management_allowed',
					'desc' => esc_html__( 'Allow Site Managers to edit this page', 'vestorfilter' ),
				],
			],
		];

		return $meta_boxes;

	}

	public function hide_disallowed_users( $args ) {

		if ( current_user_can( 'manage_options' ) ) {
			return $args;
		}

		$args['role__in'] = [ 'agent', 'manager', 'subscriber' ];

		return $args;

	}

	public function replace_role_links( $links ) {

		if ( current_user_can( 'manage_options' ) ) {
			return $links;
		}

		return [
			'leads' => sprintf( '<a href="%s">Leads</a>', add_query_arg( 'role', 'subscriber', admin_url( 'users.php' ) ) ),
			'agents' => sprintf( '<a href="%s">Agents</a>', add_query_arg( 'role', 'agent', admin_url( 'users.php' ) ) ),
			'managers' => sprintf( '<a href="%s">Managers</a>', add_query_arg( 'role', 'manager', admin_url( 'users.php' ) ) ),
		];

	}

	public function remove_hidden_roles( $roles ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			foreach( $roles as $role => $caps ) {
				if ( ! in_array( $role, [ 'agent', 'manager', 'subscriber' ] ) ) {
					unset($roles[$role]);
				}
			}
		}
		return $roles;
	}

	public function can_user_add_user( $mycaps, $checkcaps, $args, $user ) {

		if ( empty( $mycaps['create_users'] ) || empty( $mycaps['site-manager'] ) ) {
			return $mycaps;
		}

		$max_users = absint( get_option( 'max_allowed_users' ) ?: 9 );
		$total_users = ( new \WP_User_Query( [ 
			'role__in' => [ 'agent', 'manager' ], 
			'fields' => 'ID', 
			'count_total' => true ] 
		) )->get_total();

		if ( $total_users >= $max_users ) {
			unset( $mycaps['create_users'] );
			unset( $caps['promote_users'] );
		}

		return $mycaps;

	}

	public function can_user_edit_user( $mycaps, $checkcaps, $args, $user ) {

		if ( empty( $mycaps['create_users'] ) || empty( $mycaps['site-manager'] ) ) {
			return $mycaps;
		}

		if ( in_array( 'edit_users', $checkcaps ) && $args[0] === 'edit_user' ) {
			if ( empty( $args[2] ) ) {
				unset( $mycaps['edit_users'] );
			} else {
				$user_to_edit = get_user_by( 'id', $args[2] );
				if ( ! $user_to_edit ) {
					unset( $mycaps['edit_users'] );
				} else if ( ! empty( $user_to_edit->roles ) && ! in_array( $user_to_edit->roles[0], [ 'subscriber', 'agent', 'manager' ] ) ) {
					unset( $mycaps['edit_users'] );
				}
			}

		}

		return $mycaps;

	}

}

add_action( 'vestorfilter_installed', array( 'VestorFilter\Admin', 'init' ) );
