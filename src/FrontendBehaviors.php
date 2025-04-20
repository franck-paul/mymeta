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
use Dotclear\Plugin\TemplateHelper\Code;

class FrontendBehaviors
{
    public static function addTplPath(): string
    {
        App::frontend()->template()->appendPath(My::tplPath());

        return '';
    }

    /**
     * @param      string                                               $b      The block
     * @param      array<string, string>|ArrayObject<string, string>    $attr   The attribute
     */
    public static function templateBeforeBlock(string $b, array|ArrayObject $attr): string
    {
        $attr = $attr instanceof ArrayObject ? $attr : new ArrayObject($attr);

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
        if ($b !== 'Entries' && $b !== 'Comments') {
            return '';
        }

        if (!isset($attr['mymetaid'])) {
            if (empty($attr['no_context'])) {
                return Code::getPHPCode(
                    self::metaAll(...),
                );
            }

            return '';
        }

        $metaid = App::con()->escapeStr($attr['mymetaid']);
        if (isset($attr['mymetavalue'])) {
            $values = $attr['mymetavalue'];
            if (str_starts_with((string) $values, '!')) {
                $values = substr((string) $values, 1);
            }

            $cond = [];
            foreach (explode(',', (string) $values) as $expr) {
                $cond[] = "'" . App::con()->escapeStr($expr) . "'";
            }

            return Code::getPHPCode(
                self::metaValue(...),
                [
                    !str_starts_with((string) $values, '!'),
                    $cond,
                    $metaid,
                ]
            );
        }

        if (str_starts_with((string) $metaid, '!')) {
            $metaid = substr((string) $metaid, 1);
        }

        return Code::getPHPCode(
            self::metaID(...),
            [
                !str_starts_with((string) $metaid, '!'),
                $metaid,
            ]
        );
    }

    // Template code methods

    private static function metaAll(
    ): void {
        global $params; // @phpcode-remove
        if (App::frontend()->context()->exists('mymeta')) {
            if (!isset($params)) {
                $params = [];
            }
            if (!isset($params['from'])) {
                $params['from'] = '';
            }
            if (!isset($params['sql'])) {
                $params['sql'] = '';
            }
            $params['from'] .= ', ' . App::con()->prefix() . 'meta META ';
            $params['sql']  .= 'AND META.post_id = P.post_id ';
            $params['sql']  .= "AND META.meta_type = '" . App::con()->escapeStr(App::frontend()->context()->mymeta->id) . "' ";
            $params['sql']  .= "AND META.meta_id = '" . App::con()->escapeStr(App::frontend()->context()->meta->meta_id) . "' ";
        }
    }

    /**
     * @param      array<int, string>        $_cond_    The condition
     */
    private static function metaValue(
        bool $_in_,
        array $_cond_,
        string $_metaid_
    ): void {
        global $params; // @phpcode-remove
        if (!isset($params)) {
            $params = [];
        }
        if (!isset($params['from'])) {
            $params['from'] = '';
        }
        if (!isset($params['sql'])) {
            $params['sql'] = '';
        }
        $params['from'] .= ', ' . App::con()->prefix() . 'meta META ';
        $params['sql']  .= 'AND META.post_id = P.post_id ';
        $params['sql']  .= 'AND META.meta_type = ' . $_metaid_ . ' ';
        $params['sql']  .= 'AND META.meta_id ' . ($_in_ ? 'in' : 'not in') . ' (' . implode(',', $_cond_) . ')';
    }

    private static function metaID(
        bool $_in_,
        string $_metaid_
    ): void {
        global $params; // @phpcode-remove
        if (!isset($params)) {
            $params = [];
        }
        if (!isset($params['sql'])) {
            $params['sql'] = '';
        }
        $params['sql'] .= 'AND P.post_id ' . ($_in_ ? 'in' : 'not in') . ' (SELECT META.post_id from ' . App::con()->prefix() . 'meta META WHERE META.meta_type = ' . $_metaid_ . ')';
    }
}
