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

use dcCore;

class FrontendTemplate
{
    public static function getCommonMyMeta($attr)
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
    protected static function attr2str($attr)
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

    public static function getOperator($op)
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

    public static function MyMetaURL($attr)
    {
        $f = dcCore::app()->tpl->getFilters($attr);

        return '<?php echo ' . sprintf($f, 'dcCore::app()->blog->url.dcCore::app()->url->getBase("mymeta").' .
        '"/".dcCore::app()->ctx->mymeta->id."/".rawurlencode(dcCore::app()->ctx->meta->meta_id)') . '; ?>';
    }

    public static function MetaType($attr)
    {
        $f = dcCore::app()->tpl->getFilters($attr);

        return '<?php echo ' . sprintf($f, 'dcCore::app()->ctx->meta->meta_type') . '; ?>';
    }

    public static function MyMetaTypePrompt($attr)
    {
        $f   = FrontendTemplate::getCommonMyMeta($attr);
        $res = '<?php if (dcCore::app()->ctx->mymeta != null && dcCore::app()->ctx->mymeta->enabled) echo dcCore::app()->ctx->mymeta->prompt; ?>' . "\n";

        return sprintf($f, $res);
    }

    public static function EntryMyMetaValue($attr)
    {
        $f = FrontendTemplate::getCommonMyMeta($attr);

        $res = '<?php if (dcCore::app()->ctx->mymeta != null && dcCore::app()->ctx->mymeta->enabled)' . "\n" .
        'echo dcCore::app()->ctx->mymeta->getValue(dcCore::app()->mymeta->dcmeta->getMetaStr(dcCore::app()->ctx->posts->post_meta,dcCore::app()->ctx->mymeta->id),' .
        FrontendTemplate::attr2str($attr) . '); ?>';

        return sprintf($f, $res);
    }

    public static function MyMetaValue($attr)
    {
        $f = FrontendTemplate::getCommonMyMeta($attr);

        $res = '<?php if (dcCore::app()->ctx->mymeta != null && dcCore::app()->ctx->mymeta->enabled) {' . "\n" .
        'echo dcCore::app()->ctx->mymeta->getValue(dcCore::app()->ctx->meta->meta_id,' . FrontendTemplate::attr2str($attr) . '); ' . "\n" .
        '} ?>';

        return sprintf($f, $res);
    }

    public static function EntryMyMetaIf($attr, $content)
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

    public static function MyMetaData($attr, $content)
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
