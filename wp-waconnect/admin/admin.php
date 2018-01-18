<?php

//Settings
function init_wac() {
  register_setting('waconnect', 'wac_enable_popup');
  register_setting('waconnect', 'wac_pp_msg');
  register_setting('waconnect', 'wac_pp_number');
  register_setting('waconnect', 'wac_pp_text');
  register_setting('waconnect', 'wac_pp_btn');
  register_setting('waconnect', 'wac_enable_floating');
  register_setting('waconnect', 'wac_ff_type');
  register_setting('waconnect', 'wac_ff_location');
  register_setting('waconnect', 'wac_ff_number');
  register_setting('waconnect', 'wac_ff_text');
  register_setting('waconnect', 'wac_ff_label');
  register_setting('waconnect', 'wac_api');
  register_setting('waconnect', 'wac_lang');
}

//Options
function init_wac_menu() {
  add_options_page(
    'WaConnect',
    'WaConnect Settings',
    'manage_options',
    'waconnect',
    'wac_options_page');
}

function wac_options_page() {
  include( 'wac_options.php' );
}
if (is_admin()) {
  add_action('admin_init', 'init_wac');
  add_action('admin_menu', 'init_wac_menu');
}

?>