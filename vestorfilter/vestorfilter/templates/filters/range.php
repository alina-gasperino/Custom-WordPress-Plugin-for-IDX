<?php 

if ( empty( $input_prefix ) ) {
	$input_prefix = 'vf-filter-toggle__';
}

$values = empty( $value ) ? [ $filter['min'], $filter['max'] ] : explode( ':', $value );
if ( count ( $values ) !== 2 ) {
	$values = [ $filter['min'], $filter['max'] ];
}
if ( empty( $values[0] ) ) {
	$values[0] = $filter['min'];
}
if ( empty( $values[1] ) ) {
	$values[1] = $filter['max'];
}

?>
<input type="hidden" data-filter-value="<?php echo esc_attr( $key ) ?>" id="filter--<?php echo sanitize_title( $key ) ?>" name="<?php echo esc_attr( $key ) ?>" value="<?php echo esc_attr( $value ) ?>">
<div class="range-slider"
data-for="<?php echo esc_attr( $input_prefix ); ?><?php echo esc_attr( $key ); ?>"
data-value-start="<?php echo esc_attr( $values[0] ); ?>"
data-value-end="<?php echo esc_attr( $values[1] ); ?>"
data-key="<?php echo esc_attr( $key ); ?>"
data-min="<?php echo esc_attr( $filter['min'] ?? '' ); ?>"
data-max="<?php echo esc_attr( $filter['max'] ?? '' ); ?>"
data-no-max="<?php echo esc_attr( ! empty( $filter['nomax'] ) ? 'true' : 'false' ); ?>"
data-step="<?php echo esc_attr( $filter['step'] ?? 1 ); ?>"
data-format="<?php echo esc_attr( $filter['format'] ?? 'int' ); ?>"
data-range="<?php echo esc_attr( json_encode( $filter['range'] ?? false ) ); ?>"></div>
