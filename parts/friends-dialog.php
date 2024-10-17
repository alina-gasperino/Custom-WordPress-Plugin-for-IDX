<?php 
if ( empty( $args ) ) {
	return;
}
extract( $args );

use \VestorFilter\Util\Icons;

?>

<aside class="popup" id="friend-favorite-modal" aria-hidden="true" data-is-for-agents="<?= $is_agent ? 'yes' : 'no' ?>">

	<div class="popup__overlay"></div>

	<div class="popup__inside">

		<button class="popup__close" aria-controls="friend-favorite-modal" aria-expanded="true">
			<?php echo Icons::use( 'action-close' ); ?>
			<span class="screen-reader-text">Close this Window</span>
		</button>

		<div class="popup__contents">
		
			<header>
				<h2>Save This Property for <?php $is_agent ? ' a Lead' : 'a Friend' ?></h2>
			</header>

			<form action="" method="POST" data-vf-favorite="friend" class="frm_ajax_submit frm-show-form">

				<div class="frm_form_field form-field frm_top_container">
					<label for="field_new_friend_email" id="field_new_friend_email" class="frm_primary_label">Select a <?= $is_agent ? 'Lead' : 'Friend' ?></label>
					<select id="field_friend_id" name="friend_id">
					<?php foreach( $friends as $friend ) : ?>
						<option value="<?= esc_attr( $friend->ID ) ?>"><?= esc_html( $friend->display_name ) ?> - <?= esc_html( $friend->user_email ) ?></option>
					<?php endforeach; ?>
					</select>
				</div>

				<div class="frm_submit">
					<button class="frm_button_submit frm_final_submit" type="submit">Save Favorite</button>
				</div>

				<input type="hidden" id="field_property_id" name="property_id" value="">

				<?php wp_create_nonce( 'wp_rest' ); ?>

			</form>

		</div>

	</div>

</aside>
<?php if ( $is_agent ) : ?>
<script>
	document.addEventListener( 'modal-opened', () => {
		let openModal = document.querySelector( '.popup[aria-hidden="false"]' );
		if ( ! openModal || openModal.id != 'friend-favorite-modal' ) {
			return;
		}
		console.log( 'init slect2');
		$('#field_friend_id').select2();
	} );
</script>
<?php endif; ?>