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

require dirname(__FILE__).'/class.mymeta.php';
require dirname(__FILE__).'/_widgets.php';

$_menu['Plugins']->addItem(__('My Metadata'),'plugin.php?p=mymeta','index.php?pf=mymeta/mymeta.png',
		preg_match('/plugin.php\?p=mymeta(&.*)?$/',$_SERVER['REQUEST_URI']),
		$core->auth->check('usage,contentadmin',$core->blog->id));

$core->addBehavior('adminPostFormSidebar',array('mymetaBehaviors','mymetaSidebar'));
$core->addBehavior('adminPostForm',array('mymetaBehaviors','mymetaInForm'));
$core->addBehavior('adminPostForm',array('mymetaBehaviors','mymetaPostHeader'));

$core->addBehavior('adminAfterPostCreate',array('mymetaBehaviors','setMymeta'));
$core->addBehavior('adminAfterPostUpdate',array('mymetaBehaviors','setMymeta'));

$core->addBehavior('adminPageFormSidebar',array('mymetaBehaviors','mymetaSidebar'));
$core->addBehavior('adminPageForm',array('mymetaBehaviors','mymetaInForm'));

$core->addBehavior('adminPostsActionsCombo',array('mymetaBehaviors','adminPostsActionsCombo'));
$core->addBehavior('adminPostsActions',array('mymetaBehaviors','adminPostsActions'));
$core->addBehavior('adminPostsActionsContent',array('mymetaBehaviors','adminPostsActionsContent'));
$core->addBehavior('adminPostsActionsHeaders',array('mymetaBehaviors','adminPostsActionsHeaders'));

$core->addBehavior('adminAfterPageCreate',array('mymetaBehaviors','setMymeta'));
$core->addBehavior('adminAfterPageUpdate',array('mymetaBehaviors','setMymeta'));
# BEHAVIORS
class mymetaBehaviors
{

	public static function mymetaPostHeader($post)
	{
		$mymeta = new myMeta($GLOBALS['core']);

		echo $mymeta->postShowHeader($post);
	}
	public static function mymetaSidebar($post)
	{
	}

	public static function mymetaInForm($post)
	{
		$mymeta = new myMeta($GLOBALS['core']);
		if ($mymeta->hasMeta()) {
			echo $mymeta->postShowForm($post);
		}

	}

	public static function setMymeta($cur,$post_id)
	{
		$mymeta = new myMeta($GLOBALS['core']);
		$mymeta->setMeta($post_id,$_POST);
	}


	public static function adminPostsActionsCombo($args)
	{
		$args[0][__('MyMeta')] = array(__('Set MyMeta') => 'mymeta_set');

	}

	public static function adminPostsActionsHeaders() {
		$mymeta = new myMeta($GLOBALS['core']);
		return $mymeta->postShowHeader(null,true);
	}

	public static function adminPostsActions($core,$posts,$action,$redir)
	{
		if ($action == 'mymeta_set' && !empty($_POST['mymeta_ok']))
		{
			$mymeta = new myMeta($GLOBALS['core']);
			if ($mymeta->hasMeta()) {
				while ($posts->fetch())
				{
					$mymeta->setMeta($posts->post_id,$_POST,false);
				}
			}
			http::redirect($redir);
		}
	}

	public static function adminPostsActionsContent($core,$action,$hidden_fields)
	{
		if ($action == 'mymeta_set')
		{

			$mymeta = new myMeta($core);
			if ($mymeta->hasMeta()) {
				echo '<h2>'.__('Set Metadata').'</h2>'.
					'<form action="posts_actions.php" method="post">'.
					$mymeta->postShowForm(null).
					'<p><input type="submit" value="'.__('save').'" />'.
					$hidden_fields.
					$core->formNonce().
					form::hidden(array('action'),'mymeta_set').
					form::hidden(array('mymeta_ok'),'1').'</p></form>';
			}
		}
	}

}

# REST
class mymetaRest
{
}
