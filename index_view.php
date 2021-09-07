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

if (empty($_GET['id'])) {
	dcPage::addErrorNotice(__('Something went wrong when editing mymeta'));
	http::redirect($p_url);
	exit;
}
$nb_per_page=20;
$page = !empty($_GET['page']) ? max(1,(integer) $_GET['page']) : 1;
$mymetaEntry = $mymeta->getByID($_GET['id']);
if ($mymetaEntry == null) {
	dcPage::addErrorNotice(__('Something went wrong when editing mymeta'));
	http::redirect($p_url);
	exit;
}
class adminMyMetaList extends adminGenericList
{
	public function display($page,$nb_per_page,$enclose_block='')
	{
		if ($this->rs->isEmpty())
		{
			echo '<p><strong>'.__('No entries found').'</strong></p>';
		}
		else
		{
			$pager = new dcPager($page,$this->rs_count,$nb_per_page,$nb_per_page);

			$html_block =
			'<table class="clear"><tr>'.
			'<th>'.__('Value').'</th>'.
			'<th>'.__('Nb Posts').'</th>'.
			'</tr>%s</table>';

			if ($enclose_block) {
				$html_block = sprintf($enclose_block,$html_block);
			}

			echo $pager->getLinks();

			$blocks = explode('%s',$html_block);

			echo $blocks[0];

			while ($this->rs->fetch())
			{
				echo $this->postLine();
			}

			echo $blocks[1];

			echo $pager->getLinks();
		}
	}

	private function postLine()
	{
		global $p_url,$mymetaEntry;
		$res = '<tr class="line">';

		$res .=
		'<td class="nowrap"><a href="'.$p_url.'&amp;m=viewposts&amp;id='.$mymetaEntry->id.'&amp;value='.rawurlencode($this->rs->meta_id).'">'.
		$this->rs->meta_id.'</a></td>'.
		'<td class="nowrap">'.$this->rs->count.' '.(($this->rs->count<=1)?__('entry'):__('entries')).'</td>'.
		'</tr>';

		return $res;
	}
}
$statuses = array(
	'valchg' => __('Value has been successfully changed')
);

?>
<html>
<head>
  <title><?php echo __('My metadata').'&gt;'.$mymetaEntry->id; ?></title>
  <?php echo dcPage::jsPageTabs('mymeta');?>
</head>
<body>
<?php

echo dcPage::breadcrumb(
	array(
		html::escapeHTML($core->blog->name) => '',
		__('My Metadata') => $p_url,
		html::escapeHTML($mymetaEntry->id) => '',
	)).dcPage::notices();


$params=array(
	'meta_type' => $mymetaEntry->id,
	'order' => 'count DESC',
	'limit' => array((($page-1)*$nb_per_page),$nb_per_page)
);


$rs = $mymeta->getMetadata($params,false);
$count = $mymeta->getMetadata($params,true);
echo '<div class="fieldset"><h3>'.sprintf(__('Values of metadata "%s"'),html::escapeHTML($mymetaEntry->id)).'</h3>';
$list = new adminMyMetaList($core,$rs,$count->f(0));
echo $list->display($page,$nb_per_page,'%s');
echo '</div>';
?>
</body>
</html>
