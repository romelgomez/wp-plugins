<?php
	    $plugin_url = plugin_dir_url( __FILE__ );
		wp_enqueue_style('waconnect_popupcss',plugins_url ( 'popup/assets/css/popup.css', __FILE__ ));
		wp_enqueue_script('waconnect_popupjs',plugins_url ( 'popup/assets/js/jquery.popup.min.js', __FILE__ ));
		wp_enqueue_script('waconnect_cookiejs',plugins_url ( 'js/js.cookie.js', __FILE__ ));
?>


	<div id="wacPopup" style="display:none">

		<p><?php echo get_option('wac_pp_msg'); ?></p>
		<a href="<?php echo wa_build_link(get_option('wac_pp_number'),get_option('wac_pp_text')); ?>" class="wa-button wa-button-popup" target="_blank"><?php echo get_option("wac_pp_btn")?></a>
	</div>


<script type="text/javascript">
	jQuery(document).ready(function($) {
		var popup = new jQuery.Popup();
		var cookieValue = Cookies.get('wacNoNag');
		if(cookieValue != 1){
 		popup.open("#wacPopup");
 		}
 		Cookies.set("wacNoNag", 1, { expires : 1 });
	});
</script>