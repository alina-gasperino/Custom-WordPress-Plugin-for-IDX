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