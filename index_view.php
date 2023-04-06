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

use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Network\Http;

if (!defined('DC_CONTEXT_ADMIN')) {
    return;
}

if (empty($_GET['id'])) {
    dcPage::addErrorNotice(__('Something went wrong when editing mymeta'));
    Http::redirect(dcCore::app()->admin->getPageURL());
    exit;
}
$nb_per_page = 20;
$page        = !empty($_GET['page']) ? max(1, (int) $_GET['page']) : 1;

dcCore::app()->admin->mymetaEntry = dcCore::app()->admin->mymeta->getByID($_GET['id']);
if (dcCore::app()->admin->mymetaEntry == null) {
    dcPage::addErrorNotice(__('Something went wrong when editing mymeta'));
    Http::redirect(dcCore::app()->admin->getPageURL());
    exit;
}

class adminMyMetaList extends adminGenericListV2
{
    public function display($page, $nb_per_page, $enclose_block = '')
    {
        if ($this->rs->isEmpty()) {
            echo '<p><strong>' . __('No entries found') . '</strong></p>';
        } else {
            $pager = new dcPager($page, $this->rs_count, $nb_per_page, $nb_per_page);

            $html_block = '<table class="clear"><tr>' .
            '<th>' . __('Value') . '</th>' .
            '<th>' . __('Nb Posts') . '</th>' .
            '</tr>%s</table>';

            if ($enclose_block) {
                $html_block = sprintf($enclose_block, $html_block);
            }

            echo $pager->getLinks();

            $blocks = explode('%s', $html_block);

            echo $blocks[0];
            while ($this->rs->fetch()) {
                echo $this->postLine();
            }

            echo $blocks[1];

            echo $pager->getLinks();
        }
    }

    private function postLine()
    {
        return
        '<tr class="line">' .
        '<td class="nowrap"><a href="' . dcCore::app()->admin->getPageURL() . '&amp;m=viewposts&amp;id=' . dcCore::app()->admin->mymetaEntry->id . '&amp;value=' . rawurlencode($this->rs->meta_id) . '">' . dcCore::app()->admin->mymetaEntry->displayValue($this->rs->meta_id) . '</a></td>' .
        '<td class="nowrap">' . $this->rs->count . ' ' . (($this->rs->count <= 1) ? __('entry') : __('entries')) . '</td>' .
        '</tr>';
    }
}
$statuses = [
    'valchg' => __('Value has been successfully changed'),
];

?>
<html>
<head>
  <title><?php echo __('My metadata') . '&gt;' . dcCore::app()->admin->mymetaEntry->id; ?></title>
  <?php echo dcPage::jsPageTabs('mymeta');?>
</head>
<body>
<?php

echo dcPage::breadcrumb(
    [
        Html::escapeHTML(dcCore::app()->blog->name)             => '',
        __('My Metadata')                                       => dcCore::app()->admin->getPageURL(),
        Html::escapeHTML(dcCore::app()->admin->mymetaEntry->id) => '',
    ]
) . dcPage::notices();

$params = [
    'meta_type' => dcCore::app()->admin->mymetaEntry->id,
    'order'     => 'count DESC',
    'limit'     => [(($page - 1) * $nb_per_page),$nb_per_page],
];

$rs    = dcCore::app()->admin->mymeta->getMetadata($params, false);
$count = dcCore::app()->admin->mymeta->getMetadata($params, true);
echo '<div class="fieldset"><h3>' . sprintf(__('Values of metadata "%s"'), Html::escapeHTML(dcCore::app()->admin->mymetaEntry->id)) . '</h3>';
$list = new adminMyMetaList($rs, $count->f(0));
echo $list->display($page, $nb_per_page, '%s');
echo '</div>';
?>
</body>
</html>
