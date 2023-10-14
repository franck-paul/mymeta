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
use dcUrlHandlers;

class FrontendUrl extends dcUrlHandlers
{
    /**
     * @param      null|string  $args   The arguments
     */
    public static function tag(?string $args): void
    {
        $n = self::getPageNumber($args);
        if ($args == '' && !$n) {
            self::p404();
        } else {
            if ($n) {
                dcCore::app()->public->setPageNumber($n);
            }
            $values = explode('/', $args);
            $mymeta = dcCore::app()->mymeta->getByID($values[0]);
            if ($mymeta == null || !$mymeta->enabled) {
                self::p404();
            }
            dcCore::app()->ctx->mymeta = $mymeta;

            if (sizeof($values) == 1) {
                $tpl = ($mymeta->tpl_list == '') ? 'mymetas.html' : $mymeta->tpl_list;
                if ($mymeta->url_list_enabled && dcCore::app()->tpl->getFilePath($tpl)) {
                    self::serveDocument($tpl);
                } else {
                    self::p404();
                }
            } else {
                $mymeta_value = $values[1];

                dcCore::app()->ctx->meta = dcCore::app()->meta->computeMetaStats(dcCore::app()->mymeta->dcmeta->getMetadata([
                    'meta_id'   => $mymeta->id,
                    'meta_type' => $mymeta_value,
                ]));

                $tpl = ($mymeta->tpl_single == '') ? 'mymeta.html' : $mymeta->tpl_single;
                if (!dcCore::app()->ctx->meta->isEmpty() && $mymeta->url_single_enabled && dcCore::app()->tpl->getFilePath($tpl)) {
                    self::serveDocument($tpl);
                } else {
                    self::p404();
                }
            }
        }
    }
}
