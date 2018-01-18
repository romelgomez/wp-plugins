<?php
/* @var $global DUP_PRO_Brand_Entity */


if (isset($_REQUEST['action'])) {
	//check_admin_referer($nonce_action);

	$action = $_REQUEST['action'];
	switch ($action) {

		case 'bulk-delete':
			$brand_ids = $_REQUEST['selected_id'];
			foreach ($brand_ids as $brand_id) {
				DUP_PRO_Brand_Entity::delete_by_id($brand_id);
			}
			break;

		case 'delete':
			$brand_id = (int) $_REQUEST['brand_id'];
			DUP_PRO_Brand_Entity::delete_by_id($brand_id);
			break;

	}
}

$brands = DUP_PRO_Brand_Entity::get_all();
$brand_count = count($brands);
$is_freelancer_plus = true;

?>

<style>
    /*Detail Tables */
    table.brand-tbl td {height: 45px}
    table.brand-tbl a.name {font-weight: bold}
    table.brand-tbl input[type='checkbox'] {margin-left: 5px}
    table.brand-tbl div.sub-menu {margin: 5px 0 0 2px; display: none}
    table tr.brand-detail {display:none; margin: 0;}
    table tr.brand-detail td { padding: 3px 0 5px 20px}
    table tr.brand-detail div {line-height: 20px; padding: 2px 2px 2px 15px}
    table tr.brand-detail td button {margin:5px 0 5px 0 !important; display: block}
    tr.brand-detail label {min-width: 150px; display: inline-block; font-weight: bold}
	form#dup-brand-form {padding:0}
</style>

<div <?php echo ($is_freelancer_plus) ? "style='display:none'" : ""; ?>>
	<h2><?php DUP_PRO_U::_e("Installer Branding") ?></h2>
	<hr size="1"/>
	<?php DUP_PRO_U::_e("This feature is only availble with Duplicator Pro Freelancer and above.  The Installer Branding allows for the installer to be branded with your own "
		. "custom name.  Below is quick sample of how you can customize the installer."); ?>
	<br/><br/>
	TODO ADD IMAGE (Cory will finish this later)
</div>


<!-- ====================
TOOL-BAR -->
<div <?php echo ($is_freelancer_plus) ? "" : "style='display:none'"; ?>>
<table class="dpro-edit-toolbar">
    <tr>
        <td>
            <select id="bulk_action">
                <option value="-1" selected="selected"><?php _e("Bulk Actions"); ?></option>
                <option value="delete" title="Delete selected brand endpoint(s)"><?php _e("Delete"); ?></option>
            </select>
            <input type="button" class="button action" value="<?php DUP_PRO_U::_e("Apply") ?>" onclick="DupPro.Settings.Brand.BulkAction()">
        </td>
        <td>
			<div class="btnnav">
				<span><i class="fa fa-photo"></i> <?php DUP_PRO_U::_e("Brands"); ?></span>
				<a href="javascript:void(0)" onclick="DupPro.Settings.Brand.AddNew()" class="add-new-h2"><?php DUP_PRO_U::_e('Add New'); ?></a>
			</div>
        </td>
    </tr>
</table>

<form id="dup-brand-form" action="<?php echo $brand_list_url; ?>" method="post">
<?php wp_nonce_field($nonce_action); ?>
<input type="hidden" id="dup-brand-form-action" name="action" value=""/>
<input type="hidden" id="dup-selected-brand" name="brand_id" value="-1"/>

<!-- ====================
LIST ALL STORAGE -->
<table class="widefat brand-tbl">
	<thead>
		<tr>
			<th style='width:10px;'><input type="checkbox" id="dpro-chk-all" title="Select all brand endpoints" onclick="DupPro.Settings.Brand.SetAll(this)"></th>
			<th style='width:300px;'>Name</th>
			<th><?php DUP_PRO_U::_e('Active'); ?></th>
		</tr>
	</thead>
	<tbody>
		<?php
		$i = 0;
		foreach ($brands as $brand) :
			$i++;
			//$brand_type = $brand->get_mode_text();
			?>
			<tr id='main-view-<?php echo $brand->id ?>' class="brand-row <?php echo ($i % 2) ? 'alternate' : ''; ?>">
				<td>
					<?php if ($brand->editable) : ?>
						<input name="selected_id[]" type="checkbox" value="<?php echo $brand->id; ?>" class="item-chk" />
					<?php else : ?>
						<input type="checkbox" disabled="disabled" />
					<?php endif; ?>
				</td>
				<td>
					<?php if ($brand->editable) : ?>
						<a href="javascript:void(0);" onclick="DupPro.Settings.Brand.Edit('<?php echo $brand->id; ?>')"><b><?php echo $brand->name; ?></b></a>
						<div class="sub-menu">
							<a href="javascript:void(0);" onclick="DupPro.Settings.Brand.Edit('<?php echo $brand->id; ?>')">Edit</a> |
							<a href="javascript:void(0);" onclick="DupPro.Settings.Brand.View('<?php echo $brand->id; ?>');">Quick View</a> |
							<a href="javascript:void(0);" onclick="DupPro.Settings.Brand.Delete('<?php echo $brand->id; ?>');">Delete</a>
						</div>
					<?php else : ?>
						<a href="javascript:void(0);" onclick="DupPro.Settings.Brand.Edit(0)"><b><?php DUP_PRO_U::_e('Default'); ?></b></a>
						<div class="sub-menu">
							<a href="javascript:void(0);" onclick="DupPro.Settings.Brand.Edit(0)">View</a> |
							<a href="javascript:void(0);" onclick="DupPro.Settings.Brand.View('<?php echo $brand->id; ?>');">Quick View</a>
						</div>
					<?php endif; ?>
				</td>
				<td>
					<?php if (! $brand->editable) : ?>
						<?php echo DUP_PRO_U::_e('Enabled'); ?>
					<?php endif; ?>

				</td>
			</tr>
			<tr id='quick-view-<?php echo $brand->id ?>' class='<?php echo ($i % 2) ? 'alternate' : ''; ?> brand-detail'>
				<td colspan="3">
					<b><?php DUP_PRO_U::_e('QUICK VIEW') ?></b> <br/>
					<div>
						<label><?php DUP_PRO_U::_e('Name') ?>:</label>
						<?php echo $brand->name ?>
					</div>
					<div>
						<label><?php DUP_PRO_U::_e('Notes') ?>:</label>
						<?php echo (strlen($brand->notes)) ? $brand->notes : DUP_PRO_U::__('(no notes)'); ?>
					</div>
					<div>
						<label><?php DUP_PRO_U::_e('Logo') ?>:</label>
						<?php echo $brand->logo ?>
					</div>
					<button type="button" class="button" onclick="DupPro.Settings.Brand.View('<?php echo $brand->id; ?>');"><?php DUP_PRO_U::_e('Close') ?></button>
				</td>
			</tr>
		<?php endforeach; ?>
	</tbody>
	<tfoot>
		<tr>
			<th colspan="8" style="text-align:right; font-size:12px">
				<?php echo DUP_PRO_U::__('Total') . ': ' . $brand_count; ?>
			</th>
		</tr>
	</tfoot>
</table>
</form>
</div>

<script>
jQuery(document).ready(function ($) {

	//Shows detail view
	DupPro.Settings.Brand.AddNew = function()
	{
		document.location.href = '<?php echo "{$brand_edit_url}&action=new"; ?>';
	}

	DupPro.Settings.Brand.Edit = function (id)
	{
		if (id == 0) {
			document.location.href = '<?php echo "{$brand_edit_url}&action=default&id="; ?>' + id;
		} else {
			document.location.href = '<?php echo "{$brand_edit_url}&action=edit&id="; ?>' + id;
		}
	}

	//Shows detail view
	DupPro.Settings.Brand.View = function (id)
	{
		$('#quick-view-' + id).toggle();
		$('#main-view-' + id).toggle();
	}

	//Delets a single record
	DupPro.Settings.Brand.Delete = function (id)
	{
		if (confirm("<?php DUP_PRO_U::_e('Are you sure you want to delete the selected items?') ?>")) {
			jQuery("#dup-brand-form-action").val('delete');
			jQuery("#dup-selected-brand").val(id);
			jQuery("#dup-brand-form").submit();
		}
	}

	DupPro.Settings.Brand.BulkAction = function () 
	{
		var action = $('#bulk_action').val();
		var checked = ($('.item-chk:checked').length > 0);

		if (checked) {
			switch (action) {
				case 'delete':
					var message = "<?php DUP_PRO_U::_e('Delete the selected items?') ?>";
					if (confirm(message)) {
						jQuery("#dup-brand-form-action").val('bulk-delete');
						jQuery("#dup-brand-form").submit();
					}
					break;
			}
		}
	}

	//Sets all for deletion
	DupPro.Settings.Brand.SetAll = function (chkbox) {
		$('.item-chk').each(function () {
			this.checked = chkbox.checked;
		});
	}

	//Name hover show menu
	$("tr.brand-row").hover(
			function () {
				$(this).find(".sub-menu").show();
			},
			function () {
				$(this).find(".sub-menu").hide();
			}
	);
});
</script>
