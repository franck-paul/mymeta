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

use ArrayObject;
use dcCore;
use dcPage;
use dcPostsActions;
use Dotclear\Helper\Html\Html;
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

    public static function adminPostsActions(dcPostsActions $ap)
    {
        $ap->addAction(
            [__('MyMeta') => [__('Set MyMeta') => 'mymeta_set']],
            [static::class, 'adminSetMyMeta']
        );
    }

    public static function adminSetMyMeta(dcPostsActions $ap, ArrayObject $post)
    {
        if (!empty($post['mymeta_ok'])) {
            // Cope with submission
            $posts  = $ap->getRS();
            $mymeta = new MyMeta();
            if ($mymeta->hasMeta()) {
                while ($posts->fetch()) {
                    $mymeta->setMeta($posts->post_id, $_POST, false);
                }
            }
            $ap->redirect(true, ['upd' => 1]);
        } else {
            $mymeta = new MyMeta();
            $head   = $mymeta->postShowHeader(null, true);

            $ap->beginPage(
                dcPage::breadcrumb(
                    [
                        Html::escapeHTML(dcCore::app()->blog->name) => '',
                        __('Entries')                               => $ap->getRedirection(true),
                        __('Set MyMeta')                            => '',
                    ]
                ),
                $head
            );

            echo
            '<form action="' . $ap->getURI() . '" method="post">' .
            $ap->getCheckboxes() .
            $mymeta->postShowForm(null) .
            dcCore::app()->formNonce() . $ap->getHiddenFields() .
            form::hidden(['action'], 'mymeta_set') .
            form::hidden(['mymeta_ok'], '1') . '</p>' .
            '<p><input type="submit" value="' . __('save') . '" name="set_mymeta" />' .
            '</form>';

            $ap->endPage();
        }
    }
}
