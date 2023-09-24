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
use Dotclear\Database\MetaRecord;
use Dotclear\Helper\Html\Html;
use form;

abstract class MyMetaField extends MyMetaEntry
{
    public $enabled;
    public $default;
    public $contexts;
    public $post_types;
    public $tpl_list           = '';
    public $url_list_enabled   = true;
    public $tpl_single         = '';
    public $url_single_enabled = true;

    public static function cmp_pos($a, $b)
    {
        return $a->pos <=> $b->pos;
    }

    public function __construct($id = '')
    {
        $this->enabled    = false;
        $this->prompt     = '';
        $this->default    = '';
        $this->contexts   = [];
        $this->pos        = 1000;
        $this->id         = $id;
        $this->post_types = null;
    }

    /**
     * postShowForm
     *
     * Displays form input field (when editting a post) including prompt
     * Notice : submitted field name must be prefixed with "mymeta_"
     *
     * @param dcMeta            $dcmeta     dcMeta instance to use
     * @param MetaRecord|null   $post       the post resultset
     *
     * @return mixed
     */
    public function postShowForm($dcmeta, ?MetaRecord $post, $value = '', $bypass_disabled = false)
    {
        if ($this->enabled || $bypass_disabled) {
            $res     = '';
            $this_id = 'mymeta_' . $this->id;
            $value   = '';
            if (isset($_POST[$this_id])) {
                $value = Html::escapeHTML($_POST[$this_id]);
            } elseif (!is_null($post)) {
                $value = $dcmeta->getMetaStr($post->post_meta, $this->id);
            }
            $res .= '<p><label for="' . $this_id . '"><strong>' . $this->prompt . '</strong></label>';
            $res .= $this->postShowField($this_id, $value);
            $res .= '</p>';

            return $res;
        }
    }

    /**
     * postHeader
     *
     * Displays extra data in post edit page header
     *
     * @param MetaRecord $post the post resultset
     *
     * @return mixed
     */
    public function postHeader($post = null, $standalone = false)
    {
        return '';
    }

    /**
     * postShowField
     *
     * displays inputable mymeta field (usually a textfield)
     *
     * @param string $id mymeta id
     * @param string $value current mymeta value
     *
     * @return string
     */
    protected function postShowField($id, $value)
    {
        return form::field($id, 40, 255, $value, 'maximal');
    }

    /**
     * setPostMeta
     *
     * updates post meta for a given post, when a post is submitted
     *
     * @param dcMeta $dcmeta current dcMeta instance
     * @param integer $post_id post_id to update
     * @param array $post HTTP POST parameters
     */
    public function setPostMeta($dcmeta, $post_id, $post, $deleteIfEmpty = true)
    {
        if (!empty($post['mymeta_' . $this->id]) || $deleteIfEmpty) {
            $dcmeta->delPostMeta($post_id, $this->id);
        }
        if (!empty($post['mymeta_' . $this->id])) {
            $dcmeta->setPostMeta($post_id, $this->id, Html::escapeHTML($post['mymeta_' . $this->id]));
        }
    }

    /**
     * Display current value
     *
     * @param      string  $value  The value
     *
     * @return     string
     */
    public function displayValue(string $value)
    {
        return $value;
    }

    /**
     * getValue
     *
     * Returns public value for a given mymeta value
     * usually returns the value itself
     *
     * @param string $value the value to retrieve
     *
     * @return string the converted public value
     */
    public function getValue($value, $attr)
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
    public function adminForm()
    {
        return '';
    }

    /**
     * adminUpdate
     *
     * This function is triggered on mymeta update
     * to set mymeta fields defined in adminForm
     *
     * @param MetaRecord $post the post resultset
     */
    public function adminUpdate($post)
    {
        $this->prompt  = Html::escapeHTML($post['mymeta_prompt']);
        $this->enabled = (bool) $post['mymeta_enabled'];
    }

    public function isEnabledFor($mode)
    {
        if (is_array($this->post_types)) {
            return in_array($mode, $this->post_types);
        }

        return true;
    }

    public function isRestrictionEnabled()
    {
        return !is_array($this->post_types);
    }

    public function getRestrictions()
    {
        if (is_array($this->post_types)) {
            return join(',', $this->post_types);
        }

        return false;
    }
}
