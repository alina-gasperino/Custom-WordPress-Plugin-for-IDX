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
