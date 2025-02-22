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
use Dotclear\Helper\Html\Form\Button;
use Dotclear\Helper\Html\Form\Checkbox;
use Dotclear\Helper\Html\Form\Fieldset;
use Dotclear\Helper\Html\Form\Form;
use Dotclear\Helper\Html\Form\Hidden;
use Dotclear\Helper\Html\Form\Input;
use Dotclear\Helper\Html\Form\Label;
use Dotclear\Helper\Html\Form\Legend;
use Dotclear\Helper\Html\Form\Note;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Radio;
use Dotclear\Helper\Html\Form\Submit;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Html\Html;
use Exception;

class ManageEdit extends Process
{
    /**
     * Initializes the page.
     */
    public static function init(): bool
    {
        App::backend()->mymeta = new MyMeta();

        return self::status(My::checkContext(My::MANAGE) && (($_REQUEST['m'] ?? 'mymeta') === 'edit'));
    }

    /**
     * Processes the request(s).
     */
    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        $filterTplFile = static fn ($file): string => str_replace(['\\','/'], ['',''], trim((string) $file));

        if (!empty($_POST['mymeta_id'])) {
            try {
                $mymetaid                = preg_replace('#[^a-zA-Z0-9_-]#', '', (string) $_POST['mymeta_id']);
                $mymetaEntry             = App::backend()->mymeta->newMyMeta($_POST['mymeta_type'], $mymetaid);
                $mymetaEntry->id         = $mymetaid;
                $mymetaEntry->post_types = false;
                if (isset($_POST['mymeta_restrict']) && $_POST['mymeta_restrict'] == 'yes' && isset($_POST['mymeta_restricted_types'])) {
                    $post_types = explode(',', (string) $_POST['mymeta_restricted_types']);
                    array_walk($post_types, static fn ($v): string => trim(Html::escapeHTML($v)));
                    $mymetaEntry->post_types = $post_types;
                }

                $mymetaEntry->url_list_enabled   = isset($_POST['enable_list']);
                $mymetaEntry->url_single_enabled = isset($_POST['enable_single']);
                $mymetaEntry->tpl_single         = $filterTplFile($_POST['single_tpl']) ?: 'mymeta.html';
                $mymetaEntry->tpl_list           = $filterTplFile($_POST['list_tpl']) ?: 'mymetas.html';

                $mymetaEntry->adminUpdate($_POST);
                App::backend()->mymeta->update($mymetaEntry);
                App::backend()->mymeta->store();

                Notices::addsuccessNotice(sprintf(
                    __('Metadata "%s" has been successfully updated'),
                    Html::escapeHTML($mymetaid)
                ));
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

        $mymeta_type = '';
        $page_title  = '';
        $mymetaid    = '';
        $lock_id     = false;
        $mymetaentry = null;

        if (array_key_exists('id', $_REQUEST)) {
            $mymetaid    = $_REQUEST['id'];
            $mymetaentry = App::backend()->mymeta->getByID($_REQUEST['id']);
            if ($mymetaentry == null) {
                Notices::addErrorNotice(__('Something went wrong while editing metadata'));
                My::redirect();
                exit;
            }

            $page_title  = __('Edit metadata') . ' "' . $mymetaentry->prompt . '"';
            $mymeta_type = $mymetaentry->getMetaTypeId();
            $lock_id     = true;
        } elseif (!empty($_REQUEST['mymeta_type'])) {
            $mymeta_type = Html::escapeHTML($_REQUEST['mymeta_type']);
            $page_title  = __('New metadata');
            $mymetaentry = App::backend()->mymeta->newMyMeta($mymeta_type);
            $mymetaid    = '';
            $lock_id     = false;
        }

        $types      = App::backend()->mymeta->getTypesAsCombo();
        $type_label = array_search($mymeta_type, $types, true);
        if (!$type_label) {
            Notices::addErrorNotice(__('Something went wrong while editing metadata'));
            My::redirect();
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

        $buttons = [];
        if ($lock_id) {
            // Disabled fields are not included in $_POST[] on submit, so keep their values
            $buttons[] = (new Hidden(['mymeta_id'], $mymetaid));
        }
        if ($mymetaid !== '') {
            $buttons[] = (new Button(['back'], __('Back')))
                ->class(['go-back','reset','hidden-if-no-js']);
        } else {
            $buttons[] = (new Button(['back'], __('Cancel')))
                ->class(['go-back','reset','hidden-if-no-js']);
        }

        $tpl_single   = $mymetaentry->tpl_single ?: 'mymeta.html';
        $tpl_list     = $mymetaentry->tpl_list ?: 'mymetas.html';
        $restrictions = $mymetaentry->getRestrictions() ?: '';

        echo (new Form('meta-edit'))
            ->method('post')
            ->action(App::backend()->getPageURL())
            ->fields([
                (new Note())
                    ->class('form-note')
                    ->text(sprintf(__('Fields preceded by %s are mandatory.'), (new Text('span', '*'))->class('required')->render())),
                (new Fieldset())
                    ->legend(new Legend(__('Metadata definition')))
                    ->fields([
                        (new Para())
                            ->items([
                                (new Input('mymeta_id'))
                                    ->size(20)
                                    ->maxlength(255)
                                    ->value($mymetaid)
                                    ->disabled($lock_id)
                                    ->label((new Label((new Text('span', '*'))->render() . __('Identifier (as stored in meta_type in database):'), Label::OL_TF))
                                        ->class('required')),
                            ]),
                        (new Para())
                            ->items([
                                (new Input('mymeta_prompt'))
                                    ->size(40)
                                    ->maxlength(255)
                                    ->default($mymetaentry->prompt)
                                    ->label(new Label(__('Prompt') . ' : ', Label::OL_TF)),
                            ]),
                        (new Note())
                            ->text(sprintf(__('Metadata type : %s'), __($mymeta_type))),
                        (new Text(null, $mymetaentry->adminForm())),
                    ]),
                (new Fieldset())
                    ->legend(new Legend(__('Metadata URLs')))
                    ->fields([
                        (new Para())
                            ->items([
                                (new Checkbox('enable_list', $mymetaentry->url_list_enabled))
                                    ->value(1)
                                    ->label(new Label(__('Enable metadata values list public page'), Label::IL_FT)),
                            ]),
                        (new Para())
                            ->items([
                                (new Input('list_tpl'))
                                    ->size(40)
                                    ->maxlength(255)
                                    ->default($tpl_list)
                                    ->label(new Label(__('List template file (leave empty for default mymetas.html)'), Label::OL_TF)),
                            ]),
                        (new Para())
                            ->items([
                                (new Checkbox('enable_single', $mymetaentry->url_single_enabled))
                                    ->value(1)
                                    ->label(new Label(__('Enable single metadata value public page'), Label::IL_FT)),
                            ]),
                        (new Para())
                            ->items([
                                (new Input('single_tpl'))
                                    ->size(40)
                                    ->maxlength(255)
                                    ->default($tpl_single)
                                    ->label(new Label(__('Single template file (leave empty for default mymeta.html)'), Label::OL_TF)),
                            ]),
                    ]),
                (new Fieldset())
                    ->legend(new Legend(__('Metadata restrictions')))
                    ->fields([
                        (new Para())
                           ->items([
                               (new Radio(['mymeta_restrict'], $mymetaentry->isRestrictionEnabled()))
                                   ->value('none')
                                   ->label(new Label(__('Display meta field for any post type'), Label::IL_FT)),
                           ]),
                        (new Para())
                           ->items([
                               (new Radio(['mymeta_restrict'], !$mymetaentry->isRestrictionEnabled()))
                                    ->value('none')
                                    ->label((new Label(__('Restrict to the following post types :') . ' ', Label::IL_FT))
                                        ->class('classic')),
                               (new Input('mymeta_restricted_types'))
                                    ->size(40)
                                    ->maxlength(255)
                                    ->default($restrictions),
                           ]),
                    ]),
                (new Para())
                    ->class('form-buttons')
                    ->items([
                        ... My::hiddenFields([
                            'mymeta_enabled' => $mymetaentry->enabled,
                            'mymeta_type'    => $mymeta_type,
                            'm'              => 'edit',
                        ]),
                        ... $buttons,
                        (new Submit('saveconfig', __('Save'))),
                    ]),
            ])
        ->render();

        Page::closeModule();
    }
}
