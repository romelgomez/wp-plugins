<?php
/* @var $brand DUP_PRO_Brand_Entity */
$was_updated = false;

//check_admin_referer($nonce_action);
$_REQUEST['action'] = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'new';
$_REQUEST['id']		= isset($_REQUEST['id'])	 ? $_REQUEST['id'] : 0;

$is_freelancer_plus = true;

switch ($_REQUEST['action']) {

	case 'new':
		$brand = new DUP_PRO_Brand_Entity();
		$brand->name = DUP_PRO_U::__('New Brand');
		break;

	case 'default':
		$brand = DUP_PRO_Brand_Entity::get_default_brand();
		break;

	case 'edit':
		$brand = DUP_PRO_Brand_Entity::get_by_id($_REQUEST['id']);
		break;

	case 'save':
		$was_updated	 = true;
		$brand			 = new DUP_PRO_Brand_Entity();
		$brand->name	 = DUP_PRO_U::setVal($_POST['name'], 'New Brand');
		$brand->notes	 = DUP_PRO_U::setVal($_POST['notes'], '');
		$brand->logo	 = stripcslashes(DUP_PRO_U::setVal($_POST['logo'], ''));
		$brand->save();
		break;
}
?>

<style>
    #dup-storage-form input[type="text"], input[type="password"] { width: 250px;}
	#dup-storage-form input#name {width:100%; max-width: 500px}
	#dup-storage-form input#_local_storage_folder {width:100% !important; max-width: 500px}
	td.dpro-sub-title {padding:0; margin: 0}
	td.dpro-sub-title b{padding:20px 0; margin: 0; display:block; font-size:1.25em;}
	input#max_default_store_files {width:50px !important}
	form#dpro-package-brand-form {padding: 0}
	form#dpro-package-brand-form input[type="text"] { width:350px;}
	form#dpro-package-brand-form .readonly {background:transparent; border:none;}
	textarea#brand-notes {width:350px;}
	textarea#brand-logo {width:600px; height:50px; font-size: 12px}
	textarea#brand-default-logo {width:600px;; height:50px; font-size: 12px}
	div.style-guide-link {text-align: right; width: 100%; display: inline-block; margin:0 0 5px 0}
	table.form-table {width:800px}
	div.dpro-dlg-alert-txt {line-height: 20px; font-size: 14px !important}

	div.preview-area {border:2px dashed #CDCDCD; width:95%; height:175px; background:#fff; font-family: Verdana,Arial,sans-serif;}
	div.preview-box {border:1px solid #CDCDCD; border-radius: 5px; width: 750px; margin: 10px auto 0 auto; height:130px; border-bottom: 1px dashed #999}
	div.preview-header {height:45px; background: #F1F1F1; box-shadow: 0 5px 3px -3px #999;}
	div.preview-title {font-size:26px; padding:10px 0 7px 15px; font-weight: bold;  min-height:30px}
	div.preview-content {padding:8px 15px 0 15px; clear:both}
	div.preview-version {white-space:nowrap; color:#777; font-size:11px; font-style:italic; text-align:right; padding:0 15px 5px 0; line-height: 14px; font-weight:normal; float:right}
	div.preview-version a {color:#999}
	div.preview-mode {text-align: right; color:#999; font-style: italic; font-size: 12px}
	div.preview-steps {font-size: 22px;  padding: 0 0 5px 0;   border-bottom: 1px solid #D3D3D3;  font-weight: bold;  margin: 15px 0 20px 0;}
	div.preview-steps b {color:red}
	div#preview-logo {display: inline-block}
	div.preview-notes {text-align:center; font-style: italic; font-size: 12px; margin:5px}
</style>

<?php
	if ($was_updated) {
		$update_message = 'Brand Saved!';
		echo "<div class='notice notice-success is-dismissible dpro-wpnotice-box'><p>{$update_message}</p></div>";
	}
?>
 <!-- ====================
TOOL-BAR -->
<table class="dpro-edit-toolbar">
	<tr>
		<td></td>
		<td>
			<div class="btnnav">
				<a href="<?php echo $brand_list_url; ?>" class="add-new-h2"> <i class="fa fa-photo"></i> <?php DUP_PRO_U::_e('Brands'); ?></a>
				<?php if ($_REQUEST['action'] == 'new') : ?>
					<span><?php DUP_PRO_U::_e('Add New'); ?></span>
				<?php else: ?>
					<a href="<?php echo $brand_edit_url; ?>&action=new" class="add-new-h2"><?php DUP_PRO_U::_e('Add New'); ?></a>
				<?php endif; ?>
			</div>
		</td>
	</tr>
</table>
<hr class="dpro-edit-toolbar-divider"/>

<form id="dpro-package-brand-form" action="<?php echo $brand_edit_url; ?>" method="post" data-parsley-ui-enabled="true">
    <?php wp_nonce_field($nonce_action); ?>
	<input type="hidden" name="id" id="brand-id" value="<?php echo $brand->id; ?>" />
	<input type="hidden" name="action" id="brand-action" value="<?php echo $_REQUEST['action']; ?>" />

	<?php if ($_REQUEST['action'] == 'default') : ?>
		<table class="provider form-table">
			<tr>
				<th scope="row"><label><?php DUP_PRO_U::_e("Name"); ?></label></th>
				<td><?php echo $brand->name; ?></td>
			</tr>
			<tr">
				<th scope="row"><label><?php DUP_PRO_U::_e("Notes"); ?></label></th>
				<td><?php echo $brand->notes; ?></td>
			</tr>
			<tr>
				<th scope="row"><label><?php DUP_PRO_U::_e("Logo"); ?></label></th>
				<td>
					<div class="style-guide-link">
						<a href="javascript:void" class="button button-small" onclick="DupPro.Brand.ShowStyleGuide();"><?php DUP_PRO_U::_e("Style Guide"); ?></a>
					</div>
					<textarea id="brand-default-logo" readonly="true"><?php echo $brand->logo; ?></textarea>
				</td>
			</tr>
			<tr>
				<th scope="row"><label><?php DUP_PRO_U::_e("Active"); ?></label></th>
				<td>Yes/No</td>
			</tr>
		</table>
		<i><?php DUP_PRO_U::_e("The default brand cannot be changed"); ?></i>
		<br/><br/>
	<?php else: ?>
		<table class="provider form-table">
			<tr>
				<th scope="row"><label><?php DUP_PRO_U::_e("Name"); ?></label></th>
				<td><input type="text" name="name" id="brand-name" value="<?php echo $brand->name; ?>" data-parsley-required></td>
			</tr>
			<tr>
				<th scope="row"><label><?php DUP_PRO_U::_e("Notes"); ?></label></th>
				<td><textarea name="notes" id="brand-notes"><?php echo $brand->notes; ?></textarea></td>
			</tr>
			<tr>
				<th scope="row"><label><?php DUP_PRO_U::_e("Logo"); ?></label></th>
				<td>
					<div class="style-guide-link">
						<a href="javascript:void" class="button button-small" onclick="DupPro.Brand.ShowStyleGuide();"><?php DUP_PRO_U::_e("Style Guide"); ?></a>
					</div>
					<textarea name="logo" id="brand-logo" required="true"><?php echo $brand->logo; ?></textarea>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="brand-active"><?php DUP_PRO_U::_e("Active"); ?></label></th>
				<td>
					<input type="checkbox" name="active" id="brand-active" />
					<label for="brand-active"><?php DUP_PRO_U::_e("Make this the active brand"); ?></label>
				</td>
			</tr>
		</table>
	<?php endif; ?>

	<!-- ================================
    PREVIEW AREA -->
	<h2><?php DUP_PRO_U::_e('Preview Area:'); ?></h2>
	<div class="preview-area">
		<div class="preview-box">
			<div class="preview-header">
				<div class="preview-title">
					<div id="preview-logo">
						<?php echo $brand->logo; ?>
					</div>
					<div class="preview-version">
						<?php DUP_PRO_U::_e("version: "); echo DUPLICATOR_PRO_VERSION; ?> <br/>
						» <a href="javascript:void(0)">info</a> » <a href="javascript:void(0)">help</a> <i class="fa fa-question-circle"></i>
					</div>
				</div>
				<div class="preview-content">
					<div class="preview-mode"><?php DUP_PRO_U::_e("Mode: Standard Install"); ?></div>
					<div class="preview-steps">
						<?php DUP_PRO_U::_e("Step <b>1</b> of 4: Deployment"); ?>
					</div>
				</div>
			</div>
		</div>
		<div class="preview-notes">
			<?php DUP_PRO_U::_e("Note: Be sure to validate the final results in the installer.php file."); ?>
		</div>
	</div>
    <br style="clear:both" />
    <button class="button button-primary" type="button" onclick="DupPro.Settings.Brand.Save()"><?php DUP_PRO_U::_e('Save Brand'); ?></button>
</form>

<!-- ==========================================
THICK-BOX DIALOGS: -->
<?php
	$guide_msg  = DUP_PRO_U::__('The brandable area allows for a loose set of html and custom styling.  Below is a general guide.<br/><br/>');
	$guide_msg .=  DUP_PRO_U::__('- <b>Embed Image:</b><br/> &lt;img src="/wp-content/uploads/image.png /&gt; <br/><br/>');
	$guide_msg .=  DUP_PRO_U::__('- <b>Text Only:</b><br/> My Installer Name <br/><br/>');
	$guide_msg .=  DUP_PRO_U::__('- <b>Text &amp; Font-Awesome:</b><br/> &lt;i class="fa fa-cube"&gt;&lt;/i&gt; My Company <br/><small>Note: Font-Awesome 4.7 is the referenced library</small><br/><br/>');

	$alert1 = new DUP_PRO_UI_Dialog();
	$alert1->title		= DUP_PRO_U::__('Branding Guide');
	$alert1->message	= $guide_msg;
	$alert1->width	= 650;
	$alert1->height	= 350;
	$alert1->initAlert();
?>

<script>
	DupPro.Brand = new Object();

	/*	Shows the style Guide */
	DupPro.Brand.ShowStyleGuide = function()
	{
		<?php $alert1->showAlert(); ?>
		return;
	}


    jQuery(document).ready(function ($)
	{
		DupPro.Settings.Brand.Save = function() {
			if ($('#dpro-package-brand-form').parsley().validate()) {
				$('#brand-action').val('save');
				$('#dpro-package-brand-form').submit();
			}
        }

		//INIT
		$('#brand-name').focus();
    });
</script>


