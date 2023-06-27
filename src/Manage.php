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
use Exception;
use form;
use stdClass;

class Manage extends dcNsProcess
{
    protected static $init = false; /** @deprecated since 2.27 */
    /**
     * Initializes the page.
     */
    public static function init(): bool
    {
        static::$init = My::checkContext(My::MANAGE);

        dcCore::app()->admin->mymeta = new MyMeta();

        if (!empty($_REQUEST['m'])) {
            switch ($_REQUEST['m']) {
                case 'edit':
                    static::$init = static::$init && ManageEdit::init();

                    break;
                case 'editsection':
                    static::$init = static::$init && ManageEditSection::init();

                    break;
                case 'view':
                    static::$init = static::$init && ManageView::init();

                    break;
                case 'viewposts':
                    static::$init = static::$init && ManageViewPosts::init();

                    break;
            }
        }

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

        if (!empty($_REQUEST['m'])) {
            switch ($_REQUEST['m']) {
                case 'edit':
                    return ManageEdit::process();

                case 'editsection':
                    return ManageEditSection::process();

                case 'view':
                    return ManageView::process();

                case 'viewposts':
                    return ManageViewPosts::process();
            }
        }

        dcCore::app()->admin->mymeta = new MyMeta(true);
        if (dcCore::app()->admin->mymeta->settings->mymeta_fields != null) {
            $backup = dcCore::app()->admin->mymeta->settings->mymeta_fields;
            $fields = unserialize(base64_decode(dcCore::app()->admin->mymeta->settings->mymeta_fields));
            if (is_array($fields) && count($fields) > 0
                                  && get_class(current($fields)) === stdClass::class) {
                foreach ($fields as $k => $v) {
                    $newfield          = dcCore::app()->admin->mymeta->newMyMeta($v->type);
                    $newfield->id      = $k;
                    $newfield->enabled = $v->enabled;
                    $newfield->prompt  = $v->prompt;
                    switch ($v->type) {
                        case 'list':
                            $newfield->values = $v->values;

                            break;
                    }
                    dcCore::app()->admin->mymeta->update($newfield);
                }
                dcCore::app()->admin->mymeta->reorder();
                dcCore::app()->admin->mymeta->store();

                if (dcCore::app()->admin->mymeta->settings->mymeta_fields_backup == null) {
                    dcCore::app()->admin->mymeta->settings->put(
                        'mymeta_fields_backup',
                        $backup,
                        'string',
                        'MyMeta fields backup (0.3.x version)'
                    );
                }
                dcCore::app()->adminurl->redirect('admin.plugin.' . My::id());
            }
        }
        dcCore::app()->admin->mymeta = new MyMeta();

        if (!empty($_POST['action']) && !empty($_POST['entries'])) {
            try {
                $entries = $_POST['entries'];
                $action  = (string) $_POST['action'];
                $msg     = '';
                if (preg_match('/^(enable|disable)$/', $action)) {
                    dcCore::app()->admin->mymeta->setEnabled($entries, ($action === 'enable'));
                    $msg = ($action === 'enable') ?
                        __('Mymeta entries have been successfully enabled')
                        : __('Mymeta entries have been successfully disabled');
                } elseif (preg_match('/^(delete)$/', $action)) {
                    dcCore::app()->admin->mymeta->delete($entries);
                    $msg = __('Mymeta entries have been successfully deleted');
                }
                dcCore::app()->admin->mymeta->store();

                dcPage::addSuccessNotice($msg);
                dcCore::app()->adminurl->redirect('admin.plugin.' . My::id());
            } catch (Exception $e) {
                dcCore::app()->error->add($e->getMessage());
            }
        }

        if (!empty($_POST['newsep']) && !empty($_POST['mymeta_section'])) {
            try {
                $section         = dcCore::app()->admin->mymeta->newSection();
                $section->prompt = Html::escapeHTML($_POST['mymeta_section']);
                dcCore::app()->admin->mymeta->update($section);
                dcCore::app()->admin->mymeta->store();

                dcPage::addSuccessNotice(sprintf(
                    __('Section "%s" has been successfully created'),
                    Html::escapeHTML($_POST['mymeta_section'])
                ));
                dcCore::app()->adminurl->redirect('admin.plugin.' . My::id());
            } catch (Exception $e) {
                dcCore::app()->error->add($e->getMessage());
            }
        }

        // Order links
        $order = [];
        if (empty($_POST['mymeta_order']) && !empty($_POST['order'])) {
            $order = $_POST['order'];
            asort($order);
            $order = array_keys($order);
        } elseif (!empty($_POST['mymeta_order'])) {
            $order = explode(',', $_POST['mymeta_order']);
        }
        if (!empty($_POST['saveorder']) && !empty($order)) {
            try {
                dcCore::app()->admin->mymeta->reorder($order);
                dcCore::app()->admin->mymeta->store();

                dcPage::addSuccessNotice(__('Mymeta have been successfully reordered'));
                dcCore::app()->adminurl->redirect('admin.plugin.' . My::id());
            } catch (Exception $e) {
                dcCore::app()->error->add($e->getMessage());
            }
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

        if (!empty($_REQUEST['m'])) {
            switch ($_REQUEST['m']) {
                case 'edit':
                    ManageEdit::render();

                    return;

                case 'editsection':
                    ManageEditSection::render();

                    return;

                case 'view':
                    ManageView::render();

                    return;

                case 'viewposts':
                    ManageViewPosts::render();

                    return;
            }
        }

        $types = dcCore::app()->admin->mymeta->getTypesAsCombo();

        $combo_action                = [];
        $combo_action[__('enable')]  = 'enable';
        $combo_action[__('disable')] = 'disable';
        $combo_action[__('delete')]  = 'delete';

        $head = dcPage::jsLoad('js/jquery/jquery-ui.custom.js') .
        dcPage::jsLoad('js/jquery/jquery.ui.touch-punch.js') .
        dcPage::jsModuleLoad(My::id() . '/js/_meta_lists.js');

        dcPage::openModule(__('My Metadata'), $head);

        echo dcPage::breadcrumb(
            [
                Html::escapeHTML(dcCore::app()->blog->name) => '',
                __('My Metadata')                           => '',
            ]
        );
        echo dcPage::notices();

        // Form
        echo
        '<p class="top-add"><a class="button add" href="#new-meta">' . __('Add a metadata') . '</a></p>';

        echo
        '<h3>' . __('MyMeta list') . '</h3>';

        echo
        '<form action="' . dcCore::app()->admin->getPageURL() . '" method="post" id="mymeta-form">' .
        '<table class="dragable">' .
        '<thead>' .
        '<tr>' .
        '<th colspan="4">' . __('ID') . '</th>' .
        '<th>' . __('Type') . '</th>' .
        '<th>' . __('Prompt') . '</th>' .
        '<th>' . __('Posts') . '</th>' .
        '<th>' . __('Count') . '</th>' .
        '<th colspan="2">' . __('Status') . '</th>' .
        '</tr>' .
        '</thead>' .
        '<tbody id="mymeta-list">';

        $metaStat = dcCore::app()->admin->mymeta->getMyMetaStats();
        $stats    = [];
        while ($metaStat->fetch()) {
            $stats[$metaStat->meta_type] = $metaStat->count;
        }

        $allMeta = dcCore::app()->admin->mymeta->getAll();
        foreach ($allMeta as $meta) {
            if ($meta instanceof MyMetaSection) {
                echo
                '<tr class="line" id="l_' . $meta->id . '">' .
                 '<td class="handle minimal">' .
                form::field(['order[' . $meta->id . ']'], 2, 5, $meta->pos, 'position') . '</td>' .
                '<td class="minimal">' . form::checkbox(['entries[]'], $meta->id) . '</td>' .
                '<td class="nowrap minimal status"><a href="' . dcCore::app()->adminurl->get('admin.plugin.' . My::id(), [
                    'm'  => 'editsection',
                    'id' => $meta->id,
                ], '&') . '">' .
                '<img src="images/menu/edit.svg" class="icon-mini" alt="' . __('edit MyMeta') . '" /></a></td>' .
                '<td class="nowrap maximal" colspan="6">' .
                '<strong>' . sprintf(__('Section: %s'), Html::escapeHTML($meta->prompt)) . '</strong></td>' .
                '</tr>';
            } else {
                $img = '<img alt="%1$s" title="%1$s" src="images/%2$s" />';
                if ($meta->enabled) {
                    $img_status = sprintf($img, __('published'), 'check-on.png');
                } else {
                    $img_status = sprintf($img, __('unpublished'), 'check-off.png');
                }
                $st           = $stats[$meta->id] ?? 0;
                $restrictions = $meta->getRestrictions();
                if (!$restrictions) {
                    $restrictions = __('All');
                }
                echo
                '<tr class="line' . ($meta->enabled ? '' : ' offline') . '" id="l_' . $meta->id . '">' .
                 '<td class="handle minimal">' .
                form::field(['order[' . $meta->id . ']'], 2, 5, $meta->pos, 'position') . '</td>' .
                '<td class="minimal">' . form::checkbox(['entries[]'], $meta->id) . '</td>' .
                '<td class="nowrap minimal status"><a href="' . dcCore::app()->adminurl->get('admin.plugin.' . My::id(), [
                    'm'  => 'edit',
                    'id' => $meta->id,
                ], '&') . '">' .
                '<img src="images/menu/edit.svg" class="icon-mini" alt="' . __('edit MyMeta') . '" /></a></td>' .
                '<td class="nowrap"><a href="' . dcCore::app()->adminurl->get('admin.plugin.' . My::id(), [
                    'm'  => 'view',
                    'id' => $meta->id,
                ], '&') . '">' .
                Html::escapeHTML($meta->id) . '</a></td>' .
                '<td class="nowrap">' . $meta->getMetaTypeDesc() . '</td>' .
                '<td class="nowrap maximal">' . $meta->prompt . '</td>' .
                '<td>' . $restrictions . '</td><td class="nowrap">' .
                $st . ' ' . (($st <= 1) ? __('entry') : __('entries')) . '</td>' .
                '<td class="nowrap minimal">' . $img_status . '</td>' .
                '</tr>';
            }
        }

        echo
        '</tbody>' .
        '</table>' .
        '<div class="two-cols">' .
        '<p class="col">';

        echo form::hidden('mymeta_order', '');
        echo form::hidden(['p'], 'mymeta');
        echo dcCore::app()->formNonce();

        echo
        '<input type="submit" name="saveorder" value="' . __('Save order') . '" />' .
        '</p>' .
        '<p class="col right">';

        echo
        __('Selected metas action:') .
        form::combo('action', $combo_action);

        echo
        '<input type="submit" value="' . __('ok') . '" />' .
        '</p>' .
        '</div>' .
        '</form>' .
        '<div class="fieldset clear">' .
        '<form method="post" action="' . dcCore::app()->admin->getPageURL() . '">';

        echo
        '<h3 id="new-meta">' . __('New metadata') . '</h3>' .
        '<p>' . __('New MyMeta') . ' : ' .
        form::combo('mymeta_type', $types, '') .
        '&nbsp;<input type="submit" name="new" value="' . __('Create MyMeta') . '" />' .
        form::hidden(['p'], 'mymeta') .
        form::hidden(['m'], 'edit') . dcCore::app()->formNonce() .
        '</p>' .
        '</form>';

        echo
        '<form method="post" action="' . dcCore::app()->admin->getPageURL() . '">' .
        '<p>' . __('New section') . ' : ' .
        form::field('mymeta_section', 20, 255) .
        '&nbsp;<input type="submit" name="newsep" value="' . __('Create section') . '" />' .
        form::hidden(['p'], 'mymeta') .
        dcCore::app()->formNonce() .
        '</p>' .
        '</form>' .
        '</div>';

        dcPage::closeModule();
    }
}
