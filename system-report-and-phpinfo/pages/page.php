<?php

abstract class PeepSoDeveloperToolsPage
{
    public $title = NULL;

    public $file_extension = 'txt';
    public $file_mime = 'text/plain';

    public $menu_slug = 'SLUG';

    public $description = 'Amazing Features Here';

    public function page_start($export_key = '')
    {
        $PeepSoDeveloperTools = PeepSoDeveloperTools::get_instance();

        wp_enqueue_style('peepsodebug_common', $PeepSoDeveloperTools::assets_path().'css/common.css');
        ?>
        <h2><?php echo __('Developer Tools by PeepSo','peepsodebug'); ?></h2>
        <div class="wrap peepsodebug-wrap">
        <h3 class="nav-tab-wrapper wp-clearfix">
            <?php
            foreach($PeepSoDeveloperTools->pages_config as $page) {
                $active = ('peepsodebug_' . $page == $_GET['page']) ? 'nav-tab-active' :'';
                printf('<a href="%s" class="nav-tab %s">%s</a>', menu_page_url( 'peepsodebug_'.$page, FALSE ), $active, $PeepSoDeveloperTools->pages[$page]->title);
            }
            ?>
        </h3>

        <?php
        if($export_key) {
            // Export Button
            $PeepSoDeveloperTools_buttons = array();
            ob_start();
            ?>
            <form action="<?php echo esc_url($_SERVER['REQUEST_URI']); ?>" method="post">
                <input type="hidden" name="export_content" value="<?php echo $export_key; ?>">
                <input type="hidden" name="system_report_export" value="1">
                <input type="submit" value="<?php echo __('&darr; Export', 'wordpress-system-report'); ?>"
                       class="button button-primary">
            </form>
            <?php
            $buttons['export'] = ob_get_clean();

            // Reload button
            ob_start();
            ?>
            <form action="<?php echo esc_url($_SERVER['REQUEST_URI']); ?>" method="post">
                <input type="submit" value="<?php echo __('&#8634; Refresh', 'wordpress-system-report'); ?>"
                       class="button button-secondary">
            </form>
            <?php
            $buttons['reload'] = ob_get_clean();

            $buttons = apply_filters('peepsodebug_buttons', $buttons);


            if (count($buttons)) {
                printf('<div class="peepsodebug-action-buttons">%s</div>', implode(' ', $buttons));
            }
        }
    }

    public function page_end()
    {
        ?>
        </div>
        <?php
    }

    public static function peepsodebug_buttons($buttons){
        return $buttons;
    }

    abstract public function page();

    abstract public function page_data();
}