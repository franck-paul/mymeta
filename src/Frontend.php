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

class Frontend extends dcNsProcess
{
    public static function init(): bool
    {
        static::$init = My::checkContext(My::FRONTEND);

        return static::$init;
    }

    public static function process(): bool
    {
        if (!static::$init) {
            return false;
        }

        dcCore::app()->tpl->addValue('MetaType', [FrontendTemplate::class,'MetaType']);
        dcCore::app()->tpl->addValue('MyMetaTypePrompt', [FrontendTemplate::class,'MyMetaTypePrompt']);
        dcCore::app()->tpl->addValue('EntryMyMetaValue', [FrontendTemplate::class,'EntryMyMetaValue']);
        dcCore::app()->tpl->addValue('MyMetaValue', [FrontendTemplate::class,'MyMetaValue']);
        dcCore::app()->tpl->addValue('MyMetaURL', [FrontendTemplate::class,'MyMetaURL']);
        dcCore::app()->tpl->addBlock('EntryMyMetaIf', [FrontendTemplate::class,'EntryMyMetaIf']);
        dcCore::app()->tpl->addBlock('MyMetaIf', [FrontendTemplate::class,'EntryMyMetaIf']);
        dcCore::app()->tpl->addBlock('MyMetaData', [FrontendTemplate::class,'MyMetaData']);

        dcCore::app()->addBehaviors([
            'templateBeforeBlockV2'  => [FrontendBehaviors::class,'templateBeforeBlock'],
            'publicBeforeDocumentV2' => [FrontendBehaviors::class,'addTplPath'],

            'initWidgets' => [Widgets::class,'initWidgets'],
        ]);

        dcCore::app()->mymeta = new MyMeta();

        return true;
    }
}
