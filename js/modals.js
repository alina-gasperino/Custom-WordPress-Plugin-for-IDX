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

