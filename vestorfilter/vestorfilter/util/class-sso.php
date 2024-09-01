<?php

namespace VestorFilter\Util;

use VestorFilter\Plugin;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

class SSO extends Singleton {

	/**
	 * A pointless bit of self-reference
	 *
	 * @var object
	 */
	public static $instance = null;

	public function install() {

		add_action( 'rest_api_init', array( $this, 'init_rest' ) );

		add_shortcode( 'vestofilter-sso', array( self::class, 'render_login_buttons') );
		add_shortcode( 'hyperdrive-sso', array( self::class, 'render_login_buttons') );

		add_action( 'init', array( $this, 'oauth_inject' ) );

	}

	public function init_rest() {

		register_rest_route(
			'vestorfilter/v0',
			'sso-auth',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'rest_auth' ),
				'permission_callback' => '__return_true',
			)
		);

	}

	public function rest_auth( $request ) {

		if ( is_user_logged_in() ) {
			return new \WP_Error( 'unnecessary', 'A user is already logged in.', [ 'status' => '401' ] );
		}

		if ( ! isset( $request['method'] ) ) {
			return new \WP_Error( 'incomplete', 'No authentication method sent.', [ 'status' => '403' ] );
		}

		$payload = null;
		$user = null;

		switch ( $request['method'] ) {

			case 'google':

				if ( ! defined( 'GOOGLE_CLIENT_ID' ) || ! defined( 'GOOGLE_CLIENT_SECRET' ) ) {
					return new \WP_Error( 'error', 'Google API services are not installed', [ 'status' => '500' ] );
				}

				if ( empty( $request['idtoken'] ) ) {
					return new \WP_Error( 'error', 'No authentication token sent', [ 'status' => '403' ] );
				}

				require_once Plugin::$plugin_path . '/vendor/autoload.php';

				$client = new \Google_Client( [ 'client_id' => GOOGLE_CLIENT_ID . '.apps.googleusercontent.com' ] );
				$client->setClientSecret( GOOGLE_CLIENT_SECRET );
				$client->addScope( 'profile' );
				$client->addScope( 'email' );
				$client->addScope( \Google_Service_PeopleService::USER_PHONENUMBERS_READ );

				if ( ! $client ) {
					return new \WP_Error( 'error', 'Error authenticating Google API services', [ 'status' => '500' ] );
				}

				$client->setAccessToken( $request['access'] );
				$payload = $client->verifyIdToken( $request['idtoken'] );

				if ( $payload ) {

					try {
						$service = new \Google_Service_PeopleService($client);

						$person = $service->people->get( 'people/me', array( 'personFields' => 'phoneNumbers' ) );
						$numbers = $person->getPhoneNumbers();
						if ( ! empty( $numbers ) ) {
							$phone = $numbers[0]->getValue();
							$twilio = $numbers[0]->getCanonicalForm();
							$payload['phone'] = $phone;
							$payload['twilio'] = $twilio;
						}
					} catch( \Exception $e ) {}

					$email = $payload['email'];
					$user = get_user_by( 'email', $email );
				}

			break;

			case 'facebook':

				if ( ! defined( 'FACEBOOK_APP_ID' ) || ! defined( 'FACEBOOK_APP_SECRET' ) ) {
					return new \WP_Error( 'error', 'Facebook API services are not installed', [ 'status' => '500' ] );
				}

				if ( empty( $request['token'] ) || empty( $request['uid'] ) ) {
					return new \WP_Error( 'error', 'No authentication token sent', [ 'status' => '403' ] );
				}

				require_once Plugin::$plugin_path . '/vendor/autoload.php';

				$fb = new \Facebook\Facebook( [
					'app_id'                => FACEBOOK_APP_ID,
					'app_secret'            => FACEBOOK_APP_SECRET,
					'default_graph_version' => 'v2.10',
				] );

				try {
					$helper = $fb->getJavaScriptHelper();
					$accessToken = $helper->getAccessToken();
				} catch ( \Facebook\Exception\SDKException $e ) {
					return new \WP_Error( 'error', 'Error authenticating Facebook API services', [ 'status' => '500' ] );
				}

				if ( empty( $accessToken ) ) {
					return new \WP_Error( 'error', 'Error authenticating Facebook access token', [ 'status' => '500' ] );
				}

				try {
					// Returns a `Facebook\Response` object
					$uid = absint( $request['uid'] );
					$response = $fb->get("/{$uid}/?fields=id,name,email", $accessToken->getValue() );
				} catch( \Facebook\Exception\SDKException $e) {
					return new \WP_Error( 'error', 'There was an issue retrieving the user account', [ 'status' => '500' ] );
				}

				$fbuser = $response->getGraphUser();
				if ( empty( $fbuser) ) {
					return new \WP_Error( 'error', 'There was an issue retrieving your account details', [ 'status' => '401' ] );
				}
				$name = $fbuser->getName();
				$email = $fbuser->getField( 'email' );

				$payload = [
					'name' => $name,
					'email' => $email,
				];

				if ( empty( $email ) ) {
					return new \WP_Error( 'error', 'We could not access the email address of your Facebook account.', [ 'status' => '403' ] );
				}

				$user = get_user_by( 'email', $email );

			break;

		}

		if ( ! empty( $user ) ) {

			add_action( 'set_logged_in_cookie', [ $this, 'get_li_cookie_data' ], 20, 6 );

			wp_set_current_user( $user->ID );
			wp_set_auth_cookie( $user->ID, true, true );
			wp_get_current_user();

			$response = [
				'user'   => [
					'email' => $user->user_email,
					'login' => $user->user_login,
					'display_name' => $user->display_name,
				],
				'cookie' => $this->login_cookie,
				'message' => "Hi, {$user->display_name}! You've been successfully logged in.",
			];
			
			do_action( 'wp_login', $user->display_name, $user );

		} else {

			$response = new \WP_Error( 'bad_login', 'Could not authenticate user', [ 'status' => '401' ] );

		}

		$response = apply_filters( 'vestorfilter_sso_login_response', $response, $user, $request['method'], $payload );

		return $response;

	}

	private $login_cookie = null;

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

	public static function build_footer() {

		if ( ! current_theme_supports( 'vestorfilter-sso' ) ) {
			return;
		}

		//self::echo_google_signin_api();

		printf(
			'<script>
				window.vfSSO_authEndpoint = "%s";
				window.vfSSO_keys = %s;
				window.vfSSO_oauthRedirect = "%s";
				window.vfSSO_oauthOrigin = "%s";
			</script>
			<script src="%s"></script>',
			get_rest_url( null, 'vestorfilter/v0/sso-auth' ),
			json_encode( [
				'google' => defined( 'GOOGLE_CLIENT_ID' ) ? GOOGLE_CLIENT_ID : null,
				'facebook' => defined( 'FACEBOOK_APP_ID' ) ? FACEBOOK_APP_ID : null,
				'linkedin' => defined( 'LINKEDIN_CLIENT_ID' ) ? LINKEDIN_CLIENT_ID : null,
			] ),
			esc_attr( add_query_arg( 'oauth', 'linkedin', get_bloginfo( 'url' ) ) ),
			esc_attr( get_bloginfo( 'url' ) ),
			Plugin::$plugin_uri . "/assets/js/sso.js?v=" . Plugin::$build_version,
		);
		printf( '<script src="https://apis.google.com/js/platform.js?onload=vfSSO_initGoogle" async defer></script>' );
		printf( '<script async defer crossorigin="anonymous" src="https://connect.facebook.net/en_US/sdk.js"></script>' );
		/*printf(
			'<script async defer src="%s"></script>',
			Controller::$uri . "/assets/oauth.min.js?v=0.6.2",
		);*/

	}

	public static function echo_google_signin_meta() {

		if ( ! defined( 'GOOGLE_CLIENT_ID' ) ) {
			return;
		}

		printf(
			'<meta name="google-signin-scope" content="profile email">
			<meta name="google-signin-client_id" content="%s.apps.googleusercontent.com">',
			GOOGLE_CLIENT_ID
		);

	}

	public static function echo_google_signin_api() {

		if ( ! defined( 'GOOGLE_CLIENT_ID' ) ) {
			return;
		}



	}

	public static function render_login_buttons( $atts = [], $content = '') {

		if ( is_user_logged_in() ) {
			return;
		}

		$label = 'Sign in with';
		if ( isset( $atts['label'] ) ) {
			$label = $atts['label'];
		}

		$ajaxload = empty( $atts['ajaxload'] ) ? 'false' : 'true';

		add_action( 'wp_footer', array( self::class, 'build_footer' ), 99 );

		ob_start();

		?>

		<div class="vf-sso" style="display:none">

			<?php if ( ! empty( $label ) ) : ?>
			<span class="label"><?php echo $label; ?></span>
			<?php endif; ?>

			<div data-ajax="<?php echo $ajaxload; ?>" data-vf-sso="google" class="vf-sso__google"></div>
			<div data-ajax="<?php echo $ajaxload; ?>" data-vf-sso="facebook" class="vf-sso__facebook"></div>
			<div data-ajax="<?php echo $ajaxload; ?>" data-vf-sso="linkedin" class="vf-sso__linkedin"></div>

		</div>

		<?php

		return ob_get_clean();

	}

	public function oauth_inject() {

		if ( ! isset( $_GET['oauth' ] ) ) {
			return;
		}

		switch ( $_GET['oauth' ] ) {

			case 'linkedin':

				if ( isset( $_GET['error'] ) ) {
					echo 'LinkedIn authorization was canceled.';
					exit;
				}

				$code = filter_input( INPUT_GET, 'code', FILTER_SANITIZE_STRING );
				if ( empty( $code ) ) {
					echo 'There was an error receiving the authentication response from LinkedIn';
					exit;
				}

				$response = wp_safe_remote_post(
					'https://www.linkedin.com/oauth/v2/accessToken',
					[
						'method'      => 'POST',
						'timeout'     => 10,
						'redirection' => 5,
						'headers'     => array(),
						'httpversion' => '1.0',
						'blocking'    => true,
						'body'        => array(
							'grant_type'    => 'authorization_code',
							'code'          => $code,
							'redirect_uri'  => add_query_arg( 'oauth', 'linkedin', get_bloginfo( 'url' ) ),
							'client_id'     => LINKEDIN_CLIENT_ID,
							'client_secret' => LINKEDIN_CLIENT_SECRET,
						),
					]
				);

				if ( $response['response']['code'] !== 200 || empty( $response['body'] ) ) {
					echo 'There was an error authenticating your LinkedIn account';
					exit;
				}

				$body = json_decode( $response['body'] );
				if (  empty( $body->access_token ) ) {
					echo 'There was an error communicating with LinkedIn';
					//var_dump( $body );
					exit;
				}

				$response = wp_safe_remote_get(
					'https://api.linkedin.com/v2/clientAwareMemberHandles?q=members&projection=(elements*(primary,type,handle~))',
					[
						'method'      => 'GET',
						'timeout'     => 10,
						'redirection' => 5,
						'headers'     => array(
							'Authorization' => 'Bearer ' . $body->access_token,
						),
						'httpversion' => '1.1',
						'blocking'    => true,
					]
				);

				if ( $response['response']['code'] !== 200 || empty( $response['body'] ) ) {
					echo 'There was an error receiving data from your LinkedIn account';
					//var_dump( $response );
					exit;
				}

				$contact = json_decode( $response['body'], true );
				foreach( $contact['elements'] as $contact ) {
					if ( $contact['type'] === 'EMAIL' && $contact['primary'] === true ) {
						$email = $contact['handle~']['emailAddress'];
						break;
					}
				}

				if ( empty( $email ) ) {
					echo 'There is no email address associated with your LinkedIn account.';
				}

				$user = get_user_by( 'email', $email );
				$payload = [];
				if ( ! empty( $user ) ) {

					add_action( 'set_logged_in_cookie', [ $this, 'get_li_cookie_data' ], 20, 6 );

					wp_set_current_user( $user->ID );
					wp_set_auth_cookie( $user->ID, true, true );
					wp_get_current_user();

					$response = [
						'sso'    => 'login',
						'user'   => [
							'email' => $user->user_email,
							'login' => $user->user_login,
							'display_name' => $user->display_name,
						],
						'cookie' => $this->login_cookie,
						'message' => "Hi, {$user->display_name}! You've been successfully logged in.",
					];

				} else {

					$response = wp_safe_remote_get(
						'https://api.linkedin.com/v2/me',
						[
							'method'      => 'GET',
							'timeout'     => 10,
							'redirection' => 5,
							'headers'     => array(
								'Authorization' => 'Bearer ' . $body->access_token,
							),
							'httpversion' => '1.1',
							'blocking'    => true,
						]
					);

					if ( $response['response']['code'] === 200 && ! empty( $response['body'] ) ) {
						$payload = json_decode( $response['body'], true );
						$payload['email'] = $email;
					}

					$response = new \WP_Error( 'bad_login', 'Could not authenticate user', [ 'status' => '401' ] );

				}

				$response = apply_filters( 'vestorfilter_sso_login_response', $response, $user, 'linkedin', $payload );

				if ( is_wp_error( $response ) ) {
					echo $response->get_error_message();
					exit;
				}

				?>
				<script>
					var response = <?php echo json_encode( $response ) ?>;
					window.opener.postMessage( response, "<?= esc_attr( get_bloginfo( 'url' ) ); ?>" );
				</script>
				<?php

			break;

		}

		exit;

	}


}

add_action( 'vestorfilter_installed', array( 'VestorFilter\Util\SSO', 'init' ) );
