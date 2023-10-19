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
use dcCore;

class FrontendTemplate
{
    /**
     * @param      array<string, mixed>|\ArrayObject<string, mixed>  $attr      The attribute
     *
     * @return     string
     */
    public static function getCommonMyMeta(array|ArrayObject $attr): string
    {
        if (isset($attr['type'])) {
            $attr['id'] = $attr['type'];
        }
        if (isset($attr['id']) && preg_match('/[a-zA-Z0-9-_]+/', (string) $attr['id'])) {
            return '<?php' . "\n" .
            'dcCore::app()->ctx->mymeta = dcCore::app()->mymeta->getByID(\'' . $attr['id'] . '\'); ?>' . "\n" .
            '%s' . "\n" .
            '<?php dcCore::app()->ctx->mymeta = null;' . "\n" . '?>';
        }

        return '%s';
    }

    /**
     * @param      ArrayObject<string, mixed>|array<string, mixed>  $attr   The attribute
     *
     * @return     string
     */
    protected static function attr2str(array|ArrayObject $attr): string
    {
        $filter = ['id','type'];
        $a      = [];
        foreach ($attr as $k => $v) {
            if (!in_array($k, $filter)) {
                $a[] = "'" . addslashes($k) . "' =>'" . addslashes($v) . "'";
            }
        }

        return 'array(' . join(',', $a) . ')';
    }

    public static function getOperator(string $op): string
    {
        switch (strtolower($op)) {
            case 'or':
            case '||':
                return '||';
            case 'and':
            case '&&':
            default:
                return '&&';
        }
    }

    /**
     * @param      array<string, mixed>|\ArrayObject<string, mixed>  $attr      The attribute
     *
     * @return     string
     */
    public static function MyMetaURL(array|ArrayObject $attr): string
    {
        $f = dcCore::app()->tpl->getFilters($attr);

        return '<?php echo ' . sprintf($f, 'App::blog()->url().dcCore::app()->url->getBase("mymeta").' .
        '"/".dcCore::app()->ctx->mymeta->id."/".rawurlencode(dcCore::app()->ctx->meta->meta_id)') . '; ?>';
    }

    /**
     * @param      array<string, mixed>|\ArrayObject<string, mixed>  $attr      The attribute
     *
     * @return     string
     */
    public static function MetaType(array|ArrayObject $attr): string
    {
        $f = dcCore::app()->tpl->getFilters($attr);

        return '<?php echo ' . sprintf($f, 'dcCore::app()->ctx->meta->meta_type') . '; ?>';
    }

    /**
     * @param      array<string, mixed>|\ArrayObject<string, mixed>  $attr      The attribute
     *
     * @return     string
     */
    public static function MyMetaTypePrompt(array|ArrayObject $attr): string
    {
        $f   = FrontendTemplate::getCommonMyMeta($attr);
        $res = '<?php if (dcCore::app()->ctx->mymeta != null && dcCore::app()->ctx->mymeta->enabled) echo dcCore::app()->ctx->mymeta->prompt; ?>' . "\n";

        return sprintf($f, $res);
    }

    /**
     * @param      array<string, mixed>|\ArrayObject<string, mixed>  $attr      The attribute
     *
     * @return     string
     */
    public static function EntryMyMetaValue(array|ArrayObject $attr): string
    {
        $f = FrontendTemplate::getCommonMyMeta($attr);

        $res = '<?php if (dcCore::app()->ctx->mymeta != null && dcCore::app()->ctx->mymeta->enabled)' . "\n" .
        'echo dcCore::app()->ctx->mymeta->getValue(dcCore::app()->mymeta->dcmeta->getMetaStr(dcCore::app()->ctx->posts->post_meta,dcCore::app()->ctx->mymeta->id),' .
        FrontendTemplate::attr2str($attr) . '); ?>';

        return sprintf($f, $res);
    }

    /**
     * @param      array<string, mixed>|\ArrayObject<string, mixed>  $attr      The attribute
     *
     * @return     string
     */
    public static function MyMetaValue(array|ArrayObject $attr): string
    {
        $f = FrontendTemplate::getCommonMyMeta($attr);

        $res = '<?php if (dcCore::app()->ctx->mymeta != null && dcCore::app()->ctx->mymeta->enabled) {' . "\n" .
        'echo dcCore::app()->ctx->mymeta->getValue(dcCore::app()->ctx->meta->meta_id,' . FrontendTemplate::attr2str($attr) . '); ' . "\n" .
        '} ?>';

        return sprintf($f, $res);
    }

    /**
     * @param      array<string, mixed>|\ArrayObject<string, mixed>  $attr      The attribute
     * @param      string                                            $content   The content
     *
     * @return     string
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
            if (substr($value, 1, 1) == '!') {
                $if[] = "\$value !='" . substr($value, 1) . "'";
            } else {
                $if[] = "\$value =='" . $value . "'";
            }
        }
        $res = '<?php' . "\n" .
        'if (dcCore::app()->ctx->mymeta != null && dcCore::app()->ctx->mymeta->enabled) :' . "\n" .
        '  $value=dcCore::app()->mymeta->dcmeta->getMetaStr(dcCore::app()->ctx->posts->post_meta,dcCore::app()->ctx->mymeta->id); ' . "\n" .
        '  if(' . implode(' ' . $operator . ' ', $if) . ') : ?>' .
        $content .
        '  <?php endif; ' . "\n" .
        'endif; ?>';

        return sprintf($f, $res);
    }

    /**
     * @param      array<string, mixed>|\ArrayObject<string, mixed>  $attr      The attribute
     * @param      string                                            $content   The content
     *
     * @return     string
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
        'dcCore::app()->ctx->meta = dcCore::app()->meta->computeMetaStats(dcCore::app()->mymeta->dcmeta->getMetadata([' .
            "'meta_id' => dcCore::app()->ctx->mymeta->id, " .
            "'limit' => " . $limit .
        '])); ' .
        "dcCore::app()->ctx->meta->sort('" . $sortby . "','" . $order . "'); " .
        '?>';

        $res .= '<?php while (dcCore::app()->ctx->meta->fetch()) : ' . "\n" .
        'dcCore::app()->ctx->mymeta = dcCore::app()->mymeta->getByID(dcCore::app()->ctx->meta->meta_type); ?>' . "\n" .
        $content . '<?php dcCore::app()->ctx->mymeta = null; endwhile; ' .
        'dcCore::app()->ctx->meta = null; ?>';

        return sprintf($f, $res);
    }
}
