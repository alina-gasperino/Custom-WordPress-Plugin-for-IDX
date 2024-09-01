<?php

if ( empty( $input_prefix ) ) {
	$input_prefix = 'vf-filter-toggle__';
}

$available = apply_filters( "vestorfilter_{$key}_available_options", get_option( "vestorfilter_{$key}_options" ) ?: array() );

?>
<div class="option-toggles" data-for="<?php echo esc_attr( $input_prefix ); ?><?php echo esc_attr( $key ); ?>">

<?php if ( empty( $filter['default'] ) ) : ?>
<div class="pill-toggle">
	<input data-filter-value="" data-key="<?php echo esc_attr( $key ); ?>" class="pill-toggle__input" type="radio" name="<?php echo sanitize_title( $key ); ?>" value="" 
		id="vf-filter-option__<?php echo esc_attr( $key ); ?>--clear">
	<label class="pill-toggle__label clear" for="vf-filter-option__<?php echo esc_attr( $key ); ?>--clear">(Clear)</label>
</div>
<?php endif; ?>

<?php foreach ( $filter['options'] as $optkey => $option ) : ?>
	<?php 

	if ( is_object( $option ) ) {
		$optkey = $option->value;
		$optval = $option->value;
	} else {
		$optval = $option;
	}

	if ( ! empty( $available ) && ! in_array( $optval, $available ) ) {
		continue;
	}

	?>
	<div class="pill-toggle">
		<input data-filter-value="<?php echo esc_attr( $key ) ?>" data-key="<?php echo esc_attr( $key ); ?>" class="pill-toggle__input" type="radio" name="<?php echo sanitize_title( $key ); ?>" value="<?php echo esc_attr( $optkey ); ?>" 
		id="vf-filter-option__<?php echo esc_attr( $key ); ?>--<?php echo sanitize_title( $optkey ); ?>" <?php checked( $optkey, $value ) ?>>
		<label class="pill-toggle__label" for="vf-filter-option__<?php echo esc_attr( $key ); ?>--<?php echo sanitize_title( $optkey ); ?>"><?php echo esc_html( $optval ) ?></label>
	</div>
<?php endforeach; ?>

</div>