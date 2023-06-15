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

use form;

// Items list meta type
class MyMetaList extends MyMetaField
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

    private function arrayToValues($array)
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
