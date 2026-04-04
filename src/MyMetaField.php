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

use Dotclear\Database\MetaRecord;
use Dotclear\Helper\Html\Form\Component;
use Dotclear\Helper\Html\Form\Input;
use Dotclear\Helper\Html\Form\Label;
use Dotclear\Helper\Html\Form\None;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Strong;
use Dotclear\Helper\Html\Html;
use Dotclear\Interface\Core\MetaInterface;

abstract class MyMetaField extends MyMetaEntry
{
    public bool $enabled = false;

    public string $default = '';

    public string $tpl_list = '';

    public bool $url_list_enabled = true;

    public string $tpl_single = '';

    public bool $url_single_enabled = true;

    /**
     * @var array<string, string>
     */
    public array $values = [];

    /**
     * @var bool|null|array<string>
     */
    public $post_types;

    public function __construct(string $id = '')
    {
        $this->prompt = '';
        $this->pos    = 1000;
        $this->id     = $id;
    }

    /**
     * postForm
     *
     * Displays form input field (when editting a post) including prompt
     * Notice : submitted field name must be prefixed with "mymeta_"
     *
     * @param MetaInterface     $meta     Meta instance to use
     * @param MetaRecord|null   $post     the post resultset
     */
    public function postForm(MetaInterface $meta, ?MetaRecord $post, string $value = '', bool $bypass_disabled = false): Component
    {
        if ($this->enabled || $bypass_disabled) {
            $this_id    = 'mymeta_' . $this->id;
            $post_value = isset($_POST[$this_id]) && is_string($post_value = $_POST[$this_id]) ? $post_value : null;
            $value      = '';
            if ($post_value !== null) {
                $value = Html::escapeHTML($_POST[$this_id]);
            } elseif ($post instanceof MetaRecord) {
                $post_meta = is_string($post_meta = $post->post_meta) ? $post_meta : null;
                $value     = $meta->getMetaStr($post_meta, $this->id);
            }

            return (new Para())
                ->items([
                    $this->formField($this_id, $value, $this->prompt),
                ]);
        }

        return (new None());
    }

    /**
     * postHeader
     *
     * Displays extra data in post edit page header
     *
     * @param MetaRecord $post the post resultset
     */
    public function postHeader($post = null, bool $standalone = false): string
    {
        return '';
    }

    /**
     * formField
     *
     * get inputable mymeta field (usually a textfield)
     *
     * @param string    $id     mymeta id
     * @param string    $value  current mymeta value
     * @param string    $label  field label
     */
    protected function formField(string $id, string $value, string $label): Component
    {
        return (new Input($id))
            ->size(40)
            ->maxlength(255)
            ->value($value)
            ->class('maximal')
            ->label(new Label((new Strong($label))->render(), Label::IL_TF));
    }

    /**
     * setPostMeta
     *
     * updates post meta for a given post, when a post is submitted
     *
     * @param MetaInterface             $meta         current Meta instance
     * @param int                       $post_id      post_id to update
     */
    public function setPostMeta(MetaInterface $meta, int $post_id, bool $delete_if_empty = true): void
    {
        $mymeta_value = isset($_POST['mymeta_' . $this->id]) && is_string($mymeta_value = $_POST['mymeta_' . $this->id]) ? $mymeta_value : '';
        if ($mymeta_value !== '' || $delete_if_empty) {
            $meta->delPostMeta($post_id, $this->id);
        }

        if ($mymeta_value !== '') {
            $meta->setPostMeta($post_id, $this->id, Html::escapeHTML($mymeta_value));
        }
    }

    /**
     * Display current value
     *
     * @param      string  $value  The value
     */
    public function displayValue(string $value): string
    {
        return $value;
    }

    /**
     * getValue
     *
     * Returns public value for a given mymeta value
     * usually returns the value itself
     *
     * @param string                $value the value to retrieve
     * @param array<string, mixed>  $attr
     *
     * @return string the converted public value
     */
    public function getValue(string $value, array $attr): string
    {
        return $value;
    }

    /**
     * adminForm
     *
     * returns extra fields in mymeta type admin form
     *
     * @return string the html code to output
     */
    public function adminForm(): string
    {
        return '';
    }

    /**
     * adminUpdate
     *
     * This function is triggered on mymeta update
     * to set mymeta fields defined in adminForm
     *
     * @param array<mixed> $post the post resultset
     */
    public function adminUpdate(array $post): void
    {
        $prompt  = isset($post['mymeta_prompt'])  && is_string($prompt = $post['mymeta_prompt']) ? $prompt : '';
        $enabled = isset($post['mymeta_enabled']) && is_numeric($enabled = $post['mymeta_enabled']) && (bool) $enabled;

        $this->prompt  = Html::escapeHTML($prompt);
        $this->enabled = $enabled;
    }

    public function isEnabledFor(string $mode): bool
    {
        if (is_array($this->post_types)) {
            return in_array($mode, $this->post_types);
        }

        return true;
    }

    public function isRestrictionEnabled(): bool
    {
        return !is_array($this->post_types);
    }

    public function getRestrictions(): string|false
    {
        if (is_array($this->post_types)) {
            return implode(',', $this->post_types);
        }

        return false;
    }
}
