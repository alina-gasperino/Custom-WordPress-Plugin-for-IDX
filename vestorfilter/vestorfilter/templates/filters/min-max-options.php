<?php

$values = explode( ':', $value );
$values['min'] = empty( $values[0] ) ? '' : absint( $values[0] );
$values['max'] = empty( $values[1] ) ? ''  : absint( $values[1] );

$minmax = $values['min'] . ':' . $values['max'];
if ( $minmax === ':' ) {
	$minmax = '';
}

if ( empty( $input_prefix ) ) {
	$input_prefix = 'vf-filter-toggle__';
}

$available = apply_filters( "vestorfilter_{$key}_available_options", get_option( "vestorfilter_{$key}_options" ) ?: array() );

?>
<div class="option-toggles" data-for="<?php echo esc_attr( $input_prefix ); ?>

	<?php echo esc_attr( $key ); ?>">

	<input data-filter-value="<?= esc_attr( $key ) ?>" 
		   data-key="<?php echo esc_attr( $key ); ?>"
		   class="pill-toggle__input"
		   type="hidden" 
		   name="<?php echo sanitize_title( $key ); ?>" 
		   value="<?php echo esc_attr( $minmax ); ?>"
		   data-value-min="<?= esc_attr( $values['min'] ); ?>"
		   data-value-max="<?= esc_attr( $values['max'] ); ?>"
		>
		

	<div class="min-max-toggles">
	<?php foreach( ['min','max'] as $range ) : ?>
		<span class="min-max-toggles__title"><?= ucwords( $range ) ?></span>
		<ul class="min-max-toggles__range min-max-toggles--<?= $key . '-' . $range ?>">
		<?php foreach ( $filter["$range-options"] as $value => $label ) : ?>
			<li class="min-max-option <?= ( $value == $values[$range]  ? 'active' : '' ) ?>">
				<input class="pill-toggle__input" type="radio" name="<?= sanitize_title( $key ) ?>_<?=$range?>" value="<?= esc_attr( $value ) ?>" 
				data-range-for="<?php echo esc_attr( $key ); ?>" 
				data-range-of="<?=$range?>"
                data-format="<?php echo $filter['format'] ?>"

				id="vf-filter-option__<?= esc_attr( $key . '-' . $range . '--' . $value ) ?>" <?php checked( $value, $values[$range] ) ?>>
				<label class="pill-toggle__label" for="vf-filter-option__<?= esc_attr( $key . '-' . $range . '--' . $value ) ?>"><?=  $label  ?></label>
			</li>
		<?php endforeach; ?>
			<li class="min-max-option clear">
				<input class="pill-toggle__input clear" type="radio" name="<?= sanitize_title( $key ) ?>_<?=$range?>" value="" 
				data-range-for="<?php echo esc_attr( $key ); ?>" 
				data-range-of="<?=$range?>"
				id="vf-filter-option__<?= esc_attr( $key . '-' . $range . '--clear' ) ?>">
				<label class="pill-toggle__label clear min-max-toggles__clear" for="vf-filter-option__<?= esc_attr( $key . '-' . $range . '--clear' ) ?>">(Clear)</label>
			</li>
		</ul>
		
	<?php endforeach; ?>
	</div>


</div>