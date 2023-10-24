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
use Dotclear\Core\Frontend\Utility;

class FrontendBehaviors
{
    public static function addTplPath(): string
    {
        App::frontend()->template()->appendPath(implode(DIRECTORY_SEPARATOR, [My::path(), Utility::TPL_ROOT]));

        return '';
    }

    /**
     * @param      string                                               $b      The block
     * @param      array<string, string>|ArrayObject<string, string>    $attr   The attribute
     *
     * @return     string
     */
    public static function templateBeforeBlock(string $b, array|ArrayObject $attr): string
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
            return '';
        }
        if (!isset($attr['mymetaid'])) {
            if (empty($attr['no_context'])) {
                return
                '<?php if (App::frontend()->context()->exists("mymeta")) { ' .
                    "if (!isset(\$params)) { \$params = array(); }\n" .
                    "if (!isset(\$params['from'])) { \$params['from'] = ''; }\n" .
                    "if (!isset(\$params['sql'])) { \$params['sql'] = ''; }\n" .
                    "\$params['from'] .= ', '.App::con()->prefix().'meta META ';\n" .
                    "\$params['sql'] .= 'AND META.post_id = P.post_id ';\n" .
                    "\$params['sql'] .= \"AND META.meta_type = '\".App::con()->escapeStr(App::frontend()->context()->mymeta->id).\"' \";\n" .
                    "\$params['sql'] .= \"AND META.meta_id = '\".App::con()->escapeStr(App::frontend()->context()->meta->meta_id).\"' \";\n" .
                "} ?>\n";
            }

            return '';
        }
        $metaid = App::con()->escapeStr($attr['mymetaid']);
        if (isset($attr['mymetavalue'])) {
            $values  = $attr['mymetavalue'];
            $in_expr = ' in ';
            if (substr($values, 0, 1) == '!') {
                $in_expr = ' not in ';
                $values  = substr($values, 1);
            }
            $cond = [];
            foreach (explode(',', $values) as $expr) {
                $cond[] = "'" . App::con()->escapeStr($expr) . "'";
            }

            return
            "<?php\n" .
            "if (!isset(\$params)) { \$params = array(); }\n" .
            "if (!isset(\$params['from'])) { \$params['from'] = ''; }\n" .
            "if (!isset(\$params['sql'])) { \$params['sql'] = ''; }\n" .
            "@\$params['from'] .= ', '.App::con()->prefix().'meta META ';\n" .
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
            "(SELECT META.post_id from \".App::con()->prefix().\"meta META where META.meta_type = '" . $metaid . "') \";\n" .
        "?>\n";
    }
}
