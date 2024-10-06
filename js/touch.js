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