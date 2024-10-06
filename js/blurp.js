

var resetBlurp = () => {
	vestorImageUtil.blurpLoad();
}

var maybeLoadPhotos = ( e ) => {
	return;

	let scrollWindow;
	if ( e.hasOwnProperty('currentTarget') && e.currentTarget ) {
		scrollWindow = e.currentTarget;
	} else if ( e.hasOwnProperty('target') && e.target ) {
		scrollWindow = e.target;
	} else {
		return;
	}

	let container, windowHeight;
	if ( scrollWindow.hasOwnProperty( 'document' ) ) {
		container = scrollWindow.document;
		windowHeight = scrollWindow.innerHeight;
	} else {
		container = scrollWindow;
		windowHeight = scrollWindow.offsetHeight;
	}

	vfDebug( 'maybe reload photos', container, windowHeight, e );

	vestorImageUtil.loadPhotosInView( container, windowHeight, e.queryPhotos ?? false );

};

var vestorImageUtil = (function() {

	let unloadedPhotos = [];

	let observerList = [ null, '.vf-search__map-results' ];
	let observers = [];

	const observerListener = ( entries, observer ) => {
		for ( let entry of entries ) {
			if ( entry.target.src ) {
				continue;
			}
			if ( entry.isIntersecting ) {
				entry.target.src = entry.target.dataset.src;
				entry.target.removeAttribute( 'data-src' );
			}
		}

	};

	const addObserver = ( parentNode ) => {

		observerList.push( parentNode );

	}

	const attachToObservers = ( node ) => {

		if ( node.observerAttached ) {
			return;
		}

		for ( let observer of observers ) {
			observer.observe( node );
			node.onload = () => {
				node.classList.add( 'is-loaded' );
			};
		}

		node.observerAttached = true;

	};

	const attachDefaultObservers = () => {

		for( let observerQuery of observerList ) {

			let observerRoot = observerQuery ? document.querySelector( observerQuery ) : null;
			if ( observerRoot || observerQuery == null ) {
				let newObserver = new IntersectionObserver( observerListener, {
					root: observerRoot,
					rootMargin: '200px 0px 200px 0px',
					threshold: 0.5
				} );
				observers.push( newObserver );
			}

		}

		let nodes = document.querySelectorAll( 'img[data-src]:not([src])' );
		for( let node of nodes ) {
			attachToObservers( node );
		}

	};


	const loadPhotosInView = ( container, windowHeight, requery ) => {

		if ( requery ) {
			vfDebug( 'requery photos' );
			unloadedPhotos = container.querySelectorAll( 'img[data-src]:not([src])' );
			
		}

		vfDebug( 'collected', unloadedPhotos );

		if ( ! unloadedPhotos ) {
			return;
		}

		for( let i in unloadedPhotos ) {
			let img = unloadedPhotos[i];
			if ( ! img.dataset || ! img.dataset.src ) {
				continue;
			}
			if ( img.dataset.src.indexOf( '{{' ) !== -1 ) {
				continue;
			}
			if ( img.dataset.src.indexOf('//') === -1 ) {
				img.dataset.src = vfEndpoints.images + img.dataset.src;
			}
			if ( ! img.parentHeight ) {
				img.parentHeight = windowHeight;
			}
			
		}

		vfDebug( 'load photos complete' );

	};

	const forceImageLoad = ( container ) => {

		let images;
		if ( container.tagName === 'IMG' ) {
			images = [ container ];
		} else {
			images = container.querySelectorAll( 'img[data-src]' );
		}
		for ( let image of images ) {
			if ( image.src || ! image.dataset.src ) {
				continue;
			}
			image.src = image.dataset.src;
			image.removeAttribute( 'data-src' );
		}

	};

	function blurpSwapImages( preloaded ) {

		var box;
		var windowHeight = window.innerHeight|| document.documentElement.clientHeight || document.body.clientHeight;

		for ( var i = 0; i < preloaded.length; i += 1 ) {

			if ( preloaded[i].classList.contains( 'blurp__seen' ) ) {
				continue;
			}

			box = preloaded[i].getBoundingClientRect();

			if ( box.bottom > -100 && box.top < windowHeight + 100 ) {
				preloaded[i].classList.add( 'blurp__seen' );
				blurpSwapImage( preloaded[i] );
			}

		}

	}

	function lazySwapImages( images ) {

		var box;
		var windowHeight = window.innerHeight|| document.documentElement.clientHeight || document.body.clientHeight;

		for ( var i = 0; i < images.length; i += 1 ) {

			if ( images[i].classList.contains( 'blurp__seen' ) ) {
				continue;
			}

			box = images[i].getBoundingClientRect();

			if ( box.bottom > -100 && box.top < windowHeight + 100 ) {
				images[i].classList.add( 'blurp__seen' );
				lazySwapImage( images[i] );
			}

		}

	}

	function blurpSwapImage( element ) {

		var img = document.createElement( 'img' ),
			src = '',
			old = element.querySelector( 'img' );

		if ( window.innerWidth < 768 && element.dataset.blurpMobile ) {
			src = element.dataset.blurpMobile;
		} else if ( element.dataset.blurpDesktop ) {
			src = element.dataset.blurpDesktop;
		} else {
			src = element.dataset.blurpReplace;
		}

		if ( src.length > 0 ) {
			img.src = src;
		} else {
			element.classList.add( 'blurp__error' );
			return;
		}

		img.alt = old.alt;
		old.alt = '';

		img.addEventListener( 'load', blurpImgLoaded );
		img.classList.add( 'blurp__replacement' );
		if ( img.style.objectPosition ) {
			img.style.objectPosition = old.style.objectPosition;
		}
		if ( img.style.objectFit ) {
			img.style.objectFit = old.style.objectFit;
		}

		element.append( img );
		element.classList.add( 'blurp__seen' );

	}

	function lazySwapImage( element ) {

		var src = element.dataset.lazyReplace;

		if ( src.length > 0 ) {
			element.src = src;
		} else {
			return;
		}

	}

	function blurpScrolled() {

		if ( window.blurpScrollTimer === null ) {

			blurpSwapImages( window.blurps );
			lazySwapImages( window.lazies );

			window.blurpScrollTimer = true;
			
			window.setTimeout( function ( ) {
				window.blurpScrollTimer = null;
			}, 100 );
		}

	}

	function blurpImgLoaded( e ) {
		var self = this;
		if ( self.parentNode ) {
			self.parentNode.classList.add( 'blurp__loaded' );
		}
		//window.setTimeout( blurpRemoveOldBackground, 800, self.parentNode );
	}

	function blurpRemoveOldBackground( target ) {
		target.style.backgroundImage = '';
	}

	function blurpLoad() {

		window.blurps = document.querySelectorAll( '[data-blurp-replace]' );
		window.lazies = document.querySelectorAll( '[data-lazy-replace]' );

		window.addEventListener( 'scroll', ( e ) => { debounce( blurpScrolled, 200, e ) } );

		window.blurpScrollTimer = null;
		blurpScrolled();

		if ( ! document.body.classList.contains( 'page-template-template-results' ) ) {
			attachDefaultObservers();
		}

	}

	//window.addEventListener( 'scroll', ( e ) => { debounce( maybeLoadPhotos, 200, e ) } );

	if ( document.readyState === "complete" || document.readyState === "interactive" ) {

		blurpLoad();
		//attachDefaultObservers();

	} else {

		window.addEventListener( 'DOMContentLoaded', blurpLoad );
		
		/*window.addEventListener( 'DOMContentLoaded', () => { 
			debounce( maybeLoadPhotos, 500, { currentTarget: window, queryPhotos: true } );
		} );*/

	}

	document.addEventListener( 'vestorfilters|map-setup-complete', attachDefaultObservers );

	return {
		blurpLoad,
		loadPhotosInView,
		addObserver,
		attachToObservers,
		forceImageLoad
	};

})();
