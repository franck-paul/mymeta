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

class FrontendTemplateCode
{
    /**
     * PHP code for tpl:MyMetaURL value
     *
     * @param      array<int|string, mixed>     $_params_  The parameters
     */
    public static function MyMetaURL(
        array $_params_,
        string $_tag_
    ): void {
        $mymeta_id      = App::frontend()->context()->mymeta instanceof \Dotclear\Plugin\mymeta\MyMetaEntry ? App::frontend()->context()->mymeta->id : '';
        $mymeta_meta_id = App::frontend()->context()->meta instanceof \Dotclear\Database\MetaRecord && is_string($mymeta_meta_id = App::frontend()->context()->meta->meta_id) ? $mymeta_meta_id : '';
        if ($mymeta_id !== '' && $mymeta_meta_id !== '') {
            echo App::frontend()->context()::global_filters(
                App::blog()->url() . App::url()->getBase('mymeta') . '/' . $mymeta_id . '/' . rawurlencode($mymeta_meta_id),
                $_params_,
                $_tag_
            );
        }
        unset($mymeta_id, $mymeta_meta_id);
    }

    /**
     * PHP code for tpl:MetaType value
     *
     * @param      array<int|string, mixed>     $_params_  The parameters
     */
    public static function MetaType(
        array $_params_,
        string $_tag_
    ): void {
        $mymeta_type = App::frontend()->context()->meta instanceof \Dotclear\Database\MetaRecord && is_string($mymeta_type = App::frontend()->context()->meta->meta_type) ? $mymeta_type : '';
        echo App::frontend()->context()::global_filters(
            $mymeta_type,
            $_params_,
            $_tag_
        );
        unset($mymeta_type);
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
        if (App::frontend()->mymeta instanceof \Dotclear\Plugin\mymeta\MyMeta) {
            if ($_id_ !== '') {
                App::frontend()->context()->mymeta = App::frontend()->mymeta->getByID($_id_);
            }
            if (App::frontend()->context()->mymeta instanceof \Dotclear\Plugin\mymeta\MyMetaField && App::frontend()->context()->mymeta->enabled) {
                echo App::frontend()->context()::global_filters(
                    App::frontend()->context()->mymeta->prompt,
                    $_params_,
                    $_tag_
                );
            }
            if ($_id_ !== '') {
                App::frontend()->context()->mymeta = null;
            }
        }
    }

    /**
     * PHP code for tpl:EntryMyMetaValue value
     *
     * @param      array<string, string>        $_attr_    The parameters
     * @param      array<int|string, mixed>     $_params_  The parameters
     */
    public static function EntryMyMetaValue(
        string $_id_,
        array $_attr_,
        array $_params_,
        string $_tag_
    ): void {
        if (App::frontend()->mymeta instanceof \Dotclear\Plugin\mymeta\MyMeta && App::frontend()->context()->posts instanceof \Dotclear\Database\MetaRecord) {
            if ($_id_ !== '') {
                App::frontend()->context()->mymeta = App::frontend()->mymeta->getByID($_id_);
            }
            if (App::frontend()->context()->mymeta instanceof \Dotclear\Plugin\mymeta\MyMetaField && App::frontend()->context()->mymeta->enabled) {
                $mymeta_post_meta = is_string($mymeta_post_meta = App::frontend()->context()->posts->post_meta) ? $mymeta_post_meta : '';
                echo App::frontend()->context()::global_filters(
                    App::frontend()->context()->mymeta->getValue(
                        App::frontend()->mymeta->meta->getMetaStr(
                            $mymeta_post_meta,
                            App::frontend()->context()->mymeta->id
                        ),
                        $_attr_
                    ),
                    $_params_,
                    $_tag_
                );
                unset($mymeta_post_meta);
            }
            if ($_id_ !== '') {
                App::frontend()->context()->mymeta = null;
            }
        }
    }

    /**
     * PHP code for tpl:MyMetaValue value
     *
     * @param      array<string, mixed>         $_attr_    The parameters
     * @param      array<int|string, mixed>     $_params_  The parameters
     */
    public static function MyMetaValue(
        string $_id_,
        array $_attr_,
        array $_params_,
        string $_tag_
    ): void {
        if (App::frontend()->mymeta instanceof \Dotclear\Plugin\mymeta\MyMeta) {
            if ($_id_ !== '') {
                App::frontend()->context()->mymeta = App::frontend()->mymeta->getByID($_id_);
            }
            if (App::frontend()->context()->mymeta instanceof \Dotclear\Plugin\mymeta\MyMetaField && App::frontend()->context()->mymeta->enabled) {
                $mymeta_meta_id = App::frontend()->context()->meta instanceof \Dotclear\Database\MetaRecord && is_string($mymeta_meta_id = App::frontend()->context()->meta->meta_id) ? $mymeta_meta_id : '';
                if ($mymeta_meta_id !== '') {
                    echo App::frontend()->context()::global_filters(
                        App::frontend()->context()->mymeta->getValue(
                            $mymeta_meta_id,
                            $_attr_
                        ),
                        $_params_,
                        $_tag_
                    );
                }
                unset($mymeta_meta_id);
            }
            if ($_id_ !== '') {
                App::frontend()->context()->mymeta = null;
            }
        }
    }

    /**
     * PHP code for tpl:EntryMyMetaIf block
     */
    public static function EntryMyMetaIf(
        string $_id_,
        string $_test_HTML,
        string $_content_HTML,
    ): void {
        if (App::frontend()->mymeta instanceof \Dotclear\Plugin\mymeta\MyMeta && App::frontend()->context()->posts instanceof \Dotclear\Database\MetaRecord) {
            if ($_id_ !== '') {
                App::frontend()->context()->mymeta = App::frontend()->mymeta->getByID($_id_);
            }
            if (App::frontend()->context()->mymeta instanceof \Dotclear\Plugin\mymeta\MyMetaField && App::frontend()->context()->mymeta->enabled) {
                $mymeta_post_meta = is_string($mymeta_post_meta = App::frontend()->context()->posts->post_meta) ? $mymeta_post_meta : '';
                $mymeta_value     = App::frontend()->mymeta->meta->getMetaStr(
                    $mymeta_post_meta,
                    App::frontend()->context()->mymeta->id
                );
                /* @phpstan-ignore-next-line */
                if (($_test_HTML) === true) : ?>
                $_content_HTML
            <?php endif;
                unset($mymeta_value, $mymeta_post_meta);
            }
            if ($_id_ !== '') {
                App::frontend()->context()->mymeta = null;
            }
        }
    }

    /**
     * PHP code for tpl:MyMetaData block
     */
    public static function MyMetaData(
        string $_id_,
        mixed $_limit_,
        string $_sortby_,
        string $_order_,
        string $_content_HTML,
    ): void {
        if (App::frontend()->mymeta instanceof \Dotclear\Plugin\mymeta\MyMeta) {
            if ($_id_ !== '') {
                App::frontend()->context()->mymeta = App::frontend()->mymeta->getByID($_id_);
            }
            $mymeta_id = App::frontend()->context()->mymeta instanceof \Dotclear\Plugin\mymeta\MyMetaEntry ? App::frontend()->context()->mymeta->id : '';
            if ($_limit_) {
                App::frontend()->context()->meta = App::meta()->computeMetaStats(App::frontend()->mymeta->meta->getMetadata([
                    'meta_type' => $mymeta_id,
                    'limit'     => $_limit_,
                ]));
            } else {
                App::frontend()->context()->meta = App::meta()->computeMetaStats(App::frontend()->mymeta->meta->getMetadata([
                    'meta_type' => $mymeta_id,
                ]));
            }
            unset($mymeta_id);
            App::frontend()->context()->meta->sort($_sortby_, $_order_);
            while (App::frontend()->context()->meta->fetch()) {
                $mymeta_type = is_string($mymeta_type = App::frontend()->context()->meta->meta_type) ? $mymeta_type : '';

                App::frontend()->context()->mymeta = App::frontend()->mymeta->getByID($mymeta_type); ?>
            $_content_HTML
            <?php App::frontend()->context()->mymeta = null;
            }
            App::frontend()->context()->meta = null;
            unset($mymeta_type);
            if ($_id_ !== '') {
                App::frontend()->context()->mymeta = null;
            }
        }
    }
}
