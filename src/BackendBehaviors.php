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
use Dotclear\App;
use Dotclear\Core\Backend\Action\ActionsPosts;
use Dotclear\Core\Backend\Page;
use Dotclear\Database\Cursor;
use Dotclear\Database\MetaRecord;
use Dotclear\Helper\Html\Html;
use form;

class BackendBehaviors
{
    public static function mymetaPostHeader(?MetaRecord $post): string
    {
        $mymeta = new MyMeta();
        echo $mymeta->postShowHeader($post);

        return '';
    }

    public static function mymetaSidebar(?MetaRecord $post): string
    {
        return '';
    }

    public static function mymetaInForm(?MetaRecord $post): string
    {
        $mymeta = new MyMeta();
        if ($mymeta->hasMeta()) {
            echo $mymeta->postShowForm($post);
        }

        return '';
    }

    public static function setMymeta(Cursor $cur, int $post_id): string
    {
        $mymeta = new MyMeta();
        $mymeta->setMeta($post_id, $_POST);

        return '';
    }

    public static function adminPostsActions(ActionsPosts $ap): string
    {
        $ap->addAction(
            [__('MyMeta') => [__('Set MyMeta') => 'mymeta_set']],
            static::adminSetMyMeta(...)
        );

        return '';
    }

    /**
     * @param      ActionsPosts                 $ap     Actions
     * @param      ArrayObject<string, mixed>   $post   The post
     */
    public static function adminSetMyMeta(ActionsPosts $ap, ArrayObject $post): void
    {
        if (!empty($post['mymeta_ok'])) {
            // Cope with submission
            $posts  = $ap->getRS();
            $mymeta = new MyMeta();
            if ($mymeta->hasMeta()) {
                while ($posts->fetch()) {
                    $mymeta->setMeta((int) $posts->post_id, $_POST, false);
                }
            }

            $ap->redirect(true, ['upd' => 1]);
        } else {
            $mymeta = new MyMeta();
            $head   = $mymeta->postShowHeader(null, true);

            $ap->beginPage(
                Page::breadcrumb(
                    [
                        Html::escapeHTML(App::blog()->name()) => '',
                        __('Entries')                         => $ap->getRedirection(true),
                        __('Set MyMeta')                      => '',
                    ]
                ),
                $head
            );

            echo
            '<form action="' . $ap->getURI() . '" method="post">' .
            $ap->getCheckboxes() .

            $mymeta->postShowForm(null) .
            App::nonce()->getFormNonce() .
            $ap->getHiddenFields() .
            form::hidden(['action'], 'mymeta_set') .
            form::hidden(['mymeta_ok'], '1') . '</p>' .
            '</p>' .
            '<p><input type="submit" value="' . __('save') . '" name="set_mymeta">' .
            '</form>';

            $ap->endPage();
        }
    }
}
