jQuery(document).ready(function($) {
	// Invalidate cache of WooCommerce minicart when Currency changes. This will
	// ensure that the minicart is updated correctly
	supports_html5_storage = ('sessionStorage' in window && window['sessionStorage'] !== null);

	if(supports_html5_storage) {
		$('.widget_wc_aelia_currencyswitcher_widget, .widget_wc_aelia_billing_country_selector_widget').on('submit', 'form', function() {
			sessionStorage.removeItem('wc_fragments', '');
		});
	}

	// Hide the "Change Currency" button and submit the Widget form when Currency
	// changes
	$('.widget_wc_aelia_currencyswitcher_widget')
		.find('.change_currency')
		.hide()
		.end()
		.on('change', '#aelia_cs_currencies', function(event) {
			var currency_widget_form = $(this).closest('form');
			$(currency_widget_form).submit();
			event.stopPropagation();
			return false;
		});

	// Hide the "Change country" button and submit the Widget form when billing
	// country changes
	$('.currency_switcher.widget_wc_aelia_country_selector_widget')
		.find('.change_country')
		.hide()
		.end()
		.on('change', '.countries', function(event) {
			var widget_form = $(this).closest('form');
			$(widget_form).submit();
			event.stopPropagation();
			return false;
		});

});
