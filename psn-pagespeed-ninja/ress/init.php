<?php
/*
 * RESSIO Responsive Server Side Optimizer
 * https://github.com/ressio/
 *
 * @copyright   Copyright (C) 2013-2018 Kuneri, Ltd. All rights reserved.
 * @license     GNU General Public License version 2
 */

if (defined('RESSIO_OB_START')) {
    return;
}

require_once dirname(__FILE__) . '/ressio.php';

define('RESSIO_OB_START', true);

ob_start();
register_shutdown_function('Ressio_ob_callback');

function Ressio_ob_callback()
{
    $buffer = ob_get_contents();
    ob_end_clean();

    $ressio = new Ressio();
    $result = $ressio->run($buffer);

    echo $result;
}
