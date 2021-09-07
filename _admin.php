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

require dirname(__FILE__) . '/class.mymeta.php';
require dirname(__FILE__) . '/_widgets.php';

$_menu['Plugins']->addItem(__('My Metadata'),'plugin.php?p=mymeta','index.php?pf=mymeta/mymeta.png',
        preg_match('/plugin.php\?p=mymeta(&.*)?$/', $_SERVER['REQUEST_URI']),
        $core->auth->check('usage,contentadmin', $core->blog->id));

$core->addBehavior('adminPostFormSidebar', ['mymetaBehaviors','mymetaSidebar']);
$core->addBehavior('adminPostForm', ['mymetaBehaviors','mymetaInForm']);
$core->addBehavior('adminPostForm', ['mymetaBehaviors','mymetaPostHeader']);

$core->addBehavior('adminAfterPostCreate', ['mymetaBehaviors','setMymeta']);
$core->addBehavior('adminAfterPostUpdate', ['mymetaBehaviors','setMymeta']);

$core->addBehavior('adminPageFormSidebar', ['mymetaBehaviors','mymetaSidebar']);
$core->addBehavior('adminPageForm', ['mymetaBehaviors','mymetaInForm']);

$core->addBehavior('adminPostsActionsCombo', ['mymetaBehaviors','adminPostsActionsCombo']);
$core->addBehavior('adminPostsActions', ['mymetaBehaviors','adminPostsActions']);
$core->addBehavior('adminPostsActionsContent', ['mymetaBehaviors','adminPostsActionsContent']);
$core->addBehavior('adminPostsActionsHeaders', ['mymetaBehaviors','adminPostsActionsHeaders']);

$core->addBehavior('adminAfterPageCreate', ['mymetaBehaviors','setMymeta']);
$core->addBehavior('adminAfterPageUpdate', ['mymetaBehaviors','setMymeta']);
# BEHAVIORS
class mymetaBehaviors
{
    public static function mymetaPostHeader($post)
    {
        $mymeta = new myMeta($GLOBALS['core']);

        echo $mymeta->postShowHeader($post);
    }
    public static function mymetaSidebar($post)
    {
    }

    public static function mymetaInForm($post)
    {
        $mymeta = new myMeta($GLOBALS['core']);
        if ($mymeta->hasMeta()) {
            echo $mymeta->postShowForm($post);
        }
    }

    public static function setMymeta($cur, $post_id)
    {
        $mymeta = new myMeta($GLOBALS['core']);
        $mymeta->setMeta($post_id, $_POST);
    }

    public static function adminPostsActionsCombo($args)
    {
        $args[0][__('MyMeta')] = [__('Set MyMeta') => 'mymeta_set'];
    }

    public static function adminPostsActionsHeaders()
    {
        $mymeta = new myMeta($GLOBALS['core']);

        return $mymeta->postShowHeader(null, true);
    }

    public static function adminPostsActions($core, $posts, $action, $redir)
    {
        if ($action == 'mymeta_set' && !empty($_POST['mymeta_ok'])) {
            $mymeta = new myMeta($GLOBALS['core']);
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
            $mymeta = new myMeta($core);
            if ($mymeta->hasMeta()) {
                echo '<h2>' . __('Set Metadata') . '</h2>' .
                    '<form action="posts_actions.php" method="post">' .
                    $mymeta->postShowForm(null) .
                    '<p><input type="submit" value="' . __('save') . '" />' .
                    $hidden_fields .
                    $core->formNonce() .
                    form::hidden(['action'], 'mymeta_set') .
                    form::hidden(['mymeta_ok'], '1') . '</p></form>';
            }
        }
    }
}

# REST
class mymetaRest
{
}
