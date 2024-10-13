<?php 

namespace VestorFilter; 

use \VestorFilter\Util\Icons as Icons;

$misc_filters = '';

?>
<div class="vf-search__subfilters navbar-collapse">

	<?php \VestorFilter\Util\Template::get_part( 'vestorfilter', 'filters/vestorfilters-panel' ); ?>

	<ul class="navbar-nav" style="z-index: 11;">

	<?php
        foreach ( Data::get_allowed_filters() as $key => $filter ) : ?>

		<?php

		if ( isset( $filter['display'] ) && $filter['display'] === false ) {
			continue;
		}

		if ( ! empty( $filter['misc'] ) ) {
			ob_start();
		}

		$value = filter_input( INPUT_GET, $key, FILTER_SANITIZE_STRING ) ?: null;
		if ( $value === ':' ) {
			$value = null;
		}
		$value = apply_filters( 'vestortemplate_search_value__' . $key, $value, $filter );


		if ( empty( $filter['index'] ) && $filter['type'] === 'options' && ! isset( $filter['options'][ $value ] ) ) {
			$value = null;
		}

		if ( empty( $value ) && ! empty( $filter['default'] ) ) {
			$value = $filter['default'];
		}

		$rules = Data::make_rules_string( $filter['rules'] ?? [] );

		$classes = [ 'nav-item', 'dropdown' ];
		if ( isset( $filter['label2'] ) ) {
			$classes[] = 'two-line';
		}
		if ( ! empty( $value ) ) {
			$classes[] = 'active';
		}
		if ( ! empty( $filter['misc'] ) ) {
			$classes[] = 'misc';
		}
		if ( ! empty( $filter['classes'] ) ) {
			$classes = array_merge( $classes, $filter['classes'] );
		}

		?>

		<li data-key="<?php echo esc_attr( $key ); ?>" <?php
		
			if ( ! empty( $rules ) ) {
				printf( 
					'data-rules="%s" ',
					esc_attr( $rules )
				);
			}
			
			printf( 
				'class="%s"',
				implode( ' ', $classes )
			);
			
			?>>

			<?php

			$classes = 'vf-search__filters-toggle pill-btn dropdown-toggle';

			$classes = apply_filters( 'vestortemplate_search_filter__' . $key, $classes, $value, $filter );

			?>
			<button type="button"  id="vf-filter-toggle__<?php echo esc_attr( $key ); ?>" aria-controls="vf-filter-panel__<?php echo esc_attr( $key ); ?>" class="<?php echo esc_attr( $classes ); ?>" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" onclick="openCatcher(this.id);">

				<span class="inside">

					<?php echo use_icon( 'data-' . $key ); ?>

					<span class="label"><?php echo esc_html( $filter['label'] ); ?></span>

					<span class="value"><?php 

						$output = Data::get_filter_value( $filter, $value, $key );
						
						echo esc_html( $output );
						
						?></span>

				</span>

			</button>

			<div class="dropdown-menu" aria-hidden="true" id="vf-filter-panel__<?php echo esc_attr( $key ); ?>">

				<div class="inside">

				<?php \VestorFilter\Util\Template::get_part( 'vestorfilter', 'filters/' . $filter['type'], [ 'key' => $key, 'filter' => $filter, 'value' => $value ] ); ?>

				</div>

			</div>

		</li>

		<?php 
		
		if ( ! empty( $filter['misc'] ) ) {
			$misc_filters .= ob_get_clean();
		}

		?>

	<?php endforeach; ?>

		<li class="nav-item dropdown more-filters">

			<button type="button" id="vf-filter-toggle__more" aria-controls="vf-filter-panel__more" class="vf-search__filters-toggle pill-btn dropdown-toggle stay-open" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" onclick="openCatcher(this.id);">

				<span class="inside">

					<?php echo Icons::inline( 'more-filters' ); ?>

					<span class="label"><?php echo esc_html_e( 'Filters', 'vestorfilter' ); ?></span>
					<span class="value"></span>

				</span>

			</button>

			<div class="dropdown-menu" aria-hidden="true" id="vf-filter-panel__more">

				<button type="button" aria-expanded="false" aria-controls="vf-filter-panel__more" class="vf-search__nav close stay-open" onclick="closeMoreFilters();">
					<?php echo Icons::inline( 'bx-check' ); ?>
					<span>Done</span>
				</button>

				<div class="inside">

					<ul class="filter-list">
					
					<?php echo $misc_filters; ?>
					
					</ul>

				</div>

			</div>

		</li>

	</ul>
<?php

    /**
     * Click Catcher DIV element
     *
     * None visible element to catch the user clicks outside
     * filter menus and close them by triggering
     * function " closeMoreFilters() ".
     *
     * @Note:   It has no other purpose.
     *          It has no styling css rule class.
     * @id:     filter-click-catcher
     */

?>
    <div id="filter-click-catcher" style="position:fixed; min-width: 100%; height: 100vh; top:49px; left: 0px; z-index: 10; overflow-x: hidden; pointer-events: none;" data-click-catcher="close" onclick="closeMoreFilters();"></div>
</div>
<style>
    @media (min-width: 576px)
    {
        .vf-block-results__loop {flex-flow: column;}
    }
</style>

