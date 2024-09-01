<?php

namespace VestorFilter\Hooks;

use VestorFilter\Log as Log;


class Account extends \VestorFilter\Util\Singleton {

	public static $instance;

	public $auth_cookie = null, $login_cookie = null, $user = null, $password = null, $form_values = null;

	public function install() {

		add_action( 'frm_validate_entry', [ $this, 'validate_registration' ], 10, 2 );
		add_action( 'frmreg_after_create_user', [ $this, 'register_user' ], 9, 2 );

		add_action( 'frm_validate_entry', [ $this, 'validate_login' ], 10, 2 );
		add_action( 'frm_process_entry', [ $this, 'process_login' ], 10, 3 );

		add_action( 'frm_validate_entry', [ $this, 'validate_account' ], 10, 2 );
		add_action( 'frm_process_entry', [ $this, 'update_account' ], 10, 3 );

		add_filter( 'vestorfilter_sso_login_response', [ $this, 'setup_sso_login' ], 10, 4 );
		add_filter( 'vestorfilter_sso_login_response', [ $this, 'register_sso_account' ], 20, 4 );

		add_action( 'wp_login', [ $this, 'log_login' ], 10, 2 );


	}

	public function setup_sso_login( $response, $user, $method, $payload ) {

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( ! empty( $response['cookie'] ) ) {

			$response['vestorNonce'] = self::make_nonce( $user->ID, $response['cookie']['token'] );
			$response['payload'] = apply_filters( 'vestorfilter_auth_payload', [], $user );

		}

		if ( ! empty( $payload['phone'] ) ) {
			if ( ! get_user_meta( $user->ID, 'phone', true ) ) {
				update_user_meta( $user->ID, 'phone', $payload['phone'] );
				update_user_meta( $user->ID, '_twilio_number', $payload['twilio'] );
			}
		}

		return $response;

	}

	public function register_sso_account( $response, $user, $method, $payload ) {

		if ( is_wp_error( $response ) && ! empty( $payload ) ) {

			switch ( $method ) {

				case 'google':

					$email = $payload['email'];
					$username = $payload['email'];
					$password = wp_generate_password( 20 );

					$name = $payload['name'];
					$fname = $payload['given_name'];
					$lname = $payload['family_name'];

					$phone = $payload['phone'];
					$twilio = $payload['twilio'];

				break;

				case 'facebook':

					$email = $payload['email'];
					$username = $payload['email'];
					$password = wp_generate_password( 20 );

					$name = $payload['name'];
					list( $fname, $lname ) = explode( ' ', $name, 2 );
					

				break;

				case 'linkedin':

					$email = $payload['email'];
					$username = $payload['email'];
					$password = wp_generate_password( 20 );

					$name  = $payload['localizedFirstName'] . ' ' . $payload['localizedLastName'];
					$fname = $payload['localizedFirstName'];
					$lname = $payload['localizedLastName'];

				break;

			}

			if ( ! empty( $email ) ) {
				$user_id = wp_insert_user( [
					'user_login' => $username,
					'user_pass'  => $password,
					'user_email' => $email,
					'display_name' => $name,
					'first_name' => $fname,
					'last_name' => $lname,
				] );

				if ( $user_id ) {

					if ( ! empty( $phone ) ) {
						update_user_meta( $user->ID, 'phone', $phone );
						update_user_meta( $user->ID, '_twilio_number', $twilio );
					}

					add_action( 'set_logged_in_cookie', [ $this, 'get_li_cookie_data' ], 20, 6 );

					wp_set_current_user( $user_id );
					wp_set_auth_cookie( $user_id, true, true );
					wp_get_current_user();

					$user = get_user_by( 'id', $user_id );

					if ( empty( $fname ) ) {
						$fname = $name;
					}

					$response = [];
					$response['sso']         = 'registered';
					$response['vestorNonce'] = self::make_nonce( $user_id, $this->login_cookie['token'] );
					$response['payload']     = apply_filters( 'vestorfilter_auth_payload', [], $user );
					$response['message']     = 'Hello, ' . $fname . '! Your account was successfully created.';
					$response['user']        = [
						'email' => $email,
						'login' => $email,
						'display_name' => $name,
					];
					$response['cookie']      = $this->login_cookie;

					if ( class_exists( 'FrmEntry' ) ) {

						$fname_id = \FrmField::get_id_by_key( 'reg_fname' );
						$lname_id = \FrmField::get_id_by_key( 'reg_lname' );
						$email_id = \FrmField::get_id_by_key( 'reg_email' );
						$goal_id  = \FrmField::get_id_by_key( 'reg_goal' );
						$phone_id = \FrmField::get_id_by_key( 'phone' );
						$uid_id   = \FrmField::get_id_by_key( 'epce5' );

						$sms_id = \FrmField::get_id_by_key( 'sms_consent' );

						\FrmEntry::create(array(
							'form_id'     => 2,
							'item_key'    => 'entry',
							'frm_user_id' => $user_id,
							'item_meta'   => array(
								$fname_id => $fname,
								$lname_id => $lname ?? '',
								$email_id => $email,
								$goal_id  => 'N/A (Registered via ' . $method . ')',
								$phone_id => '',
								$uid_id   => $user_id,
								$sms_id   => '',
							),
						));

					}

					Log::add( [ 'action' => 'registered', 'user' => $user_id ] );
					update_user_meta( $user_id, '_lead_tag', 'new' );

					do_action( 'vestorfilter_user_created', $user );

				} 
			}

		}

		return $response;

	}

	public function validate_registration( $errors, $values ) {

		if ( $values['form_key'] !== 'user-registration' ) {
			return $errors;
		}

		$phone_id = \FrmField::get_id_by_key( 'phone' );
		$phone = $values['item_meta'][ $phone_id ] ?? null;

		if ( empty( $phone ) ) {
			$errors[ 'field' . $phone_id ] = 'Phone number cannot be left blank';
		} else if ( absint( $phone ) < 1000000000 ) {
			$errors[ 'field' . $phone_id ] = 'Phone number is invalid';
		}

		return $errors;

	}

	public function register_user( $user_id, $data ) {

		$user  = get_user_by( 'id', $user_id );
		$phone = get_user_meta( $user_id, 'phone', true );
		$twilio_phone = preg_replace( '/[^0-9]/', '', $phone );
		if ( strlen( $twilio_phone ) >= 10 ) {
			if ( strlen( $twilio_phone ) === 10 ) {
				$twilio_phone = '1' . $twilio_phone;
			}
			$twilio_phone = '+' . $twilio_phone;
			update_user_meta( $user_id, '_twilio_number', $twilio_phone );
		} else {
			delete_user_meta( $user_id, '_twilio_number' );
		}

		wp_set_password( $phone, $user_id );

		add_action( 'set_logged_in_cookie', [ $this, 'get_li_cookie_data' ], 20, 6 );

		$this->user = $user;

		wp_set_current_user( $user->ID );

		wp_signon(
			[
				'user_login'    => $user->user_email,
				'user_password' => $phone,
				'remember'      => true,
			]
		);

		\VestorFilter\Favorites::create_user_slug( $user );

		wp_get_current_user();

		Log::add( [ 'action' => 'registered', 'user' => $user_id ] );
		
		do_action( 'vestorfilter_user_created', $user );
		do_action( 'wp_login', $user->user_login, $user );

		add_action( 'frm_filter_final_form', [ $this, 'add_form_data' ], 11 );

	}

	public static function make_nonce( $uid, $token ) {

		$i     = wp_nonce_tick();

		return substr( wp_hash( $i . '|wp_rest|' . $uid . '|' . $token, 'nonce' ), -12, 10 );

	}

	public function get_auth_cookie_data( $value, $expire, $expiration, $user_id, $scheme, $token ) {

		$secure = apply_filters( 'secure_auth_cookie', $secure, $user_id );
		if ( $secure ) {
			$auth_cookie_name = SECURE_AUTH_COOKIE;
		} else {
			$auth_cookie_name = AUTH_COOKIE;
		}

		$this->auth_cookie = [
			'name'        => $auth_cookie_name,
			'value'       => $value,
			'expire'      => $expire,
			'expiration'  => $expiration,
			'user_id'     => $user_id,
			'scheme'      => $scheme,
			'token'       => $token,
			'path'        => [ PLUGINS_COOKIE_PATH, ADMIN_COOKIE_PATH ],
			'domain'      => COOKIEDOMAIN ?? '',
		];

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
			'path'        => defined( 'COOKIEPATH' ) ? COOKIEPATH : '/',
			'domain'      => defined( 'COOKIEDOMAIN' ) ? COOKIEDOMAIN : '',
		];

	}

	public function add_form_data( $form ) {

		if ( empty( $this->user ) || empty( $this->login_cookie ) ) {
			return $form;
		}

		$nonce = self::make_nonce( $this->user->ID, $this->login_cookie['token'] );

		$data = apply_filters( 'vestorfilter_auth_payload', [], $this->user );

		$form          = str_replace(
			'</form>',
			"\r\n"
			. '<textarea style="display:none!important" data-vestor-payload>' . esc_html( json_encode( $data ) ) . '</textarea>'
			. '<textarea style="display:none!important" data-vestor-cookie="login">' . esc_html( json_encode( $this->login_cookie ) ) . '</textarea>'
			. "\r\n"
			. '<input data-vestor-nonce type="hidden" name="rest_nonce" value="' . $nonce . '" />'
			. '<input data-vestor-user type="hidden" name="user_id" value="' . $this->user->ID . '" />'
			. "\r\n"
			. '</form>',
			$form
		);

		return $form;
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

		$user = wp_authenticate_username_password( null, $username, $password );
		$user = wp_authenticate_email_password( $user, $username, $password );
		if ( empty( $user ) || is_wp_error( $user ) ) {
			$errors[ 'field' . $email_id ] = '';
			$errors[ 'field' . $password_id ] = '';
		} else {
			$this->user = $user;
			$this->password = $password;
		}

		add_filter( 'frm_invalid_error_message', [ $this, 'login_error_message' ], 10, 1 );

		return $errors;

	}

	function process_login( $params, $errors, $form ) {

		if ( $form->form_key !== 'user-login' || empty( $this->user ) ) {
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


	function login_error_message( $invalid_msg ) {

		$invalid_msg = 'Your username and password was incorrect or could not be found.';

		return $invalid_msg;
	}

	public function validate_account( $errors, $values ) {

		if ( $values['form_key'] !== 'edit-user-profile' ) {
			return $errors;
		}

		if ( ! is_user_logged_in() ) {
			$errors[] = "You aren't logged in.";
			return $errors;
		}

		$fname_id = \FrmField::get_id_by_key( 'edit_fname' );
		$lname_id = \FrmField::get_id_by_key( 'edit_lname' );
		$phone_id = \FrmField::get_id_by_key( 'edit_phone' );
		$email_id = \FrmField::get_id_by_key( 'edit_email' );
		$paswd_id = \FrmField::get_id_by_key( 'edit_password' );

		$fname = $values['item_meta'][ $fname_id ] ?? null;
		$lname = $values['item_meta'][ $lname_id ] ?? null;
		$phone = $values['item_meta'][ $phone_id ] ?? null;
		$email = $values['item_meta'][ $email_id ] ?? null;
		$paswd = $values['item_meta'][ $paswd_id ] ?? null;

		if ( empty( $fname ) ) {
			$errors[ 'field' . $fname_id ] = 'First name cannot be left blank';
		}
		if ( empty( $lname ) ) {
			$errors[ 'field' . $lname_id ] = 'Last name cannot be left blank';
		}
		if ( empty( $phone ) ) {
			$errors[ 'field' . $phone_id ] = 'Phone number cannot be left blank';
		} else if ( absint( $phone ) < 1000000000 ) {
			$errors[ 'field' . $phone_id ] = 'Phone number is invalid';
		}
		

		if ( ! empty( $email ) ) {
			$find = get_user_by( 'email', $email );
			if ( $find ) {
				$errors[ 'field' . $email_id ] = 'This email is already being used by another user';
			}
		}

		$this->form_values = [
			'first_name' => $fname,
			'last_name'  => $lname,
			'phone'      => $phone,
			'email'      => $email,
			'password'   => $paswd,
		];

		return $errors;

	}

	function update_account( $params, $errors, $form ) {

		if ( $form->form_key !== 'edit-user-profile' || ! is_user_logged_in() || ! empty( $errors ) ) {
			return;
		}

		$user_id = get_current_user_id();

		if ( ! empty( $this->form_values ) ) {

			update_user_meta( $user_id, 'phone', $this->form_values['phone'] );
			$twilio_phone = preg_replace( '/[^0-9]/', '', $this->form_values['phone'] );
			if ( strlen( $twilio_phone ) >= 10 ) {
				if ( strlen( $twilio_phone ) === 10 ) {
					$twilio_phone = '1' . $twilio_phone;
				}
				$twilio_phone = '+' . $twilio_phone;
				update_user_meta( $user_id, '_twilio_number', $twilio_phone );
			} else {
				delete_user_meta( $user_id, '_twilio_number' );
			}
			

			$update_user = [
				'first_name' => $this->form_values['first_name'],
				'last_name'  => $this->form_values['last_name'],
			];
			if ( ! empty( $this->form_values['email'] ) ) {
				$update_user['user_email'] = $this->form_values['email'];
			}
			if ( ! empty( $this->form_values['password'] ) ) {
				$update_user['user_pass'] = $this->form_values['password'];
			}

			$update_user['ID'] = $user_id;
			wp_update_user( $update_user );

			Log::add( [ 'action' => 'user-updated', 'user' => $user_id ] );

		}

	}

	function log_login( $who, $user ) {

		Log::add( [ 'action' => 'logged-in', 'user' => $user->ID ] );

	}


}

add_action( 'vestorfilter_installed', [ 'VestorFilter\Hooks\Account', 'init' ] );
