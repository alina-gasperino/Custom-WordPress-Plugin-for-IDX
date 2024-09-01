<?php

namespace VestorFilter\Hooks;

use VestorFilter\Favorites as Favorites;
use VestorFilter\Log as Log;
use VestorFilter\Search;


class Subscribe extends \VestorFilter\Util\Singleton {

	public static $instance;

	public $values;

	private $password;

	public function install() {

		add_action( 'frm_validate_entry', [ $this, 'get_values' ], 10, 3 );
		add_action( 'frm_process_entry', [ $this, 'process_subscription' ], 10, 3 );
		add_action( 'frm_process_entry', [ $this, 'process_agent_save' ], 10, 3 );

		add_filter( 'frm_setup_new_fields_vars', [ $this, 'setup_available_leads' ], 20, 2 );

	}

	function get_values( $errors, $values ) {

		if ( $values['form_key'] === 'search_update_preferences' || $values['form_key'] === 'save_search_for_user' ) {
			$this->values = $values;
			if ( ! current_user_can( 'use_dashboard' ) ) {
				add_filter( 'frm_success_filter', function ( $type, $form ) {
					$type = 'message';
					$form->options['success_msg'] = "Your search subscription has been saved!";
					return $type;
				}, 10, 2);
			}
		}

		

		return $errors;

	}

	function process_subscription( $params, $errors, $form ) {

		if ( ! $form->form_key === 'search_update_preferences' ) {
			return;
		}

		if ( empty( $this->values ) ) {
			return;
		}

		$user_id = get_current_user_id();
		if ( empty( $user_id )) {
			return;
		}

		$query_id = \FrmField::get_id_by_key( 'search_query_hash' );
		$name_id = \FrmField::get_id_by_key( 'search_name' );
		$freq_id  = \FrmField::get_id_by_key( 'email_update_frequency' );

		$query_hash = $this->values['item_meta'][ $query_id ] ?? '';
		$freq  = $this->values['item_meta'][ $freq_id ] ?? '';
		$name  = $this->values['item_meta'][ $name_id ] ?? '';


		if ( empty( $query_hash ) ) {
			return;
		}
		$query_hash = sanitize_title( $query_hash );
		if ( ! Favorites::is_search_saved( $query_hash, $user_id ) ) {
			return;
		}

		if ( $freq !== 'never' ) {
			$freq = absint( $freq );
		}
		if ( $freq < 0 || $freq > 30 ) {
			$freq = 7;
		}

		if ( ! empty( $name ) ) {
			$name = filter_var( $name, FILTER_SANITIZE_STRING );
			Favorites::set_search_name( $query_hash, $name, $user_id );
		}

		Log::add( [ 'action' => 'search-subscribed', 'value' => $query_hash, 'user' => $user_id, 'performed_by' => $user_id ] );
		Favorites::add_email_subscription( $query_hash, $freq, $user_id );

	}

	function process_agent_save( $params, $errors, $form ) {

		if ( ! $form->form_key === 'save_search_for_user' ) {
			return;
		}

		if ( empty( $this->values ) ) {
			return;
		}

		$user_id = get_current_user_id();
		if ( empty( $user_id )) {
			return;
		}


		$lead_id  = \FrmField::get_id_by_key( 'saved_lead_id' );
		$query_id = \FrmField::get_id_by_key( 'saved_search_query_hash' );
		$name_id  = \FrmField::get_id_by_key( 'saved_search_name' );
		$freq_id  = \FrmField::get_id_by_key( 'saved_search_freq' );
		$filt_id  = \FrmField::get_id_by_key( 'saved_search_filters' );

		$lead       = $this->values['item_meta'][ $lead_id ] ?? '';
		$query_hash = $this->values['item_meta'][ $query_id ] ?? '';
		$freq       = $this->values['item_meta'][ $freq_id ];
		$name       = $this->values['item_meta'][ $name_id ];
		$filter_str = $this->values['item_meta'][ $filt_id ];

		if ( empty( $lead ) || $lead === 'me' || ! current_user_can( 'use_dashboard' ) ) {
			$lead = get_current_user_id();
			
		}

		if ( empty( $query_hash ) || empty( $lead ) || empty( $filter_str ) ) {
			return;
		}
		$lead = absint( $lead );
		if ( empty( $lead ) ) {
			return;
		}

		$query_hash = sanitize_title( $query_hash );

		//if ( Favorites::is_search_saved( $query_hash, $lead ) ) {
		//	return;
		//}
		
		if ( ! is_string( $filter_str ) ) {
			return;
		}
		
		parse_str( $filter_str, $filters );

		$hash_filters = Search::get_query_filters( $filters );
		if ( isset( $hash_filters['geo'] ) ) {
			unset( $hash_filters['geo'] );
		}
		$test_hash = Search::get_hash( $hash_filters );
		if ( $test_hash !== $query_hash ) {
			return;
		}
		if ( ! empty( $name ) ) {
			$filters['name'] = $name;
		}
		if ( $lead !== get_current_user_id() ) {
			$filters['added_by'] = get_current_user_id();
		}


		wp_cache_delete( 'favorite_searches__' . $lead, 'vestorfilter' );
		$all_searches = Favorites::get_searches( $lead );
		if ( empty( $all_searches[ $query_hash ] ) ) {
			Log::add( [ 'action' => 'search-saved', 'value' => $query_hash, 'user' => $lead, 'performed_by' => $user_id ] );
		}
		$all_searches[ $query_hash ] = json_encode( $filters );

		update_user_meta( $lead, '_favorite_searches', $all_searches );

		if ( $freq !== 'never' ) {
			$freq = absint( $freq );
			if ( $freq <= 0 || $freq > 30 ) {
				$freq = 7;
			}
		} else {
			$freq = 'never';
		}

		Log::add( [ 'action' => 'search-subscribed', 'value' => $query_hash, 'user' => $lead, 'performed_by' => $user_id ] );
		Favorites::add_email_subscription( $query_hash, $freq, $lead );

	}
	
	function setup_available_leads( $values, $field ) {
		
		if ( $field->field_key === 'saved_lead_id' ) {

			$options = array( [
				'label' => 'Me',
				'value' => 'me',
			] );

			if ( current_user_can( 'see_leads' ) ) {

				$all_users = new \WP_User_Query( [
					'role__in' => [ 'subscriber' ],
					'orderby'  => 'user_registered',
					'order'    => 'DESC',
					'number'   => -1,
				] );

				foreach( $all_users->get_results() as $user ) {
					if ( current_user_can( 'manage_agents' ) || current_user_can( 'see_leads' ) || get_user_meta( $user->ID, '_assigned_agent', true ) == get_current_user_id() ) {
						$options[] = [
							'label' => $user->display_name . ' - ' . $user->user_email,
							'value' => $user->ID
						];
					} 
				}
				
				$values['options'] = $options;

			}

		}
		return $values;
	}

}

add_action( 'vestorfilter_installed', [ 'VestorFilter\Hooks\Subscribe', 'init' ] );
