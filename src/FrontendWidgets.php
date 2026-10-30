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
    public static function mymetaList(WidgetsElement $widgetsElement): string
    {
        if ($widgetsElement->isOffline()) {
            return '';
        }

        if (!$widgetsElement->checkHomeOnly(App::url()->getType())) {
            return '';
        }

        if (!App::frontend()->mymeta instanceof MyMeta) {
            return '';
        }

        $mymeta = App::frontend()->mymeta;

        $allmeta  = $mymeta->getAll();
        $prompt   = ($widgetsElement->get('prompt') === 'prompt');
        $base_url = App::blog()->url() . App::url()->getBase('mymeta') . '/';
        $section  = '';
        if ($widgetsElement->get('section') !== '') {
            $section      = $widgetsElement->get('section');
            $display_meta = false;
        } else {
            $display_meta = true;
        }

        $items = [];
        foreach ($allmeta as $allmetum) {
            if ($allmetum instanceof MyMetaSection) {
                if ($allmetum->id === $section) {
                    $display_meta = true;
                } elseif ($section !== '') {
                    $display_meta = false;
                }
            } elseif ($display_meta
                && $allmetum->enabled
                && $allmetum->url_list_enabled
            ) {
                $items[] = (new Li())
                    ->items([
                        (new Link())
                            ->href($base_url . rawurlencode($allmetum->id))
                            ->text(Html::escapeHTML($prompt ? $allmetum->prompt : $allmetum->id)),
                    ]);
            }
        }

        if ($items === []) {
            return '';
        }

        $list = (new Ul())
            ->items($items);

        return $widgetsElement->renderDiv(
            $widgetsElement->getContentOnly(),
            'mymetalist ' . $widgetsElement->getClass(),
            '',
            ($widgetsElement->getTitle() !== '' ? $widgetsElement->renderTitle(Html::escapeHTML($widgetsElement->getTitle())) : '') . $list->render()
        );
    }

    public static function mymetaValues(WidgetsElement $widgetsElement): string
    {
        if ($widgetsElement->isOffline()) {
            return '';
        }

        if (!$widgetsElement->checkHomeOnly(App::url()->getType())) {
            return '';
        }

        if (!App::frontend()->mymeta instanceof MyMeta) {
            return '';
        }

        $mymeta = App::frontend()->mymeta;

        $limit    = is_numeric($limit = $widgetsElement->get('limit')) ? abs((int) $limit) : 0;
        $is_cloud = ($widgetsElement->get('displaymode') === 'cloud');

        $id = is_string($id = $widgetsElement->get('mymetaid')) ? $id : '';
        if ($id === '') {
            return '';
        }

        $entry = $mymeta->getByID($id);

        if ($entry === null
            || !$entry instanceof MyMetaField
            || !$entry->enabled
        ) {
            return '';
        }

        $metaRecord = $mymeta->getMeta($entry->id, (string) $limit);

        if ($metaRecord->isEmpty()) {
            return '';
        }

        $sort = is_string($sort = $widgetsElement->get('sortby')) ? $sort : '';
        if (!in_array($sort, ['meta_id_lower', 'count'], true)) {
            $sort = 'meta_id_lower';
        }

        $order = is_string($order = $widgetsElement->get('orderby')) ? $order : '';
        if ($order !== 'asc') {
            $order = 'desc';
        }

        $metaRecord->sort($sort, $order);

        $base_url = App::blog()->url() . App::url()->getBase('mymeta') . '/' . $entry->id;

        $items = [];
        while ($metaRecord->fetch()) {
            $class = '';
            if ($is_cloud) {
                $decile = $metaRecord->intField('roundpercent');
                $class  = 'tag' . $decile;
            }

            $id = $metaRecord->strField('meta_id');
            if ($id !== '') {
                $items[] = (new Li())
                    ->items([
                        (new Link())
                            ->href($base_url . '/' . rawurlencode($id))
                            ->class($class)
                            ->text($id)
                            ->rel('tag'),
                    ]);
            }
        }

        $list = (new Ul())
            ->items($items);

        $all = (new None());
        if ($entry->url_list_enabled
            && !is_null($widgetsElement->get('allvalueslinktitle'))
            && $widgetsElement->get('allvalueslinktitle') !== ''
        ) {
            $title = is_string($title = $widgetsElement->get('allvalueslinktitle')) ? $title : '';

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

        return $widgetsElement->renderDiv(
            $widgetsElement->getContentOnly(),
            'mymetavalues ' . ($is_cloud ? ' tags' : '') . $widgetsElement->getClass(),
            '',
            ($widgetsElement->getTitle() !== '' ? $widgetsElement->renderTitle(Html::escapeHTML($widgetsElement->getTitle())) : '') . $list->render() . $all->render()
        );
    }
}
