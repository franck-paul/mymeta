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

use Dotclear\Helper\Html\Form\Checkbox;
use Dotclear\Helper\Html\Form\Component;
use Dotclear\Helper\Html\Form\Label;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Interface\Core\MetaInterface;

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

    /**
     * formField
     *
     * get inputable mymeta field (usually a textfield, here a checkbox)
     *
     * @param string    $id     mymeta id
     * @param string    $value  current mymeta value
     * @param string    $label  field label
     */
    protected function formField(string $id, string $value, string $label): Component
    {
        return (new Checkbox($id, (bool) $value))
            ->value(1)
            ->label(new Label((new Text('strong', $label))->render(), Label::IL_TF));
    }

    /**
     * Sets the post meta.
     *
     * @param      MetaInterface            $meta             The Meta instance
     * @param      int                      $post_id          The post identifier
     * @param      array<string, string>    $post             The post
     * @param      bool                     $delete_if_empty  The delete if empty
     */
    public function setPostMeta(MetaInterface $meta, int $post_id, array $post, bool $delete_if_empty = true): void
    {
        if (!empty($post['mymeta_' . $this->id]) || $delete_if_empty) {
            $meta->delPostMeta($post_id, $this->id);
        }

        if (!empty($post['mymeta_' . $this->id])) {
            $meta->setPostMeta($post_id, $this->id, '1');
        }
    }

    public function displayValue(string $value): string
    {
        return (bool) $value ? '[x]' : '[ ]';
    }
}
