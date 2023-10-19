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
use Dotclear\App;
use Dotclear\Helper\Html\Html;
use Dotclear\Plugin\widgets\WidgetsElement;

class FrontendWidgets
{
    public static function mymetaList(WidgetsElement $w): string
    {
        if ($w->offline) {
            return '';
        }

        if (($w->homeonly == 1 && !dcCore::app()->url->isHome(dcCore::app()->url->type)) || ($w->homeonly == 2 && dcCore::app()->url->isHome(dcCore::app()->url->type))) {
            return '';
        }

        $allmeta  = dcCore::app()->mymeta->getAll();
        $prompt   = ($w->prompt == 'prompt');
        $items    = [];
        $base_url = App::blog()->url() . dcCore::app()->url->getBase('mymeta') . '/';
        $section  = '';
        if ($w->section != '') {
            $section      = $w->section;
            $display_meta = false;
        } else {
            $display_meta = true;
        }
        foreach ($allmeta as $meta) {
            if ($meta instanceof MyMetaSection) {
                if ($meta->id == $section) {
                    $display_meta = true;
                } elseif ($section != '') {
                    $display_meta = false;
                }
            } elseif ($display_meta && $meta->enabled
                                    && $meta->url_list_enabled) {
                $items[] = '<li><a href="' . $base_url . rawurlencode($meta->id) . '">' .
                    Html::escapeHTML($prompt ? $meta->prompt : $meta->id) . '</a></li>';
            }
        }
        if (count($items) == 0) {
            return '';
        }

        $res = ($w->title ? $w->renderTitle(Html::escapeHTML($w->title)) : '') . '<ul>' . join('', $items) . '</ul>';

        return $w->renderDiv((bool) $w->content_only, 'mymetalist ' . $w->class, '', $res);
    }

    public static function mymetaValues(WidgetsElement $w): string
    {
        if ($w->offline) {
            return '';
        }

        if (($w->homeonly == 1 && !dcCore::app()->url->isHome(dcCore::app()->url->type)) || ($w->homeonly == 2 && dcCore::app()->url->isHome(dcCore::app()->url->type))) {
            return '';
        }

        $res = ($w->title ? $w->renderTitle(Html::escapeHTML($w->title)) : '') . '<ul>';

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

        $base_url = App::blog()->url() . dcCore::app()->url->getBase('mymeta') . '/' . $mymetaEntry->id;
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
            Html::escapeHTML($w->allvalueslinktitle) . '</a></strong></p>';
        }

        return $w->renderDiv((bool) $w->content_only, 'mymetavalues ' . ($is_cloud ? ' tags' : '') . $w->class, '', $res);
    }
}
