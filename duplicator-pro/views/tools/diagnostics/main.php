<?php
defined("ABSPATH") or die("");
wp_enqueue_script('dup-handlebars');
require_once(DUPLICATOR_PRO_PLUGIN_PATH.'/classes/entities/class.global.entity.php');
require_once(DUPLICATOR_PRO_PLUGIN_PATH.'/assets/js/javascript.php');
require_once(DUPLICATOR_PRO_PLUGIN_PATH.'/views/inc.header.php');
require_once(DUPLICATOR_PRO_PLUGIN_PATH.'/classes/class.scan.check.php');
require_once(DUPLICATOR_PRO_PLUGIN_PATH.'/classes/ui/class.ui.dialog.php');

global $wp_version;
global $wpdb;

$action_response = null;

$txt_found     = DUP_PRO_U::__("Found");
$txt_not_found = DUP_PRO_U::__("Removed");

$view_state          = DUP_PRO_UI_ViewState::getArray();
$ui_css_srv_panel    = (isset($view_state['dup-settings-diag-srv-panel']) && $view_state['dup-settings-diag-srv-panel']) ? 'display:block' : 'display:none';
$ui_css_opts_panel   = (isset($view_state['dup-settings-diag-opts-panel']) && $view_state['dup-settings-diag-opts-panel']) ? 'display:block' : 'display:none';
$installer_files     = DUP_PRO_Server::getInstallerFiles();
$orphaned_filepaths  = DUP_PRO_Server::getOrphanedPackageFiles();
$scan_run            = (isset($_POST['action']) && $_POST['action'] == 'duplicator_recursion') ? true : false;
$archive_file        = (isset($_GET['package'])) ? esc_html($_GET['package']) : '';
$archive_path        = empty($archive_file) ? '' : DUPLICATOR_PRO_WPROOTPATH.$archive_file;
$long_installer_path = (isset($_GET['installer_name'])) ? DUPLICATOR_PRO_WPROOTPATH.esc_html($_GET['installer_name']) : '';

//POST BACK
$action_updated     = null;
$_REQUEST['action'] = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'display';

if (isset($_REQUEST['action'])) {
    switch ($_REQUEST['action']) {
        case 'duplicator_pro_tools' :
            $action_response = DUP_PRO_U::__('Plugin settings reset.');
            break;
        case 'duplicator_pro_ui_view_state' :
            $action_response = DUP_PRO_U::__('View state settings reset.');
            break;
        case 'duplicator_pro_package_active' :
            $action_response = DUP_PRO_U::__('Active package settings reset.');
            break;
        case 'installer' :
            $action_response = DUP_PRO_U::__('Installer file cleanup ran!');
            $css_hide_msg    = 'div#dpro-global-error-reserved-files {display:none}';
            break;
        case 'purge-orphans':
            $action_response = DUP_PRO_U::__('Cleaned up orphaned package files!');
            break;
        case 'tmp-cache':
            DUP_PRO_Package::tmp_cleanup(true);
            $action_response = DUP_PRO_U::__('Build cache removed.');
            break;
    }
}
?>

<style>
<?php echo isset($css_hide_msg) ? $css_hide_msg : ''; ?>
    div#message {margin:0px 0px 10px 0px}
    td.dpro-settings-diag-header {background-color:#D8D8D8; font-weight: bold; border-style: none; color:black}
    table.widefat th {font-weight:bold; }
    table.widefat td {padding:2px 2px 2px 8px; }
    table.widefat td:nth-child(1) {width:10px;}
    table.widefat td:nth-child(2) {padding-left: 20px; width:100% !important}
    textarea.dup-opts-read {width:100%; height:40px; font-size:12px}
    button.dpro-store-fixed-btn {min-width: 155px; text-align: center}
    div.success {color:#4A8254}
    div.failed {color:red}
    table.dpro-reset-opts td:first-child {font-weight: bold}
    table.dpro-reset-opts td {padding:4px}
    div#dpro-tools-delete-moreinfo {display: none; padding: 5px 0 0 20px; border:1px solid #dfdfdf;  border-radius: 5px; padding:10px; margin:5px; width:98% }
    div#dpro-tools-delete-orphans-moreinfo {display: none; padding: 5px 0 0 20px; border:1px solid #dfdfdf;  border-radius: 5px; padding:10px; margin:5px; width:98% }

    /*PHP_INFO*/
    div#dpro-phpinfo {padding:10px 5px;}
    div#dpro-phpinfo table {padding:1px; background:#dfdfdf; -webkit-border-radius:4px;-moz-border-radius:4px;border-radius:4px; width:100% !important; box-shadow:0 8px 6px -6px #777;}
    div#dpro-phpinfo td, th {padding:3px; background:#fff; -webkit-border-radius:2px;-moz-border-radius:2px;border-radius:2px;}
    div#dpro-phpinfo tr.h img {display:none;}
    div#dpro-phpinfo tr.h td {background:none;}
    div#dpro-phpinfo tr.h th {text-align:center; background-color:#efefef;}
    div#dpro-phpinfo td.e {font-weight:bold}
</style>



<?php
$section        = isset($_GET['section']) ? $_GET['section'] : 'diagnostic';
$txt_diagnostic = DUP_PRO_U::__("Information");
$txt_log        = DUP_PRO_U::__("Logs");
$txt_support    = DUP_PRO_U::__("Support");
$tools_url      = 'admin.php?page=duplicator-pro-tools&tab=diagnostics';

switch ($section) {
    case 'diagnostic':
        echo "<div class='dpro-sub-tabs'><b>{$txt_diagnostic}</b> &nbsp;|&nbsp; <a href='{$tools_url}&section=log'>{$txt_log}</a> &nbsp;|&nbsp; <a href='{$tools_url}&section=support'>{$txt_support}</a></div>";
        include(dirname(__FILE__) . '/diagnostic.php');
        break;
    case 'log':
        echo "<div class='dpro-sub-tabs'><a href='{$tools_url}&section=diagnostic'>{$txt_diagnostic}</a>  &nbsp;|&nbsp;<b>{$txt_log}</b>  &nbsp;|&nbsp; <a href='{$tools_url}&section=support'>{$txt_support}</a></div>";
        include(dirname(__FILE__) . '/log.php');
        break;
    case 'support':
        echo "<div class='dpro-sub-tabs'><a href='{$tools_url}&section=diagnostic'>{$txt_diagnostic}</a> &nbsp;|&nbsp; <a href='{$tools_url}&section=log'>{$txt_log}</a> &nbsp;|&nbsp; <b>{$txt_support}</b> </div>";
        include(dirname(__FILE__) . '/support.php');

        break;
}
?>
