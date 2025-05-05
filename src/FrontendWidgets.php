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

        $allmeta  = App::frontend()->mymeta->getAll();
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

        if (count($items) == 0) {
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

        $limit       = abs((int) $w->get('limit'));
        $is_cloud    = ($w->get('displaymode') === 'cloud');
        $mymetaEntry = App::frontend()->mymeta->getByID($w->get('mymetaid'));

        if (!$mymetaEntry || !$mymetaEntry->enabled) {
            return '';
        }

        $rs = App::frontend()->mymeta->getMeta((string) $mymetaEntry->id, (string) $limit);

        if ($rs->isEmpty()) {
            return '';
        }

        $sort = $w->get('sortby');
        if (!in_array($sort, ['meta_id_lower','count'])) {
            $sort = 'meta_id_lower';
        }

        $order = $w->get('orderby');
        if ($order !== 'asc') {
            $order = 'desc';
        }

        $rs->sort($sort, $order);

        $base_url = App::blog()->url() . App::url()->getBase('mymeta') . '/' . $mymetaEntry->id;

        $items = [];
        while ($rs->fetch()) {
            $class = '';
            if ($is_cloud) {
                $class = 'tag' . $rs->roundpercent;
            }

            $items[] = (new Li())
                ->items([
                    (new Link())
                        ->href($base_url . '/' . rawurlencode((string) $rs->meta_id))
                        ->class($class)
                        ->text($rs->meta_id)
                        ->extra('rel="tag"'),
                ]);
        }

        $list = (new Ul())
            ->items($items);

        $all = (new None());
        if ($mymetaEntry->url_list_enabled && !is_null($w->get('allvalueslinktitle')) && $w->get('allvalueslinktitle') !== '') {
            $all = (new Para())
                ->items([
                    (new Link())
                        ->href($base_url)
                        ->items([
                            (new Strong(Html::escapeHTML($w->get('allvalueslinktitle')))),
                        ]),
                ]);
        }

        return $w->renderDiv(
            (bool) $w->content_only,
            'mymetavalues ' . ($is_cloud ? ' tags' : '') . $w->class,
            '',
            ($w->title ? $w->renderTitle(Html::escapeHTML($w->title)) : '') . $list->render() . $all->render()
        );
    }
}
