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

class ManageEditSection extends dcNsProcess
{
    protected static $init = false; /** @deprecated since 2.27 */
    /**
     * Initializes the page.
     */
    public static function init(): bool
    {
        static::$init = My::checkContext(My::MANAGE) && (($_REQUEST['m'] ?? 'mymeta') === 'editsection');

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

        if (!empty($_POST['saveconfig'])) {
            try {
                $mymetaid     = Html::escapeHTML($_POST['mymeta_id']);
                $mymetaprompt = Html::escapeHTML($_POST['mymeta_prompt']);

                $mymetaSection = dcCore::app()->admin->mymeta->getByID($mymetaid);
                if ($mymetaSection instanceof MyMetaSection) {
                    $mymetaSection->prompt = $mymetaprompt;
                    dcCore::app()->admin->mymeta->update($mymetaSection);
                    dcCore::app()->admin->mymeta->store();
                }
                dcPage::addSuccessNotice(__('Section has been successfully updated'));
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

        if (array_key_exists('id', $_REQUEST)) {
            $page_title    = __('Edit section');
            $mymetaid      = $_REQUEST['id'];
            $mymetasection = dcCore::app()->admin->mymeta->getByID($_REQUEST['id']);
            if (!($mymetasection instanceof MyMetaSection)) {
                dcPage::addErrorNotice(__('Something went wrong while editing section'));
                dcCore::app()->adminurl->redirect('admin.plugin.' . My::id());
                exit;
            }
        } else {
            dcPage::addErrorNotice(__('Something went wrong while editing section'));
            dcCore::app()->adminurl->redirect('admin.plugin.' . My::id());
            exit;
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
        '<h3>' . __('MyMeta section definition') . '</h3>' .
        '<p>' .
        '<label class="required">' . __('Title') . ' ' .
        form::field('mymeta_prompt', 20, 255, $mymetasection->prompt, '', '') .
        '</label>' .
        '</p>' .
        '</div>' .
        '<p>' .
        '<input type="hidden" name="p" value="mymeta" />' .
        '<input type="hidden" name="m" value="editsection" />';

        echo form::hidden('mymeta_id', $mymetaid) .
        dcCore::app()->formNonce() .
        '<input type="submit" name="saveconfig" value="' . __('Save') . '" />' .
        '</p>' .
        '</form>';

        dcPage::closeModule();
    }
}
