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
use dcNamespace;
use dcNsProcess;
use Exception;

class Install extends dcNsProcess
{
    public static function init(): bool
    {
        static::$init = My::checkContext(My::INSTALL);

        return static::$init;
    }

    public static function process(): bool
    {
        if (!static::$init) {
            return false;
        }

        try {
            // Init
            $settings = dcCore::app()->blog->settings->get(My::id());

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
                $newfield          = $mymeta->newMyMeta($v->type);
                $newfield->id      = $k;
                $newfield->enabled = $v->enabled;
                $newfield->prompt  = $v->prompt;
                switch ($v->type) {
                    case 'list':
                        $newfield->values = $v->values;

                        break;
                }
                $mymeta->update($newfield);
            }
            $mymeta->reorder();
            $mymeta->store();

            if ($settings->mymeta_fields_backup == null) {
                $settings->put(
                    'mymeta_fields_backup',
                    $backup,
                    dcNamespace::NS_STRING,
                    'MyMeta fields backup (0.3.x version)'
                );
            }

            return true;
        } catch (Exception $e) {
            dcCore::app()->error->add($e->getMessage());
        }

        return true;
    }
}
