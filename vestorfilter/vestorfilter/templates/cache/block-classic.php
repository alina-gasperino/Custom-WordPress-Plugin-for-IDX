<?php

use VestorFilter\Favorites;
use VestorFilter\Property as Property;

$property_type = $property->get_index( 'property-type' );

$logo = $property->get_source()->get_compliance_logo();
$line = $property->get_source()->get_compliance_line( $property->get_office_name() );

$has_compliance = ! empty( $logo ) || ! empty( $line );

$thumbnail = $property->get_thumbnail_url( false );
if(strpos($thumbnail, 'http') === false) {
    global $vfdb;
    $id = $property->ID();
    $thumbnail = $vfdb->get_results("SELECT `url` from `wp_propertycache_photo_rmlsreso` where `property_id` = '$id'")[0]->url;
}
//$tiny = $property->get_thumbnail_datauri();
if(is_user_logged_in() && current_user_can('use_dashboard')) {
    $admins = true;
}
$class = 'vf-property-block';
$hidden = $property->get_data( 'hidden' );
if ( $hidden > 0 ) {
	$class .= ' is-unavailable';
}
if ( $has_compliance ) {
	$class .= ' has-compliance';
}
foreach( $property_type as $type ) {
	$class .= ' property-type--' . $type;
}

if(!is_front_page()) {
    $user_slug = get_query_var('user_slug');
    if ($user_slug) {
        $user_id = Favorites::find_user_from_slug($user_slug);
    } else {
        $user_id = (is_user_logged_in() ? get_current_user_id() : 0);
    }
}
?>
<blockquote data-mlsid="<?= esc_attr( $property->MLSID() ); ?>" cite="{{ property:url }}" id="property--<?php echo $property->ID(); ?>" data-property-id="<?php echo $property->ID(); ?>" data-user-id="<?php echo $user_id; ?>" class="<?= esc_attr( $class ) ?>" itemscope itemtype="https://schema.org/Place">

	<?php if ( $property->has_photos() && ! $hidden && $thumbnail ): ?>
	<figure data-gallery-photo class="vf-property-block__image" data-photos="<?= esc_attr( $property->get_photo_count() ) ?>">
		<a class="vf-property-block__image--link" href="{{ property:url }}">
			<img data-src="{{ image:<?= urlencode( $thumbnail ) ?> }}" alt="Photo of <?= esc_attr( $property->get_prop( 'address_line1' ) ); ?>">
		</a>
	</figure>
	<?php else: ?>
	<figure class="vf-property-block__image placeholder"></figure>
	<?php endif; ?>

	<?php if ( $recommended ?? false ) : ?>
	<span class="vf-property-block__recommendation"><?php _e( 'Recommended by your Agent', 'vestorfilter' ) ?></span>
	<?php endif; ?>

	<?php if ( $friend_recommended ?? false ) : ?>
	<span class="vf-property-block__recommendation"><?php _e( 'Recommended by your friend', 'vestorfilter' ) ?></span>
	<?php endif; ?>

	<?php if ( ! $hidden ) : ?><a class="vf-property-block__address" href="{{ property:url }}"><?php endif; ?>

	<?php echo $property->get_address_html(); ?>

	<?php if ( ! $hidden ) : ?></a><?php endif; ?>

	<?php if ( ! $hidden ) : ?>

		<span class="vf-property-block__meta">

			<?php $bathrooms = $property->get_prop( 'bathrooms' ); ?>

			<?php if ( in_array( 'land', $property_type ) ) : ?>

				<?php if ( ( $size = $property->get_prop( 'lot' ) ) > 0 ) : ?>
				<span class="meta meta--lot"><?php echo esc_html( round( $size, 2 ) ) ?> acres</span>
				<?php endif; if ( $size = $property->get_prop( 'lot_est' ) ) : ?>
				<span class="meta meta--no-icon"><?php echo esc_html( $size ) ?></span>
				<?php endif; ?>

				<?php if ( $zoning = $property->get_prop( 'zoning' ) ) : ?>
				<span class="meta meta--zoning"><?php echo esc_html( $zoning ) ?></span>
				<?php endif; ?>

			<?php elseif ( in_array( 'commercial', $property_type ) ) : ?>

				<?php if ( ( $size = $property->get_prop( 'lot' ) ) > 0 ) : ?>
				<span class="meta meta--lot"><?php echo esc_html( round( $size, 2 ) ) ?> acres</span>
				<?php elseif ( $size = $property->get_prop( 'lot_est' ) ) : ?>
				<span class="meta meta--no-icon"><?php echo esc_html( $size ) ?></span>
				<?php endif; ?>

				<?php if ( $zoning = $property->get_prop( 'zoning' ) ) : ?>
				<span class="meta meta--zoning"><?php echo esc_html( $zoning ) ?></span>
				<?php endif; ?>

				<?php if ( $sqft = $property->get_prop( 'sqft_gross' ) ) : ?>
				<span class="meta meta--sqft"><?php echo $property->get_prop( 'sqft_gross' ); ?> <?php esc_html_e( 'ft&sup2;', 'vestorfilter' ); ?></span>
				<?php endif; ?>

			<?php elseif ( in_array( 'mf', $property_type ) ) : ?>

				<?php if ( $units = $property->get_prop( 'units' ) ) : ?>
				<span class="meta meta--units"><?php echo $property->get_prop( 'units' ); ?> <?php esc_html_e( 'units', 'vestorfilter' ); ?></span>
				<?php endif; ?>

				<?php if ( $beds = $property->get_prop( 'bedrooms_mf' ) ) : ?>
				<span class="meta meta--bedrooms"><?php echo esc_html( $beds ) ?> <?php esc_html_e( 'bed', 'vestorfilter' ); ?></span>
				<?php endif; ?>

				<?php if ( $sqft = $property->get_prop( 'sqft_mf' ) ) : ?>
				<span class="meta meta--sqft"><?php echo esc_html( $sqft ) ?> <?php esc_html_e( 'ft&sup2;', 'vestorfilter' ); ?></span>
				<?php endif; ?>

			<?php elseif ( array_intersect( [ 'condos', 'sf', '55' ], $property_type ) ) : ?>

				<?php if ( $beds = $property->get_prop( 'bedrooms' ) ) : ?>
				<span class="meta meta--bedrooms"><?php echo $beds ?> <?php esc_html_e( 'bed', 'vestorfilter' ); ?></span>
				<?php endif; ?>
				<?php if ( $bathrooms ) : ?>
				<span class="meta meta--bathrooms"><?php echo Property::format_meta( $bathrooms, 'bathrooms' ); ?> <?php esc_html_e( 'bath', 'vestorfilter' ); ?></span>
				<?php endif; ?>
				<?php if ( $sqft = $property->get_prop( 'sqft' ) ) : ?>
				<span class="meta meta--sqft"><?php echo $sqft ?> <?php esc_html_e( 'ft&sup2;', 'vestorfilter' ); ?></span>
				<?php endif; ?>

			<?php endif; ?>

		</span>

		<?php if ( $has_compliance ) : ?>
		<figure class="vf-property-block__compliance vf-property-block__meta--compliance">
			<?php echo $logo; ?>
			<?php if ( $line ) : ?>
				<figcaption><?php echo $line; ?></figcaption>
			<?php endif; ?>
		</figure>
		<?php endif; ?>
        <?php if(isset($admins) && $admins && !is_front_page()) { ?>
        <button <?php echo ($recommended ? 'data-agent="1"' : ''); ?> style="margin-top: 35px;" class="vf-property-block__flags--price remove-saved-<?= $property->ID();?>"><?php _e( 'Remove', 'vestorfilter' ) ?></button>
        <script>
            restAPI = '<?php echo wp_create_nonce('wp_rest'); ?>';
            jQuery(".remove-saved-<?php echo $property->ID(); ?>").on("click", function (e) {
                e.preventDefault();
                let propertyID = jQuery(this).closest("blockquote").data('property-id');
                let userID = jQuery(this).closest("blockquote").data('user-id');
                let _this = jQuery(this);
                let restore = jQuery(_this).data('restore');
                let data = {
                    'property' : propertyID,
                    'user' : userID,
                    '_wpnonce' : restAPI,
                    'restore' : restore,
                };
                jQuery.post("/wp-json/vestorfilter/v1/account/removeSaved", data, function (response) {
                    if(response.status === true) {
                        if(restore === undefined) {
                            jQuery(_this).html('Restore?');
                            jQuery(_this).attr("data-restore", "1");
                        } else if(restore == 0) {
                            jQuery(_this).html('Restore?');
                            jQuery(_this).data("restore", "1");
                        } else {
                            jQuery(_this).html('Remove');
                            jQuery(_this).data("restore", "0");
                        }
                    } else {
                        alert(response.message);
                    }
                });
                console.log(data);
            });
        </script>
    <?php } ?>
		<span class="vf-property-block__flags--price vf-property-block__price"><?php echo $property->get_price( 'html' ); ?></span>

		<!--{{ flags }}-->

	<?php else: // ! $hidden ?>
	<span class="vf-property-block__meta--unavailable">No longer available</span>
	<?php endif; ?>

</blockquote>
