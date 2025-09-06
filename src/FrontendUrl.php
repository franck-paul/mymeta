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
use Dotclear\Core\Url;

class FrontendUrl extends Url
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
                App::frontend()->setPageNumber($n);
            }

            $values = explode('/', (string) $args);
            $mymeta = App::frontend()->mymeta->getByID($values[0]);

            if ($mymeta == null || !$mymeta->enabled) {
                self::p404();
            }

            App::frontend()->context()->mymeta = $mymeta;

            if (count($values) === 1) {
                // Meta list
                $tpl = $mymeta->tpl_list ?: 'mymetas.html';

                if ($mymeta->url_list_enabled && App::frontend()->template()->getFilePath($tpl)) {
                    self::serveDocument($tpl);
                } else {
                    self::p404();
                }
            } else {
                // Meta value
                $mymeta_value = $values[1];

                $rs = App::frontend()->mymeta->dcmeta->getMetadata([
                    'meta_type' => $mymeta->id,
                    'meta_id'   => $mymeta_value,
                ]);
                App::frontend()->context()->meta = App::meta()->computeMetaStats($rs);

                $tpl = $mymeta->tpl_single ?: 'mymeta.html';

                if (!App::frontend()->context()->meta->isEmpty() && $mymeta->url_single_enabled && App::frontend()->template()->getFilePath($tpl)) {
                    self::serveDocument($tpl);
                } else {
                    self::p404();
                }
            }
        }
    }
}
