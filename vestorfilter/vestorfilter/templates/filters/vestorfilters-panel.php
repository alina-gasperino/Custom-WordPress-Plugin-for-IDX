<?php

namespace VestorFilter;

use \VestorFilter\Util\Icons;

use \VestorFilter\Filters as Filters;
use \VestorFilter\Data as Data;

?>
<div class="vf-panel vf-vestorfilters" id="vestorfilter-selection-panel">

	<?php $filters = \VestorFilter\Filters::get_all( false, false ); ?>

	<button type="button" aria-expanded="false" aria-controls="vf-vestorfilter-panel" class="vf-vestorfilters__toggle">
		<?php echo Icons::inline( 'vf' ); ?>
		<span></span>
		<?php echo Icons::inline( 'arrow' ); ?>
	</button>

	<?php if ( ! empty( $learn_more ) ) : ?>

	<a href="#more" class="btn btn-secondary vf-vestorfilters__learn-more">
		Learn More about Vestor Filter &trade;
	</a>

	<?php endif; ?>

	<div class="inside" aria-hidden="true" id="vf-vestorfilter-panel">

		<button type="button" aria-expanded="false" aria-controls="vf-vestorfilter-panel" class="vf-search__nav close with-icon">
			<?php echo Icons::inline( 'bx-check' ); ?>
			<span>Done</span>
		</button>

		<ul class="menu">

		<?php

		if ( empty( $active_filter ) ) {
			$active_filter = \VestorFilter\Search::get_vf();
		}

		?>

		<?php foreach( $filters as $key ): ?>

			<?php

			$classes = 'vf-vestorfilters__toggle pill-btn dropdown-toggle';
			$classes = apply_filters( 'vestortemplate_vestorfilter__' . $key, $classes );

			$value = \VestorFilter\Search::get_filter_value( $key );

			$active = $active_filter === $key ? "true" : "false";

			$rules = Data::make_rules_string( Filters::get_filter_rules( $key ) );
			
			//$alt = Filters::get_alt_filter( $key );

			?>

			<li id="filter_option_<?php echo esc_attr( $key ) ?>"
				data-filter-active="<?php echo $active; ?>"
				data-toggle-group="vestorfilters"
				data-filter-key="vf"
				data-filter-value="<?php echo esc_attr( $key ); ?>"
				class="vf-vestorfilters__option <?php echo esc_attr( $key ); if ( $active === 'true' ) echo ' active'; ?>"
				data-rules="<?php echo esc_attr( $rules ); ?>"
				data-default-type="<?php echo Filters::get_default_type( $key ); ?>">

				<label for="filter_toggle_<?php echo esc_attr( $key ) ?>">

				<?php do_action( 'vestorfilter_filter_toggle_start__' . sanitize_title( $key, $value ) ); ?>

				<?php echo Icons::inline( Filters::get_filter_icon( $key ) ); ?>

				<span class="label"><?php echo esc_html( ucwords( Filters::get_filter_name( $key ) ) ); ?></span>

				<span style="display:none" class="description"><?php echo Filters::get_filter_description( $key ); ?></span>

				<?php do_action( 'vestorfilter_filter_toggle_end__' . sanitize_title( $key ), $value ); ?>

				</label>

				<input data-filter-value="<?php echo esc_attr( $key ) ?>" id="filter_toggle_<?php echo esc_attr( $key ) ?>" style="display:none" type="radio" name="vf" value="<?php echo esc_attr( $key ) ?>" <?php checked( $active_filter, $key ) ?>>

			</li>

		<?php endforeach; ?>

		</ul>

	</div>

	<span class="spacer"></span>

</div>