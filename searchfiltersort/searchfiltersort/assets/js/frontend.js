/**
 * Frontend script for SearchFilterSort.
 * Handles filtering, AJAX, pagination, and slider UI.
 *
 * @package searchfiltersort
 */

/* global sfltsData, noUiSlider */
(function ($) {
	'use strict';

	class SFLTSFront {
		constructor(container) {
			this.$el = container;
			this.state = {
				postType:       container.data('post-type'),
				taxonomy:       container.data('taxonomy'),
				perPage:        container.data('per-page'),
				filterPosition: container.data('filter-position'),
				paginationType: container.data('pagination'),
				page:           1,
				maxPages:       1,
				selectedCats:   [],
				priceMin:       '',
				priceMax:       '',
				sort:           'date',
				loading:        false
			};

			this.$grid = container.find('.sflts-grid');
			this.$count = container.find('.sflts-count');
			this.$loadMore = container.find('.sflts-load-more');
			this.$pagination = container.find('.sflts-pagination-links');

			this.bind();
			this.initSlider();
			this.fetch();
		}

		bind() {
			const self = this;

			this.$el.on('change', '.sflts-filter-category', function () {
				const id = parseInt($(this).val(), 10);

				if ($(this).is(':checked')) {
					self.state.selectedCats.push(id);
				} else {
					self.state.selectedCats = self.state.selectedCats.filter((term) => term !== id);
				}

				self.resetAndFetch();
			});

			this.$el.on('change', '.sflts-price-min, .sflts-price-max', () => {
				const $minInput = self.$el.find('.sflts-price-min');
				const $maxInput = self.$el.find('.sflts-price-max');

				const minDefault = parseFloat($minInput.data('default'));
				const maxDefault = parseFloat($maxInput.data('default'));

				let minVal = $minInput.val() !== '' ? parseFloat($minInput.val()) : null;
				let maxVal = $maxInput.val() !== '' ? parseFloat($maxInput.val()) : null;

				if (minVal === null && maxVal === null) {
					minVal = minDefault;
					maxVal = maxDefault;
				} else {
					if (minVal === null) {
						minVal = minDefault;
					}
					if (maxVal === null) {
						maxVal = maxDefault;
					}
				}

				self.state.priceMin = minVal;
				self.state.priceMax = maxVal;

				if (self.slider) {
					self.slider.set([minVal, maxVal]);
				}

				self.resetAndFetch();
			});

			this.$el.on('change', '.sflts-sort-select', function () {
				self.state.sort = $(this).val();
				self.resetAndFetch();
			});

			this.$el.on('click', '.sflts-reset', () => {
				self.$el.find('.sflts-filter-category').prop('checked', false);
				self.$el.find('.sflts-price-min, .sflts-price-max').val('');
				self.$el.find('.sflts-sort-select').val('date');

				self.state.selectedCats = [];
				self.state.priceMin = '';
				self.state.priceMax = '';
				self.state.sort = 'date';

				if (self.slider) {
					self.slider.set([0, 1000]);
				}

				self.resetAndFetch();
			});

			this.$loadMore.on('click', () => {
				if (self.state.page < self.state.maxPages) {
					self.state.page += 1;
					self.fetch(true);
				}
			});

			this.$pagination.on('click', 'a', function (e) {
				e.preventDefault();
				const page = parseInt($(this).data('page'), 10);
				if (page && page !== self.state.page) {
					self.state.page = page;
					self.fetch();
				}
			});
		}

		initSlider() {
			const sliderEl = this.$el.find('.sflts-price-slider')[0];
			if (sliderEl && window.noUiSlider) {
				const min = parseFloat(sliderEl.dataset.min) || 0;
				const max = parseFloat(sliderEl.dataset.max) || 1000;

				const startMin = this.state.priceMin || min;
				const startMax = this.state.priceMax || max;

				this.slider = noUiSlider.create(sliderEl, {
					start: [startMin, startMax],
					connect: true,
					range: { min: min, max: max }
				});

				this.slider.on('change', (values) => {
					this.$el.find('.sflts-price-min').val(values[0]);
					this.$el.find('.sflts-price-max').val(values[1]);
					this.state.priceMin = values[0];
					this.state.priceMax = values[1];
					this.resetAndFetch();
				});
			}
		}

		resetAndFetch() {
			this.state.page = 1;
			this.fetch();
		}

		fetch(append = false) {
			if (this.state.loading) {
				return;
			}
			this.state.loading = true;

			const payload = {
				action: 'sflts_filter',
				nonce: sfltsData.nonce,
				post_type: this.state.postType,
				taxonomy: this.state.taxonomy,
				paged: this.state.page,
				per_page: this.state.perPage,
				categories: this.state.selectedCats,
				price_min: this.state.priceMin,
				price_max: this.state.priceMax,
				sort: this.state.sort
			};

			if ( ! append ) {
				this.$grid.addClass('sflts-loading').html(`<p>${sfltsData.strings.loading}</p>`);
			}

			$.post(sfltsData.ajaxUrl, payload, (response) => {
				if (response.success) {
					if (append) {
						this.$grid.append(response.data.html);
					} else {
						this.$grid.html(response.data.html).removeClass('sflts-loading');
					}
					this.state.maxPages = response.data.max_pages;
					this.updateCounts(response.data.total);
					this.updatePagination();
				} else {
					const message = response.data && response.data.message ? response.data.message : sfltsData.strings.noResults;
					// Safely insert message as text.
					this.$grid.html('<p class="sflts-error"></p>');
					this.$grid.find('.sflts-error').text(message);
				}
			}).always(() => {
				this.state.loading = false;
			});
		}

		updateCounts(total) {
			const showing = this.$grid.find('.sflts-item').length;
			this.$count.text(`${showing} / ${total}`);
		}

		updatePagination() {
			if ('load_more' === this.state.paginationType) {
				if (this.state.page < this.state.maxPages) {
					this.$loadMore.show();
					this.$pagination.hide();
				} else {
					this.$loadMore.hide();
				}
				return;
			}

			this.$loadMore.hide();

			const total = this.state.maxPages;
			const current = this.state.page;

			if (total <= 1) {
				this.$pagination.hide();
				return;
			}

			let html = '';
			const added = new Set();

			const addPage = (i) => {
				if (i < 1 || i > total) {
					return;
				}
				if (added.has(i)) {
					return; // prevent duplicate buttons.
				}
				added.add(i);

				const active = i === current ? ' class="active"' : '';
				html += `<a href="#" data-page="${i}"${active}>${i}</a>`;
			};

			// --- START PAGES ---
			addPage(1);
			addPage(2);

			// --- START ELLIPSIS ---
			if (current > 4) {
				html += `<span class="sflts-ellipsis">…</span>`;
			}

			// --- MIDDLE WINDOW ---
			addPage(current - 1);
			addPage(current);
			addPage(current + 1);

			// --- END ELLIPSIS ---
			if (current < total - 3) {
				html += `<span class="sflts-ellipsis">…</span>`;
			}

			// --- END PAGES ---
			addPage(total - 1);
			addPage(total);

			this.$pagination.html(html).show();
		}
	}

	$(document).ready(() => {
		$('.sflts-container').each(function () {
			new SFLTSFront($(this));
		});
	});
})(jQuery);