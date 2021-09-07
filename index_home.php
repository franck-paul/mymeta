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

$mymeta = new mymeta($core,true);
if ($mymeta->settings->mymeta_fields != null) {
	$backup = $mymeta->settings->mymeta_fields;
	$fields = unserialize(base64_decode($mymeta->settings->mymeta_fields));
	if (is_array($fields) && count($fields)>0
		&& get_class(current($fields)) == 'stdClass') {

		foreach ($fields as $k => $v) {
			$newfield = $mymeta->newMyMeta($v->type);
			$newfield->id = $k;
			$newfield->enabled = $v->enabled;
			$newfield->prompt = $v->prompt;
			switch($v->type) {
				case 'list':$newfield->values = $v->values;;break;
			}
			$mymeta->update($newfield);

		}
		$mymeta->reorder();
		$mymeta->store();

		if ($mymeta->settings->mymeta_fields_backup == null) {
			$mymeta->settings->put("mymeta_fields_backup",
					$backup,
					'string',
					"MyMeta fields backup (0.3.x version)");
		}
		http::redirect($p_url);
		exit;
	}
}
$mymeta = new mymeta($core);
$dcmeta = new dcMeta($core);
if (!empty($_POST['action']) && !empty($_POST['entries']))
{
	$entries = $_POST['entries'];
	$action = $_POST['action'];
	if (preg_match('/^(enable|disable)$/',$action))
	{
		$mymeta->setEnabled($entries,($action==="enable"));
		$msg = ($action==="enable")?
			__('Mymeta entries have been successfully enabled')
			: __('Mymeta entries have been successfully disabled');

	}
	elseif (preg_match('/^(delete)$/',$action)) {
		$mymeta->delete($entries);
		$msg = __('Mymeta entries have been successfully deleted');
	}
	$mymeta->store();
	dcPage::addSuccessNotice($msg);
	http::redirect($p_url);
	exit;
}
if (!empty($_POST['newsep']) && !empty($_POST['mymeta_section'])) {
	$section = $mymeta->newSection();
	$section->prompt = html::escapeHTML($_POST['mymeta_section']);
	$mymeta->update($section);
	$mymeta->store();
	dcPage::addSuccessNotice(sprintf(
		__('Section "%s" has been successfully created'),
		html::escapeHTML($_POST['mymeta_section'])));
	http::redirect($p_url);
	exit;
}

# Order links
$order = array();
if (empty($_POST['mymeta_order']) && !empty($_POST['order'])) {
	$order = $_POST['order'];
	asort($order);
	$order = array_keys($order);
} elseif (!empty($_POST['mymeta_order'])) {
	$order = explode(',',$_POST['mymeta_order']);
}

if (!empty($_POST['saveorder']) && !empty($order))
{
	$mymeta->reorder($order);
	$mymeta->store();

	dcPage::addSuccessNotice (__('Mymeta have been successfully reordered'));
	http::redirect($p_url);
	exit;
}
$types = $mymeta->getTypesAsCombo();


$combo_action = array();
$combo_action[__('enable')] = 'enable';
$combo_action[__('disable')] = 'disable';
$combo_action[__('delete')] = 'delete';


?>
<html>
<head>
  <title><?php echo __('My Metadata'); ?></title>
  <?php // echo dcPage::jsToolMan(); ?>
  <?php echo
	dcPage::jsLoad('js/jquery/jquery-ui.custom.js').
	dcPage::jsLoad('js/jquery/jquery.ui.touch-punch.js').
  	dcPage::jsLoad('index.php?pf=mymeta/js/_meta_lists.js');

  ?>
</head>
<body>
<?php

echo dcPage::breadcrumb(
	array(
		html::escapeHTML($core->blog->name) => '',
		__('My Metadata') => ''
	)).dcPage::notices();

echo
'<p class="top-add"><a class="button add" href="#new-meta">'.__('Add a metadata').'</a></p>';

echo '<h3>'.__('MyMeta list').'</h3>';
?>

<form action="plugin.php" method="post" id="mymeta-form">
	<table class="dragable">
		<thead>
			<tr>
				<th colspan="4"><?php echo __('ID'); ?></th>
				<th><?php echo __('Type'); ?></th>
				<th><?php echo __('Prompt'); ?></th>
				<th><?php echo __('Posts'); ?></th>
				<th><?php echo __('Count'); ?></th>
				<th colspan="2"><?php echo __('Status'); ?></th>
			</tr>
		</thead>
		<tbody id="mymeta-list">
			<?php
				$metaStat = $mymeta->getMyMetaStats();
				$stats = array();
				while ($metaStat->fetch()) {
					$stats[$metaStat->meta_type]=$metaStat->count;
				}

				$allMeta = $mymeta->getAll();
				foreach ($allMeta as $meta) {
					if ($meta instanceof myMetaSection) {
						echo
						'<tr class="line" id="l_'.$meta->id.'">'.
						 '<td class="handle minimal">'.
						form::field(array('order['.$meta->id.']'),2,5,$meta->pos,'position').'</td>'.
						'<td class="minimal">'.form::checkbox(array('entries[]'),$meta->id).'</td>'.
						'<td class="nowrap minimal status"><a href="plugin.php?p=mymeta&amp;m=editsection&amp;id='.$meta->id.'">'.
						'<img src="images/menu/edit.png" alt="'.__('edit MyMeta').'" /></a></td>'.
						'<td class="nowrap maximal" colspan="6">'.
						'<strong>'.sprintf(__('Section: %s'),html::escapeHTML($meta->prompt)).'</strong></td>'.
						'</tr>';
					} else {
						$img = '<img alt="%1$s" title="%1$s" src="images/%2$s" />';
						if ($meta->enabled) {
							$img_status = sprintf($img,__('published'),'check-on.png');
						} else {
							$img_status = sprintf($img,__('unpublished'),'check-off.png');
						}
						$st = (isset($stats[$meta->id]))?$stats[$meta->id]:0;
						$restrictions = $meta->getRestrictions();
						if (!$restrictions)
							$restrictions=__('All');
						echo
						'<tr class="line'.($meta->enabled ? '' : ' offline').'" id="l_'.$meta->id.'">'.
						 '<td class="handle minimal">'.
						form::field(array('order['.$meta->id.']'),2,5,$meta->pos,'position').'</td>'.
						'<td class="minimal">'.form::checkbox(array('entries[]'),$meta->id).'</td>'.
						'<td class="nowrap minimal status"><a href="plugin.php?p=mymeta&amp;m=edit&amp;id='.$meta->id.'">'.
						'<img src="images/menu/edit.png" alt="'.__('edit MyMeta').'" /></a></td>'.
						'<td class="nowrap"><a href="plugin.php?p=mymeta&amp;m=view&amp;id='.$meta->id.'">'.
						html::escapeHTML($meta->id).'</a></td>'.
						'<td class="nowrap">'.$meta->getMetaTypeDesc().'</td>'.
						'<td class="nowrap maximal">'.$meta->prompt.'</td>'.
						'<td>'.$restrictions.'</td><td class="nowrap">'.
						$st.' '.(($st<=1)?__('entry'):__('entries')).'</td>'.
						'<td class="nowrap minimal">'.$img_status.'</td>'.
						'</tr>';
					}
				}
			?>
		</tbody>
	</table>
	<div class="two-cols">
		<p class="col">
			<?php
				echo form::hidden('mymeta_order','');
				echo form::hidden(array('p'),'mymeta');
				echo $core->formNonce();
			?>
			<input type="submit" name="saveorder" value="<?php echo __('Save order'); ?>" />
		</p>
		<p class="col right">
			<?php
				echo
					__('Selected metas action:').
					form::combo('action',$combo_action);
			?>
			<input type="submit" value="<?php echo __('ok'); ?>" />
		</p>
	</div>
</form>
<div class="fieldset clear">
	<form method="post" action="plugin.php">
		<?php
			echo '<h3 id="new-meta">'.__('New metadata').'</h3>'.
				'<p>'.__('New MyMeta').' : '.
				form::combo('mymeta_type', $types,'').
				'&nbsp;<input type="submit" name="new" value="'.__('Create MyMeta').'" />'.
				form::hidden(array('p'),'mymeta').
				form::hidden(array('m'),'edit').$core->formNonce().
				'</p>';
		?>
	</form>
	<form method="post" action="plugin.php">
		<?php
			echo '<p>'.__('New section').' : '.
				form::field('mymeta_section', '','').
				'&nbsp;<input type="submit" name="newsep" value="'.__('Create section').'" />'.
				form::hidden(array('p'),'mymeta').
				$core->formNonce().
				'</p>';
		?>
	</form>
</div>
</body>
</html>
