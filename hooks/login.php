<?php

namespace VestorTheme;

use VestorFilter\Util\Icons;


add_action( 'theme__after_footer', 'VestorTheme\output_login_form_popup' );

function output_login_form_popup() {

	if ( is_user_logged_in() ) {
		return;
	}

	if ( class_exists( 'FrmRegGlobalSettings' ) ) {
		$global_settings = new \FrmRegGlobalSettings;
		$reset_page = $global_settings->get_global_page( 'resetpass_page' );
		$login_page = $global_settings->get_global_page( 'login_page' );
		if ( $reset_page ) {
			$reset_url = get_permalink( $reset_page );
		}
		$this_page = get_queried_object();
		if ( $this_page && ! empty( $this_page->ID ) && $this_page->ID == $login_page ) {
			return;
		}
	} 
	
	if ( empty( $reset_url ) ) {
		$reset_url = '/wp-login.php?action=reset';
	}

	

	?>

	<aside class="popup" id="login-modal" aria-hidden="true">

		<div class="popup__overlay"></div>

		<div class="popup__inside">

			<button class="popup__close" aria-controls="login-modal" aria-expanded="true">
				<?php echo Icons::use( 'action-close' ); ?>
				<span class="screen-reader-text">Close this Window</span>
			</button>

			<div class="popup__contents">
			
				<header>
					<h2>Log In To Access</h2>
				</header>

				<p>Accounts allow you to save, share and edit all your homes, as well as save, share and edit all your custom search results.</p>

				<?php echo \VestorFilter\Util\SSO::render_login_buttons( [ 'ajaxload' => true, 'label' => '' ] ); ?>


				<?php echo do_shortcode( '[formidable id="user-login" title=false description=false]' ); ?>
				
				<p>
				Forgot your password?
				<a href="<?php echo esc_url( $reset_url ); ?>">Reset it here</a>
				</p>

				<hr>

				<button class="btn btn-secondary" aria-controls="register-modal" aria-expanded="false">
					Create an account
				</button>

			</div>

		</div>

	</aside>

	<?php

}
/*
add_action( 'login_form', 'VestorTheme\login_sso_fields', 11 );

function login_sso_fields() {
	
	echo 

}
*/