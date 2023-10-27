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
use Dotclear\Core\Backend\Listing\Pager;

class BackendList extends Listing
{
    public function display(int $page, int $nb_per_page, string $enclose_block = ''): void
    {
        if ($this->rs->isEmpty()) {
            echo '<p><strong>' . __('No entries found') . '</strong></p>';
        } else {
            $pager = new Pager($page, (int) $this->rs_count, $nb_per_page, $nb_per_page);

            $html_block = '<table class="clear"><tr><th>' . __('Value') . '</th>' .
            '<th>' . __('Nb Posts') . '</th>' .
            '</tr>%s</table>';

            if ($enclose_block !== '' && $enclose_block !== '0') {
                $html_block = sprintf($enclose_block, $html_block);
            }

            echo $pager->getLinks();

            $blocks = explode('%s', $html_block);

            echo $blocks[0];
            while ($this->rs->fetch()) {
                echo $this->postLine();
            }

            echo $blocks[1];

            echo $pager->getLinks();
        }
    }

    private function postLine(): string
    {
        return
        '<tr class="line"><td class="nowrap"><a href="' . App::backend()->getPageURL() . '&amp;m=viewposts&amp;id=' . App::backend()->mymetaEntry->id . '&amp;value=' . rawurlencode($this->rs->meta_id) . '">' . App::backend()->mymetaEntry->displayValue($this->rs->meta_id) . '</a></td>' .
        '<td class="nowrap">' . $this->rs->count . ' ' . (($this->rs->count <= 1) ? __('entry') : __('entries')) . '</td>' .
        '</tr>';
    }
}
