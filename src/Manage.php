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

        App::backend()->mymeta = new MyMeta();

        $mymeta = App::backend()->mymeta;

        if (!empty($_POST['action']) && !empty($_POST['entries'])) {
            try {
                $action = is_string($action = $_POST['action']) ? $action : '';

                /**
                 * @var array<string>
                 */
                $entries = is_array($entries = $_POST['entries']) ? $entries : [];

                $msg = '';
                if (preg_match('/^(enable|disable)$/', $action)) {
                    $mymeta->setEnabled($entries, $action === 'enable');
                    $msg = $action === 'enable' ?
                        __('Metadata entries have been successfully enabled') :
                        __('Metadata entries have been successfully disabled');
                } elseif (preg_match('/^(delete)$/', $action)) {
                    $mymeta->delete($entries);
                    $msg = __('Metadata entries have been successfully deleted');
                }

                $mymeta->store();

                App::backend()->notices()->addSuccessNotice($msg);
                My::redirect();
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
        }

        if (!empty($_POST['newsep']) && !empty($_POST['mymeta_section'])) {
            try {
                $prompt = is_string($prompt = $_POST['mymeta_section']) ? Html::escapeHTML($prompt) : '';

                $section         = $mymeta->newSection();
                $section->prompt = $prompt;
                $mymeta->update($section);
                $mymeta->store();

                App::backend()->notices()->addSuccessNotice(sprintf(__('Section "%s" has been successfully created'), $prompt));
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
        if (empty($_POST['mymeta_order']) && !empty($_POST['order']) && is_array($_POST['order'])) {
            $post_order = $_POST['order'];
            asort($post_order);
            $order = array_map(static fn (int|string $value): string => (string) $value, array_keys($post_order));
        } elseif (!empty($_POST['mymeta_order'])) {
            $meta_order = is_string($meta_order = $_POST['mymeta_order']) ? explode(',', $meta_order) : [];
            $order      = $meta_order;
        }
        $order = array_filter($order);

        if (!empty($_POST['saveorder']) && $order !== []) {
            try {
                $mymeta->reorder($order);
                $mymeta->store();

                App::backend()->notices()->addSuccessNotice(__('Metadata have been successfully reordered'));
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

        /**
         * @var MyMeta
         */
        $mymeta = App::backend()->mymeta;

        $types = $mymeta->getTypesAsCombo() ?? [];

        $combo_action                = [];
        $combo_action[__('enable')]  = 'enable';
        $combo_action[__('disable')] = 'disable';
        $combo_action[__('delete')]  = 'delete';

        $meta_stat = $mymeta->getMyMetaStats();
        $stats     = [];
        while ($meta_stat->fetch()) {
            $meta_type = is_string($meta_type = $meta_stat->meta_type) ? $meta_type : '';
            if ($meta_type !== '') {
                $stats[$meta_type] = $meta_stat->count;
            }
        }
        $all_metadata = $mymeta->getAll();

        // Head

        $head = App::backend()->page()->jsLoad('js/jquery/jquery-ui.custom.js') .
        App::backend()->page()->jsLoad('js/jquery/jquery.ui.touch-punch.js') .
        My::jsLoad('_meta_lists.js');

        App::backend()->page()->openModule(My::name(), $head);

        echo App::backend()->page()->breadcrumb(
            [
                Html::escapeHTML(App::blog()->name()) => '',
                __('My Metadata')                     => '',
            ]
        );
        echo App::backend()->notices()->getNotices();

        // Form
        $metadata = function (array $metadatas, array $stats) {
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
                } elseif ($meta instanceof MyMetaField) {
                    // Metadata
                    $image = fn (string $src, string $label, string $class): Img => (new Img('images/' . $src))
                        ->class(['mark', 'mark-' . $class])
                        ->alt($label)
                        ->title($label);

                    $image_status = $meta->enabled ?
                        $image('published.svg', __('published'), 'published') :
                        $image('unpublished.svg', __('unpublished'), 'unpublished');

                    $st = is_numeric($st = $stats[$meta->id] ?? 0) ? (int) $st : 0;

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
                                ->text($st . ' ' . ($st <= 1 ? __('entry') : __('entries'))),
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

        App::backend()->page()->closeModule();
    }
}
