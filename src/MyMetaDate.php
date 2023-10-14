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

use Dotclear\Helper\Date;
use Dotclear\Helper\Html\Html;
use Dotclear\Interface\Core\MetaInterface;
use form;

// Datepicker  meta type
class MyMetaDate extends MyMetaField
{
    protected function postShowField(string $id, string $value): string
    {
        $timestamp = $value ? strtotime($value) : time();

        return form::datetime($id, ['default' => Html::escapeHTML(Date::str('%Y-%m-%dT%H:%M', $timestamp))]);
    }

    public function getMetaTypeId(): string
    {
        return 'date';
    }

    public function getMetaTypeDesc(): string
    {
        return __('Date');
    }

    /**
     * Sets the post meta.
     *
     * @param      MetaInterface            $dcmeta         current dcMeta instance
     * @param      int                      $post_id        The post identifier
     * @param      array<string, string>    $post           The post
     * @param      bool                     $deleteIfEmpty  The delete if empty
     */
    public function setPostMeta(MetaInterface $dcmeta, int $post_id, array $post, bool $deleteIfEmpty = true): void
    {
        $timestamp = !empty($post['mymeta_' . $this->id]) ? strtotime($post['mymeta_' . $this->id]) : 0;
        $dcmeta->delPostMeta($post_id, $this->id);
        if ($timestamp) {
            $value = date('Y-m-d H:i:00', $timestamp);
            $dcmeta->setPostMeta($post_id, $this->id, $value);
        }
    }

    public function displayValue(string $value): string
    {
        return (string) date('Y-m-d H:i', strtotime($value)) . ' UTC';
    }
}
