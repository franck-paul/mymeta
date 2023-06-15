<?php
/**
 * @brief mymeta, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @author Jean-Christian Denis, Franck Paul and contributors
 *
 * @copyright Jean-Christian Denis, Franck Paul
 * @copyright GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
declare(strict_types=1);

namespace Dotclear\Plugin\mymeta;

// Simple textfield meta type
class MyMetaString extends MyMetaField
{
    public function getMetaTypeId()
    {
        return 'string';
    }

    public function getMetaTypeDesc()
    {
        return __('String');
    }
}
