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

class FrontendTemplateCode
{
    /**
     * PHP code for tpl:MyMetaTypePrompt value
     *
     * @param      array<int|string, mixed>     $_params_  The parameters
     */
    public static function MyMetaURL(
        array $_params_,
        string $_tag_
    ): void {
        echo \Dotclear\Core\Frontend\Ctx::global_filters(
            App::blog()->url() . App::url()->getBase('mymeta') . '/' . App::frontend()->context()->mymeta->id . '/' . rawurlencode((string) App::frontend()->context()->meta->meta_id),
            $_params_,
            $_tag_
        );
    }

    /**
     * PHP code for tpl:MyMetaTypePrompt value
     *
     * @param      array<int|string, mixed>     $_params_  The parameters
     */
    public static function MetaType(
        array $_params_,
        string $_tag_
    ): void {
        echo \Dotclear\Core\Frontend\Ctx::global_filters(
            App::frontend()->context()->meta->meta_type,
            $_params_,
            $_tag_
        );
    }

    /**
     * PHP code for tpl:MyMetaTypePrompt value
     *
     * @param      array<int|string, mixed>     $_params_  The parameters
     */
    public static function MyMetaTypePrompt(
        string $_id_,
        array $_params_,
        string $_tag_
    ): void {
        if ($_id_ !== '') {
            App::frontend()->context()->mymeta = App::frontend()->mymeta->getByID($_id_);
        }
        if (App::frontend()->context()->mymeta !== null && App::frontend()->context()->mymeta->enabled) {
            echo \Dotclear\Core\Frontend\Ctx::global_filters(
                App::frontend()->context()->mymeta->prompt,
                $_params_,
                $_tag_
            );
        }
        if ($_id_ !== '') {
            App::frontend()->context()->mymeta = null;
        }
    }

    /**
     * PHP code for tpl:EntryMyMetaValue value
     *
     * @param      array<int|string, mixed>     $_attr_    The parameters
     * @param      array<int|string, mixed>     $_params_  The parameters
     */
    public static function EntryMyMetaValue(
        string $_id_,
        array $_attr_,
        array $_params_,
        string $_tag_
    ): void {
        if ($_id_ !== '') {
            App::frontend()->context()->mymeta = App::frontend()->mymeta->getByID($_id_);
        }
        if (App::frontend()->context()->mymeta !== null && App::frontend()->context()->mymeta->enabled) {
            echo \Dotclear\Core\Frontend\Ctx::global_filters(
                App::frontend()->context()->mymeta->getValue(
                    App::frontend()->mymeta->dcmeta->getMetaStr(
                        App::frontend()->context()->posts->post_meta,
                        App::frontend()->context()->mymeta->id
                    ),
                    $_attr_
                ),
                $_params_,
                $_tag_
            );
        }
        if ($_id_ !== '') {
            App::frontend()->context()->mymeta = null;
        }
    }

    /**
     * PHP code for tpl:MyMetaValue value
     *
     * @param      array<int|string, mixed>     $_attr_    The parameters
     * @param      array<int|string, mixed>     $_params_  The parameters
     */
    public static function MyMetaValue(
        string $_id_,
        array $_attr_,
        array $_params_,
        string $_tag_
    ): void {
        if ($_id_ !== '') {
            App::frontend()->context()->mymeta = App::frontend()->mymeta->getByID($_id_);
        }
        if (App::frontend()->context()->mymeta !== null && App::frontend()->context()->mymeta->enabled) {
            echo \Dotclear\Core\Frontend\Ctx::global_filters(
                App::frontend()->context()->mymeta->getValue(
                    App::frontend()->context()->meta->meta_id,
                    $_attr_
                ),
                $_params_,
                $_tag_
            );
        }
        if ($_id_ !== '') {
            App::frontend()->context()->mymeta = null;
        }
    }

    /**
     * PHP code for tpl:EntryMyMetaIf block
     */
    public static function EntryMyMetaIf(
        string $_id_,
        string $_test_,
        string $_content_HTML,
    ): void {
        if ($_id_ !== '') {
            App::frontend()->context()->mymeta = App::frontend()->mymeta->getByID($_id_);
        }
        if (App::frontend()->context()->mymeta != null && App::frontend()->context()->mymeta->enabled) {
            $mymeta_value = App::frontend()->mymeta->dcmeta->getMetaStr(App::frontend()->context()->posts->post_meta, App::frontend()->context()->mymeta->id);
            /* @phpstan-ignore-next-line */
            if (($_test_) === true) : ?>
                $_content_HTML
            <?php endif;
            unset($mymeta_value);
        }
        if ($_id_ !== '') {
            App::frontend()->context()->mymeta = null;
        }
    }

    /**
     * PHP code for tpl:EntryMyMetaIf block
     */
    public static function MyMetaData(
        string $_id_,
        mixed $_limit_,
        string $_sortby_,
        string $_order_,
        string $_content_HTML,
    ): void {
        if ($_id_ !== '') {
            App::frontend()->context()->mymeta = App::frontend()->mymeta->getByID($_id_);
        }
        App::frontend()->context()->meta = App::meta()->computeMetaStats(App::frontend()->mymeta->dcmeta->getMetadata([
            'meta_type' => App::frontend()->context()->mymeta->id,
            'limit'     => $_limit_,
        ]));
        App::frontend()->context()->meta->sort($_sortby_, $_order_);
        while (App::frontend()->context()->meta->fetch()) {
            App::frontend()->context()->mymeta = App::frontend()->mymeta->getByID(App::frontend()->context()->meta->meta_type); ?>
            $_content_HTML
            <?php App::frontend()->context()->mymeta = null;
        }
        App::frontend()->context()->meta = null;
        if ($_id_ !== '') {
            App::frontend()->context()->mymeta = null;
        }
    }
}
