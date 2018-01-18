<?php
function motopressCEupdatePalettes() {
    require_once dirname(__FILE__).'/../verifyNonce.php';
    require_once dirname(__FILE__).'/../settings.php';
    require_once dirname(__FILE__).'/../access.php';
    require_once dirname(__FILE__).'/../functions.php';

    if ( isset( $_POST['palettes'] ) && !empty( $_POST['palettes'] ) ){
        $palettes = $_POST['palettes'];
        update_option('motopress-palettes', $palettes);
        wp_send_json_success(array('palettes' => $palettes));
    } else {
        motopressCESetError(__("Error while getting the palettes", 'motopress-content-editor-lite'));
    }

    exit;
}