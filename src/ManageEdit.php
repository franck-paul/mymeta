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

class ManageEdit extends dcNsProcess
{
    /**
     * Initializes the page.
     */
    public static function init(): bool
    {
        static::$init = My::checkContext(My::MANAGE) && (($_REQUEST['m'] ?? 'mymeta') === 'edit');

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

        $filterTplFile = fn ($file) => str_replace(['\\','/'], ['',''], trim((string) $file));

        if (!empty($_POST['mymeta_id'])) {
            try {
                $mymetaid                = preg_replace('#[^a-zA-Z0-9_-]#', '', (string) $_POST['mymeta_id']);
                $mymetaEntry             = dcCore::app()->admin->mymeta->newMyMeta($_POST['mymeta_type'], $mymetaid);
                $mymetaEntry->id         = $mymetaid;
                $mymetaEntry->post_types = false;
                if (isset($_POST['mymeta_restrict']) && $_POST['mymeta_restrict'] == 'yes') {
                    if (isset($_POST['mymeta_restricted_types'])) {
                        $post_types = explode(',', $_POST['mymeta_restricted_types']);
                        array_walk($post_types, fn ($v) => trim(Html::escapeHTML($v)));
                        $mymetaEntry->post_types = $post_types;
                    }
                }
                $mymetaEntry->url_list_enabled   = isset($_POST['enable_list']);
                $mymetaEntry->url_single_enabled = isset($_POST['enable_single']);
                $mymetaEntry->tpl_single         = $filterTplFile($_POST['single_tpl']) ?: 'mymeta.html';
                $mymetaEntry->tpl_list           = $filterTplFile($_POST['list_tpl']) ?: 'mymetas.html';

                $mymetaEntry->adminUpdate($_POST);
                dcCore::app()->admin->mymeta->update($mymetaEntry);
                dcCore::app()->admin->mymeta->store();
                dcPage::addsuccessNotice(sprintf(
                    __('MyMeta "%s" has been successfully updated'),
                    Html::escapeHTML($mymetaid)
                ));
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

        $mymeta_type = '';
        $page_title  = '';
        $mymetaid    = '';
        $lock_id     = false;
        $mymetaentry = null;

        if (array_key_exists('id', $_REQUEST)) {
            $page_title  = __('Edit MyMeta');
            $mymetaid    = $_REQUEST['id'];
            $mymetaentry = dcCore::app()->admin->mymeta->getByID($_REQUEST['id']);
            if ($mymetaentry == null) {
                dcPage::addErrorNotice(__('Something went wrong while editing mymeta'));
                dcCore::app()->adminurl->redirect('admin.plugin.' . My::id());
                exit;
            }
            $mymeta_type = $mymetaentry->getMetaTypeId();
            $lock_id     = true;
        } elseif (!empty($_REQUEST['mymeta_type'])) {
            $mymeta_type = Html::escapeHTML($_REQUEST['mymeta_type']);
            $page_title  = __('New MyMeta');
            $mymetaentry = dcCore::app()->admin->mymeta->newMyMeta($mymeta_type);
            $mymetaid    = '';
            $lock_id     = false;
        }
        $types      = dcCore::app()->admin->mymeta->getTypesAsCombo();
        $type_label = array_search($mymeta_type, $types);
        if (!$type_label) {
            dcPage::addErrorNotice(__('Something went wrong while editing mymeta'));
            dcCore::app()->adminurl->redirect('admin.plugin.' . My::id());
        }

        $head = dcPage::jsPageTabs('mymeta');

        dcPage::openModule(__('My metadata'), $head);

        echo dcPage::breadcrumb(
            [
                Html::escapeHTML(dcCore::app()->blog->name) => '',
                __('My Metadata')                           => dcCore::app()->admin->getPageURL(),
                $page_title                                 => '',
            ]
        );
        echo dcPage::notices();

        // Form
        echo
        '<form method="post" action="' . dcCore::app()->admin->getPageURL() . '">' .
        '<div class="fieldset">' .
        '<h3>' . __('MyMeta definition') . '</h3>' .
        '<p>' .
        '<label class="required" for="mymeta_id">' . __('Identifier (as stored in meta_type in database):') . ' ' .
        '</label>' .
        form::field(['mymeta_id'], 20, 255, $mymetaid, '', '', $lock_id) .
        '</p>' .
        '<p>' .
        '<label for="mymeta_prompt">' . __('Prompt') . ' : ' . '</label>' .
        form::field(['mymeta_prompt'], 40, 255, $mymetaentry->prompt) .
        '</p>' .
        '<p>' .
        '<em>' . sprintf(__('MyMeta type : %s'), __($mymeta_type)) . '</em>' .
        '</p>' .
        $mymetaentry->adminForm() .
        '</div>' .
        '<div class="fieldset">' .
        '<h3>' . __('MyMeta URLs') . '</h3>';

        $base_url   = dcCore::app()->blog->url . dcCore::app()->url->getBase('mymeta') . '/' . $mymetaentry->id;
        $tpl_single = $mymetaentry->tpl_single;
        $tpl_list   = $mymetaentry->tpl_list;

        echo
        '<p><label class="classic" for="enable_list">' .
        form::checkbox(['enable_list'], 1, $mymetaentry->url_list_enabled) .
        __('Enable MyMeta values list public page') . '</label></p>' .
        '<p><label class="classic">' . __('List template file (leave empty for default mymetas.html)') . ' : </label>' .
        form::field(['list_tpl'], 40, 255, empty($tpl_list) ? 'mymetas.html' : $tpl_list) .
        '</p>' .
        '<p><label class="classic" for="enable_single">' .
        form::checkbox(['enable_single'], 1, $mymetaentry->url_single_enabled) .
        __('Enable single mymeta value public page') .
        '</label></p>' .
        '<p><label class="classic">' . __('Single template file (leave empty for default mymeta.html)') . ' : </label>' .
        form::field(['single_tpl'], 40, 255, empty($tpl_single) ? 'mymeta.html' : $tpl_single) .
        '</p>';

        echo
        '</div>' .
        '<div class="fieldset">' .
        '<h3>' . __('MyMeta restrictions') . '</h3>' .
        '<p>';

        echo '<label class="classic" for="mymeta_restrict">' . form::radio(['mymeta_restrict'], 'none', $mymetaentry->isRestrictionEnabled()) .
        __('Display meta field for any post type') . '</label></p>';
        echo '<p><label class="classic" for="mymeta_restrict">' . form::radio(['mymeta_restrict'], 'yes', !$mymetaentry->isRestrictionEnabled()) .
        __('Restrict to the following post types :');

        $restrictions = $mymetaentry->getRestrictions();
        echo form::field('mymeta_restricted_types', 40, 255, $restrictions ?: '') . '</label></p>';

        echo
        '</p>' .
        '</div>' .
        '<p>' .
        '<input type="hidden" name="p" value="mymeta" />' .
        '<input type="hidden" name="m" value="edit" />';

        if ($lock_id) {
            echo form::hidden(['mymeta_id'], $mymetaid);
        }
        echo form::hidden(['mymeta_enabled'], $mymetaentry->enabled);
        echo form::hidden(['mymeta_type'], $mymeta_type);
        echo dcCore::app()->formNonce();

        echo
        '<input type="submit" name="saveconfig" value="' . __('Save') . '" />' .
        '</p>' .
        '</form>';

        dcPage::closeModule();
    }
}