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

$GLOBALS['__autoload']['myMeta'] = dirname(__FILE__).'/class.mymeta.php';

$GLOBALS['core']->url->register('mymeta','meta','^meta/(.+)$',array('urlMymeta','tag'));
