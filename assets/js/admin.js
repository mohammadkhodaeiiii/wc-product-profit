/**
 * WooCommerce Product Profit Calculator — Admin scripts.
 *
 * Live profit, markup and margin calculations without page reload.
 * Uses vanilla JavaScript; jQuery is only used for WooCommerce variation hooks.
 *
 * @package WC_Product_Profit
 */

( function () {
	'use strict';

	if ( typeof wcppData === 'undefined' ) {
		return;
	}

	const config = wcppData;

	/**
	 * Parse a localized price string into a float.
	 *
	 * @param {string} value Raw input value.
	 * @return {number}
	 */
	function parsePrice( value ) {
		if ( ! value || typeof value !== 'string' ) {
			return 0;
		}

		let cleaned = value.trim();

		if ( ! cleaned ) {
			return 0;
		}

		// Strip everything except digits, separators and minus sign.
		const decimalSep  = config.decimalSep || '.';
		const thousandSep = config.thousandSep || ',';

		if ( thousandSep ) {
			const escaped = thousandSep.replace( /[.*+?^${}()|[\]\\]/g, '\\$&' );
			cleaned = cleaned.replace( new RegExp( escaped, 'g' ), '' );
		}

		if ( decimalSep && decimalSep !== '.' ) {
			cleaned = cleaned.replace( decimalSep, '.' );
		}

		cleaned = cleaned.replace( /[^\d.\-]/g, '' );

		const num = parseFloat( cleaned );
		return isNaN( num ) ? 0 : num;
	}

	/**
	 * Format a number with locale-aware thousand separators.
	 *
	 * @param {number} num       The number.
	 * @param {number} decimals  Decimal places.
	 * @return {string}
	 */
	function formatNumber( num, decimals ) {
		const fixed = num.toFixed( decimals );
		const parts = fixed.split( '.' );
		const intPart = parts[0].replace( /\B(?=(\d{3})+(?!\d))/g, config.thousandSep || ',' );
		return decimals > 0 ? intPart + ( config.decimalSep || '.' ) + parts[1] : intPart;
	}

	/**
	 * Format a monetary amount using the WooCommerce price format.
	 *
	 * @param {number} amount The amount.
	 * @return {string}
	 */
	function formatMoney( amount ) {
		const formatted = formatNumber( amount, 2 );
		const format    = config.priceFormat || '%1$s%2$s';
		return format.replace( '%1$s', config.currencySymbol ).replace( '%2$s', formatted );
	}

	/**
	 * Format a percentage.
	 *
	 * @param {number} value The percentage value.
	 * @return {string}
	 */
	function formatPercent( value ) {
		return formatNumber( value, config.decimals ) + '%';
	}

	/**
	 * Calculate profit metrics.
	 *
	 * @param {number} salePrice Effective sale price.
	 * @param {number} costPrice Cost price.
	 * @return {{profit: number, markup: number, margin: number, status: string}}
	 */
	function calculate( salePrice, costPrice ) {
		const profit = salePrice - costPrice;

		let markup = 0;
		if ( costPrice > 0 ) {
			markup = ( profit / costPrice ) * 100;
		}

		let margin = 0;
		if ( salePrice > 0 ) {
			margin = ( profit / salePrice ) * 100;
		}

		let status = 'zero';
		if ( profit > 0 ) {
			status = 'positive';
		} else if ( profit < 0 ) {
			status = 'negative';
		}

		return { profit, markup, margin, status };
	}

	/**
	 * Resolve the color for a profit status.
	 *
	 * @param {string} status positive|zero|negative
	 * @return {string}
	 */
	function colorForStatus( status ) {
		const map = {
			positive: config.positiveColor,
			zero:     config.zeroColor,
			negative: config.negativeColor,
		};
		return map[ status ] || config.zeroColor;
	}

	/**
	 * Update a summary card DOM element with calculated values.
	 *
	 * @param {HTMLElement} summaryEl The summary wrapper.
	 * @param {number}      salePrice  Effective sale price.
	 * @param {number}      costPrice  Cost price.
	 */
	function updateSummary( summaryEl, salePrice, costPrice ) {
		const profitEl  = summaryEl.querySelector( '[data-wcpp="profit"]' );
		const markupEl  = summaryEl.querySelector( '[data-wcpp="markup"]' );
		const marginEl  = summaryEl.querySelector( '[data-wcpp="margin"]' );
		const badgeEl   = summaryEl.querySelector( '[data-wcpp="status-badge"]' );
		const profitCard = summaryEl.querySelector( '[data-wcpp="profit-card"]' );

		if ( costPrice <= 0 && salePrice <= 0 ) {
			setEmptyState( summaryEl );
			return;
		}

		const result = calculate( salePrice, costPrice );
		const color  = colorForStatus( result.status );

		if ( profitEl ) {
			profitEl.textContent = formatMoney( result.profit );
			profitEl.className = 'wcpp-card__value wcpp-card__value--' + result.status;
			profitEl.style.color = color;
		}

		if ( markupEl && config.showMarkup ) {
			markupEl.textContent = formatPercent( result.markup );
			markupEl.className = 'wcpp-card__value wcpp-card__value--' + result.status;
			markupEl.style.color = color;
		}

		if ( marginEl && config.showMargin ) {
			marginEl.textContent = formatPercent( result.margin );
			marginEl.className = 'wcpp-card__value wcpp-card__value--' + result.status;
			marginEl.style.color = color;
		}

		if ( badgeEl ) {
			const labels = {
				positive: '+',
				zero:     '=',
				negative: '−',
			};
			badgeEl.textContent = labels[ result.status ] || '=';
			badgeEl.className = 'wcpp-summary__badge wcpp-summary__badge--' + result.status;
		}

		if ( profitCard ) {
			profitCard.style.borderColor = color + '33';
		}
	}

	/**
	 * Reset a summary card to its empty placeholder state.
	 *
	 * @param {HTMLElement} summaryEl The summary wrapper.
	 */
	function setEmptyState( summaryEl ) {
		const fields = summaryEl.querySelectorAll( '[data-wcpp="profit"], [data-wcpp="markup"], [data-wcpp="margin"]' );
		fields.forEach( function ( el ) {
			el.textContent = '—';
			el.className = 'wcpp-card__value';
			el.style.color = '';
		} );

		const badgeEl = summaryEl.querySelector( '[data-wcpp="status-badge"]' );
		if ( badgeEl ) {
			badgeEl.textContent = config.i18n && config.i18n.noData ? config.i18n.noData : '—';
			badgeEl.className = 'wcpp-summary__badge';
		}
	}

	/**
	 * Get the effective sale price from regular and sale inputs.
	 *
	 * @param {HTMLInputElement|null} regularInput Regular price input.
	 * @param {HTMLInputElement|null} saleInput    Sale price input.
	 * @return {number}
	 */
	function getEffectiveSalePrice( regularInput, saleInput ) {
		const sale    = saleInput ? parsePrice( saleInput.value ) : 0;
		const regular = regularInput ? parsePrice( regularInput.value ) : 0;
		return sale > 0 ? sale : regular;
	}

	/**
	 * Bind live calculation to a simple product form.
	 */
	function bindSimpleProduct() {
		const regularInput = document.getElementById( '_regular_price' );
		const saleInput    = document.getElementById( '_sale_price' );
		const costInput    = document.getElementById( '_wc_cost_price' );
		const summaryEl    = document.getElementById( 'wcpp-simple-summary' );

		if ( ! summaryEl ) {
			return;
		}

		function recalculate() {
			const sale = getEffectiveSalePrice( regularInput, saleInput );
			const cost = costInput ? parsePrice( costInput.value ) : 0;
			updateSummary( summaryEl, sale, cost );
		}

		[ regularInput, saleInput, costInput ].forEach( function ( input ) {
			if ( input ) {
				input.addEventListener( 'input', recalculate );
				input.addEventListener( 'change', recalculate );
			}
		} );

		recalculate();
	}

	/**
	 * Bind live calculation to a single variation row.
	 *
	 * @param {HTMLElement} row The variation DOM row.
	 */
	function bindVariationRow( row ) {
		const summaryEl = row.querySelector( '[data-wcpp-summary="variation"]' );
		if ( ! summaryEl ) {
			return;
		}

		const regularInput = row.querySelector( 'input[name^="variable_regular_price"]' );
		const saleInput    = row.querySelector( 'input[name^="variable_sale_price"]' );
		const costInput    = row.querySelector( 'input[name^="_wc_cost_price_variation"]' );

		function recalculate() {
			const sale = getEffectiveSalePrice( regularInput, saleInput );
			const cost = costInput ? parsePrice( costInput.value ) : 0;
			updateSummary( summaryEl, sale, cost );
		}

		[ regularInput, saleInput, costInput ].forEach( function ( input ) {
			if ( input && ! input.dataset.wcppBound ) {
				input.dataset.wcppBound = '1';
				input.addEventListener( 'input', recalculate );
				input.addEventListener( 'change', recalculate );
			}
		} );

		recalculate();
	}

	/**
	 * Bind all existing variation rows.
	 */
	function bindAllVariations() {
		document.querySelectorAll( '.woocommerce_variation' ).forEach( bindVariationRow );
	}

	/**
	 * Initialise on DOM ready.
	 */
	function init() {
		bindSimpleProduct();
		bindAllVariations();

		// WooCommerce variation panel uses jQuery — hook in only for those events.
		if ( typeof jQuery !== 'undefined' ) {
			jQuery( '#woocommerce-product-data' ).on(
				'woocommerce_variations_loaded woocommerce_variations_added',
				function () {
					bindAllVariations();
				}
			);
		}
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
