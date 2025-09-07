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
use Dotclear\Helper\Html\Form\Caption;
use Dotclear\Helper\Html\Form\Checkbox;
use Dotclear\Helper\Html\Form\Div;
use Dotclear\Helper\Html\Form\Form;
use Dotclear\Helper\Html\Form\Img;
use Dotclear\Helper\Html\Form\Input;
use Dotclear\Helper\Html\Form\Label;
use Dotclear\Helper\Html\Form\Link;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Select;
use Dotclear\Helper\Html\Form\Set;
use Dotclear\Helper\Html\Form\Strong;
use Dotclear\Helper\Html\Form\Submit;
use Dotclear\Helper\Html\Form\Table;
use Dotclear\Helper\Html\Form\Tbody;
use Dotclear\Helper\Html\Form\Td;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Html\Form\Th;
use Dotclear\Helper\Html\Form\Thead;
use Dotclear\Helper\Html\Form\Tr;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Process\TraitProcess;
use Exception;

class Manage
{
    use TraitProcess;

    /**
     * Initializes the page.
     */
    public static function init(): bool
    {
        App::backend()->mymeta = new MyMeta();

        if (!empty($_REQUEST['m'])) {
            switch ($_REQUEST['m']) {
                case 'edit':
                    return self::status(My::checkContext(My::MANAGE) && ManageEdit::init());

                case 'editsection':
                    return self::status(My::checkContext(My::MANAGE) && ManageEditSection::init());

                case 'view':
                    return self::status(My::checkContext(My::MANAGE) && ManageView::init());

                case 'viewposts':
                    return self::status(My::checkContext(My::MANAGE) && ManageViewPosts::init());
            }
        }

        return self::status(My::checkContext(My::MANAGE));
    }

    /**
     * Processes the request(s).
     */
    public static function process(): bool
    {
        if (!self::status()) {
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

        App::backend()->mymeta = new MyMeta(true);
        if (App::backend()->mymeta->settings->mymeta_fields != null) {
            $backup = App::backend()->mymeta->settings->mymeta_fields;
            $fields = unserialize(base64_decode(App::backend()->mymeta->settings->mymeta_fields));
            if (is_array($fields) && $fields !== []
                                  && current($fields) instanceof \stdClass) {
                foreach ($fields as $k => $v) {
                    $newfield = App::backend()->mymeta->newMyMeta($v->type);
                    if ($newfield instanceof \Dotclear\Plugin\mymeta\MyMetaField) {
                        $newfield->id      = (string) $k;
                        $newfield->enabled = $v->enabled;
                        $newfield->prompt  = $v->prompt;
                        if ($v->type === 'list') {
                            $newfield->values = $v->values;
                        }

                        App::backend()->mymeta->update($newfield);
                    }
                }

                App::backend()->mymeta->reorder();
                App::backend()->mymeta->store();

                if (App::backend()->mymeta->settings->mymeta_fields_backup == null) {
                    App::backend()->mymeta->settings->put(
                        'mymeta_fields_backup',
                        $backup,
                        'string',
                        'MyMeta fields backup (0.3.x version)'
                    );
                }

                My::redirect();
            }
        }

        App::backend()->mymeta = new MyMeta();

        if (!empty($_POST['action']) && !empty($_POST['entries'])) {
            try {
                $entries = $_POST['entries'];
                $action  = (string) $_POST['action'];
                $msg     = '';
                if (preg_match('/^(enable|disable)$/', $action)) {
                    App::backend()->mymeta->setEnabled($entries, ($action === 'enable'));
                    $msg = ($action === 'enable') ?
                        __('Metadata entries have been successfully enabled')
                        : __('Metadata entries have been successfully disabled');
                } elseif (preg_match('/^(delete)$/', $action)) {
                    App::backend()->mymeta->delete($entries);
                    $msg = __('Metadata entries have been successfully deleted');
                }

                App::backend()->mymeta->store();

                Notices::addSuccessNotice($msg);
                My::redirect();
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
        }

        if (!empty($_POST['newsep']) && !empty($_POST['mymeta_section'])) {
            try {
                $section         = App::backend()->mymeta->newSection();
                $section->prompt = Html::escapeHTML($_POST['mymeta_section']);
                App::backend()->mymeta->update($section);
                App::backend()->mymeta->store();

                Notices::addSuccessNotice(sprintf(
                    __('Section "%s" has been successfully created'),
                    Html::escapeHTML($_POST['mymeta_section'])
                ));
                My::redirect();
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
        }

        /**
         * Order links
         *
         * @var        array<string>
         */
        $order = [];
        if (empty($_POST['mymeta_order']) && !empty($_POST['order'])) {
            $postOrder = $_POST['order'];
            asort($postOrder);
            $order = array_map(static fn (int|string $value): string => (string) $value, array_keys($postOrder));
        } elseif (!empty($_POST['mymeta_order'])) {
            $metaOrder = explode(',', (string) $_POST['mymeta_order']);
            $order     = $metaOrder;
        }

        if (!empty($_POST['saveorder']) && !empty($order)) {
            try {
                App::backend()->mymeta->reorder($order);
                App::backend()->mymeta->store();

                Notices::addSuccessNotice(__('Metadata have been successfully reordered'));
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

        $types = App::backend()->mymeta->getTypesAsCombo();

        $combo_action                = [];
        $combo_action[__('enable')]  = 'enable';
        $combo_action[__('disable')] = 'disable';
        $combo_action[__('delete')]  = 'delete';

        $metaStat = App::backend()->mymeta->getMyMetaStats();
        $stats    = [];
        while ($metaStat->fetch()) {
            $stats[$metaStat->meta_type] = $metaStat->count;
        }
        $all_metadata = App::backend()->mymeta->getAll();

        // Head

        $head = Page::jsLoad('js/jquery/jquery-ui.custom.js') .
        Page::jsLoad('js/jquery/jquery.ui.touch-punch.js') .
        My::jsLoad('_meta_lists.js');

        Page::openModule(My::name(), $head);

        echo Page::breadcrumb(
            [
                Html::escapeHTML(App::blog()->name()) => '',
                __('My Metadata')                     => '',
            ]
        );
        echo Notices::getNotices();

        // Form
        $metadata = function ($metadatas, $stats) {
            foreach ($metadatas as $meta) {
                if ($meta instanceof MyMetaSection) {
                    // Section
                    $pos = $meta->pos ?? 0;

                    yield (new Tr('l_' . $meta->id))
                        ->class('line')
                        ->cols([
                            (new Td())
                                ->class(['handle', 'minimal'])
                                ->items([
                                    (new Input(['order[' . $meta->id . ']']))
                                        ->class('position')
                                        ->size(2)
                                        ->maxlength(255)
                                        ->value($pos),
                                ]),
                            (new Td())
                                ->class('minimal')
                                ->items([
                                    (new Checkbox(['entries[]']))
                                        ->value($meta->id),
                                ]),
                            (new Td())
                                ->class(['nowrap', 'minimal', 'status'])
                                ->items([
                                    (new Link())
                                        ->href(App::backend()->url()->get('admin.plugin.' . My::id(), [
                                            'm'  => 'editsection',
                                            'id' => $meta->id,
                                        ], '&'))
                                        ->items([
                                            (new Img('images/menu/edit.svg'))
                                                ->class('icon-mini')
                                                ->alt(__('edit metadata')),
                                        ]),
                                ]),
                            (new Td())
                                ->class(['nowrap', 'maximal'])
                                ->colspan(6)
                                ->items([
                                    (new Strong(sprintf(__('Section: %s'), Html::escapeHTML($meta->prompt)))),
                                ]),
                        ]);
                } else {
                    // Metadata
                    $image = fn ($src, $label, $class) => (new Img('images/' . $src))
                        ->class(['mark', 'mark-' . $class])
                        ->alt($label)
                        ->title($label);
                    $image_status = $meta->enabled ?
                    $image('published.svg', __('published'), 'published') :
                    $image('unpublished.svg', __('unpublished'), 'unpublished');

                    $st           = $stats[$meta->id] ?? 0;
                    $restrictions = $meta->getRestrictions();
                    if (!$restrictions) {
                        $restrictions = __('All');
                    }

                    yield (new Tr('l_' . $meta->id))
                        ->class(['line', $meta->enabled ? '' : 'offline'])
                        ->cols([
                            (new Td())
                                ->class(['handle', 'minimal'])
                                ->items([
                                    (new Input(['order[' . $meta->id . ']']))
                                        ->class('position')
                                        ->size(2)
                                        ->maxlength(255)
                                        ->value($meta->pos),
                                ]),
                            (new Td())
                                ->class('minimal')
                                ->items([
                                    (new Checkbox(['entries[]']))
                                        ->value($meta->id),
                                ]),
                            (new Td())
                                ->class(['nowrap', 'minimal', 'status'])
                                ->items([
                                    (new Link())
                                        ->href(App::backend()->url()->get('admin.plugin.' . My::id(), [
                                            'm'  => 'edit',
                                            'id' => $meta->id,
                                        ], '&'))
                                        ->items([
                                            (new Img('images/menu/edit.svg'))
                                                ->class('icon-mini')
                                                ->alt(__('edit metadata')),
                                        ]),
                                ]),
                            (new Td())
                                ->class('nowrap')
                                ->items([
                                    (new Link())
                                        ->href(App::backend()->url()->get('admin.plugin.' . My::id(), [
                                            'm'  => 'view',
                                            'id' => $meta->id,
                                        ], '&'))
                                        ->text(Html::escapeHTML($meta->id)),
                                ]),
                            (new Td())
                                ->class('nowrap')
                                ->text($meta->getMetaTypeDesc()),
                            (new Td())
                                ->class(['nowrap', 'maximal'])
                                ->text($meta->prompt),
                            (new Td())
                                ->text($restrictions),
                            (new Td())
                                ->class('nowrap')
                                ->text($st . ' ' . (($st <= 1) ? __('entry') : __('entries'))),
                            (new Td())
                                ->class(['nowrap', 'minimal'])
                                ->items([
                                    $image_status,
                                ]),
                        ]);
                }
            }
        };

        echo (new Set())
            ->items([
                (new Para())
                    ->class('new-stuff')
                    ->items([
                        (new Link())
                            ->href('#new-meta')
                            ->class(['button', 'add'])
                            ->text(__('Add a metadata')),
                    ]),
                (new Form('mymeta-form'))
                    ->method('post')
                    ->action(App::backend()->getPageURL())
                    ->fields([
                        (new Table())
                            ->class('dragable')
                            ->caption(new Caption(__('Metadata list')))
                            ->thead((new Thead())
                                ->rows([
                                    (new Tr())
                                        ->cols([
                                            (new Th())
                                                ->colspan(4)
                                                ->text(__('ID')),
                                            (new Th())
                                                ->text(__('Type')),
                                            (new Th())
                                                ->text(__('Prompt')),
                                            (new Th())
                                                ->text(__('Posts')),
                                            (new Th())
                                                ->text(__('Count')),
                                            (new Th())
                                                ->colspan(2)
                                                ->text(__('Status')),
                                        ]),
                                ]))
                            ->tbody((new Tbody('mymeta-list'))
                                ->rows([
                                    ... $metadata($all_metadata, $stats),
                                ])),
                        (new Div())
                            ->class('two-cols')
                            ->items([
                                (new Para())
                                    ->class(['col', 'checkboxes-helpers']),
                                (new Para())
                                    ->class(['col', 'right', 'form-buttons'])
                                    ->items([
                                        (new Select('action'))
                                            ->items($combo_action)
                                            ->label(new Label(__('Selected metas action:'), Label::IL_TF)),
                                        (new Submit('action_submit', __('ok'))),
                                    ]),
                                (new Para())
                                    ->items([
                                        (new Submit('saveorder', __('Save order'))),
                                        ... My::hiddenFields([
                                            'mymeta_order' => '',
                                        ]),
                                    ]),
                            ]),
                    ]),
                (new Div('new-meta'))
                    ->class(['fieldset', 'clear'])
                    ->items([
                        (new Text('h3', __('New metadata'))),
                        (new Form('new_form'))
                            ->method('post')
                            ->action(App::backend()->getPageURL())
                            ->fields([
                                (new Para())
                                    ->class('field')
                                    ->items([
                                        (new Select('mymeta_type'))
                                            ->items($types)
                                            ->label((new Label(__('New metadata:'), Label::IL_TF))
                                                ->class('classic')),
                                        (new Submit('new', __('Create the metadata'))),
                                        ... My::hiddenFields([
                                            'm' => 'edit',
                                        ]),
                                    ]),
                            ]),
                        (new Form('newsep_form'))
                            ->method('post')
                            ->action(App::backend()->getPageURL())
                            ->fields([
                                (new Para())
                                    ->class('field')
                                    ->items([
                                        (new Input('mymeta_section'))
                                            ->size(20)
                                            ->maxlength(255)
                                            ->label((new Label(__('New section:'), Label::IL_TF))
                                                ->class('classic')),
                                        (new Submit('newsep', __('Create the section'))),
                                        ... My::hiddenFields(),
                                    ]),
                            ]),
                    ]),
            ])
        ->render();

        Page::closeModule();
    }
}
