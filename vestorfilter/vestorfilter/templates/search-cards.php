<?php

	namespace VestorFilter;
	use \VestorFilter\Util\Icons as Icons;

	wp_enqueue_script( 'vestorhouse-filters' );
	wp_enqueue_script( 'vestorhouse-search' );

	$search_page = Settings::get_page_template( 'search_page' );
	if ( empty( $search_page ) || ! ( $search_url = get_permalink( $search_page ) ) ) {
		echo 'VestorFilter has not been correctly configured on this domain.';
		return;
	}

	$slug = '';
	$placeholder = __( 'Add a location or any custom word...', 'vestorfilter' );

	$default_location = Settings::get( 'default_location_id' );
	$preset_locations = [];
	if ( $default_location ) {
		$locations = is_array( $default_location ) ? $default_location : explode( ',', $default_location );
		foreach( $locations as $location_id ) {
			$location = Location::get( $location_id );
			if ( $location && count( $locations ) === 1 ) {
				$slug = trailingslashit( Location::get_slug( $location ) );
			}
			if ( $location ) {
				$preset_locations[] = $location;
				$placeholder = __( 'Add a location or add any custom word.', 'vestorfilter' );
			}
		}
	}
	if ( is_array( $default_location ) ) {
		$default_location = implode( ',', $default_location );
	}

	$classes = 'vf-search__cards container-lg';
	if ( ! empty( $search_only ) ) {
		$classes .= ' hide-cards';
	}

	if ( empty( $default_filters ) ) {
		$default_filters = [];
	}

	$default_keywords = '';

?>
<form class="<?= esc_attr( $classes ); ?>" aria-label="Search" method="GET" data-base-url="<?php echo esc_url( $search_url ); ?>" action="<?php echo esc_url( $search_url . $slug ); ?>" data-vestor-search="homepage">

	<div class="pill-input pill-input__text vf-search__filters--location">
		<?php echo Icons::use( 'data-search-alt' ); ?>
		<label for="search-location"><?php esc_html_e( 'Enter a county, city, neighborhood, zip, address, MLS #, school, and/or any custom keyword', 'vestorfilter' ); ?></label>
		<div class="value">
			<?php foreach( $preset_locations as $location ) : ?>
			<button data-label="<?= esc_attr( $location->value ) ?>" data-value="<?= esc_attr( $location->ID ) ?>" data-slug="<?= esc_attr( trailingslashit( Location::get_slug( $location ) ) ) ?>" type="button" aria-label="Remove <?= $location->value ?> from the search query" class="vf-search__location-value"><?= $location->value; ?> <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path d="M16.192 6.344L11.949 10.586 7.707 6.344 6.293 7.758 10.535 12 6.293 16.242 7.707 17.656 11.949 13.414 16.192 17.656 17.606 16.242 13.364 12 17.606 7.758z"></path></svg></button>
			<?php endforeach ?>
			<input autocomplete="off" data-default="<?= esc_attr( $placeholder ) ?>" data-search="query" type="text" value="" id="search-location" name="location_query" placeholder="<?= esc_attr( $placeholder ) ?>">
			<input type="hidden" data-filter-key="location" data-filter-value="location" value="<?= esc_attr( $default_location ?? '' ) ?>" name="location" />
			<input type="hidden" data-filter-key="search" data-filter-value="search" value="<?= esc_attr( $default_keywords ?? '' ) ?>" name="search" />

		</div>
		<div data-search-autocomplete class="vf-search__filters--location--options" data-default="<?= esc_attr( $default_location ) ?>"></div>
	</div>

	<?php \VestorFilter\Util\Template::get_part( 'vestorfilter', 'filters/vestorfilters-panel', [ 'active_filter' => $default_filters['vf'] ?? 'ppsf', 'learn_more' => ! empty( $show_learn_more ) ] ); ?>

	<?php \VestorFilter\Util\Template::get_part( 'vestorfilter', 'filters/morefilters-panel', [ 'defaults' => $default_filters ] ); ?>

	<?php \VestorFilter\Util\Template::get_part( 'vestorfilter', 'filters/agent-panel', [ 'active_filter' => 'ppsf' ] ); ?>

	<div class="vf-search__filters--actions">

		<button class="btn btn-primary reset" type="reset" data-search-action="reset" <?php if ( empty( $hash ) ) echo 'disabled' ?>>
			<span>Reset Search</span>
		</button>

		<button class="btn btn-secondary submit" type="submit" data-search-action="submit" <?php if ( empty( $hash ) ) echo 'disabled' ?>>
			<?php if ( empty( $hash ) ) : ?>
			<span>View Properties</span>
			<?php else : ?>
			<span><?= $count ?> properties found</span>
			<?php endif; ?>
			<?php echo Icons::inline('arrow'); ?>
		</button>

	</div>

</form>
