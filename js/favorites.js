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
		
		setFavoritesCount( currentFavorites.length + Object.keys( savedSearches ).length );

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
		setFavoritesCount( currentFavorites.length + Object.keys( savedSearches ).length + oneMore );



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

		setFavoritesCount( currentFavorites.length + Object.keys( savedSearches ).length );

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
