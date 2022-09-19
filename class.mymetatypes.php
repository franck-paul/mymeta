<?php
/**
 * @brief mymeta, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @author Bruno Hondelatte and contributors
 *
 * @copyright GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
myMeta::registerType('mmString');
myMeta::registerType('mmList');
myMeta::registerType('mmCheck');
myMeta::registerType('mmDate');

abstract class myMetaEntry
{
    public $id;
    public $prompt;
    public $pos;
}

abstract class myMetaField extends myMetaEntry
{
    public $enabled;
    public $id;
    public $prompt;
    public $default;
    public $contexts;
    public $post_types;
    public $tpl_list           = '';
    public $url_list_enabled   = true;
    public $tpl_single         = '';
    public $url_single_enabled = true;

    public static function cmp_pos($a, $b)
    {
        if ($a->pos == $b->pos) {
            return 0;
        }

        return ($a->pos < $b->pos) ? -1 : 1;
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
     * getMetaTypeId
     *
     * retrieves meta type ID (should be unique)
     *
     * @access public
     * @return string the meta type
     */
    abstract public function getMetaTypeId();

    /**
     * getMetaTypeDesc
     *
     * Returns meta type description (shown in combo list)
     *
     * @access public
     * @return string the description
     */
    abstract public function getMetaTypeDesc();

    /**
     * postShowForm
     *
     * Displays form input field (when editting a post) including prompt
     * Notice : submitted field name must be prefixed with "mymeta_"
     *
     * @param myMeta $dcmeta MyMeta instance to use
     * @param recordset $post the post resultset
     * @access public
     * @return void
     */
    public function postShowForm($dcmeta, $post, $value = '', $bypass_disabled = false)
    {
        if ($this->enabled || $bypass_disabled) {
            $res     = '';
            $this_id = 'mymeta_' . $this->id;
            if (isset($_POST[$this_id])) {
                $value = html::escapeHTML($_POST[$this_id]);
            } elseif ($post != null) {
                $value = ($post) ? $dcmeta->getMetaStr($post->post_meta, $this->id) : '';
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
     * @param recordset $post the post resultset
     * @access public
     * @return void
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
     * @access protected
     * @return void
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
     * @access public
     * @return void
     */
    public function setPostMeta($dcmeta, $post_id, $post, $deleteIfEmpty = true)
    {
        if (!empty($post['mymeta_' . $this->id]) || $deleteIfEmpty) {
            $dcmeta->delPostMeta($post_id, $this->id);
        }
        if (!empty($post['mymeta_' . $this->id])) {
            $dcmeta->setPostMeta($post_id, $this->id, html::escapeHTML($post['mymeta_' . $this->id]));
        }
    }

    /**
     * getValue
     *
     * Returns public value for a given mymeta value
     * usually returns the value itself
     *
     * @param string $value the value to retrieve
     * @access public
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
     * @access public
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
     * @param recordset $post the post resultset
     * @access public
     * @return void
     */
    public function adminUpdate($post)
    {
        $this->prompt  = html::escapeHTML($post['mymeta_prompt']);
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

// Simple textfield meta type
class mmString extends myMetaField
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

// Items list meta type
class mmList extends myMetaField
{
    public $values;

    public function getMetaTypeId()
    {
        return 'list';
    }
    public function getMetaTypeDesc()
    {
        return __('Items List');
    }
    private function valuesToArray($values)
    {
        $arr   = [];
        $lines = explode("\n", $values);
        foreach ($lines as $line) {
            $entries = explode(':', $line);
            if (sizeof($entries) == 1) {
                $key = $desc = trim((string) $entries[0]);
            } else {
                $key  = trim((string) $entries[0]);
                $desc = trim((string) $entries[1]);
            }
            if ($key != '') {
                $arr[$desc] = $key;
            }
        }

        return $arr;
    }

    public function arrayToValues($array)
    {
        $res = '';
        if (is_array($array)) {
            foreach ($array as $k => $v) {
                $res .= "$v : $k\n";
            }
        }

        return $res;
    }

    public function getValue($value, $attr)
    {
        $key = array_search($value, $this->values);
        if (isset($attr['key']) && $attr['key'] == 1) {
            return $value;
        }
        if ($key != null) {
            return $key;
        }

        return $value;
    }

    protected function postShowField($id, $value)
    {
        $list     = $this->values;
        $list[''] = '';

        return form::combo($id, $list, $value);
    }

    public function adminForm()
    {
        return '<p><label>' .
        __('Values : enter 1 value per line (syntax for each line : ID: description)') .
        ' </label>' .
        form::textarea('mymeta_values', 40, 10, $this->arrayToValues($this->values)) .
        '</p>';
    }

    public function adminUpdate($post)
    {
        parent::adminUpdate($post);
        if (isset($post['mymeta_values'])) {
            $this->values = $this->valuesToArray($post['mymeta_values']);
        }
    }
}

// Checkbox meta type
class mmCheck extends myMetaField
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

    public function setPostMeta($dcmeta, $post_id, $post, $deleteIfEmpty = true)
    {
        if (!empty($post['mymeta_' . $this->id]) || $deleteIfEmpty) {
            $dcmeta->delPostMeta($post_id, $this->id);
        }
        if (!empty($post['mymeta_' . $this->id])) {
            $dcmeta->setPostMeta($post_id, $this->id, 1);
        }
    }
}

// Datepicker  meta type
class mmDate extends myMetaField
{
    private static $jsSingleton = false;

    public function postHeader($post = null, $standalone = false)
    {
        $var = 'mymeta_' . $this->id . '_dtPick';
        $ret = '';
        if ($standalone && !mmDate::$jsSingleton) {
            mmDate::$jsSingleton = true;
            $ret .= dcPage::jsDatePicker();
        }
        $ret .= '<script type="text/javascript">' . "\n" .
            "\$(function() {\n" .
            'var ' . $var . " = new datePicker(\$('#mymeta_" . $this->id . "').get(0));\n" .
            $var . ".img_top = '1.5em';\n" .
            $var . ".draw();\n" .
            "});\n" .
            '</script>';

        return $ret;
    }

    protected function postShowField($id, $value)
    {
        return form::datetime($id, ['default' => html::escapeHTML(dt::str('%Y-%m-%dT%H:%M', $value))]);
    }

    public function getMetaTypeId()
    {
        return 'date';
    }
    public function getMetaTypeDesc()
    {
        return __('Date');
    }
}

// Section mymeta type
class myMetaSection extends mymetaEntry
{
}
