var vestorTemplates = ( () => {

	let actionsTemplate,
		shareTemplate,
		contactTemplate,
		tourTemplate,
		calcTemplate,
		calculatorCard,
		initialized = false;

	const initialize = () => {

		actionsTemplate = document.getElementById( 'share-links-template' );
		if ( actionsTemplate ) {
			let shareTemplateDOM = actionsTemplate.querySelector( '.property-template__quick-share' );
			if ( shareTemplateDOM ) {
				shareTemplate = shareTemplateDOM.innerHTML;
				shareTemplateDOM.remove();
			}
			actionsTemplate = actionsTemplate.innerHTML;
			console.log( actionsTemplate );
		}

		

		contactTemplate = document.getElementById( 'agent-card-template' );
		if ( contactTemplate ) {
			contactTemplate = contactTemplate.innerHTML;
		}

		tourTemplate = document.getElementById( 'tour-card-template' );
		if ( tourTemplate ) {
			tourTemplate = tourTemplate.innerHTML;
		}

		calcTemplate = document.getElementById( 'calculator-template' );
		if ( calcTemplate ) {
			calculatorCard = calcTemplate.childNodes[0];
			if ( calculatorCard ) {
				document.dispatchEvent( new CustomEvent( 'vestorfilters|calculator-attached', { detail: calculatorCard } ) );
			}
		}


		vfDebug( 'init templator' );
		initialized = true;

		document.dispatchEvent( new Event( 'vestorfilters|templator-init' ) );
	};

	const isInitialized = () => {
		return initialized;
	};

	const findHandlebars = ( htmlString ) => {
		
		let found = [];

		let attrTags = htmlString.match( /\{\{.*?\}\}/g );
		if ( attrTags ) {
			for( let tag of attrTags ) {
				let interpreted = interpretHandlebars( tag );
				found.push(interpreted);
			}
		}

		return found;
	};

	const getPropertyImage = ( { property, size, index } ) => {

		if ( ! index ) {
			index = 0;
		}

		if ( ! size || size === 'full' ) {
			size = 'url';
		}

		return getImageUrl( property.photos[ parseInt( index ) ][ size ] );

	}

	const getImageUrl = ( file ) => {

		let url = file + '';
		if( url.indexOf( '//' ) === -1 ) {
			url = vfEndpoints.images + '/' + url;
		}
		return url

	};

	const replaceHandlebars = ( htmlString, property, flags ) => {

		let contents = htmlString + '';
		let handlebars = findHandlebars( contents );

		for( let tag of handlebars ) {
			let replacement = '';
			
			switch( tag.action ) {
				case 'compliance-logo':
					if ( tag.value === 'logo' && property.logo ) {
						replacement = property.logo;
					}
					break;
				case 'property':
					if ( tag.value === 'photo' && tag.subset ) {
						replacement = getPropertyImage( { property, index: tag.subset || 0 } );
					} else if ( tag.value === 'url' ) {
						replacement = property.url;
					} else if ( tag.value === 'thumbnail' ) {
						replacement = `<img data-src="` + getImageUrl( property.block_cache.photo ) + `" alt="">`;
					} else if ( tag.value === 'price' ) {
						replacement = friendlyPrice( parseInt( property.data_cache.price ) / 100 );
					} else if ( tag.value === 'id' ) {
						replacement = property.ID;
					} else if ( tag.value === 'mlsid' ) {
						replacement = property.MLSID;
					} else if ( tag.value === 'address' ) {
						let line1comma = property.address.indexOf(',');
						let line1,line2;
						if ( line1comma ) {
							line1 = property.address.substring( 0, line1comma);
							line2 = property.address.substring( line1comma + 1 ).trim();
						} else {
							line1 = property.address;
						}
						replacement = `<span class="address address--line-1">${line1}</span>`;
						if ( line2 ) {
							replacement += `<span class="address address--line-2">${line2}</span>`;
						}
					} else if ( tag.value === 'meta' ) {
						switch ( property.property_type ) {
							case 'land':
							case 'commercial':
								replacement = '';
								if ( property.block_cache.lot || property.data_cache.lot || property.data_cache.lot_est ) {
									let lotSize = property.block_cache.lot ?? property.data_cache.lot ?? property.data_cache.lot_est;
									if ( isNaN( parseFloat( lotSize ) ) ) {
										lotSize = lotSize;
									} else if ( vfLotSizes[ lotSize ] ) {
										lotSize = vfLotSizes[ lotSize ];
									} else {
										lotSize = lotSize.toLocaleString( 'en-US', { maximumFractionDigits: 1 } ) + ' acres';
									}
									if ( lotSize ) {
										replacement += `<span class="meta meta--lot">${lotSize}</span>`;
									}
								}
								if ( property.block_cache.zoning ) {
									replacement += `<span class="meta meta--zoning">${property.block_cache.zoning}</span>`;
								}
								if ( property.property_type === 'commercial' && ( property.block_cache.sqft || property.data_cache.sqft ) ) {
									let sqft = property.block_cache.sqft || property.data_cache.sqft;
									replacement += `<span class="meta meta--sqft">${sqft}</span>`;
								}
								break;
							case 'mf':
								if ( property.block_cache.units || property.data_cache.units ) {
									let units = property.block_cache.units || property.data_cache.units;
									if ( ! property.block_cache.units ) {
										units = parseInt( units ) / 100;
									}
									replacement += `<span class="meta meta--units">${units} units</span>`;
								}
								if ( property.block_cache.beds || property.data_cache.bedrooms ) {
									let beds = property.block_cache.beds || property.data_cache.bedrooms;
									if ( ! property.block_cache.beds ) {
										beds = parseInt( beds ) / 100;
									}
									replacement += `<span class="meta meta--beds">${beds} bed</span>`;
								}
								if ( property.block_cache.sqft || property.data_cache.sqft ) {
									let sqft = property.block_cache.sqft || property.data_cache.sqft;
									if ( ! property.block_cache.sqft ) {
										sqft = parseInt( sqft ) / 100;
									}
									replacement += `<span class="meta meta--sqft">${sqft} ft&sup2;</span>`;
								}
								break;
							default:
								if ( property.block_cache.beds || property.data_cache.bedrooms ) {
									let beds = property.block_cache.beds || property.data_cache.bedrooms;
									if ( ! property.block_cache.beds ) {
										beds = parseInt( beds ) / 100;
									}
									replacement += `<span class="meta meta--beds">${beds} bed</span>`;
								}
								if ( property.block_cache.bath || property.data_cache.bathrooms ) {
									let bath = property.block_cache.bath || property.data_cache.bathrooms;
									if ( ! property.block_cache.bath ) {
										bath = parseInt( bath ) / 100;
									}
									replacement += `<span class="meta meta--bath">${bath} bath</span>`;
								}
								if ( property.block_cache.sqft || property.data_cache.sqft ) {
									let sqft = property.block_cache.sqft || property.data_cache.sqft;
									if ( ! property.block_cache.sqft ) {
										sqft = parseInt( sqft ) / 100;
									}
									replacement += `<span class="meta meta--sqft">${sqft} ft&sup2;</span>`;
								}
								break;
						}
					}
					break;
				case 'compliance':
					let logo = '', text = '';
					if ( vfSources[property.post_id+''] ) {
						text = vfSources[property.post_id].text || '{{ agency }}';
						logo = vfSources[property.post_id].logo || '';
					}
					if ( logo || property.block_cache.comp ) {
						text = property.block_cache.comp ? text.replace( '{{ agency }}', property.block_cache.comp ?? '' ) : '';
						replacement = `<figure class="vf-property-block__compliance vf-property-block__meta--compliance">${logo}<figcaption>${text}</figcaption></figure>`;
					}
					break;
				case 'image':
					if ( tag.value ) {
						replacement = getImageUrl( tag.value );
					}
					break;
				case 'vf':
					if ( tag.value === 'url' && tag.subset ) {
						let url = new URL( vestorFilters.getCurrentSearchURL() );
						url.searchParams.set( 'vf', tag.subset );
						replacement = url.toString();
					}
					break;
				case 'icon':
					replacement = `<svg class="vf-use-icon vf-use-icon--${tag.value}"><use xlink:href="#${tag.value}"></use></svg>`;
					break;
				case 'og':
					if ( tag.value === 'url' ) {
						replacement = encodeURIComponent( property.url );
					} else if ( tag.value === 'image' ) {
						replacement = getPropertyImage( { property } );
					}
					break;
				case 'actions': 
					if ( actionsTemplate ) {
						replacement = actionsTemplate + '';
						//replacement = replacement.replaceAll( /SHARE_URL/g, property.url );
						//replacement = replacement.replaceAll( /SHARE_ID/g, property.id );
						//replacement = replacement.replaceAll( /SHARE_IMAGE/g, getPropertyImage( { property } ) );
						replacement = replaceHandlebars( replacement, property );
					}
					break;
				case 'agent-contact':
					if ( contactTemplate || tourTemplate ) {
						replacement = ( contactTemplate || '' ) + ( tourTemplate || '' );
						replacement = replacement.trim();
						replacement = replaceHandlebars( replacement, property );
					}
					break;
				case 'favorite':
					let favoriteClass = vestorFavorites.getFavorites().indexOf( property.ID ) !== -1 ? ' is-favorite' : '';
					replacement = `<button type="button" class="vf-property-block__favorite-btn vf-favorite-toggle-btn${favoriteClass}" data-vestor-favorite="${property.ID}"><span>Toggle Favorite</span></button>`;
					break;
				case 'vestorfilter':
					if ( flags && flags.vf ) {
						replacement = '<!--{{ vestorfilter }}-->';
					} else if ( flags && flags.filter && flags.label !== 'Yes' && property.data_cache && property.data_cache[flags.filter] ) {
						let value = ( parseFloat( property.data_cache[flags.filter] ) / 100 ).toFixed(2);
						if ( flags.filter === 'onmarket' ) {
							value = Math.floor( Math.ceil( Date.now() - property.data_cache[flags.filter] * 10 ) / ( 3600000 * 24 ) );
							if ( value === 0 ) {
								value = 'Today';
							}
						} else if ( value > 999 ) {
							value = formatPrice( value, true );
						}
						let vfLabel = value === 'Today' ? value : flags.label.replace( '{{value}}', value );
						replacement = `<span class="vf-property-block__flags--vf vf-property-block__vf vf-property-block__vf--${flags.filter}">${vfLabel}</span>`;
					}
					break;
				case 'flags':
					let favClass = vestorFavorites.getFavorites().indexOf( property.ID ) !== -1 ? ' is-favorite' : '';
					replacement = `<button type="button" class="vf-property-block__favorite-btn vf-favorite-toggle-btn${favClass}" data-vestor-favorite="${property.ID}"><span>Toggle Favorite</span></button>`;
					if ( flags && flags.vf ) {
						replacement += '<!--{{ vestorfilter }}-->';
					} else if ( flags && flags.filter && flags.label !== 'Yes' && property.data_cache && property.data_cache[flags.filter] ) {
						let value = ( parseFloat( property.data_cache[flags.filter] ) / 100 ).toFixed(2);
						if ( flags.filter === 'onmarket' ) {
							value = Math.floor( Math.ceil( Date.now() - property.data_cache[flags.filter] * 10 ) / ( 3600000 * 24 ) );
							if ( value === 0 ) {
								value = 'Today';
							}
						} else if ( value > 999 ) {
							value = formatPrice( value, true );
						}
						let vfLabel = value === 'Today' ? value : flags.label.replace( '{{value}}', value );
						replacement += `<span class="vf-property-block__flags--vf vf-property-block__vf vf-property-block__vf--${flags.filter}">${vfLabel}</span>`;
					}
					break;
			}
			
			contents = contents.replaceAll( `<!--${tag.source}-->`, replacement );
			contents = contents.replaceAll( tag.source, replacement );
		}

		return contents;

	};

	const interpretHandlebars = ( handlebarString ) => {
		let sourceString = handlebarString + ''; // clone
		sourceString = sourceString.replace( '{{', '' ).replace( '}}', '' ).trim();
		let attrs = sourceString.split( ':', 2 );

		let action = attrs[0];
		let value, subset;
		if ( attrs.length > 1 ) {
			value = decodeURIComponent( attrs[1] );
			let subsetFound = value.match(/(.*?)\[(.*?)\]/);
			if ( subsetFound ) {
				value = subsetFound[1]
				subset = subsetFound[2];
			}
		}

		return { action, value, subset, source: handlebarString + '' };
	};

	const setupCalculator = ( container, propertyData ) => {

		if ( ! calculatorCard ) {
			return;
		}

		let changeEvent = new Event( 'change' );

		let amtField     = calculatorCard.querySelector( '#field_mortgage_amt' );
		let dpField      = calculatorCard.querySelector( '#field_downpayment_amt' );
		let taxField     = calculatorCard.querySelector( '#field_property_tax_value' );
		let hoaField     = calculatorCard.querySelector( '#field_hoa_fee' );
		let hoaFreqField = calculatorCard.querySelector( '#field_hoa_fee_freq' );

		if ( amtField ) {
			amtField.value = Math.round( propertyData.price / 100 );
		}
		if ( dpField ) {
			dpField.value = Math.round( propertyData.price * 0.2 / 100 );
		}
		if ( taxField ) {
			taxField.value = Math.round( propertyData.taxes / 100 );
		}
		if ( hoaField ) {
			hoaField.value = propertyData.hoa ? Math.round( propertyData.hoa / 100 ) : '';
		}
		if ( hoaFreqField ) {
			hoaFreqField.value = '1';
		}

		container.append( calculatorCard );

	};

	const resetCalculator = () => {

		if ( calculatorCard ) {
			calcTemplate.append( calculatorCard );
		}

	}

	const forceCalculatorChange = () => {

		if ( calculatorCard ) {
			for ( let input of calculatorCard.querySelectorAll('input') ) {
				jQuery(input).change();
			}
		}

	};

	const friendlyPrice = ( value ) => {

		let returnValue = value.toLocaleString( 'en-US', { maximumFractionDigits: 0 } );

		return '$' + returnValue;

	};

	const formatPrice = ( value, withSign ) => {

		let divisor;

		

		if ( value < 1000000 ) {
			divisor = value/1000;
		} else {
			divisor = value/1000000;
		}
		let returnValue = divisor.toLocaleString( 'en-US', { maximumFractionDigits: value < 1000000 ? 0 : 1 } );
		returnValue += ( value < 1000000 ? 'K' : 'M' );

		if ( withSign ) {
			return '$' + returnValue;
		}

		return returnValue;

	};

	const setupShareDialog = () => {

		if ( ! shareTemplate ) {
			return;
		}
		let mapShareDialog = document.getElementById( 'map-share-dialog' );
		if ( ! mapShareDialog ) {
			return;
		}

		let url = vestorSearch.getShareUrl();
		vfDebug( 'share url', url );
		if ( ! url ) {
			mapShareDialog.parentNode.style.display = 'none';
			return;
		}
		mapShareDialog.parentNode.style.display = '';

		let inside = shareTemplate + '';
		inside = inside.replace( /{{ og:url }}/g, url );
		inside = inside.replace( /{{ og:image }}/g, '' );
		inside = inside.replace( /{{ property:url }}/g, url );

		mapShareDialog.innerHTML = inside;



	}

	if ( document.readyState == 'complete' ) {
		initialize();
	} else {
		document.addEventListener( 'DOMContentLoaded', initialize );
	}

	document.addEventListener( 'vestorfilters|redraw-map', setupShareDialog );

	return {
		interpretHandlebars,
		findHandlebars,
		replaceHandlebars,
		setupCalculator,
		resetCalculator,
		forceCalculatorChange,
		formatPrice,
		isInitialized
	};

} )();