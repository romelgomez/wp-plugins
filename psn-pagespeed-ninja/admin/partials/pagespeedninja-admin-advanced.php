<?php
/** @var array $config */
/** @var PagespeedNinja_Admin $this */

ob_start();
include dirname(dirname(dirname(__FILE__))) . '/includes/options.json.php';
$options = ob_get_clean();
$options = json_decode($options);
/** @var array $options */

$presets = array(
    'safe' => array(),
    'optimal' => array(),
    'ultra' => array(),
    'experimental' => array()
);

foreach ($options as $section) {
    if (isset($section->items)) {
        /** @var array {$section->items} */
        foreach ($section->items as $item) {
            if (isset($item->presets)) {
                $presets['safe'][] = "'" . $item->name . "':" . (is_string($item->presets[0]) ? "'{$item->presets[0]}'" : $item->presets[0]);
                $presets['optimal'][] = "'" . $item->name . "':" . (is_string($item->presets[1]) ? "'{$item->presets[1]}'" : $item->presets[1]);
                $presets['ultra'][] = "'" . $item->name . "':" . (is_string($item->presets[2]) ? "'{$item->presets[2]}'" : $item->presets[2]);
                $presets['experimental'][] = "'" . $item->name . "':" . (is_string($item->presets[3]) ? "'{$item->presets[3]}'" : $item->presets[3]);
            }
        }
    }
}

foreach ($presets as $preset => &$values) {
    $values = "'$preset':{" . implode(',', $values) . '}';
}
unset($values);
echo "<script>\nvar pagespeedninja_presets={\n" . implode(",\n", $presets) . "};\n</script>";

?>
<div class="psnwrap">
    <form action="options.php" method="post" id="pagespeedninja_form">
        <?php settings_fields('pagespeedninja_config'); ?>
        <?php do_settings_sections('pagespeedninja_config'); ?>
        <?php $config = get_option('pagespeedninja_config'); ?>

        <div id="pagespeedninja">
            <div class="headerbar">
                <a href="#" class="save" title="<?php esc_attr_e('Save changes'); ?>"><?php _e('Save'); ?></a>
                <div class="logo"></div>
            </div>
            <div class="tabs">
                <a href="#" class="basic"><?php _e('General'); ?></a>
                <a href="#" class="active advanced"><?php _e('Advanced'); ?></a>
            </div>

            <div class="main column-wide">

                <div class="presets">
                    <h3><?php _e('Presets'); ?></h3>
                    <label><input type="radio" name="preset" onclick="pagespeedninjaLoadPreset('safe')"> <?php _e('Safe'); ?></label>
                    <label><input type="radio" name="preset" onclick="pagespeedninjaLoadPreset('optimal')"> <?php _e('Optimal'); ?></label>
                    <label><input type="radio" name="preset" onclick="pagespeedninjaLoadPreset('ultra')"> <?php _e('Ultra'); ?></label>
                    <label><input type="radio" name="preset" onclick="pagespeedninjaLoadPreset('experimental')"> <?php _e('Experimental'); ?></label>
                    <label><input type="radio" name="preset" onclick="pagespeedninjaLoadPreset('')" checked> <?php _e('Custom'); ?></label>
                </div>

                <?php
                $first = true;
                /** @var stdClass $section */
                /** @var array {$section->items} */
                foreach ($options as $section) : ?>
                    <div<?php echo isset($section->id) ? ' id="psi_' . $section->id . '"' : ''; ?>>
                        <div class="header">
                            <div class="expando<?php echo $first ? ' open' : ''; ?>"></div>
                            <div class="title"><?php echo $section->title; ?></div>
                            <?php
                            if (isset($section->id)) {
                                $this->render('checkbox', 'psi_' . $section->id, $config);
                            }
                            ?>
                        </div>
                        <div class="content<?php echo $first ? ' show' : ''; ?>">
                            <?php $first = false; ?>
                            <?php if (!isset($section->items) || count($section->items) === 0) : ?>
                                <div class="line todo"><?php _e('Will be implemented further.'); ?></div>
                            <?php else : ?>
                                <?php foreach ($section->items as $item) :
                                    if ($item->type === 'hidden') {
                                        continue;
                                    }
                                    ?>
                                    <div class="line"><?php
                                    $this->title($item->title, isset($item->tooltip) ? $item->tooltip : '');
                                    $this->render($item->type, $item->name, $config, $item);
                                    ?></div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

    </form>
</div>