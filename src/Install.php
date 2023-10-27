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
use Dotclear\Core\Process;
use Exception;

class Install extends Process
{
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

            if ($settings->mymeta_fields == null) {
                return true;
            }

            $backup = $settings->mymeta_fields;
            $fields = unserialize(base64_decode($settings->mymeta_fields));
            if (!is_array($fields) || count($fields) == 0) {
                return true;
            }

            if (get_class(current($fields)) != 'stdClass') {
                return true;
            }

            $mymeta = new MyMeta(true);
            foreach ($fields as $k => $v) {
                $newfield = $mymeta->newMyMeta($v->type);
                if ($newfield instanceof \Dotclear\Plugin\mymeta\MyMetaField) {
                    $newfield->id      = (string) $k;
                    $newfield->enabled = $v->enabled;
                    $newfield->prompt  = $v->prompt;
                    if ($v->type === 'list') {
                        $newfield->values = $v->values;
                    }

                    $mymeta->update($newfield);
                }
            }

            $mymeta->reorder();
            $mymeta->store();

            if ($settings->mymeta_fields_backup == null) {
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
