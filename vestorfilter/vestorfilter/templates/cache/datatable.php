<?php
namespace VestorFilter; 

use \VestorFilter\Util\Icons as Icons;
?>
<?php if ( ! empty( $header ) ) : ?>
<header><?php echo $header; ?></header>
<?php endif; ?>

<?php

$icons_ok = isset( $icons ) && $icons === false ? false : true;
$unit_no  = $unit ?? false;

if ( ! empty( $property ) ) {
	$indentifier = '--' . $property->ID();
} else {
	$indentifier = '';
}

foreach ( $fields as $label => $field ) {

	if ( is_array( $field ) ) {

		$has_output = false;
		ob_start();

		$key = sanitize_title( $label );

		?>

	<div class="datatable__row type-multifield">
		<?php if ( isset( $field['icon'] ) && $icons_ok ) : ?>
		<span aria-hidden="true" class="icon"><?php echo use_icon($field['icon']);?></span>
		<?php endif; ?>
		<span class="label"><?php echo esc_html( apply_filters( 'vestorfilter_data_label__' . $label, $label ) ); ?></span>
		<button class="btn with-icon with-icon__down-caret accordion-toggle" aria-expanded="false" aria-controls="datatable__<?php echo $key ?>-values<?= $indentifier ?>"><span class='sr-only'>Expand</span></button>
		<div class="accordion collapse" aria-hidden="true" id="datatable__<?php echo $key ?>-values<?= $indentifier ?>">
		<?php

		foreach ( $field as $l => $f ) {

			if ( $l === 'icon' || $f === 'icon' ) {
				continue;
			}

			$template = \VestorFilter\Property::get_field_template( $f );
			if ( empty( $template ) ) {
				continue;
			}
			$icon = $template['icon'];

			$value = $property->get_formatted_meta( $f, $unit_no );

			if ( empty( $value ) ) {
				continue;
			}

			$has_output = true;

			?>
			<div class="datatable__subrow type-<?php echo $template['type'] ?>">
			<?php if ( $icons_ok && $icon !== ( $field['icon'] ?? null ) ): ?>
			<span aria-hidden="true" class="icon"><!--{{ icon:<?=$icon?> }}--></span>
			<?php endif; ?>
			<?php

			if ( $template['type'] === 'multi' ) {

				$values = explode( ',', $value );

				?>

				<?php if ( ! is_numeric( $l ) ) : ?>
				<span class="label"><?php echo esc_html( apply_filters( 'vestorfilter_data_label__' . $f, $l ) ); ?></span>
				<?php endif; ?>

				<ul>
					<?php  ?>
					<?php foreach ( $values as $value ) : ?>
					<li><?php echo trim( $value ) ?></li>
					<?php endforeach; ?>
				</ul>

				<?php

			} else { ?>

				<?php if ( ! is_numeric( $l ) ) : ?>
				<span class="label"><?php echo esc_html( apply_filters( 'vestorfilter_data_label__' . $f, $l ) ); ?></span>
				<?php endif; ?>

				<span class="value"><?php echo  $value; ?></span>

			<?php

			}

			echo '</div>';
		}

		?>
		</div>
	</div>

	<?php

		$output = ob_get_clean();

		if ( ! empty( $has_output ) ) {
			echo $output;
		}



	} else {
        if($field == 'cap') {
            if(!$property->is_type('mf')) {
                continue;
            }
        }
	$template = \VestorFilter\Property::get_field_template( $field );
	if ( empty( $template ) ) {
		continue;
	}
	$icon = $template['icon'];

	if($field == 'lot') {
	    $value = $property->get_formatted_meta( $field, $unit_no, true );
    } else {
        $value = $property->get_formatted_meta( $field, $unit_no );
    }
	if ( empty( $value ) || ( ! empty( $hide_empty ) && $value === 'No' ) ) {
		continue;
	}

	?>

	<div class="datatable__row type-<?php echo $template['type']; ?> key-<?php echo $field; ?>">
		<?php if ( $icons_ok ): ?>
		<span aria-hidden="true" class="icon"><?php echo use_icon($icon); ?></span>
		<?php endif; if ( ! empty( $with_label ) ): ?>
		<span class="label"><?php echo esc_html( apply_filters( 'vestorfilter_data_label__' . $field, $template['label'] ) ); ?></span>
		<?php endif; ?>
		<?php if ( $template['type'] === 'multi' ) : ?>
		<button class="btn with-icon with-icon__down-caret accordion-toggle" aria-expanded="false" aria-controls="datatable__<?php echo $field ?>-values<?= $indentifier ?>"><span class='sr-only'>Expand</span></button>
		<div class="accordion collapse" aria-hidden="true" id="datatable__<?php echo $field ?>-values<?= $indentifier ?>">
			<ul>
				<?php $values = explode( ',', $value ); ?>
				<?php foreach ( $values as $value ) : ?>
				<li><?php echo trim( $value ) ?></li>
				<?php endforeach; ?>
			</ul>
		</div>
		<?php else: ?>
		<span class="value"><?php echo $value; ?></span>
		<?php endif; ?>
	</div>
	<?php

	}

} // endforeach
?>
<script src="<?php echo plugin_dir_url( __DIR__ ); ?>/js/strings.js"></script>