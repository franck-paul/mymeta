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
use Dotclear\Helper\Process\TraitProcess;
use Exception;
use stdClass;

class Install
{
    use TraitProcess;

    public static function init(): bool
    {
        return self::status(My::checkContext(My::INSTALL));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        try {
            // Init
            $settings = My::settings();

            $fields = is_string($fields = $settings->mymeta_fields) ? $fields : '';

            if ($fields === '') {
                return true;
            }

            $backup = $fields;

            try {
                $fields = unserialize(base64_decode($fields));
            } catch (Exception $exception) {
                App::error()->add($exception->getMessage());
            }

            if (!is_array($fields) || count($fields) === 0) {
                return true;
            }

            $item = current($fields);
            if (is_object($item) && !$item instanceof stdClass) {
                // Not in old format
                return true;
            }

            $mymeta = new MyMeta(true);
            foreach ($fields as $key => $value) {
                if ($value instanceof stdClass) {
                    $type = is_string($type = $value->type) ? $type : '';
                    if ($type !== '') {
                        $newfield = $mymeta->newMyMeta($type);
                        if ($newfield instanceof MyMetaField) {
                            $enabled = is_bool($enabled = $value->enabled) && $enabled;
                            $prompt  = is_string($prompt = $value->prompt) ? $prompt : '';

                            $newfield->id      = (string) $key;
                            $newfield->enabled = $enabled;
                            $newfield->prompt  = $prompt;
                            if ($type === 'list' && is_array($value->values)) {
                                $values = [];
                                foreach ($value->values as $k => $v) {
                                    $values[(string) $k] = is_string($v) ? $v : '';
                                }
                                $newfield->values = $values;
                            }

                            $mymeta->update($newfield);
                        }
                    }
                }
            }

            $mymeta->reorder();
            $mymeta->store();

            if ($settings->mymeta_fields_backup === null) {
                $settings->put(
                    'mymeta_fields_backup',
                    $backup,
                    App::blogWorkspace()::NS_STRING,
                    'MyMeta fields backup (0.3.x version)'
                );
            }

            return true;
        } catch (Exception $exception) {
            App::error()->add($exception->getMessage());
        }

        return true;
    }
}
