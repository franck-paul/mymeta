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
use Dotclear\Core\Backend\Listing\ListingPosts;
use Dotclear\Core\Backend\Notices;
use Dotclear\Core\Backend\Page;
use Dotclear\Core\Process;
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
use Exception;

class ManageViewPosts extends Process
{
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
            Notices::addErrorNotice(__('Something went wrong while editing metadata value'));
            My::redirect();
        }

        App::backend()->mymetaEntry = App::backend()->mymeta->getByID($_GET['id']);
        if (App::backend()->mymetaEntry == null) {
            Notices::addErrorNotice(__('Something went wrong while editing metadata value'));
            My::redirect();
        }

        $value = rawurldecode((string) $_GET['value']);

        App::backend()->posts_actions_page = new BackendActions(
            App::backend()->url()->get('admin.plugin'),
            ['p' => My::id(), 'm' => 'viewposts', 'id' => App::backend()->mymetaEntry->id]
        );

        App::backend()->posts_actions_page_rendered = null;
        if (App::backend()->posts_actions_page->process()) {
            App::backend()->posts_actions_page_rendered = true;

            return true;
        }

        // Rename a tag
        if (!empty($_POST['rename'])) {
            $new_value = $_POST['mymeta_' . App::backend()->mymetaEntry->id];

            try {
                if (App::backend()->mymeta->dcmeta->updateMeta($value, $new_value, App::backend()->mymetaEntry->id)) {
                    Notices::addSuccessNotice(sprintf(
                        __('Metadata value successfully updated from "%s" to "%s"'),
                        Html::escapeHTML($value),
                        Html::escapeHTML($new_value)
                    ));
                    My::redirect([
                        'm'      => 'view',
                        'id'     => App::backend()->mymetaEntry->id,
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
                App::backend()->mymeta->dcmeta->delMeta($value, App::backend()->mymetaEntry->id);
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

        if (App::backend()->posts_actions_page_rendered) {
            App::backend()->posts_actions_page->render();

            return;
        }

        $value = rawurldecode((string) $_GET['value']);

        $this_url = App::backend()->getPageURL() . '&amp;m=viewposts&amp;id=' . App::backend()->mymetaEntry->id . '&amp;value=' . rawurlencode($value);

        $page        = empty($_GET['page']) ? 1 : $_GET['page'];
        $nb_per_page = 30;

        $params               = [];
        $params['limit']      = [(($page - 1) * $nb_per_page),$nb_per_page];
        $params['no_content'] = true;

        $params['meta_id']   = $value;
        $params['meta_type'] = App::backend()->mymetaEntry->id;

        $params['post_type'] = '';

        # Get posts
        $post_list = null;
        $posts     = null;

        try {
            $posts     = App::backend()->mymeta->dcmeta->getPostsByMeta($params);
            $counter   = App::backend()->mymeta->dcmeta->getPostsByMeta($params, true);
            $post_list = new ListingPosts($posts, $counter->f(0));
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

        # --BEHAVIOR-- adminPostsActionsCombo
        App::behavior()->callBehavior('adminPostsActionsCombo', [&$combo_action]);

        $head = Page::jsLoad('js/_posts_list.js') .
        Page::jsJson('mymeta', ['msg' => __('Are you sure you want to remove this metadata?')]) .
        My::jsLoad('mymeta.js') .
        Page::jsPageTabs('mymeta') .
        App::backend()->mymetaEntry->postHeader(null, true);

        Page::openModule(My::name(), $head);

        echo Page::breadcrumb(
            [
                Html::escapeHTML(App::blog()->name())               => '',
                __('My Metadata')                                   => App::backend()->getPageURL(),
                Html::escapeHTML(App::backend()->mymetaEntry->id)   => App::backend()->getPageURL() . '&m=view&id=' . App::backend()->mymetaEntry->id,
                sprintf(__('Value "%s"'), Html::escapeHTML($value)) => '',
            ]
        );
        echo Notices::getNotices();

        // Form
        echo (new Text('h3', sprintf(__('Entries having metadata id "%s" set to "%s"'), Html::escapeHTML(App::backend()->mymetaEntry->id), Html::escapeHTML($value))))->render();

        // Show posts
        if ($post_list instanceof ListingPosts) {
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
                                            ->items(App::backend()->posts_actions_page->getCombo())
                                            ->label(new Label(__('Selected entries action:'), Label::IL_TF)),
                                        (new Submit('form-entries-submit', __('ok'))),
                                        ... My::hiddenFields([
                                            'post_type' => '',
                                            'm'         => 'serie_posts',
                                            'id'        => App::backend()->mymetaEntry->id,
                                        ]),
                                    ]),
                            ]),
                    ])
                ->render()
            );
        }

        // Remove tag
        if (!$posts->isEmpty() && App::auth()->check(App::auth()->makePermissions([
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

        if (!$posts->isEmpty()) {
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
                            App::backend()->mymetaEntry->postForm(App::backend()->mymeta->dcmeta, null, Html::escapeHTML($value), true),
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

        Page::closeModule();
    }
}
