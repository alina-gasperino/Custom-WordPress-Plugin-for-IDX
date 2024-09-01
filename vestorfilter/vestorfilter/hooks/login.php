<?php

namespace VestorFilter\Hooks;

class Login extends \VestorFilter\Util\Singleton {

	public static $instance;

	public $user, $auth_cookie, $login_cookie, $nonce;

	private $password;

	public function install() {

		add_action( 'frm_validate_entry', [ $this, 'validate_login' ], 10, 2 );

		add_action( 'frm_process_entry', [ $this, 'process_login' ], 10, 3 );

	}

	public function validate_login( $errors, $values ) {

		if ( $values['form_key'] !== 'user-login' ) {
			return $errors;
		}

		$email_id = \FrmField::get_id_by_key( 'login_email' );
		$password_id = \FrmField::get_id_by_key( 'login_pass' );
		$user_id = \FrmField::get_id_by_key( 'login_id' );

		$username = $values['item_meta'][ $email_id ] ?? null;
		$password = $values['item_meta'][ $password_id ] ?? null;

		if ( empty( $username ) ) {
			$errors[ 'field' . $email_id ] = 'Email cannot be left blank';
		}
		if ( empty( $password ) ) {
			$errors[ 'field' . $password_id ] = 'Password cannot be left blank';
		}

		$username = sanitize_user( $username );
    	$password = trim( $password );

		$user = apply_filters( 'authenticate', null, $username, $password );
		if ( empty( $user ) || is_wp_error( $user ) ) {
			$errors[ 'field' . $email_id ] = '';
			$errors[ 'field' . $password_id ] = '';
		} else {
			$this->user = $user;
			$this->password = $password;
		}

		//var_dump( $errors );
		//die();

		add_filter( 'frm_invalid_error_message', [ $this, 'login_error_message' ], 10, 1 );

		return $errors;

	}

	function process_login( $params, $errors, $form ) {

		if ( ! $form->form_key === 'user-login' || empty( $this->user ) ) {
			return;
		}

		wp_set_current_user( $this->user->ID );

		add_action( 'set_logged_in_cookie', [ $this, 'get_li_cookie_data' ], 20, 6 );

		wp_signon(
			[
				'user_login'    => $this->user->user_email,
				'user_password' => $this->password,
				'remember'      => true,
			]
		);

		wp_get_current_user();

		add_action( 'frm_filter_final_form', [ $this, 'add_form_data' ], 11 );

	}

	public function get_li_cookie_data( $value, $expire, $expiration, $user_id, $scheme, $token ) {

		$this->login_cookie = [
			'name'        => LOGGED_IN_COOKIE,
			'value'       => $value,
			'expire'      => $expire,
			'expiration'  => $expiration,
			'user_id'     => $user_id,
			'scheme'      => $scheme,
			'token'       => $token,
			'path'        => COOKIEPATH ?? '/',
			'domain'      => COOKIEDOMAIN ?? '',
		];

	}

	public function add_form_data( $form ) {

		$nonce = Registration::make_nonce( $this->user->ID, $this->login_cookie['token'] );

		$form          = str_replace(
			'</form>',
			"\r\n"
			. '<textarea style="display:none!important" data-vestor-cookie="login">' . esc_html( json_encode( $this->login_cookie ) ) . '</textarea>'
			. '<input data-vestor-nonce type="hidden" name="rest_nonce" value="' . $nonce . '" />'
			. "\r\n"
			. '</form>',
			$form
		);

		return $form;
	}

	function login_error_message( $invalid_msg ) {
		
		$invalid_msg = 'Your username and password was incorrect or could not be found.';
		
		return $invalid_msg;
	}

}

