	<style>
	.newoption{
		color:#76b300;
	}

	</style>

	<h1>WaConnect Settings</h1>

				<form method="post" action="options.php">
						<hr />

				<h2 class="title">General Settings</h2>
					<strong>Language</strong>
					<select name="wac_lang">
					<option value="en">Default</option>
						<option value="az"  <?php if ( get_option("wac_lang") == az ) echo "selected"; ?>>Azərbaycanca</option><option value="id"  <?php if ( get_option("wac_lang") == id ) echo "selected"; ?>>Bahasa Indonesia</option><option value="ms"  <?php if ( get_option("wac_lang") == ms ) echo "selected"; ?>>Bahasa Melayu</option><option value="ca"  <?php if ( get_option("wac_lang") == ca ) echo "selected"; ?>>Català</option><option value="cs"  <?php if ( get_option("wac_lang") == cs ) echo "selected"; ?>>Česky</option><option value="da"  <?php if ( get_option("wac_lang") == da ) echo "selected"; ?>>Dansk</option><option value="de"  <?php if ( get_option("wac_lang") == de ) echo "selected"; ?>>Deutsch</option><option value="et"  <?php if ( get_option("wac_lang") == et ) echo "selected"; ?>>Eesti</option><option value="en"  <?php if ( get_option("wac_lang") == en ) echo "selected"; ?>>English</option><option value="es"  <?php if ( get_option("wac_lang") == es ) echo "selected"; ?>>Español</option><option value="fr"  <?php if ( get_option("wac_lang") == fr ) echo "selected"; ?>>Français</option><option value="hr"  <?php if ( get_option("wac_lang") == hr ) echo "selected"; ?>>Hrvatski</option><option value="it"  <?php if ( get_option("wac_lang") == it ) echo "selected"; ?>>Italiano</option><option value="sw"  <?php if ( get_option("wac_lang") == sw ) echo "selected"; ?>>Kiswahili</option><option value="lv"  <?php if ( get_option("wac_lang") == lv ) echo "selected"; ?>>Latviešu</option><option value="lt"  <?php if ( get_option("wac_lang") == lt ) echo "selected"; ?>>Lietuviškai</option><option value="hu"  <?php if ( get_option("wac_lang") == hu ) echo "selected"; ?>>Magyar</option><option value="nl"  <?php if ( get_option("wac_lang") == nl ) echo "selected"; ?>>Nederlands</option><option value="nb"  <?php if ( get_option("wac_lang") == nb ) echo "selected"; ?>>Norsk</option><option value="uz"  <?php if ( get_option("wac_lang") == uz ) echo "selected"; ?>>Oʻzbekcha</option><option value="fil"  <?php if ( get_option("wac_lang") == fil ) echo "selected"; ?>>Pilipino</option><option value="pl"  <?php if ( get_option("wac_lang") == pl ) echo "selected"; ?>>Polski</option><option value="pt_br"  <?php if ( get_option("wac_lang") == pt_br ) echo "selected"; ?>>Português (BR)</option><option value="pt_pt"  <?php if ( get_option("wac_lang") == pt_pt ) echo "selected"; ?>>Português (PT)</option><option value="ro"  <?php if ( get_option("wac_lang") == ro ) echo "selected"; ?>>Română</option><option value="sq"  <?php if ( get_option("wac_lang") == sq ) echo "selected"; ?>>Shqip</option><option value="sk"  <?php if ( get_option("wac_lang") == sk ) echo "selected"; ?>>Slovenčina</option><option value="sl"  <?php if ( get_option("wac_lang") == sl ) echo "selected"; ?>>Slovenščina</option><option value="fi"  <?php if ( get_option("wac_lang") == fi ) echo "selected"; ?>>Suomi</option><option value="sv"  <?php if ( get_option("wac_lang") == sv ) echo "selected"; ?>>Svensk</option><option value="vi"  <?php if ( get_option("wac_lang") == vi ) echo "selected"; ?>>Tiếng Việt</option><option value="tr"  <?php if ( get_option("wac_lang") == tr ) echo "selected"; ?>>Türkçe</option><option value="el"  <?php if ( get_option("wac_lang") == el ) echo "selected"; ?>>Ελληνικά</option><option value="bg"  <?php if ( get_option("wac_lang") == bg ) echo "selected"; ?>>Български</option><option value="kk"  <?php if ( get_option("wac_lang") == kk ) echo "selected"; ?>>Қазақша</option><option value="mk"  <?php if ( get_option("wac_lang") == mk ) echo "selected"; ?>>Македонски</option><option value="ru"  <?php if ( get_option("wac_lang") == ru ) echo "selected"; ?>>Pусский</option><option value="sr"  <?php if ( get_option("wac_lang") == sr ) echo "selected"; ?>>Српски</option><option value="uk"  <?php if ( get_option("wac_lang") == uk ) echo "selected"; ?>>Українська</option><option value="he"  <?php if ( get_option("wac_lang") == he ) echo "selected"; ?>>‏עברית‏</option><option value="ar"  <?php if ( get_option("wac_lang") == ar ) echo "selected"; ?>>العربية</option><option value="fa"  <?php if ( get_option("wac_lang") == fa ) echo "selected"; ?>>فارسی</option><option value="ur"  <?php if ( get_option("wac_lang") == ur ) echo "selected"; ?>>اردو</option><option value="bn"  <?php if ( get_option("wac_lang") == bn ) echo "selected"; ?>>বাংলা </option><option value="hi"  <?php if ( get_option("wac_lang") == hi ) echo "selected"; ?>>हिंदी</option><option value="gu"  <?php if ( get_option("wac_lang") == gu ) echo "selected"; ?>>ગુજરાતી</option><option value="kn"  <?php if ( get_option("wac_lang") == kn ) echo "selected"; ?>>ಕನ್ನಡ</option><option value="mr"  <?php if ( get_option("wac_lang") == mr ) echo "selected"; ?>>मराठी</option><option value="ta"  <?php if ( get_option("wac_lang") == ta ) echo "selected"; ?>>தமிழ்</option><option value="te"  <?php if ( get_option("wac_lang") == te ) echo "selected"; ?>>తెలుగు</option><option value="ml"  <?php if ( get_option("wac_lang") == ml ) echo "selected"; ?>>മലയാളം</option><option value="th"  <?php if ( get_option("wac_lang") == th ) echo "selected"; ?>>ภาษาไทย</option><option value="zh_cn"  <?php if ( get_option("wac_lang") == zh_cn ) echo "selected"; ?>>简体中文</option><option value="zh_tw"  <?php if ( get_option("wac_lang") == zh_tw ) echo "selected"; ?>>繁體中文</option><option value="ja"  <?php if ( get_option("wac_lang") == ja ) echo "selected"; ?>>日本語</option><option value="ko"  <?php if ( get_option("wac_lang") == ko ) echo "selected"; ?>>한국어</option>
					</select>
				<hr />
				<h2 class="title">Popup Settings</h2>
				<p>Popup will be displayed once every 24 hours to each visitor (uses cookies)</p>
					<?php wp_nonce_field('update-options'); ?>
					<?php settings_fields('waconnect');?>

					<br><label><input class="wac-input" type="checkbox" name="wac_enable_popup" value="1"
						<?php if ( get_option('wac_enable_popup') == 1 ) echo 'checked="checked"'; ?> /> Popup is enabled </label> <br>

					<br /><p><strong>Popup message</strong></p>
						<textarea rows="4" cols="50" name="wac_pp_msg"><?php echo get_option('wac_pp_msg'); ?></textarea><br/>

					<br /><p><strong>Number ( Optional ) </strong></p>
						<input type="text" class="regular-text"  name="wac_pp_number" value="<?php echo get_option('wac_pp_number'); ?>" />
						<p class="description">Without + Sign or leading 00, But with country code. ( E.g: 97333001234 ) </p>

					<br /><p><strong>Message  ( Optional ) </strong></p>
						<input type="text" class="regular-text" name="wac_pp_text" value="<?php echo get_option('wac_pp_text'); ?>" /><br/>
						<p class="description">Message that you want to be sent once a user clicks the button</p>
					<br /><p><strong>Button text </strong></p>
						<input type="text" class="regular-text" name="wac_pp_btn" value="<?php echo get_option('wac_pp_btn'); ?>" /><br/>
						<p class="description">Button label</p>
						<hr />

				<h2 class="title">Floating/sticky button</h2>
					<br><label><input class="wac-input" type="checkbox" name="wac_enable_floating" value="1"
						<?php if ( get_option('wac_enable_floating') == 1 ) echo 'checked="checked"'; ?> /> Floating button is enabled </label> <br>

					<br /><p><strong>Type</strong></p>
						<select name="wac_ff_type" id="floatingtype">
							<option value="sticky" <?php if ( get_option('wac_ff_type') == 'sticky' ) echo 'selected'; ?> >Sticky</option>
							<option value="floating"  <?php if ( get_option('wac_ff_type') == 'floating' ) echo 'selected'; ?>>Floating</option>
							<option value="stickytext"  <?php if ( get_option('wac_ff_type') == 'stickytext' ) echo 'selected'; ?>>Sticky with Text</option>
						</select>
						<div id="stickylabel" >
						<br/>
						<br/>
						<strong class='newoption'>Sticky button label</strong><br/>
						<input type="text" class="regular-text" name="wac_ff_label" value="<?php echo get_option('wac_ff_label') ?>">
						</div>
						<br/>

						<div id="walocation">
					<br /><p><strong>Location</strong></p>
						<select name="wac_ff_location">
							<option value="leftm" <?php if ( get_option('wac_ff_location') == 'leftm' ) echo 'selected'; ?> >Left middle</option>
							<option value="rightm"  <?php if ( get_option('wac_ff_location') == 'rightm' ) echo 'selected'; ?>>Right middle</option>
							<option value="bottomr"  <?php if ( get_option('wac_ff_location') == 'bottomr' ) echo 'selected'; ?>>Bottom right</option>
							<option value="bottoml"  <?php if ( get_option('wac_ff_location') == 'bottoml' ) echo 'selected'; ?>>Bottom left</option>
							<option value="topl"  <?php if ( get_option('wac_ff_location') == 'topl' ) echo 'selected'; ?>>Top left</option>
							<option value="topr"  <?php if ( get_option('wac_ff_location') == 'topr' ) echo 'selected'; ?>>Top right</option>
						</select>
						<br/>
						</div>

					<br /><p><strong>Number ( Optional ) </strong></p>
						<input type="text" class="regular-text"  name="wac_ff_number" value="<?php echo get_option('wac_ff_number'); ?>" />
						<p class="description">Without + Sign or leading 00, But with country code. ( E.g: 97333001234 ) </p>

					<br /><p><strong>Message  ( Optional ) </strong></p>
						<input type="text" class="regular-text" name="wac_ff_text" value="<?php echo get_option('wac_ff_text'); ?>" /><br/>
						<p class="description">Message that you want to be sent once a user clicks the button</p>
						<hr />
				<h2 class="title">API</h2>
						<select name="wac_api">
							<option value="api" <?php if ( get_option('wac_api') == 'api' ) echo 'selected'; ?> >HTTP/HTTPS API</option>
							<option value="scheme"  <?php if ( get_option('wac_api') == 'scheme' ) echo 'selected'; ?>>URL Scheme</option>
							<option value="web"  <?php if ( get_option('wac_api') == 'web' ) echo 'selected'; ?>>Web (Bypass)</option>
						</select>
					<p class="description">Wether to use link or URL scheme, <strong>Don't change</strong> this value unless you know what you're doing</p>
						<hr />


					<?php wp_nonce_field('update-options'); ?>
					<?php settings_fields('waconnect');?>
					<div id="email">
						<p class="submit"><input type="submit" class="button button-primary" value="<?php _e('Save Changes') ?>" /></p>
					</div>
			</form>
	<script type="text/javascript">
		jQuery(document).on("ready",function(){

		jQuery('#floatingtype').change(function(){
		    if( jQuery(this).val() != 'stickytext'){
		        jQuery('#stickylabel').hide();
		        jQuery('#walocation').show();
		    }else{
		        jQuery('#stickylabel').show();
		        jQuery('#walocation').hide();
		    }
		});

		jQuery("#floatingtype").trigger("change");


		});

	</script>