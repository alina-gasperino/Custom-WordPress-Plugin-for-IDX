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