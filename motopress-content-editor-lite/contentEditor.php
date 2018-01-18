<?php
function motopressCEAddTools() {
    global $isMotoPressCEPage;
    $isMotoPressCEPage = true;

    $motopressCELibrary = MPCELibrary::getInstance();

    $gridObjects = $motopressCELibrary->getGridObjects();
    $renderedShortcodes = array(
        'grid' => array(),
        'empty' => array()
    );

    // Rendered Grid Objects
    foreach(array($gridObjects['row']['shortcode'], $gridObjects['row']['inner'], $gridObjects['span']['shortcode'], $gridObjects['span']['inner']) as $shortcodeName) {
        $shortcode = generateShortcodeFromLibrary($shortcodeName);
        $renderedShortcodes['grid'][$shortcodeName] = do_shortcode($shortcode);
    }

    // Rendered Empty Spans
    foreach(array($gridObjects['span']['shortcode'], $gridObjects['span']['inner']) as $shortcodeName){
        $shortcode = generateShortcodeFromLibrary($shortcodeName, array('motopress-empty', 'mp-hidden-phone'));
        $renderedShortcodes['empty'][$shortcodeName] = do_shortcode($shortcode);
    }

    if (MPCEContentManager::isEditorAvailableForPost()) {
    	global $motopressCESettings;

    	$postID = get_the_ID();
		$postEnabled = MPCEContentManager::isPostEnabledForEditor($postID);
		$scriptSuffix = $motopressCESettings['script_suffix'];

		add_action('admin_head', 'motopressCEAddCEBtn');

    	wp_register_style('mpce-style',$motopressCESettings['plugin_dir_url'] . 'includes/css/style' . $scriptSuffix . '.css', null, $motopressCESettings['plugin_version']);
        wp_enqueue_style('mpce-style');

        if (!$postEnabled) return;

	    require_once $motopressCESettings['plugin_dir_path'] . 'includes/ce/ThemeFix.php';
	    $themeFix = new MPCEThemeFix(MPCEThemeFix::DEACTIVATE);

        wp_localize_script('jquery', 'motopress', $motopressCESettings['motopress_localize']);
        wp_localize_script('jquery', 'motopressCE',
            array(
                'postID' => $postID,
//                'postPreviewUrl' => post_preview(),
                'nonces' => array(
                    'motopress_ce_get_wp_settings' => wp_create_nonce('wp_ajax_motopress_ce_get_wp_settings'),
//                    'motopress_ce_render_content' => wp_create_nonce('wp_ajax_motopress_ce_render_content'),
//                    'motopress_ce_remove_temporary_post' => wp_create_nonce('wp_ajax_motopress_ce_remove_temporary_post'),
//                    'motopress_ce_get_library' => wp_create_nonce('wp_ajax_motopress_ce_get_library'),
                    'motopress_ce_render_shortcode' => wp_create_nonce('wp_ajax_motopress_ce_render_shortcode'),
                    'motopress_ce_render_template' => wp_create_nonce('wp_ajax_motopress_ce_render_template'),
					'motopress_ce_render_shortcodes_string' => wp_create_nonce('wp_ajax_motopress_ce_render_shortcodes_string'),
                    'motopress_ce_get_attachment_thumbnail' => wp_create_nonce('wp_ajax_motopress_ce_get_attachment_thumbnail'),
                    'motopress_ce_colorpicker_update_palettes' => wp_create_nonce('wp_ajax_motopress_ce_colorpicker_update_palettes'),
                    'motopress_ce_render_youtube_bg' => wp_create_nonce('wp_ajax_motopress_ce_render_youtube_bg'),
                    'motopress_ce_render_video_bg' => wp_create_nonce('wp_ajax_motopress_ce_render_video_bg'),
                    'motopress_ce_get_translations' => wp_create_nonce('wp_ajax_motopress_ce_get_translations'),
                ),
                'settings' => array(
                    'wp' => $motopressCESettings,
                    'library' => $motopressCELibrary->getData(),
                    'translations' => motopressCEGetLangStrings()
                ),
                'rendered_shortcodes' => $renderedShortcodes,
		        'info' => array(
					'is_headway_themes' => $themeFix->isHeadwayTheme()
		        ),
				'styleEditor' => MPCECustomStyleManager::getLocalizeJSData()
            )
        );

        add_action('admin_footer', 'motopressCEHTML'); //admin_head

        motopressCECheckDomainMapping();

        wp_register_style('mpce', $motopressCESettings['plugin_dir_url'] . 'mp/ce/css/ce' . $scriptSuffix . '.css', null, $motopressCESettings['plugin_version']);
        wp_enqueue_style('mpce');

        $customPreloaderImageSrc = apply_filters('mpce_preloader_src', false);
        if ($customPreloaderImageSrc) {
            echo '<style type="text/css">#motopress-preload{background-image: url("' . esc_url($customPreloaderImageSrc) . '") !important;}</style>';
        }

	    // TODO: Maybe load async
        wp_register_script('mpce-knob', $motopressCESettings['plugin_dir_url'] . 'knob/jquery.knob.min.js', array(), $motopressCESettings['plugin_version'], true);
        wp_enqueue_script('mpce-knob');

        if (get_user_meta(get_current_user_id(), 'rich_editing', true) === 'false' && !wp_script_is('editor')) {
            wp_enqueue_script('editor');
        }

        wp_enqueue_script('wp-link');
    }
}

 function motopressCEGetLangStrings() {
	global $motopressCESettings;

	return include($motopressCESettings['plugin_dir_path'] . 'includes/jsTranslations.php');
}

function generateShortcodeFromLibrary($shortcodeName, $customClasses = array()) {
    $motopressCELibrary = MPCELibrary::getInstance();
    $shortcodeObject = $motopressCELibrary->getObject($shortcodeName);
    $gridObjects = $motopressCELibrary->getGridObjects();
    $shortcode = '[' . $shortcodeName;
    foreach($shortcodeObject->getParameters() as $parameterName => $parameter) {
        if (isset($parameter['default']) && $parameter['default'] !== '') {
            $shortcode .= ' ' . $parameterName . '="' . $parameter['default'] . '"';
        }
    }
    $shortcodeStyles = $shortcodeObject->getStyles();
    $styleClassesArr = isset($shortcodeStyles['default']) && !empty($shortcodeStyles['default']) ? array_merge($customClasses, $shortcodeStyles['default']) : $customClasses;
    if (!empty($styleClassesArr)) {
        $shortcode .= ' mp_style_classes="';
        $shortcode .= implode(' ', $styleClassesArr);
        $shortcode .= '"';
    }

    // Add column width parameter
//    if (in_array($shortcodeName, array($gridObjects['span']['shortcode'], $gridObjects['span']['inner']))) {
//        $shortcode .= ' ' . $gridObjects['span']['attr'] . '="' . $gridObjects['row']['col'] . '"';
//    }

    $shortcode .= ']<div class="motopress-filler-content"></div>[/' . $shortcodeName . ']';
    return $shortcode;
}

function motopressCECheckDomainMapping() {
    global $wpdb;

    if (is_multisite()) {
	    $wmudmActive = is_plugin_active('wordpress-mu-domain-mapping/domain_mapping.php');
        if ($wmudmActive) {
            $blogDetails = get_blog_details();
            $mappedDomains = $wpdb->get_col(sprintf("SELECT domain FROM %s WHERE blog_id = %d ORDER BY id ASC", $wpdb->dmtable, $blogDetails->blog_id));
            if (!empty($mappedDomains)) {
                if (!in_array(parse_url($blogDetails->siteurl, PHP_URL_HOST), $mappedDomains)) {
                    add_action('admin_notices', 'motopressCEDomainMappingNotice');
                }
            }
        }
    }
}

function motopressCEDomainMappingNotice() {
    $linkDomainMapping = apply_filters('mpce_link_domain_mapping', 'https://motopress.zendesk.com/hc/en-us/articles/200884839-WordPress-Multisite-domain-mapping-configuration');
    echo '<div class="error"><p>' . sprintf(__("Use the mapped domain to access the admin area. Find out <a href='%s' target='_blank'>here</a> how to setup it", 'motopress-content-editor-lite'), esc_url($linkDomainMapping)) . '</p></div>';
}

function motopressCEHTML() {
	if (!user_can_richedit()) return false;

	global $post, $motopressCESettings, $pagenow;

	$proBtnTitle = __("Duplicate Object", 'motopress-content-editor-lite');
	$liteBtnTitle = __("This feature is only available in Pro version", 'motopress-content-editor-lite');

	$duplicateBtnTitle = $liteBtnTitle;
	$duplicateBtnAttrs = 'disabled="disabled"';
?>
    <div id="motopress-content-editor" style="display: none;">
        <div class="motopress-content-editor-navbar">
            <div class="navbar-inner">
                <div id="motopress-logo">
                    <?php $logoSrc = apply_filters('mpce_logo_src', $motopressCESettings['plugin_dir_url'] . 'images/logo.png?ver='.$motopressCESettings['plugin_version']);?>
                    <img src="<?php echo esc_url($logoSrc); ?>">
                </div>
                <div class="motopress-page-name">
                    <span id="motopress-post-type"><?php echo get_post_type() == 'page' ? __("Page", 'motopress-content-editor-lite') : __("Post", 'motopress-content-editor-lite'); ?></span>:
                    <span id="motopress-title"></span>
                    <input type="text" id="motopress-input-edit-title" class="motopress-hide" >
                </div>
	            <div class="pull-left motopress-control-btns motopress-leftbar-control-btns">
                    <button class="motopress-btn-red" id="mpce-add-widget" title="<?php echo __("+ Add", 'motopress-content-editor-lite'); ?>"><?php echo __("+ Add", 'motopress-content-editor-lite'); ?></button>
                </div>
                <div class="pull-left motopress-control-btns motopress-object-control-btns">
                    <button class="motopress-btn-default" id="motopress-content-editor-duplicate" title="<?php echo $duplicateBtnTitle; ?>" <?php echo $duplicateBtnAttrs; ?>><div class="motopress-content-editor-duplicate-icon"></div></button>
	                <button class="motopress-btn-default" id="motopress-content-editor-delete" title="<?php echo __("Delete Object", 'motopress-content-editor-lite'); ?>"><div class="motopress-content-editor-delete-icon"></div></button>
                </div>
                <div class="pull-right navbar-btns">					
                    <button class="motopress-btn-blue<?php if ($post->post_status === 'publish') echo ' motopress-ajax-update'; ?>" id="motopress-content-editor-publish"><?php echo __("Publish", 'motopress-content-editor-lite'); ?></button>
                    <button class="motopress-btn-default<?php if ($pagenow !== 'post-new.php') echo ' motopress-ajax-update'; ?>" id="motopress-content-editor-save"><?php echo __("Save", 'motopress-content-editor-lite'); ?></button>
					<button class="motopress-btn-default" id="motopress-content-editor-preview"><?php echo __("Preview", 'motopress-content-editor-lite'); ?></button>
					<button class="motopress-btn-default" id="motopress-content-editor-device-mode-preview" title="<?php echo __("Responsive Preview", 'motopress-content-editor-lite'); ?>"><div></div></button>
                    <?php $isHideTutorials = apply_filters('mpce_hide_tutorials', false);
                    if (!$isHideTutorials) {
                        echo '<button class="motopress-btn-default btn-tutorials" id="motopress-content-editor-tutorials">' . _x("?", "Help" ,'motopress-content-editor-lite') . '</button>';
                    }
                    ?>
                    <button class="motopress-btn-default" id="motopress-content-editor-close" title="<?php echo __("Close", 'motopress-content-editor-lite'); ?>"><div></div></button>
                    <?php
                    $upgradeBtnTitle = __("Upgrade", 'motopress-content-editor-lite');
                    ?>
                    <button class="motopress-btn-red" id="motopress-content-editor-upgrade" onclick="window.open('<?php echo $motopressCESettings['lite_upgrade_url'] ?>','_blank')"><?php echo $upgradeBtnTitle; ?></button>
                    <?php ?>
                </div>
            </div>
        </div>
		<div id="motopress-content-editor-preview-device-panel" class="motopress-hide">
			<div>
				<div class="motopress-content-editor-preview-mode-btn motopress-content-editor-preview-desktop" data-mode="desktop"></div>
			</div>
<!--			<div>
				<div class="motopress-content-editor-preview-mode-btn motopress-content-editor-preview-tablet-landscape" data-mode="tablet-landscape"></div>
			</div>-->
			<div>
				<div class="motopress-content-editor-preview-mode-btn motopress-content-editor-preview-tablet" data-mode="tablet"></div>
			</div>
			<div>
				<div class="motopress-content-editor-preview-mode-btn motopress-content-editor-preview-phone" data-mode="phone"></div>
			</div>
<!--			<div>
				<div class="motopress-content-editor-preview-mode-btn motopress-content-editor-preview-phone-landscape" data-mode="phone-landscape"></div>
			</div>-->
			<div>
				<div class="motopress-content-editor-preview-edit"></div>
			</div>
		</div>

        <div id="motopress-flash"></div>

        <div id="motopress-content-editor-scene-wrapper">
	        <?php
	        $iframeSrc = get_permalink($post->ID);

	        //@todo: fix protocol for http://codex.wordpress.org/Administration_Over_SSL
	        //fix different site (WordPress Address) and home (Site Address) url for iframe security
	        $siteUrl = get_site_url();
	        $homeUrl = get_home_url();
	        $siteUrlArr = parse_url($siteUrl);
	        $homeUrlArr = parse_url($homeUrl);
	        if ($homeUrlArr['scheme'] !== $siteUrlArr['scheme'] || $homeUrlArr['host'] !== $siteUrlArr['host']) {
		        $iframeSrc = str_replace($homeUrl, $siteUrl, $iframeSrc);
	        }

	        // Fix for Domain Mapping plugin (separate frontend and backend domains)
	        if (is_plugin_active('domain-mapping/domain-mapping.php')) {
		        $iframeSrc = add_query_arg('dm', 'bypass', $iframeSrc);
	        }

	        $iframeSrc = add_query_arg(array('mpce-post-id' => $post->ID), $iframeSrc);
	        $iframeSrc = add_query_arg(array('motopress-ce' => '1'), $iframeSrc);
	        $iframeSrc = wp_nonce_url($iframeSrc, 'mpce-edit-post_' . $post->ID);
	        ?>
	        <form id="mpce-form" action="<?php echo $iframeSrc; ?>" method="POST" target="motopress-content-editor-scene">
		        <div class="mpce-form-fields">
			        <input type="hidden" name="mpce_title" />
			        <textarea name="mpce_editable_content"></textarea>
			        <input type="hidden" name="mpce-post-id" value="<?php echo $post->ID; ?>" />
			        <input type="hidden" name="mpce_page_template" />
		        </div>
	        </form>
        </div>

        <!-- Video Tutorials -->
        <div id="motopress-tutorials-modal" class="motopress-modal modal motopress-soft-hide fade">
            <div class="modal-header">
                <p id="tutsModalLabel" class="modal-header-label"><?php printf(__("%s Help & Tutorials", 'motopress-content-editor-lite'), $motopressCESettings['brand_name']); ?><button type="button" tabindex="0" class="close massive-modal-close" data-dismiss="modal" aria-hidden="true">&times;</button></p>
            </div>
            <div class="modal-body"></div>
        </div>

        <!-- Code editor -->        
        <div id="motopress-code-editor-modal" class="motopress-modal modal motopress-soft-hide fade" role="dialog" aria-labelledby="codeModalLabel" aria-hidden="true">
            <div class="modal-header">
                <p id="codeModalLabel" class="modal-header-label"><?php echo __("Edit", 'motopress-content-editor-lite') . ' ' . __("WordPress Text", 'motopress-content-editor-lite'); ?></p>
            </div>
            <div class="modal-body">
                <div id="motopress-code-editor-wrapper">
                    <?php
                        wp_editor('', 'motopresscodecontent', array(
                            'textarea_rows' => false,
                            'tinymce' => array(
                                'remove_linebreaks' => false,
                                'schema' => 'html5',
                                'theme_advanced_resizing' => false
                            )
                        ));
                    ?>
                </div>
            </div>
            <div class="modal-footer">
                <button id="motopress-save-code-content" class="motopress-btn-blue"><?php echo __("Save", 'motopress-content-editor-lite'); ?></button>
                <button class="motopress-btn-default" data-dismiss="modal" aria-hidden="true"><?php echo __("Close", 'motopress-content-editor-lite'); ?></button>
            </div>
        </div>

		
		
    </div>

    <div id="motopress-preload">
        <input type="text" id="motopress-knob">

        <div id="motopress-error">
            <div id="motopress-error-title"><?php echo __("An internal error occurred during loading Visual Builder. Please submit this data to support team.", 'motopress-content-editor-lite'); ?></div>
            <div id="motopress-error-message">
                <div id="motopress-system">
                    <p id="motopress-browser"></p>
                    <p id="motopress-platform"></p>
                </div>
            </div>
            <div class="motopress-terminate">
                <button id="motopress-terminate" class="motopress-btn-default"><?php echo __("Terminate & Close", 'motopress-content-editor-lite'); ?></button>
            </div>
        </div>
        <script type="text/javascript">
	        // Test
//            window.MP.loadJqDynamically = false;
            window.MP.Error = {
                terminate: function() {
                    jQuery('html').css({
                        overflow: '',
                        paddingTop: 32
                    });
                    jQuery('body > #wpadminbar').prependTo('#wpwrap > #wpcontent');
                    //jQuery('#wpwrap').show();
                    var mpce = jQuery('#motopress-content-editor');
                    mpce.siblings('.motopress-hide').removeClass('motopress-hide');
                    //jQuery('#wpwrap').css('height', '');
                    jQuery('#wpwrap').height('');
                    //jQuery('#wpwrap').children(':not(#wpcontent)').removeClass('motopress-wpwrap-hidden');
                    //jQuery('#wpwrap > #wpcontent').children(':not(#wpadminbar)').removeClass('motopress-wpwrap-hidden');
                    var preload = jQuery('#motopress-preload');
                    preload.hide();
                    var error = preload.children('#motopress-error');
                    error.find('#motopress-system').prevAll().remove();
                    error.hide();
                    mpce.hide();
                    jQuery(window).trigger('resize'); //fix tinymce toolbar (wp v4.0)
                },
                log: function(e, isMainEditor) {
	                isMainEditor = isMainEditor !== undefined && isMainEditor;

	                if (MP.Settings.debug) {

		                console.log(e);

	                } else {

		                console.group('CE error');
		                console.warn('Name: ' + e.name);
		                console.warn('Message: ' + e.message);
		                if (e.hasOwnProperty('fileName')) console.warn('File: ' + e.fileName);
		                if (e.hasOwnProperty('lineNumber')) console.warn('Line: ' + e.lineNumber);
		                console.warn('Browser: ' + navigator.userAgent);
		                console.warn('Platform: ' + navigator.platform);
		                console.groupEnd();

	                }

                    var error = jQuery('#motopress-preload > #motopress-error');
                    var text = e.name + ': ' + e.message + '.';
                    if (e.hasOwnProperty('fileName')) {
                        text += ' ' + e.fileName;
                    }
                    if (e.hasOwnProperty('lineNumber')) {
                        text += ':' + e.lineNumber;
                    }
                    error.find('#motopress-system').before(jQuery('<p />', {text: text}));
                    error.show();

	                if (isMainEditor) {
		                jQuery('#motopress-preload').stop().show();
	                }
                }
            };

            jQuery(document).ready(function($) {
                $('#motopress-knob').knob({
                    readOnly: true,
                    displayInput: false,
                    thickness: 0.05,
                    fgColor: '#d34937',
                    width: 136,
                    height: 136
                });

                $('#motopress-system')
                    .children('#motopress-browser').text('Browser: ' + navigator.userAgent)
                    .end()
                    .children('#motopress-platform').text('Platform: ' + navigator.platform);

                $('#motopress-terminate').on('click', function() {
                    MP.Error.terminate();
                });
            });
        </script>
    </div>

<?php

}

function motopressCEAddArea() {
	global $post;

	$postID = $post->ID;
	$postActive = MPCEContentManager::isPostEnabledForEditor($postID);
	$builderActive = MPCEContentManager::isEditorAvailableForPost($postID);

	if ($postActive && $builderActive) {
		$content = MPCEContentManager::getEditorContent($postID);
		$content = $content ? $content : '';

		$editorHeight = (int)get_user_setting('ed_size');
		$editorHeight = $editorHeight ? $editorHeight : 300;
		?>
		<div id="motopress-ce-tinymce-wrap">
			<?php wp_editor($content, MPCEContentManager::CONTENT_EDITOR_ID, array('editor_height' => $editorHeight)); ?>
		</div>
		<?php
	}
}
add_action('edit_form_after_editor', 'motopressCEAddArea');

function motopressCEAddCEBtn() {
    global $post, $motopressCESettings, $motopressCEIsjQueryVer, $wp_version;

	$postID = get_the_ID();
	$mpceEnabled = MPCEContentManager::isPostEnabledForEditor($postID);
	$post_status = get_post_status($postID);
	$userCanRichedit = user_can_richedit();

	$scriptSuffix = $motopressCESettings['script_suffix'];
	$pluginDirUrl = $motopressCESettings['plugin_dir_url'];
    ?>
    <script type="text/javascript">
    (function($) {
	    /* --- Define Scopes --- */
        window.MP = {
	        Loader: (function() {
		        var callbacks = [], map = {};
		        return {
			        add: function(name, callback) {
				        callbacks.push(callback);
				        map[name] = callbacks.length - 1;
			        },
			        execSingle: function(name) {
				        if (map[name] !== undefined) {
					        callbacks[map[name]]();
				        }
			        },
			        execAll: function() {
				        var cbLen = callbacks.length;
				        for (var i = 0; i < cbLen; i++) {
					        if (typeof callbacks[i] === 'function') {
						        callbacks[i]();
					        }
				        }
			        }
		        }
	        })()
        };
        window.CE = {};

        /* --- Vars --- */
	    var mpceEditorId = 'motopresscecontent',
		    wpEditorId = 'content',
		    mpceCodeEditorId = 'motopresscodecontent';

	    <?php if ($mpceEnabled) { ?>

		/**
	     * This func is in tinyMCE.mpceEditor scope
	     * @returns {boolean}
	     */
		window.mpceIsDirtyDecorator = function() {
	        return MP.WpRevision.isDirty();
        };

	    window.MP.WpRevision = new (function WpRevision() {
		    var self = this,
				dirty = false,
				wpEditor = null, mpceEditor = null;

		    // --- Public ---
		    this.init = function() {
			    wpEditor = tinyMCE.get('content');
	            mpceEditor = tinyMCE.get('motopresscecontent');

			    bindDecorator();
		    };

	        /**
		     * If builder's isDirty == TRUE then use it, else use tinyMCE's isDirty
		     * @returns {boolean}
		     */
	        this.isDirty = function() {
			    var isDirty = this.isBuilderDirty();
			    // if (!MP.Editor.myThis.isOpen()) {
			    if (!isDirty) isDirty = mpceEditor.old_isDirty();
			    if (!isDirty) isDirty = wpEditor.old_isDirty();
			    // }

			    return isDirty;
	        };

	        this.isBuilderDirty = function() {
		        return dirty;
	        };

	        this.setBuilderDirty = function(value) {
		        dirty = value;
	        };

	        this.makeWpNonDirty = function() {
		        // It will reset `tinyMCE Dirty flag`
		        tinyMCE.triggerSave();
	        };
	        // --- End Public ---

		    // --- Private ---
		    function bindDecorator() {
			    var hasDirtyFunc = (mpceEditor && typeof mpceEditor.isDirty !== 'undefined');

	            if (hasDirtyFunc) {
		            // Focus builder's editor
	                mpceEditor.focus();

		            // Store original func
		            wpEditor.old_isDirty = wpEditor.isDirty;
	                mpceEditor.old_isDirty = mpceEditor.isDirty;

		            // Bind decorated func
		            wpEditor.isDirty = mpceIsDirtyDecorator;
		            mpceEditor.isDirty = mpceIsDirtyDecorator;
	                tinyMCE.activeEditor.isDirty = mpceIsDirtyDecorator;
	            }
	        }
	        // --- End Private ---
	    });
	    <?php } ?>
	    /* --- End Define Scopes --- */

	    $(document).ready(function() {
		    /* --- Tabs --- */
		    // --- Vars ---
		    var
			    $title = $('#title'),
			    $defaultTab = $('#mpce-tab-default'),
			    $editorTab = $('#mpce-tab-editor'),
			    activeTabClass = 'nav-tab-active',
			    $mpceArea = $('#motopress-ce-tinymce-wrap'),
			    $postEditorArea = $('#postdivrich'),
			    $areaSet = $mpceArea.add($postEditorArea),
			    $mpceTabs = $('.mpce-tab');

		    var preloader = $('#motopress-preload');
		    // --- End Vars ---

		    var insertStatusField = function(status) {
			    $('.mpce-hidden-fields').empty()
				    .append(
					    $('<input />', {
						    type: 'hidden',
						    name: 'mpce-status',
						    value: status ? 'enabled' : 'disabled'
					    })
				    );
		    };

		    var switchWithStatus = function(status) {
			    $(window).off('beforeunload');
//			    preloader.show();
			    insertStatusField(status);
			    $('form#post').submit();
		    };

		    var setDefaultTitle = function() {
			    var noTitle = '<?php echo "Post #{$postID}"; ?>';
			    if (!noTitle) noTitle = '<?php echo __("no title", 'motopress-content-editor-lite'); ?>';
			    $title.val(noTitle);
			    $('form[name="post"] #title-prompt-text').addClass('screen-reader-text');
		    };

		    var wpTabCallback = function() {
			    if ($(this).hasClass(activeTabClass)) return;

			    //if (confirm('Are you sure?')) {
			    $(this).off('click', wpTabCallback);
			    switchWithStatus(false);
			    //}
		    };

		    var mpceTabCallback = function() {
			    if ($(this).hasClass(activeTabClass)) return;
			    $(this).off('click', mpceTabCallback);

			    <?php if ($post_status == 'auto-draft') { ?>
			    if ($title.length && !$.trim($title.val()).length) {
					setDefaultTitle();
			    }
			    <?php } ?>

//			    sessionStorage.setItem('motopressPluginAutoOpen', true);
			    switchWithStatus(true);
		    };

		    // --- Bind Events ---
		    !$defaultTab.hasClass(activeTabClass) && $defaultTab.on('click', wpTabCallback);
		    !$editorTab.hasClass(activeTabClass) && $editorTab.on('click', mpceTabCallback);
		    // --- End Bind Events ---

		    /* --- End Tabs --- */

    <?php if ($mpceEnabled) { ?>

		    // Vars
		    var supportedBrowser = !MPCEBrowser.IE && !MPCEBrowser.Opera;
		    var motopressCEButton = $('#motopress-ce-btn');

		    // Init
		    if (supportedBrowser) {
			    motopressCEButton.show();
		    } else {
			    motopressCEButton.remove();
		    }

		    if (supportedBrowser) {
	            var
		            $form = $('#mpce-form'),
		            draftSaved = false,
		            tinymceDefined = (typeof tinyMCE !== 'undefined'),
		            userCanRichedit = <?php echo (int) $userCanRichedit; ?>,
		            pluginAutoOpen = false;

	            var
	                editorIds = [wpEditorId, mpceCodeEditorId, mpceEditorId],
		            tinyMCEInitedDefers = {};

	            motopressCE.tinyMCEInited = {};
	            motopressCE.tinyMCEInitedArray = [];

	            editorIds.forEach(function(id) {
		            var defer = $.Deferred();
		            var promise = defer.promise();

		            tinyMCEInitedDefers[id] = defer;
					motopressCE.tinyMCEInited[id] = promise;
					motopressCE.tinyMCEInitedArray.push(promise);
	            });


	            // --- Load Top Editor scripts ---
	            // Load CanJS
	            var canjsStatus = $.ajax({
		            url: '<?php echo $pluginDirUrl . 'vendors/canjs/can.custom.min.js?ver=' . $motopressCESettings['canjs_version']; ?>',
		            dataType: 'script',
		            cache: true,
//		            success: function(script, textStatus) {},
		            error: function(xhr, textStatus, error) {
			            MP.Error.log(error);
		            }
	            });

	            // Load Top Editor
	            var parentEditorStatus = $.ajax({
		            url: '<?php echo $pluginDirUrl . 'mp/ce/editor' . $scriptSuffix . '.js?ver=' . $motopressCESettings['plugin_version']; ?>',
		            dataType: 'script',
		            cache: true,
		            error: function(xhr, textStatus, error) {
			            MP.Error.log(error);
		            }
	            });

	            // Load Bootstrap JS
	            var bootstrapStatus = $.ajax({
		            url: '<?php echo $pluginDirUrl . 'bootstrap/bootstrap2-custom.min.js'; ?>',
		            dataType: 'script',
		            cache: true,
		            error: function(xhr, textStatus, error) {
						MP.Error.log(error);
		            }
	            });

	            // Load Bootstrap CSS
	            var head = $('head')[0];
                var bootstrapCSS = $('<link />', {
	                rel: 'stylesheet',
                    href: '<?php echo $pluginDirUrl; ?>' + 'bootstrap/bootstrap-icon.min.css'
                })[0];
                head.appendChild(bootstrapCSS);
	            // --- End Load Top Editor scripts ---

                <?php if (extension_loaded('mbstring')) { ?>
                    <?php if ($motopressCEIsjQueryVer) { ?>
                        motopressCEButton.on('click', function() {

	                    <?php if ($userCanRichedit) { ?>

	                        if (!tinymceDefined) {
		                        alert('<?php echo __("WordPress visual editor is required", 'motopress-content-editor-lite'); ?>');
		                        return;
	                        }

	                        $('#motopress-content-editor-scene').remove();
	                        $form.after('<iframe id="motopress-content-editor-scene" class="motopress-content-editor-scene" name="motopress-content-editor-scene" style="min-width: 100% !important;"></iframe>');

                            //console.time('ce');
                            //console.profile();

                            preloader.show();

	                        // This code block unused since v2.2.0
	                        // ... `post_status` can't be `auto-draft` because we can open builder only after saving draft (after switch to builder tab).
	                        if (!draftSaved) {
	                        <?php if ($post_status == 'auto-draft') { ?>
		                        <?php if (version_compare($wp_version, '3.6', '<')) { ?>
		                        var editor = tinymceDefined && tinymce.get(wpEditorId);
		                        if (editor && !editor.isHidden()) {
			                        editor.save();
		                        }
		                        var postData = {
			                        post_title: $title.val() || '',
			                        content: $('#content').val() || '',
			                        excerpt: $('#excerpt').val() || ''
		                        };
		                        <?php } else { ?>
		                        var postData = wp.autosave.getPostData();
		                        <?php } ?>

		                        // Wrong content condition. Since v2.2.0 content placed in another field.
		                        if (!postData.content.length && !postData.excerpt.length && !$.trim(postData.post_title).length) {
			                        setDefaultTitle();
			                        draftSaved = true;
		                        }
	                        <?php } ?>
	                        }
//                            sessionStorage.setItem('motopressPluginAutoSaved', false);

	                        if ($.isEmptyObject(CE)) {
	                            // Run Top Editor script on load
					            parentEditorStatus.done(function() {
						            window.MP.Loader.execSingle('parent_editor');
					            });

                            } else {
								MP.Editor.myThis.open();
                            }

                        <?php } else { ?>
	                        alert('<?php echo __("\"Disable the visual editor when writing option\" should be disabled in Your Profile settings to start page builder", 'motopress-content-editor-lite'); ?>');
                        <?php } ?>
                        });

					<?php if ($userCanRichedit) { ?>

                        function mpceOnEditorInit() {
	                        // Wait for Top Editor scripts
	                        $.when.apply($, [canjsStatus, bootstrapStatus, parentEditorStatus]).done(function() {
		                        motopressCEButton.removeAttr('disabled');
		                        if (pluginAutoOpen) {
			                        motopressCEButton.click();
		                        }
	                        });
                        }

			            if (tinymceDefined) {

                        	<?php
					            //fix different site (WordPress Address) and home (Site Address) url for sessionStorage
					            $paramPluginAutoOpen = 'false';
					            if (isset($_GET['motopress-ce-auto-open']) && $_GET['motopress-ce-auto-open'] === 'true') {
									$paramPluginAutoOpen = 'true';
					            }
				            ?>

				            var editorState = "<?php echo wp_default_editor(); ?>";
				            var paramPluginAutoOpen = <?php echo $paramPluginAutoOpen; ?>;
				            pluginAutoOpen = sessionStorage.getItem('motopressPluginAutoOpen');
	                        pluginAutoOpen = !!((pluginAutoOpen && pluginAutoOpen === 'true') || paramPluginAutoOpen);
				            pluginAutoOpen = pluginAutoOpen && motopressCEButton.length > 0;

	                        if (pluginAutoOpen) preloader.show();

				            function resolveMPTinyMCEEditors(editorID, editor) {
					            if (tinyMCEInitedDefers.hasOwnProperty(editorID)) {
						            tinyMCEInitedDefers[editorID].resolve(editor);
					            }
				            }

							if (tinyMCE.majorVersion === '4') {
								tinyMCE.on('AddEditor', function(args) {
									args.editor.on('init', function(ed) {
										resolveMPTinyMCEEditors(args.editor.id, args.editor);
									});
								});
							} else {
								tinyMCE.onAddEditor.add(function(mce, ed) {
									ed.onInit.add(function(ed) {
										resolveMPTinyMCEEditors(ed.editorId, ed);
									});
								});
							}

				            if (editorState === 'tinymce') {
					            $.when.apply($, motopressCE.tinyMCEInitedArray).done(function() {
						            mpceOnEditorInit();
						            var focusT = setTimeout(function() {
							            tinyMCE.get(mpceEditorId).focus();
							            window.MP.WpRevision.init();
							            clearTimeout(focusT);
						            }, 0);
								});
	                        }
	                        // TODO: Now the tinyMCE state is always `tinymce` and this condition no longer needed
	                        else {
					            mpceOnEditorInit();
	                        }
	                    }

		            <?php } ?>

		            sessionStorage.setItem('motopressPluginAutoOpen', false);

                    <?php } else {
                        add_action('admin_notices', 'motopressCEIsjQueryVerNotice');
                    } // endif jquery version check
                } else {
                    add_action('admin_notices', 'motopressCEIsMBStringEnabledNotice');
                } ?>
            }
	    <?php } ?>
	    });
    })(jQuery);
    </script>
    <?php $isHideNativeEditor = apply_filters('mpce_hide_native_editor', false); ?>
	<style type="text/css"><?php
	    ($isHideNativeEditor || $mpceEnabled) && print('#postdivrich{display: none;}');
	    ($isHideNativeEditor) && print('#motopress-ce-tinymce-wrap{display: none;}');
	    ($isHideNativeEditor && $mpceEnabled) && print('#mpce-tab-default{display: none;}');
	?></style><?php
}

function motopressCEIsjQueryVerNotice() {
    echo '<div class="error"><p>' . sprintf(__("Minimum jQuery version - %s. Minimum jQuery UI version - %s. Please update WordPress to 3.5 or higher.", 'motopress-content-editor-lite'), MPCERequirements::MIN_JQUERY_VER, MPCERequirements::MIN_JQUERYUI_VER) . '</p></div>';
}

function motopressCEIsMBStringEnabledNotice() {
    global $motopressCESettings;
    echo '<div class="error"><p>' . sprintf(__("%s error: contact your hosting provider to enable mbstring extension.", 'motopress-content-editor-lite'), $motopressCESettings['brand_name']) . '</p></div>';
}

require_once $motopressCESettings['plugin_dir_path'] . 'includes/getWpSettings.php';
add_action('wp_ajax_motopress_ce_get_wp_settings', 'motopressCEGetWpSettings');
if (!isset($motopressCERequirements)) $motopressCERequirements = new MPCERequirements();
require_once $motopressCESettings['plugin_dir_path'] . 'includes/ce/renderShortcode.php';
add_action('wp_ajax_motopress_ce_render_shortcode', 'motopressCERenderShortcode');
require_once $motopressCESettings['plugin_dir_path'] . 'includes/ce/renderTemplate.php';
add_action('wp_ajax_motopress_ce_render_template', 'motopressCERenderTemplate');
require_once $motopressCESettings['plugin_dir_path'] . 'includes/ce/renderShortcodesString.php';
add_action('wp_ajax_motopress_ce_render_shortcodes_string', 'motopressCERenderShortcodeString');
require_once $motopressCESettings['plugin_dir_path'] . 'includes/ce/getAttachmentThumbnail.php';
add_action('wp_ajax_motopress_ce_get_attachment_thumbnail', 'motopressCEGetAttachmentThumbnail');
require_once $motopressCESettings['plugin_dir_path'] . 'includes/ce/updatePalettes.php';
add_action('wp_ajax_motopress_ce_colorpicker_update_palettes', 'motopressCEupdatePalettes');
add_action('wp_ajax_motopress_ce_render_youtube_bg', array('MPCEShortcode', 'renderYoutubeBackgroundVideo'));
add_action('wp_ajax_motopress_ce_render_video_bg', array('MPCEShortcode', 'renderHTML5BackgroundVideo'));