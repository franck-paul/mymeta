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

class Frontend extends Process
{
    public static function init(): bool
    {
        return self::status(My::checkContext(My::FRONTEND));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        dcCore::app()->tpl->addValue('MetaType', FrontendTemplate::MetaType(...));
        dcCore::app()->tpl->addValue('MyMetaTypePrompt', FrontendTemplate::MyMetaTypePrompt(...));
        dcCore::app()->tpl->addValue('EntryMyMetaValue', FrontendTemplate::EntryMyMetaValue(...));
        dcCore::app()->tpl->addValue('MyMetaValue', FrontendTemplate::MyMetaValue(...));
        dcCore::app()->tpl->addValue('MyMetaURL', FrontendTemplate::MyMetaURL(...));
        dcCore::app()->tpl->addBlock('EntryMyMetaIf', FrontendTemplate::EntryMyMetaIf(...));
        dcCore::app()->tpl->addBlock('MyMetaIf', FrontendTemplate::EntryMyMetaIf(...));
        dcCore::app()->tpl->addBlock('MyMetaData', FrontendTemplate::MyMetaData(...));

        dcCore::app()->addBehaviors([
            'templateBeforeBlockV2'  => FrontendBehaviors::templateBeforeBlock(...),
            'publicBeforeDocumentV2' => FrontendBehaviors::addTplPath(...),

            'initWidgets' => Widgets::initWidgets(...),
        ]);

        dcCore::app()->mymeta = new MyMeta();

        return true;
    }
}
