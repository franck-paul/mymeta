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
if (!defined('DC_RC_PATH')) {
    return;
}

require_once __DIR__ . '/_widgets.php';

dcCore::app()->tpl->addValue('MetaType', ['tplMyMeta','MetaType']);
dcCore::app()->tpl->addValue('MyMetaTypePrompt', ['tplMyMeta','MyMetaTypePrompt']);
dcCore::app()->tpl->addValue('EntryMyMetaValue', ['tplMyMeta','EntryMyMetaValue']);
dcCore::app()->tpl->addValue('MyMetaValue', ['tplMyMeta','MyMetaValue']);
dcCore::app()->tpl->addValue('MyMetaURL', ['tplMyMeta','MyMetaURL']);
dcCore::app()->tpl->addBlock('EntryMyMetaIf', ['tplMyMeta','EntryMyMetaIf']);
dcCore::app()->tpl->addBlock('MyMetaIf', ['tplMyMeta','EntryMyMetaIf']);
dcCore::app()->tpl->addBlock('MyMetaData', ['tplMyMeta','MyMetaData']);

dcCore::app()->addBehaviors([
    'templateBeforeBlockV2'  => ['behaviorsMymeta','templateBeforeBlock'],
    'publicBeforeDocumentV2' => ['behaviorsMymeta','addTplPath'],
]);

dcCore::app()->mymeta = new myMeta();

class behaviorsMymeta
{
    public static function addTplPath()
    {
        dcCore::app()->tpl->setPath(dcCore::app()->tpl->getPath(), __DIR__ . '/' . dcPublic::TPL_ROOT);
    }

    public static function templateBeforeBlock($b, $attr)
    {
        /* tpl:Entries extra attributes :
            <tpl:Entries mymetaid="<id>" mymetavalue="value1,value2,value3">
                selects mymeta entries having mymetaid <id> with values value1, value2 or value3
            <tpl:Entries mymetaid="<id>" mymetavalue="!value1,value2,value3">
                selects mymeta entries having mymetaid <id> with any value but value1, value2 or value3
            <tpl:Entries mymetaid="<id>">
                selects mymeta entries having mymetaid <id> set to any value
            <tpl:Entries mymetaid="!<id>">
                selects mymeta entries having mymetaid <id> not set
        */
        if ($b != 'Entries' && $b != 'Comments') {
            return;
        }
        if (!isset($attr['mymetaid'])) {
            if (empty($attr['no_context'])) {
                return
                '<?php if (dcCore::app()->ctx->exists("mymeta")) { ' .
                    "if (!isset(\$params)) { \$params = array(); }\n" .
                    "if (!isset(\$params['from'])) { \$params['from'] = ''; }\n" .
                    "if (!isset(\$params['sql'])) { \$params['sql'] = ''; }\n" .
                    "\$params['from'] .= ', '.dcCore::app()->prefix.'meta META ';\n" .
                    "\$params['sql'] .= 'AND META.post_id = P.post_id ';\n" .
                    "\$params['sql'] .= \"AND META.meta_type = '\".dcCore::app()->con->escape(dcCore::app()->ctx->mymeta->id).\"' \";\n" .
                    "\$params['sql'] .= \"AND META.meta_id = '\".dcCore::app()->con->escape(dcCore::app()->ctx->meta->meta_id).\"' \";\n" .
                "} ?>\n";
            }

            return;
        }
        $metaid = dcCore::app()->con->escape($attr['mymetaid']);
        if (isset($attr['mymetavalue'])) {
            $values  = $attr['mymetavalue'];
            $in_expr = ' in ';
            if (substr($values, 0, 1) == '!') {
                $in_expr = ' not in ';
                $values  = substr($values, 1);
            }
            $cond = [];
            foreach (explode(',', $values) as $expr) {
                $cond[] = "'" . dcCore::app()->con->escape($expr) . "'";
            }

            return
            "<?php\n" .
            "if (!isset(\$params)) { \$params = array(); }\n" .
            "if (!isset(\$params['from'])) { \$params['from'] = ''; }\n" .
            "if (!isset(\$params['sql'])) { \$params['sql'] = ''; }\n" .
            "@\$params['from'] .= ', '.dcCore::app()->prefix.'meta META ';\n" .
            "@\$params['sql'] .= 'AND META.post_id = P.post_id ';\n" .
            "\$params['sql'] .= \"AND META.meta_type = '" . $metaid . "' \";\n" .
            "\$params['sql'] .= \"AND META.meta_id " . $in_expr . ' (' . join(',', $cond) . ") \";\n" .
            "?>\n";
        }
        $in_expr = ' in ';
        if (substr($metaid, 0, 1) == '!') {
            $in_expr = ' not in ';
            $metaid  = substr($metaid, 1);
        }

        return
        "<?php\n" .
        "@\$params['sql'] .= \"AND P.post_id " . $in_expr .
            "(SELECT META.post_id from \".dcCore::app()->prefix.\"meta META where META.meta_type = '" . $metaid . "') \";\n" .
        "?>\n";
    }
}

class tplMyMeta
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
        $f   = tplMyMeta::getCommonMyMeta($attr);
        $res = '<?php if (dcCore::app()->ctx->mymeta != null && dcCore::app()->ctx->mymeta->enabled) echo dcCore::app()->ctx->mymeta->prompt; ?>' . "\n";

        return sprintf($f, $res);
    }

    public static function EntryMyMetaValue($attr)
    {
        $f = tplMyMeta::getCommonMyMeta($attr);

        $res = '<?php if (dcCore::app()->ctx->mymeta != null && dcCore::app()->ctx->mymeta->enabled)' . "\n" .
        'echo dcCore::app()->ctx->mymeta->getValue(dcCore::app()->mymeta->dcmeta->getMetaStr(dcCore::app()->ctx->posts->post_meta,dcCore::app()->ctx->mymeta->id),' .
        tplMyMeta::attr2str($attr) . '); ?>';

        return sprintf($f, $res);
    }

    public static function MyMetaValue($attr)
    {
        $f = tplMyMeta::getCommonMyMeta($attr);

        $res = '<?php if (dcCore::app()->ctx->mymeta != null && dcCore::app()->ctx->mymeta->enabled) {' . "\n" .
        'echo dcCore::app()->ctx->mymeta->getValue(dcCore::app()->ctx->meta->meta_id,' . tplMyMeta::attr2str($attr) . '); ' . "\n" .
        '} ?>';

        return sprintf($f, $res);
    }

    public static function EntryMyMetaIf($attr, $content)
    {
        $f        = tplMyMeta::getCommonMyMeta($attr);
        $if       = [];
        $operator = isset($attr['operator']) ? tplMyMeta::getOperator($attr['operator']) : '&&';
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
        $f     = tplMyMeta::getCommonMyMeta($attr);
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

class widgetsMyMeta
{
    public static function mymetaList($w)
    {
        if ($w->offline) {
            return;
        }

        if (($w->homeonly == 1 && !dcCore::app()->url->isHome(dcCore::app()->url->type)) || ($w->homeonly == 2 && dcCore::app()->url->isHome(dcCore::app()->url->type))) {
            return;
        }

        $allmeta  = dcCore::app()->mymeta->getAll();
        $prompt   = ($w->prompt == 'prompt');
        $items    = [];
        $base_url = dcCore::app()->blog->url . dcCore::app()->url->getBase('mymeta') . '/';
        $section  = '';
        if ($w->section != '') {
            $section      = $w->section;
            $display_meta = false;
        } else {
            $display_meta = true;
        }
        foreach ($allmeta as $k => $meta) {
            if ($meta instanceof myMetaSection) {
                if ($meta->id == $section) {
                    $display_meta = true;
                } elseif ($section != '') {
                    $display_meta = false;
                }
            } elseif ($display_meta && $meta->enabled
                                    && $meta->url_list_enabled) {
                $items[] = '<li><a href="' . $base_url . rawurlencode($meta->id) . '">' .
                    html::escapeHTML($prompt ? $meta->prompt : $meta->id) . '</a></li>';
            }
        }
        if (count($items) == 0) {
            return;
        }

        $res = ($w->title ? $w->renderTitle(html::escapeHTML($w->title)) : '') . '<ul>' . join('', $items) . '</ul>';

        return $w->renderDiv($w->content_only, 'mymetalist ' . $w->class, '', $res);
    }

    public static function mymetaValues($w)
    {
        if ($w->offline) {
            return;
        }

        if (($w->homeonly == 1 && !dcCore::app()->url->isHome(dcCore::app()->url->type)) || ($w->homeonly == 2 && dcCore::app()->url->isHome(dcCore::app()->url->type))) {
            return;
        }

        $res = ($w->title ? $w->renderTitle(html::escapeHTML($w->title)) : '') . '<ul>';

        $limit       = abs((int) $w->limit);
        $is_cloud    = ($w->displaymode == 'cloud');
        $mymetaEntry = dcCore::app()->mymeta->getByID($w->mymetaid);

        if ($mymetaEntry == null || !$mymetaEntry->enabled) {
            return '<li>not enabled</li>';
        }

        $rs = dcCore::app()->mymeta->getMeta($mymetaEntry->id, $limit);

        if ($rs->isEmpty()) {
            return '<li>empty</li>';
        }

        $sort = $w->sortby;
        if (!in_array($sort, ['meta_id_lower','count'])) {
            $sort = 'meta_id_lower';
        }

        $order = $w->orderby;
        if ($order != 'asc') {
            $order = 'desc';
        }

        $rs->sort($sort, $order);

        $base_url = dcCore::app()->blog->url . dcCore::app()->url->getBase('mymeta') . '/' . $mymetaEntry->id;
        while ($rs->fetch()) {
            $class = '';
            if ($is_cloud) {
                $class = 'class="tag' . $rs->roundpercent . '" ';
            }
            $res .= '<li><a href="' . $base_url . '/' . rawurlencode($rs->meta_id) . '" ' . $class . 'rel="tag">' .
                $rs->meta_id . '</a></li>';
        }

        $res .= '</ul>';

        if ($mymetaEntry->url_list_enabled && !is_null($w->allvalueslinktitle) && $w->allvalueslinktitle !== '') {
            $res .= '<p><strong><a href="' . $base_url . '">' .
            html::escapeHTML($w->allvalueslinktitle) . '</a></strong></p>';
        }

        return $w->renderDiv($w->content_only, 'mymetavalues ' . ($is_cloud ? ' tags' : '') . $w->class, '', $res);
    }
}

class urlMymeta extends dcUrlHandlers
{
    public static function tag($args)
    {
        $n = self::getPageNumber($args);
        if ($args == '' && !$n) {
            self::p404();
        } else {
            if ($n) {
                dcCore::app()->public->setPageNumber($n);
            }
            $values = explode('/', $args);
            $mymeta = dcCore::app()->mymeta->getByID($values[0]);
            if ($mymeta == null || !$mymeta->enabled) {
                self::p404();
            }
            dcCore::app()->ctx->mymeta = $mymeta;

            if (sizeof($values) == 1) {
                $tpl = ($mymeta->tpl_list == '') ? 'mymetas.html' : $mymeta->tpl_list;
                if ($mymeta->url_list_enabled && dcCore::app()->tpl->getFilePath($tpl)) {
                    self::serveDocument($tpl);
                } else {
                    self::p404();
                }
            } else {
                $mymeta_value = $values[1];

                dcCore::app()->ctx->meta = dcCore::app()->meta->computeMetaStats(dcCore::app()->mymeta->dcmeta->getMetadata([
                    'meta_id'   => $mymeta->id,
                    'meta_type' => $mymeta_value,
                ]));

                $tpl = ($mymeta->tpl_single == '') ? 'mymeta.html' : $mymeta->tpl_single;
                if (!dcCore::app()->ctx->meta->isEmpty() && $mymeta->url_single_enabled && dcCore::app()->tpl->getFilePath($tpl)) {
                    self::serveDocument($tpl);
                } else {
                    self::p404();
                }
            }
        }
    }
}
