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

dcCore::app()->addBehavior('initWidgets', ['MyMetaWidgets','initWidgets']);

class MyMetaWidgets
{
    public static function initWidgets($w)
    {
        $mymeta                             = new myMeta();
        $mymetalist                         = $mymeta->getIDsAsWidgetList();
        $mymetasections                     = $mymeta->getSectionsAsWidgetList();
        $mymetasections[__('All sections')] = '';

        // Widget for list of metadata
        $w
            ->create('mymetalist', __('MyMeta List'), ['widgetsMyMeta', 'mymetaList'])
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
            ->create('mymetavalues', __('MyMeta Values list'), ['widgetsMyMeta', 'mymetaValues'])
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
