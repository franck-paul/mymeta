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

        $w->create('mymetalist', __('MyMeta List'), ['widgetsMyMeta','mymetaList']);

        $w->mymetalist->setting('title', __('Title'), '', 'text');
        $w->mymetalist->setting(
            'prompt',
            __('Value to display'),
            'prompt',
            'combo',
            [__('ID') => 'id', __('Prompt') => 'prompt']
        );
        $w->mymetalist->setting(
            'section',
            __('Section to display'),
            '',
            'combo',
            $mymetasections
        );
        $w->mymetalist->setting('homeonly', __('Home page only'), 0, 'check');

        $w->create('mymetavalues', __('MyMeta Values list'), ['widgetsMyMeta','mymetaValues']);
        $w->mymetavalues->setting('title', __('Title'), '', 'text');
        $w->mymetavalues->setting('mymetaid', __('MyMeta ID'), current($mymetalist), 'combo', $mymetalist);
        $w->mymetavalues->setting(
            'displaymode',
            __('Display mode'),
            'list',
            'combo',
            [__('Cloud') => 'cloud', __('List') => 'list']
        );
        $w->mymetavalues->setting('limit', __('Limit (empty means no limit):'), '20');
        $w->mymetavalues->setting(
            'sortby',
            __('Order by:'),
            'meta_id_lower',
            'combo',
            [__('Meta name') => 'meta_id_lower', __('Entries count') => 'count']
        );
        $w->mymetavalues->setting(
            'orderby',
            __('Sort:'),
            'asc',
            'combo',
            [__('Ascending') => 'asc', __('Descending') => 'desc']
        );
        $w->mymetavalues->setting('allvalueslinktitle', __('Link to all values:'), __('All values'));
        $w->mymetavalues->setting('homeonly', __('Home page only'), 0, 'check');
    }
}
