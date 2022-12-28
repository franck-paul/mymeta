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
if (!defined('DC_CONTEXT_ADMIN')) {
    return;
}

if (!dcCore::app()->newVersion(basename(__DIR__), dcCore::app()->plugins->moduleInfo(basename(__DIR__), 'version'))) {
    return;
}

$mymeta_settings = dcCore::app()->blog->settings->mymeta;

if ($mymeta_settings->mymeta_fields == null) {
    return true;
}
$backup = $mymeta_settings->mymeta_fields;
$fields = unserialize(base64_decode($mymeta_settings->mymeta_fields));
if (!is_array($fields) || count($fields) == 0) {
    return true;
}
if (get_class(current($fields)) != 'stdClass') {
    return true;
}

$mymeta = new myMeta(true);
foreach ($fields as $k => $v) {
    $newfield          = $mymeta->newMyMeta($v->type);
    $newfield->id      = $k;
    $newfield->enabled = $v->enabled;
    $newfield->prompt  = $v->prompt;
    switch ($v->type) {
        case 'list':
            $newfield->values = $v->values;

            break;
    }
    $mymeta->update($newfield);
}
$mymeta->reorder();
$mymeta->store();

if ($mymeta_settings->mymeta_fields_backup == null) {
    $mymeta_settings->put(
        'mymeta_fields_backup',
        $backup,
        'string',
        'MyMeta fields backup (0.3.x version)'
    );
}

return true;
