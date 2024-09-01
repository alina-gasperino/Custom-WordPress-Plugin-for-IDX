<?php

use VestorFilter\Property as Property;

$property_type = $property->get_index( 'property-type' );

$active_class = ! empty( $is_active ) ? 'active' : '';

$logo = $property->get_source()->get_compliance_logo();
$line = $property->get_source()->get_compliance_line( $property->get_office_name() );
$source = $property->get_source()->slug();

$has_compliance = ! empty( $logo ) || ! empty( $line );

$thumbnail = $property->get_thumbnail_url();
$tiny = $property->get_thumbnail_datauri();

if ( ! empty( $agent ) ) {
	$active_class .= ' is-recommeded';
}

$hidden = $property->get_data( 'hidden' );
if ( $hidden ) {
	$active_class .= ' is-unavailable';
}

if ( empty( $user ) ) {
	$user = null;
}

?>
<blockquote data-mlsid="<?= esc_attr( $property->MLSID() ); ?>" cite="<?php echo esc_url( $property->get_page_url() ?: '#' ) ?>" id="property--<?php echo $property->ID(); ?>" data-property-id="<?php echo $property->ID(); ?>" class="vf-property vf-panel <?php echo $active_class; ?>" itemscope itemtype="https://schema.org/Place">

	<?php if ( $property->has_photos() && ! $hidden ): ?>
	<figure data-gallery-photo class="vf-property__image">

		<a class="vf-property__image--link" href="<?php echo $property->get_page_url() ?>" <?php 
			if ( $thumbnail !== $tiny ) echo 'data-blurp-replace="' . $thumbnail . '"' ?>><img itemprop="photo" src="<?php echo $tiny ?>" alt="Photo of <?php echo $property->get_prop( 'address_line1' ); ?>" /></a>

		<?php if ( strtolower( $property->get_prop( 'status' ) ) === 'active' ) : ?>

		<button class="vf-property__image-toggle" data-gallery-toggle>
			<span><?php echo esc_html( $property->get_photo_count() ) ?> Photos Available</span>
		</button>

		<?php endif; ?>

	</figure>
	<?php else: ?>
	<figure class="vf-property__image placeholder"></figure>
	<?php endif; ?>

	<div class="vf-property__meta<?= $has_compliance ? ' with-compliance' : '' ?>">

		<?php if ( ! empty( $agent ) ) : ?>
		<span class="vf-property__meta--recommendation">Recommended By Your Agent</span>
		<?php elseif ( ! empty( $friend ) ) : ?>
		<span class="vf-property__meta--recommendation">Recommended By <?= $friend->display_name ?></span>
		<?php endif; ?>

		<?php if ( ! $hidden ) : ?>
		<a class="address" href="<?php echo $property->get_page_url() ?: '#' ?>">
		<?php endif; ?>
		
		<?php echo $property->get_address_html(); ?>
		
		<?php if ( ! $hidden ) : ?>
		</a>
		<?php endif; ?>

		<?php if ( ! $hidden ) : ?>
		<span class="meta">

			<?php $bathrooms = $property->get_prop( 'bathrooms' ); ?>

			<?php if ( in_array( 'land', $property_type ) ) : ?>

				<?php if ( ( $size = $property->get_prop( 'lot' ) ) > 0 ) : ?>
				<span class="lot"><?php echo esc_html( round( $size, 2 ) ) ?> acres</span>
				<?php endif; if ( $size = $property->get_prop( 'lot_est' ) ) : ?>
				<span class="no-icon"><?php echo esc_html( $size ) ?></span>
				<?php endif; ?>

				<?php if ( $zoning = $property->get_prop( 'zoning' ) ) : ?>
				<span class="zoning"><?php echo esc_html( $zoning ) ?></span>
				<?php endif; ?>

			<?php elseif ( in_array( 'commercial', $property_type ) ) : ?>

				<?php if ( ( $size = $property->get_prop( 'lot' ) ) > 0 ) : ?>
				<span class="lot"><?php echo esc_html( round( $size, 2 ) ) ?> acres</span>
				<?php elseif ( $size = $property->get_prop( 'lot_est' ) ) : ?>
				<span class="no-icon"><?php echo esc_html( $size ) ?></span>
				<?php endif; ?>

				<?php if ( $zoning = $property->get_prop( 'zoning' ) ) : ?>
				<span class="zoning"><?php echo esc_html( $zoning ) ?></span>
				<?php endif; ?>

				<?php if ( $sqft = $property->get_prop( 'sqft_gross' ) ) : ?>
				<span class="sqft"><?php echo $property->get_prop( 'sqft_gross' ); ?> <?php esc_html_e( 'ft&sup2;', 'vestorfilter' ); ?></span>
				<?php endif; ?>

			<?php elseif ( in_array( 'mf', $property_type ) ) : ?>

				<?php if ( $units = $property->get_prop( 'units' ) ) : ?>
				<span class="units"><?php echo $property->get_prop( 'units' ); ?> <?php esc_html_e( 'units', 'vestorfilter' ); ?></span>
				<?php endif; ?>

				<?php if ( $beds = $property->get_prop( 'bedrooms_mf' ) ) : ?>
				<span class="bedrooms"><?php echo esc_html( $beds ) ?> <?php esc_html_e( 'bed', 'vestorfilter' ); ?></span>
				<?php endif; ?>

				<?php if ( $sqft = $property->get_prop( 'sqft_mf' ) ) : ?>
				<span class="sqft"><?php echo esc_html( $sqft ) ?> <?php esc_html_e( 'ft&sup2;', 'vestorfilter' ); ?></span>
				<?php endif; ?>

			<?php elseif ( array_intersect( [ 'condos', 'sf', '55' ], $property_type ) ) : ?>

				<?php if ( $beds = $property->get_prop( 'bedrooms' ) ) : ?>
				<span class="bedrooms"><?php echo $beds ?> <?php esc_html_e( 'bed', 'vestorfilter' ); ?></span>
				<?php endif; ?>
				<?php if ( $bathrooms ) : ?>
				<span class="bathrooms"><?php echo Property::format_meta( $bathrooms, 'bathrooms' ); ?> <?php esc_html_e( 'bath', 'vestorfilter' ); ?></span>
				<?php endif; ?>
				<?php if ( $sqft = $property->get_prop( 'sqft' ) ) : ?>
				<span class="sqft"><?php echo $sqft ?> <?php esc_html_e( 'ft&sup2;', 'vestorfilter' ); ?></span>
				<?php endif; ?>

			<?php endif; ?>

		</span>
		
		<?php if ( $has_compliance ) : ?>
		<figure class="vf-property__meta--compliance <?=$source?>">
			<?php echo $logo; ?>
			<?php if ( $line ) : ?>
				<figcaption><?php echo $line; ?></figcaption>
			<?php endif; ?>
		</figure>
		<?php endif; ?>

		<?php else: ?>
		<span class="vf-property__meta--unavailable">No longer available</span>
		<?php endif; ?>

	</div>

	<?php if ( ! $hidden ) : ?>
	<div class="vf-property__vitals datatable">

		<?php 
		
		\VestorFilter\Util\Template::get_part( 
			'vestorfilter', 
			'panels/vitals', 
			[ 
				'header'   => '<h3><a class="address" href="' . esc_url( $property->get_page_url() ?: '#' ) . '">' . $property->get_address_html() . '</a></h3>',
				'property' => $property,
				'icons'    => false,
				'link'     => true,
			] 
		); 
		
		$open_house = $property->get_next_openhouse();

		$tour_1   = $property->get_meta( 'tour_1' );
		$tour_2 = $property->get_meta( 'tour_2' );

		?>

		<?php if ( ! empty( $open_house ) ) : ?>
			<div class="datatable__row key-oh label-above">
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
			<?php echo \VestorFilter\Util\Icons::inline( strpos( 'youtube', $tour_1 ) !== false || strpos( 'youtu.be', $tour_1 ) !== false || strpos( 'vimeo', $tour_1 ) ? 'data-video' : 'data-tour' ); ?>
			<span><?php esc_html_e( 'Tour', 'vestorfilters' ); ?><?php if ( ! empty( $tour_2 ) ) echo ' #1'; ?></span>
		</a>
		<?php endif; if ( ! empty( $tour_2 ) ) : ?>
		<a href="<?php echo esc_url( $tour_2 ); ?>" class="btn datatable__half has-icon btn-secondary" target="_blank">
			<?php echo \VestorFilter\Util\Icons::inline( strpos( 'youtube', $tour_2 ) !== false || strpos( 'youtu.be', $tour_2 ) !== false || strpos( 'vimeo', $tour_2 ) ? 'data-video' : 'data-tour' ); ?>
			<span><?php esc_html_e( 'Tour #2', 'vestorfilters' ); ?></span>
		</a>
		<?php endif; ?>
	
	</div>
	<?php endif; ?>

	<div class="vf-property__flags">

		<?php if ( ! $hidden ) : ?>
		<span class="price"><?php echo $property->get_price( 'html' ); ?></span>

		<?php if ( $property->show_vestorfilter() && $value = $property->get_vestorfilter( $vf ) ) : if ( $value !== 'Yes' ) : ?>

		<span class="vf vf--<?= $vf ?>"><?php echo $value; ?></span>

		<?php endif; endif; ?>

		<?php endif; ?>

		<?php if ( ! empty( $friend ) ) : ?>

			<?php if ( $friend->ID === get_current_user_id() || $user === get_current_user_id() ) : ?>

			<button data-favorite-user="<?= $user ?>" data-favorite-remove="<?php echo $property->ID(); ?>" class="hide-if-no-js vf-property__flags--remove vf-property__remove">
				<span class="screen-reader-text"><?php esc_html_e( 'Remove this property from favorites', 'vestorfilter' ); ?></span>
			</button>

			<?php endif; ?>

		<?php else : ?>

		<button data-vestor-favorite="<?php echo $property->ID(); ?>" class="hide-if-no-js vf-property__flags--save vf-property__save<?php echo $property->is_favorite() ? ' is-favorite' : ''; ?>">
			<span class="screen-reader-text"><?php esc_html_e( 'Favorite this property', 'vestorfilter' ); ?></span>
		</button>

		<?php endif; ?>

	</div>

	<?php if ( ! $hidden ) : ?>

	<?php // echo $property->get_thumbnail( 'tiny', [ 'class' => 'background-image' ] ); ?>

	<a href="<?php echo $property->get_page_url() ?: '#' ?>" class="btn vf-property__expand" data-show-property><span>Show More</span></a>

	<?php endif; ?>

</blockquote>
