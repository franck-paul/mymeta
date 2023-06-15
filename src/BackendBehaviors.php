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

use dcCore;
use Dotclear\Helper\Network\Http;
use form;

class BackendBehaviors
{
    public static function mymetaPostHeader($post)
    {
        $mymeta = new MyMeta();
        echo $mymeta->postShowHeader($post);
    }

    public static function mymetaSidebar($post)
    {
    }

    public static function mymetaInForm($post)
    {
        $mymeta = new MyMeta();
        if ($mymeta->hasMeta()) {
            echo $mymeta->postShowForm($post);
        }
    }

    public static function setMymeta($cur, $post_id)
    {
        $mymeta = new MyMeta();
        $mymeta->setMeta($post_id, $_POST);
    }

    public static function adminPostsActionsCombo($args)
    {
        $args[0][__('MyMeta')] = [__('Set MyMeta') => 'mymeta_set'];
    }

    public static function adminPostsActionsHeaders()
    {
        $mymeta = new MyMeta();

        return $mymeta->postShowHeader(null, true);
    }

    public static function adminPostsActions($posts, $action, $redir)
    {
        if ($action == 'mymeta_set' && !empty($_POST['mymeta_ok'])) {
            $mymeta = new MyMeta();
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
            $mymeta = new MyMeta();
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
