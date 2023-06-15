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
use dcNsProcess;

class Prepend extends dcNsProcess
{
    public static function init(): bool
    {
        static::$init = My::checkContext(My::PREPEND);

        return static::$init;
    }

    public static function process(): bool
    {
        if (!static::$init) {
            return false;
        }

        dcCore::app()->url->register('mymeta', 'meta', '^meta/(.+)$', [FrontendUrl::class,'tag']);

        MyMeta::registerType(MyMetaString::class);
        MyMeta::registerType(MyMetaList::class);
        MyMeta::registerType(MyMetaCheck::class);
        MyMeta::registerType(MyMetaDate::class);

        class_alias(MyMetaString::class, 'mmString');
        class_alias(MyMetaList::class, 'mmList');
        class_alias(MyMetaCheck::class, 'mmCheck');
        class_alias(MyMetaDate::class, 'mmDate');

        return true;
    }
}
