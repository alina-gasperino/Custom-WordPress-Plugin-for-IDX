<?php

namespace VestorTheme;

use VestorFilter\Util\Icons;
	
	?>

	<aside class="popup <?php if ( current_user_can( 'use_dashboard' ) ) echo ' agent-subscription' ?>" id="subscribe-for-user-modal" aria-hidden="true" >

		<div class="popup__overlay"></div>

		<div class="popup__inside">

			<button class="popup__close" aria-controls="subscribe-for-user-modal" aria-expanded="true">
				<?php echo Icons::use( 'action-close' ); ?>
				<span class="screen-reader-text">Close this Window</span>
			</button>

			<div class="popup__contents">
			
				<header>
					<h2>Instant Home Alerts</h2>
					<?php if ( ! current_user_can( 'use_dashboard' ) ) : ?>
						<p>Sign up to receive email updates on your searches</p>
					<?php endif; ?>
				</header>

				<?php echo do_shortcode( '[formidable id="save_search_for_user" title=false description=false]' ); ?>
				
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
