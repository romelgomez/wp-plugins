<?php
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
	global $motopressCESettings;
    require_once $motopressCESettings['plugin_dir_path'] . 'includes/ce/Access.php';
    $ceAccess = new MPCEAccess();
    $access = $ceAccess->hasAccess($_POST['postID']);

    if (!$access) {
        require_once $motopressCESettings['plugin_dir_path'] . 'includes/functions.php';
        motopressCESetError(__("Maybe you are not logged in or you have no permission", 'motopress-content-editor-lite'));
    }
}