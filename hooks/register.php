<?php

namespace VestorTheme;

use VestorFilter\Util\Icons;

add_action( 'theme__after_footer', 'VestorTheme\output_register_form_popup' );

function output_register_form_popup() {

	if ( is_user_logged_in() ) {
		return;
	}

	$use_ajax = true;

	if ( class_exists( 'FrmRegGlobalSettings' ) ) {
		$global_settings = new \FrmRegGlobalSettings;
		$login_page = $global_settings->get_global_page( 'login_page' );
		$this_page = get_queried_object();
		if ( $this_page && ! empty( $this_page->ID ) && $this_page->ID == $login_page ) {
			$use_ajax = false;
		}
	}

	?>

	<aside class="popup" id="register-modal" aria-hidden="true" data-use-ajax="<?php echo $use_ajax ? 'true' : 'false' ?>">

		<div class="popup__overlay"></div>

		<div class="popup__inside is-wide">

			<button class="popup__close" aria-controls="register-modal" aria-expanded="true">
				<?php echo Icons::use( 'action-close' ); ?>
				<span class="screen-reader-text">Close this Window</span>
			</button>

			<div class="popup__contents">

				<header>
					<h2>Register an Account</h2>
				</header>

				<?php echo \VestorFilter\Util\SSO::render_login_buttons( [ 'ajaxload' => $use_ajax, 'label' => 'Register with' ] ); ?>

				<hr>

				<?php echo do_shortcode( '[formidable id="user-registration"]' ); ?>

				<hr>

				<p><strong>Already have an account?</strong>

				<button class="btn btn-inline" aria-controls="login-modal" aria-expanded="false">
					Log In
				</button>

				</p>

			</div>

		</div>

	</aside>

	<?php

}

add_action( 'init', 'VestorTheme\register_shortcode' );

function register_shortcode() {

	add_shortcode( 'registration-btn',  'VestorTheme\registration_button' );

}

function registration_button() {

	return sprintf( '<button class="btn btn-secondary hide-if-logged-in" aria-controls="register-modal" aria-expanded="false">%s</button>',
		__( 'Create an Account', 'vestortheme' )
	);
}