( function( settings ) {

	var log = document.getElementById( 'vestorfilter-import-log' );
	var progress = document.getElementById( 'vestorfilter-import-progress' );
	var stop = document.getElementById( 'vestorfilter-import-cancel-btn' );
	var importList = [];
	var importIndex;
	var completed = 0;
	var isStopped = false;

	stop.addEventListener( 'click', function () {
		isStopped = true;
	} );

	function vf_getPropertyList() {

		var oReq = new XMLHttpRequest();
		var url = settings.ajaxListURL;
		url += '?_wpnonce=' + settings.ajaxNonce + '&source=' + settings.sourceId;

		oReq.open( "GET", url );
		oReq.send();
		oReq.responseType = 'json';

		oReq.addEventListener( "load", vf_syncProperties );

	}

	function vf_syncProperties( e ) {

		var response = e.target.response;
		var status = e.target.status;
		var message = document.createElement( 'p' );

		if ( status !== 200 ) {
			message.innerHTML = response.message;
			message.classList.add( 'error' );
		} else {

			/*for ( var index in response ) {
				if ( response.hasOwnProperty(index) ) {
					importList.push({
						mlsid: index,
						category: response[index]
					});
				}
			}*/
			for ( var i = 0; i < response.length; i += 1 ) {
				importList.push({
					mlsid: response[i].id,
					category: response[i].class
				});
			}

			message.innerHTML = importList.length + ' properties were found to update';
			message.classList.add( 'message' );

			for ( importIndex = 0; importIndex < 5 && importIndex < importList.length; importIndex += 1 ) {
				vf_importProperty( importIndex );
			}

		}

		log.append( message );

	}

	function vf_importProperty( index ) {

		var oReq = new XMLHttpRequest();
		var url = settings.ajaxSyncURL;

		oReq.open( "POST", url );
		oReq.responseType = 'json';
		oReq.setRequestHeader( 'X-WP-Nonce', settings.ajaxNonce );
		oReq.setRequestHeader( "Content-Type", "application/x-www-form-urlencoded" );

		oReq.send( 'source=' + settings.sourceId 
				 + '&property=' + importList[index].mlsid
				 + '&category=' + importList[index].category
		);

		oReq.propertyId = importList[index].mlsid;
		oReq.listIndex = index;

		oReq.addEventListener( "load", vf_propertyImported );
		oReq.addEventListener( 'error', function(e) {
			console.log( 'error', e );
			vf_importProperty( index );
		} );

	}

	function vf_propertyImported( e ) {

		var response = e.target.response;
		var status = e.target.status;
		var message = document.createElement( 'p' );

		completed += 1;

		if ( status !== 200 && response ) {
			console.log( e.target );
			message.innerHTML = e.target.propertyId + ' (error): ' + response.message;
			message.classList.add( 'error' );
		} else if ( status !== 200 ) { 
			console.log( e.target );
			message.innerHTML = e.target.propertyId + ' (Request failed)';
			message.classList.add( 'error' );
		}else {
			message.innerHTML = e.target.propertyId + ' was updated';
			message.classList.add( 'message' );

			if ( response.photos !== false ) {
				message.innerHTML += ' including ' + response.photos + ' photos';
			}
		}

		log.append( message );

		if ( isStopped ) {
			progress.innerHTML = 'CANCELED';
			return;
		}

		importIndex += 1;
		if ( completed >= importList.length - 1 ) {
			vf_propertyImportCompleted();
		} else if ( importIndex < importList.length ) {
			vf_importProperty( importIndex + 0 );
		}

		progress.innerHTML = Math.round( completed * 100 / importList.length ) + '%';

	}

	function vf_propertyImportCompleted() {

		progress.innerHTML = '100%';

		var oReq = new XMLHttpRequest();
		var url = settings.ajaxCompleteURL;
		
		oReq.open( "POST", url );
		oReq.setRequestHeader( 'X-WP-Nonce', settings.ajaxNonce );
		oReq.setRequestHeader( "Content-Type", "application/x-www-form-urlencoded" );

		oReq.send( 'source=' + settings.sourceId + '&time=' + settings.now );

		oReq.responseType = 'json';

		oReq.addEventListener( "load", function(e) {

			var response = e.target.response;
			var status = e.target.status;
			var message = document.createElement( 'p' );
	
			if ( status !== 200 ) {
				message.innerHTML = response.message;
				message.classList.add( 'error' );
			} else {
				message.innerHTML = 'Synchronization completed';
				message.classList.add( 'message' );
			}

			log.append( message );

		} );

	}

	vf_getPropertyList();

} )( vestorFilterImport );