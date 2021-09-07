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

$this->registerModule(
	/* Name */			"My Meta",
	/* Description*/		"User-defined metadata management in posts",
	/* Author */			"Bruno Hondelatte",
	/* Version */			'0.5.3',
	/* Permissions */		'usage,contentadmin',
	/* Priority */			1001
);
