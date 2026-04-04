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
use Dotclear\Core\Backend\Listing\Listing;
use Dotclear\Database\MetaRecord;
use Dotclear\Helper\Html\Form\Link;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Strong;
use Dotclear\Helper\Html\Form\Table;
use Dotclear\Helper\Html\Form\Tbody;
use Dotclear\Helper\Html\Form\Td;
use Dotclear\Helper\Html\Form\Th;
use Dotclear\Helper\Html\Form\Thead;
use Dotclear\Helper\Html\Form\Tr;

class BackendList extends Listing
{
    public function display(int $page, int $nb_per_page, string $enclose_block = '%s'): void
    {
        if ($this->rs->isEmpty()) {
            echo (new Para())
                ->items([
                    (new Strong(__('No entries found'))),
                ])
            ->render();

            return;
        }

        $pager  = App::backend()->listing()->pager($page, (int) $this->rs_count, $nb_per_page, $nb_per_page)->getLinks();
        $values = function (MetaRecord $rs) {
            /**
             * @var MyMetaField $mymetaEntry
             */
            $mymetaEntry = App::backend()->mymetaEntry;

            while ($rs->fetch()) {
                $meta_id = is_string($meta_id = $rs->meta_id) ? $meta_id : '';
                if ($meta_id !== '') {
                    $count = is_numeric($count = $rs->count) ? (int) $count : 0;
                    yield (new Tr())
                        ->class('line')
                        ->cols([
                            (new Td())
                                ->class('nowrap')
                                ->items([
                                    (new Link())
                                        ->href(App::backend()->getPageURL() . '&amp;m=viewposts&amp;id=' . $mymetaEntry->id . '&amp;value=' . rawurlencode($meta_id))
                                        ->text($mymetaEntry->displayValue($meta_id)),
                                ]),
                            (new Td())
                                ->class('nowrap')
                                ->text($count . ' ' . ($count <= 1 ? __('entry') : __('entries'))),
                        ]);
                }
            }
        };
        $buffer = (new Table())
            ->class('clear')
            ->thead((new Thead())
                ->rows([
                    (new Th())
                        ->text(__('Value')),
                    (new Th())
                        ->text(__('Nb Posts')),
                ]))
            ->tbody((new Tbody())
                ->rows([
                    ... $values($this->rs),
                ]))
        ->render();

        if ($enclose_block !== '') {
            $buffer = sprintf($enclose_block, $buffer);
        }

        echo $pager . $buffer . $pager;
    }
}
