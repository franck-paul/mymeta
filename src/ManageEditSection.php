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
use Exception;
use form;

/**
 * @todo switch Helper/Html/Form/...
 */
class ManageEditSection extends Process
{
    /**
     * Initializes the page.
     */
    public static function init(): bool
    {
        App::backend()->mymeta = new MyMeta();

        return self::status(My::checkContext(My::MANAGE) && (($_REQUEST['m'] ?? 'mymeta') === 'editsection'));
    }

    /**
     * Processes the request(s).
     */
    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        if (!empty($_POST['saveconfig'])) {
            try {
                $mymetaid     = Html::escapeHTML($_POST['mymeta_id']);
                $mymetaprompt = Html::escapeHTML($_POST['mymeta_prompt']);

                $mymetaSection = App::backend()->mymeta->getByID($mymetaid);
                if ($mymetaSection instanceof MyMetaSection) {
                    $mymetaSection->prompt = $mymetaprompt;
                    App::backend()->mymeta->update($mymetaSection);
                    App::backend()->mymeta->store();
                }

                Notices::addSuccessNotice(__('Section has been successfully updated'));
                My::redirect();
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
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

        if (array_key_exists('id', $_REQUEST)) {
            $page_title    = __('Edit section');
            $mymetaid      = $_REQUEST['id'];
            $mymetasection = App::backend()->mymeta->getByID($_REQUEST['id']);
            if (!($mymetasection instanceof MyMetaSection)) {
                Notices::addErrorNotice(__('Something went wrong while editing section'));
                My::redirect();
                exit;
            }
        } else {
            Notices::addErrorNotice(__('Something went wrong while editing section'));
            My::redirect();
            exit;
        }

        $head = Page::jsPageTabs('mymeta');

        Page::openModule(My::name(), $head);

        echo Page::breadcrumb(
            [
                Html::escapeHTML(App::blog()->name()) => '',
                __('My Metadata')                     => App::backend()->getPageURL(),
                $page_title                           => '',
            ]
        );
        echo Notices::getNotices();

        // Form
        echo
        '<form method="post" action="' . App::backend()->getPageURL() . '">' .
        '<div class="fieldset">' .
        '<h3>' . __('MyMeta section definition') . '</h3>' .
        '<p>' .
        '<label class="required">' . __('Title') . ' ' .
        form::field('mymeta_prompt', 20, 255, $mymetasection->prompt, '', '') .
        '</label>' .
        '</p>' .
        '</div>' .
        '<p>' .
        '<input type="hidden" name="p" value="mymeta">' .
        '<input type="hidden" name="m" value="editsection">' .
        My::parsedHiddenFields([
            'mymeta_id' => $mymetaid,
        ]) .
        '<input type="submit" name="saveconfig" value="' . __('Save') . '">' .
        '</p>' .
        '</form>';

        Page::closeModule();
    }
}
