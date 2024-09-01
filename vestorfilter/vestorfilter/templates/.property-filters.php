<?php

	namespace VestorFilter;

	use \VestorFilter\Util\Icons as Icons;

	wp_enqueue_script( 'vestorhouse-filters' );
	wp_enqueue_script( 'vestorhouse-search' );
	
	$url = Settings::get_page_url( 'search' );
	$base = Settings::get_page_url( 'search' );

?>
<form data-base-url="<?php echo esc_url( $base ); ?>" action="<?php echo esc_url( Search::get_url() ); ?>" method="GET" data-vestor-search class="vf-search__filters has-more-filters navbar navbar-expand-md">

	<?php //\VestorFilter\Util\Template::get_part( 'vestorfilter', 'filters/vestorfilters-panel' ); ?>

	<a class="vf-search__nav btn btn-primary btn-back" data-vestor-back tabindex="-1" href="<?php echo esc_url( Settings::get_page_url( 'search' ) ); ?>">
		<?php echo Icons::inline( 'arrow' ); ?>
		<span class="screen-reader-text">Back</span>
	</a>

	<button type="button" class="btn btn-toggle vf-search__nav search" aria-controls="vf-search-panel" aria-expanded="false">
		<?php echo Icons::inline( 'bx-search-alt' ); ?>
		<span class="screen-reader-text">Search</span>
	</button>

	<?php \VestorFilter\Util\Template::get_part( 'vestorfilter', 'filters/mobile-search-panel' ); ?>

	

	<div class="vf-search__subfilter-group" id="vf-subfilter-panel">

		<?php \VestorFilter\Util\Template::get_part( 'vestorfilter', 'filters/subfilters-panel' ); ?>

	</div>

	<?php //\VestorFilter\Util\Template::get_part( 'vestorfilter', 'filters/agent-panel' ); ?>

	<input type="hidden" name="vf" data-filter-value="vf" value="<?= esc_attr( Search::get_filter_value( 'vf' ) ); ?>">

	<button class="screen-reader-text vf-search__filters--submit" type="submit">
			<?php echo Icons::inline( 'search' ); ?>
			<span class="screen-reader-text">Search</span>
		</button>

</form>
