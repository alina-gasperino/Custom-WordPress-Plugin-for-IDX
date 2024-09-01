( function() {

	var dataBtn = document.querySelector( 'button[data-action="export-data"]' );
	if ( dataBtn ) {
		dataBtn.addEventListener( 'click', function() {

			window.location.href = vfExportData.url;

		} );
	}

} )();