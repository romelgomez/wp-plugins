<?php
add_thickbox();
$staging_options_wptc = new Staging_Options_Wptc();
class Staging_Options_Wptc{

  public function __construct() {
    $this->init();
  }

  public function init(){
    $this->get_status();
  }

  private function get_status(){
    $this->print_title();
  }

  private function print_title(){
    echo "<h1 >
            <a style='display:none'  class='button' id='wptc_staging_submit'>Test staging</a>
          </h1>
          <h2 id='staging_area_wptc'>Staging Area</h2>
          <div id='staging_current_progress' style='display:none'>Checking status...</div>
          <div id='wptc-content-id' style='display:none;'>
            <p> This is my hidden content! It will appear in ThickBox when the link is clicked. </p>
          </div>
          <a style='display:none' href='#TB_inline?width=600&height=550&inlineId=wptc-content-id' class='thickbox wptc-thickbox'>View my inline content!</a>";
  }
}