( function( settings ) {

	var log = document.getElementById( 'vestorfilter-import-log' );
	var progress = document.getElementById( 'vestorfilter-import-progress' );
	var stop = document.getElementById( 'vestorfilter-import-cancel-btn' );
	var currentOffset = 0;
	var isStopped = false;

	stop.addEventListener( 'click', function () {
		isStopped = true;
	} );

	function vf_completedProperties( e ) {

		var response = e.target.response;
		var status = e.target.status;
		var message = document.createElement( 'p' );

		if ( status !== 200 ) {

			message.innerHTML = response ? response.message : 'There was an error with records ' + e.target.offset + ' - ' + (e.target.offset+100);
			message.classList.add( 'error' );

			log.append( message );

		}

		if ( isStopped === true ) {
			progress.innerHTML = 'CANCELED';
			return;
		}

		currentOffset += 100;
		if ( currentOffset < settings.totalProperties ) {
			vf_processProperties( currentOffset );
			progress.innerHTML = Math.round( currentOffset * 100 / settings.totalProperties ) + '%';
		} else {
			progress.innerHTML = '100%';
		}

	}

	function vf_processProperties( offset ) {

		var oReq = new XMLHttpRequest();
		var url = settings.ajaxProcessURL;

		oReq.open( "POST", url );
		oReq.responseType = 'json';
		oReq.setRequestHeader( 'X-WP-Nonce', settings.ajaxNonce );
		oReq.setRequestHeader( "Content-Type", "application/x-www-form-urlencoded" );

		oReq.send( 'source=' + settings.sourceId 
				 + '&offset=' + offset
				 + '&limit=100'
		);

		oReq.offset = offset;
		oReq.limit = 100;

		oReq.addEventListener( "load", vf_completedProperties );
		oReq.addEventListener( 'error', function(e) {
			console.log( 'error', e );
			vf_completedProperties(e);
		} );

	}

	for ( currentOffset = 0; currentOffset < 500 && currentOffset < settings.totalProperties; currentOffset += 100 ) {
		vf_processProperties( currentOffset );
	}

} )( vestorFilterProcess );