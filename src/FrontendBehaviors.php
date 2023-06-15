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
use dcPublic;

class FrontendBehaviors
{
    public static function addTplPath()
    {
        dcCore::app()->tpl->setPath(dcCore::app()->tpl->getPath(), My::path() . '/' . dcPublic::TPL_ROOT);
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
