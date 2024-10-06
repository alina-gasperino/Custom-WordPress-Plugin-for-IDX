var vestorProperty = ( function() {

	let currentProperty, currentGallery;

	const initialize = () => {

		if ( typeof thisProperty != 'undefined' ) {
			currentProperty = thisProperty;
		}

		document.dispatchEvent( new Event( 'vestorfilters|property-init' ) );
		vfDebug( 'init property' );

		if ( typeof thisProperty != 'undefined' ) {
			if ( vestorTemplates.isInitialized() ) {
				setupPropertyPage();
			} else {
				document.addEventListener( 'vestorfilters|templator-init', setupPropertyPage );
			}
			
		}

	};

	const setupPropertyPage = () => {
		vestorProperty.installTemplateTags( document.getElementById( 'property' ), thisProperty );
		vestorTemplates.forceCalculatorChange();

		
		let map = document.querySelector('[data-vestor-map]');
		vfDebug('setup map now plz', map);
		if ( map ) {
			setupGoogleMap( map );
		}
	}

	const setupCurrentProperty = ( { detail } ) => {

		if ( currentProperty && currentProperty.id !== detail.id && currentGallery ) {
			vfDebug( 'destroying gallery' );
			currentGallery.destroy();
			currentGallery = null;
		}
		currentProperty = detail;

		let currentUrl = new URL( document.location );
		if ( currentUrl.hash === 'gallery' ) {
			let galleryButton = document.querySelector( '[data-gallery-toggle]' );
			if ( galleryButton ) {
				vfDebug( 'trying to open photo gallery from preload' );
				maybeOpenPhotoGallery( { target: galleryButton } );
			}
		}

	};

	const pushGalleryState = ( button ) => {

		let currentUrl = new URL( document.location );
		if ( currentUrl.hash !== 'gallery' ) {
			vfDebug( 'pushing gallery history' );
			currentUrl.hash = 'gallery';
			history.pushState( 
				{
					view: 'gallery',
					property: currentProperty,
					filters: vestorFilters.getActiveFilters()
				},
				'',
				currentUrl.toString()
			);
			vfDebug( currentGallery );
		}

	};

	const maybeOpenPhotoGallery = ( e ) => {

		let self = e.target.closest( '[data-gallery-toggle]' );
		if ( ! self ) {
			return;
		}

		if ( e.hasOwnProperty( 'stopPropagation' ) ) {
			e.stopPropagation();
			e.preventDefault();
		}

		if ( ! window.lightGallery ) {
			setTimeout( maybeOpenPhotoGallery, 500, e );
			return;
		}

		if ( currentProperty.photos && currentProperty.photos.length > 1 ) {

			if ( currentGallery ) {
				vfDebug( 'trying to open existing gallery' );
				currentGallery.openGallery();
				pushGalleryState( self );
			} else {
				let photos = [];
				for ( let photo of currentProperty.photos ) {
					let photoUrl = photo.url;
					if ( photoUrl.indexOf( 'http://' ) !== -1 ) {
						photoUrl = photoUrl.replace( 'http://', '//' );
					}
					photos.push( {
						src: photoUrl,
						thumb: photo.thumbnail
					} );
				}
				self.addEventListener( 'lgInit', ( event ) => {
					vfDebug( 'initialized gallery' );
					currentGallery = event.detail.instance;
					currentGallery.openGallery();
					pushGalleryState( self );
				} );
				window.lightGallery( self, {
					licenseKey: '5F6B6D4F-44AA4A3D-88EF5657-E0A9919C',
					dynamic: true,
					plugins: [lgZoom, lgThumbnail],
					dynamicEl: photos,
					download: false,
					mobileSettings: { controls: false, showCloseIcon: true, download: false, }
				} );
			}

		}

	};

	const installTemplateTags = ( wrapper, response ) => {

		let html = ( response.html ?? wrapper.innerHTML ) + '';
		html = html.replace( '<div class="property-template__agent-contact"></div>', '<!--{{ agent-contact }}-->' );
		html = html.replace( '<div class="property-template__actions"></div>', '<!--{{ actions }}-->' );

		wrapper.innerHTML = vestorTemplates.replaceHandlebars( html + '', response );

		let leftColumn = wrapper.querySelector( '.property-template__secondary .column-left' );

		if ( window.innerWidth < 768 && leftColumn ) {
			let agentPanel = wrapper.querySelector( '#contact-agent-card' );
			vfDebug( agentPanel );
			if ( agentPanel ) {
				leftColumn.append( agentPanel );
			}
			let tourPanel = wrapper.querySelector( '#tour-property-card' );
			if ( tourPanel ) {
				leftColumn.append( tourPanel );
			}
		}

		if ( leftColumn ) {
			vestorTemplates.setupCalculator( leftColumn, response.data );
		}

		let additionalPhotoContainer = wrapper.querySelector( '[data-gallery-thumbnails]' );
		if ( additionalPhotoContainer && response.photos.length && additionalPhotoContainer.childElementCount === 0 ) {
			let propertyPhotos = [];
			for( let photo of response.photos ) {
				let photoUrl = photo.url;
				if ( photoUrl.indexOf( '//' ) === -1 ) {
					photoUrl = response.imageLocation + '/' + photoUrl;
				}
				propertyPhotos.push( photoUrl );
			}
			additionalPhotoContainer.innerHTML = JSON.stringify( propertyPhotos );
		}

		let favoriteBtn = wrapper.querySelector( '[data-vestor-favorite]' );
		if ( favoriteBtn && vestorFavorites.getFavorites().indexOf( favoriteBtn.dataset.vestorFavorite ) !== -1 ) {
			favoriteBtn.classList.add( 'is-favorite' );
		}

		

	};

	const maybeOpenTourModal = ( e ) => {

		if ( ! e.target.closest( '[data-vestor-tour]' ) ) {
			return;
		}

		e.stopPropagation();

		let date = document.querySelector( 'input[name="tour-date"]:checked' );
		let location = document.querySelector( 'input[name="tour-location"]:checked' );

		let tourFrm = document.getElementById( 'form_schedule-tour' );
		if ( tourFrm ) {

			let dateField = document.getElementById( 'field_tour_date' );
			if ( dateField ) {
				let now = new Date();
				let minDate = now.getFullYear() + '-' + (now.getMonth()+1) + '-' + (now.getDate()+'').padStart(2, '0');
				dateField.setAttribute( 'min', minDate );
				dateField.type = "date";
				if ( date ) {
					dateField.value = date.value;
				}
			}

			if ( location ) {
				let typeField = document.getElementById( 'field_tour_type-' + location.value );
				if ( typeField ) {
					typeField.checked = true;
				}
			}

			let linkField = document.getElementById( 'field_tour_link' );
			if ( linkField ) {
				linkField.value = currentProperty.url || window.location.href;
			}

			let mlsField = document.getElementById( 'field_tour_mlsid' );
			if ( mlsField ) {
				mlsField.value = currentProperty.MLSID;
			}

			let oldMessage = tourFrm.querySelector( '.frm_message' );
			if ( oldMessage ) {
				oldMessage.innerHTML = '';
			}

			toggleModal( 'tour-modal', true );

		}

	};
	
	document.addEventListener( 'vestorfilters|property-loaded', setupCurrentProperty );
	document.addEventListener( 'click', maybeOpenPhotoGallery );
	document.addEventListener( 'click', maybeOpenTourModal );

	if ( document.readyState == 'complete' ) {
		initialize();
	} else {
		document.addEventListener( 'DOMContentLoaded', initialize );
	}

	return {
		getCurrentGallery: () => { return currentGallery },
		getCurrentProperty: () => { return currentProperty },
		setupCurrentProperty,
		installTemplateTags
	};

} )();

