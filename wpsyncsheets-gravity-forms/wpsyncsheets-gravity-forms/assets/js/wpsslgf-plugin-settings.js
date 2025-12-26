/**
 * Main WPSyncSheetsGravityForms namespace.
 *
 * @since 1.0.0
 * @package wpsyncsheets-gravity-forms
 */

( function ( $ ) {
		$( document ).ready(
			function () {
				$( '#gform_google_revoke_button' ).on(
					'click',
					function ( e ) {
						e.preventDefault();
						changeAuthToken( '' );
					}
				);
				function changeAuthToken( token ) {
					$( 'input#authToken' ).val( token );
					$( '#gform-settings #gform-settings-save' ).trigger( 'click' );
				}
			}
		);
} )( jQuery );
// Create new Google Spreadsheet and Sheet Name.
( function ( $ ) {
		$( document ).ready(
			function () {
				$( '#clearloader' ).hide();
				$( '#cleartext' ).hide();
				var spreedSheets = $( "select#spreedsheets" ).val();
				$( "#gform_google_view_button" ).attr( 'href',"https://docs.google.com/spreadsheets/d/" + spreedSheets );
				$( "#gform_google_download_button" ).attr( 'href',"https://docs.google.com/spreadsheets/d/" + spreedSheets + "/export" );
				$( '#gform_google_new_spreadsheet_button' ).on(
					'click',
					function ( e ) {
						e.preventDefault();
						var newspreadsheettext = $( '#newspreadsheetlabel' ).val();
						var newsheettext       = $( '#sheetlabel' ).val();
						if (newspreadsheettext == '') {
							alert( 'Please add spreadsheet name' ); return false; }
						if (newsheettext == '') {
							alert( 'Please add sheet name' ); return false; }
						var createnewspreadsheet = $( '<input type=\"hidden\" name=\"createnewspreadsheet\" value=\"yes\" />' );
						$( '#gform-settings' ).append( createnewspreadsheet );
						$( '#gform-settings #gform-settings-save' ).trigger( 'click' );
					}
				);
				$( '#gform_google_clear_button' ).on(
					'click',
					function ( e ) {
						e.preventDefault();

						var formId   = getUrlParameter( 'id' );
						var feedId   = getUrlParameter( 'fid' );
						var _wpnonce = $( "#wpsslgf_general_settings" ).val();
						var wpssg_admin_ajax_object;
						if (typeof admin_ajax_object === 'undefined') {
							wpssg_admin_ajax_object = ajaxurl;
						} else {
							wpssg_admin_ajax_object = admin_ajax_object.ajaxurl;
						}
						$.ajax(
							{
								url : wpssg_admin_ajax_object,
								type : 'post',
								data :"action=wpsslgf_clear_spreadsheet&form_id=" + formId + "&fid=" + feedId + "&_wpnonce=" + _wpnonce,
								beforeSend:function () {
									if (confirm( "Are you sure? You want to enable Clear Spreadsheet this is clear all your entries within the spreadsheet and you would be remained only with sheet headers." )) {
										$( '#clearloader' ).show();
										$( '#gform_google_clear_button' ).hide();
									} else {
										return false;
									}
								},
								success : function ( response ) {
									if (response === 'successful') {
										alert( 'Spreadsheet Cleared successfully' );
									} else {
										alert( response );
									}
									$( '#clearloader' ).hide();
									document.getElementById( "gform_google_clear_button" ).style.display = "inline-block";
								},
								error: function (s) {
									alert( 'Error' );
									$( '#clearloader' ).hide();
									document.getElementById( "gform_google_clear_button" ).style.display = "inline-block";
								}
							}
						);
					}
				);
			}
		);
} )( jQuery );
// Sync all entries button.
( function ( $ ) {
		$( document ).ready(
			function () {
				$( '#gform_google_sync_button' ).on(
					'click',
					function ( e ) {
						e.preventDefault();
						$( '#gform_google_sync_button' ).hide();
						$( '#note' ).hide();
						$( '#syncloader' ).show();
						$( '#synctext' ).show();
						syncfun();
					}
				);
				function syncfun() {
					var synccallbutton = $( '<input type=\"hidden\" name=\"synccall\" value=\"yes\" />' );
					$( '#gform-settings' ).append( synccallbutton );
					$( '#gform-settings #gform-settings-save' ).trigger( 'click' );
				}
			}
		);
} )( jQuery );
// Set Default Google Spreadsheet Headers.
( function ( $ ) {
		$( document ).ready(
			function () {
				$( '#gform_google_header_button' ).on(
					'click',
					function ( e ) {
						e.preventDefault();
						changeAuthToken();
					}
				);
				function changeAuthToken() {
					var headerset = $( '<input type=\"hidden\" name=\"headerset\" value=\"yes\" />' );
					$( '#gform-settings' ).append( headerset );
					$( '#gform-settings #gform-settings-save' ).trigger( 'click' );
				}
			}
		);
} )( jQuery );


window.getUrlParameter = function( sParam ) {
	var sPageURL            = window.location.search.substring( 1 ),
		sURLVariables       = sPageURL.split( '&' ),
		sURLVariableslength = sURLVariables.length,
		sParameterName,
		i;
	for (i = 0; i < sURLVariableslength; i++) {
		sParameterName = sURLVariables[i].split( '=' );

		if (sParameterName[0] === sParam) {
			return sParameterName[1] === undefined ? true : decodeURIComponent( sParameterName[1] );
		}
	}
};
