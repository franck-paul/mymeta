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

use dcAdmin;
use dcCore;
use dcNsProcess;

class Backend extends dcNsProcess
{
    protected static $init = false; /** @deprecated since 2.27 */
    public static function init(): bool
    {
        static::$init = My::checkContext(My::BACKEND);

        // dead but useful code, in order to have translations
        __('My Meta') . __('User-defined metadata management in posts');

        return static::$init;
    }

    public static function process(): bool
    {
        if (!static::$init) {
            return false;
        }

        dcCore::app()->menu[dcAdmin::MENU_PLUGINS]->addItem(
            __('My Metadata'),
            My::makeUrl(),
            My::icons(),
            preg_match(My::urlScheme(), $_SERVER['REQUEST_URI']),
            My::checkContext(My::MENU)
        );

        dcCore::app()->addBehaviors([
            'adminPostFormSidebar' => [BackendBehaviors::class, 'mymetaSidebar'],
            'adminPostForm'        => [BackendBehaviors::class, 'mymetaInForm'],

            'adminAfterPostCreate' => [BackendBehaviors::class, 'setMymeta'],
            'adminAfterPostUpdate' => [BackendBehaviors::class, 'setMymeta'],

            'adminPageFormSidebar' => [BackendBehaviors::class, 'mymetaSidebar'],
            'adminPageForm'        => [BackendBehaviors::class, 'mymetaInForm'],

            'adminPostsActions' => [BackendBehaviors::class, 'adminPostsActions'],

            'adminAfterPageCreate' => [BackendBehaviors::class, 'setMymeta'],
            'adminAfterPageUpdate' => [BackendBehaviors::class, 'setMymeta'],
        ]);

        dcCore::app()->addBehaviors([
            'adminPostForm' => [BackendBehaviors::class, 'mymetaPostHeader'],
        ]);

        if (My::checkContext(My::WIDGETS)) {
            dcCore::app()->addBehaviors([
                'initWidgets' => [Widgets::class,'initWidgets'],
            ]);
        }

        return true;
    }
}
