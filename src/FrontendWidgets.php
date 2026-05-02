<?php

/**
 * @brief mymeta, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @author Franck Paul and contributors
 *
 * @copyright Franck Paul contact@open-time.net
 * @copyright GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
declare(strict_types=1);

namespace Dotclear\Plugin\mymeta;

use Dotclear\App;
use Dotclear\Helper\Html\Form\Li;
use Dotclear\Helper\Html\Form\Link;
use Dotclear\Helper\Html\Form\None;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Strong;
use Dotclear\Helper\Html\Form\Ul;
use Dotclear\Helper\Html\Html;
use Dotclear\Plugin\widgets\WidgetsElement;

class FrontendWidgets
{
    public static function mymetaList(WidgetsElement $w): string
    {
        if ($w->offline) {
            return '';
        }

        if (($w->homeonly == 1 && !App::url()->isHome(App::url()->getType())) || ($w->homeonly == 2 && App::url()->isHome(App::url()->getType()))) {
            return '';
        }

        if (!App::frontend()->mymeta instanceof MyMeta) {
            return '';
        }

        $mymeta = App::frontend()->mymeta;

        $allmeta  = $mymeta->getAll();
        $prompt   = ($w->get('prompt') === 'prompt');
        $base_url = App::blog()->url() . App::url()->getBase('mymeta') . '/';
        $section  = '';
        if ($w->get('section') !== '') {
            $section      = $w->get('section');
            $display_meta = false;
        } else {
            $display_meta = true;
        }

        $items = [];
        foreach ($allmeta as $meta) {
            if ($meta instanceof MyMetaSection) {
                if ($meta->id == $section) {
                    $display_meta = true;
                } elseif ($section != '') {
                    $display_meta = false;
                }
            } elseif ($display_meta && $meta->enabled
                                    && $meta->url_list_enabled) {
                $items[] = (new Li())
                    ->items([
                        (new Link())
                            ->href($base_url . rawurlencode((string) $meta->id))
                            ->text(Html::escapeHTML($prompt ? $meta->prompt : $meta->id)),
                    ]);
            }
        }

        if ($items === []) {
            return '';
        }

        $list = (new Ul())
            ->items($items);

        return $w->renderDiv(
            (bool) $w->content_only,
            'mymetalist ' . $w->class,
            '',
            ($w->title ? $w->renderTitle(Html::escapeHTML($w->title)) : '') . $list->render()
        );
    }

    public static function mymetaValues(WidgetsElement $w): string
    {
        if ($w->offline) {
            return '';
        }

        if (($w->homeonly == 1 && !App::url()->isHome(App::url()->getType())) || ($w->homeonly == 2 && App::url()->isHome(App::url()->getType()))) {
            return '';
        }

        if (!App::frontend()->mymeta instanceof MyMeta) {
            return '';
        }

        $mymeta = App::frontend()->mymeta;

        $limit    = is_numeric($limit = $w->get('limit')) ? abs((int) $limit) : 0;
        $is_cloud = ($w->get('displaymode') === 'cloud');

        $id = is_string($id = $w->get('mymetaid')) ? $id : '';
        if ($id === '') {
            return '';
        }

        $entry = $mymeta->getByID($id);

        if ($entry === null || !$entry instanceof MyMetaField || !$entry->enabled) {
            return '';
        }

        $rs = $mymeta->getMeta($entry->id, (string) $limit);

        if ($rs->isEmpty()) {
            return '';
        }

        $sort = is_string($sort = $w->get('sortby')) ? $sort : '';
        if (!in_array($sort, ['meta_id_lower', 'count'], true)) {
            $sort = 'meta_id_lower';
        }

        $order = is_string($order = $w->get('orderby')) ? $order : '';
        if ($order !== 'asc') {
            $order = 'desc';
        }

        $rs->sort($sort, $order);

        $base_url = App::blog()->url() . App::url()->getBase('mymeta') . '/' . $entry->id;

        $items = [];
        while ($rs->fetch()) {
            $class = '';
            if ($is_cloud) {
                $decile = is_numeric($decile = $rs->roundpercent) ? (int) $decile : 0;
                $class  = 'tag' . $decile;
            }

            $id = is_string($id = $rs->meta_id) ? $id : '';
            if ($id !== '') {
                $items[] = (new Li())
                    ->items([
                        (new Link())
                            ->href($base_url . '/' . rawurlencode($id))
                            ->class($class)
                            ->text($id)
                            ->extra('rel="tag"'),
                    ]);
            }
        }

        $list = (new Ul())
            ->items($items);

        $all = (new None());
        if ($entry->url_list_enabled && !is_null($w->get('allvalueslinktitle')) && $w->get('allvalueslinktitle') !== '') {
            $title = is_string($title = $w->get('allvalueslinktitle')) ? $title : '';

            if ($title !== '') {
                $all = (new Para())
                    ->items([
                        (new Link())
                            ->href($base_url)
                            ->items([
                                (new Strong(Html::escapeHTML($title))),
                            ]),
                    ]);
            }
        }

        return $w->renderDiv(
            (bool) $w->content_only,
            'mymetavalues ' . ($is_cloud ? ' tags' : '') . $w->class,
            '',
            ($w->title ? $w->renderTitle(Html::escapeHTML($w->title)) : '') . $list->render() . $all->render()
        );
    }
}
