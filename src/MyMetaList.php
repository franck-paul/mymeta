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

use Dotclear\Helper\Html\Form\Component;
use Dotclear\Helper\Html\Form\Label;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Select;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Html\Form\Textarea;

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
                $key  = trim($entries[0]);
                $desc = $key;
            } else {
                $key  = trim($entries[0]);
                $desc = trim($entries[1]);
            }

            if ($key !== '') {
                $arr[$desc] = $key;
            }
        }

        return $arr;
    }

    /**
     * @param      array<string, string>   $array  The array
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

    /**
     * formField
     *
     * get inputable mymeta field (usually a textfield, here a select)
     *
     * @param string    $id     mymeta id
     * @param string    $value  current mymeta value
     * @param string    $label  field label
     */
    protected function formField(string $id, string $value, string $label): Component
    {
        $list     = $this->values;
        $list[''] = '';

        return (new Select($id))
            ->items($list)
            ->default($value)
            ->label(new Label((new Text('strong', $label))->render(), Label::IL_TF));
    }

    public function adminForm(): string
    {
        return (new Para())
            ->items([
                (new Textarea('mymeta_values', $this->arrayToValues($this->values)))
                    ->cols(40)
                    ->rows(10)
                    ->label(new Label(__('Values : enter 1 value per line (syntax for each line : ID: description)'), Label::OL_TF)),
            ])
        ->render();
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
