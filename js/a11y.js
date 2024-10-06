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

