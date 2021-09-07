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
if (!defined('DC_CONTEXT_ADMIN')) { return; }

$mymeta = new myMeta($core);

if (!empty($_REQUEST['m'])) {
	switch ($_REQUEST['m']) {
		case 'edit' :
			require dirname(__FILE__).'/index_edit.php';
			break;
		case 'view' :
			require dirname(__FILE__).'/index_view.php';
			break;
		case 'viewposts' :
			require dirname(__FILE__).'/index_view_posts.php';
			break;
		case 'editsection' :
			require dirname(__FILE__).'/index_edit_section.php';
			break;
	}
} else {
	require dirname(__FILE__).'/index_home.php';
}
