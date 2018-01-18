jQuery(document).ready(function($) {

	jQuery("#disable_vulns_wptc").on("click", function() {
		jQuery("#enable_vulns_options_wptc").hide();
	});

	jQuery("#enable_vulns_wptc").on("click", function() {
		jQuery("#enable_vulns_options_wptc").show();
	});


	jQuery("input[name=wptc_vulns_plugins]:checkbox").change(function(){
		jQuery('#wptc_vulns_plugins_dw').hide();
		if(jQuery(this).is(':checked')){
			jQuery('#wptc_vulns_plugins_dw').show();
			fancy_tree_init_vulns_plugins_wptc();
		}
	});

	jQuery("input[name=wptc_vulns_themes]:checkbox").change(function(){
		jQuery('#wptc_vulns_themes_dw').hide();
		if(jQuery(this).is(':checked')){
			jQuery('#wptc_vulns_themes_dw').show();
			fancy_tree_init_vulns_themes_wptc();
		}
	});

	if(jQuery("input[name=wptc_vulns_plugins]").is(':checked')){
		jQuery('#wptc_vulns_plugins_dw').show();
		fancy_tree_init_vulns_plugins_wptc();
	}

	if(jQuery("input[name=wptc_vulns_themes]").is(':checked')){
		jQuery('#wptc_vulns_themes_dw').show();
		fancy_tree_init_vulns_themes_wptc();
	}

});

function fancy_tree_init_vulns_plugins_wptc(){
	jQuery("#wptc_vulns_plugins_dw").fancytree({
		checkbox: true,
		selectMode: 2,
		icon:true,
		debugLevel:0,
		source: {
			url: ajaxurl,
			data: {
				"action": "get_installed_plugins_vulns_wptc",
				security: wptc_ajax_object.ajax_nonce,
			},
		},
		init: function (event, data) {
			data.tree.getRootNode().visit(function (node) {
				if (node.data.preselected) node.setSelected(true);
			});
		},
		select: function(event, data) {
			// Get a list of all selected nodes, and convert to a key array:
			var selKeys = jQuery.map(data.tree.getSelectedNodes(), function(node){
				return node.key;
			});
			jQuery("#vulns_include_plugins_wptc").val(selKeys.join(","));
		},
		dblclick: function(event, data) {
			data.node.toggleSelected();
		},
		keydown: function(event, data) {
			if( event.which === 32 ) {
				data.node.toggleSelected();
				return false;
			}
		},
		cookieId: "fancytree-Cb3",
		idPrefix: "fancytree-Cb3-"
	});
}

function fancy_tree_init_vulns_themes_wptc(){
	jQuery("#wptc_vulns_themes_dw").fancytree({
		checkbox: true,
		selectMode: 2,
		icon:true,
		debugLevel:0,
		source: {
			url: ajaxurl,
			security: wptc_ajax_object.ajax_nonce,
			data: {
				"action": "get_installed_themes_vulns_wptc",
				security: wptc_ajax_object.ajax_nonce,
			},
		},
		init: function (event, data) {
			data.tree.getRootNode().visit(function (node) {
				if (node.data.preselected) node.setSelected(true);
			});
		},
		select: function(event, data) {
			var selKeys = jQuery.map(data.tree.getSelectedNodes(), function(node){
				return node.key;
			});
			jQuery("#vulns_include_themes_wptc").val(selKeys.join(","));
		},
		dblclick: function(event, data) {
			data.node.toggleSelected();
		},
		keydown: function(event, data) {
			if( event.which === 32 ) {
				data.node.toggleSelected();
				return false;
			}
		},
		cookieId: "fancytree-Cb3",
		idPrefix: "fancytree-Cb3-"
	});
}


