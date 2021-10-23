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
if (!defined('DC_RC_PATH')) {
    return;
}

$this->registerModule(
    'My Meta',                                      // Name
    'User-defined metadata management in posts',    // Description
    'Bruno Hondelatte and contributors',            // Author
    '0.5.4',                                        // Version
    [
        //        'requires'    => [['core', '2.17']],
        'priority'    => 1001,
        'permissions' => 'usage,contentadmin',                      // Permissions
        'type'        => 'plugin',                                  // Type
        'settings'    => [                                          // Settings
            'self' => ''
        ],

        'details'    => 'https://open-time.net/?q=mymeta',       // Details URL
        'support'    => 'https://github.com/franck-paul/mymeta', // Support URL
        'repository' => 'https://raw.githubusercontent.com/franck-paul/mymeta/main/dcstore.xml'
    ]
);
