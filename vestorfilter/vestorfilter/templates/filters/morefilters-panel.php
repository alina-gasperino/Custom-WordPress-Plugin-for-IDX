<?php

namespace VestorFilter;

use \VestorFilter\Util\Icons as Icons;

if ( empty( $defaults ) ) {
	$defaults = [];
}

?>
<div class="vf-morefilters vf-panel vf-collapse">

	<button type="button" id="vf-morefilters__toggle" aria-controls="vf-morefilters__contents" class="vf-collapse__toggle stay-open" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">

		<span class="inside">

			<?php echo Icons::inline( 'more-filters' ); ?>

			<span class="label"><?php echo esc_html_e( 'Property Filters', 'vestorfilter' ); ?></span>

		</span>

	</button>

	<div class="inside" id="vf-morefilters__contents">

		<button type="button" aria-expanded="false" aria-controls="vf-morefilters__contents" class="vf-search__nav close stay-open">
			<?php echo Icons::inline( 'bx-check' ); ?>
			<span>Done</span>
		</button>

		<ul class="menu">

		<?php foreach ( Data::get_allowed_filters() as $key => $filter ) : ?>

			<?php

			if ( isset( $filter['display'] ) && ( $filter['display'] === false || $filter['display'] === 'results' ) ) {
				continue;
			}

			$value = filter_input( INPUT_GET, $key, FILTER_SANITIZE_STRING ) ?: null;
			if ( $value === ':' ) {
				$value = null;
			}
			if ( empty( $value ) && isset( $defaults[$key] ) ) {
				$value = $defaults[$key];
			}
			
			$value = apply_filters( 'vestortemplate_search_value__' . $key, $value, $filter );

			if ( $filter['type'] === 'options' && isset( $filter['options'][ $value ] ) ) {
				$value = $value;
			} elseif ( $filter['type'] === 'options' && ! in_array( $value, $filter['options'] ) ) {
				$value = null;
			}

			if ( empty( $value ) && ! empty( $filter['default'] ) ) {
				$value = $filter['default'];
			}

			if ( $value === ':' ) {
				$value = '';
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

				$classes = 'vf-morefilters__toggle pill-btn dropdown-toggle';
				$classes = apply_filters( 'vestortemplate_search_filter__' . $key, $classes, $value, $filter );

				?>

				<!--input type="hidden" data-filter-value="<?php echo esc_attr( $key ); ?>" name="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $value ) ?>"-->

				<button type="button"  id="vf-morefilters__toggle--<?php echo esc_attr( $key ); ?>" aria-controls="vf-morefilters__panel--<?php echo esc_attr( $key ); ?>" class="<?php echo esc_attr( $classes ); ?>" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">

					<span class="inside">

						<?php echo use_icon( 'data-' . $key ); ?>

						<span class="label"><?php echo esc_html( $filter['label'] ); ?></span>

						<span class="value"><?php 
						
						if ( $filter['type'] === 'range' ) {
							echo esc_html( apply_filters( 'vestortemplate_filter_value__' . $key, str_replace( ':', ' - ', $value ?: '' ), $value ) );
						} elseif ( $filter['type'] === 'options' && isset( $filter['options'][ $value ] ) ) {
							echo esc_html( apply_filters( 'vestortemplate_filter_value__' . $key, $filter['options'][ $value ], $value ) );
						} else {
							echo esc_html( $value ?: '' );
						}
						
						?></span>

					</span>

				</button>

				<div class="vf-morefilters__panel" id="vf-morefilters__panel--<?php echo esc_attr( $key ); ?>">

					<div class="inside">

					<?php 
					
					\VestorFilter\Util\Template::get_part(
						'vestorfilter',
						'filters/' . $filter['type'],
						[
							'key'          => $key,
							'filter'       => $filter,
							'value'        => $value,
							'input_prefix' => 'vf-morefilters__toggle--'
						]
					); ?>

					</div>

				</div>

			</li>

		<?php endforeach; ?>

		</ul>

	</div>

	<span class="spacer"></span>


</div>
