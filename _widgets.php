<?php
# ***** BEGIN LICENSE BLOCK *****
# This file is part of DotClear Mymeta plugin.
#
# Copyright (c) 2010 Bruno Hondelatte, and contributors.
# Many, many thanks to Olivier Meunier and the Dotclear Team.
# Licensed under the GPL version 2.0 license.
# See LICENSE file or
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# ***** END LICENSE BLOCK *****
if (!defined('DC_RC_PATH')) { return; }

$core->addBehavior('initWidgets', array('MyMetaWidgets','initWidgets'));

class MyMetaWidgets
{
	public static function initWidgets($w)
	{
		$mymeta = new myMeta($GLOBALS['core']);
		$mymetalist = $mymeta->getIDsAsWidgetList();
		$mymetasections = $mymeta->getSectionsAsWidgetList();
		$mymetasections[__('All sections')]='';
		$w->create('mymetalist',__('MyMeta List'), array('widgetsMyMeta','mymetaList'));
		$w->mymetalist->setting('title',__('Title'), '','text');
		$w->mymetalist->setting('prompt',__('Value to display'),'prompt','combo',
			array(__('ID') => 'id', __('Prompt') => 'prompt'));
		$w->mymetalist->setting('section',__('Section to display'),'','combo',
		$mymetasections);
		$w->mymetalist->setting('homeonly',__('Home page only'),0,'check');

		$w->create('mymetavalues',__('MyMeta Values list'), array('widgetsMyMeta','mymetaValues'));
		$w->mymetavalues->setting('title',__('Title'), '','text');
		$w->mymetavalues->setting('mymetaid',__('MyMeta ID'),current($mymetalist),'combo',$mymetalist);
		$w->mymetavalues->setting('displaymode',__('Display mode'),'list','combo',
			array(__('Cloud') => 'cloud', __('List') => 'list')
		);
		$w->mymetavalues->setting('limit',__('Limit (empty means no limit):'),'20');
		$w->mymetavalues->setting('sortby',__('Order by:'),'meta_id_lower','combo',
			array(__('Meta name') => 'meta_id_lower', __('Entries count') => 'count')
		);
		$w->mymetavalues->setting('orderby',__('Sort:'),'asc','combo',
			array(__('Ascending') => 'asc', __('Descending') => 'desc')
		);
		$w->mymetavalues->setting('allvalueslinktitle',__('Link to all values:'),__('All values'));
		$w->mymetavalues->setting('homeonly',__('Home page only'),0,'check');
	}
}
