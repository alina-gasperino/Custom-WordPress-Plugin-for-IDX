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
