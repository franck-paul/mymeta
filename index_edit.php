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

function filterTplFile($file, $default)
{
    $f = trim($file);

    return str_replace(['\\','/'], ['',''], $f);
}

if (!empty($_POST['mymeta_id'])) {
    $mymetaid                = preg_replace('#[^a-zA-Z0-9_-]#', '', $_POST['mymeta_id']);
    $mymetaEntry             = $mymeta->newMyMeta($_POST['mymeta_type'], $mymetaid);
    $mymetaEntry->id         = $mymetaid;
    $mymetaEntry->post_types = false;
    if (isset($_POST['mymeta_restrict']) && $_POST['mymeta_restrict'] == 'yes') {
        if (isset($_POST['mymeta_restricted_types'])) {
            $post_types = explode(',', $_POST['mymeta_restricted_types']);
            array_walk($post_types, create_function('&$v', '$v=trim(html::escapeHTML($v));'));
            $mymetaEntry->post_types = $post_types;
        }
    }
    $mymetaEntry->url_list_enabled   = isset($_POST['enable_list']);
    $mymetaEntry->url_single_enabled = isset($_POST['enable_single']);
    $mymetaEntry->tpl_single         = filterTplFile($_POST['single_tpl'], 'mymeta.html');
    $mymetaEntry->tpl_list           = filterTplFile($_POST['list_tpl'], 'mymetas.html');

    $mymetaEntry->adminUpdate($_POST);
    $mymeta->update($mymetaEntry);
    $mymeta->store();
    dcPage::addsuccessNotice(sprintf(
        __('MyMeta "%s" has been successfully updated'),
        html::escapeHTML($mymetaid)
    ));
    http::redirect($p_url);
    exit;
}

if (array_key_exists('id', $_REQUEST)) {
    $page_title  = __('Edit MyMeta');
    $mymetaid    = $_REQUEST['id'];
    $mymetaentry = $mymeta->getByID($_REQUEST['id']);
    if ($mymetaentry == null) {
        dcPage::addErrorNotice(__('Something went wrong while editing mymeta'));
        http::redirect($p_url);
        exit;
    }
    $mymeta_type = $mymetaentry->getMetaTypeId();
    $lock_id     = true;
} elseif (!empty($_REQUEST['mymeta_type'])) {
    $mymeta_type = html::escapeHTML($_REQUEST['mymeta_type']);
    $page_title  = __('New MyMeta');
    $mymetaentry = $mymeta->newMyMeta($mymeta_type);
    $mymetaid    = '';
    $lock_id     = false;
}
$types      = $mymeta->getTypesAsCombo();
$type_label = array_search($mymeta_type, $types);
if (!$type_label) {
    dcPage::addErrorNotice(__('Something went wrong while editing mymeta'));
    http::redirect($p_url);
}

?>
<html>
<head>
  <title><?php echo __('My metadata'); ?></title>
  <?php echo dcPage::jsPageTabs('mymeta');
  ?>
</head>
<body>
<?php
echo dcPage::breadcrumb(
    [
        html::escapeHTML($core->blog->name) => '',
        __('My Metadata')                   => $p_url,
        $page_title                         => ''
    ]) . dcPage::notices();

if (!$core->error->flag()):?>
	<form method="post" action="plugin.php">
		<div class="fieldset">
			<h3><?php echo __('MyMeta definition'); ?></h3>
			<p>
				<label class="required" for="mymeta_id"><?php echo __('Identifier (as stored in meta_type in database):') . ' '; ?>
				</label>
				<?php echo form::field(['mymeta_id'], 20, 255, $mymetaid, '', '', $lock_id); ?>
			</p>
			<p>
				<label for="mymeta_prompt"><?php echo __('Prompt') . ' : '; ?></label>
				<?php echo form::field(['mymeta_prompt'], 40, 255, $mymetaentry->prompt); ?>
			</p>
			<p>
				<?php echo '<em>' . sprintf(__('MyMeta type : %s'), __($mymeta_type)) . '</em>'; ?>
			</p>
			<?php echo $mymetaentry->adminForm();?>
		</div>
		<div class="fieldset">
			<h3><?php echo __('MyMeta URLs'); ?></h3>
				<?php
                $base_url   = $core->blog->url . $core->url->getBase('mymeta') . '/' . $mymetaentry->id;
                $tpl_single = $mymetaentry->tpl_single;
                $tpl_list   = $mymetaentry->tpl_list;
                echo
                    '<p><label class="classic" for="enable_list">' .
                    form::checkbox(['enable_list'], 1, $mymetaentry->url_list_enabled) .
                    __('Enable MyMeta values list public page') . '</label></p>' .
                    '<p><label class="classic">' . __('List template file (leave empty for default mymetas.html)') . ' : </label>' .
                    form::field(['list_tpl'], 40, 255, empty($tpl_list)?'mymetas.html':$tpl_list) .
                    '</p>' .
                    '<p><label class="classic" for="enable_single">' .
                    form::checkbox(['enable_single'], 1, $mymetaentry->url_single_enabled) .
                    __('Enable single mymeta value public page') .
                    '</label></p>' .
                    '<p><label class="classic">' . __('Single template file (leave empty for default mymeta.html)') . ' : </label>' .
                    form::field(['single_tpl'], 40, 255, empty($tpl_single)?'mymeta.html':$tpl_single) .
                    '</p>'; ?>
		</div>
		<div class="fieldset">
			<h3><?php echo __('MyMeta restrictions'); ?></h3>
			<p>
				<?php
                echo '<label class="classic" for="mymeta_restrict">' . form::radio(['mymeta_restrict'], 'none', $mymetaentry->isRestrictionEnabled()) .
                __('Display meta field for any post type') . '</label></p>';
                echo '<p><label class="classic" for="mymeta_restrict">' . form::radio(['mymeta_restrict'], 'yes', !$mymetaentry->isRestrictionEnabled()) .
                __('Restrict to the following post types :');
                $restrictions = $mymetaentry->getRestrictions();
                echo form::field('mymeta_restricted_types', 40, 255, $restrictions?$restrictions:'') . '</label></p>'; ?>
			</p>
		</div>
		<p>
			<input type="hidden" name="p" value="mymeta" />
			<input type="hidden" name="m" value="edit" />
			<?php
                if ($lock_id) {
                    echo form::hidden(['mymeta_id'], $mymetaid);
                }
                echo form::hidden(['mymeta_enabled'], $mymetaentry->enabled);
                echo form::hidden(['mymeta_type'], $mymeta_type);
                echo $core->formNonce()
            ?>
			<input type="submit" name="saveconfig" value="<?php echo __('Save'); ?>" />
		</p>
	</form>

<?php
endif;
?>
</body>
</html>
