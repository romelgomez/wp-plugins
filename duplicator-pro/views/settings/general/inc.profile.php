<?php
defined("ABSPATH") or die("");

$global  = DUP_PRO_Global_Entity::get_instance();

$nonce_action	 = 'duppro-settings-general-edit';
$action_updated  = null;
$action_response = DUP_PRO_U::__("Profile Settings Updated");

//SAVE RESULTS
if (isset($_REQUEST['action'])) {

    check_admin_referer($nonce_action);
	if ($_REQUEST['action'] == 'save') {
		$global->profile_idea	= isset($_POST['_profile_idea']) ? 1 : 0;
		$global->profile_alpha	= isset($_POST['_profile_alpha']) ? 1 : 0;
		$global->profile_beta	= isset($_POST['_profile_beta']) ? 1 : 0;
    }

	$action_updated = $global->save();
	$global->adjust_settings_for_system();	
}

?>

<style>
	td.profiles p.description {margin:5px 0 20px 25px; font-size:11px}
</style>

<form id="dup-settings-form" action="<?php echo self_admin_url('admin.php?page=' . DUP_PRO_Constants::$SETTINGS_SUBMENU_SLUG); ?>" method="post" data-parsley-validate>
<?php wp_nonce_field($nonce_action); ?>
<input type="hidden" id="dup-settings-action" name="action" value="save">
<input type="hidden" name="page" value="<?php echo DUP_PRO_Constants::$SETTINGS_SUBMENU_SLUG ?>">
<input type="hidden" name="tab" value="general">
<input type="hidden" name="subtab" value="profile">

<?php if ($action_updated) : ?>
	<div class="notice notice-success is-dismissible dpro-wpnotice-box"><p><?php echo $action_response; ?></p></div>
<?php endif; ?>

<!-- ===============================
OPTIONS -->
<h3 class="title"><?php DUP_PRO_U::_e("Options") ?> </h3>
<hr size="1" />
<table class="form-table">
	<tr>
		<th scope="row"><label><?php DUP_PRO_U::_e("Profiles"); ?></label></th>
		<td class="profiles">
			<?php
				DUP_PRO_U::_e("Profiles allow users to test and preview various features of upcoming concepts the Duplicator team is working on.<br/>  "
					. "These settings should not be used on production sites; unless specified by the Duplicator team.<br/> "
					. "Please leave your <a href='https://snapcreek.com/support/?idea=1' target='_blank'>feedback</a>!");
			?>
			<br/><br/>
			<!-- ================
			IDEA -->
			<input type="checkbox" name="_profile_idea" id="_profile_idea" <?php echo DUP_PRO_UI::echoChecked($global->profile_idea); ?> />
			<label for="_profile_idea"><?php DUP_PRO_U::_e("Design Concepts"); ?></label>
			<i class="fa fa-question-circle"
				data-tooltip-title="<?php DUP_PRO_U::_e("Concept Views"); ?>"
				data-tooltip="<?php DUP_PRO_U::_e('Checking this checkbox will enable various idea design concepts.  These features DO NOT function, they are simply UI mockups.  Please '
					. 'let us know what you think of the concepts as they may eventually become features. '); ?>"></i>

			<p class="description">
				<?php
					DUP_PRO_U::_e("- Installer Branding - see: Settings &gt; Packages &gt; Installer Branding SubTab ");
				?>
			</p>

			<!-- ================
			ALPHA -->
			<input type="checkbox" name="_profile_alpha" id="_profile_alpha" <?php echo DUP_PRO_UI::echoChecked($global->profile_alpha); ?> />
			<label for="_profile_alpha"><?php DUP_PRO_U::_e("Alpha Features"); ?></label>
			<i class="fa fa-question-circle"
				data-tooltip-title="<?php DUP_PRO_U::_e("Alpha Features"); ?>"
				data-tooltip="<?php DUP_PRO_U::_e('Checking this checkbox will enable various alpha features.  These features should never be used in production and with high caution '
					. 'on development and staging enviroments.'); ?>"></i>

			<p class="description">
				<?php
					DUP_PRO_U::_e("- N/A");
					//DUP_PRO_U::_e("- DupArchive Scheduled Builds - see: Settings &gt; General &gt; Basic &gt; Archive: Scheduled Builds");
				?>
			</p>

			<!-- ================
			BETA -->
			<input type="checkbox" name="_profile_beta" id="_profile_beta" <?php echo DUP_PRO_UI::echoChecked($global->profile_beta); ?> />
			<label for="_profile_beta"><?php DUP_PRO_U::_e("Beta Features"); ?></label>
			<i class="fa fa-question-circle"
				data-tooltip-title="<?php DUP_PRO_U::_e("Debug views"); ?>"
				data-tooltip="<?php DUP_PRO_U::_e('Checking this checkbox will enable various beta features.  These features should not be used in production environments.  Please '
					. 'let us know what you think of these new features when they are released. '); ?>"></i>

			<p class="description">
				<?php
					//DUP_PRO_U::_e("- N/A");
					DUP_PRO_U::_e("- Migrate a site over this site - see: Tools &gt; Import tab. <br/>");
				?>
			</p>

			<!-- ================
			LIVE -->
			<input type="checkbox" checked="checked" disabled="disabled" />
			<label for="_profile_beta"><?php DUP_PRO_U::_e("Current Features"); ?></label>
			<p class="description">
				<?php
					DUP_PRO_U::_e("- See the <a href='https://snapcreek.com/duplicator/docs/changelog/' target='_blank'>changelog</a> for the latest updates</a>.");
				?>
			</p>
		</td>
	</tr>
</table>

<p class="submit" style="margin:5px 0px 0xp 5px;">
	<input type="submit" name="submit" id="submit" class="button-primary" value="<?php DUP_PRO_U::_e('Save Feature Settings') ?>" style="display: inline-block;" />
</p>
</form>
