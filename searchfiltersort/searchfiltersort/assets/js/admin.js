/**
 * Admin JavaScript.
 *
 * @package searchfiltersort
 * @since 1.0.0
 */

(function ($) {
	'use strict';

	/**
	 * Build shortcode from current form values.
	 *
	 * @return {void}
	 */
	const buildShortcode = () => {
		const postType  = $('.sflts-post-type').val() || 'post';
		const taxonomy  = $('.sflts-taxonomy').val() || 'category';
		const columns   = $('.sflts-columns').val() || '3';
		const position  = $('.sflts-filter-position').val() || 'top';

		// Build shortcode with all parameters.
		let shortcode = '[SearchFilterSort';

		if ( postType ) {
			shortcode += ` post_type="${ postType }"`;
		}

		if ( taxonomy ) {
			shortcode += ` taxonomy="${ taxonomy }"`;
		}

		if ( columns ) {
			shortcode += ` columns="${ columns }"`;
		}

		if ( position ) {
			shortcode += ` filter_position="${ position }"`;
		}

		shortcode += ']';

		$('#sflts-shortcode-box').text(shortcode);
	};

	/**
	 * Copy shortcode to clipboard.
	 *
	 * @return {void}
	 */
	const copyShortcode = () => {
		const shortcodeText = $('#sflts-shortcode-box').text();
		const $temp         = $('<textarea></textarea>');

		$('body').append($temp);
		$temp.val(shortcodeText).select();
		document.execCommand('copy');
		$temp.remove();

		const $btn         = $('.sflts-copy-shortcode');
		const originalText = $btn.text();

		$btn.text('✓ ' + (sfltsAdmin.strings.copied || 'Copied!'));

		setTimeout(() => {
			$btn.text(originalText);
		}, 2000);
	};

	$(document).ready(() => {
		// Initialize color pickers.
		$('.sflts-color-picker').wpColorPicker({
			change: function (event, ui) {
				buildShortcode();
			}
		});

		// Update shortcode on field changes.
		$('.sflts-settings-form').on('change', '.sflts-field', buildShortcode);
		$('.sflts-settings-form').on('input', '.sflts-field[type="number"]', buildShortcode);
		$('.sflts-settings-form').on('change', 'input[type="checkbox"]', buildShortcode);

		// Copy shortcode button.
		$('.sflts-copy-shortcode').on('click', copyShortcode);

		// Build initial shortcode.
		buildShortcode();
	});
})(jQuery);