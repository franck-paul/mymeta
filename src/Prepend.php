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
use Dotclear\Core\Process;

class Prepend extends Process
{
    public static function init(): bool
    {
        return self::status(My::checkContext(My::PREPEND));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        dcCore::app()->url->register('mymeta', 'meta', '^meta/(.+)$', FrontendUrl::tag(...));

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
