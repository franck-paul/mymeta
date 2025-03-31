<?php

/**
 * @brief mymeta, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @author Franck Paul and contributors
 *
 * @copyright Franck Paul carnet.franck.paul@gmail.com
 * @copyright GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
declare(strict_types=1);

namespace Dotclear\Plugin\mymeta;

use ArrayObject;
use Dotclear\Plugin\TemplateHelper\Code;

class FrontendTemplate
{
    /**
     * @param      array<string, mixed>|\ArrayObject<string, mixed>  $attr      The attribute
     */
    public static function MyMetaURL(array|ArrayObject $attr): string
    {
        return Code::getPHPTemplateValueCode(
            FrontendTemplateCode::MyMetaURL(...),
            attr: $attr,
        );
    }

    /**
     * @param      array<string, mixed>|\ArrayObject<string, mixed>  $attr      The attribute
     */
    public static function MetaType(array|ArrayObject $attr): string
    {
        return Code::getPHPTemplateValueCode(
            FrontendTemplateCode::MetaType(...),
            attr: $attr,
        );
    }

    /**
     * @param      array<string, mixed>|\ArrayObject<string, mixed>  $attr      The attribute
     */
    public static function MyMetaTypePrompt(array|ArrayObject $attr): string
    {
        $attr = $attr instanceof ArrayObject ? $attr : new ArrayObject($attr);

        return Code::getPHPTemplateValueCode(
            FrontendTemplateCode::MyMetaTypePrompt(...),
            [
                self::metaID($attr),
            ],
            attr: $attr,
        );
    }

    /**
     * @param      array<string, mixed>|\ArrayObject<string, mixed>  $attr      The attribute
     */
    public static function EntryMyMetaValue(array|ArrayObject $attr): string
    {
        $attr = $attr instanceof ArrayObject ? $attr : new ArrayObject($attr);

        return Code::getPHPTemplateValueCode(
            FrontendTemplateCode::EntryMyMetaValue(...),
            [
                self::metaID($attr),
                array_filter($attr->getArrayCopy(), fn ($key): bool => !in_array($key, ['id', 'type']), ARRAY_FILTER_USE_KEY),
            ],
            attr: $attr,
        );
    }

    /**
     * @param      array<string, mixed>|\ArrayObject<string, mixed>  $attr      The attribute
     */
    public static function MyMetaValue(array|ArrayObject $attr): string
    {
        $attr = $attr instanceof ArrayObject ? $attr : new ArrayObject($attr);

        return Code::getPHPTemplateValueCode(
            FrontendTemplateCode::MyMetaValue(...),
            [
                self::metaID($attr),
                array_filter($attr->getArrayCopy(), fn ($key): bool => !in_array($key, ['id', 'type']), ARRAY_FILTER_USE_KEY),
            ],
            attr: $attr,
        );
    }

    /**
     * @param      array<string, mixed>|\ArrayObject<string, mixed>  $attr      The attribute
     * @param      string                                            $content   The content
     */
    public static function EntryMyMetaIf(array|ArrayObject $attr, string $content): string
    {
        $attr = $attr instanceof ArrayObject ? $attr : new ArrayObject($attr);

        $operator = isset($attr['operator']) ? self::getOperator($attr['operator']) : '&&';

        /**
         * Warning: Take care of $mymeta_value variable used in template code
         * Should be renamed here if renamed in FrontendTemplateCode::EntryMyMetaIf() code.
         */
        $if = [];
        if (isset($attr['defined'])) {
            $sign = ($attr['defined'] == 'true' || $attr['defined'] == '1') ? '!' : '';
            $if[] = $sign . 'empty($mymeta_value)';
        }
        if (isset($attr['value'])) {
            $value = $attr['value'];
            $if[]  = substr((string) $value, 1, 1) === '!' ?
                '$mymeta_value !=' . var_export(substr((string) $value, 1), true) :
                '$mymeta_value ==' . var_export($value, true);
        }
        $test = implode(' ' . $operator . ' ', $if);

        if ($if === []) {
            return '';
        }

        return Code::getPHPTemplateBlockCode(
            FrontendTemplateCode::EntryMyMetaIf(...),
            [
                self::metaID($attr),
                $test,
            ],
            $content,
            $attr,
        );
    }

    /**
     * @param      array<string, mixed>|\ArrayObject<string, mixed>  $attr      The attribute
     * @param      string                                            $content   The content
     */
    public static function MyMetaData(array|ArrayObject $attr, string $content): string
    {
        $attr = $attr instanceof ArrayObject ? $attr : new ArrayObject($attr);

        $limit = isset($attr['limit']) ? (int) $attr['limit'] : null;
        $combo = ['meta_id_lower', 'count', 'latest', 'oldest'];

        $sortby = 'meta_id_lower';
        if (isset($attr['sortby']) && in_array($attr['sortby'], $combo)) {
            $sortby = mb_strtolower((string) $attr['sortby']);
        }

        $order = 'asc';
        if (isset($attr['order']) && $attr['order'] == 'desc') {
            $order = 'desc';
        }

        return Code::getPHPTemplateBlockCode(
            FrontendTemplateCode::MyMetaData(...),
            [
                self::metaID($attr),
                $limit,
                $sortby,
                $order,
            ],
            $content,
            $attr,
        );
    }

    // Helpers

    /**
     * Gets the operator.
     *
     * @param      string  $op     The operator
     */
    protected static function getOperator(string $op): string
    {
        return match (strtolower($op)) {
            'or', '||' => '||',
            default => '&&',
        };
    }

    /**
     * Gets meta ID from attributes, if present
     *
     * @param      ArrayObject<string, mixed>         $attr   The attributes
     */
    protected static function metaID(ArrayObject $attr): string
    {
        if (isset($attr['type'])) {
            $attr['id'] = $attr['type'];
        }

        if (isset($attr['id']) && preg_match('/[a-zA-Z0-9-_]+/', (string) $attr['id'])) {
            return (string) $attr['id'];
        }

        return '';
    }
}
