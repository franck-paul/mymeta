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
    public function getMetaTypeId(): string
    {
        return 'list';
    }

    public function getMetaTypeDesc(): string
    {
        return __('Items List');
    }

    /**
     * @param      string  $values  The values
     *
     * @return     array<string, string>
     */
    private function valuesToArray(string $values): array
    {
        $arr   = [];
        $lines = explode("\n", $values);
        foreach ($lines as $line) {
            $entries = explode(':', $line);
            if (count($entries) == 1) {
                $key  = trim((string) $entries[0]);
                $desc = $key;
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

    /**
     * @param      array<string, string>   $array  The array
     *
     * @return     string
     */
    private function arrayToValues(array $array): string
    {
        $res = '';
        foreach ($array as $k => $v) {
            $res .= sprintf('%s : %s%s', $v, $k, PHP_EOL);
        }

        return $res;
    }

    /**
     * @param      string                   $value  The value
     * @param      array<string, mixed>     $attr   The attribute
     *
     * @return     string  The value.
     */
    public function getValue(string $value, array $attr): string
    {
        $key = array_search($value, $this->values, true);
        if (isset($attr['key']) && $attr['key'] == 1) {
            return $value;
        }

        if ($key != null) {
            return $key;
        }

        return $value;
    }

    protected function postShowField(string $id, string $value): string
    {
        $list     = $this->values;
        $list[''] = '';

        return form::combo($id, $list, $value);
    }

    public function adminForm(): string
    {
        return '<p><label>' .
        __('Values : enter 1 value per line (syntax for each line : ID: description)') .
        ' </label>' .
        form::textarea('mymeta_values', 40, 10, $this->arrayToValues($this->values)) .
        '</p>';
    }

    /**
     * @param      array<string, string>  $post   The post
     */
    public function adminUpdate(array $post): void
    {
        parent::adminUpdate($post);
        if (isset($post['mymeta_values'])) {
            $this->values = $this->valuesToArray($post['mymeta_values']);
        }
    }
}
