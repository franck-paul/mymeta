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
use Dotclear\Helper\Html\Form\Component;
use Dotclear\Helper\Html\Form\Datetime;
use Dotclear\Helper\Html\Form\Label;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Html\Html;
use Dotclear\Interface\Core\MetaInterface;

// Datepicker  meta type
class MyMetaDate extends MyMetaField
{
    /**
     * formField
     *
     * get inputable mymeta field (usually a textfield, here specialized to datetime)
     *
     * @param string    $id     mymeta id
     * @param string    $value  current mymeta value
     * @param string    $label  field label
     */
    protected function formField(string $id, string $value, string $label): Component
    {
        $timestamp = $value !== '' && $value !== '0' ? strtotime($value) : time();

        return (new Datetime($id))
            ->value(Html::escapeHTML(Date::str('%Y-%m-%dT%H:%M', $timestamp)))
            ->label(new Label((new Text('strong', $label))->render(), Label::IL_TF));
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
     * @param      MetaInterface            $meta           current Meta instance
     * @param      int                      $post_id        The post identifier
     * @param      array<string, string>    $post           The post
     * @param      bool                     $delete_if_empty  The delete if empty
     */
    public function setPostMeta(MetaInterface $meta, int $post_id, array $post, bool $delete_if_empty = true): void
    {
        $timestamp = empty($post['mymeta_' . $this->id]) ? 0 : strtotime($post['mymeta_' . $this->id]);
        $meta->delPostMeta($post_id, $this->id);
        if ($timestamp) {
            $value = date('Y-m-d H:i:00', $timestamp);
            $meta->setPostMeta($post_id, $this->id, $value);
        }
    }

    public function displayValue(string $value): string
    {
        return date('Y-m-d H:i', (int) strtotime($value)) . ' UTC';
    }
}
