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

abstract class MyMetaEntry
{
    public string $id;

    public string $prompt;

    public int $pos;

    /**
     * getMetaTypeId
     *
     * retrieves meta type ID (should be unique)
     *
     * @return string the meta type
     */
    abstract public function getMetaTypeId(): string;

    /**
     * getMetaTypeDesc
     *
     * Returns meta type description (shown in combo list)
     *
     * @return string the description
     */
    abstract public function getMetaTypeDesc(): string;
}
