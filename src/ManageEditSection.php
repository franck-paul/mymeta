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
use Dotclear\Helper\Html\Form\Button;
use Dotclear\Helper\Html\Form\Form;
use Dotclear\Helper\Html\Form\Input;
use Dotclear\Helper\Html\Form\Label;
use Dotclear\Helper\Html\Form\Note;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Span;
use Dotclear\Helper\Html\Form\Submit;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Process\TraitProcess;
use Exception;

class ManageEditSection
{
    use TraitProcess;

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

                App::backend()->notices()->addSuccessNotice(__('Section has been successfully updated'));
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
            $mymetaid      = $_REQUEST['id'];
            $mymetasection = App::backend()->mymeta->getByID($_REQUEST['id']);
            if (!($mymetasection instanceof MyMetaSection)) {
                App::backend()->notices()->addErrorNotice(__('Something went wrong while editing section'));
                My::redirect();
                exit;
            }
        } else {
            App::backend()->notices()->addErrorNotice(__('Something went wrong while editing section'));
            My::redirect();
            exit;
        }

        $page_title = __('Edit section') . ' ' . $mymetasection->prompt;
        $head       = App::backend()->page()->jsPageTabs('mymeta');

        App::backend()->page()->openModule(My::name(), $head);

        echo App::backend()->page()->breadcrumb(
            [
                Html::escapeHTML(App::blog()->name()) => '',
                __('My Metadata')                     => App::backend()->getPageURL(),
                $page_title                           => '',
            ]
        );
        echo App::backend()->notices()->getNotices();

        // Form
        echo (new Form('section_edit'))
            ->method('post')
            ->action(App::backend()->getPageURL())
            ->fields([
                (new Note())
                    ->class('form-note')
                    ->text(sprintf(__('Fields preceded by %s are mandatory.'), (new Span('*'))->class('required')->render())),
                (new Para())
                    ->items([
                        (new Input('mymeta_prompt'))
                            ->size(20)
                            ->maxlength(255)
                            ->default($mymetasection->prompt)
                            ->label((new Label((new Span('*'))->render() . __('Title:'), Label::IL_TF))
                                ->class('required')),
                    ]),
                (new Para())
                    ->class('form-buttons')
                    ->items([
                        (new Submit('saveconfig', __('Save'))),
                        ... My::hiddenFields([
                            'm'         => 'editsection',
                            'mymeta_id' => $mymetaid,
                        ]),
                        (new Button(['back'], __('Back')))
                            ->class(['go-back','reset','hidden-if-no-js']),
                    ]),
            ])
        ->render();

        App::backend()->page()->closeModule();
    }
}
