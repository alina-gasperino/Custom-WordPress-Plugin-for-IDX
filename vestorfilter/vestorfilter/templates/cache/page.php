<?php
namespace VestorFilter; 
use VestorFilter\Util\Template as Template;
use VestorFilter\Cache as Cache;
use VestorFilter\Property as Property;
use VestorFilter\Filters as Filters;
use VestorFilter\Util;

$hidden = Cache::get_data_value( $property->ID(), 'hidden' );
if ( $hidden > 0 ) {
	return;
}

$property->load_all_vestorfilters();

//$compliance_logo       = $property->get_source()->get_compliance_logo();
$compliance_text       = $property->get_source()->get_compliance_line( $property->get_office_name() );
$compliance_source     = $property->get_source()->slug();
//$compliance_location   = false;
$compliance_photo_text = $property->get_source()->get_compliance_line_under_photo( $property->get_office_name() );


?>

<div class="property-template__primary">

	<div class="property-template__actions"></div>

	<section class="property-template__vitals datatable" id="vitals">

		<?php

		\VestorFilter\Util\Template::get_part(
			'vestorfilter',
			'cache/vitals',
			[
				'property' => $property,
				'header'   => '<h1>' . $property->get_address_html() . '</h1>',
			]
		);

		$tour_1   = $property->get_meta( 'tour_1' );
		$tour_2 = $property->get_meta( 'tour_2' );

		$open_house = $property->get_next_openhouse();

		?>

		<?php if ( ! empty( $open_house ) ) : ?>
			<div class="datatable__row">
				<span class="icon" aria-hidden="true"><?= Util\Icons::use('data-oh'); ?></span>
				<span class="label">Open House</span>
				<span class="value"><?php

					echo date("m/d/Y", strtotime( $open_house[0] ) )
						. '<br>'
						. date("h:i - ", strtotime( $open_house[0] ) )
						. date("h:i a", strtotime( $open_house[1] ) )

				?></span>
			</div>
		<?php endif; ?>
		<?php if ( ! empty( $tour_1 ) ) : ?>
		<a href="<?php echo esc_url( $tour_1 ); ?>" class="btn datatable__half has-icon btn-secondary" target="_blank">
			<?php echo strpos( 'youtube', $tour_1 ) !== false || strpos( 'youtu.be', $tour_1 ) !== false || strpos( 'vimeo', $tour_1 ) ? Util\Icons::use('data-video') : Util\Icons::use('data-tour') ?>
			<span><?php esc_html_e( 'Tour', 'vestorfilters' ); ?><?php if ( ! empty( $tour_2 ) ) echo ' #1'; ?></span>
		</a>
		<?php endif; if ( ! empty( $tour_2 ) ) : ?>
		<a href="<?php echo esc_url( $tour_2 ); ?>" class="btn datatable__half has-icon btn-secondary" target="_blank">
			<?php echo strpos( 'youtube', $tour_2 ) !== false || strpos( 'youtu.be', $tour_2 ) !== false || strpos( 'vimeo', $tour_2 ) ? Util\Icons::use('data-video') : Util\Icons::use('data-tour'); ?>
			<span><?php esc_html_e( 'Tour #2', 'vestorfilters' ); ?></span>
		</a>
		<?php endif; ?>
	</section>

	<section class="property-template__vestorfilter datatable" id="vestorfilters">

		<?php

			\VestorFilter\Util\Template::get_part(
				'vestorfilter',
				'cache/datatable',
				[
					'fields'     => [],
					'header'     => '<h2>VestorFilter&trade;</h2>',
					'property'   => $property,
				],
			);

		?>

		<?php foreach( Filters::get_all( true ) as $filter_key ) : ?>
			<?php if ( ! $property->can_show_vestorfilter( $filter_key ) ) continue; ?>
			<?php if ( $value = $property->get_vestorfilter( $filter_key ) ) : if ( $value === 'Yes' ) : ?>
			<div class="datatable__row" data-vestor-link="<?= $filter_key ?>">
				<span class="label"><?php echo Filters::get_filter_name_singular( $filter_key ) ?></span>
				<span class="value"><?php echo $value; ?></span>
			</div>
			<?php elseif ( $value !== 'No' && ! empty( $value ) && $filter_key !== 'lotm' ) : ?>
			<div class="datatable__row" data-vestor-filter="<?= $filter_key ?>">
				<span class="label"><?php echo Filters::get_filter_name_singular( $filter_key ) ?></span>
				<span class="value"><?php echo $filter_key === 'lotm' ? '{{ data:onmarket[dom] }}' : $value ?></span>
			</div>
		<?php endif; endif; endforeach; ?>

	</section>

	<div class="property-template__agent-contact"></div>

</div>

<?php if ( $property->has_photos() ) : ?>

<section class="property-template__gallery" id="gallery">

	<?php $photos = $property->get_photos(); ?>
	<figure class="property-template__gallery--photo" data-gallery-photo>
		<img src="{{ property:photo[0] }}" alt="<?php echo esc_attr( $photos[0]->alt ); ?>">

		<?php if ( strtolower( $property->get_prop( 'status' ) ) === 'active' ) : ?>
		<button class="property-template__gallery--more-photos" data-gallery-toggle>
			<span class="label">
				<?= Util\Icons::use('data-photo') ?>
				<span><?php echo esc_html( count( $photos ) ) ?> Photos Available</span>
			</span>
			<?php if ( ! empty( $compliance_photo_text ) ) : ?>
				<span class="compliance-text <?=$compliance_source?>">
					<span><?php echo $compliance_photo_text; ?></span>
				</span>
			<?php endif; ?>
		</button>
		<?php endif; ?>
	</figure>

	<div class="property-template__gallery--thumbnails" role="Photo gallery" data-gallery-thumbnails="<?= count( $photos ) ?>"></div>

</section>

<?php endif; ?>

<div class="property-template__secondary">

	<div class="column-right">

		<section class="datatable property-template__description" id="financials">

			<header><h2>Property Description</h2></header>

			<p><?php echo $property->get_meta( 'description' ); ?></p>

			<?php if ( ! empty( $compliance_text ) ) : ?>
			<p class="compliance-text <?=$compliance_source?>">
				{{ compliance-logo }}
				<?php if ( $compliance_text ) : ?>
					<span><?php echo $compliance_text; ?></span>
				<?php endif; ?>
			</p>
			<?php endif; ?>

			<h3 class="datatable__subheader">Financial Data</h3>

			<?php

				\VestorFilter\Util\Template::get_part(
					'vestorfilter',
					'cache/datatable',
					[
						'fields'     => [
							'sold',
							'sold_price',
							'price_drop',
							'price_original',
							'price_range',
							'taxes',
							'hoa',
						],
						'property'   => $property,
						'with_label' => true,
					],
				);

			?>

		</section>

		<?php if ( defined( 'GOOGLE_MAP_KEY' ) && $property->get_prop( 'address_yn' ) ) : ?>

		<section class="datatable property-template__map" id="map">

			<header><h2>Find On Map</h2></header>

			<div class="map" data-vestor-map="<?php echo esc_attr( $property->get_address_string( true ) ); ?>"></div>

		</section>

		<?php endif; ?>

	</div>

	<div class="column-left">

		<section class="datatable property-template__facts" id="facts">

			<?php

			\VestorFilter\Util\Template::get_part(
				'vestorfilter',
				'cache/datatable',
				[
					'header'     => '<h3>Home Facts</h3>',
					'fields'     => [
						'status',
						'listing_id',
						'year_built',
						'stories',
						'green',
						'hes',
						'Interior Features' => [
							'icon' => 'data-int_features',
							'int_features',
							'Kitchen' => 'kitchen',
							'Fireplace Type' => 'fireplace'
						],
						'Exterior Features' => [
							'icon' => 'data-ext_features',
							'ext_features',
							'ext_desc',
						],
						'Garage' => [
							'icon' => 'data-garage',
							'Type' => 'garage',
							'Spaces' => 'garage_spaces',
						],
					],
					'property'   => $property,
					'with_label' => true,
				],
			);

			?>

			<div class="datatable__row type-string key-mlsid">
				<span class="icon" aria-hidden="true"><?php echo use_icon('data-mlsid'); ?></span>
				<span class="label">MLS ID</span>
				<span class="value"><?php echo $property->MLSID(); ?></span>
			</div>

		</section>

		<?php

		$units = $property->get_meta( 'units' );
		$types = $property->get_meta( 'unit_types', false );

		if ( ! empty( $types ) && is_array( $types ) ) {

			$name = $property->get_source()->get_meta( 'field_unit_name' );
			$beds = $property->get_source()->get_meta( 'field_unit_bathrooms' );
			$bath = $property->get_source()->get_meta( 'field_unit_bedrooms' );
			$sqft = $property->get_source()->get_meta( 'field_unit_sqft' );
			$feat = $property->get_source()->get_meta( 'field_unit_features' );
			$rent = $property->get_source()->get_meta( 'field_unit_rent' );

			$count = 0;

			?>

			<section class="datatable property-template__units" id="units">

				<header><h2>Unit Details</h2></header>

				<?php foreach ( $types as $type ) : $count ++; $unit = json_decode( $type, true ); ?>

					<div class="datatable__row type-multifield">
						<span class="label">Unit <?= $name && ! empty( $unit[ $name ] ) ? $unit[ $name ] : $count ?></span>
						<button class="btn with-icon with-icon__down-caret accordion-toggle" aria-expanded="false" aria-controls="datatable__unit-<?= $count ?>-values"><span class="sr-only">Expand</span></button>
						<div class="accordion collapse" aria-hidden="true" id="datatable__unit-<?= $count ?>-values">
							<?php if ( $beds && ! empty( $unit[ $beds ] ) ) : ?>
							<div class="datatable__subrow type-int">
								<span aria-hidden="true" class="icon"><?= Util\Icons::use('data-bedrooms'); ?></span>
								<span class="value"><?= $unit[ $beds ] ?> bedrooms</span>
							</div>
							<?php endif; ?>
							<?php if ( $bath && ! empty( $unit[ $bath ] ) ) : ?>
							<div class="datatable__subrow type-float">
								<span aria-hidden="true" class="icon"><?= Util\Icons::use('data-bathrooms'); ?></span>
								<span class="value"><?= $unit[ $bath ] ?> bathrooms</span>
							</div>
							<?php endif; ?>
							<?php if ( $sqft && ! empty( $unit[ $sqft ] ) ) : ?>
							<div class="datatable__subrow type-int">
								<span aria-hidden="true" class="icon"><?= Util\Icons::use('data-sqft'); ?></span>
								<span class="value"><?= $unit[ $sqft ] ?> sq. ft</span>
							</div>
							<?php endif; ?>
							<?php if ( $rent && ! empty( $unit[ $rent ] ) ) : ?>
							<div class="datatable__subrow type-currency">
								<span aria-hidden="true" class="icon"><?= Util\Icons::use('data-rent'); ?></span>
								<span class="value">Rent: $<?= $unit[ $rent ] ?></span>
							</div>
							<?php endif; ?>
						</div>
					</div>

				<?php endforeach; ?>

			</section>

		<?php } elseif ( is_numeric( $units ) && $units > 0 && in_array( 'mf', $property->get_index( 'property-type' ) ) ) {

			ob_start();

			for( $unit = 1; $unit <= $units; $unit ++ ) {

				\VestorFilter\Util\Template::get_part(
					'vestorfilter',
					'cache/datatable',
					[
						'fields'   => [
							'Unit ' . $unit => [
								'unit_bedrooms',
								'unit_bathrooms',
								'unit_sqft',
								'unit_rent',
								'unit_features',
							]
						],
						//'header'   => '<h3>Unit ' . $unit . '</h3>',
						'property' => $property,
						'unit'     => $unit,
					],
				);

			}

			$output = trim( ob_get_clean() );

			if ( ! empty( $output ) ) : ?>

				<section class="datatable property-template__units" id="units">

				<header><h2>Unit Details</h2></header>

				<?php echo $output; ?>

				</section>

			<?php endif;

		}

		?>

	</div>

</div>