/**
 * Admin Enqueue Script
 *
 * @package     wpsyncsheets-for-gravityforms
 */

(function ($) {
	"use strict";
	$( document ).ready(
		function () {
			"use strict";
			$( "#authlink" ).on(
				"click",
				function (e) {
					$( '#authbtn' ).hide();
					document.getElementById( "authtext" ).style.display = "inline-block";
				}
			);
			$( "#revoke" ).on(
				"click",
				function (e) {
					document.getElementById( "authtext" ).style.display     = "none";
					document.getElementById( "client_token" ).style.display = "none";
				}
			);
			$( "#reset_settings" ).on(
				"click",
				function (e) {
					e.preventDefault();
					var wpnonce = jQuery( '#wpsslgf_api_settings' ).val();
					jQuery.ajax(
						{
							url : admin_ajax_object.ajaxurl,
							type : 'post',
							data :"action=wpsslgf_reset_settings&_wpnonce=" + wpnonce,
							beforeSend:function () {
								if (confirm( "Are you sure you want to reset settings?" )) {

								} else {
									return false;
								}
							},
							success : function ( response ) {
								if (String( response ) === 'successful') {
									window.location.href = admin_ajax_object.adminSettingsPageUrl;
								} else {
									alert( response );
								}
							},
							error: function (s) {
								alert( 'Error' );
							}
						}
					);
				}
			);
			$( '.wpsslgf-support' ).parent().attr( 'target','_blank' );
		}
	);

	$( document ).ready(
		function () {
			$( ".wpsslgf-nav-dashboard" ).addClass( "active" );
			if ($( "#error-message" ).length > 0) {
				var message = $( "#error-message" ).val();
				$( ".alert-messages .container" ).append(
					'<div class="alert alert-danger fade in alert-dismissible" role="alert"><svg width="32" height="32" viewBox="0 0 512 512" xmlns="http://www.w3.org/2000/svg"><defs><style>.cls-1{fill:none;stroke:#721c24;stroke-linecap:round;stroke-linejoin:round;stroke-width:20px;}</style></defs><g data-name="Layer 2" id="Layer_2"><g data-name="E410, Error, Media, media player, multimedia" id="E410_Error_Media_media_player_multimedia"><circle class="cls-1" cx="256" cy="256" r="246"/><line class="cls-1" x1="371.47" x2="140.53" y1="140.53" y2="371.47"/><line class="cls-1" x1="371.47" x2="140.53" y1="371.47" y2="140.53"/></g></g></svg>' +
					message +
					'<a href="#" class="close" data-dismiss="alert" aria-label="close" title="close">×</a></div>'
				);
			}
			if ($( "#success-message" ).length > 0) {
				var message = $( "#success-message" ).val();
				$( ".alert-messages .container" ).append(
					'<div class="alert alert-success fade in alert-dismissible" role="alert"><svg fill="#000000" height="32" width="32" version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"  viewBox="0 0 52 52" xml:space="preserve"><g><path d="M26,0C11.664,0,0,11.663,0,26s11.664,26,26,26s26-11.663,26-26S40.336,0,26,0z M26,50C12.767,50,2,39.233,2,26S12.767,2,26,2s24,10.767,24,24S39.233,50,26,50z"/><path d="M38.252,15.336l-15.369,17.29l-9.259-7.407c-0.43-0.345-1.061-0.274-1.405,0.156c-0.345,0.432-0.275,1.061,0.156,1.406l10,8C22.559,34.928,22.78,35,23,35c0.276,0,0.551-0.114,0.748-0.336l16-18c0.367-0.412,0.33-1.045-0.083-1.411C39.251,14.885,38.62,14.922,38.252,15.336z"/></g></svg>' +
					message +
					'<a href="#" class="close" data-dismiss="alert" aria-label="close" title="close">×</a></div>'
				);
			}
			if ($( "#info-message" ).length > 0) {
				var message = $( "#info-message" ).val();
				$( ".alert-messages .container" ).append(
					'<div class="alert alert-info fade in alert-dismissible"><svg fill="#000000" width="32" height="32" viewBox="0 0 1920 1920" xmlns="http://www.w3.org/2000/svg"><path d="M960 0c530.193 0 960 429.807 960 960s-429.807 960-960 960S0 1490.193 0 960 429.807 0 960 0Zm0 101.053c-474.384 0-858.947 384.563-858.947 858.947S485.616 1818.947 960 1818.947 1818.947 1434.384 1818.947 960 1434.384 101.053 960 101.053Zm-42.074 626.795c-85.075 39.632-157.432 107.975-229.844 207.898-10.327 14.249-10.744 22.907-.135 30.565 7.458 5.384 11.792 3.662 22.656-7.928 1.453-1.562 1.453-1.562 2.94-3.174 9.391-10.17 16.956-18.8 33.115-37.565 53.392-62.005 79.472-87.526 120.003-110.867 35.075-20.198 65.9 9.485 60.03 47.471-1.647 10.664-4.483 18.534-11.791 35.432-2.907 6.722-4.133 9.646-5.496 13.23-13.173 34.63-24.269 63.518-47.519 123.85l-1.112 2.886c-7.03 18.242-7.03 18.242-14.053 36.48-30.45 79.138-48.927 127.666-67.991 178.988l-1.118 3.008a10180.575 10180.575 0 0 0-10.189 27.469c-21.844 59.238-34.337 97.729-43.838 138.668-1.484 6.37-1.484 6.37-2.988 12.845-5.353 23.158-8.218 38.081-9.82 53.42-2.77 26.522-.543 48.24 7.792 66.493 9.432 20.655 29.697 35.43 52.819 38.786 38.518 5.592 75.683 5.194 107.515-2.048 17.914-4.073 35.638-9.405 53.03-15.942 50.352-18.932 98.861-48.472 145.846-87.52 41.11-34.26 80.008-76 120.788-127.872 3.555-4.492 3.555-4.492 7.098-8.976 12.318-15.707 18.352-25.908 20.605-36.683 2.45-11.698-7.439-23.554-15.343-19.587-3.907 1.96-7.993 6.018-14.22 13.872-4.454 5.715-6.875 8.77-9.298 11.514-9.671 10.95-19.883 22.157-30.947 33.998-18.241 19.513-36.775 38.608-63.656 65.789-13.69 13.844-30.908 25.947-49.42 35.046-29.63 14.559-56.358-3.792-53.148-36.635 2.118-21.681 7.37-44.096 15.224-65.767 17.156-47.367 31.183-85.659 62.216-170.048 13.459-36.6 19.27-52.41 26.528-72.201 21.518-58.652 38.696-105.868 55.04-151.425 20.19-56.275 31.596-98.224 36.877-141.543 3.987-32.673-5.103-63.922-25.834-85.405-22.986-23.816-55.68-34.787-96.399-34.305-45.053.535-97.607 15.256-145.963 37.783Zm308.381-388.422c-80.963-31.5-178.114 22.616-194.382 108.33-11.795 62.124 11.412 115.76 58.78 138.225 93.898 44.531 206.587-26.823 206.592-130.826.005-57.855-24.705-97.718-70.99-115.729Z" fill-rule="evenodd"/></svg>' +
					message +
					'<a href="#" class="close" data-dismiss="alert" aria-label="close" title="close">×</a></div>'
				);
			}
		}
	);

	$( document ).ready(
		function () {
			"use strict";
			$( "#submission_date_format" ).keyup(
				function (e) {
					e.preventDefault();
					setTimeout(
						function () {
							wpsslgfdatepreview();
						},
						700
					);
				}
			);
		}
	);

    // Video Tutorial Js
			$('.play-icon').click(function(e){
				e.preventDefault();
				var videoId = $(this).data('video-id');
				var videoURL = 'https://www.youtube.com/embed/' + videoId + '?autoplay=1';
				$('#youtube-video').attr('src', videoURL);

				// Add flex display before fadeIn
				$('#video-popup').css('display', 'flex').hide().fadeIn();
			});

			$('.close, #video-popup').click(function(){
				$('#youtube-video').attr('src', '');
				$('#video-popup').fadeOut(function(){
					$(this).css('display', 'none'); // optional cleanup
				});
			});

			$('.popup-content').click(function(e){
				e.stopPropagation();
			});

			//Install and Active Woocommerce Plugin button
			$('#wpsswinstallactivebtn').on('click', function () {
				var wpnonce = jQuery( '#wpssw_api_dashboard' ).val();
				var plugin_slug = 'wpsyncsheets-woocommerce'; // Replace with your plugin slug
				$('#wpsswinstallactivebtn').html('Installing...');
				jQuery.ajax(
					{
						url: admin_ajax_object.ajaxurl,
						type: "post",
						data: "action=install_and_activate_plugin&plugin_slug="+ plugin_slug +"&_wpnonce=" + wpnonce,
						success: function (response) {
							$('#wpsswinstallactivebtn').html('Activated');
							$('#wpsswinstallactivebtn').toggleClass('activated underline');
							$('#wpsswinstallactivebtn').attr('id', '');
						},
						error: function (s) {
							alert( "Error" );
						},
					}
				);
			});

			// Active Woocommerce Plugin button
			$('#wpsswactivebtn').on('click', function () {
				var wpnonce = jQuery( '#wpssw_api_dashboard' ).val();
				var plugin_slug = 'wpsyncsheets-woocommerce'; // Replace with your plugin slug
				$('#wpsswactivebtn').html('Activating...');
				jQuery.ajax(
					{
						url: admin_ajax_object.ajaxurl,
						type: "post",
						data: "action=install_and_activate_plugin&plugin_active=true&plugin_slug="+ plugin_slug +"&_wpnonce=" + wpnonce,
						success: function (response) {
							$('#wpsswactivebtn').html('Activated');
							$('#wpsswactivebtn').toggleClass('activated underline');
							$('#wpsswactivebtn').attr('id', '');
						},
						error: function (s) {
							alert( "Error" );
						},
					}
				);
			});

			//Install and Active Elementor Plugin button
			$('#wpsseinstallactivebtn').on('click', function () {
				var wpnonce = jQuery( '#wpsse_api_dashboard' ).val();
				var plugin_slug = 'wpsyncsheets-elementor'; // Replace with your plugin slug
				$('#wpsseinstallactivebtn').html('Installing...');
				jQuery.ajax(
					{
						url: admin_ajax_object.ajaxurl,
						type: "post",
						data: "action=install_and_activate_plugin&plugin_slug="+ plugin_slug +"&_wpnonce=" + wpnonce,
						success: function (response) {
							$('#wpsseinstallactivebtn').html('Activated');
							$('#wpsseinstallactivebtn').toggleClass('activated underline');
							$('#wpsseinstallactivebtn').attr('id', '');
						},
						error: function (s) {
							alert( "Error" );
						},
					}
				);
			});

			// Active Elementor Plugin button
			$('#wpsseactivebtn').on('click', function () {
				var wpnonce = jQuery( '#wpsse_api_dashboard' ).val();
				var plugin_slug = 'wpsyncsheets-elementor'; // Replace with your plugin slug
				$('#wpsseactivebtn').html('Activating...');
				jQuery.ajax(
					{
						url: admin_ajax_object.ajaxurl,
						type: "post",
						data: "action=install_and_activate_plugin&plugin_active=true&plugin_slug="+ plugin_slug +"&_wpnonce=" + wpnonce,
						success: function (response) {
							$('#wpsseactivebtn').html('Activated');
							$('#wpsseactivebtn').toggleClass('activated underline');
							$('#wpsseactivebtn').attr('id', '');
						},
						error: function (s) {
							alert( "Error" );
						},
					}
				);
			});
 
			//Install and Active Contact Form Plugin button
			$('#wpsscinstallactivebtn').on('click', function () {
				var wpnonce = jQuery( '#wpssc_api_dashboard' ).val();
				var plugin_slug = 'contactsheets-lite'; // Replace with your plugin slug
				$('#wpsscinstallactivebtn').html('Installing...');
				jQuery.ajax(
					{
						url: admin_ajax_object.ajaxurl,
						type: "post",
						data: "action=install_and_activate_plugin&plugin_slug="+ plugin_slug +"&_wpnonce=" + wpnonce,
						success: function (response) {
							$('#wpsscinstallactivebtn').html('Activated');
							$('#wpsscinstallactivebtn').toggleClass('activated underline');
							$('#wpsscinstallactivebtn').attr('id', '');
						},
						error: function (s) {
							alert( "Error" );
						},
					}
				);
			});

			// Active Contact Form Plugin button
			$('#wpsscactivebtn').on('click', function () {
				var wpnonce = jQuery( '#wpssc_api_dashboard' ).val();
				var plugin_slug = 'contactsheets-lite'; // Replace with your plugin slug
				$('#wpsscactivebtn').html('Activating...');
				jQuery.ajax(
					{
						url: admin_ajax_object.ajaxurl,
						type: "post",
						data: "action=install_and_activate_plugin&plugin_active=true&plugin_slug="+ plugin_slug +"&_wpnonce=" + wpnonce,
						success: function (response) {
							$('#wpsscactivebtn').html('Activated');
							$('#wpsscactivebtn').toggleClass('activated underline');
							$('#wpsscactivebtn').attr('id', '');
						},
						error: function (s) {
							alert( "Error" );
						},
					}
				);
			});

			//Install and Active WPForms Plugin button
			$('#wpsswpinstallactivebtn').on('click', function () {
				var wpnonce = jQuery( '#wpsswp_api_dashboard' ).val();
				var plugin_slug = 'wpsyncsheets-wpforms'; // Replace with your plugin slug
				$('#wpsswpinstallactivebtn').html('Installing...');
				jQuery.ajax(
					{
						url: admin_ajax_object.ajaxurl,
						type: "post",
						data: "action=install_and_activate_plugin&plugin_slug="+ plugin_slug +"&_wpnonce=" + wpnonce,
						success: function (response) {
							$('#wpsswpinstallactivebtn').html('Activated');
							$('#wpsswpinstallactivebtn').toggleClass('activated underline');
							$('#wpsswpinstallactivebtn').attr('id', '');
						},
						error: function (s) {
							alert( "Error" );
						},
					}
				);

			});

			// Active WPForms Plugin button
			$('#wpsswpactivebtn').on('click', function () {
				var wpnonce = jQuery( '#wpsswp_api_dashboard' ).val();
				var plugin_slug = 'wpsyncsheets-wpforms'; // Replace with your plugin slug
				$('#wpsswpactivebtn').html('Activating...');
				jQuery.ajax(
					{
						url: admin_ajax_object.ajaxurl,
						type: "post",
						data: "action=install_and_activate_plugin&plugin_active=true&plugin_slug="+ plugin_slug +"&_wpnonce=" + wpnonce,
						success: function (response) {
							$('#wpsswpactivebtn').html('Activated');
							$('#wpsswpactivebtn').toggleClass('activated underline');
							$('#wpsswpactivebtn').attr('id', '');
						},
						error: function (s) {
							alert( "Error" );
						},
					}
				);
			});




})( jQuery );

window.wpsslgfTab = function(evt, tabName) {
	var i, tabContent, tabLinks;
	tabContent    = document.getElementsByClassName( "tabcontent" );
	tabcontentlen = tabContent.length;
	for (i = 0; i < tabcontentlen; i++) {
		tabContent[i].style.display = "none";
	}
	tabLinks   = document.getElementsByClassName( "tablinks" );
	tablinklen = tabLinks.length;
	for (i = 0; i < tablinklen; i++) {
		tabLinks[i].className = tabLinks[i].className.replace( " active", "" );
	}
	document.getElementById( tabName ).style.display = "block";
	var type = typeof event;
	if (type !== 'undefined') {
		evt.currentTarget.className += " active";
	}
}
window.wpsslgfNavTab = function(evt, tabName) {
	"use strict";
	var i, tabContent, tabLinks;
	tabContent           = document.getElementsByClassName( "navtabcontent" );
	var tabContentlength = tabContent.length;
	for (i = 0; i < tabContentlength; i++) {
		tabContent[i].style.display = "none";
	}
	tabLinks      = document.getElementsByClassName( "navtablinks" );
	var tablength = tabLinks.length;
	for (i = 0; i < tablength; i++) {
		tabLinks[i].className = tabLinks[i].className.replace( " active", "" );
	}
	document.getElementById( tabName ).style.display = "block";
	var type = typeof event;
	if (type !== "undefined") {
		evt.currentTarget.className += " active";
	}
}
window.wpsslgfNavTabfreevspro = function(evt, tabName) {
	"use strict";
	var i, tabContent, tabLinks;
	tabContent           = document.getElementsByClassName( "navtabcontent" );
	var tabContentlength = tabContent.length;
	for (i = 0; i < tabContentlength; i++) {
		tabContent[i].style.display = "none";
	}
	tabLinks      = document.getElementsByClassName( "navtablinks" );
	var tablength = tabLinks.length;
	for (i = 0; i < tablength; i++) {
		tabLinks[i].className = tabLinks[i].className.replace( " active", "" );
		if(tabLinks[i].className == 'navtablinks wpsslc-nav-freevspro')
		{
			tabLinks[i].className += " active";
		}
	}
	document.getElementById( tabName ).style.display = "block";
	var type = typeof evt;
	if (type !== "undefined") {
		evt.currentTarget.className += " active";
	}
	window.scrollTo({
		top: 0,
		behavior: 'smooth' // Optional for smooth animation
	});
}

window.wpsslgfNavTabLetsBegin = function(evt, tabName) {
	"use strict";
	var i, tabContent, tabLinks;
	tabContent           = document.getElementsByClassName( "navtabcontent" );
	var tabContentlength = tabContent.length;
	for (i = 0; i < tabContentlength; i++) {
		tabContent[i].style.display = "none";
	}
	tabLinks      = document.getElementsByClassName( "navtablinks" );
	var tablength = tabLinks.length;
	for (i = 0; i < tablength; i++) {
		tabLinks[i].className = tabLinks[i].className.replace( " active", "" );
		if(tabLinks[i].className == 'navtablinks wpsslgf-nav-googleapi')
		{
			tabLinks[i].className += " active";
		}
	}
	document.getElementById( tabName ).style.display = "block";
	var type = typeof evt;
	if (type !== "undefined") {
		evt.currentTarget.className += " active";
	}
	window.scrollTo({
		top: 0,
		behavior: 'smooth' // Optional for smooth animation
	});
}

window.getParameterByName = function(name, url) {
	if ( ! url) {
		url = window.location.href;
	}
	name        = name.replace( /[\[\]]/g, '\\$&' );
	var regex   = new RegExp( '[?&]' + name + '(=([^&#]*)|&|#|$)' ),
		results = regex.exec( url );
	if ( ! results) {
		return null;
	}
	if ( ! results[2]) {
		return '';
	}
	return decodeURIComponent( results[2].replace( /\+/g, ' ' ) );
}

window.wpsslgfCopy = function( id, targetid ) {
	var copyText   = document.getElementById( id );
	var textArea   = document.createElement( "textarea" );
	textArea.value = copyText.textContent;
	document.body.appendChild( textArea );
	textArea.select();
	document.execCommand( "Copy" );
	textArea.remove();
	if ( ! jQuery( "#" + targetid ).hasClass( "tooltip-click" )) {
		jQuery( "#" + targetid ).addClass( "tooltip-click" );
		setTimeout(
			function () {
				jQuery( "#" + targetid ).removeClass( "tooltip-click" );
			},
			1000
		);
	}
}
window.wpsslgfdatepreview = function(){
	jQuery( '#previewloader' ).show();
	var dataformat       = jQuery( "#submission_date_format" ).val();
	var sync_nonce_token = admin_ajax_object.sync_nonce_token;
	jQuery.ajax(
		{
			url : admin_ajax_object.ajaxurl,
			type : 'post',
			data :"action=wpsslgf_getdate_preview&dateformat=" + dataformat + "&sync_nonce_token=" + sync_nonce_token,
			success : function ( response ) {
				jQuery( "#wpsslgf_preview" ).html( response );
				jQuery( '#previewloader' ).hide();
			},
			error: function (s) {
				jQuery( '#previewloader' ).hide();
			}
		}
	);
}
window.wc_closeTopHeader = function() {
        const header = document.querySelector('.wpssw_order_top_header');
        if (header) {
            header.style.display = 'none';
        }
    }