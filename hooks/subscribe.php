<?php

namespace VestorFilter;

use VestorFilter\Util\Icons;
	
	?>

	<aside class="popup <?php if ( current_user_can( 'use_dashboard' ) ) echo ' agent-subscription' ?>" id="subscribe-for-user-modal" aria-hidden="true" >

		<div class="popup__overlay"></div>

		<div class="popup__inside">

			<button class="popup__close" aria-controls="subscribe-for-user-modal" aria-expanded="true">
				<?php echo use_icon( 'action-close' ); ?>
				<span class="screen-reader-text">Close this Window</span>
			</button>

			<div class="popup__contents">
				<?php if ( is_user_logged_in() ) { ?>
					<header>
						<h2>Instant Home Alerts</h2>
						<h3>test</h3>
					</header>
					<?php echo do_shortcode( '[formidable id="save_search_for_user" title=false description=false]' ); ?>
				<?php }
				else { ?>
					<header>
						<h2>Log In To Access</h2>
					</header>

					<p>Accounts allow you to save, share and edit all your homes, as well as save, share and edit all your custom search results.</p>
					<?php echo \VestorFilter\Util\SSO::render_login_buttons( [ 'ajaxload' => true, 'label' => '' ] ); ?>
					<?php echo do_shortcode( '[formidable id="user-login" title=false description=false]' ); ?>					
					<p>Forgot your password?
					<a href="<?php echo esc_url( $reset_url ); ?>">Reset it here</a>
					</p>
					<hr>
					<button class="btn btn-secondary" aria-controls="register-modal" aria-expanded="false">
						Create an account
					</button>
				<?php } ?>
			</div>
		</div>

	</aside>
	<script>
		document.addEventListener( 'modal-opened', () => {
			let openModal = document.querySelector( '.popup[aria-hidden="false"]' );
			if ( ! openModal || openModal.id != 'subscribe-for-user-modal' ) {
				return;
			}
			jQuery('#field_saved_lead_id').select2();
		} );
	</script>

	<?php 

	wp_enqueue_script( 'select2' );


add_filter( 'frm_get_default_value', 'VestorTheme\set_default_subscription_settings', 10, 2 );

function set_default_subscription_settings( $new_value, $field ) {
	if ( $field->field_key == 'receive_email_updates' ) { 
		$new_value = is_user_logged_in() ? get_user_meta( get_current_user_id(), 'receive_email_updates', true ) : '';
		if ( empty( $new_value ) ) {
			$new_value = 'yes';
		}
	}
	if ( $field->field_key == 'email_update_frequency' ) { 
		$new_value = is_user_logged_in() ? get_user_meta( get_current_user_id(), 'email_update_frequency', true ) : '';
		if ( empty( $new_value ) ) {
			$new_value = 7;
		}
	}

	return $new_value;
}
