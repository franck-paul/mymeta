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

use Dotclear\Helper\Clearbricks;

Clearbricks::lib()->autoload(['myMeta' => __DIR__ . '/class.mymeta.php']);

dcCore::app()->url->register('mymeta', 'meta', '^meta/(.+)$', ['urlMymeta','tag']);
