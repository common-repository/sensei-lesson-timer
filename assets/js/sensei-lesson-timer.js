var sensei_lesson_timer = jQuery.extend(sensei_lesson_timer || {}, {
	debug: false,
	plugin_settings: {},
	form_element: '',
	quiz_btn: '',
	submit_btn: '',
	form_element_label: '',
	time_running: true,
	init: function() {
		// First validate out settings as received from the server.
		sensei_lesson_timer.validate_plugin_settings();

		// Next, check that we can location at least one of the form elements in the DOM.
		if (sensei_lesson_timer.plugin_settings['form_elements'] != '') {
			var elements = sensei_lesson_timer.plugin_settings['form_elements'];

			if ( jQuery( elements.submit ).length ) {
				if ( sensei_lesson_timer.debug ) {
					console.log('slt: found form element for timer[%o]', elements.submit);
				}

				sensei_lesson_timer.form_element = sensei_lesson_timer.submit_btn = jQuery( elements.submit );
			}

			if ( jQuery( elements.view_quiz ).length ) {
				if ( sensei_lesson_timer.debug ) {
					console.log('slt: found form element for timer[%o]', elements.view_quiz);
				}

				sensei_lesson_timer.quiz_btn = jQuery( elements.view_quiz );
				if ( ! sensei_lesson_timer.form_element.length ) {
					sensei_lesson_timer.form_element = sensei_lesson_timer.quiz_btn;
				}

				jQuery(document.body).on( 'click', '.slt-active-timer.disabled', function( evt ) {
					evt.preventDefault();
				});
			}

			// If no found form elements abort.
			if ( sensei_lesson_timer.form_element == '' ) {
				if (sensei_lesson_timer.debug == true) {
					console.log('slt: no form element(s) found for for timer');
					return;
				}
			}

			if ( sensei_lesson_timer.plugin_settings['pause_on_unfocus'] == true ) {

				// Added support for jQuery UI dialog popup to pause the timer is showing.
				jQuery(document).on("dialogopen", ".ui-dialog", function (event, ui) {
				    sensei_lesson_timer.pause_timer();
				});

				// And resume timer when closed.
				jQuery(document).on("dialogclose", ".ui-dialog", function (event, ui) {
				    sensei_lesson_timer.run_timer();
				});
			}

			// Add the timer to the DOM
			sensei_lesson_timer.add_timer();

			// Showtime!
			sensei_lesson_timer.show_timer();
		}
	},

	set_unload_message: function() {

		if (sensei_lesson_timer.plugin_settings['unload_message'] != '') {
			window.onbeforeunload = function() {
				return sensei_lesson_timer.plugin_settings['unload_message'];
			}
		}
	},

	clear_unload_message: function() {
		window.onbeforeunload = null;
	},

	// Function set to handle clicks in case we leave the submit button showing.
	handle_form_element_click: function() {
		return false;
	},

	add_timer: function() {
		sensei_lesson_timer.set_unload_message();

		if (sensei_lesson_timer.debug == true) {
			console.log('slt: add_timer: placement[%o]', sensei_lesson_timer.plugin_settings['placement']);
		}

		let sizeAttr = ' style="font-size:' + sensei_lesson_timer.plugin_settings['size'] + '"';

		if ( (sensei_lesson_timer.plugin_settings['placement'] == 'outside-right')
		  || (sensei_lesson_timer.plugin_settings['placement'] == 'outside-left') ) {

		  	if ( sensei_lesson_timer.quiz_btn.length ) {
		  		var href = sensei_lesson_timer.quiz_btn.attr( 'href' );
		  		sensei_lesson_timer.quiz_btn
		  			.addClass( 'slt-active-timer disabled' )
		  			.css({ 'opacity' : '.6' })
		  			.attr( 'href', '#' )
		  			.data( 'href', href );
		  	}

		  	if ( sensei_lesson_timer.submit_btn.length ) {
		  		sensei_lesson_timer.submit_btn.attr( 'disabled', 'disabled' );
		  	}

			jQuery( sensei_lesson_timer.form_element )
				.click( sensei_lesson_timer.handle_form_element_click )
				.addClass('slt-active-timer');

			if (sensei_lesson_timer.plugin_settings['form_element_cursor'] != '') {
				sensei_lesson_timer.form_element_org_cursor = jQuery( sensei_lesson_timer.form_element ).css('cursor');
				jQuery( sensei_lesson_timer.form_element ).css('cursor', sensei_lesson_timer.plugin_settings['form_element_cursor']);
			}

			if (sensei_lesson_timer.plugin_settings['form_element_title'] != '') {
				sensei_lesson_timer.form_element_org_title = jQuery( sensei_lesson_timer.form_element ).attr('title');
				jQuery( sensei_lesson_timer.form_element ).attr('title', sensei_lesson_timer.plugin_settings['form_element_title']);
			}

			if (sensei_lesson_timer.plugin_settings['form_element_class'] != '') {
				jQuery( sensei_lesson_timer.form_element ).addClass(sensei_lesson_timer.plugin_settings['form_element_class']);
			}

			if (sensei_lesson_timer.plugin_settings['placement'] == 'outside-right') {
				jQuery('<div id="sensei_lesson_timer" class="sensei_lesson_timer-outside-right"' + sizeAttr + '></div>').insertAfter( sensei_lesson_timer.form_element );
			} else if (sensei_lesson_timer.plugin_settings['placement'] == 'outside-left') {
				jQuery('<div id="sensei_lesson_timer" class="sensei_lesson_timer-outside-left"' + sizeAttr + '></div>').insertBefore( sensei_lesson_timer.form_element );
			}
		} else if (sensei_lesson_timer.plugin_settings['placement'] == 'outside-replace') {
			jQuery( sensei_lesson_timer.form_element ).hide();
			jQuery('<div id="sensei_lesson_timer" class="sensei_lesson_timer-outside-right"' + sizeAttr + '></div>').insertAfter( sensei_lesson_timer.form_element );
		} else if ( (sensei_lesson_timer.plugin_settings['placement'] == 'inside-right')
		  || (sensei_lesson_timer.plugin_settings['placement'] == 'inside-left')
		  || (sensei_lesson_timer.plugin_settings['placement'] == 'inside-replace') ) {
			  sensei_lesson_timer.form_element_label = jQuery(sensei_lesson_timer.form_element).val();
		}
	},

	finish_timer: function() {

		if (sensei_lesson_timer.debug == true) {
			console.log('slt: finish_timer:');
		}

		jQuery( sensei_lesson_timer.form_element ).removeAttr('disabled');
		jQuery( sensei_lesson_timer.form_element ).off('click');

		// Clear our warning mesage if the user leaves
		sensei_lesson_timer.clear_unload_message();

		if ( (sensei_lesson_timer.plugin_settings['placement'] == 'outside-right')
		  || (sensei_lesson_timer.plugin_settings['placement'] == 'outside-left') ) {

			jQuery('#sensei_lesson_timer').remove();

			if ( sensei_lesson_timer.quiz_btn.length ) {
				var href = sensei_lesson_timer.quiz_btn.data( 'href' );
				sensei_lesson_timer.quiz_btn
					.removeClass( 'slt-active-timer' ).removeClass( 'disabled' )
					.css({ 'opacity' : '1' })
					.attr( 'href', href );
			}

			if ( sensei_lesson_timer.submit_btn.length ) {
				sensei_lesson_timer.submit_btn.removeAttr( 'disabled', 'disabled' );
			}

			jQuery( sensei_lesson_timer.form_element ).removeClass('slt-active-timer');

			if (sensei_lesson_timer.form_element_org_cursor != '') {
				jQuery( sensei_lesson_timer.form_element ).css('cursor', sensei_lesson_timer.form_element_org_cursor);
			}
			if (sensei_lesson_timer.form_element_org_title != '') {
				jQuery( sensei_lesson_timer.form_element ).attr('title', sensei_lesson_timer.form_element_org_title);
			}
			if (sensei_lesson_timer.plugin_settings['form_element_class'] != '') {
				jQuery( sensei_lesson_timer.form_element ).removeClass(sensei_lesson_timer.plugin_settings['form_element_class']);
			}
		} else if (sensei_lesson_timer.plugin_settings['placement'] == 'outside-replace') {
			jQuery('#sensei_lesson_timer').remove();
			jQuery( sensei_lesson_timer.form_element ).show();

		} else if ( (sensei_lesson_timer.plugin_settings['placement'] == 'inside-right')
				 || (sensei_lesson_timer.plugin_settings['placement'] == 'inside-left')
				 || (sensei_lesson_timer.plugin_settings['placement'] == 'inside-replace') ) {
			  jQuery(sensei_lesson_timer.form_element).val( sensei_lesson_timer.form_element_label );
		}

		if (sensei_lesson_timer.plugin_settings['auto_complete'] == true) {
			if (sensei_lesson_timer.debug == true) {
				console.log('slt: sending click event');
			}

			var form = jQuery( sensei_lesson_timer.plugin_settings['form_element'] ).parents( 'form' );
			if (form != undefined) {
				jQuery(sensei_lesson_timer.form_element, form).trigger( 'click' );
			}
		}
	},
	show_timer: function() {

		var countdown = sensei_lesson_timer.plugin_settings['lesson_length']  * 60 * 1000;
		//countdown = 10000;

		var min = 0;
		var sec = 0;

		if (sensei_lesson_timer.debug == true) {
			console.log('slt: show_timer: countdown[%o]', countdown);
		}

		var do_timer = false;
		var timerId = setInterval(function() {

			if (sensei_lesson_timer.time_running != true) {
				do_timer = false;
			} else {

				if ( sensei_lesson_timer.plugin_settings['pause_on_unfocus'] == true ) {
					if ( document.hasFocus() ) {
						do_timer = true;
					} else {
						do_timer = false;
					}
				} else {
					do_timer = true;
				}
			}

			// Only decrease time if do_timer is true...
			if ( do_timer == true) {
				countdown -= 1000;
			}

			// ... but we still want to go through the motion is displaying the timer
			min = ('00' + Math.floor(countdown / (60 * 1000))).slice(-2) ;
			sec = ('00' + Math.floor((countdown - (min * 60 * 1000)) / 1000)).slice(-2);  //correct

			if (countdown <= 0) {
				clearInterval(timerId);
				sensei_lesson_timer.finish_timer();

			} else {
				if ( (sensei_lesson_timer.plugin_settings['placement'] == 'outside-right')
				  || (sensei_lesson_timer.plugin_settings['placement'] == 'outside-left')
				  || (sensei_lesson_timer.plugin_settings['placement'] == 'outside-replace') ) {
					  jQuery('#sensei_lesson_timer').html("<span id='sensei_lesson_timer_minutes'>" + min + "</span><span id='sensei_lesson_timer_separator'>:</span><span id='sensei_lesson_timer_seconds'>" + sec + "</span>");

				} else if ( (sensei_lesson_timer.plugin_settings['placement'] == 'inside-right')
				  		 || (sensei_lesson_timer.plugin_settings['placement'] == 'inside-left')
				  		 || (sensei_lesson_timer.plugin_settings['placement'] == 'inside-replace') ) {

					var timer_digits = min + ':' + sec;
					if (sensei_lesson_timer.plugin_settings['placement'] == 'inside-right') {
						jQuery( sensei_lesson_timer.form_element ).val(sensei_lesson_timer.form_element_label+sensei_lesson_timer.plugin_settings['form_element_inside_spacer']+timer_digits);
					} else if (sensei_lesson_timer.plugin_settings['placement'] == 'inside-left') {
						jQuery( sensei_lesson_timer.form_element ).val(timer_digits+sensei_lesson_timer.plugin_settings['form_element_inside_spacer']+sensei_lesson_timer.form_element_label);
					} else if (sensei_lesson_timer.plugin_settings['placement'] == 'inside-replace') {
						jQuery( sensei_lesson_timer.form_element ).val(timer_digits);
					}
				}
			}
		}, 1000); //1000ms. = 1sec
	},

	run_timer: function() {
		if (sensei_lesson_timer.debug == true) {
			console.log('slt: run_time:');
		}
		sensei_lesson_timer.time_running = true;
		sensei_lesson_timer.set_unload_message();
	},

	pause_timer: function() {
		if (sensei_lesson_timer.debug == true) {
			console.log('slt: pause_timer:');
		}
		sensei_lesson_timer.time_running = false;
		sensei_lesson_timer.clear_unload_message();
	},

	validate_plugin_settings: function() {
		if (sensei_lesson_time_plugin_data != undefined) {
			sensei_lesson_timer.plugin_settings = sensei_lesson_time_plugin_data;

			if (sensei_lesson_timer.plugin_settings['debug_js'] == undefined)
				sensei_lesson_timer.plugin_settings['debug_js'] = false;

			if (sensei_lesson_timer.plugin_settings['form_element'] == undefined)
				sensei_lesson_timer.plugin_settings['form_element'] = 'input[name="quiz_complete"]';

			if (sensei_lesson_timer.plugin_settings['unload_message'] == undefined)
				sensei_lesson_timer.plugin_settings['unload_message'] = '';

			if (sensei_lesson_timer.plugin_settings['lesson_length'] == undefined)
				sensei_lesson_timer.plugin_settings['lesson_length'] = 1;

			if (sensei_lesson_timer.plugin_settings['auto_complete'] == undefined)
				sensei_lesson_timer.plugin_settings['auto_complete'] = false;

			if (sensei_lesson_timer.plugin_settings['pause_on_unfocus'] == undefined)
				sensei_lesson_timer.plugin_settings['pause_on_unfocus'] = true;

			if (sensei_lesson_timer.plugin_settings['placement'] == undefined)
				sensei_lesson_timer.plugin_settings['placement'] = 'outside-right';

			if (sensei_lesson_timer.plugin_settings['size'] == undefined)
				sensei_lesson_timer.plugin_settings['size'] = '1em';

			if (sensei_lesson_timer.plugin_settings['form_element_inside_spacer'] == undefined)
				sensei_lesson_timer.plugin_settings['form_element_inside_spacer'] = ' - ';

			if (sensei_lesson_timer.plugin_settings['form_element_outside_spacer'] == undefined)
				sensei_lesson_timer.plugin_settings['form_element_outside_spacer'] = ' ';
		}

		// Get the debug setting passed to us from the server.
		sensei_lesson_timer.debug = sensei_lesson_timer.plugin_settings['debug_js'];

		if (sensei_lesson_timer.debug == true) {
			console.log('slt: plugin_settings[%o]', sensei_lesson_timer.plugin_settings);
		}
	}
});

jQuery(document).ready(function($){

	sensei_lesson_timer.init();

});
