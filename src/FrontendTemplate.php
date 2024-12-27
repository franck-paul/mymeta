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
use Dotclear\App;

class FrontendTemplate
{
    /**
     * @param      array<string, mixed>|\ArrayObject<string, mixed>  $attr      The attribute
     */
    public static function getCommonMyMeta(array|ArrayObject $attr): string
    {
        if (isset($attr['type'])) {
            $attr['id'] = $attr['type'];
        }

        if (isset($attr['id']) && preg_match('/[a-zA-Z0-9-_]+/', (string) $attr['id'])) {
            return '<?php' . "\n" .
            'App::frontend()->context()->mymeta = App::frontend()->mymeta->getByID(\'' . $attr['id'] . '\'); ?>' . "\n" .
            '%s' . "\n" .
            '<?php App::frontend()->context()->mymeta = null;' . "\n" . '?>';
        }

        return '%s';
    }

    /**
     * @param      ArrayObject<string, mixed>|array<string, mixed>  $attr   The attribute
     */
    protected static function attr2str(array|ArrayObject $attr): string
    {
        $filter = ['id','type'];
        $a      = [];
        foreach ($attr as $k => $v) {
            if (!in_array($k, $filter)) {
                $a[] = "'" . addslashes($k) . "' =>'" . addslashes((string) $v) . "'";
            }
        }

        return 'array(' . implode(',', $a) . ')';
    }

    public static function getOperator(string $op): string
    {
        return match (strtolower($op)) {
            'or', '||' => '||',
            default => '&&',
        };
    }

    /**
     * @param      array<string, mixed>|\ArrayObject<string, mixed>  $attr      The attribute
     */
    public static function MyMetaURL(array|ArrayObject $attr): string
    {
        $f = App::frontend()->template()->getFilters($attr);

        return '<?= ' . sprintf($f, 'App::blog()->url().App::url()->getBase("mymeta").' .
        '"/".App::frontend()->context()->mymeta->id."/".rawurlencode(App::frontend()->context()->meta->meta_id)') . ' ?>';
    }

    /**
     * @param      array<string, mixed>|\ArrayObject<string, mixed>  $attr      The attribute
     */
    public static function MetaType(array|ArrayObject $attr): string
    {
        $f = App::frontend()->template()->getFilters($attr);

        return '<?= ' . sprintf($f, 'App::frontend()->context()->meta->meta_type') . ' ?>';
    }

    /**
     * @param      array<string, mixed>|\ArrayObject<string, mixed>  $attr      The attribute
     */
    public static function MyMetaTypePrompt(array|ArrayObject $attr): string
    {
        $f   = FrontendTemplate::getCommonMyMeta($attr);
        $res = '<?php if (App::frontend()->context()->mymeta != null && App::frontend()->context()->mymeta->enabled) echo App::frontend()->context()->mymeta->prompt; ?>' . "\n";

        return sprintf($f, $res);
    }

    /**
     * @param      array<string, mixed>|\ArrayObject<string, mixed>  $attr      The attribute
     */
    public static function EntryMyMetaValue(array|ArrayObject $attr): string
    {
        $f = FrontendTemplate::getCommonMyMeta($attr);

        $res = '<?php if (App::frontend()->context()->mymeta != null && App::frontend()->context()->mymeta->enabled)' . "\n" .
        'echo App::frontend()->context()->mymeta->getValue(App::frontend()->mymeta->dcmeta->getMetaStr(App::frontend()->context()->posts->post_meta,App::frontend()->context()->mymeta->id),' .
        FrontendTemplate::attr2str($attr) . '); ?>';

        return sprintf($f, $res);
    }

    /**
     * @param      array<string, mixed>|\ArrayObject<string, mixed>  $attr      The attribute
     */
    public static function MyMetaValue(array|ArrayObject $attr): string
    {
        $f = FrontendTemplate::getCommonMyMeta($attr);

        $res = '<?php if (App::frontend()->context()->mymeta != null && App::frontend()->context()->mymeta->enabled) {' . "\n" .
        'echo App::frontend()->context()->mymeta->getValue(App::frontend()->context()->meta->meta_id,' . FrontendTemplate::attr2str($attr) . '); ' . "\n" .
        '} ?>';

        return sprintf($f, $res);
    }

    /**
     * @param      array<string, mixed>|\ArrayObject<string, mixed>  $attr      The attribute
     * @param      string                                            $content   The content
     */
    public static function EntryMyMetaIf(array|ArrayObject $attr, string $content): string
    {
        $f        = FrontendTemplate::getCommonMyMeta($attr);
        $if       = [];
        $operator = isset($attr['operator']) ? FrontendTemplate::getOperator($attr['operator']) : '&&';
        if (isset($attr['defined'])) {
            $sign = ($attr['defined'] == 'true' || $attr['defined'] == '1') ? '!' : '';
            $if[] = $sign . 'empty($value)';
        }

        if (isset($attr['value'])) {
            $value = $attr['value'];
            $if[]  = substr((string) $value, 1, 1) === '!' ? "\$value !='" . substr((string) $value, 1) . "'" : "\$value =='" . $value . "'";
        }

        $res = '<?php' . "\n" .
        'if (App::frontend()->context()->mymeta != null && App::frontend()->context()->mymeta->enabled) :' . "\n" .
        '  $value=App::frontend()->mymeta->dcmeta->getMetaStr(App::frontend()->context()->posts->post_meta,App::frontend()->context()->mymeta->id); ' . "\n" .
        '  if(' . implode(' ' . $operator . ' ', $if) . ') : ?>' .
        $content .
        '  <?php endif; ' . "\n" .
        'endif; ?>';

        return sprintf($f, $res);
    }

    /**
     * @param      array<string, mixed>|\ArrayObject<string, mixed>  $attr      The attribute
     * @param      string                                            $content   The content
     */
    public static function MyMetaData(array|ArrayObject $attr, string $content): string
    {
        $f     = FrontendTemplate::getCommonMyMeta($attr);
        $limit = isset($attr['limit']) ? (int) $attr['limit'] : 'null';

        $sortby = 'meta_id_lower';
        if (isset($attr['sortby']) && $attr['sortby'] == 'count') {
            $sortby = 'count';
        }

        $order = 'asc';
        if (isset($attr['order']) && $attr['order'] == 'desc') {
            $order = 'desc';
        }

        $res = "<?php\n" .
        'App::frontend()->context()->meta = App::meta()->computeMetaStats(App::frontend()->mymeta->dcmeta->getMetadata([' .
            "'meta_id' => App::frontend()->context()->mymeta->id, " .
            "'limit' => " . $limit .
        '])); ' .
        "App::frontend()->context()->meta->sort('" . $sortby . "','" . $order . "'); " .
        '?>';

        $res .= '<?php while (App::frontend()->context()->meta->fetch()) : ' . "\n" .
        'App::frontend()->context()->mymeta = App::frontend()->mymeta->getByID(App::frontend()->context()->meta->meta_type); ?>' . "\n" .
        $content . '<?php App::frontend()->context()->mymeta = null; endwhile; ' .
        'App::frontend()->context()->meta = null; ?>';

        return sprintf($f, $res);
    }
}
