<h2 id='staging_area_wptc'>Staging Area</h2>

<?php
if($this->config = WPTC_Factory::get('config')->get_option('s2l_is_subdir_installation')){?>
	Sorry, Right now, there isn't an option to push the changes from your staging site to production site.<br>
	But, we are working on this feature which should be available in next few weeks. <?php
} else {
	add_thickbox(); ?>
	<div id="dashboard_activity" class="staging_area_wptc" style="margin: 40px 20px 30px 150px;">
		<div style="margin: 0px 0px 10px 0px;">Last copy to live on : <span id="last_copy_to_live"><?php echo $stage_to_live->get_last_time_copy_to_live(); ?></span>. </div>
		<a href="#TB_inline?width=600&height=550" class="thickbox wptc-thickbox " style="display: none"></a>
		<a id="ask_copy_staging_wptc" class="button button-primary ">Copy site to live</a>
	</div>
<?php }?>

