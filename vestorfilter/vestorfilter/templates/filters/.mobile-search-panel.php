<div class="vf-searchbar vf-search__filters--location" id="vf-search-panel" aria-hidden="true">

	<?php

	$value = '';

	$placeholder = __( 'Add a location or any custom word...', 'vestorfilter' );
	$default_location = \VestorFilter\Search::get_filter_value( 'location' ) ?: '';
	$preset_locations = [];
	if ( is_array( $default_location ) && isset( $default_location['id'] ) ) {
		$preset_locations[] = (object) [
			'ID' => $default_location['id'],
			'value' => 'Custom Map',
			'slug' => $default_location['id'],
		];
		$default_location = $default_location['search_param'] ?? null;
	} elseif ( $default_location ) {
		$locations = is_array( $default_location ) ? $default_location : explode( ',', $default_location );
		foreach( $locations as $location_id ) {
			$location = \VestorFilter\Location::get( absint( $location_id ) );
			if ( $location && count( $locations ) === 1 ) {
				$slug = trailingslashit( \VestorFilter\Location::get_slug( $location ) );
			}
			if ( $location ) {
				$preset_locations[] = $location;
				$placeholder = __( 'Add a location or add any custom word.', 'vestorfilter' );
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

	?>

	<div class="vf-searchbar__location">
		<?php foreach( $preset_locations as $location ) : ?>
			<button data-label="<?= esc_attr( $location->value ) ?>" data-value="<?= esc_attr( $location->ID ) ?>" data-slug="<?= esc_attr( $location->slug ?? trailingslashit( \VestorFilter\Location::get_slug( $location ) ) ) ?>" type="button" aria-label="Remove <?= $location->value ?> from the search query" class="vf-search__location-value"><?= $location->value; ?> <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path d="M16.192 6.344L11.949 10.586 7.707 6.344 6.293 7.758 10.535 12 6.293 16.242 7.707 17.656 11.949 13.414 16.192 17.656 17.606 16.242 13.364 12 17.606 7.758z"></path></svg></button>
		<?php endforeach ?>
		<?php foreach( $keywords as $keyword ) : ?>
			<button data-label="<?= esc_attr( $keyword ) ?>" data-value="<?= esc_attr( $keyword ) ?>" type="button" aria-label="Remove <?= $keyword ?> from the search query" class="vf-search__keyword-value"><?= $keyword; ?> <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path d="M16.192 6.344L11.949 10.586 7.707 6.344 6.293 7.758 10.535 12 6.293 16.242 7.707 17.656 11.949 13.414 16.192 17.656 17.606 16.242 13.364 12 17.606 7.758z"></path></svg></button>
		<?php endforeach ?>
		<input 
			type="text" 
			name="location"
			data-search="query"
			placeholder="<?php echo esc_attr( $placeholder ) ?>" 
			autocomplete="off"
			class="vf-searchbar__input"
		/>
		<input data-filter-value="location" type="hidden" name="location" value="<?= esc_attr( $default_location ) ?>">
		<input data-filter-value="location" type="hidden" name="search" value="<?= esc_attr( $default_search ) ?>">

	</div>

	<div data-search-autocomplete class="vf-search__filters--location--options" data-default="<?= esc_attr( $default_location ) ?>"></div>

</div>