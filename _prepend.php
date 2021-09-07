<?php
/**
 * @brief mymeta, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @author Bruno Hondelatte and contributors
 *
 * @copyright GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
if (!defined('DC_RC_PATH')) {
    return;
}

$GLOBALS['__autoload']['myMeta'] = dirname(__FILE__) . '/class.mymeta.php';

$GLOBALS['core']->url->register('mymeta', 'meta', '^meta/(.+)$', ['urlMymeta','tag']);
