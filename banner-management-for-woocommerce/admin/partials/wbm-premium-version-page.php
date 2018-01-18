<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

require_once('header/plugin-header.php');
?>
    <div class="wbm-section-left">
        <div class="wbm-main-table res-cl">
            <div class="wbm-premium-features">
                <div class="section section-odd clear">
                    <h1><?php _e('Premium Features', WOO_BANNER_MANAGEMENT_TEXT_DOMAIN);?></h1>
                    <div class="landing-container pro-master-settings">
                        <div class="quickfeature">
                            <img src="<?php echo WBM_PLUGIN_URL . 'admin/images/features_04.png'; ?>" alt="<?php _e('Add Banner/Slider on Shop,Cart,Checkout,Thank You Page', WOO_BANNER_MANAGEMENT_TEXT_DOMAIN);?>" />
                            <img src="<?php echo WBM_PLUGIN_URL . 'admin/images/features_05.png'; ?>" alt="<?php _e('Add Banner/Slider on Shop,Cart,Checkout,Thank You Page', WOO_BANNER_MANAGEMENT_TEXT_DOMAIN);?>" />
                        </div>
                        <div class="col-2">
                            <div class="section-title">
                                <h2><?php _e('Allows you to manage page and category wise banners in your WooCommerce store.', WOO_BANNER_MANAGEMENT_TEXT_DOMAIN);?></h2>
                            </div>
                            <ul>
                                <li><b><?php _e('Add Banner/Slider on Shop,Cart,Checkout,Thank You Page:', WOO_BANNER_MANAGEMENT_TEXT_DOMAIN) ?></b> <?php _e('You can easily add banner in WooCommerce Shop,Cart,Checkout and Thank You Page.', WOO_BANNER_MANAGEMENT_TEXT_DOMAIN);?></li>
                                <li><b><?php _e('Add Banner/Slider on Category Page :', WOO_BANNER_MANAGEMENT_TEXT_DOMAIN) ?></b> <?php _e('You can easily add banner in WooCommerce Category Page.', WOO_BANNER_MANAGEMENT_TEXT_DOMAIN);?></li>
                            </ul>
                        </div>
                        <div class="col-1">
                            <img src="<?php echo WBM_PLUGIN_URL . 'admin/images/features.png'; ?>" alt="<?php _e('Add Banner/Slider on Shop,Cart,Checkout,Thank You Page', WOO_BANNER_MANAGEMENT_TEXT_DOMAIN);?>" />
                        </div>
                    </div>
                </div>
                <div class="section section-even clear">
                    <div class="landing-container">
                        <div class="col-1">
                            <img src="<?php echo WBM_PLUGIN_URL . 'admin/images/features_01.png'; ?>" alt="<?php _e('Shipping method Based On Country, State and Zipcode', WOO_BANNER_MANAGEMENT_TEXT_DOMAIN);?>" />
                        </div>
                        <div class="col-2">
                            <div class="section-title">
                                <h2><?php _e('Manage Banner Specific banner ', WOO_BANNER_MANAGEMENT_TEXT_DOMAIN);?></h2>
                            </div>
                            <p><?php _e('Add single Banner for Shop Page, Cart Page, Checkout Page, Thank you and category page', WOO_BANNER_MANAGEMENT_TEXT_DOMAIN);?></p>
                        </div>
                    </div>
                </div>
                <div class="section section-odd clear">
                    <div class="landing-container">
                        <div class="col-2">
                            <div class="section-title">
                                <h2><?php _e('Random or Multiple Banner Slider ', WOO_BANNER_MANAGEMENT_TEXT_DOMAIN);?></h2>
                            </div>
                            <p><?php _e('Add random/slider banner for Shop Page, Cart Page, Checkout Page, Thank you and category page.', WOO_BANNER_MANAGEMENT_TEXT_DOMAIN);?></p>
                        </div>
                        <div class="col-1">
                            <img src="<?php echo WBM_PLUGIN_URL . 'admin/images/features_02.png'; ?>" alt="<?php _e('Shipping method Based On Custom Zone', WOO_BANNER_MANAGEMENT_TEXT_DOMAIN);?>" />
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
<?php require_once('header/plugin-sidebar.php'); ?>