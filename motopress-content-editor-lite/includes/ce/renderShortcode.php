<?php
function motopressCERenderShortcode() {
    require_once dirname(__FILE__).'/../verifyNonce.php';
    require_once dirname(__FILE__).'/../settings.php';
    require_once dirname(__FILE__).'/../access.php';
    require_once dirname(__FILE__).'/../functions.php';
    require_once dirname(__FILE__).'/shortcode/ShortcodeCommon.php';

    $errorMessage = sprintf(__("An error occurred while rendering %s", 'motopress-content-editor-lite'), __("shortcode", 'motopress-content-editor-lite'));

    if (
        isset($_POST['closeType']) && !empty($_POST['closeType']) &&
        isset($_POST['shortcode']) && !empty($_POST['shortcode'])
    ) {
        global $motopressCESettings;
        $errors = array();

        $closeType = $_POST['closeType'];
        $shortcode = $_POST['shortcode'];
        $parameters = null;
        if (isset($_POST['parameters']) && !empty($_POST['parameters'])) {
            $parameters = json_decode(stripslashes($_POST['parameters']));
            if (!$parameters) {
                $errors[] = $errorMessage;
            }
        }
        $styles = null;
        if (isset($_POST['styles']) && !empty($_POST['styles'])) {
            $styles = json_decode(stripslashes($_POST['styles']));
            if (!$styles) {
                $errors[] = $errorMessage;
            }
        }

        if (empty($errors)) {
            $motopressCELibrary = MPCELibrary::getInstance();
            do_action('motopress_render_shortcode', $shortcode); //for motopress-cherryframework plugin

            $content = null;
            if (isset($_POST['content']) && !empty($_POST['content'])) {
                $content = stripslashes($_POST['content']);
                $content = MPCEShortcode::unautopBuilderShortcodes($content);
                if (isset($_POST['wrapRender']) && $_POST['wrapRender'] === 'true') {
	                require_once $motopressCESettings['plugin_dir_path'] . 'includes/ce/renderContent.php';

//                    $content = motopressCECleanupShortcode($content);
                    $content = motopressCEParseObjectsRecursive($content);
                }
            }
                                   
            $str = MPCEShortcode::toShortcode($closeType, $shortcode, $parameters, $styles, $content);
			$shortcodeWrapper = '<div class="motopress-ce-rendered-content-wrapper">' . $str . '</div>';
			
            $result = apply_filters('the_content', $shortcodeWrapper);
            $result = MPCEContentManager::getInstance()->filterContent($result);

			$result .= '<div class="motopress-ce-private-styles-updates-wrapper">' . MPCECustomStyleManager::getInstance()->getPrivateStylesTag(true) . '</div>';
			echo $result;
        } else {
            if ($motopressCESettings['debug']) {
                print_r($errors);
            } else {
                motopressCESetError($errorMessage);
            }
        }
    } else {
        motopressCESetError($errorMessage);
    }
    exit;
}