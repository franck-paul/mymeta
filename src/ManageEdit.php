<?php

/**
 * @brief mymeta, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @author Franck Paul and contributors
 *
 * @copyright Franck Paul contact@open-time.net
 * @copyright GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
declare(strict_types=1);

namespace Dotclear\Plugin\mymeta;

use Dotclear\App;
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
use Dotclear\Helper\Html\Form\Span;
use Dotclear\Helper\Html\Form\Submit;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Process\TraitProcess;
use Exception;

class ManageEdit
{
    use TraitProcess;

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

        $filterTplFile = fn (string $file): string => str_replace(['\\','/'], ['',''], trim($file));

        if (!empty($_POST['mymeta_id'])) {
            try {
                /**
                 * @var MyMeta
                 */
                $mymeta = App::backend()->mymeta;

                $id   = is_string($id = $_POST['mymeta_id']) ? $id : '';
                $id   = is_string($id = preg_replace('#[^a-zA-Z0-9_-]#', '', $id)) ? $id : '';
                $type = isset($_POST['mymeta_type']) && is_string($type = $_POST['mymeta_type']) ? $type : '';

                $field = $mymeta->newMyMeta($type, $id);
                if ($field instanceof MyMetaField) {
                    $field->id         = $id;
                    $field->post_types = false;

                    $restrict = isset($_POST['mymeta_restrict'])         && is_string($restrict = $_POST['mymeta_restrict']) ? $restrict : '';
                    $types    = isset($_POST['mymeta_restricted_types']) && is_string($types = $_POST['mymeta_restricted_types']) ? $types : '';
                    if ($restrict === 'yes' && $types !== '') {
                        $post_types = array_filter(explode(',', $types));
                        $stack      = [];
                        foreach ($post_types as $post_type) {
                            $post_type = trim(Html::escapeHTML($post_type));
                            if ($post_type !== '') {
                                $stack[] = $post_type;
                            }
                        }

                        $field->post_types = $stack;
                    }

                    $field->url_list_enabled   = isset($_POST['enable_list']);
                    $field->url_single_enabled = isset($_POST['enable_single']);

                    $single_tpl = isset($_POST['single_tpl']) && is_string($single_tpl = $_POST['single_tpl']) ? $single_tpl : 'mymeta.html';
                    $list_tpl   = isset($_POST['list_tpl'])   && is_string($list_tpl = $_POST['list_tpl']) ? $list_tpl : 'mymetas.html';

                    $field->tpl_single = $filterTplFile($single_tpl);
                    $field->tpl_list   = $filterTplFile($list_tpl);

                    $field->adminUpdate($_POST);
                    $mymeta->update($field);
                    $mymeta->store();

                    App::backend()->notices()->addSuccessNotice(sprintf(
                        __('Metadata "%s" has been successfully updated'),
                        Html::escapeHTML($id)
                    ));
                } else {
                    App::backend()->notices()->addErrorNotice(__('Something went wrong while editing metadata'));
                }

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

        /**
         * @var MyMeta
         */
        $mymeta = App::backend()->mymeta;

        $mymeta_type = '';
        $page_title  = '';
        $id          = '';
        $lock_id     = false;

        /**
         * @var ?MyMetaField
         */
        $mymetaentry = null;

        if (array_key_exists('id', $_REQUEST)) {
            $id          = isset($_REQUEST['id']) && is_string($id = $_REQUEST['id']) ? Html::escapeHTML($id) : '';
            $mymetaentry = $mymeta->getByID($id);
            if ($mymetaentry !== null) {
                $page_title  = __('Edit metadata') . ' "' . $mymetaentry->prompt . '"';
                $mymeta_type = $mymetaentry->getMetaTypeId();
                $lock_id     = true;
            }
        } elseif (!empty($_REQUEST['mymeta_type'])) {
            $mymeta_type = is_string($mymeta_type = $_REQUEST['mymeta_type']) ? Html::escapeHTML($mymeta_type) : '';
            $mymetaentry = $mymeta->newMyMeta($mymeta_type);
            if ($mymetaentry !== null) {
                $page_title = __('New metadata');
                $lock_id    = false;
            }
        }

        if ($mymetaentry === null) {
            App::backend()->notices()->addErrorNotice(__('Something went wrong while editing metadata'));
            My::redirect();
        }

        $types      = $mymeta->getTypesAsCombo();
        $type_label = $types !== null && array_search($mymeta_type, $types, true);
        if (!$type_label) {
            App::backend()->notices()->addErrorNotice(__('Something went wrong while editing metadata'));
            My::redirect();
        }

        $head = App::backend()->page()->jsPageTabs('mymeta');

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

        $buttons = [];
        if ($lock_id) {
            // Disabled fields are not included in $_POST[] on submit, so keep their values
            $buttons[] = (new Hidden(['mymeta_id'], $id));
        }

        if ($id !== '') {
            $buttons[] = (new Button(['back'], __('Back')))
                ->class(['go-back','reset','hidden-if-no-js']);
        } else {
            $buttons[] = (new Button(['back'], __('Cancel')))
                ->class(['go-back','reset','hidden-if-no-js']);
        }

        /**
         * @var MyMetaField
         */
        $field = $mymetaentry;

        $tpl_single = $field->tpl_single !== '' ? $field->tpl_single : 'mymeta.html';
        $tpl_list   = $field->tpl_list   !== '' ? $field->tpl_list : 'mymetas.html';

        $restrictions = $field->getRestrictions() ?: '';

        echo (new Form('meta-edit'))
            ->method('post')
            ->action(App::backend()->getPageURL())
            ->fields([
                (new Note())
                    ->class('form-note')
                    ->text(sprintf(__('Fields preceded by %s are mandatory.'), (new Span('*'))->class('required')->render())),
                (new Fieldset())
                    ->legend(new Legend(__('Metadata definition')))
                    ->fields([
                        (new Para())
                            ->items([
                                (new Input('mymeta_id'))
                                    ->size(20)
                                    ->maxlength(255)
                                    ->value($id)
                                    ->disabled($lock_id)
                                    ->label((new Label((new Span('*'))->render() . __('Identifier (as stored in meta_type in database):'), Label::OL_TF))
                                        ->class('required')),
                            ]),
                        (new Para())
                            ->items([
                                (new Input('mymeta_prompt'))
                                    ->size(40)
                                    ->maxlength(255)
                                    ->default($field->prompt)
                                    ->label(new Label(__('Prompt') . ' : ', Label::OL_TF)),
                            ]),
                        (new Note())
                            ->text(sprintf(__('Metadata type : %s'), __($mymeta_type))),
                        (new Text(null, $field->adminForm())),
                    ]),
                (new Fieldset())
                    ->legend(new Legend(__('Metadata URLs')))
                    ->fields([
                        (new Para())
                            ->items([
                                (new Checkbox('enable_list', $field->url_list_enabled))
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
                                (new Checkbox('enable_single', $field->url_single_enabled))
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
                               (new Radio(['mymeta_restrict'], $field->isRestrictionEnabled()))
                                   ->value('none')
                                   ->label(new Label(__('Display meta field for any post type'), Label::IL_FT)),
                           ]),
                        (new Para())
                           ->items([
                               (new Radio(['mymeta_restrict'], !$field->isRestrictionEnabled()))
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
                            'mymeta_enabled' => (string) $field->enabled,
                            'mymeta_type'    => $mymeta_type,
                            'm'              => 'edit',
                        ]),
                        ... $buttons,
                        (new Submit('saveconfig', __('Save'))),
                    ]),
            ])
        ->render();

        App::backend()->page()->closeModule();
    }
}
