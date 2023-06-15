<?php
/**
 * @brief mymeta, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @author Franck Paul and contributors
 *
 * @copyright Franck Paul carnet.franck.paul@gmail.com
 * @copyright GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
declare(strict_types=1);

namespace Dotclear\Plugin\mymeta;

use Dotclear\Plugin\widgets\WidgetsStack;

class Widgets
{
    public static function initWidgets(WidgetsStack $w)
    {
        $mymeta                             = new MyMeta();
        $mymetalist                         = $mymeta->getIDsAsWidgetList();
        $mymetasections                     = $mymeta->getSectionsAsWidgetList();
        $mymetasections[__('All sections')] = '';

        // Widget for list of metadata
        $w
            ->create('mymetalist', __('MyMeta List'), [FrontendWidgets::class, 'mymetaList'])
            ->addTitle(__('Title'))
            ->setting(
                'prompt',
                __('Value to display'),
                'prompt',
                'combo',
                [
                    __('ID')     => 'id',
                    __('Prompt') => 'prompt',
                ]
            )
            ->setting(
                'section',
                __('Section to display'),
                '',
                'combo',
                $mymetasections,
            )
            ->addHomeOnly()
            ->addContentOnly()
            ->addClass()
            ->addOffline();

        // Widget for currently displayed post

        $w
            ->create('mymetavalues', __('MyMeta Values list'), [FrontendWidgets::class, 'mymetaValues'])
            ->addTitle(__('Title'))
            ->setting(
                'mymetaid',
                __('MyMeta ID'),
                current($mymetalist),
                'combo',
                $mymetalist,
            )
            ->setting(
                'displaymode',
                __('Display mode'),
                'list',
                'combo',
                [
                    __('Cloud') => 'cloud',
                    __('List')  => 'list',
                ]
            )
            ->setting('limit', __('Limit (empty means no limit):'), '20')
            ->setting(
                'sortby',
                __('Order by:'),
                'meta_id_lower',
                'combo',
                [
                    __('Meta name')     => 'meta_id_lower',
                    __('Entries count') => 'count',
                ]
            )
            ->setting(
                'orderby',
                __('Sort:'),
                'asc',
                'combo',
                [
                    __('Ascending')  => 'asc',
                    __('Descending') => 'desc',
                ]
            )
            ->setting('allvalueslinktitle', __('Link to all values:'), __('All values'))
            ->addHomeOnly()
            ->addContentOnly()
            ->addClass()
            ->addOffline();
    }
}
