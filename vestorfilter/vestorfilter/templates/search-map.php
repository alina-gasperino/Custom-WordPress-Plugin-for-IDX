<?php

use VestorFilter\Location;

use VestorFilter\Property;
use VestorFilter\Search as VestorSearch;
use VestorFilter\Filters as Filters;
use VestorFilter\Settings as Settings;

use \VestorFilter\Util\Icons as Icons;
use \VestorFilter\Util\Math;

$total = $data ? count( $data ) : 0;
$content = [];

$mode = Settings::get( 'default_results_view' ) === 'list' ? 'list' : 'map';

$current_locations = VestorSearch::get_filter_value( 'location' ) ?: [];
if ( ! is_array( $current_locations ) ) {
	$current_locations = explode( ',', $current_locations );
}

//if ( empty( $data ) && !current_user_can('administrator')) {
//	$mode = 'list';
//}
if ( isset( $_GET['mode'] ) ) {
	$mode = ( $_GET['mode'] === 'list' ? 'list' : 'map' );
}

$minmax = VestorSearch::get_geo();

$center = VestorSearch::get_center();
$zoom   = VestorSearch::get_zoom();

$vf = VestorSearch::get_vf();

$bounds = [
	'ne' => Location::get_ne_bounds(),
	'sw' => Location::get_sw_bounds(),
];


$lot_options = Settings::get( 'filters_lot' );
$lot_values  = [];
foreach( $lot_options as $option ) {
	if ( isset( $option['terms'] ) ) {
		$options = $option['terms'];
	} else {
		$options = $option;
	}
	foreach( $options as $term ) {
		$lot_values[ $term ] = $option['label'];
	}
}

$force_geo = $_GET['g'] ?? null;
if ( ! empty( $force_geo ) ) {
	$force_geo = Math::conv_base( $force_geo, '0123456789,-', false );
	$force_geo = explode( ',', $force_geo );
	if ( count( $force_geo ) === 3 ) {
		$geo = [
			'center' => [ 
				'lat' => Location::geo_to_float( (int) $force_geo[1] * 100 ), 
				'lng' => Location::geo_to_float( (int) $force_geo[2] * 100 ) 
			],
			'zoom' => (int) $force_geo[0],
		];
	}
}



?>

<div class="vf-search__map is-<?=$mode?>-view <?php //empty( $data ) && !current_user_can('administrator') ? 'no-results' : '' ?>">
	
	<div class="vf-search__map-inside" data-results-wrapper>
		<div class="vf-search__map-wrapper" id="gmap-interface" data-results-map></div>
		<div class="vf-search__map-key" data-results-key></div>

		<div class="vf-search__map-custom-selector">
			<button type="button" aria-controls="user-maps-selector" aria-expanded="false" class="btn vf-search__map-draw-mode-btn">
				<svg class="vf-use-icon vf-use-icon--data-polygon"><use xlink:href="#action-draw"></use></svg>
			</button>
			<button type="button" data-custom-map="save" class="btn vf-search__map-save-btn">
				<svg class="vf-use-icon vf-use-icon--data-save"><use xlink:href="#data-save"></use></svg>
				<span class="screen-reader-text"><?php esc_html_e( 'Save Map', 'vestorfilter' ); ?></span>
			</button>
			<button type="button" data-custom-map="cancel" class="btn vf-search__map-cancel-btn">
				<svg class="vf-use-icon vf-use-icon--data-close"><use xlink:href="#action-close"></use></svg>
				<span class="screen-reader-text"><?php esc_html_e( 'Cancel', 'vestorfilter' ); ?></span>
			</button>
			

			<div class="user-maps-selector" id="user-maps-selector" aria-hidden="true">
			<?php if ( is_user_logged_in() ) : foreach( Location::get_user_maps() as $user_map ) : ?>
				<?php $selected = in_array( $user_map['id'], $current_locations ) ? ' is-selected' : ''; ?>
				<button type="button" data-custom-map="<?= esc_attr( $user_map['id'] ) ?>" data-filters="<?= esc_attr( json_encode( $user_map['filters'] ) ) ?>" class="user-maps-selector__list-item<?= $selected ?>">
					<?php echo esc_html( $user_map['name'] ); ?>
				</button>
			<?php endforeach; endif; ?>
			<button type="button" data-custom-map="new" class="user-maps-selector__list-item user-maps-selector__list-item--new">
				<?php esc_html_e( 'Create New Custom Map', 'vestorfilter' ); ?>
			</button>
			</div>
		</div>

		<button type="button" data-custom-map="edit" class="btn vf-search__map-edit-btn">
			<svg class="vf-use-icon vf-use-icon--data-edit"><use xlink:href="#data-polygon"></use></svg>
			<span class="screen-reader-text"><?php esc_html_e( 'Edit', 'vestorfilter' ); ?></span>
		</button>
		<button type="button" data-custom-map="delete" class="btn vf-search__map-delete-btn">
			<svg class="vf-use-icon vf-use-icon--data-trash"><use xlink:href="#data-trash"></use></svg>
			<span class="screen-reader-text"><?php esc_html_e( 'Delete', 'vestorfilter' ); ?></span>
		</button>

		<button class="btn btn-toggle btn-primary vf-search__map-location-reset" data-location-action="close" type="button">
		<?= Icons::use( 'action-reset' ); ?>
			<span class="screen-reader-text">Reset</span>
		</button>
		<button title="Subscribe to this map" class="btn btn-toggle btn-primary vf-search__map-location-subscribe" data-location-action="subscribe" type="button" class="btn btn-primary btn-save has-tooltip" tabindex="-1">
			<?php echo Icons::use( 'action-subscribe' ); ?>
			<span class="screen-reader-text">Save this Search</span>
		</button>

		<?php if ( current_user_can( 'manage_vf_options' ) ) : ?>
		<div class="vf-search__map-share-dialog">
			<button title="Share Map" class="btn btn-toggle btn-primary vf-search__map-share-btn" aria-expanded="false" aria-controls="map-share-dialog" type="button" class="btn btn-primary btn-share has-tooltip" tabindex="-1">
				<?php echo Icons::use( 'action-share' ); ?>
				<span class="screen-reader-text">Share this Search</span>
			</button>
			<div class="vf-search__map-share-dialog-inside" id="map-share-dialog" aria-hidden="true"></div>
		</div>
		<?php endif; ?>

		<?php if ( isset( $_GET['debug'] ) ) : ?>
		<div id="map-debug-panel"></div>
		<?php endif; ?>
	</div>
	<!--script src="https://unpkg.com/@googlemaps/markerclusterer/dist/index.min.js"></script-->
	<script>
    <?php //if ( ! empty( $data ) || current_user_can('administrator') ) : ?>
		<?php if ( ! empty( VestorSearch::map_hash() ) ) : ?>
			vestorResultsHash = '<?= VestorSearch::map_hash() ?>';
		<?php else : ?>
		vestorResultsHash = null;
		<?php endif; ?>
		var vfBlockTemplate = `<?= Property::get_cache_template() ?>`;
		var vfMapBounds = <?= json_encode( $bounds ) ?>;
		var initialVestorMapPins = [];
		var vfLotSizes = <?= json_encode( $lot_values ) ?>;
			<?php /*
			$minmax = [ 
				[ 'lat' => Location::float_to_geo( 90 ), 'lon' => Location::float_to_geo( 180 ) ],
				[ 'lat' => Location::float_to_geo( -90 ), 'lon' => Location::float_to_geo( -180 ) ] 
			];
			$count = 0;
			foreach ( $data as $property ) {
				if ( $count > 20 ) {
					break;
				}
				if ( $count < 20 ) {
					if ( $count > 0 ) {
						echo ',';
					}
					$content[] = (array) $property;
					unset( $property->block_cache );

					echo json_encode( $property );
					
				}
				$count ++;
			}
			?>
		];
		<?php */
		if ( ! empty( $minmax ) ) {
			foreach( $minmax as $i => $coord ) {
				$minmax[$i] = [
					'lat' => Location::geo_to_float( $coord[0] ),
					'lng' => Location::geo_to_float( $coord[1] ),
				];
			}
		}
		?>
		var totalProperties = <?= VestorSearch::total_results() ?>;
		<?php if ( ! empty( $minmax ) ) : ?>
		var initialMapRect = <?= json_encode( $minmax ) ?>;
		<?php else : ?>
		var initialMapRect = null;
		<?php endif; ?>
		//var initialVestorMapData = <?php //json_encode( $map_polys ) ?>;
		var highlightedMaps = <?= json_encode( $maps ) ?>;
		<?php if ( ! empty( $center ) ) : ?>
		var initialMapCenter = {
			'center': {
				'lat': <?= Location::geo_to_float( $center[0] ) ?>,
				'lng': <?= Location::geo_to_float( $center[1] ) ?>
			}<?php if ( $zoom ) : ?>,
			'zoom': <?= $zoom ?>
			<?php endif; ?>
		};
		<?php endif; ?>
		<?php if ( ! empty( $geo ) ) : ?>
		var sharedGeo = <?= json_encode( $geo ) ?>;
		<?php endif; ?>
		document.body.dataset.mapReady = 'true';

		let vfIsListMode = <?= $mode === 'list' ? 'true' : 'false' ?>
		<?php if ( ! empty( $error ) ) { ?>
			document.addEventListener( 'vestorfilters|messages-ready', () => {
				vestorMessages.show( `<?= esc_attr( $error ) ?>`, 'error' );
			} );
		<?php } ?>

		//console.log( initialVestorMapData, rect );
    <?php //endif; ?>
	</script>
	<?php /*
	<div class="vf-search__map--icons" style="display:none">
		<span class="vf-search-map__icon" data-property-type="sf"><?php echo Icons::inline( 'pin-sf' ) ?></span>
		<span class="vf-search-map__icon" data-property-type="mf"><?php echo Icons::inline( 'pin-mf' ) ?></span>
		<span class="vf-search-map__icon" data-property-type="commercial"><?php echo Icons::inline( 'pin-commercial' ) ?></span>
		<span class="vf-search-map__icon" data-property-type="land"><?php echo Icons::inline( 'pin-land' ) ?></span>
		<span class="vf-search-map__icon" data-property-type="other"><?php echo Icons::inline( 'pin-other' ) ?></span>
		<span class="vf-search-map__icon" data-property-type="selected"><?php echo Icons::inline( 'pin-selected' ) ?></span>
	</div>
	*/ ?>
	<div class="vf-search__map-results">
		<ul class="map-search-results">
			<?php //if ( empty( $data ) && !current_user_can('administrator') ) : ?>
<!--				<li class="map-search-results__item map-search-results__item--empty">-->
<!--					<p>No properties could be found within your specified search paramaters.</p>-->
<!--				</li>-->
			<?php if ( $mode === 'list' ) : ?>
				<?php 
				
				while ( $loop->has_properties() ) :
					$property = $loop->current_property();
					$template = Property::get_cache_html( 'block-classic', $property );
					if ( ! $template ) {
						continue;
					}

				?>
				<li class="map-search-results__item">
					<?php echo $template; ?>
				</li>
				<?php $loop->next(); endwhile; ?>
			<?php endif; ?>
		</ul>
	</div>
	<div id="property-panel" class="vf-search__map-property-panel" data-property-panel></div>
</div>
