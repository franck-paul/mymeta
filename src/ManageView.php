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
use Dotclear\App;
use Dotclear\Core\Backend\Notices;
use Dotclear\Core\Backend\Page;
use Dotclear\Core\Process;
use Dotclear\Helper\Html\Html;

class ManageView extends Process
{
    /**
     * Initializes the page.
     */
    public static function init(): bool
    {
        dcCore::app()->admin->mymeta = new MyMeta();

        return self::status(My::checkContext(My::MANAGE) && (($_REQUEST['m'] ?? 'mymeta') === 'view'));
    }

    /**
     * Processes the request(s).
     */
    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        if (empty($_GET['id'])) {
            Notices::addErrorNotice(__('Something went wrong when editing mymeta'));
            dcCore::app()->adminurl->redirect('admin.plugin.' . My::id());
        }

        dcCore::app()->admin->mymetaEntry = dcCore::app()->admin->mymeta->getByID($_GET['id']);
        if (dcCore::app()->admin->mymetaEntry == null) {
            Notices::addErrorNotice(__('Something went wrong when editing mymeta'));
            dcCore::app()->adminurl->redirect('admin.plugin.' . My::id());
        }

        return true;
    }

    /**
     * Renders the page.
     */
    public static function render(): void
    {
        if (!self::status()) {
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

        $head = Page::jsPageTabs('mymeta');

        Page::openModule(My::name() . ' &gt; ' . dcCore::app()->admin->mymetaEntry->id, $head);

        echo Page::breadcrumb(
            [
                Html::escapeHTML(App::blog()->name())                   => '',
                __('My Metadata')                                       => dcCore::app()->admin->getPageURL(),
                Html::escapeHTML(dcCore::app()->admin->mymetaEntry->id) => '',
            ]
        );
        echo Notices::getNotices();

        // Form
        echo '<div class="fieldset"><h3>' . sprintf(__('Values of metadata "%s"'), Html::escapeHTML(dcCore::app()->admin->mymetaEntry->id)) . '</h3>';
        $list = new BackendList($rs, $count->f(0));
        $list->display($page, $nb_per_page, '%s');
        echo '</div>';

        Page::closeModule();
    }
}
