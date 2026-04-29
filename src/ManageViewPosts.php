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
use Dotclear\Database\MetaRecord;
use Dotclear\Helper\Html\Form\Div;
use Dotclear\Helper\Html\Form\Fieldset;
use Dotclear\Helper\Html\Form\Form;
use Dotclear\Helper\Html\Form\Label;
use Dotclear\Helper\Html\Form\Legend;
use Dotclear\Helper\Html\Form\Note;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Select;
use Dotclear\Helper\Html\Form\Submit;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Process\TraitProcess;
use Exception;

class ManageViewPosts
{
    use TraitProcess;

    /**
     * Initializes the page.
     */
    public static function init(): bool
    {
        App::backend()->mymeta = new MyMeta();

        return self::status(My::checkContext(My::MANAGE) && (($_REQUEST['m'] ?? 'mymeta') === 'viewposts'));
    }

    /**
     * Processes the request(s).
     */
    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        if (empty($_GET['id']) || empty($_GET['value'])) {
            App::backend()->notices()->addErrorNotice(__('Something went wrong while editing metadata value'));
            My::redirect();
        }

        /**
         * @var MyMeta
         */
        $mymeta = App::backend()->mymeta;

        App::backend()->mymetaEntry = null;

        $id = isset($_GET['id']) && is_string($id = $_GET['id']) ? $id : '';
        if ($id !== '') {
            App::backend()->mymetaEntry = $mymeta->getByID($id);
        }
        if (App::backend()->mymetaEntry === null) {
            App::backend()->notices()->addErrorNotice(__('Something went wrong while editing metadata value'));
            My::redirect();
        }

        /**
         * @var MyMetaField $mymetaEntry
         */
        $mymetaEntry = App::backend()->mymetaEntry;

        $value = isset($_GET['value']) && is_string($value = $_GET['value']) ? rawurldecode($value) : '';

        App::backend()->posts_actions_page = new BackendActions(
            App::backend()->url()->get('admin.plugin'),
            [
                'p'  => My::id(),
                'm'  => 'viewposts',
                'id' => $mymetaEntry->id,
            ]
        );

        App::backend()->posts_actions_page_rendered = null;
        if (App::backend()->posts_actions_page->process()) {
            App::backend()->posts_actions_page_rendered = true;

            return true;
        }

        // Rename a tag
        if (!empty($_POST['rename'])) {
            $new_value = isset($_POST['mymeta_' . $mymetaEntry->id]) && is_string($new_value = $_POST['mymeta_' . $mymetaEntry->id]) ? $new_value : '';

            try {
                if ($mymeta->meta->updateMeta($value, $new_value, $mymetaEntry->id)) {
                    App::backend()->notices()->addSuccessNotice(sprintf(
                        __('Metadata value successfully updated from "%1$s" to "%2$s"'),
                        Html::escapeHTML($value),
                        Html::escapeHTML($new_value)
                    ));
                    My::redirect([
                        'm'      => 'view',
                        'id'     => $mymetaEntry->id,
                        'status' => 'valchg',
                    ]);
                }
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
        }

        // Delete a tag
        if (!empty($_POST['delete']) && App::auth()->check(App::auth()->makePermissions([
            App::auth()::PERMISSION_PUBLISH,
            App::auth()::PERMISSION_CONTENT_ADMIN,
        ]), App::blog()->id())) {
            try {
                $mymeta->meta->delMeta($value, $mymetaEntry->id);
                My::redirect([
                    'm'   => 'view',
                    'del' => 1,
                ]);
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
         * @var BackendActions
         */
        $pap = App::backend()->posts_actions_page;
        if (App::backend()->posts_actions_page_rendered) {
            $pap->render();

            return;
        }

        /**
         * @var MyMeta
         */
        $mymeta = App::backend()->mymeta;

        /**
         * @var MyMetaField $mymetaEntry
         */
        $mymetaEntry = App::backend()->mymetaEntry;

        $value = isset($_GET['value']) && is_string($value = $_GET['value']) ? rawurldecode($value) : '';

        $this_url = App::backend()->getPageURL() . '&amp;m=viewposts&amp;id=' . $mymetaEntry->id . '&amp;value=' . rawurlencode($value);

        $page        = isset($_GET['page']) && is_numeric($page = $_GET['page']) ? (int) $page : 1;
        $nb_per_page = 30;

        $params          = [];
        $params['limit'] = [
            ($page - 1) * $nb_per_page,     // Offset
            $nb_per_page,                   // Limit
        ];
        $params['no_content'] = true;

        $params['meta_id']   = $value;
        $params['meta_type'] = $mymetaEntry->id;

        $params['post_type'] = '';

        # Get posts
        $post_list = null;
        $posts     = null;

        try {
            $posts     = $mymeta->meta->getPostsByMeta($params);
            $counter   = $mymeta->meta->getPostsByMeta($params, true);
            $post_list = App::backend()->listing()->posts($posts, $counter->cardinal());
        } catch (Exception $exception) {
            App::error()->add($exception->getMessage());
        }

        # Actions combo box
        $combo_action = [];
        if (App::auth()->check(App::auth()->makePermissions([
            App::auth()::PERMISSION_PUBLISH,
            App::auth()::PERMISSION_CONTENT_ADMIN,
        ]), App::blog()->id())) {
            $combo_action[__('Status')] = [
                __('Publish')         => 'publish',
                __('Unpublish')       => 'unpublish',
                __('Schedule')        => 'schedule',
                __('Mark as pending') => 'pending',
            ];
        }

        $combo_action[__('Mark')] = [
            __('Mark as selected')   => 'selected',
            __('Mark as unselected') => 'unselected',
        ];
        $combo_action[__('Change')] = [__('Change category') => 'category'];
        if (App::auth()->check(App::auth()->makePermissions([
            App::auth()::PERMISSION_ADMIN,
        ]), App::blog()->id())) {
            $combo_action[__('Change')] = array_merge(
                $combo_action[__('Change')],
                [__('Change author') => 'author']
            );
        }

        if (App::auth()->check(App::auth()->makePermissions([
            App::auth()::PERMISSION_DELETE,
            App::auth()::PERMISSION_CONTENT_ADMIN,
        ]), App::blog()->id())) {
            $combo_action[__('Delete')] = [__('Delete') => 'delete'];
        }

        /**
         * @var array<string, array<string, string>>
         */
        $combo_backend_action = $pap->getCombo();

        # --BEHAVIOR-- adminPostsActionsCombo
        App::behavior()->callBehavior('adminPostsActionsCombo', [&$combo_action]);

        $head = App::backend()->page()->jsLoad('js/_posts_list.js') .
        App::backend()->page()->jsJson('mymeta', ['msg' => __('Are you sure you want to remove this metadata?')]) .
        My::jsLoad('mymeta.js') .
        App::backend()->page()->jsPageTabs('mymeta') .
        $mymetaEntry->postHeader(null, true);

        App::backend()->page()->openModule(My::name(), $head);

        echo App::backend()->page()->breadcrumb(
            [
                Html::escapeHTML(App::blog()->name())               => '',
                __('My Metadata')                                   => App::backend()->getPageURL(),
                Html::escapeHTML($mymetaEntry->id)                  => App::backend()->getPageURL() . '&m=view&id=' . $mymetaEntry->id,
                sprintf(__('Value "%s"'), Html::escapeHTML($value)) => '',
            ]
        );
        echo App::backend()->notices()->getNotices();

        // Form
        echo (new Text('h3', sprintf(__('Entries having metadata id "%1$s" set to "%2$s"'), Html::escapeHTML($mymetaEntry->id), Html::escapeHTML($value))))->render();

        // Show posts
        if ($post_list) {
            $post_list->display(
                $page,
                $nb_per_page,
                (new Form('form-entries'))
                    ->method('post')
                    ->action(App::backend()->getPageURL())
                    ->fields([
                        (new Text(null, '%s')),  // Post list comes here
                        (new Div())
                            ->class('two-cols')
                            ->items([
                                (new Para())
                                    ->class(['col', 'checkboxes-helpers']),
                                (new Para())
                                    ->class(['col', 'right', 'form-buttons'])
                                    ->items([
                                        (new Select('action'))
                                            ->items($combo_backend_action)
                                            ->label(new Label(__('Selected entries action:'), Label::IL_TF)),
                                        (new Submit('form-entries-submit', __('ok'))),
                                        ... My::hiddenFields([
                                            'post_type' => '',
                                            'm'         => 'serie_posts',
                                            'id'        => $mymetaEntry->id,
                                        ]),
                                    ]),
                            ]),
                    ])
                ->render()
            );
        }

        // Remove tag
        if ($posts instanceof MetaRecord && !$posts->isEmpty() && App::auth()->check(App::auth()->makePermissions([
            App::auth()::PERMISSION_CONTENT_ADMIN,
        ]), App::blog()->id())) {
            echo (new Form('tag_delete'))
                ->method('post')
                ->action($this_url)
                ->fields([
                    (new Para())
                        ->class('form-buttons')
                        ->items([
                            (new Submit('delete', __('Delete this tag'))),
                            ... My::hiddenFields(),
                        ]),
                ])
            ->render();
        }

        if ($posts instanceof MetaRecord && !$posts->isEmpty()) {
            echo (new Form('tag_rename'))
                ->method('post')
                ->action($this_url)
                ->fields([
                    (new Fieldset())
                        ->legend(new Legend(__('Change metadata value')))
                        ->fields([
                            (new Note())
                                ->class('info')
                                ->text(__('This will change the meta value for all entries having this value')),
                            $mymetaEntry->postForm($mymeta->meta, null, Html::escapeHTML($value), true),
                            (new Para())
                                ->class('form-buttons')
                                ->items([
                                    (new Submit('rename', __('Save'))),
                                    ... My::hiddenFields([
                                        'value' => Html::escapeHTML($value),
                                    ]),
                                ]),
                        ]),
                ])
            ->render();
        }

        App::backend()->page()->closeModule();
    }
}
