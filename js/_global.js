var vfDebug = ( ...params ) => {
	if ( ! vfDebugMode ) {
		return;
	}
	console.log( ...params );
};
var vfTrace = ( ...params ) => {
	if ( ! vfDebugMode ) {
		return;
	}
	console.trace( ...params );
};

var debounce = ( fn, timeout, ...args ) => {

	let debounceArgs = {};
	if ( fn.method ) {
		debounceArgs = fn;
	} else {
		debounceArgs.method = fn;
	}
	if ( timeout ) {
		debounceArgs.timeout = timeout;
	}

	if ( debounceArgs.method.priority && ! debounceArgs.priority ) {
		return;
	}

	if ( debounceArgs.method.debounceTimer ?? false ) {
		clearTimeout( debounceArgs.method.debounceTimer );
	}

	debounceArgs.method.debounceTimer = setTimeout( debounceArgs.method, debounceArgs.timeout, ...args );
	if ( debounceArgs.priority ) {
		debounceArgs.method.priority = debounceArgs.priority;
	}

}

var makeWPSlug = ( text ) => {

	let replacement = text + '';
	replacement = replacement.toLowerCase();
	replacement = replacement.replaceAll( /^[a-z0-9]/g, '-' );
	replacement = replacement.replaceAll( '--', '-' );

	return replacement;

};


( function() {
	"use strict";

	const setupComplianceDates = () => {

		let sources = document.querySelectorAll( '.compliance-date' );
		for ( let source of sources ) {

			let xhr = new XMLHttpRequest();
			xhr.open( "GET", '/wp-json/vestorfilter/v1/source/last-updated' );
			xhr.send();
			xhr.responseType = 'json';
			xhr.sourceObj = source;

			xhr.addEventListener( "load", ( e ) => {

				let { response, status } = e.target;

				if ( status === 200 ) {

					e.target.sourceObj.innerHTML = response.date;

				}

			} );

		}

	};

	
	if ( document.readyState === "complete" ) {
		setupComplianceDates();
	} else {
		document.addEventListener( 'DOMContentLoaded', setupComplianceDates );
	}

} )();