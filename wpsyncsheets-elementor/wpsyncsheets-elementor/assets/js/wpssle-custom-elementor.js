/**
 * Admin Enqueue Script
 *
 * @package
 */

jQuery( window ).on( 'load', function () {
	'use strict';
	const headerNamesGlobal = [];
	( function ( e, ep, $ ) {
		const WPSSLEoptinIntegration = {
			onElementChange: function onElementChange( setting ) {},
			onSectionActive: function onSectionActive( setting ) {
				const self = this;
				const newList = '';
				jQuery( '.elementor-control-sheet_list' ).hide();

				const spreadsheet_id = jQuery(
					'[data-setting = spreadsheetid]'
				).val();
				let spreadsheet_list = [];
				if (
					String( spreadsheet_id ) !== '' ||
					parseInt( spreadsheet_id ) !== 0
				) {
					spreadsheet_list = jQuery(
						'[data-setting = spreadsheetid] option'
					)
						.map( function () {
							return jQuery( this ).val();
						} )
						.get();
					if (
						jQuery.inArray( spreadsheet_id, spreadsheet_list ) ===
						-1
					) {
						jQuery( '[data-setting = spreadsheetid]' )
							.val( '' )
							.change();
						jQuery( '[data-setting = sheet_name]' )
							.val( '' )
							.trigger( 'input' );
					} else {
						const sheet_name = jQuery(
							'[data-setting = sheet_name]'
						).val();
						let sheet_list = [];
						if ( String( sheet_name ) !== '' ) {
							sheet_list = jQuery(
								'[data-setting = sheet_list] option'
							)
								.map( function () {
									return jQuery( this ).text();
								} )
								.get();
							if (
								sheet_list.length > 0 &&
								jQuery.inArray( sheet_name, sheet_list ) === -1
							) {
								jQuery( '[data-setting = sheet_name]' )
									.val( '' )
									.trigger( 'input' );
							}
						}
					}
				}
			},
		};
		ep.modules.forms.wpsse = Object.assign(
			ep.modules.forms.mailchimp,
			WPSSLEoptinIntegration
		);
		ep.modules.forms.wpsse.addSectionListener(
			'section_wpsse',
			WPSSLEoptinIntegration.onSectionActive
		);
	} )( elementor, elementorPro, jQuery );
} );
