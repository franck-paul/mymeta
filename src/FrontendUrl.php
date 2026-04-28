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
use Dotclear\Core\Url;

class FrontendUrl extends Url
{
    /**
     * @param      null|string  $args   The arguments
     */
    public static function tag(?string $args): void
    {
        $n = self::getPageNumber($args);
        if ($args === '' && !$n) {
            self::p404();
        } else {
            $args = is_string($args) ? $args : '';
            if ($args === '') {
                self::p404();
            }

            if ($n) {
                App::frontend()->setPageNumber($n);
            }

            if (!App::frontend()->mymeta instanceof MyMeta) {
                self::p404();
            }

            /**
             * @var MyMeta
             */
            $mymeta = App::frontend()->mymeta;

            $values = explode('/', $args);
            $field  = $mymeta->getByID($values[0]);

            if ($field === null || !$field instanceof MyMetaField || !$field->enabled) {
                self::p404();
            }

            App::frontend()->context()->mymeta = $field;

            if (count($values) === 1) {
                // Meta list
                $tpl = $field->tpl_list ?: 'mymetas.html';

                if ($field->url_list_enabled && App::frontend()->template()->getFilePath($tpl)) {
                    self::serveDocument($tpl);
                } else {
                    self::p404();
                }
            } else {
                // Meta value
                $mymeta_value = $values[1];

                $rs = $mymeta->meta->getMetadata([
                    'meta_type' => $field->id,
                    'meta_id'   => $mymeta_value,
                ]);
                App::frontend()->context()->meta = App::meta()->computeMetaStats($rs);

                $tpl = $field->tpl_single ?: 'mymeta.html';

                if (!App::frontend()->context()->meta->isEmpty() && $field->url_single_enabled && App::frontend()->template()->getFilePath($tpl)) {
                    self::serveDocument($tpl);
                } else {
                    self::p404();
                }
            }
        }
    }
}
