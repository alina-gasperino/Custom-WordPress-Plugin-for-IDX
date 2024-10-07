var vestorSearch = ( function() {
	"use strict";

	let searchForm;
	let searchResults;
	let submitBtn;
	let searchBtn;
	let resetBtns;
	let searchInput;
	let searchOptions;
	let propertyTemplate;
	let mapResults;
	let mapResultsWrapper;
	let searchPanels;
	let resultsPanel;
	let fallbackFilters = null;
	let shouldReset = false;
	let maybeResetNextTime = false;
	let isHomepage = false;
	
	let resultsRequest,
		searchRequest,
		sessionProperties,
		currentHash = null;

	const filterChangeEvent = new Event( 'vestorfilters|filter-change' );
	const resetChangeEvent = new CustomEvent( 'vestorfilters|filter-change', { detail: { reset: true } } );
	const locationChangeEvent = new CustomEvent( 'vestorfilters|filter-change', { detail: { location: true } } );

	let searchXHR;

	const initializeSearch = () => {

		vfDebug( 'init search' );

		searchPanels = document.querySelectorAll( '#vf-search-panel,#vf-filter-panel__location' );
		searchForm = document.querySelector( '[data-vestor-search]' );

		if ( ! searchForm ) {
			return;
		}

		mapResultsWrapper = document.querySelector('.vf-search__map');
		mapResults = document.querySelector( '[data-results-map]' );
		searchResults = document.querySelector( '[data-vestor-results]' );
		resultsPanel = document.querySelector( '[data-vf-results]' );
		submitBtn = searchForm.querySelector( 'button[type="submit"]' );
		resetBtns = document.querySelectorAll( 'button[type="reset"]' );
		searchInput = searchForm.querySelectorAll( 'input[data-search="query"]' );
		searchOptions = searchForm.querySelectorAll( '[data-search-autocomplete]' );
		propertyTemplate = document.querySelector( '[data-vestor-property]' );
		searchBtn = document.querySelector( 'button[aria-controls="vf-search-panel"]' );
		

		searchForm.classList.add( 'ready' );

		if ( window.vfEndpoints.exact ) {
			searchXHR = new XMLHttpRequest();
			searchXHR.open( 'GET', window.vfEndpoints.exact );
			searchXHR.responseType = 'json';
			searchXHR.addEventListener( 'load', populateExactMatchSearch );
		}

		searchForm.addEventListener( 'reset', resetFilters );

		searchForm.addEventListener( 'submit', function( e ) {

			vfDebug( 'lets submit' );

			if ( searchForm && searchForm.classList.contains( 'is-location-options-open' ) ) {

				let selectedOption = searchForm.querySelector( 'input[data-search="id"]:checked' );

				if ( ! selectedOption ) {
					let firstOption = searchForm.querySelector( 'input[data-search="id"],button[data-search="keyword"]' );
					firstOption.focus();


					if ( firstOption.dataset.search === 'keyword' ) {
						addSearchKeyword( { currentTarget: firstOption } );
					} else {
						firstOption.checked = true;
					}

				}

				//document.body.classList.remove( 'is-toggle-open' );

				closeAutocompletePanel();

				e.preventDefault();
				e.stopPropagation();
				return false;

			}

			let isEmpty = true;
			for( let input of searchInput ) {
				if ( input.value.length > 0 ) {
					isEmpty = false;
				}
			}
			if ( isEmpty ) {
				vfDebug( 'closing everything' );
				closeAutocompletePanel();
				closeAllExpanders();
				//e.preventDefault();
				//e.stopPropagation();
				//return false;
			}

			if ( searchForm.classList.contains( 'is-location-options-open' ) || submitBtn.disabled ) {
				e.preventDefault();
				return false;
			}

		} );

		

		document.addEventListener( 'vestorfilters|filter-change', ( { detail } ) => { 
			if ( detail && detail.location && mapResults ) {
				return;
			}
			if ( window.innerWidth < 768 && mapResults ) {
				return;
			}

			window.sessionStorage.clear();
			if ( mapResults ) {
				document.body.classList.add( 'whole-page-refresh' );
				vestorMaps.resetSelectedPin();
			}

			if ( detail && detail.reset ) {
				shouldReset = false;
				maybeResetNextTime = false;
			}

			downloadResults();
		} );
		document.addEventListener( 'vestorfilters|filter-change',  enableReset );

		if ( ! fallbackFilters ) {
			fallbackFilters = vestorFilters.getActiveFilters();
		}

		for ( let input of searchInput ) {
			input.name = '';
			input.addEventListener( 'keyup', changeInputQuery );
		}

		for ( let panel of searchPanels ) {
			panel.addEventListener( 'click', ( e ) => {
				e.stopPropagation();
			} );
		}

		let doneBtns = searchForm.querySelectorAll( '.vf-search__nav.close')
		for( let btn of doneBtns ) {
			btn.addEventListener( 'click', submit );
			
		}


		//for ( let panel of searchOptions ) {
		//	panel.addEventListener( 'keydown', autocompleteKeyboardNav );
		//}

		if ( searchBtn ) {
			searchBtn.addEventListener( 'click', () => {
				let thisSearchInput = document.querySelector( '#vf-search-panel input[data-search="query"]' );
				if ( thisSearchInput ) {
					thisSearchInput.focus();
				}
			} );
		}

		if ( searchInput ) {
			for( let input of searchInput ) {
				input.addEventListener( 'click', ( e ) => {
					e.currentTarget.focus();
					e.preventDefault();
					e.stopPropagation();
				} );
			}
		}

		let locationBtns = document.querySelectorAll( `button.vf-search__location-value` );
		if ( locationBtns ) {
			for( let btn of locationBtns ) {
				btn.addEventListener( 'click', removeLocationFromSearch );
			}
		}

		let keywordBtns = document.querySelectorAll( `button.vf-search__keyword-value` );
		if ( keywordBtns ) {
			for( let btn of keywordBtns ) {
				btn.addEventListener( 'click', removeKeywordFromSearch );
			}
		}

		/*window.addEventListener( 'click', ( e ) => {
			if ( searchForm.classList.contains( 'is-location-options-open' ) ) {
				searchForm.classList.remove( 'is-location-options-open' );
				closeAllExpanders();
			}
		} );*/

		if ( ! mapResults && locationBtns ) {
			window.defaultLocationBtns = [];
			for( let btn of locationBtns ) {
				window.defaultLocationBtns.push( btn.cloneNode( true ) );
			}

		}

		if ( searchForm.dataset.vestorSearch === 'homepage' ) {
			//vfDebug( 'trying to get results' );
			isHomepage = true;
			downloadResults();
		}

		document.dispatchEvent( new Event( 'vestorfilters|search-init' ) );

	};

	const submit = ( e ) => {

		if ( mapResults ) {

			let vfBtn = document.querySelector( '.vf-vestorfilters__toggle' );
			if ( vfBtn ) {
				vfBtn.setAttribute( 'aria-expanded', 'false' );
				vfBtn.nextElementSibling.setAttribute( 'aria-hidden', 'true' );
				vfBtn.nextElementSibling.classList.remove( 'show' );
				if ( e ) {
					e.stopPropagation();
				}
			}

			vfDebug( 'lets download' );

			window.sessionStorage.clear();

			document.body.classList.add( 'whole-page-refresh' );

			let locationToggle = document.getElementById( 'vf-filter-toggle__location' );
			if ( locationToggle && locationToggle.parentNode.classList.contains( 'active' ) ) {
				locationToggle.parentNode.classList.remove( 'active' );
				a11yToggleExpand( { target: locationToggle, forced: false } );
			}

			if ( window.innerWidth < 768 ) {
				closeAllExpanders();
				let moreBtn = document.querySelector( 'button[aria-controls="vf-filter-panel__more"]' );
				//console.log( moreBtn );
				if ( moreBtn ) {
					moreBtn.parentNode.classList.remove( 'active' );
					a11yToggleExpand( { target: moreBtn, forced: false } );
				}
			}

			downloadResults();

			

		}

	};

	const resetFilters = ( e ) => {

		vfDebug( 'do filter reset' );

		if ( ! fallbackFilters ) {
			resetFilterQuery();
		} else {
			vestorFilters.setFilterDefaults( fallbackFilters );
		}

		resetBtns = document.querySelectorAll( 'button[type="reset"]' );
		for( let resetBtn of resetBtns ) {
			resetBtn.disabled = true;
		}

		document.dispatchEvent( resetChangeEvent );

		//document.body.classList.add( 'whole-page-refresh' );
		//downloadResults();

	}

	const enableReset = ( e ) => {

		if ( e && e.detail && e.detail.reset ) {
			searchForm.classList.remove( 'has-changed' );
		} else {
			searchForm.classList.add( 'has-changed' );
		}
		resetBtns = document.querySelectorAll( 'button[type="reset"],button.reset' );
		for( let resetBtn of resetBtns ) {
			resetBtn.disabled = false;
		}
		void( document.offsetHeight ); // force reflow

		//vestorFilters.collapseFilterList( true );

	};

	const resetSomeFilters = ( newLocation ) => {

		let except = [ 'vf', 'property-type', 'status', 'location' ];

		let allFilters = searchForm.querySelectorAll( '.vf-morefilters input[data-filter-value]' );
		for ( let filterInput of allFilters ) {
			if ( except.includes( filterInput.dataset.key ) ) {
				continue;
			}
			filterInput.checked = false;
		}
		let navItems = searchForm.querySelectorAll( '.nav-item.active' );
		for ( let item of navItems ) {
			if ( except.includes( item.dataset.key ) ) {
				continue;
			}
			item.classList.remove( 'active' );
			let valueLabel = item.querySelector( '.dropdown-toggle .value' );
			if ( valueLabel ) {
				valueLabel.innerHTML = '';
			}
		}

		let newFilters = {
			vf: 'ppsf',
			'property-type': 'sf',
			status: 'active'
		};

		for( let key in newFilters ) {
			let existing = searchForm.querySelector( `input[name="${key}"]:checked` );
			if ( existing ) {
				newFilters[key] = existing.value;
			}
		}

		newFilters.location = newLocation;

		vestorFilters.setFilterDefaults( newFilters );

	}

	const resetFilterQuery = ( except ) => {

		searchOptions = searchForm.querySelectorAll( '[data-search-autocomplete]' );
		let defaultLocation = searchOptions[0].dataset.default;

		let allFilters = searchForm.querySelectorAll( '.vf-morefilters input[data-filter-value]' );
		for ( let filterInput of allFilters ) {
			filterInput.checked = false;

		}
		let navItems = searchForm.querySelectorAll( '.nav-item.active' );
		for ( let item of navItems ) {
			item.classList.remove( 'active' );
			let valueLabel = item.querySelector( '.dropdown-toggle .value' );
			if ( valueLabel ) {
				valueLabel.innerHTML = '';
			}
		}

		let defaults = {
			vf: 'ppsf',
			'property-type': 'all',
			status: 'active'
		};
		if ( defaultLocation ) {
			defaults[ 'location' ] = defaultLocation;
			let searchLocation = document.querySelector( '#search-location' );
			if ( searchLocation && searchLocation.dataset.default ) {
				searchLocation.placeholder = searchLocation.dataset.default;
			}

			for ( let input of searchInput ) {

				let hiddenInput = input.parentNode.querySelector( 'input[name="location"]' );
				hiddenInput.value = defaultLocation;

				let locationBtns = input.parentNode.querySelectorAll( `button.vf-search__location-value` );
				if ( locationBtns ) {
					for( let btn of locationBtns ) {
						btn.remove();
					}
				}
				if ( window.defaultLocationBtns ) {
					for( let btn of window.defaultLocationBtns ) {
						let newBtn = btn.cloneNode( true );
						newBtn.addEventListener( 'click', removeLocationFromSearch );
						input.parentNode.insertBefore( newBtn, input );
					}
				}
			}
		}

		vestorFilters.setFilterDefaults( defaults );

		document.dispatchEvent( filterChangeEvent );

	};

	const processDataResponse = ( { target }, resetBounds ) => {

		searchForm.classList.remove( 'is-searching' );
		document.body.classList.remove( 'whole-page-refresh' );
		

		let { response, status } = target;

		if ( status !== 200 || ! response ) {
			vestorMessages.show( response.message, 'error', 2500 );
			return;
		}
		if ( isHomepage || ! mapResults ) {
			setupMapData( response, false, false );
			return;
		}

		if ( response.total === 0 ) {
			maybeResetNextTime = true;
			vestorMessages.show( 'No properties could be found within your specified search paramaters.', 'error', 2500 );
			return;
		}

		if ( response.search_hash && response.search_nonce ) {
			searchForm.dataset.vestorSearch = response.search_hash;
			searchForm.dataset.vestorNonce = response.search_nonce;
		}

		if ( ! response.hash ) {
			currentHash = null;
			window.sessionStorage.clear();
		}
		
		if ( resetBounds || maybeResetNextTime ) {
			document.body.classList.add( 'whole-page-refresh' );
			vestorMaps.resetMapBounds( response.bounds, response.center, downloadResults );
		} else {
			setupMapData( response, resetBounds, true );
		}

		maybeResetNextTime = false;

	}

	const getShareUrl = () => {

		let response = window.sessionStorage.getItem( 'searchlink' );
		if ( ! response ) {
			return null;
		}
		try {
			let url = new URL( response );
		} catch {
			return null;
		}
		return response;

	};

	const setupMapData = ( response, resetBounds, resetPins ) => {

		if ( ! document.body.classList.contains( 'page-template-property' ) ) {
			let total = parseInt( response.total ) + 0;
			/*if ( total >= 700 ) {
				total += '+';
			}*/
			submitBtn.querySelector( 'span' ).innerHTML = total + ' properties found';
		}

		searchForm.classList.remove( 'is-searching' );
		document.body.classList.remove( 'whole-page-refresh' );
		submitBtn.disabled = false;

		vfDebug( 'setup map data', resetBounds, resetPins, response );

		if ( response.share ) {
			window.sessionStorage.setItem( 'searchlink', response.share );
		}

		if ( response.hash ) {

			try {
				currentHash = window.sessionStorage.getItem( 'searchhash' );
				window.sessionStorage.setItem( 'searchresponse', JSON.stringify( {
					base_url: response.base_url,
					map: true,
					search_maps: response.search_maps,
					title: response.title,
					total: response.total,
					url: response.url,
				} ) );
				if ( currentHash !== response.hash ) {
					window.sessionStorage.clear();
					
					window.sessionStorage.setItem( 'searchhash',     response.hash );
					window.sessionStorage.setItem( 'searchexpires',  response.expires );
					window.sessionStorage.setItem( 'searchgeo',      JSON.stringify( { 
						bounds: response.bounds, 
						center: response.center 
					} ) );
					window.sessionStorage.setItem( 'searchfilters',  JSON.stringify( response.filters ) );
					window.sessionStorage.setItem( 'searchresponse', JSON.stringify( {
						base_url: response.base_url,
						map: true,
						search_maps: response.search_maps,
						title: response.title,
						total: response.total,
						url: response.url,
						share: response.share,
					} ) );
					window.sessionStorage.setItem( 'searchcache',    JSON.stringify( response.properties ) );
					window.sessionStorage.setItem( 'searchlink', response.share );
				}

			} catch {};

			sessionProperties = response.properties;

		} else {
			
			currentHash = null;
		}

		if ( mapResults ) {

			window.scrollTo(0,0);
			document.body.classList.add( 'at-top' );

			let currentUrl = new URL( window.location.href );
			if ( currentUrl.searchParams.has( 'property' ) ) {
				response.showProperty = currentUrl.searchParams.get( 'property' );
			}

			if ( response.error ) {
				vestorMessages.show( response.error, 'error', 4000 );
				return;
			}

			if ( response.total == 0 ) {
				maybeResetNextTime = true;
				vestorMessages.show( 'No properties could be found within your specified search paramaters.', 'error', 2500 );
				return;
			}

			response.resetBounds = resetBounds;

			if ( resetPins ) {
				response.resetPins = resetPins;
			}

			/*let currentFilters = vestorFilters.getActiveFilters();
			if ( currentFilters.vf ) {
				response.url += '&vf=' + currentFilters.vf;
			}

			vfDebug( 'redraw map now', currentFilters );*/

			if ( mapResultsWrapper.classList.contains( 'is-map-view' ) ) {
				document.dispatchEvent( new CustomEvent( 'vestorfilters|redraw-map', { detail: response } ) );
			} else {
				document.dispatchEvent( new CustomEvent( 'vestorfilters|rebuild-list', { detail: response } ) );
			}

		} else {

			vfDebug( 'just reset count' );

			let total = parseInt( response.total ) + 0;

			submitBtn.querySelector( 'span' ).innerHTML = total + ' properties found';
			

			searchForm.classList.remove( 'is-searching' );
			document.body.classList.remove( 'whole-page-refresh' );
			submitBtn.disabled = false;

		}


	};

	/*const mergeCacheProperties = ( newProperties ) => {

		let properties = window.sessionStorage.getItem( 'searchcache' );
		if ( ! properties ) {
			properties = sessionProperties;
		} else {
			properties = JSON.parse( properties );
		}

		for( let property of newProperties ) {
			let found = properties.find( ({ ID }) => ID === property.ID );
			if ( ! found ) {
				properties.push( Object.assign( {}, property ) );
			}
		}

		return properties;

	};*/

	const getAvailableProperties = () => {

		return sessionProperties;

	};
	
	const getCurrentHash = () => {

		return window.sessionStorage.getItem( 'searchhash' );

	};

	const getCacheCount = () => {

		let response = JSON.parse( window.sessionStorage.getItem( 'searchresponse' ) || null );
		if ( ! response ) {
			return null;
		}
	
		return response.total;

	};

	const sortProperties = ( properties, filter ) => {

		return properties;

		let vf = vfFormats[filter] ?? false;
		if ( ! vf ) {
			vfDebug( 'no rules to sort', filter );
			return properties;
		}

		if ( ! vf.rules || ! vf.rules.data ) {
			vfDebug( 'no data in rules', vf );
			return properties;
		}

		properties.sort( (a, b) => {

			for( let rule of vf.rules.data ) {
				let aData = a.data_cache[rule.key] ?? false;
				let bData = b.data_cache[rule.key] ?? false;
				a.hidden = false;
				b.hidden = false;

				if ( aData === false ) {
					a.hidden = true;
					return 2;
				}
				if ( bData === false ) {
					b.hidden = true;
					return -2;
				}
				if ( rule.comparison ?? null ) {
					if ( rule.comparison === '>=' ) {
						if ( aData < rule.value ) {
							a.hidden = true;
							return 2;
						}
						if ( bData < rule.value ) {
							b.hidden = true;
							return -2;
						}
					}
				}
				if ( aData === bData ) {
					continue;
				}
				if ( rule.order ?? null ) {
					if ( rule.order === 'ASC' ) {
						return aData < bData ? -1 : 1;
					}
					if ( rule.order === 'DESC' ) {
						return aData > bData ? -1 : 1;
					}
				}
			}
			return 0;
		} );

		return properties;

	};

	const downloadResults = ( args ) => {

		if ( searchResults || ! searchForm ) {
			return;
		}

		let detail = {}, onComplete, noGeo = false;
		if ( args ) {
			detail = args.detail ?? {};
			onComplete = args.onComplete ?? null;
			noGeo = args.noGeo ?? false;
		} else {
			args = {};
		}


		if ( ! isHomepage ) {
			fallbackFilters = vestorFilters.getActiveFilters();
		} else {
			let activeFilters = vestorFilters.getActiveFilters();
			if ( ! activeFilters.location && ! ( vfAllowLocationless ?? false ) ) {
				return;
			}
		}

		
		let resetBounds = shouldReset;
		if ( shouldReset ) {
			shouldReset = false;
		}

		searchForm.classList.add( 'is-searching' );

		if ( submitBtn && ! document.body.classList.contains( 'page-template-property' ) ) {
			submitBtn.disabled = true;
			submitBtn.querySelector( 'span' ).innerHTML = 'Searching...';
		}

		let query = vestorFilters.getFilterQuery( searchForm );

		vfDebug( 'downloading results', args, query );

		let locationChanged = false;
		if ( document.body.classList.contains( 'location-changed' ) ) {
			document.body.classList.remove( 'location-changed' );
			locationChanged = true;
		}
		/*let locationToggle = document.getElementById( 'vf-filter-toggle__location' );
		if ( locationToggle && locationToggle.parentNode.classList.contains( 'active' ) ) {
			locationToggle.parentNode.classList.remove( 'active' );
			a11yToggleExpand( { target: locationToggle, forced: false } );
		}*/

		let properties, response, expires;
		currentHash = window.sessionStorage.getItem( 'searchhash' );

		if ( ! detail.bounds && mapResults && mapResultsWrapper.classList.contains( 'is-map-view' ) ) {
			detail.bounds = vestorMaps.getMapBounds().toJSON();
			detail.zoom   = vestorMaps.getMapZoom();
		}
		if ( noGeo || locationChanged ) {
			detail = {};
			window.sessionStorage.clear();
			resetBounds = true;
			currentHash = null;
		}

		if ( ! mapResults ) {
			currentHash = null;
		}

		let withinBounds = true;

		if ( currentHash ) {

			let cacheFilters = JSON.parse( window.sessionStorage.getItem( 'searchfilters' ) || 'null' );
			let cacheFiltersSorted = {};
			Object.keys(cacheFilters).sort().map(i=>cacheFiltersSorted[i]=cacheFilters[i]);
			
			if ( ! cacheFilters ) {
				currentHash = null;
				response = null;
				currentHash = null;
				window.sessionStorage.clear();
			} else {

				let currentFilters = vestorFilters.getActiveFilters();
				let sortedFilters = {};
				Object.keys(currentFilters).sort().map(i=>sortedFilters[i]=currentFilters[i]);

				//vfDebug( JSON.stringify( sortedFilters ), JSON.stringify( cacheFiltersSorted ) );
				if ( JSON.stringify( sortedFilters ) === JSON.stringify( cacheFiltersSorted ) ) {
					let geo = JSON.parse( window.sessionStorage.getItem( 'searchgeo' ) || 'null' );
					if ( detail && detail.bounds ) {
						vfDebug( 'download results within', geo, detail.bounds );
			
						if ( ! geo || ! geo.bounds ||
							detail.bounds.south * 1000000 > geo.bounds.max[0] ||
							detail.bounds.north * 1000000 < geo.bounds.min[0] ||
							detail.bounds.east  * 1000000 > geo.bounds.max[1] ||
							detail.bounds.west  * 1000000 < geo.bounds.min[1] ) {
							withinBounds = false;
						}
			
					}
	
					if ( withinBounds ) {
	
						try {
							properties = JSON.parse( window.sessionStorage.getItem( 'searchcache' ) || 'null' );
							expires = window.sessionStorage.getItem( 'searchexpires' );
							if ( properties && expires && expires < Date.now() ) {
								response = JSON.parse( window.sessionStorage.getItem( 'searchresponse' ) || 'null' );
								if ( response ) {
									response.filters    = JSON.parse( window.sessionStorage.getItem( 'searchfilters' ) );
									response.hash       = currentHash;
									response.properties = properties;
									response.initial    = true;
									response.expires    = expires;
									response.subset     = 'no';
								}
							} else {
								window.sessionStorage.clear();
							}
						} catch {
							response = null;
							currentHash = null;
							window.sessionStorage.clear();
						};
					}
				} else {
					currentHash = null;
					response = null;
					window.sessionStorage.clear();
				}
			}
		}

		if ( currentHash && properties && response ) {
			vfDebug( 'cache response', response );
			document.dispatchEvent( 
				new Event( 'vestorfilters|cache-response-complete' )
			);
			setupMapData( response, false, true );
			if ( onComplete ) {
				onComplete();
			}

			return;
		}

		if ( currentHash ) {
			query += `&hash=${currentHash}`;
		}

		if ( detail.bounds ) {
			query += `&geo=${detail.bounds.south},${detail.bounds.west},${detail.bounds.north},${detail.bounds.east}`;
			if ( detail.zoom ) {
				query += '&zoom=' + detail.zoom;
			}
		}

		if ( args.forceProperty ) {
			query += '&forced=' + args.forceProperty;
		}
		if ( detail.selected ) {
			query += '&forced=' + detail.selected;
		}

		let resultsArgs = {
			query: query,
			onComplete: (e) => {
				vfDebug( 'live response', e, noGeo, resetBounds );
				processDataResponse( e, noGeo || resetBounds );
				document.dispatchEvent( 
					new Event( 'vestorfilters|live-response-complete' )
				);

				if ( onComplete ) {
					onComplete(e);
				}
			}
		};

		if ( propertyTemplate && searchForm ) {
			let url = new URL( searchForm.getAttribute( 'action' ) );
			resultsArgs.url = url.pathname;
		}

		loadResults( resultsArgs, true );

	};

	const loadResults = ( { query, wait, onComplete } ) => {

		if ( resultsRequest && ! wait ) {
			resultsRequest.abort();
		}
		if ( resultsRequest && resultsRequest.readyState !== 4 && wait ) {
			vfDebug( 'waiting to install', query );
			setTimeout( loadResults, 500, { query, wait, onComplete } );
			return;
		}

		let lookupUrl = '/wordpress/wp-json/vestorfilter/v1/search/map-data';
		//if ( document.body.dataset.mapReady === 'true' ) {
		//	document.body.dataset.mapReady = 'false';
		//	return;
		//}

		if ( query && query.length > 0 ) {
			lookupUrl += '?' + query;
		}

		vfDebug( lookupUrl );

		resultsRequest = new XMLHttpRequest();
		resultsRequest.open( "GET", lookupUrl );
		let nonce = window.vestorAccount.getNonce();
		if ( nonce ) {
			resultsRequest.setRequestHeader( 'X-WP-Nonce', nonce );
		}
		
		resultsRequest.send();
		resultsRequest.responseType = 'json';

		resultsRequest.addEventListener( "load", onComplete );

	};

	const changeInputQuery = ( e ) => {

		//searchForm.classList.add( 'ready' );
		//submitBtn.querySelector( 'span' ).innerHTML = '48 properties found';

		let { currentTarget, keyCode } = e;

		searchForm.classList.remove( 'is-location-selected' );
		for ( let input of searchInput ) {
			input.placeholder = '';
			input.value = currentTarget.value;
			if ( currentTarget.value.length > 0 ) {
				input.name = 'location_query';
			}
		}
		let selectedOption = searchForm.querySelector( 'input[data-search="id"]:checked' );
		if ( selectedOption ) {
			selectedOption.checked = false;
		}

		if ( keyCode === 8 && currentTarget.value === '' ) {
			vestorSearch.submit();
			return;
		}

		let optionList = currentTarget.parentNode.nextElementSibling;
		let inputPanel = currentTarget.parentNode.parentNode;

		if ( keyCode === 13 && optionList.classList.contains( 'live-search' ) ) {

			let firstOption = optionList.querySelector( 'button[data-search="keyword"],a.autocomplete-option' );
			firstOption.focus();
			e.preventDefault();
			e.stopPropagation();

			if ( firstOption.tagName === 'A' ) {
				window.location.href = firstOption.href;
			} else {
				addSearchKeyword( { currentTarget: firstOption } );
			}
			return;

		}

		// down arrow
		if ( searchForm.classList.contains( 'is-location-options-open' ) && ( keyCode == 40 || keyCode === 9 ) ) {

			if ( ! optionList.classList.contains( 'live-search' ) ) {

				let firstOption = optionList.querySelector( '[data-search]' );
				firstOption.focus();
				if ( firstOption.tagName === 'INPUT' ) {
					firstOption.checked = true;
				}

			} else {

				let firstOption = optionList.querySelector( 'a.autocomplete-option' );
				firstOption.focus();

			}

			return;

		}

		if ( currentTarget.value.length < 3 ) {
			for ( let optionList of searchOptions ) {
				optionList.innerHTML = '';
			}
			searchForm.classList.remove( 'is-searching' );
			searchForm.classList.remove( 'is-location-options-open' );
			return;
		}

		populateSearchOptions( currentTarget.value, inputPanel );

		if ( searchForm.classList.contains( 'is-location-options-open' ) && keyCode == 13 ) {
			//vfDebug('hello');
			for ( let option of searchForm.querySelectorAll( 'input[data-search]' ) ) {
				if ( currentTarget.value === option.dataset.value ) {
					option.focus();
					if ( option.dataset.search === 'keyword' ) {
						addSearchKeyword( { currentTarget: option } );
					} else {
						option.checked = true;
						closeAutocompletePanel();
					}
				}
			}
			return false;
		}

	};

	/*const autocompleteKeyboardNav = ( e ) => {

		let { currentTarget, keyCode } = e;

		// down or tab or up
		if ( keyCode === 40 || keyCode === 9 || keyCode === 38 ) {

			let activeElement = document.activeElement;
			if ( ! activeElement ) {
				return;
			}
			if ( ! activeElement.classList.contains( 'autocomplete-option' ) ) {
				return;
			}

			if ( keyCode !== 38 && activeElement.nextElementSibling ) {
				activeElement.nextElementSibling.focus();
			}

			if ( keyCode === 38 && activeElement.previousElementSibling ) {
				activeElement.previousElementSibling.focus();
			}

			e.stopPropagation();
			e.preventDefault();

			return false;

		}

	};*/

	const changeAutocompleteSelection = ( { currentTarget } ) => {

		if ( ! currentTarget ) {
			return;
		}

		let radioFor = currentTarget.getAttribute( 'for' );

		if ( ! radioFor.length ) {
			return;
		}

		let input = document.getElementById( radioFor );
		input.checked = true;

		document.body.classList.add( 'location-changed' );

		closeAutocompletePanel();

	};

	const navigateAutocompleteSelection = ( e ) => {

		let { keyCode, currentTarget } = e;

		let parent = currentTarget.tagName === 'A' ? currentTarget : currentTarget.parentNode;

		//if ( keyCode && [9, 13, 32].indexOf( keyCode ) !== -1 ) {

		e.stopPropagation();
		e.preventDefault();

		if ( [9, 40].indexOf( keyCode ) !== -1 ) {
			if ( parent.nextElementSibling ) {
				if ( parent.nextElementSibling.tagName === 'A' ) {
					parent.nextElementSibling.focus();
				} else {
					let next = parent.nextElementSibling.querySelector('[data-search],a.autocomplete-option');
					next.focus();
					if ( next.tagName === 'INPUT' ) {
						next.checked = true;
					} else {
						let currentChecked = parent.parentNode.querySelector('input:checked');
						if ( currentChecked ) {
							currentChecked.checked = false;
						}
					}
				}
				return;
			}
		}

		if ( keyCode === 38 ) {
			if ( parent.previousElementSibling ) {
				if ( parent.previousElementSibling.tagName === 'A' ) {
					parent.previousElementSibling.focus();
				} else {
					let prev = parent.previousElementSibling.querySelector('[data-search],a.autocomplete-option');
					prev.focus();
					if ( prev.keyCode === 'INPUT' ) {
						prev.checked = true;
					}
				}
				return;
			} else {
				let form = currentTarget.closest('form');
				form.querySelector('#search-location').focus();
			}
		}

		if ( [13, 32].indexOf( keyCode ) !== -1 ) {

			if ( currentTarget.tagName === 'A' ) {
				window.location.href = currentTarget.href;
				return;
			}

			if ( currentTarget.dataset.search === 'keyword' ) {
				addSearchKeyword(e);
			}

			closeAutocompletePanel( keyCode );
			return false;
		}


		//}

	};

	const addSearchKeyword = ( { currentTarget } ) => {


		let currentValues;
		for ( let input of searchInput ) {

			let newSelection = document.createElement( 'button' );
			let newSelectionX = document.createElementNS("http://www.w3.org/2000/svg", "svg");
			let newSelectionXPath = document.createElementNS( "http://www.w3.org/2000/svg", "path" );
			newSelectionXPath.setAttribute( 'd', 'M16.192 6.344L11.949 10.586 7.707 6.344 6.293 7.758 10.535 12 6.293 16.242 7.707 17.656 11.949 13.414 16.192 17.656 17.606 16.242 13.364 12 17.606 7.758z' );
			newSelectionX.setAttributeNS( "http://www.w3.org/2000/xmlns/", "xmlns:xlink", "http://www.w3.org/1999/xlink" );
			newSelectionX.setAttribute( 'width', '24' );
			newSelectionX.setAttribute( 'height', '24' );
			newSelectionX.setAttribute( 'viewBox', '0 0 24 24' );
			newSelectionX.append( newSelectionXPath );

			newSelection.innerHTML = currentTarget.dataset.searchLabel;
			newSelection.append( newSelectionX );
			newSelection.dataset.value = currentTarget.dataset.searchValue;
			newSelection.dataset.label = currentTarget.dataset.searchLabel;
			newSelection.type = 'button';
			newSelection.classList.add( 'vf-search__keyword-value' );
			newSelection.setAttribute( 'aria-label', `Remove ${currentTarget.dataset.searchValue} from the search query` );

			newSelection.addEventListener( 'click', removeKeywordFromSearch );

			searchForm.classList.add( 'is-location-selected' );


			input.value = '';
			input.placeholder = 'Add a location or add any custom word.';
			input.name = '';

			let keywordInput = input.parentNode.querySelector( 'input[name="search"]' );
			if ( keywordInput ) {
				currentValues = keywordInput.value.trim().length > 0 ? keywordInput.value.split( ' ' ) : [];
				if ( currentValues.indexOf( currentTarget.dataset.searchValue ) === -1 ) {
					currentValues.push(currentTarget.dataset.searchValue );
					keywordInput.value = currentValues.join( ' ' );
					input.parentNode.insertBefore( newSelection, input );
				}
			}
		}



		currentTarget.closest('form').querySelector('input[data-search="query"]').focus();

		document.body.classList.add( 'location-changed' );
		document.dispatchEvent( locationChangeEvent );
		
		closeAutocompletePanel();

	};

	const closeAutocompletePanel = () => {

		searchForm.classList.remove( 'is-location-options-open' );

		let selectedOption = searchForm.querySelector( 'input[data-search="id"]:checked' );
		let optionList = searchForm.querySelector( '[data-search-autocomplete]' );

		if ( ! selectedOption ) {
			optionList.innerHTML = '';
			return;
		}

		let selectedLabel = searchForm.querySelector( `label[for="${selectedOption.id}"]` );
		if ( ! selectedLabel ) {
			optionList.innerHTML = '';
			return;
		}

		addLocationFilter( { 
			label: selectedOption.dataset.value, 
			value: selectedOption.value,
			slug: selectedOption.dataset.slug
		} );

		optionList.innerHTML = '';

		/*if ( ! submitBtn.disabled ) {
			vfDebug( 'download results plz' );
			downloadResults();

			for( let resetBtn of resetBtns ) {
				resetBtn.disabled = false;
			}
		}*/

		//document.dispatchEvent( filterChangeEvent );

	};

	const addLocationFilter = ( { label, value, slug, type, dontThrowEvent } ) => {

		let locationToggle = document.getElementById( 'vf-filter-toggle__location' );

		let currentFilters = vestorFilters.getActiveFilters();
		vfDebug( 'add filter', currentFilters );
		if ( currentFilters.location && currentFilters.location.indexOf('[') !== -1 ) {
			//document.body.classList.add( 'whole-page-refresh' );
			removeAllLocationFilters();
		}

		let currentValues;
		for( let input of searchInput ) {

			let newSelection = document.createElement( 'button' );
			let newSelectionX = document.createElementNS("http://www.w3.org/2000/svg", "svg");
			let newSelectionXPath = document.createElementNS( "http://www.w3.org/2000/svg", "path" );
			newSelectionXPath.setAttribute( 'd', 'M16.192 6.344L11.949 10.586 7.707 6.344 6.293 7.758 10.535 12 6.293 16.242 7.707 17.656 11.949 13.414 16.192 17.656 17.606 16.242 13.364 12 17.606 7.758z' );
			newSelectionX.setAttributeNS( "http://www.w3.org/2000/xmlns/", "xmlns:xlink", "http://www.w3.org/1999/xlink" );
			newSelectionX.setAttribute( 'width', '24' );
			newSelectionX.setAttribute( 'height', '24' );
			newSelectionX.setAttribute( 'viewBox', '0 0 24 24' );
			newSelectionX.append( newSelectionXPath );

			newSelection.innerHTML = label;
			newSelection.append( newSelectionX );
			newSelection.dataset.value = value;
			newSelection.dataset.label = label;
			newSelection.type = 'button';
			newSelection.classList.add( 'vf-search__location-value' );
			newSelection.setAttribute( 'aria-label', `Remove ${label} from the search query` );

			newSelection.addEventListener( 'click', removeLocationFromSearch );

			searchForm.classList.add( 'is-location-selected' );
			if ( slug ) {
				newSelection.dataset.slug = slug + '/';
			} else {
				newSelection.dataset.slug = '';
			}


			input.value = '';
			input.placeholder = 'Add a location or any custom word.';
			input.name = '';

			let lastBtns = input.parentNode.querySelectorAll( 'button[data-value]' );
			if ( lastBtns.length > 0 ) {
				lastBtns[ lastBtns.length - 1 ].insertAdjacentElement( 'afterend', newSelection );
			} else {
				input.parentNode.prepend( newSelection );
			}


			let locationInput = input.parentNode.querySelector( 'input[name="location"]' );
			if ( locationInput ) {
				currentValues = locationInput.value.trim().length > 0 ? locationInput.value.split( ',' ) : [];
				currentValues.push( value );
				locationInput.value = currentValues.join( ',' );
				if ( currentValues.length === 1 && slug ) {
					searchForm.action = searchForm.dataset.baseUrl + slug + '/';
				} else {
					searchForm.action = searchForm.dataset.baseUrl;
				}

				if ( currentValues.length === 0 ) {
					input.placeholder = 'Add a location or any custom word...';
				}
			}

			resetLocationLabel( searchForm );
		}

		if ( ! dontThrowEvent ) {
			document.dispatchEvent( locationChangeEvent );
		}

		document.body.classList.add( 'location-changed' );

	};

	const removeAllLocationFilters = ( throwEvent ) => {

		for ( let input of searchInput ) {

			let locationInput = input.parentNode.querySelector( 'input[name="location"]' );
			let form = input.closest( 'form' );

			locationInput.value = '';
			input.placeholder = 'Add a location or any custom word...';
			
			let searchValues = input.parentNode.querySelector( 'input[name="search"]' ).value.split(' ');

			form.action = form.dataset.baseUrl;

			let buttons = form.querySelectorAll( `button.vf-search__location-value` );
			for( let button of buttons ) {
				button.remove();
			}

			let locationToggle = form.querySelector( '.vf-search__filters-toggle' );
			if ( locationToggle ) {

				locationToggle.querySelector( '.value' ).innerHTML = 'Search';
				locationToggle.querySelector( '.label' ).innerHTML = '';

				if ( searchValues && searchValues.length > 0 ) { 
					locationToggle.parentNode.classList.add( 'active' );
				} else {
					locationToggle.parentNode.classList.remove( 'active' );
				}
			}

		}

		if ( searchInput ) {
			searchForm.action = searchForm.dataset.baseUrl;
		}

		if ( throwEvent ) {
			document.dispatchEvent( locationChangeEvent );
		} else {
			document.body.classList.add( 'location-changed' );
		}

		document.body.classList.remove( 'custom-map-loaded' );

	};

	const removeLocationFilter = ( locationId, dontThrowEvent ) => {

		for ( let input of searchInput ) {
			let locationInput = input.parentNode.querySelector( 'input[name="location"]' );
			let form = locationInput.closest( 'form' );
			let newValues;
			if ( locationInput ) {
				let currentValues = locationInput.value.split( ',' );
				//if ( currentValues.length <= 1 ) {
				//	return;
				//}
				newValues = [];
				for( let value of currentValues ) {
					if ( parseInt( value ) !== parseInt( locationId ) ) {
						newValues.push( value );
					}
				}
				locationInput.value = newValues.join( ',' );
			}
			if ( newValues.length === 0 ) {
				input.placeholder = 'Add a location or any custom word...';
			}
			let searchValues = input.parentNode.querySelector( 'input[name="search"]' ).value.split(' ');

			let parentNode;
			let buttons = form.querySelectorAll( `button.vf-search__location-value[data-value="${locationId}"]` );
			for( let button of buttons ) {
				parentNode = button.parentNode;
				button.remove();
			}

			if ( newValues.length === 1 ) {

				let location = vestorFilters.getLocation( newValues[0] );
				if ( location ) {
					form.action = form.dataset.baseUrl + location.slug + '/';
				} else {
					form.action = form.dataset.baseUrl;
				}
				
			} else {
				form.action = form.dataset.baseUrl;
			}

			resetLocationLabel( form );

		}

		//downloadResults();

		//for( let resetBtn of resetBtns ) {
		//	resetBtn.disabled = true;
		//}

		if ( ! dontThrowEvent ) {
			document.dispatchEvent( locationChangeEvent );
		}

		document.body.classList.add( 'location-changed' );

	};

	const resetLocationLabel = ( parent ) => {

		let locationToggle = document.getElementById( 'vf-filter-toggle__location' );
		if ( ! locationToggle ) {
			return;
		}

		let searchToggles = parent.querySelectorAll( '.vf-search__location-value' );
		let locations = 0;
		let searches = 0;
		for( let toggle of searchToggles ) {
			let value = parseInt( toggle.dataset.value );
			if ( isNaN( value ) ) {
				searches++;
			} else {
				locations++;
			}
		}

		
		if ( locations && searches ) {
			locationToggle.querySelector( '.value' ).innerHTML = 'Multiple parameters';
			locationToggle.querySelector( '.label' ).innerHTML = '';
		} else if ( locations ) {
			locationToggle.querySelector( '.value' ).innerHTML = locations > 1 ? 'Multiple locations' : searchToggles[0].dataset.label;
			locationToggle.querySelector( '.label' ).innerHTML = '';
		} else if ( searches ) {
			locationToggle.querySelector( '.value' ).innerHTML = searches > 1 ? 'Multiple keywords' : searchToggles[0].dataset.label;
			locationToggle.querySelector( '.label' ).innerHTML = '';
		} else {
			locationToggle.querySelector( '.value' ).innerHTML = 'Search';
			locationToggle.querySelector( '.label' ).innerHTML = '';
		}
		locationToggle.parentNode.classList.add( 'active' );

	};

	const resetLocationFilter = () => {

		if ( ! fallbackFilters ) {
			return;
		}

		removeAllLocationFilters();
		vfDebug( 'reset location', fallbackFilters );
		for( let locationId of fallbackFilters.location.split(',') ) {
			setLocationFilter( { locationId, append: true, dontThrowEvent: true } );
		}

		document.dispatchEvent( locationChangeEvent );

		document.body.classList.remove( 'location-changed' );

	};

	const setLocationFilter = ( { locationId, append, remove, user, dontThrowEvent }, locationInfo ) => {

		vfDebug( 'set location filter', locationId, append, remove, user );

		if ( locationId.indexOf( ['['] ) !== -1 ) {
			user = locationId.substring( 0, locationId.indexOf( '[' ) );
			locationId = locationId.substring( locationId.indexOf( '[' ) + 1, locationId.indexOf( ']' ) );
		}

		if ( user ) {
			vfDebug( 'set custom map', locationId, user );

			vestorSearch.removeAllLocationFilters();
			vestorSearch.addLocationFilter( {
				label: 'Custom Area',
				value: `${user}[${locationId}]`,
				slug: false,
				type: 'custom',
				dontThrowEvent
			} );
			document.body.classList.add( 'custom-map-loaded' );
			//document.dispatchEvent( locationChangeEvent );
			document.activeElement.blur();
			return;
		}

		let locations = locationId.split(',');

		let currentFilters = vestorFilters.getActiveFilters();
		if ( currentFilters.location && currentFilters.location.indexOf('[') !== -1 ) {
			append = false;
		}

		if ( ! append ) {
			removeAllLocationFilters();
		}

		if ( remove ) {
			for ( let id of locations ) {
				removeLocationFilter( id );
			}
		} else {
			for ( let id of locations ) {
				let location = vestorFilters.getLocation( id );
				if ( location || locationInfo ) {
					vestorSearch.addLocationFilter( 
						locationInfo || {
							label: location.value,
							value: id,
							slug: location.slug,
							type: location.type,
							dontThrowEvent
						} 
					);
				}
			}
		}

		if ( ! dontThrowEvent ) {
			document.dispatchEvent( locationChangeEvent );
		}

		document.activeElement.blur();

		document.body.classList.add( 'location-changed' );

	};

	const removeLocationFromSearch = ( e ) => {

		const { currentTarget } = e;

		removeLocationFilter( currentTarget.dataset.value );

	};

	const removeKeywordFromSearch = ( e ) => {

		const { currentTarget } = e;

		let newValues;
		let locationValues;

		for ( let input of searchInput ) {
			let keywordInput = input.parentNode.querySelector( 'input[name="search"]' );
			//let form = keywordInput.closest( 'form' );
			if ( keywordInput ) {
				let currentValues = keywordInput.value.split( ' ' );
				//if ( currentValues.length <= 1 ) {
				//	return;
				//}
				newValues = [];
				for( let value of currentValues ) {
					if ( value !== currentTarget.dataset.value ) {
						newValues.push( value );
					}
				}
				keywordInput.value = newValues.join( ' ' );
			}
			if ( newValues.length === 0 ) {
				input.placeholder = 'Add a location or any custom word...';
			}
			locationValues = input.parentNode.querySelector( 'input[name="location"]' ).value.split(',');
		}

		let parentNode = currentTarget.parentNode;
		let buttons = document.querySelectorAll( `button.vf-search__keyword-value[data-value="${currentTarget.dataset.value}"]` );
		for( let button of buttons ) {
			button.remove();
		}

		for ( let input of searchInput ) {
			let form = input.closest( 'form' );
			if ( newValues.length === 1 ) {
				let firstBtn = form.querySelector( 'button.vf-search__location-value' );
				form.action = form.dataset.baseUrl + firstBtn.dataset.slug + '/';
			} else {
				form.action = form.dataset.baseUrl;
			}
		}

		//downloadResults();

		for( let resetBtn of resetBtns ) {
			resetBtn.disabled = false;
		}

		resetLocationLabel( form );

		document.body.classList.add( 'location-changed' );

	};

	const populateSearchOptions = ( searchQuery, inputPanel ) => {

		for ( let optionList of searchOptions ) {
			optionList.innerHTML = '';
		}
		let inputValue = new RegExp( searchQuery, 'i' ) ;
		let querySearch = searchQuery.toLowerCase().replace(/[^a-zA-Z0-9 ]/gi,'');
		querySearch = querySearch.replace( 'saint ','st ' );

		let optionList = inputPanel.querySelector( '[data-search-autocomplete]' );
		optionList.classList.remove( 'has-error' );
		optionList.classList.remove( 'no-results' );
		searchForm.classList.remove( 'live-search' );
		optionList.classList.remove( 'live-search' );



		if ( querySearch.length > 5 && ! isNaN( querySearch ) && searchXHR ) {

			let url = new URL( window.vfEndpoints.exact );
			url.searchParams.set( 'for', 'mlsid' );
			url.searchParams.set( 'query', querySearch );

			searchXHR.abort();
			searchXHR.open( 'GET', url );
			searchXHR.send();

			optionList.classList.add( 'is-downloading-locations' );

		} else {

			let foundOptions = [];

			for( let data of vestorFilters.locations ) {
				let optionValue = data.value.toLowerCase().replace(/[^a-zA-Z0-9 ]/gi,'');
				if ( optionValue.indexOf( querySearch ) !== -1 ) {
					foundOptions.push( data );
				}
			}

			if ( foundOptions.length > 0 ) {

				for ( let option of foundOptions ) {

					let value = option.value;

					value = value.replace( inputValue, '<strong>$&</strong>' );

					let newOption = document.createElement( 'div' );
					newOption.classList.add( 'autocomplete-option' );

					let newInput = document.createElement( 'input' );
					newInput.type = 'radio';
					newInput.name = 'location';
					newInput.id = 'autocomplete-location__' + option.ID;
					newInput.value = option.ID;
					newInput.dataset.value = option.value;
					newInput.dataset.search = 'id';
					newInput.dataset.slug = option.url;
					//newInput.addEventListener( 'change', changeAutocompleteSelection );
					newInput.addEventListener( 'keydown', navigateAutocompleteSelection );

					let newLabel = document.createElement( 'label' );
					newLabel.classList.add( 'autocomplete-option__label' );
					newLabel.innerHTML = '<span class="type">' + option.type + '</span>';
					newLabel.innerHTML += '<span class="value">' + value + '</span>';
					newLabel.setAttribute( 'for', newInput.id );
					newLabel.addEventListener( 'click', changeAutocompleteSelection );

					newOption.append( newInput );
					newOption.append( newLabel );

					optionList.append( newOption );

				}

				optionList.classList.remove( 'is-downloading-locations' );

			} else {

				let fullSearch = searchQuery.toLowerCase().replace(/[^a-z0-9 ]/gi,'');

				let url = new URL( window.vfEndpoints.exact );
				url.searchParams.set( 'for', 'address' );
				url.searchParams.set( 'query', fullSearch );

				searchXHR.abort();
				searchXHR.open( 'GET', url );
				searchXHR.send();

				optionList.classList.add( 'is-downloading-locations' );

			}
		}

		let keywordOption = document.createElement( 'div' );
		keywordOption.classList.add( 'autocomplete-option' );

		let keywordInput = document.createElement( 'button' );
		keywordInput.type = 'button';
		keywordInput.id = 'autocomplete-keyword';
		keywordInput.innerHTML = '<span class="type">Search Keyword</span>';
		keywordInput.innerHTML += '<span class="value">' + searchQuery + '</span>';
		keywordInput.dataset.searchValue = querySearch;
		keywordInput.dataset.searchLabel = searchQuery;
		keywordInput.dataset.search = 'keyword';
		//newInput.addEventListener( 'change', changeAutocompleteSelection );
		keywordInput.addEventListener( 'keydown', navigateAutocompleteSelection );
		keywordInput.addEventListener( 'click', addSearchKeyword );

		keywordOption.append( keywordInput );
		optionList.append( keywordOption );

		searchForm.classList.add( 'is-location-options-open' );

	};

	const populateExactMatchSearch = ( { target } ) => {

		const { status, response } = target;

		vfDebug( 'trying to make popular', response );

		if ( status !== 200 ) {
			for ( let optionList of searchOptions ) {
				optionList.classList.remove( 'is-downloading-locations' );
				optionList.classList.add( 'has-error' );
			}
		}

		

		for ( let option of response ) {

			let newOption = document.createElement( 'a' );
			newOption.classList.add( 'autocomplete-option' );
			newOption.href = option.url;
			//newOption.addEventListener( 'click', changeAutocompleteSelection );

			let newLabel = document.createElement( 'span' );
			newLabel.classList.add( 'autocomplete-option__label' );
			newLabel.classList.add( 'autocomplete-option__link' );
			newLabel.innerHTML = '<span class="type">' + option.label + '</span>';
			newLabel.innerHTML += '<span class="value">' + option.sublabel + '</span>';

			newOption.append( newLabel );

			for ( let optionList of searchOptions ) {
				let cloneOption = newOption.cloneNode(true);
				cloneOption.addEventListener( 'keydown', navigateAutocompleteSelection );

				//let searchKeyword = optionList.querySelector( '[data-search="keyword"]' );
				optionList.append( cloneOption );

				//if ( searchKeyword ) {
				//	optionList.after( cloneOption, searchKeyword.parentNode );
				//} else {
				//	
				//}
			}

		}

		for ( let optionList of searchOptions ) {
			if ( response.length === 0 ) {
				optionList.classList.add( 'no-results' );
			} else {
				optionList.classList.add( 'live-search' );
			}
			optionList.classList.remove( 'is-downloading-locations' );
		}

	};

	document.addEventListener( 'vestorfilters|map-ready', downloadResults );

	if ( document.readyState == 'complete' ) {
		initializeSearch();
		/*if ( mapResults ) {
			downloadResults();
		}*/
	} else {
		document.addEventListener( 'DOMContentLoaded', () => {
			initializeSearch()
			/*if ( mapResults ) {
				downloadResults();
			}*/
		} );
		
	}

	return {
		setLocationFilter,
		addLocationFilter,
		removeLocationFilter,
		removeAllLocationFilters,
		sortProperties,
		getAvailableProperties,
		getCurrentHash,
		getCacheCount,
		getShareUrl,
		setupMapData,
		downloadResults,
		loadResults,
		resetFilters,
		resetLocationFilter,
		submit
	};

} )();
