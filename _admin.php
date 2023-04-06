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

use Dotclear\Helper\Network\Http;

if (!defined('DC_CONTEXT_ADMIN')) {
    return;
}

require_once __DIR__ . '/class.mymeta.php';
require_once __DIR__ . '/_widgets.php';

dcCore::app()->menu[dcAdmin::MENU_PLUGINS]->addItem(
    __('My Metadata'),
    'plugin.php?p=mymeta',
    'index.php?pf=mymeta/mymeta.png',
    preg_match('/plugin.php\?p=mymeta(&.*)?$/', $_SERVER['REQUEST_URI']),
    dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
        dcAuth::PERMISSION_USAGE,
        dcAuth::PERMISSION_CONTENT_ADMIN,
    ]), dcCore::app()->blog->id)
);

dcCore::app()->addBehaviors([
    'adminPostFormSidebar' => ['mymetaBehaviors','mymetaSidebar'],
    'adminPostForm'        => ['mymetaBehaviors','mymetaInForm'],

    'adminAfterPostCreate' => ['mymetaBehaviors','setMymeta'],
    'adminAfterPostUpdate' => ['mymetaBehaviors','setMymeta'],

    'adminPageFormSidebar' => ['mymetaBehaviors','mymetaSidebar'],
    'adminPageForm'        => ['mymetaBehaviors','mymetaInForm'],

    'adminPostsActionsCombo'   => ['mymetaBehaviors','adminPostsActionsCombo'],
    'adminPostsActionsV2'      => ['mymetaBehaviors','adminPostsActions'],
    'adminPostsActionsContent' => ['mymetaBehaviors','adminPostsActionsContent'],
    'adminPostsActionsHeaders' => ['mymetaBehaviors','adminPostsActionsHeaders'],

    'adminAfterPageCreate' => ['mymetaBehaviors','setMymeta'],
    'adminAfterPageUpdate' => ['mymetaBehaviors','setMymeta'],
]);
dcCore::app()->addBehaviors([
    'adminPostForm' => ['mymetaBehaviors','mymetaPostHeader'],
]);

# BEHAVIORS
class mymetaBehaviors
{
    public static function mymetaPostHeader($post)
    {
        $mymeta = new myMeta();
        echo $mymeta->postShowHeader($post);
    }

    public static function mymetaSidebar($post)
    {
    }

    public static function mymetaInForm($post)
    {
        $mymeta = new myMeta();
        if ($mymeta->hasMeta()) {
            echo $mymeta->postShowForm($post);
        }
    }

    public static function setMymeta($cur, $post_id)
    {
        $mymeta = new myMeta();
        $mymeta->setMeta($post_id, $_POST);
    }

    public static function adminPostsActionsCombo($args)
    {
        $args[0][__('MyMeta')] = [__('Set MyMeta') => 'mymeta_set'];
    }

    public static function adminPostsActionsHeaders()
    {
        $mymeta = new myMeta();

        return $mymeta->postShowHeader(null, true);
    }

    public static function adminPostsActions($posts, $action, $redir)
    {
        if ($action == 'mymeta_set' && !empty($_POST['mymeta_ok'])) {
            $mymeta = new myMeta();
            if ($mymeta->hasMeta()) {
                while ($posts->fetch()) {
                    $mymeta->setMeta($posts->post_id, $_POST, false);
                }
            }
            Http::redirect($redir);
        }
    }

    public static function adminPostsActionsContent($core, $action, $hidden_fields)
    {
        if ($action == 'mymeta_set') {
            $mymeta = new myMeta();
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
