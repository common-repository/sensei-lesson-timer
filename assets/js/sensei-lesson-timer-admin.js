window.SenseiTimerAdmin = window.SenseiTimerAdmin || {};

( function( window, document, $, app, undefined ) {
	'use strict';

	app.init = function() {
		app.$types = $( '#sensei-lesson-timer tr:first-of-type' );
		app.ajax( app.showTypes );
	};

	app.ajax = function( cb ) {
		$.post( ajaxurl, {
			'action' : 'sensei_timer_post_types',
			'nonce'  : window.SenseiTimerAdminNonce,
		}, cb );
	};

	app.showTypes = function( response ) {
		if ( response.success && response.data ) {
			app.$types.show().find( 'td' ).html( response.data );
		}
	};

	$( app.init );

} )( window, document, jQuery, window.SenseiTimerAdmin );
