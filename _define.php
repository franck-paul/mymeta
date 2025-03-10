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
$this->registerModule(
    'My Meta',
    'User-defined metadata management in posts',
    'Bruno Hondelatte and contributors',
    '6.1',
    [
        'date'        => '2025-02-26T16:05:52+0100',
        'requires'    => [['core', '2.33']],
        'priority'    => 1001,
        'permissions' => 'My',
        'type'        => 'plugin',
        'settings'    => [
            'self' => '',
        ],

        'details'    => 'https://open-time.net/?q=mymeta',
        'support'    => 'https://github.com/franck-paul/mymeta',
        'repository' => 'https://raw.githubusercontent.com/franck-paul/mymeta/main/dcstore.xml',
    ]
);
