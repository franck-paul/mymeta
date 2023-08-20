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
use Dotclear\Core\Backend\Menus;
use Dotclear\Core\Process;

class Backend extends Process
{
    public static function init(): bool
    {
        // dead but useful code, in order to have translations
        __('My Meta') . __('User-defined metadata management in posts');

        return self::status(My::checkContext(My::BACKEND));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        dcCore::app()->admin->menus[Menus::MENU_PLUGINS]->addItem(
            __('My Metadata'),
            My::manageUrl(),
            My::icons(),
            preg_match(My::urlScheme(), $_SERVER['REQUEST_URI']),
            My::checkContext(My::MENU)
        );

        dcCore::app()->addBehaviors([
            'adminPostFormSidebar' => BackendBehaviors::mymetaSidebar(...),
            'adminPostForm'        => BackendBehaviors::mymetaInForm(...),

            'adminAfterPostCreate' => BackendBehaviors::setMymeta(...),
            'adminAfterPostUpdate' => BackendBehaviors::setMymeta(...),

            'adminPageFormSidebar' => BackendBehaviors::mymetaSidebar(...),
            'adminPageForm'        => BackendBehaviors::mymetaInForm(...),

            'adminPostsActions' => BackendBehaviors::adminPostsActions(...),

            'adminAfterPageCreate' => BackendBehaviors::setMymeta(...),
            'adminAfterPageUpdate' => BackendBehaviors::setMymeta(...),
        ]);

        dcCore::app()->addBehaviors([
            'adminPostForm' => BackendBehaviors::mymetaPostHeader(...),
        ]);

        if (My::checkContext(My::WIDGETS)) {
            dcCore::app()->addBehaviors([
                'initWidgets' => Widgets::initWidgets(...),
            ]);
        }

        return true;
    }
}
