<?php
/** @var array $config */
/** @var PagespeedNinja_Admin $this */
?>
<div class="psnwrap">
    <div id="pagespeedninja">
        <div class="headerbar">
            <div class="logo"></div>
        </div>
    </div>
</div>

<div id="pagespeedninja_afterinstall_popup" style="display:none">
    <form action="options.php" method="post" id="pagespeedninja-popup-form">
        <?php settings_fields('pagespeedninja_config'); ?>
        <?php do_settings_sections('pagespeedninja_config'); ?>
        <?php
            $config = get_option('pagespeedninja_config');
            $config['afterinstall_popup'] = '1';
            $this->hidden($config, 'afterinstall_popup');
        ?>
        <label title="Allow use of pagespeed.ninja's above-the-fold generation tools.">
            <input type="hidden" name="pagespeedninja_config[allow_ext_atfcss]" value="0" />
            <input type="checkbox" name="pagespeedninja_config[allow_ext_atfcss]" value="1" checked />
            Use pagespeed.ninja critical CSS service
        </label>
        <label title="Allow send anonymous data about PageSpeed Ninja usage to pagespeed.ninja.">
            <input type="hidden" name="pagespeedninja_config[allow_ext_stats]" value="0" />
            <input type="checkbox" name="pagespeedninja_config[allow_ext_stats]" value="1" checked />
            Send anonymous statistics
        </label>
        <label title="Displays a small text link to the PageSpeed Ninja website in the footer ('Optimized with PageSpeed Ninja').">
            <input type="hidden" name="pagespeedninja_config[footer]" value="0" />
            <input type="checkbox" name="pagespeedninja_config[footer]" value="1" />
            Support badge in the footer
        </label>
        <p>These settings may be changed further in the Advanced settings of PageSpeed Ninja plugin.</p>
        <input type="submit" value="Save" />
    </form>
</div>

<script>
    jQuery(function () {
        setTimeout(function () {
            window.tb_remove = function () {
                return false;
            };
            tb_show('', '"#TB_inline?width=500&height=270&inlineId=pagespeedninja_afterinstall_popup');
        }, 0);
    });
</script>