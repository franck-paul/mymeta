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

class Backend
{
    use TraitProcess;

    public static function init(): bool
    {
        // dead but useful code, in order to have translations
        __('My Meta');
        __('User-defined metadata management in posts');

        MyMeta::registerType(MyMetaString::class);
        MyMeta::registerType(MyMetaList::class);
        MyMeta::registerType(MyMetaCheck::class);
        MyMeta::registerType(MyMetaDate::class);

        // Cope with legacy
        class_alias(MyMetaString::class, 'mmString');
        class_alias(MyMetaList::class, 'mmList');
        class_alias(MyMetaCheck::class, 'mmCheck');
        class_alias(MyMetaDate::class, 'mmDate');

        return self::status(My::checkContext(My::BACKEND));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        My::addBackendMenuItem(App::backend()->menus()::MENU_PLUGINS);

        App::behavior()->addBehaviors([
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

        App::behavior()->addBehaviors([
            'adminPostForm' => BackendBehaviors::mymetaPostHeader(...),
        ]);

        if (My::checkContext(My::WIDGETS)) {
            App::behavior()->addBehaviors([
                'initWidgets' => Widgets::initWidgets(...),
            ]);
        }

        return true;
    }
}
