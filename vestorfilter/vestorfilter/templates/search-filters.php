<?php

	namespace VestorFilter;

	use \VestorFilter\Util\Icons as Icons;

	wp_enqueue_script( 'vestorhouse-filters' );
	wp_enqueue_script( 'vestorhouse-search' );
	
	$search_hash = Search::get_hash();
	$is_favorite = Favorites::is_search_saved( $search_hash, get_current_user_id() );

	$url = Search::get_url();
	$base_url = $url;
	if ( $pos = strpos( $base_url, '?' ) ) {
		$base_url = substr( $base_url, 0, $pos );
	}

?>
<form data-base-url="<?php echo esc_url( Settings::get_page_url( 'search' ) ); ?>" 
	action="<?php echo esc_url( $base_url ); ?>" 
	method="GET" 
	data-vestor-search="<?php echo esc_attr( $search_hash ); ?>" 
	data-vestor-nonce="<?= Search::get_nonce() ?>"
	class="vf-search__filters has-more-filters navbar navbar-expand-md">

	<button type="button" class="btn btn-toggle vf-search__nav search" data-control-toggle="search">
		<?php echo Icons::use( 'data-search-alt' ); ?>
		<span class="screen-reader-text">Search</span>
	</button>

	<div class="vf-search__subfilter-group" id="vf-subfilter-panel">

		<a class="btn btn-toggle btn-primary vf-search__filters-reset" href="<?= esc_attr( $url ) ?>">
			<?php echo use_icon( 'action-reset' ); ?>
			<span class="screen-reader-text">Reset</span>
		</a>

		<span class="vf-search__nav loading-animation"></span>

		<button title="Save this search and recieve email alerts" data-vestor-save="form" type="button" class="btn btn-primary btn-save has-tooltip" tabindex="-1">
			<?php echo Icons::use( 'action-subscribe' ); ?>
			<span class="screen-reader-text">Save this Search</span>
		</button>

		<button class="vf-search__nav btn btn-primary btn-back" data-vestor-back tabindex="-1" type="button">
			<?php echo Icons::inline( 'arrow' ); ?>
			<span class="screen-reader-text">Back to Map</span>
		</button>

		<?php \VestorFilter\Util\Template::get_part( 'vestorfilter', 'filters/subfilters-panel' ); ?>

		<button class="btn btn-toggle vf-search__nav mode-toggle vf-search__nav--map" type="button" data-switch-mode='map'><?= Icons::use( 'data-maps' ) ?></button>
		<button class="btn btn-toggle vf-search__nav mode-toggle vf-search__nav--list" type="button" data-switch-mode='list'><?= Icons::use( 'data-units' ) ?></button>

		<button class="btn btn-toggle btn-primary vf-search__filters-submit" type="submit">
			<?= Icons::use( 'action-check' ); ?>
			<span class="screen-reader-text">Search</span>
		</button>

	</div>

	<!--button type="button" class="vf-search__nav next" aria-label="Next Property"><?php echo Icons::inline( 'arrow' ); ?></button-->

	

</form>
