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

use dcMeta;
use form;

// Checkbox meta type
class MyMetaCheck extends MyMetaField
{
    public $values;

    public function getMetaTypeId()
    {
        return 'boolean';
    }

    public function getMetaTypeDesc()
    {
        return __('Checkbox');
    }

    protected function postShowField($id, $value)
    {
        return form::checkbox($id, 1, $value);
    }

    public function setPostMeta(dcMeta $dcmeta, $post_id, $post, $deleteIfEmpty = true)
    {
        if (!empty($post['mymeta_' . $this->id]) || $deleteIfEmpty) {
            $dcmeta->delPostMeta($post_id, $this->id);
        }
        if (!empty($post['mymeta_' . $this->id])) {
            $dcmeta->setPostMeta($post_id, $this->id, '1');
        }
    }

    public function displayValue(string $value)
    {
        return (bool) $value ? '[x]' : '[ ]';
    }
}
