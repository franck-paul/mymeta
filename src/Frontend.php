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

class Frontend
{
    use TraitProcess;

    public static function init(): bool
    {
        MyMeta::registerType(MyMetaString::class);
        MyMeta::registerType(MyMetaList::class);
        MyMeta::registerType(MyMetaCheck::class);
        MyMeta::registerType(MyMetaDate::class);

        // Cope with legacy
        class_alias(MyMetaString::class, 'mmString');
        class_alias(MyMetaList::class, 'mmList');
        class_alias(MyMetaCheck::class, 'mmCheck');
        class_alias(MyMetaDate::class, 'mmDate');

        return self::status(My::checkContext(My::FRONTEND));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        App::frontend()->template()->addValue('MetaType', FrontendTemplate::MetaType(...));
        App::frontend()->template()->addValue('MyMetaTypePrompt', FrontendTemplate::MyMetaTypePrompt(...));
        App::frontend()->template()->addValue('EntryMyMetaValue', FrontendTemplate::EntryMyMetaValue(...));
        App::frontend()->template()->addValue('MyMetaValue', FrontendTemplate::MyMetaValue(...));
        App::frontend()->template()->addValue('MyMetaURL', FrontendTemplate::MyMetaURL(...));
        App::frontend()->template()->addBlock('EntryMyMetaIf', FrontendTemplate::EntryMyMetaIf(...));
        App::frontend()->template()->addBlock('MyMetaIf', FrontendTemplate::EntryMyMetaIf(...));
        App::frontend()->template()->addBlock('MyMetaData', FrontendTemplate::MyMetaData(...));

        App::behavior()->addBehaviors([
            'templateBeforeBlockV2'  => FrontendBehaviors::templateBeforeBlock(...),
            'publicBeforeDocumentV2' => FrontendBehaviors::addTplPath(...),

            'initWidgets' => Widgets::initWidgets(...),
        ]);

        App::frontend()->mymeta = new MyMeta();

        return true;
    }
}
