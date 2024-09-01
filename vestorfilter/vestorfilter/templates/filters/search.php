<?php

use \VestorFilter\Util\Icons as Icons;

if ( empty( $input_prefix ) ) {
	$input_prefix = 'vf-filter-toggle__';
}

$placeholder = __( 'Add a location or any custom word...', 'vestorfilter' );
$settings_location = \VestorFilter\Settings::get( 'default_location_id' );

$default_location = ! empty( $value ) ? $value : $settings_location;
$preset_locations = [];

if ( is_array( $default_location ) && isset( $default_location['id'] ) ) {
	$preset_locations[] = (object) [
		'ID' => $default_location['id'],
		'value' => 'Custom Map',
		'slug' => $default_location['id'],
	];
	$default_location = $default_location['search_param'] ?? null;
} elseif ( is_string( $default_location ) && strpos( $default_location, '[' ) !== false ) {
	$preset_locations[] = (object) [
		'ID' => $default_location,
		'value' => 'Custom Map',
		'slug' => '',
	];
	$default_location = $default_location ?? null;
} elseif ( $default_location ) {
	$locations = is_array( $default_location ) ? $default_location : explode( ',', $default_location );
	foreach( $locations as $location_id ) {
		$location = \VestorFilter\Location::get( absint( $location_id ) );
		if ( $location && count( $locations ) === 1 ) {
			$slug = trailingslashit( \VestorFilter\Location::get_slug( $location ) );
		}
		if ( $location ) {
			$preset_locations[] = $location;
			$placeholder = __( 'Add a location or any custom word.', 'vestorfilter' );
		}
	}
	if ( is_array( $default_location ) ) {
		$default_location = implode( ',', $default_location );
	}
}

$default_search = $_REQUEST['search'] ?? '';
if ( ! empty( $default_search ) ) {
	$keywords = explode( ' ', $default_search );
	foreach( $keywords as $id => $keyword ) {
		$keywords[$id] = strtolower( preg_replace( '/[^a-zA-Z0-9-]/i', '', $keyword ) );
	}
	$default_search = implode( ' ', $keywords );
} else {
	$keywords = [];
}

if ( is_array( $settings_location ) ) {
	$settings_location = implode( ',', $settings_location );
}

?>
<div class="vf-filter-panel__location--input vf-search__filters--location" data-for="<?php echo esc_attr( $input_prefix ); ?><?php echo esc_attr( $key ); ?>" >

	<label><?php esc_html_e( 'Location or Keyword', 'vestorfilter' ); ?></label>
	<div class="value">
		<?php foreach( $preset_locations as $location ) : ?>
			<button data-label="<?= esc_attr( $location->value ) ?>" data-value="<?= esc_attr( $location->ID ) ?>" data-slug="<?= esc_attr( $location->slug ?? trailingslashit( \VestorFilter\Location::get_slug( $location ) ) ) ?>" type="button" aria-label="Remove <?= $location->value ?> from the search query" class="vf-search__location-value"><?= $location->value; ?> <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path d="M16.192 6.344L11.949 10.586 7.707 6.344 6.293 7.758 10.535 12 6.293 16.242 7.707 17.656 11.949 13.414 16.192 17.656 17.606 16.242 13.364 12 17.606 7.758z"></path></svg></button>
		<?php endforeach ?>
		<?php foreach( $keywords as $keyword ) : ?>
			<button data-label="<?= esc_attr( $keyword ) ?>" data-value="<?= esc_attr( $keyword ) ?>" type="button" aria-label="Remove <?= $keyword ?> from the search query" class="vf-search__keyword-value"><?= $keyword; ?> <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path d="M16.192 6.344L11.949 10.586 7.707 6.344 6.293 7.758 10.535 12 6.293 16.242 7.707 17.656 11.949 13.414 16.192 17.656 17.606 16.242 13.364 12 17.606 7.758z"></path></svg></button>
		<?php endforeach ?>

		<button type="button" data-location-action="clear" class="btn vf-filter-panel__location-clear">
			<?php echo Icons::use( 'action-close' ); ?>
			<span class="screen-reader-text">Clear locations</span>
		</button>

		<input autocomplete="off" data-search="query" type="text" class="value" value="" id="search-location" name="location_query" placeholder="<?= esc_attr( $placeholder ) ?>">
		<button type="button" data-location-action="submit" class="btn vf-filter-panel__location-submit">
			<?php echo Icons::use( 'action-check' ); ?>
			<span class="screen-reader-text">Update location</span>
		</button>
		<button type="button" data-location-action="reset" class="btn vf-filter-panel__location-reset">
			<?php echo Icons::use( 'action-reset' ); ?>
			<span class="screen-reader-text">Reset location</span>
		</button>
		
		
		<input data-filter-value="location" type="hidden" name="location" value="<?= esc_attr( $default_location ) ?>" data-default="<?= esc_attr( $settings_location ) ?>">
		<input data-filter-value="search" type="hidden" name="search" value="<?= esc_attr( $default_search ) ?>">

	</div>
	<div data-search-autocomplete class="vf-search__filters--location--options"></div>

</div>