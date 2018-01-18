<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
$image_url = WBM_PLUGIN_URL . 'admin/images/right_click.png';
?>
<div class="dotstore_plugin_sidebar">

    <div class="dotstore_discount_voucher">
        <span class="dotstore_discount_title"><?php _e('Discount Voucher', WOO_BANNER_MANAGEMENT_TEXT_DOMAIN); ?></span>
        <span class="dotstore-upgrade"><?php _e('Upgrade to premium now and get', WOO_BANNER_MANAGEMENT_TEXT_DOMAIN); ?></span>
        <strong class="dotstore-OFF"><?php _e('10% OFF', WOO_BANNER_MANAGEMENT_TEXT_DOMAIN); ?></strong>
        <span class="dotstore-with-code"><?php _e('with code', WOO_BANNER_MANAGEMENT_TEXT_DOMAIN); ?><b><?php _e('FLAT10', WOO_BANNER_MANAGEMENT_TEXT_DOMAIN); ?></b></span>
        <a class="dotstore-upgrade" href="<?php echo esc_url('store.multidots.com/woocommerce-category-banner-management'); ?>" target="_blank"><?php _e('Upgrade Now!', WOO_BANNER_MANAGEMENT_TEXT_DOMAIN); ?></a>
    </div>

    <div class="dotstore-important-link">
        <div class="video-detail important-link">
            <a href="https://www.youtube.com/watch?v=rTL2pyH16Eo" target="_blank">
                <img width="100%" src="<?php echo WBM_PLUGIN_URL . 'admin/images/plugin-videodemo.png'; ?>" alt="Advanced Flat Rate Shipping For WooCommerce">
            </a>
        </div>
    </div>

    <div class="dotstore-important-link">
        <h2><span class="dotstore-important-link-title"><?php _e('Important link', WOO_BANNER_MANAGEMENT_TEXT_DOMAIN); ?></span></h2>
        <div class="video-detail important-link">
            <ul>
                <li>
                    <img src="<?php echo $image_url; ?>">
                    <a target="_blank" href="<?php echo esc_url('https://store.multidots.com/wp-content/uploads/2017/02/Banner-Management-for-WooCommerce-help-document-.pdf'); ?>"><?php _e('Plugin documentation', WOO_BANNER_MANAGEMENT_TEXT_DOMAIN); ?></a>
                </li>
                <li>
                    <img src="<?php echo $image_url; ?>">
                    <a target="_blank" href="<?php echo esc_url('store.multidots.com/dotstore-support-panel'); ?>"><?php _e('Support platform', WOO_BANNER_MANAGEMENT_TEXT_DOMAIN); ?></a>
                </li>
                <li>
                    <img src="<?php echo $image_url; ?>">
                    <a target="_blank" href="<?php echo esc_url('store.multidots.com/suggest-a-feature'); ?>"><?php _e('Suggest A Feature', WOO_BANNER_MANAGEMENT_TEXT_DOMAIN); ?></a>
                </li>
                <li>
                    <img src="<?php echo $image_url; ?>">
                    <a  target="_blank" href="<?php echo esc_url('wordpress.org/plugins/banner-management-for-woocommerce/#developers'); ?>"><?php _e('Changelog', WOO_BANNER_MANAGEMENT_TEXT_DOMAIN); ?></a>
                </li>
            </ul>
        </div>
    </div>

    <div class="dotstore-important-link">
        <h2><span class="dotstore-important-link-title"><?php _e('OUR POPULAR PLUGINS', WOO_BANNER_MANAGEMENT_TEXT_DOMAIN); ?></span></h2>
        <div class="video-detail important-link">
            <ul>
                <li>
                    <img class="sidebar_plugin_icone" src="<?php echo WBM_PLUGIN_URL . 'admin/images/advance-flat-rate2.png'; ?>">
                    <a target="_blank" href="<?php echo esc_url('store.multidots.com/advanced-flat-rate-shipping-method-for-woocommerce'); ?>"><?php _e('Advanced Flat Rate Shipping Method', WOO_BANNER_MANAGEMENT_TEXT_DOMAIN); ?></a>
                </li>
                <li>
                    <img class="sidebar_plugin_icone" src="<?php echo WBM_PLUGIN_URL . 'admin/images/wc-conditional-product-fees.png'; ?>">
                    <a  target="_blank" href="<?php echo esc_url('store.multidots.com/woocommerce-conditional-product-fees-checkout'); ?>"><?php _e('WooCommerce Conditional Product Fees', WOO_BANNER_MANAGEMENT_TEXT_DOMAIN); ?></a>
                </li>
                <li>
                    <img class="sidebar_plugin_icone" src="<?php echo WBM_PLUGIN_URL . 'admin/images/advance-menu-manager.png'; ?>">
                    <a  target="_blank" href="<?php echo esc_url('store.multidots.com/advance-menu-manager-wordpress'); ?>"><?php _e('Advance Menu Manager', WOO_BANNER_MANAGEMENT_TEXT_DOMAIN); ?></a>
                </li>
                <li>
                    <img class="sidebar_plugin_icone" src="<?php echo WBM_PLUGIN_URL . 'admin/images/wc-enhanced-ecommerce-analytics-integration.png'; ?>">
                    <a target="_blank" href="<?php echo esc_url('store.multidots.com/woocommerce-enhanced-ecommerce-analytics-integration-with-conversion-tracking'); ?>"><?php _e('Woo Enhanced Ecommerce Analytics Integration', WOO_BANNER_MANAGEMENT_TEXT_DOMAIN); ?></a>
                </li>
                <li>
                    <img  class="sidebar_plugin_icone" src="<?php echo WBM_PLUGIN_URL . 'admin/images/advanced-product-size-charts.png'; ?>">
                    <a target="_blank" href="<?php echo esc_url('store.multidots.com/woocommerce-advanced-product-size-charts'); ?>"><?php _e('Advanced Product Size Charts', WOO_BANNER_MANAGEMENT_TEXT_DOMAIN); ?></a>
                </li>
                <li>
                    <img  class="sidebar_plugin_icone" src="<?php echo WBM_PLUGIN_URL . 'admin/images/wc-blocker-prevent-fake-orders'; ?>">
                    <a target="_blank" href="<?php echo esc_url('store.multidots.com/woocommerce-blocker-prevent-fake-orders-blacklist-fraud-customers'); ?>"><?php _e('WooCommerce Blocker – Prevent Fake Orders', WOO_BANNER_MANAGEMENT_TEXT_DOMAIN); ?></a>
                </li>
            </ul>
        </div>
        <div class="view-button">
            <a class="view_button_dotstore" target="_blank" href="<?php echo esc_url('store.multidots.com/plugins'); ?>store.multidots.com/plugins"><?php _e('VIEW ALL', WOO_BANNER_MANAGEMENT_TEXT_DOMAIN); ?></a>
        </div>
    </div>

</div>
</div>
</div>