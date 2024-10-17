<?php 
if ( empty( $args ) ) {
	$args = [];
}
extract( $args + [ 'is_agent' => false, 'friends' => [] ] );

use \VestorFilter\Util\Icons;

?>

<aside class="popup" id="save-map-modal" aria-hidden="true">

	<div class="popup__overlay"></div>

	<div class="popup__inside">

		<button class="popup__close" aria-controls="save-map-modal" aria-expanded="true">
			<?php echo Icons::use( 'action-close' ); ?>
			<span class="screen-reader-text">Close this Window</span>
		</button>

		<div class="popup__contents">
		
			<header>
				<h2>Save This Map</h2>
			</header>

			<form action="" method="POST" data-save-map-form>

				<div class="frm_form_field form-field frm_top_container">
					<label for="map_name" id="map_name_label" class="frm_primary_label">Map Name</label>
					<input type="text" name="map_name" id="map_name" placeholder="Custom Map" value="">
				</div>

				<?php if ( $is_agent ) : ?>
				<div class="frm_form_field form-field frm_top_container">
					<label for="field_map_user_id" id="field_map_user_id_label" class="frm_primary_label">Select a user</label>
					<select id="field_map_user_id" name="user_id">
						<option value="user" selected><?= esc_html__( 'Me', 'vestorfilter' ) ?></option>
					<?php foreach( $friends as $friend ) : ?>
						<option value="<?= esc_attr( $friend->ID ) ?>"><?= esc_html( $friend->display_name ) ?> - <?= esc_html( $friend->user_email ) ?></option>
					<?php endforeach; ?>
					</select>
				</div>
				<?php else: ?>
				<input type="hidden" id="field_map_user_id" name="user_id" value="user">
				<?php endif; ?>

				<div class="frm_submit">
					<button class="frm_button_submit frm_final_submit" type="submit">Save Map</button>
				</div>

			</form>

		</div>

	</div>

</aside>
<?php if ( $is_agent ) : ?>
<script>
	document.addEventListener( 'modal-opened', () => {
		let openModal = document.querySelector( '.popup[aria-hidden="false"]' );
		if ( ! openModal || openModal.id != 'save-map-modal' ) {
			return;
		}
		jQuery('#field_map_user_id').select2();
	} );
</script>
<?php endif; ?>