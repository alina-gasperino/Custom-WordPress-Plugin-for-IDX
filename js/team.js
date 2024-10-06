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