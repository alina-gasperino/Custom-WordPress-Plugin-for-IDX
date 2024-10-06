const setupGoogleMap = ( thisMap ) => {

	vfDebug( 'setup map', thisMap );

	if ( ! thisMap ) {
		//vfDebug( 'whert', map );
		return;
	}

	if ( thisMap.dataset.installed === 'true' ) {
		//vfDebug( 'installed', map );
		return;
	}

	let iframe = document.createElement( 'iframe' );
	iframe.src = `https://www.google.com/maps/embed/v1/place?key=${apiTokens.google}&q=` + thisMap.dataset.vestorMap;
	iframe.src 
	iframe.width = '640';
	iframe.height = '480';

	//console.log( thisMap, iframe );

	thisMap.append( iframe );
	thisMap.dataset.installed = 'true';

};


const startupMaps = () => {

	let maps = document.querySelectorAll( '[data-vestor-map]' );
	for ( let map of maps ) {
		if ( map.dataset.vestorMap === 'widget' ) {
			return;
		}
		setupGoogleMap( map );
		break;
	}

	if ( document.body.dataset.mapReady ) {

		var script = document.createElement('script');
		script.src = `https://maps.googleapis.com/maps/api/js?key=${apiTokens.google}&callback=loadResultsMap&libraries=geometry,drawing`;
		script.async = true;

		document.head.appendChild(script);

	}

}

const setupMiniMaps = () => {

	let miniMaps = document.querySelector( '[data-vestor-map="widget"]' );
	if ( miniMaps ) {
		let script = document.createElement('script');
		script.src = `https://maps.googleapis.com/maps/api/js?key=${apiTokens.google}&callback=loadMiniMaps&libraries=geometry`;
		script.async = true;
		document.head.appendChild(script);
	}

};

//if ( document.readyState == 'complete' ) {
//	startupMaps();
//} else {
document.addEventListener( 'vestorfilters|search-init', startupMaps );
//}

if ( document.readyState == 'complete' ) {
	setupMiniMaps();
} else {
	document.addEventListener( 'DOMContentLoaded', setupMiniMaps );
}

const vestorMiniMaps = ( function() {

	window.loadMiniMaps = function() {

		let miniMaps = document.querySelectorAll( '[data-vestor-map="widget"]' );
		if ( miniMaps.length === 0 ) {
			return;
		}

		for( let map of miniMaps ) {

			let params = map.querySelector('script');
			if ( ! params ) {
				vfDebug( 'could not find minimap attributes' );
				map.classList.add( 'is-error' );
				continue;
			}
			params = JSON.parse( params.innerHTML );
			if ( ! params ) {
				vfDebug( 'could not parse minimap attributes' );
				map.classList.add( 'is-error' );
				continue;
			}

			if ( ! params.lat || ! params.lon || ! params.filters || ! params.zoom ) {
				vfDebug( 'minimap attributes incomplete', params );
				map.classList.add( 'is-error' );
				continue;
			}

			vfDebug( 'mini map attributes', params );


			let container = document.createElement( 'div' );
			container.classList.add( 'vestorfilter-mini-map__inside' );
			map.append( container );

			if ( params.labels ) {
				map.dataset.showLabels = 'yes';
			}

			let options = { styles: vestorMaps.mapStyle };
			options.options = {
				clickableIcons: false,
				disableDefaultUI: true,
				mapTypeId: google.maps.MapTypeId.TERRAIN,
				mapTypeControl: false,
				gestureHandling: 'none',
				center: {
					lat: params.lat,
					lng: params.lon
				},
				zoom: params.zoom
			};

			let thisMap = new google.maps.Map( container, options );
			thisMap.addListener( 'idle', () => {
				installMiniMap( thisMap, map, params.filters );
			} );

			map.map = thisMap;

		}
		
	};

	const installMiniMap = ( map, container, filters ) => {
		
		if ( container.dataset.mapInstalled ) {
			return;
		}
		container.dataset.mapInstalled = 'working';

		let params = new URLSearchParams();
		for( let key in filters ) {
			//console.log( key );
			if ( filters.hasOwnProperty( key ) ) {
				params.set( key, filters[key] );
			}
		}
		let currentBounds = map.getBounds();
		params.set( 'geo', `${currentBounds.getSouthWest().lat()},${currentBounds.getSouthWest().lng()},${currentBounds.getNorthEast().lat()},${currentBounds.getNorthEast().lng()}` );
		params.set( 'limit', 200 );

		let query = params.toString();
		vfDebug( 'mini map query', query );

		let type = false;
		if ( filters['property-type'] ?? false ) {
			if ( filters['property-type'] !== 'all' ) {
				type = filters['property-type'];
			}
		}

		let format = false;
		if ( filters.vf ) {
			format = vfFormats[ filters.vf ];
		}

		vfDebug( 'params', container.dataset );

		vestorSearch.loadResults( { 
			query, 
			wait: true, 
			onComplete: ( e ) => installPins( { 
				e, 
				map, 
				filteredPropertyType: type, 
				format, 
				showLabels: container.dataset.showLabels 
			} ) 
		} );

	};

	const installPins = ( { e, map, filteredPropertyType, format, showLabels } ) => {
		
		let { response, status } = e.target;

		if ( status !== 200 || ! response.properties || response.properties.length === 0 ) {
			return;
		}

		let bounds = null, extend = null;
		if ( response.search_maps ) {
			let boundaryMaps = response.search_maps;
			for ( let map of boundaryMaps ) {
				if ( typeof map == 'object' ) {
					extend = new google.maps.LatLngBounds();
					let points = [];
					for( let coords of map.coords ) {
						let point = { lat: parseFloat( coords[0] ), lng: parseFloat( coords[1] ) };
						points.push( point );
						extend.extend( point );
					}
					bounds = new google.maps.Polygon({
						paths: points,
						editable: false,
						strokeWeight: 3,
						strokeColor: '#d89033',
						fillColor: '#231f20',
						fillOpacity: 0,
						zIndex: 2,
						visible: true,
					});
				}
			}
		}
		if ( bounds ) {
			bounds.setMap( map );
		}
		if ( extend ) {
			map.fitBounds( extend );
		}

		for( let property of response.properties ) {

			let coords = {
				lat: parseFloat( property.lat ) / 1000000,
				lng: parseFloat( property.lon ) / 1000000
			};
			if ( bounds && ! google.maps.geometry.poly.containsLocation( coords, bounds ) ) {
				continue;
			}

			let propertyType = filteredPropertyType || property.property_type;
			let step = vestorMaps.getPropertyStep( property, propertyType, format );
			
			let zIndex = response.properties.length - ( property.zIndex ?? i );
			let icon = vestorMaps.getPropertyIcon( propertyType, property.price / 1000, step );
			let pin = new google.maps.Marker({
				position: coords,
				title: property.title,
				map: map,
				icon,
				opacity: 1,
				optimized: true,
				zIndex: zIndex,
				visible: true,
			});
			pin.addListener( 'click', () => {
				window.location.href = vfEndpoints.property + '/' + property.MLSID + '/' + property.slug;
			} );
			if ( showLabels && showLabels === 'yes' ) {
				vfDebug( 'show label', showLabels );
				let label = {
					text: vestorTemplates.formatPrice( property.price, true ),
					className: 'marker-pin-label',
					fontSize: '12px',
					fontWeight: '700'
				};
				pin.setLabel( label );
			}
		}

	};

} )();


const vestorMaps = ( function() {

	let initialized;

	let vestorMap,
		vestorMapPolys = {},
		mapXHR,
		propertyXHR,
		drawViewportProperties,
		viewportXHR,
		viewportPins = {},
		viewportProperties,
		viewportFlags,
		viewportBounds,
		highlightMaps,
		propertyCache = [],
		propertyTypes = [],
		typeCounts = {},
		allProperties = [],
		currentMapLevel,
		currentPage = null,
		lastRedraw,
		siteBaseUrl,
		minmaxBoundRange,
		lastZoom,
		maxZoom,
		currentlySelected = null,
		mapboundsChanged = false,
		redrawing = false,
		isIdle = false,
		shouldResetBounds = false,
		hasPointsOutside = false,
		resultsPanel,
		onNextBoundsChange = null,
		afterPinInstall,
		selectedMLS = null,
		isDebug,
		debugPanel;

	let mapStyle;
	let markerCluster;

	let dontResetBoundaries = false;

	const mapResultsPanel = document.querySelector( '.map-search-results' );
	const mapResultsWrapper = document.querySelector( '[data-results-wrapper]' );
	const searchTemplateContainer = mapResultsWrapper ? mapResultsWrapper.parentNode : null;
	const propertyPanel = document.getElementById( 'property-panel' );
	const mapKeyPanel = document.querySelector( '[data-results-key]' );

	//let pinMarkers = {};
	let googleReady = false;
	let editingMode = false;
	let currentShape, currentMapId, activeMap, fallbackShape;
	let drawingManager;
	let previousZoom, largestBounds, polyBounds;

	let debounceTimer;

	const circlePaths = [
		//'M30,40a10,10 0 1,0 20,0a10,10 0 1,0 -20,0',
		'M25,40a15,15          0 1,0 30,0a15,15         0 1,0 -30,0',
		'M21.25,40a18.75,18.75 0 1,0 37.5,0a18.75,18.75 0 1,0 -37.5,0',
		'M17.5,40a22.5,22.5    0 1,0 45,0a22.5,22.5     0 1,0 -45,0',
		'M15,40a25,25          0 1,0 50,0a25,25         0 1,0 -50,0',
		'M12.5,40a27.5,27.5    0 1,0 55,0a27.5,27.5     0 1,0 -55,0'
	];

	//<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" style="fill: rgba(0, 0, 0, 1);transform: ;msFilter:;"><path d="M20.205 4.791a5.938 5.938 0 0 0-4.209-1.754A5.906 5.906 0 0 0 12 4.595a5.904 5.904 0 0 0-3.996-1.558 5.942 5.942 0 0 0-4.213 1.758c-2.353 2.363-2.352 6.059.002 8.412L12 21.414l8.207-8.207c2.354-2.353 2.355-6.049-.002-8.416z"></path></svg>
	const heartPath = 'M20.205 4.791a5.938 5.938 0 0 0-4.209-1.754A5.906 5.906 0 0 0 12 4.595a5.904 5.904 0 0 0-3.996-1.558 5.942 5.942 0 0 0-4.213 1.758c-2.353 2.363-2.352 6.059.002 8.412L12 21.414l8.207-8.207c2.354-2.353 2.355-6.049-.002-8.416z';

	const baseColors = {
		sf:         [207, 32, 47],  //'CF202F',
		'55':       [207, 32, 47],  //'CF202F',
		condos:     [162, 38, 141], //'a2268d',
		mf:         [3, 165, 80],   //'03a550',
		land:       [254, 221, 16], //'fedd10',
		commercial: [12, 114, 186], //'0c
		other:      [244, 143, 33]
	};
	const propertyColors = {
		sf:         0,
		'55':       0,
		condos:     1,
		mf:         2,
		land:       3,
		commercial: 4,
		other:      5,
	};

	const priceScale = {
		sf:   [ 200, 550, 2200 ],
		mf:   [ 400, 700, 3000 ],
		land: [ 200, 300, 1000 ],
		commercial: [ 300, 600, 3000 ],
		condos: [ 200, 550, 2000 ],
	};

	const defaultScale = {
		sf:         {
			data: 'sqft',
			range: [ 1000, 5000 ],
		},
		'55':         {
			data: 'sqft',
			range: [ 1000, 5000 ],
		},
		mf:         {
			data: 'sqft',
			range: [ 2000, 4000, 10000 ],
		},
		land:         {
			data: 'lot',
			range: [ 0.2, 1, 10 ],
		},
		commercial:         {
			data: 'sqft',
			range: [ 1000, 3500, 10000 ],
		},
		condos:         {
			data: 'sqft',
			range: [ 800, 1500, 3000 ],
		}
	};

	/*
	<div class="vf-search__map-vf-description">
		<h3><?= $vf ? esc_html( Filters::get_filter_name( $vf ) ) : '' ?></h3>
		<p><?= $vf ? esc_html( Filters::get_filter_description( $vf ) ) : '' ?></p>
	</div>
	*/

	if ( mapResultsPanel && mapResultsPanel.parentNode ) {
		let vfPanel = document.getElementById( 'vestorfilter-selection-panel' );
		if ( vfPanel ) {
			let newPanel = document.createElement('div');
			newPanel.innerHTML = vfPanel.innerHTML;
			newPanel.id = 'vestorfilter-sidebar-panel';
			newPanel.querySelector('.inside').id = 'vestorfilter-sidebar-panel__inside';
			newPanel.querySelector('button').setAttribute( 'aria-controls', 'vestorfilter-sidebar-panel__inside' );
			//newPanel.querySelector('button').innerHTML += '<svg class="vf-use-icon vf-use-icon--action-arrow"><use xlink:href="#action-arrow"></svg>';
			newPanel.querySelector('button[aria-controls="vf-vestorfilter-panel"]').remove();

			let filtersSelections = newPanel.querySelectorAll( 'li[data-filter-key="vf"]' );
			for ( let filterLi of filtersSelections ) {
				filterLi.querySelector('label').for = 'vestorfilter-sidebar-panel__input--' + filterLi.dataset.filterValue;
				filterLi.querySelector('input').id = 'vestorfilter-sidebar-panel__input--' + filterLi.dataset.filterValue;
			}

			let newP = document.createElement('p');
			newPanel.append( newP );

			mapResultsPanel.parentNode.prepend( newPanel );

			newPanel.addEventListener( 'click', ( e ) => {

				a11yToggleExpand( { target: newPanel.querySelector('button'), forced: false } );

			} );
		}
	}

	

	window.loadResultsMap = function() {

		let currentUrl = new URL( window.location.href );
		if ( currentUrl.searchParams.has( 'mlsid' ) ) {
			selectedMLS = currentUrl.searchParams.get( 'mlsid' ) + '';
		}
		if ( currentUrl.searchParams.has( 'page' ) ) {
			currentPage = currentUrl.searchParams.get( 'page' );
		}
		if ( vfIsListMode ) {
			return;
		}
		if ( currentUrl.searchParams.has( 'debug' ) ) {
			isDebug = true;
			debugPanel = document.getElementById( 'map-debug-panel' );
			if ( ! debugPanel ) {
				isDebug = false;
			}
		}

		//let pinIcons = document.querySelectorAll( '.vf-search-map__icon[data-property-type]' );

		document.body.dataset.zoomLevel = '1';

		minmaxBoundRange = new google.maps.LatLngBounds();
		minmaxBoundRange.extend( { lat: parseFloat( vfMapBounds['ne'][0] ), lng: parseFloat( vfMapBounds['ne'][1] ) } );
		minmaxBoundRange.extend( { lat: parseFloat( vfMapBounds['sw'][0] ), lng: parseFloat( vfMapBounds['sw'][1] ) } );

		vfDebug( 'bound range', minmaxBoundRange.getNorthEast().toJSON(), minmaxBoundRange.getSouthWest().toJSON() );

		/*window.addEventListener( 'scroll', ( e ) => { debounce( maybeAddMoreProperties, 500, e ) }  );
		mapResultsPanel.parentNode.addEventListener( 'scroll', ( e ) => { 
			debounce( maybeAddMoreProperties, 500, e );
			//debounce( maybeLoadPhotos, 500, { currentTarget: mapResultsPanel.parentNode } );
		} );*/

		let options = initialMapCenter || {};
		options.styles = mapStyle;
		options.options = {
			clickableIcons: false,
			disableDefaultUI: true,
			zoomControl: true,
			streetViewControl: true,
			gestureHandling: 'greedy',
			mapTypeId: google.maps.MapTypeId.TERRAIN,
			mapTypeControl: false,
			minZoom: 8,
			zoom: 15
		};
		if ( initialMapCenter ) {
			options.options.center = initialMapCenter.center;
			if ( initialMapCenter.zoom ) {
				options.options.zoom = initialMapCenter.zoom;
			}
		}
		vfDebug( options );
		vestorMap = new google.maps.Map( document.getElementById('gmap-interface'), options );

		if ( initialMapCenter && initialMapCenter.zoom ) {
			vestorMap.setZoom( initialMapCenter.zoom );
			vestorMap.panTo( initialMapCenter.center );
		} else if ( initialMapRect ) {
			let bounds = new google.maps.LatLngBounds();
			vfDebug( 'initial map square', initialMapRect );
			bounds.extend( initialMapRect['min'] );
			bounds.extend( initialMapRect['max'] );
			vestorMap.fitBounds( bounds );
		} 
		/*
		if ( initialVestorMapPins && window.vestorSkipDownload ) {
			window.vestorResultsHash = null;
			installPins( vestorMap, initialVestorMapPins );
		}
		*/

		mapboundsChanged = false;

		let firstRun = true;

		google.maps.event.addListener( vestorMap, 'idle', function() {

			if ( ! google || ! vestorMap ) {
				return;
			}

			if ( typeof sharedGeo !== 'undefined' && sharedGeo ) {
				//let bounds = new google.maps.LatLngBounds();
				//vfDebug( 'preset geo', sharedGeo );
				//bounds.extend( sharedGeo.ne );
				//bounds.extend( sharedGeo.sw );
				//vestorMap.fitBounds( bounds );
				initialMapCenter = sharedGeo;
	
				//initialMapRect['min'] = sharedGeo.sw;
				//initialMapRect['max'] = sharedGeo.new;
				vfDebug( 'force center', sharedGeo );
				vestorMap.setZoom( sharedGeo.zoom );
				vestorMap.panTo( sharedGeo.center );

				sharedGeo = null;
			}

			vfDebug( 'is idle, bounds changed: ', mapboundsChanged );

			vestorMap.setMapTypeId( google.maps.MapTypeId.TERRAIN );
			//if ( vestorMapPins.length > 0 ) {
			//	rescanPins( currentShape );
			//}
			if ( vestorMap.getZoom() > 20 ) {
				vfDebug( 'reset zoom to 20' );
				vestorMap.setZoom(20);
			}

			document.body.dataset.zoomLevel = vestorMap.getZoom();

			if ( editingMode ) {
				return;
			}

			if ( firstRun ) {
				firstRun = false;
				google.maps.event.addListener( vestorMap, 'bounds_changed', () => {
					mapboundsChanged = true;
					vfDebug( 'bounds changed' );
				} );
			}

			if ( mapboundsChanged ) {
				if ( onNextBoundsChange ) {
					onNextBoundsChange();
				}
				
				boundsChanged();
				mapboundsChanged = false;
			}

			lastZoom = vestorMap.getZoom();
			viewportBounds = vestorMap.getBounds();

		} );

		resultsPanel = document.querySelector( '[data-vf-results]' );

		googleReady = true;

		if ( highlightedMaps ) {
			highlightMaps = highlightedMaps;
		}

		previousZoom = vestorMap.getZoom();

		if ( ! initialized ) {

			document.addEventListener( 'click', ( e ) => {

				if ( document.body.classList.contains( 'is-loading' ) || document.body.classList.contains( 'is-going-away' ) ) {
					return;
				}

				let backBtn = e.target.closest( '[data-vestor-back]' );
				let mapPanel = e.target.closest('[data-results-wrapper]' );
				if ( document.body.classList.contains( 'showing-property-panel' ) && ( mapPanel || backBtn ) ) {
					history.back();
					return;
				}

				

				let propertyBlock = e.target.closest('.vf-property-block[data-property-id]' );
				if ( ! propertyBlock ) {
					return;
				}

				let favoritesBtn = e.target.closest( '[data-vestor-favorite]' );
				if ( favoritesBtn ) {
					return;
				}

				let listIndex = propertyBlock.property ? propertyBlock.property.listIndex : viewportPins[ propertyBlock.dataset.propertyId ].property.listIndex;
				let propertyId = propertyBlock.property ? propertyBlock.property.ID : viewportPins[ propertyBlock.dataset.propertyId ].property.ID;
				
				//currentlySelected = propertyId;

				if ( e.target.closest( '[itemprop="address"]' ) 
				|| searchTemplateContainer.classList.contains( 'is-list-view' )
				|| window.innerWidth < 768 
				|| ( currentlySelected && currentlySelected.property.ID === propertyId )
				) {
					loadPropertyPage( propertyId );
				} else {
					highlightProperty( { listIndex, dontZoom: true, dontScroll: true } );
				}

				e.preventDefault();
				e.stopPropagation();

				return false;

			} );

			document.addEventListener( 'click', maybeLoadCustomMap );

			document.addEventListener( 'submit', maybeSaveCustomMap );

			document.addEventListener( 'vestorfilters|filter-change', resetPolyOutlines );

			initialized = true;

			document.body.classList.add( 'whole-page-refresh' );

			google.maps.event.addListener( vestorMap, 'bounds_changed', () => { 
				vfDebug( 'bounds changed event' );
				document.body.dataset.zoomLevel = vestorMap.getZoom();
				if ( ! viewportBounds ) {
					viewportBounds = vestorMap.getBounds();
					vfDebug( 'initial bounds', viewportBounds );
					
					document.dispatchEvent( new CustomEvent( 
						'vestorfilters|map-ready', {
						detail: {
							bounds: viewportBounds.toJSON(),
							zoom: vestorMap.getZoom(),
							selected: currentlySelected ? currentlySelected.property.ID : null
						}
					} ) );
					getPolys( vestorMap.getZoom() );
				}
				if ( isDebug ) {
					debugPanel.innerHTML = "zoom: " + vestorMap.getZoom() + '<br>' +
										   "bounds: " + vestorMap.getBounds().toString();
				}
			} );

		}

	}

	document.addEventListener( 'click', ( e ) => {

		let pageBtn = e.target.closest('[data-change-page]');
		if ( pageBtn ) {
			vfDebug( 'change page request', pageBtn );
			displayPage( viewportProperties, pageBtn.dataset.changePage );
			mapResultsPanel.parentNode.scrollTo( 0, 0 );
			window.scrollTo( 0, 0 );
			resetSelectedPin();

			let currentUrl = new URL( window.location.href );
			currentUrl.searchParams.set( 'page', pageBtn.dataset.changePage );
			vestorFilters.updatePageUrl( currentUrl );

			e.stopPropagation();
			return;
		}

	} );

	const getMapBounds = () => {

		if ( vestorMap ) {
			return vestorMap.getBounds();
		}
		return null;

	};

	const getMapZoom = () => {

		if ( vestorMap ) {
			return vestorMap.getZoom();
		}
		return null;

	};

	const resetMapBounds = ( minmax, center, onComplete ) => {

		viewportBounds = null;

		let bounds = new google.maps.LatLngBounds();
		vfDebug( minmax );

		let min = { lat: parseFloat( minmax.min[0] ) / 1000000, lng: parseFloat( minmax.min[1] ) / 1000000 };
		let max = { lat: parseFloat( minmax.max[0] ) / 1000000, lng: parseFloat( minmax.max[1] ) / 1000000 };

		vfDebug( 'reset bounds', min, max );

		bounds.extend( min );
		bounds.extend( max );
		vestorMap.fitBounds( bounds );
		if ( center ) {
			vestorMap.panTo( { lat: parseFloat( center[0] ) / 1000000, lng: parseFloat( center[1] ) / 1000000 } );
		}

		onNextBoundsChange = () => {
			vestorSearch.downloadResults( { forceProperty: currentlySelected ? currentlySelected.property.ID : null } );
			onNextBoundsChange = null;
		};

		

	};

	const resetViewportPins = () => {

		for ( let propertyId in viewportPins ) {
			if ( viewportPins.hasOwnProperty( propertyId ) ) {
				viewportPins[propertyId].pin.setMap(null);
				viewportPins[propertyId] = null;
			}
		}
		viewportPins = {};

	};

	async function removeHiddenPins( bounds ) {

		let removed = 0;
		for( let propertyId in viewportPins ) {
			if ( viewportPins.hasOwnProperty( propertyId ) ) {
				if ( viewportPins[ propertyId ] ) {
					if ( viewportPins[ propertyId ].property.hidden ) {
						viewportPins[ propertyId ].pin.setVisible( false );
						removed ++;
						continue;
					}
					/*if ( bounds && bounds.contains( viewportPins[ propertyId ].coords ) ) {
						viewportPins[ propertyId ].pin.setVisible( false );
						removed ++;
					}*/
				}
				
			}
		}
		vfDebug( '--removed ' + removed + ' pins' );

	}

	const makeMarker = ( property ) => {

	};

	function installPins( gmap, mapPins, listOnly ) {

		//let bounds = new google.maps.LatLngBounds();

		let filteredPropertyType, dataRange, dataScale;
		let currentFilters = vestorFilters.getActiveFilters();
		if ( currentFilters['property-type'] ?? false ) {
			if ( currentFilters['property-type'] !== 'all' ) {
				filteredPropertyType = currentFilters['property-type'];
			}
		}
		if ( currentFilters.vf ) {
			let format = vfFormats[ currentFilters.vf ];
			dataRange = format.range;
			dataScale = format.scale;
		}
		let zoom, viewportBounds;
		if ( gmap ) {
			zoom = gmap.getZoom();
			viewportBounds = gmap.getBounds();
			vfDebug( 'installing pins', zoom, filteredPropertyType, dataRange, dataScale, mapPins );
		}
		removeHiddenPins( viewportBounds );

		let replaced = 0;
		let skipped = 0;
		let updated = 0;

		let totalProperties = vestorSearch.getCacheCount();
		if ( ! totalProperties ) {
			totalProperties = mapPins.length;
		}

		typeCounts = {};

		for( let i = 0; i < mapPins.length; i ++ ) {

			let property = mapPins[i];

			if ( ! property.property_type || ! property.lat || ! property.lon ) {
				continue;
			}

			let coords = {
				lat: parseFloat( property.lat ) / 1000000,
				lng: parseFloat( property.lon ) / 1000000
			};

			let propertyType;
			for( let type of property.property_type.split(',') ) {
				if ( propertyColors.hasOwnProperty( type ) ) {
					propertyType = type;
					break;
				}
			}
			if ( ! propertyType ) {
				propertyType = 'other';
			}

			let inBounds = gmap ? viewportBounds.contains( coords ) : true;

			if ( ! property.hidden && inBounds ) {
				if ( ! typeCounts.hasOwnProperty( propertyType ) ) {
					typeCounts[propertyType] = 0;
				}
				typeCounts[propertyType]++;
			}

			let step = 2;
			if ( dataRange && dataScale && dataScale[propertyType] ) {
				/*if ( dataRange === 'lot' && ! property.data_cache['lot'] && property.data_cache['lot_est'] ) {
					property.data_cache['lot'] = property.data_cache['lot_est'] / 43560;
					console.log( property.data_cache['lot'] );
				}*/
				if ( ! property.data_cache || ! property.data_cache[ dataRange ] ) {
					step = 2;
				} else {
					let scale = findScaleStep( dataScale[propertyType], property.data_cache[ dataRange ] / 100 );
					step = Math.floor( scale * 4 );
				}
				if ( step > 4 ) {
					step = 4;
				}
			} else if ( defaultScale[propertyType] ) {
				//vfDebug( defaultScale[propertyType] );
				let defaultScaleData = defaultScale[propertyType].data;
				if ( property.data_cache && property.data_cache[ defaultScaleData ] ) {
					let scale = findScaleStep( defaultScale[propertyType].range, property.data_cache[ defaultScaleData ] / 100 );
					step = Math.floor( scale * 4 );
				}
			}

			let isFavorite = vestorFavorites.getFavorites().indexOf( property.ID ) !== -1;
			let icon;
			if ( isFavorite ) {
				icon = {
					path:         heartPath,
					fillColor:    '#d83933',
					fillOpacity:  1,
					strokeWeight: 2,
					strokeColor:  '#ffffff',
					scale:        1,
					anchor:       new google.maps.Point( 12, 12 ),
					labelOrigin:  new google.maps.Point( 12, 24 )
				};
			} else {
				icon = getPropertyIcon( filteredPropertyType ?? propertyType, property.price / 1000, step );
			}

			property.listIndex = i + 0;

			if ( currentlySelected && currentlySelected.property.ID === property.ID ) {
				currentlySelected.property = property;
			}

			let oldPin;
			if ( viewportPins.hasOwnProperty( property.ID ) ) {

				oldPin = viewportPins[ property.ID ];
				if ( oldPin ) {
					if ( oldPin.step !== step || isFavorite ) {
						oldPin.pin.setMap( null );
						replaced ++;
					} else {
						//oldPin.listener.remove();
						//oldPin.listener = oldPin.pin.addListener( clickListener );
						//oldPin.pin.setZIndex( totalProperties - i );
						if ( gmap ) {
							oldPin.pin.setMap( gmap );
						}
						oldPin.property.listIndex = i + 0;
						oldPin.step = step;
						
						if ( zoom >= 13 && ! oldPin.labelVisible ) {
							if ( gmap ) {
								oldPin.pin.setLabel( oldPin.label );
							}
							oldPin.labelVisible = true;
						} else if ( zoom < 13 && oldPin.labelVisible ) {
							oldPin.labelVisible = false;
							if ( gmap ) {
								oldPin.pin.setLabel(null);
							}
						}
						if ( gmap ) {
							oldPin.pin.setVisible( true );
						}
						updated ++;
						continue;
					}
				}
			}

			if ( gmap ) {

				let label = {
					text: vestorTemplates.formatPrice( property.price, true ),
					className: 'marker-pin-label',
					fontSize: '12px',
					fontWeight: '700'
				};

				let zIndex = totalProperties - ( property.zIndex ?? i );

				let pin = new google.maps.Marker({
					position: coords,
					title: property.title,
					map: gmap,
					icon,
					opacity: 1,
					optimized: true,
					zIndex: isFavorite ? totalProperties : zIndex,
					visible: true,
					//label
				});
				let listener = pin.addListener( 'click', () => {
					if ( editingMode ) {
						return;
					}
					highlightProperty( { listIndex: property.listIndex, dontZoom: true } );
				} );

				if ( zoom >= 13 ) {
					pin.setLabel( label );
				}

				property.pin = pin;

				viewportPins[ property.ID ] = { coords, pin, label, property, listener, step, labelVisible: ( zoom >= 13 ) };

			}

			//vestorMapPins.push(  );

		}

		if ( propertyTypes && mapKeyPanel ) {
			mapKeyPanel.innerHTML = '';
			for( let type in propertyColors ) {
				if ( ! propertyTypes[type] ) {
					continue;
				}
				let count = typeCounts.hasOwnProperty(type) ? typeCounts[type] : 0;
				if ( count === 0 ) {
					continue;
				}
				let label = document.querySelector( `label[for="vf-filter-option__property-type--${type}"]` );
				if ( ! label ) {
					label = type;
				} else {
					label = label.innerHTML;
				}
				if ( type === 'condos' ) {
					label = 'Condos';
				}
				let newLabel = document.createElement('div');
				newLabel.classList.add('vf-search__map-key-label--' + type);
				newLabel.classList.add('vf-search__map-key-label');
				newLabel.style.setProperty( '--color', getPropertyColor( type, 0 ) );
				newLabel.innerHTML = label + ` (${count})`;
				mapKeyPanel.append( newLabel );
			}
			let listBtn = document.createElement('button');
			listBtn.setAttribute( 'class', 'btn vf-search__map-list-mode-btn' );
			listBtn.setAttribute( 'type', 'button' );
			listBtn.dataset.switchMode = 'list';
			listBtn.innerHTML = '<svg class="vf-use-icon vf-use-icon--data-dom"><use xlink:href="#data-units"></use></svg>';

			mapKeyPanel.append( listBtn );

			let mapBtn = document.createElement('button');
			mapBtn.setAttribute( 'class', 'btn vf-search__map-map-mode-btn' );
			mapBtn.setAttribute( 'type', 'button' );
			mapBtn.dataset.switchMode = 'map';
			mapBtn.innerHTML = '<svg class="vf-use-icon vf-use-icon--data-maps"><use xlink:href="#data-maps"></use></svg>';
			
			mapKeyPanel.append( mapBtn );
			
		}

		vfDebug( '--added ' + mapPins.length + ' pins, replaced: ' + replaced + ', updated: ' + updated );

		if ( typeof afterPinInstall === 'function' ) {
			vfDebug( '--run after pin install' );
			afterPinInstall();
			afterPinInstall = null;
		}

		//if ( mapPins.length === 0 && initialMapRect ) {
		//	bounds.extend( initialMapRect[0] );
		//	bounds.extend( initialMapRect[1] );
		//}

		document.body.classList.remove( 'pins-loading' );

		//return bounds;

	}

	const getPropertyIcon = ( type, value, scaleStep ) => {

		let scale = ( scaleStep ?? 2 ) * 0.05 + 0.2;

		let darkness;
		if ( typeof priceScale[type] != 'undefined' ) {
			darkness = Math.round( findScaleStep( priceScale[type], value ) * 10 ) * 100 * scale;
		} else {
			darkness = 0;
		}

		let color = propertyColors.hasOwnProperty(type) ? propertyColors[type] : propertyColors.other;
		//return  `${vfPaths.distUrl}/images/${color}_dot-${darkness}.png`;


		return {
			url:         `${vfPaths.distUrl}/images/dot-sprite-70.png`,
			labelOrigin: new google.maps.Point( 50*scale, 75*scale + 8 ),
			anchor: 	 new google.maps.Point( 50*scale, 50*scale ),
			size:        new google.maps.Size( 100*scale, 100*scale ),
			origin:      new google.maps.Point( darkness, color * 100 * scale ),
			scaledSize:  new google.maps.Size( 1100*scale, 600*scale ),
		};

	}

	const getPropertyColor = ( type, value ) => {

		let color = baseColors[type] ?? baseColors.other;
		let newColor = {};
		let darkness;
		if ( priceScale[type] ) {
			darkness = findScaleStep( priceScale[type], value ) * 100;
		} else {
			darkness = 0;
		}
		for( let i in color ) {
			newColor[i] = color[i] - darkness;
			if ( newColor[i] < 0 ) {
				newColor[i] = 0;
			}
		}
		return `rgb(${newColor[0]},${newColor[1]},${newColor[2]})`;

	};

	const getPropertyStep = ( property, type, format ) => {

		let dataRange, dataScale;
		if ( format ) {
			dataRange = format.range;
			dataScale = format.scale;
		}

		if ( dataRange && dataScale && dataScale[type] ) {
			/*if ( dataRange === 'lot' && ! property.data_cache['lot'] && property.data_cache['lot_est'] ) {
				property.data_cache['lot'] = property.data_cache['lot_est'] / 43560;
				console.log( property.data_cache['lot'] );
			}*/
			if ( ! property.data_cache || ! property.data_cache[ dataRange ] ) {
				step = 2;
			} else {
				let scale = findScaleStep( dataScale[type], property.data_cache[ dataRange ] / 100 );
				step = Math.floor( scale * 4 );
			}
			if ( step > 4 ) {
				step = 4;
			}
		} else if ( defaultScale[type] ) {
			//vfDebug( defaultScale[propertyType] );
			let defaultScaleData = defaultScale[type].data;
			if ( property.data_cache && property.data_cache[ defaultScaleData ] ) {
				let scale = findScaleStep( vestorMaps.defaultScale[type].range, property.data_cache[ defaultScaleData ] / 100 );
				step = Math.floor( scale * 4 );
			}
		}

	}

	const findScaleStep = ( scaleRange, value ) => {

		if ( value <= scaleRange[0] ) {
			return 0;
		}
		if ( value >= scaleRange[ scaleRange.length - 1 ] ) {
			return 1;
		}
		let lastValue = scaleRange[0];
		let scale = 1 / ( scaleRange.length - 1 );
		for( let i = 1; i < scaleRange.length; i ++ ) {
			let min = scale * ( i - 1 );
			if ( value < scaleRange[i] ) {
				return ( ( value - lastValue ) / ( scaleRange[i] - lastValue ) ) * scale + min;
			}
			lastValue = scaleRange[i] + 0;
		}

	}

	/*
	let hasLabels = false;
	function updatePins() {

		let zoom = vestorMap.getZoom();

		let bounds = vestorMap.getBounds();
		for( let marker of vestorMapPins ) {
			if ( bounds.contains( marker.pin.getPosition() ) ) {
				let visible = true;
				if ( currentShape && ! google.maps.geometry.poly.containsLocation( marker.property.coords, currentShape ) ) {
					visible = false;
				}
				marker.pin.setOptions( {
					visible: visible
				} );
				if ( zoom >= 13 ) {
					marker.pin.setLabel( {
						text: marker.label,
						className: 'marker-pin-label',
						fontSize: '12px',
						fontWeight: '700'
					} );
				} else {
					marker.pin.setLabel(null);
				}
			} else {
				marker.pin.setOptions( {
					visible: false
				} );
			}
		}

		if ( zoom >= 14 ) {
			hasLabels = true;
		} else {
			hasLabels = false;
		}



	}

	class ClusterRenderer {
		render( { count, position }, stats) {

			const svg = window.btoa(`
				<svg fill="#ffffff" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 240 240">
					<circle stroke-width="4" stroke="#000000" cx="120" cy="120" opacity=".8" r="70" />
				</svg>`);
			// create marker using svg icon
			return new google.maps.Marker({
				position,
				icon: {
					url: `data:image/svg+xml;base64,${svg}`,
					scaledSize: new google.maps.Size(75, 75),
				},
				label: {
					text: String(count),
					color: "rgba(0,0,0,1)",
					fontSize: "12px",
				},
				// adjust zIndex to be above other markers
				zIndex: 0,
			});
		};
	};
	const mapClusterRenderer = new ClusterRenderer();
	*/

	function getPolys( scale ) {

		//todo: only download if min/max boundaries have been exceeded

		//vfDebug( scale );
		if ( ! scale || scale < 9 ) {
			return;
		}

		let withinExistingBounds = false;
		let bounds = vestorMap.getBounds();
		if ( largestBounds && largestBounds.contains( bounds.getNorthEast() ) && largestBounds.contains( bounds.getSouthWest() ) ) {
			withinExistingBounds = true;
		}

		if ( mapXHR ) {
			mapXHR.abort();
		}

		mapXHR = new XMLHttpRequest();

		let rect = [
			{ lat: bounds.getNorthEast().lat(), lon: bounds.getNorthEast().lng() },
			{ lat: bounds.getSouthWest().lat(), lon: bounds.getSouthWest().lng() }
		];
		let getString = '/maps/';
		if ( scale < 11 ) {

			let countyMaps = window.localStorage.getItem( 'countyMaps' );
			if ( currentMapLevel === 'county' && withinExistingBounds && countyMaps ) {
				return;
			}
			currentMapLevel = 'county';

			if ( countyMaps && withinExistingBounds ) {
				countyMaps = JSON.parse( countyMaps );
				if ( countyMaps ) {
					setupPolygons( { target: { status: 200, response: countyMaps } } );
					return;
				}
			}
			getString += '?type=county&coords=' + JSON.stringify( rect );
			mapXHR.scaleType = 'county';

		} else {

			let neighborhoodMaps = window.localStorage.getItem( 'neighborhoodMaps' );
			if ( currentMapLevel === 'neighborhood' && withinExistingBounds && neighborhoodMaps ) {
				return;
			}
			currentMapLevel = 'neighborhood';

			getString += '?type=neighborhood&coords=' + JSON.stringify( rect );
			mapXHR.scaleType = 'neighborhood';

		}

		mapXHR.open( "GET", vfEndpoints.location + getString );
		let nonce = window.vestorAccount.getNonce();
		if ( nonce ) {
			mapXHR.setRequestHeader( 'X-WP-Nonce', nonce );
		}
		mapXHR.send();
		mapXHR.responseType = 'json';
		mapXHR.addEventListener( "load", setupPolygons );



	}

	function setupPolygons( { target } ) {
		let { status, response } = target;

		if ( status !== 200 ) {
			return;
		}

		if ( target.scaleType ) {
			try {
				window.localStorage.setItem( `${target.scaleType}Maps`, JSON.stringify( response ) );
			} catch( e ) {
				window.localStorage.clear();
				try {
					window.localStorage.setItem( `${target.scaleType}Maps`, JSON.stringify( response ) );
				} catch( e ) {}
			}
		}
		//vfDebug( response );
		polyBounds = installPolygons( vestorMap, response );

		if ( ! largestBounds ) {
			largestBounds = vestorMap.getBounds();
		} else {
			largestBounds.extend( vestorMap.getBounds().getNorthEast() );
			largestBounds.extend( vestorMap.getBounds().getSouthWest() );
		}
	}

	function installPolygons( gmap, maps ) {

		//for( let existingPoly of vestorMapPolys ) {
		//	existingPoly.setMap(null);
		//}
		//vestorMapPolys = [];
		let currentFilters = vestorFilters.getActiveFilters();
		if ( currentFilters.location ) {
			highlightMaps = currentFilters.location.split(',');
		} else {
			highlightMaps = [];
		}

		let zoomLevel = gmap.getZoom();
		let visibleMaps = [];

		//vfDebug( highlightMaps );
		let selectedBounds = null;

		for( let map of maps ) {
			let locationId = map.location.toString();
			let highlighted = highlightMaps.indexOf( locationId ) !== -1;
			//vfDebug( map, highlighted );
			if ( vestorMapPolys && vestorMapPolys[ locationId ] ) {
				for( let shape of vestorMapPolys[ locationId ].shapes ) {
					//vfDebug( vestorMapPolys[ locationId ] );
					shape.setOptions( {
						strokeWeight: highlighted ? 3 : 2,
						strokeColor: highlighted ? '#d89033' : '#231f20',
						zIndex: highlighted ? 1 : 0,
						visible: true,
						clickable: false
					} );
					if ( highlighted ) {
						if ( ! selectedBounds ) {
							selectedBounds = new google.maps.LatLngBounds();
						}
						selectedBounds.extend( vestorMapPolys[ locationId ].bounds.getNorthEast() );
						selectedBounds.extend( vestorMapPolys[ locationId ].bounds.getSouthWest() );
					}
				}
				visibleMaps.push(locationId);
				continue;
			}
			for ( let shapes of map.vectors ) {
				let points = [];
				let bounds = new google.maps.LatLngBounds();;
				for( let coords of shapes ) {
					let point = { lat: coords[1], lng: coords[0] };
					points.push( point );
					bounds.extend( point );
					if ( highlighted ) {
						if ( ! selectedBounds ) {
							selectedBounds = new google.maps.LatLngBounds();
						}
						selectedBounds.extend( point );
					}
				}
				
				let shape = new google.maps.Polygon({
					paths: points,
					editable: false,
					strokeWeight: highlighted ? 3 : 2,
					strokeColor: highlighted ? '#d89033' : '#231f20',
					fillColor: '#231f20',
					fillOpacity: 0,
					zIndex: highlighted ? 1 : 0,
					visible: false,
					clickable: false
				});
				/*shape.addListener( 'click', () => {
					if ( editingMode || window.innerWidth < 768 ) {
						return;
					}
					updateLocation( map.location, map.type, map.location_name );
				} );*/
				shape.setMap(gmap);
				if ( ! vestorMapPolys[ locationId ] ) {
					vestorMapPolys[ locationId ] = {
						shapes: Array(),
						type:   map.type,
						bounds: bounds
					};
				}
				vestorMapPolys[ locationId ].shapes.push( shape );
				visibleMaps.push( locationId );
			}
		}
		for( let mapId in vestorMapPolys ) {
			mapId = mapId.toString();
			if ( vestorMapPolys[ mapId ].type == 'custom' ) {
				continue;
			}
			let visible = false;
			if( visibleMaps.indexOf( mapId ) !== -1 ) {
				visible = true;
			}
			for( let shape of vestorMapPolys[ mapId ].shapes ) {
				shape.setOptions( {
					visible: visible
				} );
			}
		}
		//vfDebug( vestorMapPolys );
		//vfDebug( visibleMaps );

		vfDebug( '--return poly bounds of ', selectedBounds );
		return selectedBounds;

	}

	const resetPolyOutlines = ( e ) => {

		vfDebug( 'reset poly' );

		let currentFilters = vestorFilters.getActiveFilters();
		let currentLocations = currentFilters.location;
		if ( currentLocations && currentLocations.indexOf(',') !== -1 ) {
			currentLocations = currentFilters.location.split(',');
		} else if ( currentLocations ) {
			currentLocations = [ currentLocations ];
		} else {
			currentLocations = [];
		}

		for( let locationId in vestorMapPolys ) {
			if ( currentLocations.indexOf( locationId ) === -1 ) {
				for ( let shape of vestorMapPolys[ locationId ].shapes ) {
					shape.setOptions( {
						strokeWeight: 2,
						strokeColor: '#000',
						zIndex: 0,
					} );
				}
			}
		}

	}

	const resetPolyBounds = () => {

		let currentFilters = vestorFilters.getActiveFilters();
		let currentLocations = currentFilters.location.split(',');

		let newBounds = null;

		vfDebug( 'reset poly bounds', currentLocations );

		for( let selectedLocationId of currentLocations ) {
			selectedLocationId = selectedLocationId.toString();
			if ( vestorMapPolys[ selectedLocationId ].type === 'neighborhood' || vestorMapPolys[ selectedLocationId ].type === 'county' ) {
				if ( ! newBounds ) {
					newBounds = new google.maps.LatLngBounds();
				}
				for( let shape of vestorMapPolys[ selectedLocationId ].shapes ) {
					let shapeBounds = vestorMapPolys[ selectedLocationId ].bounds;

				}
			}
		}

	};

	/*
	function updateLocation( locationId, locationType, locationName ) {

		
		vfDebug( locationId, locationType, locationName );

		let replace = false;
		let currentFilters = vestorFilters.getActiveFilters();
		let currentLocations = currentFilters.location.split(',');
		let remove = false;

		for( let selectedLocationId of currentLocations ) {
			selectedLocationId = selectedLocationId.toString();
			if ( ! vestorMapPolys[ selectedLocationId ] ) {
				replace = true;
				break;
			} else if ( vestorMapPolys[ selectedLocationId ].type != locationType ) {
				replace = true;
				break;
			} else if ( locationId == selectedLocationId ) {
				remove = true;
				break;
			}
		}

		if ( activeMap ) {
			activeMap.shapes[0].setOptions( {
				visible: false
			} );
			activeMap = null;
		}

		//resetPolyOutlines();

		if ( ! remove ) {
			for ( let shape of vestorMapPolys[ locationId ].shapes ) {
				shape.setOptions( {
					strokeWeight: 3,
					strokeColor: '#d89033',
					zIndex: 1,
					visible: true,
				} );
			}
		} else {
			for ( let shape of vestorMapPolys[ locationId ].shapes ) {
				shape.setOptions( {
					strokeWeight: 2,
					strokeColor: '#000',
					zIndex: 0,
				} );
			}
		}

		dontResetBoundaries = true;

		let locationData = null;
		if ( locationType === 'neighborhood' ) {
			let neighborhoodMaps = window.localStorage.getItem( 'neighborhoodMaps' );
			if ( neighborhoodMaps ) {
				neighborhoodMaps = JSON.parse( neighborhoodMaps );
				for ( let neighborhood of neighborhoodMaps ) {
					vfDebug( neighborhood, locationId );
					if ( neighborhood.location == locationId ) {
						locationData = {
							label: neighborhood.location_name,
							value: locationId,
							slug: makeWPSlug( neighborhood.location_name ),
							type: 'neighborhood'
						};
						break;
					}
				}
			}
			if ( ! locationData ) {
				window.vestorMessages.show( 'An error occurred selecting this neighborhood', 'error' );
				return;
			}
		}

		vestorSearch.setLocationFilter( { locationId, append: ! replace, remove }, locationData );


		if ( locationType === 'custom' ) {
			document.body.classList.add( 'custom-map-loaded' );
		} else {
			document.body.classList.remove( 'custom-map-loaded' );
		}

		if ( locationType === 'neighborhood' || locationType === 'county' || locationType === 'custom' ) {
			resetPolyBounds();
		} else {
			polyBounds = null;
		}

	};
	*/

	const resetSelectedPin = () => {

		if ( currentlySelected && currentlySelected.icon ) {
			currentlySelected.marker.setIcon( currentlySelected.icon );
			currentlySelected.marker.setZIndex( currentlySelected.zIndex );
		}
		currentlySelected = null;

		let selectedBtn = document.querySelector( '#user-maps-selector button.is-selected' );
		if ( selectedBtn ) {
			selectedBtn.classList.remove( 'is-selected' );
			mapResultsPanel.classList.remove( 'has-selection' );
		}

	};

	const setCurrentPin = ( propertyId ) => {

		resetSelectedPin();

		let property = viewportPins[ propertyId ];
		if ( ! property ) {
			vfDebug( 'pin does not exist on map', propertyId );
			return;
		}
		let pin = property.pin;
		if ( ! pin ) {
			vfDebug( 'how did you click on a nonexistant pin' );
			return;
		}
		property.pin = pin;

		resetSelectedPin();

		currentlySelected = {
			marker: pin,
			icon: pin.getIcon(),
			zIndex: pin.getZIndex(),
			property: property.property,
		};

		pin.setIcon( {
			path:         circlePaths[4],
			fillColor:    '#ffffff',
			fillOpacity:  1,
			strokeWeight: 3,
			strokeColor:  '#d89033',
			scale:        0.5,
			anchor:       new google.maps.Point( 40, 40 ),
			labelOrigin:  new google.maps.Point( 40, 80 )
		} );
		pin.setZIndex( 999999 );

	};

	const highlightProperty = ( args ) => {

		vfDebug( 'highlight property', args );

		let { propertyId, propertyPage, listIndex, dontLoadProperty, dontZoom, dontScroll } = args;

		let property;
		
		if ( ! listIndex && listIndex !== 0 ) {
			vfDebug( 'no property to highlight' );
			return;
		}
		
		property = viewportProperties[ listIndex ];
		propertyId = property.ID;
		
		if ( ! property ) {
			vfDebug( 'could not find property in viewport' );
			return;
		}

		vfDebug( property );

		if ( mapResultsPanel.childNodes.length === 0 || ! property ) {
			return;
		}

		let pin = property.pin;
		if ( ! pin ) {
			pin = viewportPins.hasOwnProperty( propertyId ) ? viewportPins[propertyId].pin : null;
		}
		if ( ! pin ) {
			vfDebug( 'how did you click on a nonexistant pin' );
			return;
		}
		property.pin = pin;

		resetSelectedPin();

		currentlySelected = {
			marker: pin,
			icon: pin.getIcon(),
			zIndex: pin.getZIndex(),
			property
		};

		/*
		pin.setIcon( {
			url: vfPaths.distUrl + '/images/map-loading.gif',
			anchor: new google.maps.Point( 18, 18 ),
			labelOrigin: new google.maps.Point( 18, 36 ),
			size: new google.maps.Size( 36, 36 ),
			optimized: false
		} );
		pin.setZIndex( 999999 );
		*/

		let propertyPageNo = getPropertyPage( property );

		vfDebug( 'display page maybe', propertyPageNo, currentPage );

		if ( propertyPageNo !== currentPage ) {
			vfDebug( '--changing to page', propertyPageNo );
		} else {
			let selected = mapResultsPanel.querySelectorAll( `blockquote.is-selected` );
			for( let wasSelected of selected ) {
				wasSelected.classList.remove('is-selected');
				mapResultsPanel.classList.remove( 'has-selection' );
			}
		}
		displayPage( viewportProperties, propertyPageNo );

		//void( mapResultsPanel.parentNode.offsetHeight ); // force reflow

		let parentRect = mapResultsPanel.parentNode.getBoundingClientRect();
		let block = mapResultsPanel.querySelector( `li:not([data-active-property]) [data-property-id="${propertyId}"]` );
		if ( ! block ) {
			vfDebug( `couldnt find property ${propertyId}` );
			return;
		}
		let blockRect = block.getBoundingClientRect();

		vestorImageUtil.forceImageLoad( block );

		if ( window.innerWidth < 768 && searchTemplateContainer.classList.contains( 'is-map-view' ) ) {
			mapResultsPanel.parentNode.scrollTo( 0, 0 );
			vfDebug( 'map panel scroll reset' );
		} else {
			let currentScrollPosition = mapResultsPanel.parentNode.scrollTop;
			vfDebug( 'map panel scroll to element', block );
			if ( ! dontScroll ) {
				mapResultsPanel.parentNode.scrollTo( {
					top: currentScrollPosition + blockRect.top - parentRect.top - 100,
					left: 0
					//behavior: 'smooth'
				} );
			}
			vfDebug( 'done scrolling' );
		}

		block.classList.add( 'is-selected' );
		mapResultsPanel.classList.add( 'has-selection' );

		let activePropertyContainer = mapResultsPanel.querySelector( '[data-active-property]' );
		if ( ! activePropertyContainer ) {
			activePropertyContainer = document.createElement( 'li' );
			activePropertyContainer.classList.add( "map-search-results__item" );
			activePropertyContainer.classList.add( "map-search-results__item--active" );
			activePropertyContainer.dataset.activeProperty = true;
			mapResultsPanel.prepend( activePropertyContainer );
		}
		activePropertyContainer.innerHTML = block.outerHTML;
		activePropertyContainer.property = property;

		vfDebug( 'backup pin' );

		pin.setIcon( {
			path: circlePaths[4],
			fillColor: '#ffffff',
			fillOpacity: 1,
			strokeWeight: 3,
			strokeColor: '#d89033',
			scale: 0.5,
			anchor: new google.maps.Point( 40, 40 ),
			labelOrigin: new google.maps.Point( 40, 80 )
		} );
		pin.setZIndex( 999999 );

		if ( ! dontZoom ) {
			let center = pin.getPosition();
			vestorMap.panTo( center );
		}

		if ( searchTemplateContainer.classList.contains( 'is-list-view' ) ) {
			vfDebug( 'window scroll reset' );
			window.scrollTo( 0, 0 );
		}

		if ( document.body.classList.contains( 'showing-property-panel' ) ) {
			if ( propertyPage ) {
				loadPropertyPage( propertyPage );
			} else {
				loadPropertyPage( propertyId );
			}
		}

		/*debounce( 
			{
				method: maybeLoadPhotos,
				priority: true
			},
			500,
			{ currentTarget: mapResultsPanel.parentNode, queryPhotos: true } 
		);*/

	};/*
	document.addEventListener( 'vestorfilters|live-response-complete', () => {

		let currentUrl = new URL( document.location );
		if ( currentUrl.searchParams.has( 'property' ) ) {
			highlightProperty( {
				propertyId: parseInt( currentUrl.searchParams.get( 'property' ) )
			} );
		}

	} );
	*/
	const loadPropertyPage = ( propertyId, skipAnimation ) => {

		vfDebug( 'downloading property' );

		if ( document.body.classList.contains( 'showing-property-panel' ) && ! skipAnimation ) {
			propertyPanel.classList.add( 'going-away' );
			setTimeout( () => {
				loadPropertyPage( propertyId, true );
			}, 600 );
			return;
		}

		let propertyPage;
		if ( propertyId.hasOwnProperty( 'id' ) ) {
			propertyPage = propertyId;
			propertyId = propertyId.id;
		}

		window.scrollTo(0,0);
		document.body.classList.add( 'showing-property-panel' );

		if ( viewportPins[ propertyId ] ) {
			vestorMap.panTo( viewportPins[ propertyId ].coords );
		}

		setCurrentPin( propertyId );

		vestorTemplates.resetCalculator();

		propertyPanel.innerHTML = '';
		propertyPanel.classList.remove( 'going-away' );
		propertyPanel.classList.add( 'is-loading' );

		void( document.offsetHeight ); // force reflow

		if ( searchTemplateContainer.classList.contains( 'is-list-view' ) && window.innerWidth >= 768 ) {
			let block = mapResultsPanel.querySelector( `blockquote[data-property-id="${propertyId}"]` );
			if ( block ) {
				let blockRect = block.getBoundingClientRect();
				let parentRect = mapResultsPanel.parentNode.getBoundingClientRect();
				let currentScrollPosition = mapResultsPanel.parentNode.scrollTop;
				vfDebug( 'map paenl scroll to node' );
				mapResultsPanel.parentNode.scrollTo( {
					top: currentScrollPosition + blockRect.top - parentRect.top - 100,
					left: 0,
				} );
			}
		}

		if ( propertyXHR ) {
			propertyXHR.abort();
		}

		if ( propertyPage ) {

			vfDebug( 'use history state' );

			setupPropertyPage( { target: { response: propertyPage, status: 200 } } );

		} else {

			propertyXHR = new XMLHttpRequest();
			propertyXHR.addEventListener( "load", setupPropertyPage );
			propertyXHR.open( "GET", vfEndpoints.cache + 'property/' + propertyId );
			let nonce = window.vestorAccount.getNonce();
			if ( nonce ) {
				propertyXHR.setRequestHeader( 'X-WP-Nonce', nonce );
			}
			propertyXHR.send();
			propertyXHR.responseType = 'json';
			propertyXHR.propertyId = propertyId;

		}



	};

	const setupPropertyPage = ( { target } ) => {

		vfDebug( 'property page setup' );

		const { response, status } = target;

		if ( status !== 200 ) {
			// todo: show error
		}

		let wrapper = document.createElement( 'article' );

		vestorProperty.installTemplateTags( wrapper, response );

		if (! document.body.classList.contains( 'page-template-property') ) {
			let propertyMap = wrapper.querySelector( '#map' );
			if ( propertyMap ) {
				propertyMap.remove();
			}
		}

		propertyPanel.append( wrapper );

		propertyPanel.offsetHeight; // force reflow
		propertyPanel.classList.remove( 'is-loading' );

		vestorTemplates.forceCalculatorChange();

		let filters = vestorFilters.getActiveFilters();
		filters.property = response.id;
		if ( isDebug ) {
			filters.debug = true;
		}

		let url = new URL( response.url );
		if ( isDebug ) {
			url.searchParams.add( 'debug', 'true' );
		}

		let currentUrl = new URL( document.location );
		if ( currentUrl.searchParams.has( 'property' ) ) {
			let hash = currentUrl.hash;
			history.replaceState(
				{
					filters,
					property: response,
					view: 'property'
				},
				response.title,
				response.url.toString() + hash
			);
		} else {
			history.pushState(
				{
					filters,
					property: response,
					view: 'property'
				},
				response.title,
				response.url.toString()
			);
		}

		document.title = response.title;

		document.dispatchEvent( new CustomEvent( 'vestorfilters|property-loaded', { detail: response } ) );

	};



	const maybeGoBack = (e) => {

		const { state } = e;

		vfDebug( state );

		let mismatch = true;
		let propertyId;
		if ( state.filters ?? null ) {
			let activeFilters = JSON.stringify( vestorFilters.getActiveFilters() );
			if ( state.filters.property ) {
				propertyId = state.filters.property;
				delete state.filters.property;
			}
			let stateFilters = JSON.stringify( state.filters );
			if ( activeFilters == stateFilters ) {
				mismatch = false;
			}
			vfDebug( activeFilters, stateFilters, mismatch );
		}
		if ( mismatch ) {
			vfDebug( 'filter mismatch' );
		}

		const view = ( state.view ?? '' );

		if ( view !== 'gallery' ) {
			let gallery = vestorProperty.getCurrentGallery();
			if ( gallery ) {
				vfDebug( 'try to close gallery' );
				gallery.closeGallery();

				if ( view === 'property' ) {
					let currentProperty = vestorProperty.getCurrentProperty();
					if ( ( currentProperty.id ?? null ) == state.property.id ) {
						return;
					}
				}
			}
		}

		if ( view === 'gallery' ) {
			let currentProperty = vestorProperty.getCurrentProperty();
			vfDebug( 'try to open gallery', currentProperty, state.property );
			if ( ( currentProperty.id ?? null ) == state.property.id ) {
				vfDebug( 'time to open' );
				let gallery = vestorProperty.getCurrentGallery();
				if ( gallery ) {
					gallery.openGallery();
				} else {
					vestorProperty.setupCurrentProperty( { detail: state.property } );
				}
				return;
			}
		}

		if ( view === 'property' || view === 'gallery' ) {
			window.vestorResultsHash = null;
			propertyPanel.innerHTML = '';
			document.body.classList.add( 'showing-property-panel' );

			vestorFilters.setFilterDefaults( state.filters );
			let url = new URL( vestorFilters.getCurrentSearchURL() );
			url.searchParams.set( 'property', propertyId );
			if ( view === 'gallery' ) {
				url.hash = 'gallery';
			}
			if ( isDebug ) {
				url.searchParams.set( 'debug', 'true' );
			}
			history.replaceState(
				state,
				'',
				url.toString()
			);

			if ( mismatch ) {
				document.body.classList.add( 'whole-page-refresh' );
				vestorSearch.downloadResults( { 
					onComplete: () => {
						document.body.classList.remove( 'whole-page-refresh' );
					},
					forceProperty: currentlySelected ? currentlySelected.property.ID : null
				} );
			} else {
				highlightProperty( { listIndex: state.property.listIndex, propertyPage: state.property, dontZoom: true } );
			}
		}

		if ( view === 'search' ) {

			if ( ! document.body.classList.contains( 'page-template-template-search' ) ) {
				document.location.reload(false);
			}

			if ( document.body.classList.contains( 'showing-property-panel' ) ) {
				document.body.classList.remove( 'showing-property-panel' );
				document.body.classList.add( 'is-going-away' );
				setTimeout( () => {
					document.body.classList.remove( 'is-going-away' );
				}, 1000 );
				vfDebug( 'back to search' );
				if ( mismatch ) {
					vestorFilters.setFilterDefaults( state.filters );
					resetSelectedPin();
					vestorSearch.downloadResults( { forceProperty: null } );
				} else {
					vfDebug( 'no mismatch', state.showMap );
					if ( state.showMap === false ) {
						let selectedProperty = mapResultsPanel.querySelector( `[data-property-id].is-selected` );
						vfDebug( 'scroll to', selectedProperty );
						void( document.offsetHeight );
						if ( selectedProperty ) {
							setTimeout( () => {
								
								selectedProperty.scrollIntoView(true);
							}, 500 );
							
						}
					}
				}
			} else {
				window.sessionStorage.clear();
				vestorFilters.setFilterDefaults( state.filters );
				resetSelectedPin();

				vestorSearch.downloadResults( { forceProperty: null } );
			}

		}

	}
	window.addEventListener( 'popstate', maybeGoBack );


	const maybeRefreshViewport = () => {

		if ( editingMode || document.body.classList.contains( 'is-loading' ) || document.body.classList.contains( 'showing-property-panel' ) ) {
			return;
		}

		let hash = vestorSearch.getCurrentHash();
		vfDebug( 'maybe refresh viewport ', hash, maxZoom );

		if ( ! hash ) {
			return;
		}

		if ( ! maxZoom && ! hasPointsOutside ) {
			maxZoom = vestorMap.getZoom();
		}

		/*if ( maxZoom && vestorMap.getZoom() <= maxZoom ) {
			vfDebug( 'do property reset' );
			resetPropertiesFromCache();
			return;
		}*/

		document.body.classList.add( 'pins-loading' );

		vfDebug( `do refresh where bounds:${mapboundsChanged}, zoom:${vestorMap.getZoom()}, max:${maxZoom}` );

		downloadViewportPins( {
			hash: hash,
			doAfter: refreshViewport
		} );

		mapboundsChanged = false;

	};

	const resetPropertiesFromCache = () => {

		reinstallProperties( {
			properties: vestorSearch.getAvailableProperties(),
			map:        currentShape,
			vfSort:     vestorFilters.getActiveFilters().vf ?? null
		} );
		if ( viewportProperties ) {
			installPins( vestorMap, viewportProperties );
		}

	};

	document.addEventListener( 'vestorfilters|reset-map', () => {

		resetSelectedPin();
		
		downloadViewportPins( {
			doAfter: refreshViewport
		} );

	} );

	const refreshViewport = ( { target } ) => {

		if ( target.status === 200 ) {

			vfDebug( 'refresh viewport' );

			vestorSearch.setupMapData( target.response, false );

			

		} else {
			vfDebug( 'error downloading properties inside viewport', target );
			// show error

		}

	};

	const redrawMap = ( { detail } ) => {


		vfDebug( 'redraw map', detail );

		lastRedraw = detail;

		let mapContainer = document.querySelector( '.vf-search__map' );
		if ( mapContainer && detail.properties.length === 0 ) {
			mapContainer.classList.remove( 'no-results' );
		}

		let hasFriends = document.getElementById( 'friend-favorite-modal' );

		if ( ! googleReady || redrawing ) {
			setTimeout( () => {
				redrawMap( { detail } );
			}, 500 );
			return;
		}

		if ( detail.search_maps ) {
			highlightMaps = detail.search_maps;
		} else {
			highlightMaps = [];
		}

		redrawing = true;
		document.body.classList.add( 'redrawing' );

		siteBaseUrl = detail.base_url;

		let currentMap;
		let selectedMapBounds = new google.maps.LatLngBounds();

		mapResultsPanel.parentNode.scrollTo( 0, 0 );

		for( let newMap of highlightMaps ) {

			if ( typeof newMap == 'object' ) {

				vfDebug( newMap.id, vestorMapPolys[ newMap.id ] );

				let shape;
				if ( ! vestorMapPolys[ newMap.id ] ) {
					let bounds = new google.maps.LatLngBounds();
					let points = [];
					for( let coords of newMap.coords ) {
						let point = { lat: parseFloat( coords[0] ), lng: parseFloat( coords[1] ) };
						points.push( point );
						bounds.extend( point );
						selectedMapBounds.extend( point );
					}
					shape = new google.maps.Polygon({
						paths: points,
						editable: false,
						strokeWeight: 3,
						strokeColor: '#d89033',
						fillColor: '#231f20',
						fillOpacity: 0,
						zIndex: 2,
						visible: true,
					});
					
					vestorMapPolys[ newMap.id ] = {
						shapes: [ shape ],
						type:   'custom',
						bounds: bounds
					};
					vfDebug( '--create new' );
				} else {
					selectedMapBounds.extend( vestorMapPolys[ newMap.id ].bounds.getNorthEast() );
					selectedMapBounds.extend( vestorMapPolys[ newMap.id ].bounds.getSouthWest() );
					shape = vestorMapPolys[ newMap.id ].shapes[0];
					vfDebug( '--use existing' );
				}

				if ( currentShape ) {
					vfDebug( currentShape );
					currentShape.setOptions( {
						visible: false
					} );
				}

				currentMap = shape;
				currentMapId = newMap.id;

				if ( activeMap ) {
					activeMap.shapes[0].setOptions( {
						visible: false
					} );
					activeMap = null;
				}

				vfDebug( '--attach retrieved map' );

				shape.setMap( vestorMap );
				shape.setOptions( {
					visible: true
				} );

				activeMap = vestorMapPolys[ newMap.id ];
				if ( ! editingMode ) {
					currentShape = shape;
					currentShape.setOptions( {
						visible: true
					} );
				}

				let nameField = document.getElementById( 'map_name' );
				if ( nameField && newMap.name ) {
					nameField.value = newMap.name;
				}

				vfDebug( '--assign poly bounds to custom map id' );
				polyBounds = selectedMapBounds;
			}
		}

		vfDebug( '--has current map of', currentMap, currentShape );
		if ( ! currentMap && currentShape ) {
			vfDebug( 'reset current shape' );
			currentShape.setOptions( {
				visible: false
			} );
			currentShape = null;
			activeMap = null;
		}

		//let dataRange;
		//let rangeMin, rangeMax, dataRange;
		//let	priceMin, priceMax, priceRange = {};

		let reinstallArgs = {
			properties: detail.properties, 
			map:        currentShape,
			vfSort:     vestorFilters.getActiveFilters().vf ?? ''
		};
		if ( ( detail.resetBounds ?? null ) === false ) {
			vfDebug( 'no reset', polyBounds );
			reinstallArgs.boundingBox = vestorMap.getBounds();
			shouldResetBounds = false;
		}
		vfDebug( '--redraw reinstall', reinstallArgs );
		
		hasPointsOutside = false;
		let newBounds = reinstallProperties( reinstallArgs );

		if ( detail.initial ) {
			resetViewportPins();
			maxZoom = null;
		}
		if ( detail.subset === 'yes' ) {
			hasPointsOutside = true;
		}

		if ( viewportProperties ) {
			
			//vestorMap.fitBounds( newBounds );

			if ( ( detail.resetBounds ?? null ) === true || shouldResetBounds ) {
				vfDebug( '--reset bounds', polyBounds );
				if ( polyBounds ) {
					vfDebug( '--use poly bounds', polyBounds.getNorthEast(), polyBounds.getSouthWest() );
					newBounds = polyBounds;
				}

				vestorMap.fitBounds( newBounds );
			}
			if ( detail.initial && ! hasPointsOutside ) {
				maxZoom = vestorMap.getZoom();
			}

			installPins( vestorMap, viewportProperties );

		} else {
			vfDebug( 'no pins' );
			installPins( vestorMap, [] );

			if ( polyBounds ) {
				vfDebug( '--use poly bounds', polyBounds.getNorthEast(), polyBounds.getSouthWest() );
				vestorMap.fitBounds( polyBounds );
				maxZoom = null;
				hasPointsOutside = false;
			}
		}

		shouldResetBounds = false;
		redrawing = false;
		document.body.classList.remove( 'redrawing' );

		vfDebug( '--done redrawing', detail );

		/*if ( newPolys.length > 0 ) {
			newBounds = installPolygons( vestorMap, newPolys );
		}
		if ( newBounds ) {

		}*/

		if ( detail.showProperty ) {
			vfDebug( '--installing preloaded property' );
			//loadPropertyPage( detail.showProperty, true );
			//highlightProperty( { propertyId: detail.showProperty, dontZoom: true } );

		} else {
			let newState = {
				view: 'search',
				filters: vestorFilters.getActiveFilters(),
				showMap: searchTemplateContainer.classList.contains( 'is-map-view' )
			};

			let replaceUrl = new URL( vestorFilters.getCurrentSearchURL() );
			if ( isDebug ) {
				replaceUrl.searchParams.set( 'debug', 'true' );
			}
			
			if ( document.body.classList.contains( 'showing-property-panel' ) ) {
				vfDebug( '--push history', replaceUrl );
				history.pushState(
					newState,
					'',
					replaceUrl
				);
				document.body.classList.remove( 'showing-property-panel' );
			} else {
				
				vfDebug( '--update history', replaceUrl );
				history.replaceState(
					newState,
					'',
					replaceUrl
				);
			}

		}


	}
	document.addEventListener( 'vestorfilters|redraw-map', redrawMap );

	const rebuildList = ( { detail } ) => {


		vfDebug( 'redraw map', detail );

		let mapContainer = document.querySelector( '.vf-search__map' );
		if ( mapContainer && detail.properties.length === 0 ) {
			mapContainer.classList.remove( 'no-results' );
		}

		let reinstallArgs = {
			properties: detail.properties, 
			vfSort:     vestorFilters.getActiveFilters().vf ?? '',
			listOnly:   true,
		};
		reinstallProperties( reinstallArgs );

		installPins( null, viewportProperties, true );

		vfIsListMode = true;

	};
	document.addEventListener( 'vestorfilters|rebuild-list', rebuildList );

	const getPropertyPage = ( property ) => {

		if ( ! property.listIndex || property.hidden ) {
			return false;
		}

		let pageNo = Math.floor( property.listIndex / 60 );
		return pageNo;

	};

	const displayPage = ( properties, pageNumber, vf ) => {

		let start = pageNumber * 60;
		let limit = 60;
		let end = start + limit;
		let vfFlags;

		let selectedInfo;
		if ( currentlySelected ) {
			let activePropertyContainer = mapResultsPanel.querySelector( '[data-active-property]' );
			if ( activePropertyContainer ) {
				selectedInfo = activePropertyContainer.outerHTML;
			}
		}

		mapResultsPanel.innerHTML = '';

		if ( selectedInfo ) {
			mapResultsPanel.innerHTML = selectedInfo;
			mapResultsPanel.childNodes[0].childNodes[0].property = currentlySelected.property;
		}

		if ( ! vf ) {
			vf = vestorFilters.getActiveFilters().vf;
		}

		let vfTitleObj = mapResultsPanel.parentNode.querySelector( 'button[aria-controls="vestorfilter-sidebar-panel__inside"] > span' );
		let vfTitleDesc = mapResultsPanel.parentNode.querySelector( 'p' );

		vfTitleObj.innerHTML = 'VestorFilter&trade;';
		vfTitleDesc.innerHTML = '';

		vfDebug( 'display page', pageNumber, vf, start, end );
		if ( vf ) {
			vfDebug( vfFormats[vf] );
			vfFlags = vfFormats[vf];
			vfFlags.filter = ( vf === 'lotm' || vf === 'notm' ) ? 'onmarket' : vf;

			let optionOpt = document.querySelector( `li[data-filter-value="${vf}"]` );
			if ( optionOpt ) {
				vfTitleObj.innerHTML = optionOpt.querySelector( '.label' ).innerHTML;
				vfTitleDesc.innerHTML = optionOpt.querySelector( '.description' ).innerHTML;
			}

		}

		var added = 0;
		for( let i = start; i < end; i ++ ) {
			if ( ! ( properties[i] ?? false ) ) {
				vfDebug( '--no property with index ', i );
				end = i;
				break;
			}
			/*if ( ! ( properties[i].container ?? false ) ) {
				continue;
			}*/
			if ( properties[i].hidden ?? false ) {
				continue;
			}
			let newListItem = document.createElement( 'li' );
			newListItem.classList.add( 'map-search-results__item' );
			newListItem.innerHTML = vestorTemplates.replaceHandlebars( 
				vfBlockTemplate, 
				properties[i],
				vfFlags
			);

			mapResultsPanel.append( newListItem );
			newListItem.childNodes[0].property = properties[i];

			let propertyImages = newListItem.querySelectorAll( 'img[data-src]' );
			for ( let propertyImage of propertyImages ) {
				vestorImageUtil.attachToObservers( propertyImage );
			}
			added++;
		}

		vfDebug( `--added ${added} to page` );

		while( mapResultsPanel.nextElementSibling ) {
			mapResultsPanel.nextElementSibling.remove(); 
		}

		let description = document.createElement('p');
		description.classList.add( 'vf-search__map-pagination-count' );
		description.innerHTML = `Showing ${start + 1} - ${end} of ${properties.length}`;

		mapResultsPanel.parentNode.append( description );

		if ( pageNumber > 0 ) {
			let prevBtn = document.createElement( 'button' );
			prevBtn.classList.add( 'btn');
			prevBtn.classList.add( 'vf-search__map-pagination-btn' );
			prevBtn.classList.add( 'vf-search__map-pagination-btn--prev' );
			prevBtn.dataset.changePage = parseInt( pageNumber )  - 1;
			prevBtn.innerHTML = 'Previous';
			prevBtn.type = 'button';

			mapResultsPanel.parentNode.append( prevBtn );
		}
		if ( properties.length > start + limit && ! properties[start + limit].hidden ) {
			let nextBtn = document.createElement( 'button' );
			nextBtn.classList.add( 'btn');
			nextBtn.classList.add( 'vf-search__map-pagination-btn' );
			nextBtn.classList.add( 'vf-search__map-pagination-btn--next' );
			nextBtn.dataset.changePage = parseInt( pageNumber ) + 1;
			nextBtn.innerHTML = 'Next';
			nextBtn.type = 'button';

			mapResultsPanel.parentNode.append( nextBtn );
		}

		currentPage = pageNumber;

	};

	const reinstallProperties = ( { properties, map, boundingBox, vfSort, listOnly } ) => {

		if ( currentPage === null ) {
			currentPage = 0;
		}

		propertyCache = [];
		propertyTypes = [];

		let newViewportProperties = [];

		vfDebug( 'reinstall', properties );

		let bounds = new google.maps.LatLngBounds();

		let count = 0, added = 0;
		let foundCurrentSelection = false;
		if ( ! properties ) {
			return;
		}
		for( let property of properties ) {
			if ( ! property.property_type ) {
				continue;
			}
			/*let propertyContainer = document.createElement( 'fragment' );
			let content = property.block_cache;
			if ( ! content || content.length === 0 ) {
				continue;
			}*/

			/*if ( count > 700 ) {
				if ( viewportPins.hasOwnProperty( property.ID ) && viewportPins[property.ID] ) {
					viewportPins[property.ID].pin.setMap(null);
					delete viewportPins[property.ID];
				}
				continue;
			}*/
			let coords;
			if ( ! listOnly ) {
				coords = new google.maps.LatLng( {
					lat: parseFloat( property.lat ) / 1000000,
					lng: parseFloat( property.lon ) / 1000000
				} );

				property.coords = coords;
			}

			if ( map && ! google.maps.geometry.poly.containsLocation( coords, map ) ) {
				property.hidden = true;
			} else if ( boundingBox && ! boundingBox.contains( coords ) ) {
				property.hidden = true;
				hasPointsOutside = true;
			} else if ( property.hidden ) {
				property.hidden = parseInt( property.hidden );
				property.hidden = ( property.hidden > 0 );
			}

			if ( currentlySelected && currentlySelected.property.ID === property.ID ) {
				property.hidden = false;
				foundCurrentSelection = true;
			}

			if ( selectedMLS && property.MLSID === selectedMLS ) {
				property.hidden = false;
				selectedMLS = property;
			}

			if ( property.hidden === true ) {
				//property.container = null;
				continue;
			}

			if ( ! listOnly && minmaxBoundRange.contains( coords ) ) {
				bounds.extend( coords );
			}

			let types = property.property_type.split(',');
			for( let type of types ) {
				propertyTypes[type] = true;
			}

			if ( typeof property.data_cache == 'string' ) {
				property.data_cache = JSON.parse( property.data_cache );
			}
			if ( typeof property.block_cache == 'string' ) {
				property.block_cache = JSON.parse( property.block_cache );
			}

			property.url = siteBaseUrl + property.MLSID + '/' + property.slug;

			property.title = property.address;

			/*

			content = vestorTemplates.replaceHandlebars( content, property, { vf: true } );

			propertyContainer.innerHTML = content;
			for( let type of types ) {
				propertyContainer.childNodes[0].classList.add( 'property-type--' + type );
			}

			*/

			//let address = makeAddress( propertyContainer.querySelectorAll('.address span') );

			//property.container = propertyContainer.childNodes[0];
			//property.container.property = property;

			//propertyCache.push( propertyContainer.childNodes[0] );
			newViewportProperties.push( property );
			added ++;

			count ++;

		}
		vfDebug( `--setup ${added}` );

		if ( selectedMLS && typeof selectedMLS === 'string' ) {
			selectedMLS = null;
		}

		if ( ! foundCurrentSelection ) {
			resetSelectedPin();
		}

		viewportProperties = newViewportProperties; //vestorSearch.sortProperties( , vfSort );

		if ( selectedMLS ) {
			loadPropertyPage( selectedMLS.ID, true );
		}

		afterPinInstall = () => {
			if ( selectedMLS ) {
				vfDebug( '----pin install highlight mls' );
				let listIndex = selectedMLS.listIndex;
				highlightProperty( { listIndex, dontZoom: true } );
				selectedMLS = null;
				
			} else if ( currentlySelected
				&& currentlySelected.marker
				&& currentlySelected.marker.getVisible() ) {
					vfDebug( '----pin install highlight index' );
					let listIndex = currentlySelected.property.listIndex;
					highlightProperty( { listIndex, dontZoom: true } );
			} else {
				vfDebug( '----pin install display new page' );
				displayPage( newViewportProperties, 0 );
			}
		};


		//maybeLoadPhotos( { currentTarget: mapResultsPanel.parentNode } );
		/*debounce( 
			{
				method: maybeLoadPhotos,
				priority: true
			},
			500,
			{ currentTarget: mapResultsPanel.parentNode, queryPhotos: true } 
		);*/

		void( document.offsetHeight ); // force reflow

		return bounds;

	};

	const makeAddress = ( addressComponents ) => {

		if ( ! addressComponents || addressComponents.length === 0 ) {
			return '';
		}

		let address = addressComponents[0].innerText.trim();
		for( let i = 1; i < addressComponents.length; i++ ) {
			address += ', ' + addressComponents[i].innerText.trim();
		}
		return address;

	};

	const switchMapMode = ( newMode ) => {

		let currentUrl = new URL( window.location.href );

		if ( newMode === 'list' ) {
			searchTemplateContainer.classList.add( 'is-list-view' );
			searchTemplateContainer.classList.remove( 'is-map-view' );

			currentUrl.searchParams.set( 'mode', 'list' );

		} else {
			searchTemplateContainer.classList.remove( 'is-list-view' );
			searchTemplateContainer.classList.add( 'is-map-view' );
			vfDebug( 'window scroll reset' );
			window.scrollTo( 0, 0 );

			currentUrl.searchParams.set( 'mode', 'map' );
			if ( vfIsListMode ) {
				vfIsListMode = false;
				loadResultsMap();
			}

		}
		vestorFilters.updatePageUrl( currentUrl );

		//debounce( maybeLoadPhotos, 500, { currentTarget: mapResultsPanel.parentNode } );

	};

	const maybeSwitchMapMode = ( { target } ) => {

		let self = target.closest( '[data-switch-mode]' );
		if ( ! self ) {
			return;
		}
		switchMapMode( self.dataset.switchMode );

	};

	const maybeLoadCustomMap = ( e ) => {

		let mapId, mapFilters;
		let mapListToggle = e.target.closest( '[aria-controls="user-maps-selector"]' );
		if ( mapListToggle ) {

			let mapList = document.getElementById( 'user-maps-selector' );
			if ( mapList && mapList.childElementCount === 1 ) {
				mapId = 'new';
				e.preventDefault();
				e.stopPropagation();
			} else {
				return;
			}


		} else {

			let self = e.target.closest( '[data-custom-map]' );
			if ( ! self ) {
				return;
			}

			mapId = self.dataset.customMap;
			mapFilters = self.dataset.filters;
			if ( mapFilters ) {
				mapFilters = JSON.parse( mapFilters );
			}

		}

		if ( window.vestorAccount.getNonce() ) {

			loadCustomMap( mapId, mapFilters );

		} else {

			vestorAccount.afterSuccessfulAuth( ( responseData ) => {

				downloadCustomMaps( {
					onComplete: () => {
						loadCustomMap( mapId, mapFilters );
					}
				} );

			} );

			toggleModal( 'login-modal', true );

		}

	};

	const downloadCustomMaps = ( { onComplete, onError } ) => {

		let nonce = window.vestorAccount.getNonce();
		if ( ! nonce ) {
			window.vestorMessages.show( 'Please log in to complete this action.', 'error', 4000 );
		}

		let customMapXHR = new XMLHttpRequest();
		customMapXHR.open( "GET", vfEndpoints.userMaps );
		customMapXHR.setRequestHeader( 'X-WP-Nonce', nonce );
		customMapXHR.send();
		customMapXHR.responseType = 'json';
		customMapXHR.addEventListener( "load", installCustomMaps );
		if ( onComplete ) {
			customMapXHR.onComplete = onComplete;
		}
		if ( onError ) {
			customMapXHR.onError = onError;
		}

	}

	const installCustomMaps = ( { target } ) => {

		if ( target.status != 200 ) {
			if ( target.onError ) {
				target.onError();
			}
			return;
		}

		let mapList = document.getElementById( 'user-maps-selector' );
		if ( mapList ) {

			let existingBtns = mapList.querySelectorAll( 'button' );
			for ( let btn of existingBtns ) {
				if ( btn.dataset.customMap != 'new' ) {
					btn.remove();
				}
			}

			if ( target.response.maps ) {
				for( let map of target.response.maps ) {
					let newBtn = document.createElement( 'button' );
					newBtn.dataset.customMap = map.id;
					newBtn.dataset.filters = JSON.stringify( map.filters );
					newBtn.type = 'button';
					newBtn.classList.add( 'user-maps-selector__list-item' );
					newBtn.innerHTML = map.name;
		
					mapList.prepend( newBtn );
				}
			}
		}

		if ( target.onComplete ) {
			target.onComplete();
		}

	};

	const loadCustomMap = ( mapId, filters ) => {

		resetSelectedPin();

		if ( mapId === 'new' ) {
			if ( currentShape ) {
				currentShape.setOptions( {
					visible: false
				} );
			}
			fallbackShape = null;
			currentMapId = null;
			startEditingMap();
			return;
		}

		if ( mapId === 'edit' ) {
			editingMode = true;
			downloadViewportPins( { installAfter: true } );

			vfDebug( 'edit', currentShape );
			let path = [];
			for( let point of currentShape.getPath().getArray() ) {
				path.push( { lat: point.lat(), lng: point.lng() } );
			}
			vfDebug( 'fallback shape', path );
			fallbackShape = new google.maps.Polygon( {
				paths: path,
				editable: false,
				strokeWeight: 3,
				strokeColor: '#d89033',
				fillColor: '#231f20',
				fillOpacity: 0,
				zIndex: 2,
				visible: true,
			} );
			doneDrawing( currentShape, true );
			return;
		}

		if ( mapId === 'cancel' ) {
			editingMode = false;
			setTimeout( cancelDrawing, 200 );
			return;
		}

		if ( mapId === 'delete' ) {
			if ( window.confirm( 'Are you sure you want to delete the active map?' ) ) {
				deleteCurrentMap();
			}
			return;
		}

		vfDebug( 'current map id', currentMapId );


		if ( editingMode && mapId === 'save' ) {
			toggleModal( 'save-map-modal', true );
			return;
		}

		currentMapId = mapId;
		if ( currentShape ) {
			currentShape.setOptions( {
				visible: false
			} );
		}

		document.body.classList.add( 'whole-page-refresh' );

		let toggleBtns = document.querySelectorAll( '[data-custom-map]' );
		for ( let btn of toggleBtns ) {
			if ( btn.dataset.customMap == mapId ) {
				btn.classList.add( 'is-selected' );
				mapResultsPanel.classList.add( 'has-selection' );
			} else {
				btn.classList.remove( 'is-selected' );
				mapResultsPanel.classList.remove( 'has-selection' );
			}
		}

		if ( filters ) {
			vestorFilters.setFilterDefaults( filters );
		}

		vestorSearch.setLocationFilter( {
			locationId: mapId,
			append: false,
			remove: false,
			user: vfAccount.id,
			dontThrowEvent: true
		} );

		window.sessionStorage.clear();

		resetSelectedPin();
		resetViewportPins();
		shouldResetBounds = true;
		vestorSearch.downloadResults();

		let selectorBtn = document.querySelector( 'button[aria-controls="user-maps-selector"]' );
		a11yToggleExpand( { target: selectorBtn, forced: false } );

		document.body.classList.add( 'custom-map-loaded' );
		document.body.classList.add( 'whole-page-refresh' );

		document.body.classList.remove( 'location-changed' );

	};

	const boundsChanged = () => {

		vfDebug( 'bounds changed', vestorMap.getZoom() );

		getPolys( vestorMap.getZoom() );

		if ( currentlySelected && ! vestorMap.getBounds().contains( currentlySelected.marker.getPosition() ) ) {
			resetSelectedPin();
		}

		if ( editingMode ) {
			vfDebug( 'maybe download ', lastZoom, vestorMap.getZoom() );
			maybeDownloadViewportPins();
		} else {
			vfDebug( 'maybe refresh ', mapboundsChanged, lastZoom, maxZoom, vestorMap.getZoom() );
			maybeRefreshViewport();
		}

	};

	const maybeDownloadViewportPins = () => {

		if ( ! editingMode ) {
			return;
		}

		downloadViewportPins( { doAfter: refreshViewport } );

	};

	const downloadViewportPins = ( args ) => {

		let hash, installAfter, doAfter;
		if ( args ) {
			hash = args.hash ?? null;
			installAfter = args.installAfter ?? null;;
			doAfter = args.doAfter ?? null;
		}

		if ( vestorMap.getZoom() < 8 ) {
			return;
		}

		if ( ! hash ) {
			hash = null;
		}
		if ( ! installAfter ) {
			installAfter = false;
		}
		if ( ! doAfter ) {
			doAfter = installTemporaryPins;
		}

		vfDebug( 'download viewport pins', hash, installAfter );

		let currentBounds = vestorMap.getBounds();

		if ( hash ) {
			let cacheresponse = JSON.parse( window.sessionStorage.getItem( 'searchresponse' ) || null );
			let activeProperties = vestorSearch.getAvailableProperties();
			vfDebug( '--test cache', activeProperties, cacheresponse.total );

			if ( mapboundsChanged ) {
				let oldbounds = sessionStorage.getItem( 'searchgeo' );
				if ( ! oldbounds ) {
					activeProperties = null;
				} else {
					oldbounds = JSON.parse( oldbounds );
					if ( oldbounds.bounds ) {
						let min = new google.maps.LatLng( parseFloat( oldbounds.bounds.min[0] ) / 1000000, parseFloat( oldbounds.bounds.min[1] ) / 1000000 );
						let max = new google.maps.LatLng( parseFloat( oldbounds.bounds.max[0] ) / 1000000, parseFloat( oldbounds.bounds.max[1] ) / 1000000 );
						if ( currentBounds.contains(min) || currentBounds.contains(max) ) {
							activeProperties = null;
						}
					} else {
						activeProperties = null;
					}
				}
				
			}

			if ( activeProperties && cacheresponse && parseInt( cacheresponse.total ) <= activeProperties.length ) {
				vfDebug( '--skip viewport download' );
				cacheresponse.properties = activeProperties;
				doAfter( { target: { response: cacheresponse, status: 200 } } );
				return;
			}
		}

		let ne = currentBounds.getNorthEast(),
			sw = currentBounds.getSouthWest();

		let currentFilters = vestorFilters.getActiveFilters();

		let lookupUrl = new URL( vfEndpoints.search );
		for ( let key in currentFilters ) {
			if ( key === 'location' ) {
				continue;
			}
			lookupUrl.searchParams.set( key, currentFilters[key] );
		}
		if ( hash ) {
			lookupUrl.searchParams.set( 'hash', hash );
		}
		lookupUrl.searchParams.set( 'zoom', vestorMap.getZoom() );
		lookupUrl.searchParams.set( 'geo', ne.lat() + ',' + ne.lng() + ',' + sw.lat() + ',' + sw.lng() );
		if ( currentlySelected ) {
			lookupUrl.searchParams.set( 'forced', currentlySelected.property.ID );
		}

		if ( viewportXHR ) {
			viewportXHR.abort();
		}
		viewportXHR = new XMLHttpRequest();
		viewportXHR.reinstall = installAfter ?? false;


		viewportXHR.open( "GET", lookupUrl );
		let nonce = window.vestorAccount.getNonce();
		if ( nonce ) {
			viewportXHR.setRequestHeader( 'X-WP-Nonce', nonce );
		}
		viewportXHR.send();
		viewportXHR.responseType = 'json';
		viewportXHR.addEventListener( "load", doAfter );

	};

	const installTemporaryPins = ( { target } ) => {

		let { response, status } = target;

		vfDebug( 'install temp pins', response );

		if ( status != 200 ) {
			// todo: throw error plz
			return;
		}

		drawViewportProperties = response.properties;

		if ( target.reinstall ) {
			reinstallProperties( {
				properties: drawViewportProperties, 
				map: null,
				vfSort: vestorFilters.getActiveFilters().vf ?? ''
			} );
			installPins( vestorMap, viewportProperties );
		}

	}

	const startEditingMap = ( args ) => {

		editingMode = true;

		document.body.classList.add( 'is-editing-map' );

		if ( vestorMapPolys ) {
			for( let mapId in vestorMapPolys ) {

				if ( vestorMapPolys[mapId].shapes ) {
					for( let shape of vestorMapPolys[mapId].shapes ) {
						if ( vestorMapPolys[mapId].type === 'custom' ) {
							shape.setMap(null);
						} else {
							shape.setOptions( {
								fillOpacity: 0,
								strokeOpacity: 0.25
							} );
						}
					}
				}
			}
		}
		//installPins( vestorMap, [] );

		let currentBounds = vestorMap.getBounds();
		vestorFilters.disableControls();

		//vfDebug( currentBounds );

		mapKeyPanel.innerHTML = '<p>Click anywhere to begin drawing...</p>';

		if ( ! args ) {
			//let ne = currentBounds.getNorthEast(),
			//	sw = currentBounds.getSouthWest();

			//let boundsWidth = sw.lng() - ne.lng();
			//let boundsHeight = sw.lat() - ne.lat();

			//vfDebug( ne.toJSON(), sw.toJSON(), boundsWidth, boundsHeight );

			/*startingPoly = [
				{ lat: ne.lat() + boundsHeight * 0.25, lng: ne.lng() + boundsWidth * 0.25 },
				{ lat: ne.lat() + boundsHeight * 0.25, lng: ne.lng() + boundsWidth * 0.75 },
				{ lat: ne.lat() + boundsHeight * 0.75, lng: ne.lng() + boundsWidth * 0.75 },
				{ lat: ne.lat() + boundsHeight * 0.75, lng: ne.lng() + boundsWidth * 0.25 }
			];*/

			if ( currentShape ) {
				currentShape.setMap(null);
				currentShape = null;
			}
			if ( currentMapId ) {
				currentMapId = null;
			}
			

			drawingManager = new google.maps.drawing.DrawingManager({
				drawingMode: google.maps.drawing.OverlayType.POLYGON,
				drawingControl: false,
				polygonOptions: {
				  fillColor: "#000000",
				  fillOpacity: 0,
				  strokeWeight: 3,
				  clickable: false,
				  editable: false,
				  zIndex: 999999,
				},
			});
			drawingManager.setMap( vestorMap );
			google.maps.event.addListener( drawingManager, 'polygoncomplete', doneDrawing );

			editingMode = 'new';

			downloadViewportPins( {
				installAfter: true
			} );

			document.body.classList.add( 'save-disallowed' );

		} else {

			let startingPoly = args.poly;

			currentShape = new google.maps.Polygon({
				paths: startingPoly,
				editable: true,
			});

			currentShape.setMap( vestorMap );
			currentShape.addListener( 'mouseup', () => { debounce( editingShapeChanged, 500 ) } );
			//currentShape.addListener( 'remove_at', () => { debounce( editingShapeChanged, 500 ) } );
			//currentShape.addListener( 'set_at',    () => { debounce( editingShapeChanged, 500 ) } );

			mapKeyPanel.innerHTML = '<p>Adjust your map boundaries by dragging points</p>';

			editingMode = 'existing';

			downloadViewportPins( {
				installAfter: true
			} );

		}



		//

	};

	const doneDrawing = ( polygon, existingShape ) => {

		currentShape = polygon;

		if ( ! editingMode ) {
			return;
		}
		vfDebug( 'done drawing', viewportProperties );

		document.body.classList.add( 'is-editing-map' );

		currentShape.setEditable( true );
		currentShape.addListener( 'mouseup', () => { debounce( editingShapeChanged, 500 ) } );
		//currentShape.addListener( 'remove_at', () => { debounce( editingShapeChanged, 500 ) } );
		//currentShape.addListener( 'set_at',    () => { debounce( editingShapeChanged, 500 ) } );

		if ( drawingManager ) {
			drawingManager.setDrawingMode(null);
		}

		document.body.classList.remove( 'save-disallowed' );
		mapKeyPanel.innerHTML = '<p>Adjust your map boundaries by dragging points</p>';

		if ( drawViewportProperties && ! existingShape ) {
			reinstallProperties( {
				properties: drawViewportProperties, 
				map:        null,
				vfSort:     vestorFilters.getActiveFilters().vf ?? ''
			} );
			resetViewportPins();
			installPins( vestorMap, viewportProperties );
		}

	};

	const cancelDrawing = () => {

		vfDebug( 'cancel attempt' );
		editingMode = false;

		if ( drawingManager ) {
			drawingManager.setDrawingMode(null);
		}
		let isNew = true;

		if ( fallbackShape && currentShape && currentMapId ) {
			vfDebug( 'try to fall back', currentMapId );
			currentShape.setMap(null);
			currentShape.setEditable(false);
			currentShape.setOptions( {
				visible: false
			} );

			vestorMapPolys[currentMapId].shapes = [ fallbackShape ];
			currentShape = vestorMapPolys[currentMapId].shapes[0];

			fallbackShape.setMap( vestorMap );
			fallbackShape = null;
			isNew = false;

		} else if ( currentShape ) {
			vfDebug( 'reset old shape', currentMapId );
			currentShape.setEditable(false);
			currentShape.setMap(null);
			currentShape = null;

			if ( activeMap ) {
				activeMap.shapes[0].setOptions( {
					visible: false
				} );
				activeMap = null;
			}

			currentMapId = null;
		}
		
		document.body.classList.remove( 'is-editing-map' );
		document.body.classList.remove( 'save-disallowed' );

		let selectorBtn = document.querySelector( 'button[aria-controls="user-maps-selector"]' );
		a11yToggleExpand( { target: selectorBtn, forced: false } );

		vestorFilters.enableControls();

		if ( vestorMapPolys ) {
			for( let mapId in vestorMapPolys ) {
				if ( vestorMapPolys[mapId].shapes ) {
					for( let shape of vestorMapPolys[mapId].shapes ) {
						shape.setOptions( {
							strokeOpacity: 1
						} );
					}
				}
			}
		}

		
	
		vestorSearch.resetFilters();
		return;
	


	};

	const editingShapeChanged = () => {

		return;
		
		if ( viewportProperties ) {
			reinstallProperties( {
				properties: drawViewportProperties, 
				map:        currentShape,
				vfSort:     vestorFilters.getActiveFilters().vf ?? ''
			} );
			installPins( vestorMap, viewportProperties );
			mapResultsPanel.parentNode.scrollTo(0,0);
		}

	};

	const deleteCurrentMap = () => {

		vfDebug( 'delete id', currentMapId );

		let formData = new FormData();
		let nonce = window.vestorAccount.getNonce();
		formData.set( 'map_id', currentMapId ) ;

		let saveMapXHR = new XMLHttpRequest();
		saveMapXHR.open( "POST", '/wp-json/vestorfilter/v1/search/delete-map' );
		if ( nonce ) {
			saveMapXHR.setRequestHeader( 'X-WP-Nonce', nonce );
		}
		saveMapXHR.send( formData );
		saveMapXHR.responseType = 'json';

		let openMapBtn = document.querySelectorAll( `button[data-custom-map="${currentMapId}"]` );
		for( let btn of openMapBtn ) {
			btn.remove();
		}

		currentShape.setMap(null);
		currentShape = null;
		//currentMapId = null;

		let locationInput = document.querySelector( 'input[data-filter-value="location"]' );
		if ( locationInput ) {
			let defaultLocation = locationInput.dataset.default;
			vestorSearch.setLocationFilter( { 
				locationId: defaultLocation, 
				append: false, 
				remove: false 
			} );
		}

		downloadViewportPins( {
			doAfter: refreshViewport
		} );


		return false;

	};

	const maybeSaveCustomMap = ( e ) => {

		let form = e.target.closest( '[data-save-map-form]' );
		if ( ! form ) {
			return;
		}

		vfDebug( 'map id', currentMapId );

		e.preventDefault();
		e.stopPropagation();

		let points = [];
		let path = currentShape.getPath();
		for (let i = 0; i < path.getLength(); i++) {
			const point = path.getAt(i);
			const newPoint = [ point.lat().toFixed(5), point.lng().toFixed(5) ];
			if ( i > 0 && newPoint[0] == points[i-1][0] && newPoint[1] == points[i-1][1] ) {
				continue;
			}
			points.push( newPoint );
		}

		let formData = new FormData( form );
		let nonce = window.vestorAccount.getNonce();

		formData.set( 'coords', JSON.stringify( points ) ) ;
		formData.set( 'filters', JSON.stringify( vestorFilters.getActiveFilters() ) ) ;
		if ( currentMapId ) {
			formData.set( 'map_id', currentMapId );
		}

		let saveMapXHR = new XMLHttpRequest();
		saveMapXHR.open( "POST", '/wp-json/vestorfilter/v1/search/save-map' );
		if ( nonce ) {
			saveMapXHR.setRequestHeader( 'X-WP-Nonce', nonce );
		}
		saveMapXHR.send( formData );
		saveMapXHR.responseType = 'json';
		saveMapXHR.addEventListener( "load", doneSavingMap );

		return false;

	};

	const doneSavingMap = ( { target } ) => {

		const { response, status } = target;
		if ( status != 200 ) {
			// todo throw error
		}

		let selectorBtn = document.querySelector( 'button[aria-controls="user-maps-selector"]' );
		a11yToggleExpand( { target: selectorBtn, forced: false } );

		

		if ( vestorMapPolys ) {
			for( let mapId in vestorMapPolys ) {
				if ( vestorMapPolys[mapId].shapes ) {
					for( let shape of vestorMapPolys[mapId].shapes ) {
						shape.setOptions( {
							fillOpacity: 0,
							strokeOpacity: 1
						} );
					}
				}
			}
		}

		if ( response.new ) {

			let mapList = document.getElementById( 'user-maps-selector' );
			if ( mapList ) {

				let selectedBtn = mapList.querySelector( 'button.is-selected' );
				if ( selectedBtn ) {
					selectedBtn.classList.remove( 'is-selected' );
					mapResultsPanel.classList.remove( 'has-selection' );
				}

				if ( response.mine ) {

					let newBtn = document.createElement( 'button' );
					newBtn.dataset.customMap = response.id;
					newBtn.dataset.filters = JSON.stringify( response.filters );
					newBtn.type = 'button';
					newBtn.classList.add( 'user-maps-selector__list-item' );
					newBtn.classList.add( 'is-selected' );
					mapResultsPanel.classList.add( 'has-selection' );
					newBtn.innerHTML = response.name;

					mapList.prepend( newBtn );

				}


			}

		}

		document.body.classList.remove( 'save-disallowed' );
		document.body.classList.remove( 'is-editing-map' );
		toggleModal( 'save-map-modal', false );
		editingMode = false;

		currentMapId = response.id;

		currentShape.setEditable( false );;
		currentShape.setOptions( {
			strokeColor: '#d89033'
		} );
		vestorFilters.enableControls();

		document.body.classList.remove( 'location-changed' );
		document.body.classList.add( 'whole-page-refresh' );

		vestorSearch.setLocationFilter( {
			locationId: response.id,
			append: false,
			remove: false,
			user: response.user,
			dontThrowEvent: true,
		} );
		vestorSearch.downloadResults();

	};

	

	document.addEventListener( 'click', maybeSwitchMapMode );

	mapStyle = [
		{
			"featureType": "all",
			"elementType": "geometry.fill",
			"stylers": [
				{
					"weight": "2.00"
				}
			]
		},
		{
			"featureType": "all",
			"elementType": "geometry.stroke",
			"stylers": [
				{
					"color": "#9c9c9c"
				}
			]
		},
		{
			"featureType": "all",
			"elementType": "labels.text",
			"stylers": [
				{
					"visibility": "on"
				}
			]
		},
		{
			"featureType": "landscape",
			"elementType": "all",
			"stylers": [
				{
					"color": "#f2f2f2"
				}
			]
		},
		{
			"featureType": "landscape",
			"elementType": "geometry.fill",
			"stylers": [
				{
					"color": "#ffffff"
				}
			]
		},
		{
			"featureType": "landscape.man_made",
			"elementType": "geometry.fill",
			"stylers": [
				{
					"color": "#ffffff"
				}
			]
		},
		{
			"featureType": "poi",
			"elementType": "all",
			"stylers": [
				{
					"visibility": "off"
				}
			]
		},
		{
			"featureType": "road",
			"elementType": "all",
			"stylers": [
				{
					"saturation": -100
				},
				{
					"lightness": 45
				}
			]
		},
		{
			"featureType": "road",
			"elementType": "geometry.fill",
			"stylers": [
				{
					"color": "#eeeeee"
				}
			]
		},
		{
			"featureType": "road",
			"elementType": "labels.text.fill",
			"stylers": [
				{
					"color": "#7b7b7b"
				}
			]
		},
		{
			"featureType": "road",
			"elementType": "labels.text.stroke",
			"stylers": [
				{
					"color": "#ffffff"
				}
			]
		},
		{
			"featureType": "road.highway",
			"elementType": "all",
			"stylers": [
				{
					"visibility": "simplified"
				}
			]
		},
		{
			"featureType": "road.arterial",
			"elementType": "labels.icon",
			"stylers": [
				{
					"visibility": "off"
				}
			]
		},
		{
			"featureType": "transit",
			"elementType": "all",
			"stylers": [
				{
					"visibility": "off"
				}
			]
		},
		{
			"featureType": "water",
			"elementType": "all",
			"stylers": [
				{
					"color": "#46bcec"
				},
				{
					"visibility": "on"
				}
			]
		},
		{
			"featureType": "water",
			"elementType": "geometry.fill",
			"stylers": [
				{
					"color": "#c8d7d4"
				}
			]
		},
		{
			"featureType": "water",
			"elementType": "labels.text.fill",
			"stylers": [
				{
					"color": "#070707"
				}
			]
		},
		{
			"featureType": "water",
			"elementType": "labels.text.stroke",
			"stylers": [
				{
					"color": "#ffffff"
				}
			]
		}
	];

	return {
		getMapBounds,
		getMapZoom,
		resetMapBounds,
		resetSelectedPin,
		isDebugMode: () => { return isDebug },
		mapStyle,
		getPropertyIcon,
		getPropertyStep
	};

} )();