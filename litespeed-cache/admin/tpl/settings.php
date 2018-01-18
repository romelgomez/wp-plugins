<?php
if (!defined('WPINC')) die ;

$this->check_license() ;

$menu_list = array(
	'general' => __('General', 'litespeed-cache'),
	'cache' => __('Cache', 'litespeed-cache'),
	'purge' => __('Purge', 'litespeed-cache'),
	'excludes' => __('Excludes', 'litespeed-cache'),
	'optimize' => __('Optimize', 'litespeed-cache'),
	'tuning' => __('Tuning', 'litespeed-cache'),
	'media' => __('Media', 'litespeed-cache'),
	'cdn' => __('CDN', 'litespeed-cache'),
) ;

$menu_list['esi'] = __('ESI', 'litespeed-cache') ;

if (!is_multisite()) {
	$menu_list['advanced'] = __('Advanced', 'litespeed-cache') ;
}

$menu_list['debug'] = __('Debug', 'litespeed-cache') ;

if ($this->show_compatibility_tab()){
	$menu_list['compatibilities'] = __('Compatibilities', 'litespeed-cache') ;
}

$menu_list['crawler'] = __('Crawler', 'litespeed-cache') ;

global $_options ;
$_options = LiteSpeed_Cache_Config::get_instance()->get_options() ;


/**
 * This hook allows third party plugins to create litespeed cache
 * specific configuration.
 *
 * Each config should append an array containing the following:
 * 'title' (required) - The tab's title.
 * 'slug' (required) - The slug used for the tab. [a-z][A-Z], [0-9], -, _ permitted.
 * 'content' (required) - The tab's content.
 *
 * Upon saving, only the options with the option group in the input's
 * name will be retrieved.
 * For example, name="litespeed-cache-conf[my-opt]".
 *
 * @see TODO: add option save filter.
 * @since 1.0.9
 * @param array $tabs An array of third party configuration.
 * @param array $options The current configuration options.
 * @param string $option_group The option group to use for options.
 * @param boolean $disableall Whether to disable the settings or not.
 * @return mixed An array of third party configs else false on failure.
 */
$tp_tabs = apply_filters('litespeed_cache_add_config_tab',
	array(),
	$_options,
	LiteSpeed_Cache_Config::OPTION_NAME,
	$this->get_disable_all()
) ;
if ( !empty($tp_tabs) && is_array($tp_tabs) ) {
	foreach ($tp_tabs as $key => $tab) {
		if ( !is_array($tab) || !isset($tab['title']) || !isset($tab['slug']) || !isset($tab['content']) ) {
			defined( 'LSCWP_LOG' ) && LiteSpeed_Cache_Log::debug( 'WARNING: Third party tab input invalid' ) ;
			unset($tp_tabs[$key]) ;
			continue ;
		}
		if ( preg_match('/[^-\w]/', $tab['slug']) ) {
			defined( 'LSCWP_LOG' ) && LiteSpeed_Cache_Log::debug( 'WARNING: Third party config slug contains invalid characters' ) ;
			unset($tp_tabs[$key]) ;
			continue ;
		}
	}
}
else {
	$tp_tabs = array() ;
}

/**
 * Generate rules for setting usage
 * @since 1.6.2
 */
global $wp_roles ;
if ( !isset( $wp_roles ) ) {
	$wp_roles = new WP_Roles() ;
}
$roles = array_keys( $wp_roles->roles ) ;

sort( $roles ) ;


include_once LSCWP_DIR . "admin/tpl/inc/banner_promo.php" ;
?>

<div class="wrap">
	<h2>
		<?php echo __('LiteSpeed Cache Settings', 'litespeed-cache') ; ?>
		<span class="litespeed-desc">
			v<?php echo LiteSpeed_Cache::PLUGIN_VERSION ; ?>
		</span>
	</h2>
</div>
<div class="litespeed-wrap">
	<h2 class="litespeed-header">
	<?php
		$i = 1 ;
		foreach ($menu_list as $tab => $val){
			$accesskey = $i <= 9 ? "litespeed-accesskey='$i'" : '' ;
			echo "<a class='litespeed-tab' href='#$tab' data-litespeed-tab='$tab' $accesskey>$val</a>" ;
			$i ++ ;
		}
		foreach ($tp_tabs as $val){
			$accesskey = $i <= 9 ? "litespeed-accesskey='$i'" : '' ;
			echo "<a class='litespeed-tab' href='#$val[slug]' data-litespeed-tab='$val[slug]' $accesskey>$val[title]</a>" ;
			$i ++ ;
		}
	?>
	</h2>
	<div class="litespeed-body">
	<form method="post" action="options.php" id="litespeed_form_options" class="litespeed-relative">
		<!--input type="hidden" name="<?php echo LiteSpeed_Cache::ACTION_KEY ; ?>" value="<?php echo LiteSpeed_Cache::ACTION_SAVE_SETTINGS ; ?>" /-->

	<?php if ($this->get_disable_all()): ?>
		<div class="litespeed-callout-danger">
			<h4><?php echo __( 'WARNING', 'litespeed-cache' ) ; ?></h4>
			<p>
				<?php echo __('The network admin selected use primary site configs for all subsites.', 'litespeed-cache') ; ?>
				<?php echo __('The following options are selected, but are not editable in this settings page.', 'litespeed-cache') ; ?>
			</p>
		</div>
	<?php endif ; ?>

	<?php
	settings_fields(LiteSpeed_Cache_Config::OPTION_NAME) ;

	// include all tpl for faster UE
	foreach ($menu_list as $tab => $val) {
		echo "<div data-litespeed-layout='$tab'>" ;
		require LSCWP_DIR . "admin/tpl/setting/settings_$tab.php" ;
		echo "</div>" ;
	}

	foreach ($tp_tabs as $val) {
		echo "<div data-litespeed-layout='$val[slug]'>$val[content]</div>" ;
	}

	echo "<div class='litespeed-top20'></div>" ;

	if ( $this->get_disable_all() ) {
		submit_button(__('Save Changes', 'litespeed-cache'), 'litespeed-btn-primary', 'litespeed-submit', true, array('disabled' => true)) ;
	}
	else {
		submit_button(__('Save Changes', 'litespeed-cache'), 'litespeed-btn-primary', 'litespeed-submit') ;
	}

	?>
	</form>
	</div>
</div>
