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

use Dotclear\App;
use Dotclear\Core\Backend\Notices;
use Dotclear\Core\Backend\Page;
use Dotclear\Core\Process;
use Dotclear\Helper\Html\Html;

/**
 * @todo switch Helper/Html/Form/...
 */
class ManageView extends Process
{
    /**
     * Initializes the page.
     */
    public static function init(): bool
    {
        App::backend()->mymeta = new MyMeta();

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
            My::redirect();
        }

        App::backend()->mymetaEntry = App::backend()->mymeta->getByID($_GET['id']);
        if (App::backend()->mymetaEntry == null) {
            Notices::addErrorNotice(__('Something went wrong when editing mymeta'));
            My::redirect();
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
        $page        = empty($_GET['page']) ? 1 : max(1, (int) $_GET['page']);

        $params = [
            'meta_type' => App::backend()->mymetaEntry->id,
            'order'     => 'count DESC',
            'limit'     => [(($page - 1) * $nb_per_page),$nb_per_page],
        ];

        $rs    = App::backend()->mymeta->getMetadata($params, false);
        $count = App::backend()->mymeta->getMetadata($params, true);

        $head = Page::jsPageTabs('mymeta');

        Page::openModule(My::name() . ' &gt; ' . App::backend()->mymetaEntry->id, $head);

        echo Page::breadcrumb(
            [
                Html::escapeHTML(App::blog()->name())             => '',
                __('My Metadata')                                 => App::backend()->getPageURL(),
                Html::escapeHTML(App::backend()->mymetaEntry->id) => '',
            ]
        );
        echo Notices::getNotices();

        // Form
        echo '<div class="fieldset"><h3>' . sprintf(__('Values of metadata "%s"'), Html::escapeHTML(App::backend()->mymetaEntry->id)) . '</h3>';
        $list = new BackendList($rs, $count->f(0));
        $list->display($page, $nb_per_page, '%s');
        echo '</div>';

        Page::closeModule();
    }
}
