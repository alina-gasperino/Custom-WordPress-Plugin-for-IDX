<?php

namespace VestorFilter;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

class Account extends \VestorFilter\Util\Singleton {

	/**
	 * A pointless bit of self-reference
	 *
	 * @var object
	 */
	public static $instance = null;

	private static $properties = [];

	public function install() {

		add_action( 'rest_api_init', array( $this, 'init_rest' ) );

		add_action( 'wp_login', [ $this, 'invite_friend' ], 10, 2 );


	}

	public function init_rest() {

		/*register_rest_route( 'vestorfilter/v1', '/account/auth', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'rest_auth' ),
			'permission_callback' => '__return_true',
		) );*/

		register_rest_route( 'vestorfilter/v1', '/account/invite', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'invite_user' ),
			'permission_callback' => 'is_user_logged_in',
		) );

	}

	public function rest_auth( $request ) {

		if ( ! is_user_logged_in() ) {
			return false;
		}

		return [ 'user_id' => get_current_user_id(), 'nonce' => wp_create_nonce( 'wp_rest' ) ];

	}

	public function invite_user( $request ) {

		$email = $request['friend_email'] ?? null;
		$id = $request['friend_id'] ?? null;
		if ( empty( $id ) && ( empty( $email ) || ! is_email( $email ) ) ) {
			return new \WP_Error( 'bad_email', 'Please enter a valid email address.', [ 'status' => 401 ] );
		}
		if ( empty( $email ) && ! is_numeric( $id ) ) {
			return new \WP_Error( 'bad_id', 'An error occurred. Please contact support.', [ 'status' => 401 ] );
		}
		if ( ! empty( $id ) ) {
			$existing = get_user_by( 'id', $id );
			if ( ! $existing ) {
				return new \WP_Error( 'bad_id', 'An error occurred. Please contact support.', [ 'status' => 401 ] );
			}
		} else {
			$existing = get_user_by( 'email', $email );
		}
		if ( $existing ) {

			$error = $this->add_friend( $existing->ID );
			if ( is_wp_error( $error ) ) {
				return $error;
			}
			return [ 'message' => 'Email sent.' ];

		} else {

			$error = $this->send_friend_invitation( $email );
			if ( is_wp_error( $error ) ) {
				return $error;
			}

			return [ 'message' => 'Email sent.' ];
		}

	}

	public function send_friend_invitation( $email_address, $user_id = null ) {

		if ( empty( $user_id ) ) {
			$user_id = get_current_user_id();
		}

		$user = get_user_by( 'id', $user_id );
		$url = Favorites::get_favorites_url( $user_id );

//		$invitations = get_user_meta( $user_id, '_invitations' ) ?: [];
		$code = md5( $email_address );
//		if ( in_array( $code, $invitations ) ) {
//			return new \WP_Error( 'already_exists', 'You already shared your favorite homes with this user!', [ 'status' => 401 ] );
//		}

		add_user_meta( $user_id, '_invitations', $code );

		$email = new Email( 'messages' );
		$email->set_var( 'header_title', $user->display_name . ' is sharing their favorite homes with you.' );
		$email->set_var( 'address', Settings::get( 'email_footer_address' ) );
        $email->set_var( 'footer_text', str_replace('{{TEAM_MEMBER}}', '', str_replace('{{DATE}}', date('F d, Y'), Settings::get('email_footer_text'))));
        $email->set_var( 'subject', $user->display_name . ' is sharing their favorite homes with you.' );

        $custom_logo_id = get_theme_mod('custom_logo');
        if ($custom_logo_id) {
            $image = wp_get_attachment_image_src($custom_logo_id, 'brand-logo');
            $email->set_var('logo', $image[0]);
        }

		$email->add_section( [
			'title'      => 'Create an account to continue',
			'content'    => 'View their current favorite properties, or login and start searching to save your own.',
			'link_label' => 'Create an account',
			'link_href'  => add_query_arg( 'invitation_code', $code, trim( get_bloginfo( 'url' ), '/' ) . '/account/' ),
		] );

		$email->set_var( 'to', $email_address );
		$email->send();

		return true;

	}

	public function add_friend( $friend_id, $user_id = null ) {

		if ( empty( $user_id ) ) {
			$user_id = get_current_user_id();
		}

		$user = get_user_by( 'id', $user_id );
		$url = Favorites::get_favorites_url( $user_id );

		$email = new Email( 'messages' );
		$email->set_var( 'header_title', $user->display_name . ' is sharing their favorite homes with you.' );
		//$email->set_var( 'header_text', $updated . ' new matching properties since ' . date( 'M d, Y \\a\\t h:i a ', $last_sent ) );
		$email->set_var( 'address', Settings::get( 'email_footer_address' ) );
        //$email->set_var( 'footer_text', str_replace( '{{DATE}}', date( 'F d, Y' ), Settings::get( 'email_footer_text' ) ) );
        $email->set_var( 'footer_text', str_replace('{{TEAM_MEMBER}}', '', str_replace('{{DATE}}', date('F d, Y'), Settings::get('email_footer_text'))));

        //$email->set_var( 'unsubscribe_url', get_bloginfo( 'url' ) . '/favorites/' );
		$email->set_var( 'subject', $user->display_name . ' is sharing their favorite homes with you.' );
        $custom_logo_id = get_theme_mod('custom_logo');
        if ($custom_logo_id) {
            $image = wp_get_attachment_image_src($custom_logo_id, 'brand-logo');
            $email->set_var('logo', $image[0]);
        }
		$email->add_section( [
			'title'      => '',
			'content'    => '<a href="' . $url . '">View their current favorite properties</a>, or log in and start searching to save your own!',
			'link_label' => 'Start Searching',
			'link_href'  => \VestorFilter\Settings::get_page_url( 'search' ),
		] );

		$friends = get_user_meta( $user_id, '_friends' ) ?: [];
//		if ( ! in_array( $friend_id, $friends ) ) {

			add_user_meta( $user_id, '_friends', $friend_id );

			$friend = get_user_by( 'id', $friend_id );

			$email->set_var( 'to', $friend->user_email );
			$email->send();

			return true;

//		}
//        return true;
//		return new \WP_Error( 'already_exists', 'This user is already your friend!', [ 'status' => 401 ] );

	}

	function invite_friend( $who, $user ) {

		if ( empty( $_COOKIE['user_invite_code'] ) ) {
			return;
		}
		$code = sanitize_title( $_COOKIE['user_invite_code'] );

		$friends = get_users( [
			'meta_key'   => '_invitations',
			'meta_value' => $code,
		] );

		foreach( $friends as $friend ) {
			delete_user_meta( $friend->ID, '_invitations', $code );
			
			// add inviter to invitee's friends
			delete_user_meta( $user->ID, '_friends', $friend->ID );
			add_user_meta( $user->ID, '_friends', $friend->ID );

			// add new user to inviter's friends
			delete_user_meta( $friend->ID, '_friends', $user->ID );
			add_user_meta( $friend->ID, '_friends', $user->ID );
		}

		setcookie( 'user_invite_code', '', [ 'path' => '/' ] );

		Log::add( [ 'action' => 'logged-in', 'user' => $user->ID ] );

	}

	public static function  get_user_friends( $user_id = null ) {
		if ( empty( $user_id ) ) {
			$user_id = get_current_user_id();
		}
		if ( empty( $user_id ) ) {
			return [];
		}
		$friends = get_users( [
			'meta_key'   => '_friends',
			'meta_value' => $user_id,
		] );

		return $friends;

	}

}

add_action( 'vestorfilter_installed', array( 'VestorFilter\Account', 'init' ) );
