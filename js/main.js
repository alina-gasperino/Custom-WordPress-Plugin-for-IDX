var vfDebug = ( ...params ) => {
	if ( ! vfDebugMode ) {
		return;
	}
	console.log( ...params );
};
var vfTrace = ( ...params ) => {
	if ( ! vfDebugMode ) {
		return;
	}
	console.trace( ...params );
};

var debounce = ( fn, timeout, ...args ) => {

	let debounceArgs = {};
	if ( fn.method ) {
		debounceArgs = fn;
	} else {
		debounceArgs.method = fn;
	}
	if ( timeout ) {
		debounceArgs.timeout = timeout;
	}

	if ( debounceArgs.method.priority && ! debounceArgs.priority ) {
		return;
	}

	if ( debounceArgs.method.debounceTimer ?? false ) {
		clearTimeout( debounceArgs.method.debounceTimer );
	}

	debounceArgs.method.debounceTimer = setTimeout( debounceArgs.method, debounceArgs.timeout, ...args );
	if ( debounceArgs.priority ) {
		debounceArgs.method.priority = debounceArgs.priority;
	}

}

var makeWPSlug = ( text ) => {

	let replacement = text + '';
	replacement = replacement.toLowerCase();
	replacement = replacement.replaceAll( /^[a-z0-9]/g, '-' );
	replacement = replacement.replaceAll( '--', '-' );

	return replacement;

};

	/* Required to rememeber last click button id */
	var lastMenuBtnClick;

	/**
	 * openCatcher
	 *
	 * Enable pointer events of the click catcher div
	 *
	 * @param  Button id triggering the function.
	 * @return void.
	 */

	const openCatcher = (btnId) => {

		let clickCatcher  = document.getElementById('filter-click-catcher');

		/*if user clicks the same menu button twice
		* then it's no longer required to keep
		* the click catcher element open. */
		if(lastMenuBtnClick == btnId)
		{
			clickCatcher.style.pointerEvents = 'none';

			/*reseting the last click button*/
			lastMenuBtnClick = null;
		}
		/* open the click catcher element if
		 * user clicked the button first time */
		else
		{
			clickCatcher.style.pointerEvents = 'auto';
			lastMenuBtnClick = btnId;
		}
	}

	/**
	 * closeMoreFilters
	 *
	 * Close all opened filter menus and disable
	 * the pointer evenets of the click catcher.
	 *
	 * @param  void.
	 * @return void.
	 *
	 * @Note: 	it closes the menus by triggering
	 * 			closeAllExpanders() function.
	 */
	const closeMoreFilters = () =>
	{

		let clickCatcher = document.getElementById('filter-click-catcher'),
			moreBtn = document.getElementById('vf-filter-toggle__more'),
			moreExp = moreBtn.getAttribute('aria-expanded');

		if(moreExp == 'true') moreBtn.click();

		closeAllExpanders(null);
		clickCatcher.style.pointerEvents = 'none';
		lastMenuBtnClick = null;
	}


( function() {
	"use strict";

	const setupComplianceDates = () => {

		let sources = document.querySelectorAll( '.compliance-date' );
		for ( let source of sources ) {

			let xhr = new XMLHttpRequest();
			xhr.open( "GET", '/wp-json/vestorfilter/v1/source/last-updated' );
			xhr.send();
			xhr.responseType = 'json';
			xhr.sourceObj = source;

			xhr.addEventListener( "load", ( e ) => {

				let { response, status } = e.target;

				if ( status === 200 ) {

					e.target.sourceObj.innerHTML = response.date;

				}

			} );
		}

	};

	
	if ( document.readyState === "complete" ) {
		setupComplianceDates();
	} else {
		document.addEventListener( 'DOMContentLoaded', setupComplianceDates );
	}

} )();
const a11yToggleExpand = ( { target, forced } ) => {

	if ( ! target ) {
		return target;
	}

	const currentTarget = target.closest( '[aria-controls]' );
	if ( ! currentTarget ) {
		return;
	}

	//vfDebug( currentTarget );

	const targetId = currentTarget.getAttribute( 'aria-controls' );
	//alert(targetId);
	if ( ! targetId ) {
		return;
	}

	vfTrace( 'a11y expander', target, forced );

	const controls = document.getElementById( targetId );
	//vfDebug( controls );

	const isOpen = forced !== undefined ? ! forced : ( currentTarget.getAttribute( 'aria-expanded' ) === 'true' );
	//vfDebug( isOpen );

	closeAllExpanders( targetId );

	let allTargetingBtns = document.querySelectorAll( `[aria-controls="${targetId}"]` );
	for ( let btn of allTargetingBtns ) {
		btn.setAttribute( 'aria-expanded', isOpen ? 'false' : 'true' );
	}

	controls.setAttribute( 'aria-hidden', isOpen ? 'true' : 'false' );

	if ( isOpen ) {
		controls.classList.remove( 'show' );
		document.body.classList.remove( 'modal-open' );
	} else {
		controls.classList.add( 'show' );
		controls.scrollIntoView( {
			behavior: 'smooth',
			block: 'nearest',
			inline: 'nearest',
		} );

	}

	let isAnythingOpen = document.querySelectorAll( '[aria-hidden="false"]' );
	//vfDebug( isAnythingOpen );
	if ( isAnythingOpen.length > 0 && ! currentTarget.classList.contains( 'accordion-toggle' ) ) {
		document.body.classList.add( 'is-toggle-open' );
	} else {
		document.body.classList.remove( 'is-toggle-open' );
	}

};

const closeAllExpanders = ( ignoreTarget ) => {

	vfTrace( 'close all', ignoreTarget );

	let btnExpanders = document.querySelectorAll( 'button[aria-expanded]' );

	document.body.classList.remove( 'is-modal-open' );
	document.body.classList.remove( 'is-toggle-open' );

	for ( let btn of btnExpanders ) {

		let controls = btn.getAttribute( 'aria-controls' );


		if ( controls === ignoreTarget || btn.classList.contains( 'stay-open' ) ) {
			//vfDebug( btn );
			continue;
		}

		let targetObj = document.getElementById( controls );
		//vfDebug( target, targetObj )
		if ( targetObj ) {

			targetObj.setAttribute( 'aria-hidden', 'true' );
			targetObj.classList.remove( 'show' );
			//targetObj.classList.add( 'collapse' );
		}

		btn.setAttribute( 'aria-expanded', 'false' );

	}
};

( function() {
	"use strict";

	const setPillValue = ( target ) => {

		if ( target.checked ) {

			target.parentLabel.querySelector( '.value' ).innerHTML = target.nextElementSibling.innerHTML;
		}
	}

	const pillToggleExpand = ( e ) => {

		let parent = e.target.closest( '.pill-dropdown' );

		let button = parent.querySelector( 'button' );
		let input = e.target.closest( 'input' );
		if ( input ) {
			e.preventDefault();
			a11yToggleExpand( { target: button } );
		}

		e.stopPropagation();

	};

	document.addEventListener( 'change', (e) => {
		if ( e.target.closest( '.pill-dropdown' ) && e.currentTarget.tagName === 'INPUT' ) {
			vfDebug( 'pill dropdown change' );
			setPillValue( e.currentTarget );
		}
	} );


	document.addEventListener( 'click', (e) => {

		if ( e.target.closest( '.pill-dropdown' ) ) {
			vfDebug( 'pill dropdown click' );
			pillToggleExpand( e );
			return;
		}

		if ( e.target.closest( '.toggle[aria-expanded]' ) ) {
			vfDebug( 'toggle clicked' );
			vfDebug( e.target.closest( '.toggle[aria-expanded]' ) );
			a11yToggleExpand( e );
			return;
		}

		if ( e.target.closest( '[aria-expanded]' ) ) {
			vfDebug( 'generic expander' );
			a11yToggleExpand( e );
		}

		let pillSelectors = document.querySelectorAll( '.pill-dropdown button' );
		for( let selector of pillSelectors ) {
			a11yToggleExpand( {
				target: selector,
				forced: false
			} );
		}

	} );

} )();


window.vestorAccount = ( function () {
	"use strict";

	let userNonce, afterAuthAction;

	if ( typeof vfAccount !== 'undefined' ) {
		userNonce = vfAccount.nonce;
	}

	const initialize = () => {

		let navItems = document.querySelectorAll( '.requires-login a, a.requires-login' );
		for ( let anchor of navItems ) {
			anchor.addEventListener( 'click', interceptNavAuth );
		}

	};

	const isLoggedIn = () => {

		return ( userNonce ?? false ) ? true : false;
	
	};

	const interceptNavAuth = ( e ) => {

		if ( userNonce || document.body.classList.contains( 'logged-in' ) ) {
			return;
		}

		e.preventDefault();
		
		let destination = e.currentTarget.href;

		afterSuccessfulAuth( () => {
			window.location.href = destination;
		} );

		toggleModal( 'login-modal', true );

		return false;

	}

	const afterSuccessfulAuth = ( newAction ) => {

		afterAuthAction = newAction;

	};

	const formidableSuccess = ( event, form, response ) => {

		if ( form.id !== 'form_user-registration' && form.id !== 'form_user-login' ) {
			return;
		}

		let $form = jQuery( response.content );

		let $loginCookie = $form.find( '[data-vestor-cookie="login"]' );
		let $nonce = $form.find( '[data-vestor-nonce]' );
		let $payload = $form.find( '[data-vestor-payload]' );
		let $user = $form.find( '[data-vestor-user]' );
		if ( $loginCookie && $nonce ) {

			let loginCookie = JSON.parse( $loginCookie[0].value );

			Cookies.set( loginCookie.name, loginCookie.value, {
				expires: loginCookie.expire,
				path: loginCookie.path,
				domain: loginCookie.domain
			} );

			userNonce = $nonce[0].value;

			let payload;
			if ( $payload.length ) {
				payload = JSON.parse( $payload[0].value );
			}

			if ( afterAuthAction ) {
				afterAuthAction( payload );
				afterAuthAction = null;
			}

		}

		let usingAjax = jQuery( '#register-modal' )[0].dataset.useAjax;

		if ( form.id === 'form_user-registration' && usingAjax === "false" ) {
			window.location.href = window.location.protocol + '//' + window.location.hostname + '/?msg=registered';
		} else if ( form.id === 'form_user-login' && usingAjax === "false" ) {
			window.location.href = window.location.protocol + '//' + window.location.hostname + '/?msg=login';
		} else {

			let $message = jQuery( `#${form.id} .frm_message` );
			
			window.vestorMessages.show( $message[0].innerHTML, 'success', 5000 );

			jQuery( '#login-modal,#register-modal' ).remove();
			document.body.classList.remove( 'modal-open' );
			document.body.classList.remove( 'is-loading-sso' );

			if ( $user ) {
				vfAccount.id = parseInt( $user.val() );
			}

		}

	};

	const successfulSSOAuth = ( { detail } ) => {

		//vfDebug( detail );

		if ( ! detail.vestorNonce ) {
			//vfDebug( 'no nonce token sent' );
			return;
		}
		userNonce = detail.vestorNonce;
		let loginCookie = detail.cookie;

		Cookies.set( loginCookie.name, loginCookie.value, {
			expires: loginCookie.expire,
			path: loginCookie.path,
			domain: loginCookie.domain
		} );

		let payload;
		if ( detail.payload ) {
			payload = detail.payload;
		}

		if ( afterAuthAction ) {
			afterAuthAction( payload );
			afterAuthAction = null;
		}

		window.vestorMessages.show( detail.message, 'success', 5000 );
		jQuery( '#login-modal,#register-modal' ).remove();
		document.body.classList.remove( 'modal-open' );
		document.body.classList.remove( 'is-loading-sso' );

	};

	const erroredSSOAuth = ( { detail } ) => {

		window.vestorMessages.show( detail.message, 'error', 5000 );
		document.body.classList.remove( 'is-loading-sso' );

	};

	document.addEventListener( 'vestorfilter-sso|login-success', successfulSSOAuth );
	document.addEventListener( 'vestorfilter-sso|login-failure', erroredSSOAuth );

	document.addEventListener( 'DOMContentLoaded' , () => {

		initialize();

		jQuery( document ).on( 'frmFormComplete', formidableSuccess );

		if ( window.location.search.indexOf( 'msg=registered' ) > -1 ) {
			window.vestorMessages.show( "Success. You are signed in.", 'success', 7000 );
		}

		if ( window.location.search.indexOf( 'msg=login' ) > -1 ) {
			window.vestorMessages.show( "Success. You are signed in.", 'success', 7000 );
		}

	} );

	return {
		getNonce: () => { return userNonce || false; },
		afterSuccessfulAuth,
		isLoggedIn
	};

} )();

( function() {
	"use strict";

	if ( ! document.body.classList.contains( 'archive' ) && ! document.body.classList.contains( 'blog' ) ) {
		return;
	}

	const featureContainer = document.querySelector( '.archive-loop__features' );
	if ( ! featureContainer ) {
		return;
	}

	const activateFeaturedPost = ( { currentTarget } ) => {

		const activePost = document.querySelector( '.archive-loop__excerpt.active' );
		if ( activePost === currentTarget ) {
			return;
		}

		activePost.classList.remove( 'active' );
		currentTarget.classList.add( 'active' );

	};

	const posts = featureContainer.querySelectorAll( '.archive-loop__excerpt' );
	for( let post of posts ) {
		post.addEventListener( 'mouseover', activateFeaturedPost );
	}
	if ( posts ) {
		posts[0].classList.add( 'active' );
		featureContainer.classList.add( 'ready' );
	}

} )();



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

window.vestorFavorites = ( function () {
	"use strict";

	let currentFavorites = [];
	let savedSearches = [];
	let mySubscriptions = [];
	let favoriteBtns, saveBtns, removeBtns, selectedFavorite, subscribeBtns, searchNameInputs, saveForUserBtns;

	let invitationForm;

	let savedCounter;

	if ( typeof vfFavorites !== 'undefined' ) {
		currentFavorites = vfFavorites.properties;
		savedSearches    = vfFavorites.searches;
		mySubscriptions  = vfFavorites.subscribed;
	}

	document.addEventListener( 'click', ( e ) => {

		let btn = e.target.closest('[data-vestor-favorite]');
		if ( ! btn ) {
			return;
		}
		addFavorite( { currentTarget: btn } );
		e.stopPropagation();

	} );

	const initialize = () => {

		//vfDebug( 'initializing favorites' );

		let hasFriends = document.getElementById( 'friend-favorite-modal' );

		/*
		favoriteBtns = document.querySelectorAll( `button[data-vestor-favorite]` );
		for( let btn of favoriteBtns ) {
			btn.addEventListener( 'click', addFavorite );
			if ( ! btn.nextElementSibling && hasFriends ) {
				let saveForFriend = document.createElement( 'button' );
				saveForFriend.setAttribute( 'class', 'btn save-for-friend' );
				if ( hasFriends.dataset.isForAgents === 'yes' ) {
					saveForFriend.innerHTML = 'Save for Lead';
				} else {
					saveForFriend.innerHTML = 'Save for Friend';
				}
				saveForFriend.addEventListener( 'click', addFriendFavorite );
				saveForFriend.dataset.propertyId = btn.dataset.vestorFavorite;

				btn.parentNode.append( saveForFriend );
			}
		}
		*/

		document.addEventListener( 'click', ( e ) => {

			let currentTarget = e.target.closest( 'button' );
			vfDebug( currentTarget );
			if ( ! currentTarget ) {
				return;
			}
			if ( currentTarget.dataset.vestorSave && currentTarget.dataset.trash ) {
				trashSearch( { currentTarget } );
				e.preventDefault();
				e.stopPropagation();
				return;
			}

			if ( currentTarget.dataset.vestorSave || currentTarget.dataset.subscribeUser ) {
				let nonce = currentTarget.dataset.subscribeUser ?? null;
				saveSearchForUser( nonce );

				e.preventDefault();
				e.stopPropagation();
				return;
			}
			if ( currentTarget.dataset.favoriteRemove ) {
				deleteFriendFavorite( { currentTarget } );
				e.preventDefault();
				e.stopPropagation();
				return;
			}

		} );
		/*
		saveBtns = document.querySelectorAll( `button[data-vestor-save],button[data-subscribe-user]` );
		for( let btn of saveBtns ) {
			btn.addEventListener( 'click', saveSearchForUser );
		}
		*/
		/*
		removeBtns = document.querySelectorAll( `button[data-favorite-remove]` );
		for( let btn of removeBtns ) {
			btn.addEventListener( 'click', deleteFriendFavorite );
		}
		saveForUserBtns = document.querySelectorAll( `button[data-subscribe-user]` );
		for( let btn of saveForUserBtns ) {
			btn.addEventListener( 'click', saveSearchForUser );
		}
		*/
		subscribeBtns = document.querySelectorAll( `input[data-vestor-subscribe]` );
		for( let btn of subscribeBtns ) {
			btn.addEventListener( 'change', saveSubscription );
		}
		searchNameInputs = document.querySelectorAll( `input[data-search-name]` );
		for( let input of searchNameInputs ) {
			input.addEventListener( 'change', saveSearchName );
		}
		
		//setFavoritesCount( currentFavorites.length + Object.keys( savedSearches ).length );
		setFavoritesCount( currentFavorites.length );

		//alert(currentFavorites.length);

		let saveForUserBtns = document.querySelectorAll( `button[data-subscribe-user]` );
		if ( saveForUserBtns.length > 0 ) {
			let saveForLeadSelect = document.getElementById('field_saved_lead_id');
			if ( saveForLeadSelect && ! saveForLeadSelect.dataset.select2Id ) {
				jQuery( saveForLeadSelect ).select2( {
					width: '100%'
				} );
				jQuery( '.select2-container' ).on( 'click', (e) => {
					e.stopPropagation();
				} );
			}
		}

		if ( document.body.classList.contains( 'search-reminder-allowed' ) ) {
			window.setTimeout( showSearchReminder, 7000 );
		}

		let invitationForms = document.querySelectorAll( 'form[data-vf-invitation]' );
		if ( invitationForms ) {
			for( let form of invitationForms ) {
				form.addEventListener( 'submit', inviteFriend );
			}
		}

		let currentUrlParams = new URL( document.location.href );
		if ( currentUrlParams.searchParams.has('invitation_code') ) {
			let code = currentUrlParams.searchParams.get('invitation_code');
			Cookies.set( 'user_invite_code', code, {
				path: '/',
			} );
		}

		let favoriteForms = document.querySelectorAll( 'form[data-vf-favorite="friend"]' );
		if ( favoriteForms ) {
			for( let form of favoriteForms ) {
				form.addEventListener( 'submit', saveFriendFavorite );
			}
		}

		if ( document.body.classList.contains( 'page-template-template-favorites' ) ) {
			//window.addEventListener( 'scroll', () => { debounce( maybeLoadPhotos, 200, e ) } );
			//maybeLoadPhotos( { currentTarget: window } );
		}

	};

	const copyUrl = ( { target } ) => {

		//button[data-copy-url]

		let currentTarget = target.closest('button');
		if ( ! currentTarget ) {
			return;
		}
		if ( ! currentTarget.dataset.copyUrl ) {
			return;
		}

		//vfDebug( 'copy attempted' );

		let textArea = document.createElement("textarea");
		textArea.value = currentTarget.dataset.copyUrl;
		
		// Avoid scrolling to bottom
		textArea.style.top = "0";
		textArea.style.left = "0";
		textArea.style.position = "fixed";

		document.body.appendChild(textArea);
		textArea.focus();
		textArea.select();

		try {
			document.execCommand('copy');
			//var msg = successful ? 'successful' : 'unsuccessful';
			//vfDebug('Fallback: Copying text command was ' + msg);
		} catch (err) {
			console.error('Fallback: Oops, unable to copy', err);
		}

		document.body.removeChild(textArea);
		currentTarget.classList.add( 'is-copied' );

	};

	document.addEventListener( 'click', copyUrl );

	const enableFavorites = ( favorites ) => {

		for( let propertyId of favorites ) {
			let toggle = document.querySelector( `[data-vestor-favorite="${propertyId}"]` );
			if ( toggle ) {
				toggle.classList.add( 'is-favorite' );
			}
		}

		currentFavorites = favorites;

	};

	const getFavorites = () => {
		return currentFavorites;
	}

	const addFavorite = ( { currentTarget } ) => {

		let propertyId = currentTarget.dataset.vestorFavorite;

		//alert(propertyId);

		if ( window.vestorAccount.getNonce() ) {

			toggleFavorite( propertyId );

		} else {

			vestorAccount.afterSuccessfulAuth( ( responseData ) => {

				if ( responseData.favorites ) {

					enableFavorites( responseData.favorites );

				}

				toggleFavorite( propertyId, 'on' );

			} );

			toggleModal( 'login-modal', true );

		}

	};

	const addFriendFavorite = ( e ) => {

		e.stopPropagation();

		let { currentTarget } = e;

		let propertyId = currentTarget.dataset.propertyId;

		let friendModal = document.getElementById( 'friend-favorite-modal' );
		friendModal.querySelector( 'select[name="friend_id"]' ).value = '';
		friendModal.querySelector( 'input[name="property_id"]' ).value = propertyId;

		toggleModal( 'friend-favorite-modal', true );

	};

	const saveFriendFavorite = ( e ) => {

		e.preventDefault();
		e.stopPropagation();

		let form = e.currentTarget;

		if ( form.disabled ) {
			return;
		}

		let favoriteForm = new XMLHttpRequest();
		let data = new FormData( form );

		form.disabled = true;
		form.classList.add( 'frm_loading_form' );

		let errors = form.querySelectorAll( '.frm_error' );
		for( let err of errors ) {
			err.parentNode.removeChild( err );
		}

		favoriteForm.open( 'POST', '/wp-json/vestorfilter/v1/favorites/friend-save' );
		favoriteForm.setRequestHeader( 'X-WP-Nonce', window.vestorAccount.getNonce() );
		favoriteForm.send( data );
		favoriteForm.responseType = 'json';
		favoriteForm.addEventListener( 'load', ( { target } ) => {

			const { status, response } = target;

			let message = document.createElement( 'div' );
			message.classList.add( 'frm_error' );
			message.innerHTML = `<p>${response.message}</p>`;

			if ( status === 200 ) {

				message.classList.add( 'frm_message' );
				let field = form.querySelector( 'input[name="friend_email"]' );
				if ( field ) {
					field.value = '';
				}

			} else {

				message.classList.add( 'frm_error' );

			}

			form.append( message );
			form.disabled = false;
			form.classList.remove( 'frm_loading_form' );

		} );

	};

	const deleteFriendFavorite = ( e ) => {

		e.preventDefault();
		e.stopPropagation();

		let btn = e.currentTarget;

		let favoriteForm = new XMLHttpRequest();
		let data = new FormData();
		data.set( 'user_id', btn.dataset.favoriteUser );
		data.set( 'property_id', btn.dataset.favoriteRemove );


		favoriteForm.open( 'POST', '/wp-json/vestorfilter/v1/favorites/friend-remove' );
		favoriteForm.setRequestHeader( 'X-WP-Nonce', window.vestorAccount.getNonce() );
		favoriteForm.send( data );
		favoriteForm.responseType = 'json';
		favoriteForm.addEventListener( 'load', ( { target } ) => {

			const { status, response } = target;

			if ( status === 200 ) {

				btn.closest( '.vf-property' ).remove();

			}

		} );

	};

	const saveSearchForUser = ( nonce ) => {

		let searchForm = document.querySelector( 'form[data-vestor-search]' );
		let hash = searchForm.dataset.vestorSearch;

		let hashField = document.getElementById( 'field_saved_search_query_hash' );
		if ( hash && hashField ) {
			hashField.value = hash;
		}

		let filtersField = document.getElementById( 'field_saved_search_filters' );
		if ( filtersField ) {
			filtersField.value = vestorFilters.getFilterQuery();
		}

		let nameField = document.getElementById( 'field_saved_search_name' );
		if ( nameField ) {
			nameField.value = '';
		}

		let nonceField = document.getElementById( 'field_saved_search_name' );
		if ( nameField ) {
			nameField.value = '';
		}

		let formMessages = document.querySelector( `#subscribe-for-user-modal .frm_message` );
		if ( formMessages ) {
			formMessages.parentNode.removeChild( formMessages );
		}

		toggleModal( 'subscribe-for-user-modal', true, true );

	};

	const trashSearch = ( { currentTarget } ) => {

		Cookies.set( 'has_shown_favorite_reminder', 'yes', { expires: 30, path: '/' } );

		let nonce = currentTarget.dataset.vestorSave;

		deleteSearch( currentTarget.dataset.trash, nonce, currentTarget.dataset.user );
		currentTarget.parentNode.remove();


	};

	const saveSubscription = ( e ) => {

		let { currentTarget } = e;

		let nonce = currentTarget.dataset.vestorSubscribe;
		let hash = currentTarget.dataset.hash;
		let user = currentTarget.dataset.user;

		var data = new FormData();
		data.append( 'hash', hash );
		data.append( 'frequency', currentTarget.value );
		data.append( 'nonce', nonce );
		data.append( 'user', user );
		

		let subscriptionXHR = new XMLHttpRequest();

		subscriptionXHR.open( 'POST', '/wp-json/vestorfilter/v1/favorites/subscribe' );
		subscriptionXHR.setRequestHeader( 'X-WP-Nonce', window.vestorAccount.getNonce() );
		subscriptionXHR.send( data );
		subscriptionXHR.responseType = 'json';
		

	};

	const saveSearchName = ( e ) => {

		let { currentTarget } = e;

		currentTarget.parentNode.classList.add( 'is-saving' );
		currentTarget.parentNode.classList.remove( 'is-saved' );

		let nonce = currentTarget.dataset.searchName;
		let hash = currentTarget.dataset.hash;
		let user = currentTarget.dataset.user;

		var data = new FormData();
		data.append( 'hash', hash );
		data.append( 'name', currentTarget.value );
		data.append( 'nonce', nonce );
		data.append( 'user', user );
		

		let subscriptionXHR = new XMLHttpRequest();

		subscriptionXHR.open( 'POST', '/wp-json/vestorfilter/v1/favorites/name' );
		subscriptionXHR.setRequestHeader( 'X-WP-Nonce', window.vestorAccount.getNonce() );
		subscriptionXHR.send( data );
		subscriptionXHR.responseType = 'json';
		subscriptionXHR.addEventListener( 'load', ( e ) => {
			currentTarget.parentNode.classList.remove( 'is-saving' );
			currentTarget.parentNode.classList.add( 'is-saved' );
		} );
		

	};


	const toggleFavorite = ( propertyId, forceState ) => {

		let allToggles = document.querySelectorAll( `[data-vestor-favorite="${propertyId}"]` );
		for ( let toggle of allToggles ) {
			if ( ! forceState ) {
				toggle.classList.toggle( 'is-favorite' );
			} else if ( forceState === 'on' ) {
				toggle.classList.add( 'is-favorite' );
			} else if ( forceState === 'off' ) {
				toggle.classList.remove( 'is-favorite' );
			}
		}

		var data = new FormData();
		data.append( 'property', propertyId );
		if ( forceState ) {
			data.append( 'state', forceState );
		}
		let favoriteXHR = new XMLHttpRequest();

		favoriteXHR.open( 'POST', '/wp-json/vestorfilter/v1/favorites/toggle' );
		favoriteXHR.setRequestHeader( 'X-WP-Nonce', window.vestorAccount.getNonce() );
		favoriteXHR.send( data );
		favoriteXHR.responseType = 'json';
		favoriteXHR.addEventListener( 'load', setFavoritesList );

	};

	const toggleSearch = ( nonce, forceState ) => {

		let allToggles = document.querySelectorAll( `button[data-vestor-save="${nonce}"]` );
		let oneMore = 1;
		for ( let toggle of allToggles ) {
			if ( ! forceState ) {
				//toggle.classList.toggle( 'is-saved' );
			} else if ( forceState === 'on' ) {
				//toggle.classList.add( 'is-saved' );
			} else if ( forceState === 'off' ) {
				toggle.classList.remove( 'is-saved' );
			}
		}
		if ( allToggles.length ) {
			oneMore = allToggles[0].classList.contains( 'is-saved' ) ? 1 : 0;
		}
		
		let searchForm = document.querySelector( 'form[data-vestor-search]' );
		let filters = vestorFilters.getActiveFilters( searchForm );

		var data = new FormData();
		data.append( 'filters', JSON.stringify( filters ) );
		data.append( 'hash', searchForm.dataset.vestorSearch );
		data.append( 'verification', searchForm.dataset.vestorNonce );
		if ( forceState ) {
			data.append( 'state', forceState );
		}

		let saveXHR = new XMLHttpRequest();

		saveXHR.open( 'POST', '/wp-json/vestorfilter/v1/favorites/toggle-search' );
		saveXHR.setRequestHeader( 'X-WP-Nonce', window.vestorAccount.getNonce() );
		saveXHR.send( data );
		saveXHR.responseType = 'json';
		//saveXHR.addEventListener( 'load', setFavoritesList );

		
		if ( oneMore === 0 ) {
			delete savedSearches[ searchForm.dataset.vestorSearch ];
		}
		//setFavoritesCount( currentFavorites.length + Object.keys( savedSearches ).length + oneMore );
		setFavoritesCount( currentFavorites.length );



	};

	const deleteSearch = ( hash, nonce, user ) => {

		var data = new FormData();
		data.append( 'hash', hash );
		data.append( 'verification', nonce );
		if ( user ) {
			data.append( 'user', user );
		}

		let saveXHR = new XMLHttpRequest();

		saveXHR.open( 'POST', '/wp-json/vestorfilter/v1/favorites/trash-search' );
		saveXHR.setRequestHeader( 'X-WP-Nonce', window.vestorAccount.getNonce() );
		saveXHR.send( data );
		saveXHR.responseType = 'json';
		//saveXHR.addEventListener( 'load', setFavoritesList );

	};

	const setFavoritesList = ( { currentTarget } ) => {

		const { response } = currentTarget;
		if ( ! response ) {
			return;
		}

		if ( Array.isArray( response ) ) {
			currentFavorites = response;
		}
		//setFavoritesCount( currentFavorites.length + Object.keys( savedSearches ).length );
		setFavoritesCount( currentFavorites.length );

	};

	const setFavoritesCount = ( count ) => {

		if ( ! savedCounter ) {
			let wrapper = document.querySelector( '.saved-count a' );
			if ( ! wrapper ) {
				return;
			}
			savedCounter = document.createElement( 'span' );
			savedCounter.classList.add( 'saved-count__count' );
			wrapper.append( savedCounter );
		}

		savedCounter.innerHTML = count ? '(' + count + ')' : '';

	};

	const showSubscriptionModal = () => {

		let searchForm = document.querySelector( 'form[data-vestor-search]' );
		let hash = searchForm.dataset.vestorSearch;
		let hashField = document.getElementById( 'field_search_query_hash' );
		if ( hash && hashField ) {
			hashField.value = hash;
		}

		if ( mySubscriptions.hasOwnProperty( hash ) ) {
			let hashValue = mySubscriptions[hash];
			let fieldToSet = document.querySelector( `div[aria-labelledby="field_email_update_frequency_label"] input[value="${hashValue}"]` );
			if ( fieldToSet ) {
				fieldToSet.checked = true;
			}
		}

		let searchNameInput = document.querySelector( `input#field_search_name` );
		if ( searchNameInput ) {
			searchNameInput.value = '';
		}

		let formMessages = document.querySelector( `#subscribe-modal .frm_message` );
		if ( formMessages ) {
			formMessages.parentNode.removeChild( formMessages );
		}

		toggleModal( 'subscribe-modal', true );

	};

	const saveForUserComplete = ( event, form, response ) => {

		vfDebug( form, response );

		if ( form.id !== 'form_save_search_for_user' ) {
			return;
		}

		let searchForm = document.querySelector( 'form[data-vestor-search]' );
		let hash = searchForm.dataset.vestorSearch;
		let hashField = document.getElementById( 'field_saved_search_query_hash' );
		if ( hash && hashField ) {
			hashField.value = hash;
		}

		let filtersField = document.getElementById( 'field_saved_search_filters' );
		if ( filtersField ) {
			filtersField.value = vestorFilters.getFilterQuery();
		}

	}

	const subscriptionPreferenceSaved = ( event, form, response ) => {

		if ( form.id !== 'form_search_update_preferences' ) {
			return;
		}

		let searchForm = document.querySelector( 'form[data-vestor-search]' );
		let hash = searchForm.dataset.vestorSearch;
		let hashField = document.querySelector( '#field_search_query_hash' );
		
		if ( hash && hashField ) {
			hashField.value = hash;
		}

		let savedValue = form.querySelector( 'div[aria-labelledby="field_email_update_frequency_label"] input:checked' );
		if ( savedValue ) {
			let fieldToSet = document.querySelector( `div[aria-labelledby="field_email_update_frequency_label"] input[value="${savedValue.value}"]` );
			if ( fieldToSet ) {
				fieldToSet.checked = true;
			}
		}

		let searchName = form.querySelector( 'input#field_search_name' );
		if ( searchName ) {
			let fieldToSet = document.querySelector( `input#field_search_name` );
			if ( fieldToSet ) {
				fieldToSet.value = searchName.value;
			}
		}

	}

	const showSearchReminder = () => {

		let saveBtnText = document.querySelector( '#vf-subfilter-panel .btn-save .screen-reader-text' );
		if ( ! saveBtnText ) {
			return;
		}

		if ( saveBtnText.parentElement.classList.contains( 'has-been-clicked' ) ) {
			return;
		}

		saveBtnText.classList.remove( 'screen-reader-text' );
		saveBtnText.classList.add( 'btn--tooltip' );
		saveBtnText.parentNode.classList.add( 'has-tooltip' );

		saveBtnText.parentNode.addEventListener( 'click', ( {currentTarget} ) => {
			currentTarget.classList.add( 'has-been-clicked' );
			
		} );
		saveBtnText.parentNode.addEventListener( 'mouseover', ( {currentTarget} ) => {
			currentTarget.classList.add( 'has-been-clicked' );
		} );
		

		window.setTimeout( ( btn ) => {
			btn.classList.add( 'is-visible' );
			document.addEventListener( 'click', () => {
				btn.classList.remove( 'is-visible' );
			} );
		}, 200, saveBtnText );

		window.setTimeout( ( btn ) => {
			btn.classList.remove( 'is-visible' );
		}, 7000, saveBtnText );

	}

	const inviteFriend = (e) => {

		e.preventDefault();
		e.stopPropagation();

		let form = e.currentTarget;

		if ( form.disabled ) {
			return;
		}

		let invitationForm = new XMLHttpRequest();
		let data = new FormData( form );

		form.disabled = true;
		form.classList.add( 'frm_loading_form' );

		let errors = form.querySelectorAll( '.frm_error' );
		for( let err of errors ) {
			err.parentNode.removeChild( err );
		}

		invitationForm.open( 'POST', '/wp-json/vestorfilter/v1/account/invite' );
		invitationForm.setRequestHeader( 'X-WP-Nonce', window.vestorAccount.getNonce() );
		invitationForm.send( data );
		invitationForm.responseType = 'json';
		invitationForm.addEventListener( 'load', ( { target } ) => {

			const { status, response } = target;

			let message = document.createElement( 'div' );
			message.classList.add( 'frm_error' );
			message.innerHTML = `<p>${response.message}</p>`;

			if ( status === 200 ) {

				message.classList.add( 'frm_message' );
				let field = form.querySelector( 'input[name="friend_email"]' );
				if ( field ) {
					field.value = '';
				}

				let button = document.querySelector( 'button[aria-controls="existing-invitation-modal"]' );
				if ( button ) {
					button.style.display = 'none';
				}

			} else {

				message.classList.add( 'frm_error' );

			}

			form.append( message );
			form.disabled = false;
			form.classList.remove( 'frm_loading_form' );

		} );

	}

	jQuery( document ).on( 'frmFormComplete', subscriptionPreferenceSaved );
	jQuery( document ).on( 'frmFormComplete', saveForUserComplete );
	
	if ( document.readyState == 'complete' ) {
		initialize();
	} else {
		document.addEventListener( 'DOMContentLoaded', initialize );
	}

	document.addEventListener( 'vestorfilters|properties-refreshed', initialize );
	document.addEventListener( 'vestorfilters|properties-refreshed', initialize );

	return {
		initialize,
		addFavorite,
		getFavorites,
		//saveSearch,
		saveSearchForUser
	};

} )();

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

		// following is the code which switch
		// filter from property per square meter to
		// property longest on the market.
		/*
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
		*/

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
		let valueLabelText,
			format = currentTarget.getAttribute('data-format');

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
					valueLabelText = formatFunc( input.dataset.valueMin, format ) + '+';
				} else if ( input.dataset.valueMin === '' && input.dataset.valueMax !== '' ) {
					valueLabelText = formatFunc( input.dataset.valueMax, format ) + '-';
				} else if ( input.dataset.valueMin !== '' && input.dataset.valueMax !== '' ) {
					valueLabelText = formatFunc( input.dataset.valueMin, format ) + ' - ' + formatFunc( input.dataset.valueMax, format );
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
			}

		} else {			
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
( function( $ ) {

	$( document ).on( 'input change', 'input[data-frmrange]', function( e ) {
		
		let self = e.currentTarget;
		let valueObj = self.parentNode.querySelector( '.frm_range_value' );

		valueObj.innerHTML = self.value;

	} );

	$( document ).on( 'input change', '.frm_form_field input', function () {

		let downPayment = document.getElementById( 'field_downpayment_amt' );
		let salePrice = document.getElementById( 'field_mortgage_amt' );
		let pmi = document.getElementById( 'field_pmi' );

		if ( pmi && downPayment && salePrice ) {

			if ( downPayment.value / salePrice.value < 0.2 && pmi.value == '0' ) {

				pmi.value = '1.5';
				pmi.parentNode.querySelector( '.frm_range_value' ).innerHTML = '1.5';

			}

		}

	} );

	const setupShareUrls = () => {
		
		let currentUrl = window.location
		let newUrlString = currentUrl.protocol + '//' + currentUrl.hostname + currentUrl.pathname;

		let shareLinks = document.querySelectorAll( '[data-form-share]' );
		for ( let sharer of shareLinks ) {

			let $thisForm = $( sharer ).closest( 'form' );
			let fieldValues = [];

			$thisForm.find( '.is-sharable input' ).each( ( index, el ) => {
				fieldValues.push( el.id + '=' + el.value );
			} );

			let shareString = newUrlString + '?' + fieldValues.join( '&' );

			let shareAnchors = sharer.querySelectorAll( 'a' );
			for ( let anchor of shareAnchors ) {
				let replaceWith = anchor.href;
				if ( anchor.dataset.originalUrl ) {
					replaceWith = anchor.dataset.originalUrl;
				} else {
					anchor.dataset.originalUrl = anchor.href;
				}
				replaceWith = replaceWith.replace( 'REPLACE_THIS', encodeURIComponent( shareString ) );
				anchor.href = replaceWith;
			}
			let copyBtn = sharer.querySelector( '[data-copy-url]' );
			let replaceWith = copyBtn.dataset.copyUrl;
			if ( copyBtn.dataset.originalUrl ) {
				replaceWith = copyBtn.dataset.originalUrl;
			} else {
				copyBtn.dataset.originalUrl = copyBtn.dataset.copyUrl;
			}
			replaceWith = replaceWith.replace( 'REPLACE_THIS', shareString );
			copyBtn.dataset.copyUrl = replaceWith;

		}

	};

	setupShareUrls();

	$( document ).on( 'input change', '.frm_form_field input', setupShareUrls );

	const setupPropertyAccordions = ( parentNode ) => {

		if ( ! parentNode ) {
			parentNode = document;
		}

		let ctaH3 = parentNode.querySelector( '.mortgage-calculator .calculator-cta h3' );
		let submitSection;

		if ( ctaH3 ) {
			ctaH3.innerHTML = 'Get Pre-approved';
			submitSection = parentNode.querySelector( '.mortgage-calculator .frm_submit' );
			submitSection.setAttribute( 'aria-hidden', 'true' );
		}

		let sections = parentNode.querySelectorAll( 
			'.mortgage-calculator .calculator-fields, .mortgage-calculator .calculator-adtl, .mortgage-calculator .calculator-cta'
		);
		for ( let section of sections ) {

			section.classList.add( 'collapse' );
			section.classList.add( 'accordion' );
			section.setAttribute( 'aria-hidden', 'true' );

			let sectionH3 = section.querySelector( 'h3' );

			let sectionBtn = document.createElement( 'button' );
			sectionBtn.setAttribute( 'aria-controls', section.id );
			sectionBtn.setAttribute( 'aria-expanded', 'false' );
			sectionBtn.classList.add( 'btn' );
			sectionBtn.classList.add( 'with-icon' );
			sectionBtn.classList.add( 'with-icon__down-caret' );
			sectionBtn.classList.add( 'accordion-toggle' );
			sectionBtn.innerHTML = `<span>${sectionH3.innerHTML}</span>`;
			sectionBtn.type = "button";
			sectionBtn.addEventListener( 'click', (e) => {
				e.preventDefault();
				e.stopPropagation();
				
				a11yToggleExpand( e );
				

				/*let thisSection = document.getElementById( e.currentTarget.getAttribute( 'aria-controls' ) );
				if ( thisSection.classList.contains( 'calculator-cta' ) ) {
					if ( e.currentTarget.getAttribute( 'aria-expanded' ) === "true" ) {
						submitSection.setAttribute( 'aria-hidden', 'false' );
					} else {
						submitSection.setAttribute( 'aria-hidden', 'true' );
					}
				}*/
			} );

			section.parentNode.insertBefore( sectionBtn, section );
			section.addEventListener( 'click', (e) => {
				e.stopPropagation();
			} );

		}

	};

	document.addEventListener( 'vestorfilters|calculator-attached', ( { detail } ) => {
		setupPropertyAccordions( detail );
	} );
	
	const disableSwiping = ( disableTarget ) => {

		disableTarget.dataset.noSwiping = true;

	};

	/*let calcColumn = document.querySelector( '.property-template__calculator' );
	if ( calcColumn ) {
		setupPropertyAccordions( calcColumn );
		let sliders = calcColumn.querySelectorAll( '.frm_range_container' );
		for ( let slider of sliders ) {
			disableSwiping( slider.parentNode );
		}
	}

	document.addEventListener( 'vestorfilters|property-opened', ( { detail } ) => {
		
		let calculator = document.getElementById( 'mortgage-calculator' );
		let placeItShouldGo = detail.querySelector( '.property-template__secondary .column-left' );

		if ( calculator && placeItShouldGo ) {
			if ( placeItShouldGo.querySelector( '#mortgage-calculator') ) {
				return;
			}
			placeItShouldGo.append( calculator );
			calculator.querySelector( 'h3' ).innerHTML = detail.originalCalculator.querySelector('h3').innerHTML;
			let fieldsToSwap = detail.originalCalculator.querySelectorAll( 'input:not([type="hidden"])' );
			//vfDebug( fieldsToSwap );
			for( let field of fieldsToSwap ) {

				let frmField = calculator.querySelector( '#' + field.id );
				if ( frmField ) {
					frmField.value = field.value;
				}
				$( frmField ).trigger("change");
			}
			$( calculator ).find('#field_mortgage_amt,#field_property_tax_value').trigger('change');

			if ( calculator.style.display === 'none' ) {
				calculator.style.display = '';
			}
		}

	} );*/

	/*$("form.mortgage-calculator").on( "keydown", ":input:not(textarea):not(:submit)", function( event ) {
		if ( event.keyCode === 13 ) {
			event.preventDefault();
		}
	});*/

} )( jQuery );

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
				mapId: "a82416e87661f8b6",
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
window.vestorMessages = ( function () {

	const show = ( messageText, status, delayClose ) => {

		clearCurrentMessages( false );

		let panel = makeNewMessage( messageText );
		if ( status ) {
			panel.classList.add( `is-${status}` );
		}

		document.body.prepend( panel );

		window.setTimeout( ( panel ) => {

			panel.setAttribute( 'aria-hidden', 'false' );

		}, 10, panel );

		if ( delayClose ) {

			window.setTimeout( ( panel ) => {

				panel.setAttribute( 'aria-hidden', 'true' );
	
			}, delayClose, panel );

		}

	};

	const makeNewMessage = ( text ) => {

		let panel = document.createElement( 'div' );
		panel.classList.add( 'popup-message' );
		panel.setAttribute( 'aria-hidden', 'true' );

		panel.innerHTML = text;

		return panel;

	};

	const clearCurrentMessages = ( animate ) => {

		let timeout = ( animate ) ? 600 : 0;

		let messages = document.querySelectorAll( '.popup-message' );
		for ( let panel of messages ) {
			panel.setAttribute( 'aria-hidden', 'true' );
		}

		window.setTimeout( ( messagePanels ) => {

			for ( let panel of messagePanels ) {
				panel.remove();
			}

		}, timeout, messages );

	};

	document.addEventListener( 'mousedown', () => {

		clearCurrentMessages( true );

	} );

	

	return {
		show
	};

} )();

document.dispatchEvent( new Event( 'vestorfilters|messages-ready' ) );
"use strict";

const toggleModal = ( modalId, newState, noAutoClose ) => {
	
	const modal = document.getElementById( modalId );
	if ( ! modal ) {
		return;
	}

	const isOpen = newState !== undefined ? ! newState : ( modal.getAttribute( 'aria-hidden' ) === 'false' );

	const buttons = document.querySelectorAll( `button[aria-controls="${modalId}"]` );
	for ( let btn of buttons ) {
		btn.setAttribute( 'aria-expanded', isOpen ? 'false' : 'true' );
	}

	modal.setAttribute( 'aria-hidden', isOpen ? 'true' : 'false' );

	if ( ! isOpen ) {
		document.body.classList.add( 'modal-open' );
		if ( noAutoClose ) {
			document.body.classList.add( 'modal-stay-open' );
		}
		document.dispatchEvent( new Event( 'modal-opened' ) );
	} else {
		document.body.classList.remove( 'modal-open' );
	}

};

( function() {

	document.addEventListener( 'click', (e) => {
		if ( e.target.closest( '.select2-dropdown' ) ) {
			e.stopPropagation();
			return;
		}
		let modal = e.target.closest( '.popup__overlay' )
		if ( modal ) {
			e.stopPropagation();
			toggleModal( modal.parentNode.id, false );
			return;
		}
		if ( e.target.closest( '.popup' ) ) {
			e.stopPropagation();
			return;
		}
		
	} );

} )();


"use strict";

function scrollToDom( jumpTo ) {

	document.body.classList.remove( 'locked' );

	var scrollOffset = document.body.classList.contains( 'admin-bar' ) ? 40 : 0;

	window.scrollTo({
		left: 0,
		top: jumpTo.getBoundingClientRect().top + window.scrollY - 60 - scrollOffset,
		behavior: 'smooth'
	});

}

function scrollToAnchor( self ) {

	var jumpTo = document.querySelector( self.getAttribute( 'href' ) );

	if ( ! jumpTo ) {
		return true;
	}

	document.body.classList.remove( 'locked' );

	scrollToDom( jumpTo );

}

( function() {

	document.addEventListener( 'click', ( e ) => {
		let anchor = e.target.closest( 'a[href^="#"]' );
		if ( anchor ) {
			scrollToAnchor( anchor );
			e.preventDefault();
		}
	} );

} )();
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

		let lookupUrl = '/wp-json/vestorfilter/v1/search/map-data';
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
		let querySearch = searchQuery.toLowerCase().replace(/[^a-zA-Z0-9- ]/gi,'');

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
				let optionValue = data.value.toLowerCase().replace(/[^a-zA-Z0-9-]/gi,'');
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

				let searchKeyword = optionList.querySelector( '[data-search="keyword"]' );
				if ( searchKeyword ) {
					optionList.insertBefore( cloneOption, searchKeyword.parentNode );
				} else {
					optionList.append( cloneOption );
				}
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

( function() {

	let bioPopup = document.getElementById( 'team-bio' );
	if ( ! bioPopup ) {
		return;
	}

	const openBio = (e) => {
		e.preventDefault();
		e.stopPropagation();

		let self = e.currentTarget;
		let block = self.parentNode;
		let name = block.querySelector( '.wp-block-vestorfilter-team-member--name' );
		let description = block.querySelector( '.wp-block-vestorfilter-team-member--bio' );

		bioPopup.querySelector( 'h2' ).innerHTML = name.innerHTML;
		bioPopup.querySelector( '.bio-contents' ).innerHTML = description.innerHTML;

		toggleModal( 'team-bio' );
	};

	let teamMembers = document.querySelectorAll( '.wp-block-vestorfilter-team-member' );
	for( let block of teamMembers ) {

		let hasBio = block.querySelector( '.wp-block-vestorfilter-team-member--bio' );
		if ( ! hasBio ) {
			continue;
		}

		let afterPlace = block.querySelector( '.wp-block-vestorfilter-team-member--subtitle' );
		if ( ! afterPlace ) {
			block.querySelector( 'h3' );
		}
		if ( ! afterPlace ) {
			continue;
		}

		let newLink = document.createElement( 'a' );
		newLink.href = '#bio';
		newLink.addEventListener( 'click', openBio );
		newLink.innerHTML = 'READ BIO';
		newLink.classList.add( 'wp-block-vestorfilter-team-member--bio-link' )

		if ( afterPlace.nextElementSibling ) {
			block.insertBefore( newLink, afterPlace.nextElementSibling );
		} else {
			block.append( newLink );
		}
		
	}

} )();
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
( function() {

	let xDown, yDown;

	function getTouches(evt) {
		return evt.touches || evt.originalEvent.touches;
	}

	const handleTouchStart = function (evt) {
			const firstTouch = getTouches(evt)[0];
			xDown = firstTouch.clientX;
			yDown = firstTouch.clientY;
	};

	const handleTouchMove = function (evt) {

		if ( document.body.classList.contains( 'lg-on' ) ) {
			return;
		}

		if ( ! xDown || ! yDown ) {
				return;
		}

		let xUp = evt.touches[0].clientX;
		let yUp = evt.touches[0].clientY;

		let xDiff = xDown - xUp;
		let yDiff = yDown - yUp;

		let node = evt.target;
		while( node.parentNode ) {
			if ( node.dataset.noSwiping ) {
				//vfDebug( node );
				return;
			}
			node = node.parentNode;
		}

		if ( Math.abs( xDiff ) > Math.abs( yDiff ) ) {
			if ( xDiff > 0 ) {
				document.dispatchEvent( new CustomEvent( 'swipe.right', { detail: evt } ) );
			} else {
				document.dispatchEvent( new CustomEvent( 'swipe.left', { detail: evt } ) );
			}
		} else {
			if ( yDiff > 0 ) {
				document.dispatchEvent( new CustomEvent( 'swipe.up', { detail: evt } ) );
			} else {
				document.dispatchEvent( new CustomEvent( 'swipe.down', { detail: evt } ) );
			}
		}
		/* reset values */
		xDown = null;
		yDown = null;
	};

	document.addEventListener( 'touchstart', handleTouchStart, false );
	document.addEventListener( 'touchmove', handleTouchMove, false );
	//document.addEventListener( 'swipe.right', gotoNextProperty, { passive: false } );
	//document.addEventListener( 'swipe.left', gotoPreviousProperty, { passive: false } );

} )();