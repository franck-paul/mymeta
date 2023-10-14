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

use Dotclear\Interface\Core\MetaInterface;
use form;

// Checkbox meta type
class MyMetaCheck extends MyMetaField
{
    public function getMetaTypeId(): string
    {
        return 'boolean';
    }

    public function getMetaTypeDesc(): string
    {
        return __('Checkbox');
    }

    protected function postShowField(string $id, string $value): string
    {
        return form::checkbox($id, 1, $value);
    }

    /**
     * Sets the post meta.
     *
     * @param      MetaInterface            $dcmeta         The dcmeta
     * @param      int                      $post_id        The post identifier
     * @param      array<string, string>    $post           The post
     * @param      bool                     $deleteIfEmpty  The delete if empty
     */
    public function setPostMeta(MetaInterface $dcmeta, int $post_id, array $post, bool $deleteIfEmpty = true): void
    {
        if (!empty($post['mymeta_' . $this->id]) || $deleteIfEmpty) {
            $dcmeta->delPostMeta($post_id, $this->id);
        }
        if (!empty($post['mymeta_' . $this->id])) {
            $dcmeta->setPostMeta($post_id, $this->id, '1');
        }
    }

    public function displayValue(string $value): string
    {
        return (bool) $value ? '[x]' : '[ ]';
    }
}
