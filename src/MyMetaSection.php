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

// Section mymeta type
/**
 * @psalm-suppress InvalidReturnType
 */
#[\AllowDynamicProperties]
class MyMetaSection extends MyMetaEntry
{
    public function getMetaTypeId(): string
    {
        return 'section';
    }

    public function getMetaTypeDesc(): string
    {
        return __('Section');
    }
}
