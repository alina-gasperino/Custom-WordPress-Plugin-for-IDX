window.vfSSO_initGoogle = () => {

	if ( ! vfSSO_keys.google ) {
		return;
	}

	gapi.load( 'auth2', () => {

		gapi.auth2.init( {
			client_id: vfSSO_keys.google + '.apps.googleusercontent.com',
		} ).then( () => {
			let auth2 = gapi.auth2.getAuthInstance();
			auth2.signOut().then( () => {

				let btns = document.querySelectorAll( '[data-vf-sso="google"]' );
				for ( let btn of btns ) {
					let useAjax = ( btn.dataset.ajax === "true" );

					gapi.signin2.render( btn, {
						'longtitle': true,
						'height': 50,
						'scope': 'profile https://www.googleapis.com/auth/user.phonenumbers.read',
						onsuccess: useAjax ?
							vfSSO_ajaxSigninGoogle :
							vfSSO_reloadSigninGoogle
					} );

					btn.parentNode.style.display = '';
					btn.parentNode.classList.add( 'ready' );
				}
			} );

		} );

	} );

}

if ( vfSSO_keys.facebook ) {

	window.fbAsyncInit = function() {

		//console.log( 'facebook ready' );

		FB.init({
			appId            : vfSSO_keys.facebook,
			autoLogAppEvents : true,
			xfbml            : true,
			cookie           : true,
			version          : 'v8.0'
		});

		let btns = document.querySelectorAll( '[data-vf-sso="facebook"]' );
		for ( let btn of btns ) {
			let useAjax = ( btn.dataset.ajax === "true" );

			let loginBtn = document.createElement( 'button' );
			loginBtn.innerHTML = 'Facebook';
			loginBtn.classList.add( 'sso-btn' );
			loginBtn.classList.add( 'sso-btn__login' );
			loginBtn.classList.add( 'sso-btn__login--facebook' );

			loginBtn.addEventListener( 'click', vfSSO_tryFacebook );
			loginBtn.useAjax = useAjax;

			btn.append( loginBtn );

			btn.parentNode.style.display = '';
			btn.parentNode.classList.add( 'ready' );
		}
	};

}

const vfSSO_tryFacebook = ( { currentTarget } ) => {

	if ( FB ) {

		FB.login( function( response ) {
			if ( response.authResponse ) {
				//console.log( response );
				let fbdata = new FormData();
				fbdata.append( 'url', window.location.href );
				fbdata.append( 'method', 'facebook' );
				fbdata.append( 'token', response.authResponse.accessToken );
				fbdata.append( 'uid', response.authResponse.userID );
				FB.api('/' + response.authResponse.userID + '/?fields=id,name,email', () => {} );

				document.body.classList.add( 'is-loading-sso' );
				if ( currentTarget.useAjax ) {
					vfSSO_signin( new URLSearchParams(fbdata).toString(), () => {} );
				} else {
					vfSSO_signin( new URLSearchParams(fbdata).toString() );
				}

			} else {
				document.dispatchEvent( new CustomEvent( 'vestorfilter|login-failure', { detail: { message: 'Facebook login canceled' } } ) );
			}
		}, { scope: 'email,public_profile' } );

	}

}

if ( vfSSO_keys.facebook ) {

	window.fbAsyncInit = function() {

		//console.log( 'facebook ready' );

		FB.init({
			appId            : vfSSO_keys.facebook,
			autoLogAppEvents : true,
			xfbml            : true,
			cookie           : true,
			version          : 'v8.0'
		});

		let btns = document.querySelectorAll( '[data-vf-sso="facebook"]' );
		for ( let btn of btns ) {
			let useAjax = ( btn.dataset.ajax === "true" );

			let loginBtn = document.createElement( 'button' );
			loginBtn.innerHTML = 'Facebook';
			loginBtn.classList.add( 'sso-btn' );
			loginBtn.classList.add( 'sso-btn__login' );
			loginBtn.classList.add( 'sso-btn__login--facebook' );

			loginBtn.addEventListener( 'click', vfSSO_tryFacebook );
			loginBtn.useAjax = useAjax;

			btn.append( loginBtn );

			btn.parentNode.style.display = '';
			btn.parentNode.classList.add( 'ready' );
		}
	};

}

if ( vfSSO_keys.linkedin ) {

	//function vfSSO_setupLinkedin( e ) {

		//IN.Event.on( IN, "auth", vfSSO_tryLinkedin );

	let btns = document.querySelectorAll( '[data-vf-sso="linkedin"]' );
	for ( let btn of btns ) {
		let useAjax = ( btn.dataset.ajax === "true" );

		let loginBtn = document.createElement( 'button' );
		loginBtn.innerHTML = 'LinkedIn';
		loginBtn.classList.add( 'sso-btn' );
		loginBtn.classList.add( 'sso-btn__login' );
		loginBtn.classList.add( 'sso-btn__login--linkedin' );

		loginBtn.addEventListener( 'click', vfSSO_tryLinkedin );
		loginBtn.useAjax = useAjax;

		btn.append( loginBtn );

		btn.parentNode.style.display = '';
		btn.parentNode.classList.add( 'ready' );
	}
	//}


	function vfSSO_tryLinkedin( { currentTarget } ) {

		let url = 'https://www.linkedin.com/oauth/v2/authorization';
		url += `?response_type=code`;
		url += `&client_id=${vfSSO_keys.linkedin}`;
		url += `&redirect_uri=${vfSSO_oauthRedirect}`;
		url += `&scope=r_liteprofile%20r_emailaddress`;

		let features = 'resizable,scrollbars,status,width=400,height=700';
		if ( window.screen.top ) {
			features += ',top=' + ( window.screen.top + window.screen.height/2 - 350 );
			features += ',left=' + ( window.screen.left + window.screen.width/2 - 200 );
		} else if ( window.screenTop ) {
			features += ',top=' + ( window.screenTop + window.outerHeight/2 - 350 );
			features += ',left=' + ( window.screenLeft + window.outerWidth/2 - 200 );
		}

		let authWindow = window.open( url, 'linkedin_sso', features );

		window.addEventListener("message", (event) => {

			//authWindow.close();

			if ( event.isTrusted && event.origin == vfSSO_oauthOrigin ) {
				if ( event.data.sso === "login" || event.data.sso === "registered" ) {

					document.body.classList.add( 'is-loading-sso' );
					if ( currentTarget.useAjax ) {
						document.dispatchEvent( new CustomEvent( 'vestorfilter|login-success', { detail: event.data } ) );
					} else {
						window.location.reload();
					}

				}
			}

		}, false);

		/*IN.User.authorize( ( data ) => {
			console.log( data );
			IN.API.Profile("me").result( function(me) {
				var lidata = new FormData();
				lidata.append( 'method', 'linkedin' );
				lidata.append( 'id', me.values[0].id );
				console.log( me );
			});
		} );*/


		/*if ( currentTarget.useAjax ) {
			vfSSO_signin( new URLSearchParams(fbdata).toString(), () => {} );
		} else {
			vfSSO_signin( new URLSearchParams(fbdata).toString() );
		}*/
	}

}


const vfSSO_complete = ( { target } ) => {

	const { response, status } = target;

	if ( status === 200 ) {

		document.body.classList.remove( 'is-loading-sso' );
		document.dispatchEvent( new CustomEvent( 'vestorfilter|login-success', { detail: response } ) );

		if ( target.onSuccess ) {
			target.onSuccess( response );
		} else {
			window.location.reload();
		}

	} else {

		document.body.classList.remove( 'is-loading-sso' );
		document.dispatchEvent( new CustomEvent( 'vestorfilter|login-failure', { detail: response } ) );

		if ( target.onFail ) {
			target.onFail( response );
		}

	}

};

const vfSSO_ajaxSigninGoogle = ( googleUser ) => {

	let id_token = googleUser.getAuthResponse().id_token, access_code = googleUser.getAuthResponse().access_token;

	//console.log( 'use ajax method' );

	vfSSO_signin( 'method=google&idtoken=' + id_token + '&access=' + access_code, ( response ) => {} );

};

const vfSSO_reloadSigninGoogle = ( googleUser ) => {

	let id_token = googleUser.getAuthResponse().id_token, access_code = googleUser.getAuthResponse().access_token;

	//console.log( 'use reload method' );

	vfSSO_signin( 'method=google&idtoken=' + id_token + '&access=' + access_code );

};

const vfSSO_signin = ( data, onSuccess ) => {

	let xhr = new XMLHttpRequest();
	xhr.open( 'POST', window.vfSSO_authEndpoint );
	xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
	xhr.send( data );
	xhr.responseType = 'json';

	document.body.classList.add( 'is-loading-sso' );
	xhr.addEventListener( 'load', vfSSO_complete );
	if ( onSuccess ) {
		xhr.onSuccess = onSuccess;
	}

};
