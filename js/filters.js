var vestorFilters = ( function() {
	"use strict";

	let filterChangeEvent = new Event( 'vestorfilters|filter-change' ),
		locationChangeEvent = new CustomEvent( 'vestorfilters|filter-change', { detail: { location: true } } ),
		refreshEvent = new Event( 'vestorfilters|properties-refreshed' ),
		updateFiltersTimeout = null;

	let resultsWrapper,
		resultsPanel,
		searchForm,
		searchFormBtn,
		resetFormBtn;

	let navPanel,
		navPanelWrapper,
		navPanelList,
		navPanelListItems,
		locationPanel,
		moreFilterItem,
		moreFilterList,
		moreFilterBtn;

	let vw = null;

	let vfPanel,
		vfPanelButton,
		filterBar,
		filterToggles,
		filterRangeOptions,
		filtersWithRules,
		firstProperty,
		clearFilterBtns;

	let agentsAvailable, agentCard;
	let toggleLayoutBtn;

	document.addEventListener( 'click', (e) => {

		let filterOption = e.target.closest( '.vf-vestorfilters [data-filter-key="vf"]' );
		if ( filterOption ) {
			filterToggles = document.querySelectorAll( '.vf-vestorfilters [data-filter-key="vf"]' );
			toggleFilterDescription( { currentTarget: filterOption } );
			return;
		}

		let searchToggle = e.target.closest( 'button[data-control-toggle="search"]' );
		if ( searchToggle ) {
			let filterToggle = searchForm.querySelector( 'button#vf-filter-toggle__more' );
			if ( filterToggle ) {
				a11yToggleExpand( { target: filterToggle, forced: true } );

				let searchFieldToggle = searchForm.querySelector( 'button#vf-filter-toggle__location' );
				if ( searchFieldToggle ) {
					a11yToggleExpand( { target: searchFieldToggle, forced: true} );
					let searchInput = searchForm.querySelector( '#search-location' );
					searchInput.focus();
				}
			}
			return;
		}

	} );

	document.addEventListener( 'change', (e) => {

		let filterOption = e.target.closest( '.vf-vestorfilters [data-filter-key="vf"]' );
		if ( filterOption && e.target.tagName === 'INPUT' ) {
			setVestorFilter( { currentTarget: filterOption } );
			return;
		}

		let optionToggle = e.target.closest( '.option-toggles' );
		if ( optionToggle ) {
			let btnTarget = document.getElementById( optionToggle.dataset.for );
			if ( ! btnTarget ) {
				return;
			}
			if ( e.target.tagName === 'INPUT' ) {
				changeOptionToggle( { 
					currentTarget: e.target, 
					btnTarget, 
					labelTarget: btnTarget.querySelector( '.value' ),
					labelValue: e.target.nextElementSibling.innerHTML
				} );
			}
		}

	} );

	const initialize = () => {

		vfDebug( 'init filters' );

		updateFiltersTimeout = null;

		resultsWrapper = document.querySelector( '[data-vestor-results]' );
		resultsPanel = document.querySelector( '[data-vf-results]' );
		searchForm = document.querySelector( '[data-vestor-search]' );

		if ( ! searchForm ) {
			return;
		}


		vfPanel = document.getElementById( 'vf-vestorfilter-panel' );
		vfPanelButton = document.querySelector( 'button[aria-controls="vf-vestorfilter-panel"]' );
		if ( vfPanel && vfPanelButton) {
			vfPanelButton.addEventListener( 'click', (e) => {
				vfPanel.setAttribute( 'aria-hidden', vfPanelButton.getAttribute( 'aria-expanded' ) ? 'false' : 'true' );
				e.preventDefault();
			} );
		}

		filterBar = document.querySelector( '.vf-search__filters' );
		
		
		firstProperty = document.querySelector( '.vf-search__results .vf-property' );

		clearFilterBtns = document.querySelectorAll( '[data-filter-clear]' );
		for( let toggle of clearFilterBtns ) {
			toggle.addEventListener( 'click', clearFilterValue );
		}

		agentsAvailable = document.querySelectorAll( '.vf-agents li[data-filters]' );
		agentCard = document.querySelector( '.vf-agents' );
		toggleLayoutBtn = document.querySelector( '[data-layout-toggle]' );

		navPanel = document.querySelector( '.vf-search__filters' );

		locationPanel = document.querySelector( '.vf-filter-panel__location--input' );

		if ( searchForm ) {
			filtersWithRules = searchForm.querySelectorAll( '[data-rules]' );
		}

		filterRangeOptions = document.querySelectorAll( 'input[data-range-for]' );
		for( let rangeOption of filterRangeOptions ) {
			installRangeToggle( rangeOption );
		}

		if ( navPanel ) {
			navPanelWrapper = navPanel.querySelector( '.vf-search__subfilter-group' );
			navPanelList = navPanel.querySelector( 'ul.navbar-nav' );
			moreFilterItem = navPanel.querySelector( 'li.more-filters' );
			moreFilterList = moreFilterItem.querySelector( '.filter-list' );
			moreFilterBtn = moreFilterItem.querySelector( '#vf-filter-toggle__more' );
			searchFormBtn = navPanel.querySelector( '.btn[type="submit"]' );
			resetFormBtn = navPanel.querySelector( '.btn[type="reset"]' );
			if ( navPanelList ) {
				window.addEventListener( 'resize', () => { debounce( collapseFilterList, 500 ) } );
				setTimeout( collapseFilterList, 500 );
			}
		}

		if ( firstProperty && vfPanel && filterBar ) {
			window.addEventListener( 'scroll', () => { debounce( toggleVFPanel, 200, e ) } );
		}

		if ( toggleLayoutBtn ) {
			toggleLayoutBtn.addEventListener( 'click', function () {
				resultsWrapper.classList.toggle( 'is-property-view' );
				resultsWrapper.classList.toggle( 'is-list-view' );
				window.scrollTo( { top: 0, behavior: 'auto' } );
				if ( resultsWrapper.classList.contains( 'is-list-view' ) ) {
					resultsPanel.style.height = '';

					if ( window.sessionStorage ) {
						sessionStorage.setItem( 'view-mode', 'list' );
					}

				} else if ( window.sessionStorage ) {
					sessionStorage.setItem( 'view-mode', 'feature' );
				}
			} );
		}

		if ( resultsPanel ) {
			let properties = resultsPanel.querySelectorAll( '.vf-property' );
			for( let property of properties ) {
				let toggle = property.querySelector( '[data-gallery-toggle]' );
				if ( toggle ) {
					toggle.property = property;
					toggle.addEventListener( 'click', openPropertyGallery );
				}
			}
			
		}

		let currentUrl = new URL( window.location.href );
		if ( currentUrl.searchParams.has( 'property' ) ) {
			document.body.classList.add( 'showing-property-panel' );
			const propertyPanel = document.getElementById( 'property-panel' );
			if ( propertyPanel ) {
				propertyPanel.classList.add( 'is-loading' );
			}
		}

		if ( window.sessionStorage && document.body.classList.contains( 'page-template-property' ) ) {

			let filters = sessionStorage.getItem( 'filters' );
			if ( filters && filters.length > 0 ) {
				filters = JSON.parse( filters );
				let backBtn = document.querySelector( '[data-vestor-back]' );
				let query = makeFilterQuery( filters );
				if ( backBtn ) {
					backBtn.href += '?' + query;
				}
				let vfLinks = document.querySelectorAll('[data-vestor-link]');
				for( let link of vfLinks ) {
					let url = new URL( searchForm.dataset.baseUrl + '?' + query );
					url.searchParams.set( 'vf', link.dataset.vestorLink );
					link.href = url;
				}
				document.addEventListener( 'vestorfilters|search-init', () => {
					setFilterDefaults( filters );
				} );
			}

			
	
			
	
		} else if ( window.sessionStorage ) {
	
			document.addEventListener( 'DOMContentLoaded', () => {
	
				sessionStorage.setItem( 'filters', JSON.stringify( getActiveFilters() ) );
	
			} );
		}

		if ( locationPanel ) {

			const locationActionsEvent = ( e ) => {
				
				let locationAction = e.target.closest( '[data-location-action]' );
				if ( searchForm && locationAction ) {
					if ( locationAction.dataset.locationAction === 'submit' ) {
						vestorSearch.submit();
					}
					if ( locationAction.dataset.locationAction === 'reset' ) {
						vestorSearch.resetLocationFilter();
					}
					if ( locationAction.dataset.locationAction === 'clear' ) {
						vestorSearch.removeAllLocationFilters();
					}
					if ( locationAction.dataset.locationAction === 'subscribe' ) {
						vestorFavorites.saveSearchForUser( { currentTarget: locationAction } );
					}
					if ( locationAction.dataset.locationAction === 'close' ) {
						document.body.classList.add( 'whole-page-refresh' );
						vestorSearch.removeAllLocationFilters();
						let defLocationInput = document.querySelector('input[data-filter-value="location"][data-default]');
						if ( defLocationInput ) {
							vestorSearch.setLocationFilter( { 
								locationId: defLocationInput.dataset.default, 
								append: true, 
								remove: false, 
								dontThrowEvent: true 
							} );
						}
						
						document.dispatchEvent( new Event( 'vestorfilters|reset-map' ) );
					}
					e.stopPropagation();
					e.preventDefault();
					return;
				}
				
			};

			locationPanel.addEventListener( 'click', locationActionsEvent );
			document.addEventListener( 'click', locationActionsEvent );
		}

		//checkFilterRules();
		collapseFilterList();

	};

	const formatFunc = ( value, format ) => {

		let divisor;

		if ( format === 'decimal' ) {
			return value.toLocaleString( 'en-US', { minimumFractionDigits: 1 } );
		} else if ( format === 'price' ) {
			if ( value < 1000000 ) {
				divisor = value/1000;
			} else {
				divisor = value/1000000;
			}
			let returnValue = '$' + divisor.toLocaleString( 'en-US', { minimumFractionDigits: value < 1000000 ? 0 : 1 } );
			returnValue += ( value < 1000000 ? 'K' : 'MM' );
			if ( value === 5000000 ) {
				returnValue += '+';
			}
			return returnValue;
		} else if ( format === 'weeks' ) {
			return Math.ceil( value/30 ) + 'mo';
		} else {
			return value.toLocaleString( 'en-US' );
		}

	};

	const reloadPageContents = (e) => {

		resultsPanel.classList.remove( 'is-refreshing' );
		document.body.classList.remove( 'is-toggle-open' );
		document.body.classList.remove( 'whole-page-refresh' );

		if ( e.target.status !== 200 ) {
			// error time
			return;
		}

		let response = e.target.response;
		if ( ! response ) {
			// error time
			return;
		}

		let doc = document.createElement( 'div' );
		doc.innerHTML = response.html;

		let newForm = doc.querySelector( 'form[data-vestor-search]' );
		if ( newForm ) {

			searchForm.dataset.vestorSearch = newForm.dataset.vestorSearch;
			let newSaveBtn = doc.querySelector( 'button[data-vestor-save]' );
			let oldSaveBtn = searchForm.querySelector( 'button[data-vestor-save]' );
			if ( newSaveBtn && oldSaveBtn ) {
				oldSaveBtn.dataset.vestorSave = newSaveBtn.dataset.vestorSave;
				oldSaveBtn.classList = newSaveBtn.classList;
				oldSaveBtn.classList.add( 'has-been-clicked' );
			}

		}

		vfDebug( 'history changed on reload' );

		let newResults = doc.querySelector( '[data-vf-results]' );
		if ( newResults ) {
			resultsPanel.innerHTML = newResults.innerHTML;
			resetBlurp();

			let newPages = doc.querySelector( '[data-vf-pagination]' );
			let oldPages = document.querySelector( '[data-vf-pagination]' );

			oldPages.innerHTML = newPages.innerHTML;

			resultsWrapper.classList.remove( 'is-property-view' );
			resultsWrapper.classList.add( 'is-list-view' );
			window.scrollTo( { top: 0, behavior: 'auto' } );

			document.dispatchEvent( refreshEvent );
		}



	};


	const getLocation = ( locationId ) => {

		for ( let location of vfLocationData ) {
			if ( location.ID === locationId ) {
				return location;
			}
		}

		return false;

	};

	const getActiveFilters = ( parent ) => {

		if ( ! parent ) {
			parent = searchForm;
		}

		if ( ! parent ) {
			return {};
		}

		let currentUrl = new URL( window.location.href );
		if ( currentUrl.searchParams.has( 'favorites' ) ) {
			return { favorites: currentUrl.searchParams.get( 'favorites' ) };
		}

		let filters = parent.querySelectorAll( 'input[data-filter-value]' );


		let selectedFilters = {};
		for( let i = 0; i < filters.length; i += 1 ) {
			if ( filters[i].type === 'radio' && ! filters[i].checked ) {
				continue;
			}
			if ( filters[i].value.length > 0 ) {
				selectedFilters[ filters[i].name ] = filters[i].value;
			}
		}

		let searchOption = parent.querySelector( 'input[data-filter-value="location"]' );
		if ( searchOption ) {
			if ( searchOption.value.length > 0 ) {
				selectedFilters['location'] = searchOption.value;
			}
		} else {
			let searchQuery = parent.querySelector( 'input[data-search="query"]' );
			if ( searchQuery && searchQuery.value.length > 0 ) {
				selectedFilters['location_query'] = searchQuery.value;
			}
		}

		return selectedFilters;

	};

	const makeFilterQuery = ( filters ) => {

		let queryString = '';
		for( let filterKey in filters ) {
			//if ( filterKey === 'vf' && filters[filterKey] !== 'fixer' ) {
			//	continue;
			//}
			if ( queryString.length > 0 ) {
				queryString += '&';
			}
			queryString += filterKey + '=' + filters[filterKey];
		}

		return queryString;

	};

	const getFilterQuery = ( parent ) => {

		if ( ! parent ) {
			parent = searchForm;
		}

		let filters = getActiveFilters( parent );

		let queryString = makeFilterQuery( filters );

		return queryString;

	};

	const refreshResults = () => {

		let queryString = getFilterQuery();
		//vfDebug( queryString );

		if ( window.sessionStorage ) {
			sessionStorage.setItem( 'filters', JSON.stringify( getActiveFilters() ) );
		}

		vfDebug( 'history changed on refresh' );

		/*history.pushState(
			{},
			document.head.title.innerHTML,
			url + '?' + queryString
		);*/

		vestorSearch.loadResults( {
			query: queryString,
			onComplete: reloadPageContents
		} );

	};

	const getCurrentSearchURL = () => {
		let url = new URL( searchForm.action );

		let activeFilters = getActiveFilters();
		
		
		if ( ! Object.keys(activeFilters).length === 0 ) {
			activeFilters = {
				'vf': 'ppsf',
				'property-type': 'all',
				'status': 'active'
			};
			let searchOptions = searchForm.querySelector( 'button.vf-search__location-value' );
			if ( searchOptions ) {
				activeFilters.location = searchOptions.dataset.value;
			}
		}

		for( let key in activeFilters ) {
			if ( activeFilters.hasOwnProperty( key ) ) {
				//console.log(key);
				url.searchParams.set( key, activeFilters[key] );
			}
		}

		return url.toString();
	};

	const refreshResultsPage = (e) => {

		if ( ! resultsPanel ) {
			return;
		}
		vfDebug( 'refreshing' );

		//clearTimeout( updateFiltersTimeout );
		resultsPanel.classList.add( 'is-refreshing' );
		refreshResults();
		//updateFiltersTimeout = window.setTimeout( , 1000 );
	};

	const changeOptionToggle = ( { currentTarget, btnTarget, labelValue, labelTarget } ) => {

		if ( ! currentTarget.value ) {

			labelTarget.innerHTML = '';
			btnTarget.parentNode.classList.remove( 'active' );

		} else if ( currentTarget.checked && labelTarget ) {

			labelTarget.innerHTML = labelValue;
			btnTarget.parentNode.classList.add( 'active' );

		}

		if ( ! btnTarget.classList.contains( 'stay-open' ) ) {

			btnTarget.ariaExpanded = 'false';
			btnTarget.nextElementSibling.classList.remove( 'show' );
			btnTarget.nextElementSibling.setAttribute( 'aria-hidden', 'true' );

		}

		let filters = getActiveFilters();
		if ( filters.vf ) {
			vfDebug( 'option changed', filters.vf );
			if ( ! checkFilterRules( filters.vf ) ) {
				setVestorFilter( { 
					currentTarget: document.querySelector( `input[name="vf"][data-filter-value="lotm"]` ),
					dontThrowEvent: true
				} );
			}
		}

		document.dispatchEvent( filterChangeEvent );

	};

	const installRangeToggle = ( rangeOption ) => {

		rangeOption.nextElementSibling.addEventListener( 'click', ( e ) => {
			e.preventDefault();
			e.stopPropagation();
			rangeOption.checked = true;
			vfDebug( 'range changed', rangeOption );

			changeRangeOption( rangeOption );
		} );

	}

	const changeRangeOption = ( currentTarget ) => {

		let rangeInputs = document.querySelectorAll( `input[data-filter-value="${currentTarget.dataset.rangeFor}"]` );
		let valueLabelText;

		for ( let input of rangeInputs ) {
			let value = parseInt( currentTarget.value || 0 );
			if ( currentTarget.dataset.rangeOf === 'min' ) {
				input.dataset.valueMin = value === 0 ? '' : value;
			} else {
				input.dataset.valueMax = value === 0 ? '' : value;
			}
			let newValue = input.dataset.valueMin + ':' + input.dataset.valueMax;
			if ( newValue === ':' ) {
				input.value = '';
			} else {
				input.value = newValue;

				if ( input.dataset.valueMin !== '' && input.dataset.valueMax === '' ) {
					valueLabelText = formatFunc( input.dataset.valueMin, 'price' ) + '+';
				} else if ( input.dataset.valueMin === '' && input.dataset.valueMax !== '' ) {
					valueLabelText = formatFunc( input.dataset.valueMax, 'price' ) + '-';
				} else if ( input.dataset.valueMin !== '' && input.dataset.valueMax !== '' ) {
					valueLabelText = formatFunc( input.dataset.valueMin, 'price' ) + ' - ' + formatFunc( input.dataset.valueMax, 'price' );
				} else {
					input.value = '';
				}
			}
		}

		let currentlyActive = document.querySelectorAll( `.min-max-toggles--${currentTarget.dataset.rangeFor}-${currentTarget.dataset.rangeOf} .active` );
		for ( let active of currentlyActive ) {
			active.classList.remove( 'active' );
		}
		if ( currentTarget.value !== '' ) {
			currentTarget.parentNode.classList.add( 'active' );
		} else {
			currentTarget.checked = false;
		}

		let rangeToggleButton = document.querySelector( `#vf-filter-toggle__${currentTarget.dataset.rangeFor},#vf-morefilters__toggle--${currentTarget.dataset.rangeFor}` );
		let valueLabel = rangeToggleButton.querySelector( '.value' );
		if ( valueLabelText ) {
			rangeToggleButton.parentNode.classList.add( 'active' );
			valueLabel.innerHTML = valueLabelText;
		} else {
			//rangeToggleButton.parentNode.classList.remove( 'active' );
			valueLabel.innerHTML = '';
		}

		document.dispatchEvent( filterChangeEvent );

	};

	const setFilterDefaults = ( filters ) => {

		if ( ! searchForm ) {
			return;
		}

		let allFilters = searchForm.querySelectorAll( `input[data-filter-value],input[data-range-for]` );

		vfDebug( 'time to reset to default', filters );
		
		for ( let filter of allFilters ) {
			if ( filter.type === 'checkbox' || filter.type === 'radio' ) {
				filter.checked = false;
			} else {
				filter.value = '';
				if ( filter.dataset.valueMin ) {
					filter.dataset.valueMin = '';
				}
				if ( filter.dataset.valueMax ) {
					filter.dataset.valueMax = '';
				}
			}
		}

		for ( let key in filters ) {
			let value = filters[key];
			let input = searchForm.querySelector( `input[data-filter-value="${key}"][value="${value}"]` );
			if ( input ) {
				input.checked = true;
				let label = input.nextElementSibling;
				if ( label ) {
					let button = document.querySelector( `#vf-filter-toggle__${key},#vf-morefilters__toggle--${key}` );
					//vfDebug( button );
					if ( button ) {
						button.querySelector( '.value' ).innerHTML = label.innerHTML;
						button.parentNode.classList.add( 'active' );
					}
				}
			}
		}

		let locationInput = searchForm.querySelector( `input[data-filter-value="location"]` );
		let locationRadio = searchForm.querySelector( `input[data-search="id"]:checked` );
		let locationBtn = document.getElementById( `vf-filter-toggle__location` );

		if ( filters && filters.location ) {

			if ( locationInput ) {
				locationInput.value = filters.location;
			}
			let location = getLocation( filters.location );
			if ( location && locationBtn ) {
				locationBtn.querySelector( '.label' ).innerHTML = location.type;
				locationBtn.querySelector( '.value' ).innerHTML = location.value;
				locationBtn.parentNode.classList.add( 'active' );
			}

			vestorSearch.setLocationFilter( { 
				locationId: filters.location, 
				append: false, 
				remove: false, 
				dontThrowEvent: true 
			} );

		} else {

			searchForm.querySelector( '[data-search="query"]' ).setAttribute( 'placeholder', 'Add a location or any custom word...' );

			if ( locationInput ) {
				locationInput.value = '';
			}
			if ( locationRadio ) {
				locationRadio.checked = false;
			}

			if ( locationBtn ) {
				locationBtn.parentNode.classList.remove( 'active' );
			}

			vestorSearch.removeAllLocationFilters( false );

		}

		let priceInput = searchForm.querySelector( `input[data-filter-value="price"]:checked` );
		let priceBtn = document.getElementById( `vf-filter-toggle__price` );
		if ( filters && filters.price && filters.price !== ':' ) {

			let values = filters.price.split( ':' );
			let valueLabel;

			if ( values.length === 1 || values[1] === '' ) {
				valueLabel = 'More than ' + formatFunc( values[0], 'price' );
			} else if ( values.length === 2 && values[0] === '' ) {
				valueLabel = 'Up to ' + formatFunc( values[1], 'price' );
			} else if ( values.length === 2 ) {
				valueLabel = formatFunc( values[0], 'price' ) + ' - ' + formatFunc( values[1], 'price' );
			}

			if ( priceInput ) {
				priceInput.checked = false;
			}
			let newPriceInput = searchForm.querySelector( `input[data-filter-value="price"][value="${filters.price}"]` );
			if ( newPriceInput ) {
				newPriceInput.checked = true;
			}

			if ( priceBtn ) {
				priceBtn.querySelector( '.value' ).innerHTML = valueLabel;
				priceBtn.parentNode.classList.add( 'active' );
			}

		} else {

			if ( priceInput ) {
				priceInput.checked = false;
			}
			if ( priceBtn ) {
				priceBtn.querySelector( '.value' ).innerHTML = '';
				priceBtn.parentNode.classList.remove( 'active' );
			}

		}

		let activeFilter = searchForm.querySelector( `[data-filter-key].active` );
		if ( activeFilter ) {
			activeFilter.classList.remove( 'active' );
			activeFilter.setAttribute( 'aria-expanded', 'false' );
			activeFilter.querySelector( 'input' ).checked = false;
		}

		if ( filters && filters.vf ) {

			let newFilter = searchForm.querySelector( `[data-filter-key="${filters.vf}"]` );
			if ( newFilter ) {
				newFilter.classList.add( 'active' );
				newFilter.setAttribute( 'aria-expanded', 'true' );
				newFilter.querySelector( 'input' ).checked = true;
			} else {
				let propertyVF = searchForm.querySelector( `[data-filter-value="vf"]` );
				if ( propertyVF ) {
					propertyVF.value = filters.vf;
				}
				if ( filters.search ) {
					let searchInput = document.createElement( 'input' );
					searchInput.dataset.filterValue="search";
					searchInput.name="search";
					searchInput.value=filters.search;
					searchInput.type="hidden";
					searchForm.append( searchInput );
				}
			}

		}

		//console.trace( 'is this happening' );

	};

	const collapseFilterList = ( forced ) => {

		if ( ! moreFilterList ) {
			return;
		}

		let newVw = Math.max( document.documentElement.clientWidth || 0, window.innerWidth || 0 );
		if ( newVw === vw && ! forced ) {
			return;
		}
		if ( ! vw ) {
			vw = window.innerWidth;
		}

		vfDebug( 'resetting filters', vw )

		if ( vw >= 768 ) {

			//navPanel.classList.remove( 'has-more-filters' );

			let filters = moreFilterList.querySelectorAll( 'li:not(.misc):not(.min-max-option)' );
			for ( let filter of filters ) {
				navPanelList.insertBefore( filter, moreFilterItem );
			}
			vfDebug(filters);

			navPanelListItems = navPanelList.querySelectorAll( 'li:not(.more-filters):not(.min-max-option)' );

			vfDebug(navPanelListItems);

			let listBox = navPanelList.getBoundingClientRect();
			let panelBox = navPanelWrapper.getBoundingClientRect();
			let btnBoxWidth = searchFormBtn.getBoundingClientRect().width;
			if ( resultsPanel ) {
				btnBoxWidth = 0;
			}

			vfDebug( 'filter collapse change', searchFormBtn, resetFormBtn );

			let i = navPanelListItems.length - 1;
			vfDebug( panelBox.right - btnBoxWidth, panelBox.right, btnBoxWidth, listBox.right );
			while ( Math.ceil( panelBox.right - btnBoxWidth ) < Math.floor( listBox.right ) && i > 0 ) {
				if ( ! navPanelListItems[i] ) {
					break;
				}
				moreFilterList.prepend( navPanelListItems[i] );
				listBox = navPanelList.getBoundingClientRect();
				panelBox = navPanelWrapper.getBoundingClientRect();

				i -= 1;
				vfDebug( panelBox.right - btnBoxWidth, panelBox.right, btnBoxWidth, listBox.right );
			}

			if ( i < navPanelListItems.length - 1 ) {
				moreFilterList.prepend( navPanelListItems[i] );
				//navPanel.classList.add( 'has-more-filters' );
			}

		} else {

			//vfDebug( 'moving all filters' );
			
			navPanelListItems = navPanelList.querySelectorAll( 'li:not(.more-filters):not(.misc):not(.min-max-option)' );
			for( let listItem of navPanelListItems ) {
				moreFilterList.prepend( listItem );
			}
			

		}

		vw = newVw;

	};

	const toggleVFPanel = () => {
		let propBox = firstProperty.getBoundingClientRect();
		let barBox = filterBar.getBoundingClientRect();

		if ( propBox.bottom < barBox.bottom ) {
			vfPanel.classList.add( 'is-toggle' );
		} else {
			vfPanel.classList.remove( 'is-toggle' );
		}
	}

	const toggleFilterDescription = ( { currentTarget } ) => {

		let label = currentTarget.querySelector( '.label' );
		let description = currentTarget.querySelector( '.description' );

		if ( ! agentCard ) {
			return;
		}
		agentCard.querySelector( '.vf-agents__heading--title' ).innerHTML = label.innerHTML;

		for ( let agent of agentsAvailable ) {
			if ( agent.dataset.filters.split(',').indexOf( currentTarget.dataset.filterValue ) !== -1 ) {
				agent.querySelector( '.vf-agents__filter-desc' ).innerHTML =
					'<h3>' + label.innerHTML + '</h3>' +
					'<p>' + description.innerHTML + '</p>';
				agent.classList.add( 'is-visible' );
				agent.classList.remove( 'is-hidden' );
			} else {
				agent.classList.remove( 'is-visible' );
				agent.classList.add( 'is-hidden' );
			}
		}

	};


	const checkFilterRules = ( filter ) => {

		let thisFilter = document.querySelector( `#vestorfilter-selection-panel [data-toggle-group="vestorfilters"][data-filter-value="${filter}"]` );
		
		if ( ! thisFilter || ! thisFilter.dataset.rules ) {
			return true;
		}

		let { rules, altFilter } = thisFilter.dataset;
		if ( ! rules ) {
			return true;
		}

		let currentFilters = getActiveFilters();
		

		rules = rules.split(';');
		let rulesOk = true;

		vfDebug( 'checking vf rules', thisFilter, currentFilters, rules );
		for ( let rule of rules ) {
			let [ key, values ] = rule.split( ':' );
			let ruleOk = ( values[0] === '!' );
			values = values.split( ',' );
			for ( let value of values ) {
				if ( value[0] === '!' ) {
					value = value.substring( 1 );
				}
				if ( currentFilters[key] === value ) {
					ruleOk = ! ruleOk;
					break;
				}
			}
			if ( ! ruleOk ) {
				return false;
			}
		}

		return rulesOk;

		/*if ( ! rulesOk && altFilter ) {
			setVestorFilter( { currentTarget: thisFilter.querySelector( 'input' ) } );
		} else if ( ! rulesOk ) {
			setVestorFilter( { currentTarget: false } );
		}*/
		

	};

	const openPropertyGallery = (e) => {

		if ( e.preventDefault ) {
			e.preventDefault();
		}

		const { currentTarget } = e;
		const property = currentTarget.property;
		const gallery = property.querySelector( '[data-gallery-thumbnails]' );

		if ( gallery ) {

			const photos = JSON.parse( gallery.innerHTML );

			let galleryElements = [];
			for ( let photo of photos ) {
				galleryElements.push( {
					src: photo,
					thumb: photo,
				} );
			}

			lightGallery( currentTarget, {
				dynamic: true,
				dynamicEl: galleryElements,
				controls: true,
				download: false,
				thumbnail: true,
				showThumbByDefault: true,
			});

		}

		document.dispatchEvent( new Event( 'vestorfilters|gallery-opened' ) );

	};

	const clearFilterValue = ( { currentTarget } ) => {

		let filterKey = currentTarget.dataset.filterClear;
		if ( filterKey ) {
			let filters = searchForm.querySelectorAll( `[data-filter-value="${filterKey}"]` );
			for ( let toggle of filters ) {
				toggle.value = '';
			}
		}

		currentTarget.style.display = 'none';

		document.dispatchEvent( filterChangeEvent );

		vfDebug( 'cleared ' + filterKey );
		closeAllExpanders();

	};

	

	const setVestorFilter = ( { currentTarget, dontThrowEvent } ) => {

		let filterValue, ok;
		if ( currentTarget ) {
			filterValue = currentTarget.dataset.filterValue;
			ok = checkFilterRules( filterValue );
		} else {
			filterValue = false;
			ok = true;
		}

		if ( ! ok ) {
			let newPropertyType = currentTarget.dataset.defaultType;
			let currentType = searchForm.querySelector( `input[data-key="property-type"]:checked` );
			if ( currentType ) {
				currentType.checked = false;
			}
			let newType = searchForm.querySelector( `input[data-key="property-type"][value="${newPropertyType}"]` );
			if ( newType ) {
				newType.checked = true;
				let parent = newType.closest( 'li' );
				parent.classList.add( 'active' );
				let button = parent.querySelector( 'button .value' );
				button.innerHTML = newType.nextElementSibling.innerHTML;
			}
		}

		filterToggles = document.querySelectorAll( '[data-filter-key="vf"]' );

		for ( let toggle of filterToggles ) {
			if ( toggle.dataset.filterValue === filterValue ) {
				toggle.classList.add('active');
				toggle.dataset.filterActive = 'true';
				toggle.querySelector( 'input' ).checked = true;
			} else {
				toggle.classList.remove('active');
				toggle.dataset.filterActive = 'false';
				toggle.querySelector( 'input' ).checked = false;
			}
		}

		if ( ! dontThrowEvent ) {
			document.dispatchEvent( filterChangeEvent );
			document.activeElement.blur();
		}

	};

	const saveFiltersToStorage = () => {

		sessionStorage.setItem( 'filters', JSON.stringify( getActiveFilters() ) );

	};

	const resetHash = () => {

		window.vestorResultsHash = null;
		window.sessionStorage.clear();
		
	};

	const disableControls = () => {

		searchForm.disabled = true;
		searchForm.classList.add( 'is-disabled' );

	}

	const enableControls = () => {

		searchForm.disabled = false;
		searchForm.classList.remove( 'is-disabled' );

	}

	const updatePageUrl = ( newUrl ) => {

		if ( vestorMaps.isDebugMode() ) {
			newUrl.searchParams.set( 'debug', 'true' );
		}

		history.replaceState(
			history.state,
			'',
			newUrl.toString()
		);

	};

	document.addEventListener( 'vestorfilters|filter-change', resetHash );
	//document.addEventListener( 'vestorfilters|filter-change', checkFilterRules );
	document.addEventListener( 'vestorfilters|filter-change', refreshResultsPage );
	document.addEventListener( 'vestorfilters|filter-change', saveFiltersToStorage );

	

	if ( document.readyState == 'complete' ) {
		initialize();
	} else {
		document.addEventListener( 'DOMContentLoaded', initialize );
	}

	return {
		initialize,
		collapseFilterList,
		refreshResults,
		getActiveFilters,
		getFilterQuery,
		setFilterDefaults,
		getLocation,
		getCurrentSearchURL,
		disableControls,
		enableControls,
		updatePageUrl,
		locations: vfLocationData,
		getNavPanel: () => { return navPanelWrapper }
	};

} )();