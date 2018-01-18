<?php
/*
Plugin Name: WhatsApp Connect
Plugin URI: http://www.firecrown.io
Description: Makes your customers connections to you more friendly.
Version: 2.1.0
Author: FireCrown
Author URI: http://www.firecrown.io
*/

include_once ('other-functions.php'); //Get Admin Settings
include_once ('admin/admin.php'); //Get Admin Settings

class WhatsAppConnectWidget extends WP_Widget {

	// constructor
	function __construct() {
		$widget_options = array(
			'classname' => 'WhatsAppConnectWidget',
			'description' => 'WhatsApp Connect - Connect your customers to WhatsApp',
		);
		parent::__construct( 'whatsappconnect_wa', 'WhatsApp Connect', $widget_options );
	}


	// widget form creation
	function form($instance) {
		$message	= esc_attr($instance['gid']);
		$title	= esc_attr($instance['title']);
		$btnt	= esc_attr($instance['btnt']);
		$dtxt	= esc_attr($instance['dtxt']);
		?>
		<p>
			<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title'); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />
		</p>

		<p>
			<label for="<?php echo $this->get_field_id('gid'); ?>"><?php _e('Number'); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id('gid'); ?>" name="<?php echo $this->get_field_name('gid'); ?>" type="text" value="<?php echo $message; ?>" />
		</p>

		<p>
			<label for="<?php echo $this->get_field_id('btnt'); ?>"><?php _e('Button Text'); ?></label>
			<input placeholder="Example: Join Chat" class="widefat" id="<?php echo $this->get_field_id('btnt'); ?>" name="<?php echo $this->get_field_name('btnt'); ?>" type="text" value="<?php echo $btnt; ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('dtxt'); ?>"><?php _e('Default Text - Optional'); ?></label>
			<input placeholder="Example: Hey i need your service" class="widefat" id="<?php echo $this->get_field_id('dtxt'); ?>" name="<?php echo $this->get_field_name('dtxt'); ?>" type="text" value="<?php echo $dtxt; ?>" />
		</p>
		<?php
	}

	// widget update
	function update($new_instance, $old_instance) {
		$instance = $old_instance;
		$instance['gid'] = strip_tags($new_instance['gid']);
		$instance[ 'title' ] = $new_instance[ 'title' ];
		$instance[ 'btnt' ] = $new_instance[ 'btnt' ];
		$instance[ 'dtxt' ] = $new_instance[ 'dtxt' ];

		return $instance;

	}

	// widget display
	function widget($args, $instance) {
		wp_enqueue_style('waconnectcss',plugins_url ( 'waconnect.css', __FILE__ ));
		extract( $args );
		$title = apply_filters( 'widget_title', $instance[ 'title' ] );
		$gid = $instance[ 'gid' ];
		$btnt = $instance[ 'btnt' ];
		$dtxt = esc_html($instance[ 'dtxt' ]);
		$base_link = wa_build_link($gid,$dtxt);
		?>
		<div class="wa-join wa-center">
			<h4><?php echo $title; ?></h4>
			<a href="<?php echo $base_link ?>" title="Contact" class="wa-button"  target="_blank"><?php echo $btnt; ?></a>
		</div>
		<?php
	}


}

	function wac_load_plugin_css_old_wp() {
	    $plugin_url = plugin_dir_url( __FILE__ );
		wp_enqueue_style('waconnectcss',plugins_url ( 'waconnect.css', __FILE__ ));
	}

	add_action( 'wp_enqueue_scripts', 'wac_load_plugin_css_old_wp' );


function register_scwidget()
{
	return register_widget("WhatsAppConnectWidget");
}

add_action('widgets_init', 'register_scwidget');

function waconnect_func( $atts ) {
    $a = shortcode_atts( array(
        'btn' => 'Contact',
        'number' => null,
        'text' => null,
    ), $atts );

    $base_link = wa_build_link($a['number'],$a['text']);

    return '<a class="wa-button" href="'.$base_link.'" target="_blank">'.$a['btn'].'</a>';

}


add_shortcode( 'waconnect', 'waconnect_func' );

function wac_thebutton() {
	$type = get_option('wac_ff_type'); // Sticky or floating

	$number = get_option('wac_ff_number');
	$text = get_option('wac_ff_text');
	$label = get_option('wac_ff_label');

	$base_link = wa_build_link($number,$text);

	$location = "wac-". get_option('wac_ff_location');
	$output = '';
	if($type == "stickytext"){
		// echo '<a target="_blank" href="'.$base_link.'" class="wac-'.$type.' '.$location.'">'.$label.'<div class="wac-wa"></div></a>';

		$output .= '<div class="waconnect-links">';
		$output .= '<div class="waconnect-messenger"><a target="_blank" href="https://m.me/naishair.extensiones"><img src="https://www.naishair.com/wp-content/uploads/2017/08/facebook_messenger60x60.png" /></a></div>';
		$output .= '<div class="waconnect-whats-app"><a target="_blank" href="'.$base_link.'">'.$label.'<div class="wac-wa"></div></a></div>';
		$output .= '</div>';
		
	} else {
	    // echo '<a target="_blank" href="'.$base_link.'" class="wac-'.$type.' '.$location.'"><div class="wac-wa"></div></a>';

		$output .= '<div class="waconnect-links">';
		$output .= '<div class="waconnect-messenger"><a  target="_blank" href="https://m.me/naishair.extensiones"><img class="" src="https://www.naishair.com/wp-content/uploads/2017/08/facebook_messenger60x60.png" /></a></div>';
		$output .= '<div class="waconnect-whats-app"><a target="_blank" href="'.$base_link.'" class=""><div class="wac-wa"></div></a></div>';
		$output .= '</div>';

	}

	echo $output;
}

function wac_addpopup()
{
	include_once ('popup.php'); //Get Admin Settings
}

if(get_option('wac_enable_floating') == 1){
	add_action( 'wp_footer', 'wac_thebutton' );
}

if(get_option('wac_enable_popup') == 1){
	add_action( 'wp_footer', 'wac_addpopup' );
}
