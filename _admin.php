<?php
/**
 * @brief mymeta, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @author Bruno Hondelatte and contributors
 *
 * @copyright GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
if (!defined('DC_CONTEXT_ADMIN')) {
    return;
}

require __DIR__ . '/class.mymeta.php';
require __DIR__ . '/_widgets.php';

dcCore::app()->menu['Plugins']->addItem(
    __('My Metadata'),
    'plugin.php?p=mymeta',
    'index.php?pf=mymeta/mymeta.png',
    preg_match('/plugin.php\?p=mymeta(&.*)?$/', $_SERVER['REQUEST_URI']),
    dcCore::app()->auth->check('usage,contentadmin', dcCore::app()->blog->id)
);

dcCore::app()->addBehavior('adminPostFormSidebar', ['mymetaBehaviors','mymetaSidebar']);
dcCore::app()->addBehavior('adminPostForm', ['mymetaBehaviors','mymetaInForm']);
dcCore::app()->addBehavior('adminPostForm', ['mymetaBehaviors','mymetaPostHeader']);

dcCore::app()->addBehavior('adminAfterPostCreate', ['mymetaBehaviors','setMymeta']);
dcCore::app()->addBehavior('adminAfterPostUpdate', ['mymetaBehaviors','setMymeta']);

dcCore::app()->addBehavior('adminPageFormSidebar', ['mymetaBehaviors','mymetaSidebar']);
dcCore::app()->addBehavior('adminPageForm', ['mymetaBehaviors','mymetaInForm']);

dcCore::app()->addBehavior('adminPostsActionsCombo', ['mymetaBehaviors','adminPostsActionsCombo']);
dcCore::app()->addBehavior('adminPostsActions', ['mymetaBehaviors','adminPostsActions']);
dcCore::app()->addBehavior('adminPostsActionsContent', ['mymetaBehaviors','adminPostsActionsContent']);
dcCore::app()->addBehavior('adminPostsActionsHeaders', ['mymetaBehaviors','adminPostsActionsHeaders']);

dcCore::app()->addBehavior('adminAfterPageCreate', ['mymetaBehaviors','setMymeta']);
dcCore::app()->addBehavior('adminAfterPageUpdate', ['mymetaBehaviors','setMymeta']);

# BEHAVIORS
class mymetaBehaviors
{
    public static function mymetaPostHeader($post)
    {
        $mymeta = new myMeta(dcCore::app());
        echo $mymeta->postShowHeader($post);
    }

    public static function mymetaSidebar($post)
    {
    }

    public static function mymetaInForm($post)
    {
        $mymeta = new myMeta(dcCore::app());
        if ($mymeta->hasMeta()) {
            echo $mymeta->postShowForm($post);
        }
    }

    public static function setMymeta($cur, $post_id)
    {
        $mymeta = new myMeta(dcCore::app());
        $mymeta->setMeta($post_id, $_POST);
    }

    public static function adminPostsActionsCombo($args)
    {
        $args[0][__('MyMeta')] = [__('Set MyMeta') => 'mymeta_set'];
    }

    public static function adminPostsActionsHeaders()
    {
        $mymeta = new myMeta(dcCore::app());

        return $mymeta->postShowHeader(null, true);
    }

    public static function adminPostsActions($core, $posts, $action, $redir)
    {
        if ($action == 'mymeta_set' && !empty($_POST['mymeta_ok'])) {
            $mymeta = new myMeta(dcCore::app());
            if ($mymeta->hasMeta()) {
                while ($posts->fetch()) {
                    $mymeta->setMeta($posts->post_id, $_POST, false);
                }
            }
            http::redirect($redir);
        }
    }

    public static function adminPostsActionsContent($core, $action, $hidden_fields)
    {
        if ($action == 'mymeta_set') {
            $mymeta = new myMeta(dcCore::app());
            if ($mymeta->hasMeta()) {
                echo '<h2>' . __('Set Metadata') . '</h2>' .
                    '<form action="posts_actions.php" method="post">' .
                    $mymeta->postShowForm(null) .
                    '<p><input type="submit" value="' . __('save') . '" />' .
                    $hidden_fields .
                    dcCore::app()->formNonce() .
                    form::hidden(['action'], 'mymeta_set') .
                    form::hidden(['mymeta_ok'], '1') . '</p></form>';
            }
        }
    }
}
