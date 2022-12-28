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
if (!defined('DC_CONTEXT_ADMIN')) {
    return;
}
dcCore::app()->admin->mymeta = new myMeta();

if (!empty($_REQUEST['m'])) {
    switch ($_REQUEST['m']) {
        case 'edit':
            require __DIR__ . '/index_edit.php';

            break;
        case 'view':
            require __DIR__ . '/index_view.php';

            break;
        case 'viewposts':
            require __DIR__ . '/index_view_posts.php';

            break;
        case 'editsection':
            require __DIR__ . '/index_edit_section.php';

            break;
    }
} else {
    require __DIR__ . '/index_home.php';
}
