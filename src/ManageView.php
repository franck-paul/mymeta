<?php
/**
 * @brief mymeta, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @author Franck Paul and contributors
 *
 * @copyright Franck Paul carnet.franck.paul@gmail.com
 * @copyright GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
declare(strict_types=1);

namespace Dotclear\Plugin\mymeta;

use dcCore;
use dcNsProcess;
use dcPage;
use Dotclear\Helper\Html\Html;

class ManageView extends dcNsProcess
{
    protected static $init = false; /** @deprecated since 2.27 */
    /**
     * Initializes the page.
     */
    public static function init(): bool
    {
        static::$init = My::checkContext(My::MANAGE) && (($_REQUEST['m'] ?? 'mymeta') === 'view');

        dcCore::app()->admin->mymeta = new MyMeta();

        return static::$init;
    }

    /**
     * Processes the request(s).
     */
    public static function process(): bool
    {
        if (!static::$init) {
            return false;
        }

        if (empty($_GET['id'])) {
            dcPage::addErrorNotice(__('Something went wrong when editing mymeta'));
            dcCore::app()->adminurl->redirect('admin.plugin.' . My::id());
        }

        dcCore::app()->admin->mymetaEntry = dcCore::app()->admin->mymeta->getByID($_GET['id']);
        if (dcCore::app()->admin->mymetaEntry == null) {
            dcPage::addErrorNotice(__('Something went wrong when editing mymeta'));
            dcCore::app()->adminurl->redirect('admin.plugin.' . My::id());
        }

        return true;
    }

    /**
     * Renders the page.
     */
    public static function render(): void
    {
        if (!static::$init) {
            return;
        }

        $nb_per_page = 20;
        $page        = !empty($_GET['page']) ? max(1, (int) $_GET['page']) : 1;

        $params = [
            'meta_type' => dcCore::app()->admin->mymetaEntry->id,
            'order'     => 'count DESC',
            'limit'     => [(($page - 1) * $nb_per_page),$nb_per_page],
        ];

        $rs    = dcCore::app()->admin->mymeta->getMetadata($params, false);
        $count = dcCore::app()->admin->mymeta->getMetadata($params, true);

        $head = dcPage::jsPageTabs('mymeta');

        dcPage::openModule(__('My metadata') . '&gt;' . dcCore::app()->admin->mymetaEntry->id, $head);

        echo dcPage::breadcrumb(
            [
                Html::escapeHTML(dcCore::app()->blog->name)             => '',
                __('My Metadata')                                       => dcCore::app()->admin->getPageURL(),
                Html::escapeHTML(dcCore::app()->admin->mymetaEntry->id) => '',
            ]
        );
        echo dcPage::notices();

        // Form
        echo '<div class="fieldset"><h3>' . sprintf(__('Values of metadata "%s"'), Html::escapeHTML(dcCore::app()->admin->mymetaEntry->id)) . '</h3>';
        $list = new BackendList($rs, $count->f(0));
        echo $list->display($page, $nb_per_page, '%s');
        echo '</div>';

        dcPage::closeModule();
    }
}
