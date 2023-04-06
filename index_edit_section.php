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

use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Network\Http;

if (!defined('DC_CONTEXT_ADMIN')) {
    return;
}

if (!empty($_POST['saveconfig'])) {
    $mymetaid     = Html::escapeHTML($_POST['mymeta_id']);
    $mymetaprompt = Html::escapeHTML($_POST['mymeta_prompt']);

    $mymetaSection = dcCore::app()->admin->mymeta->getByID($mymetaid);
    if ($mymetaSection instanceof myMetaSection) {
        $mymetaSection->prompt = $mymetaprompt;
        dcCore::app()->admin->mymeta->update($mymetaSection);
        dcCore::app()->admin->mymeta->store();
    }
    dcPage::addSuccessNotice(__('Section has been successfully updated'));
    Http::redirect(dcCore::app()->admin->getPageURL());
    exit;
}

if (array_key_exists('id', $_REQUEST)) {
    $page_title    = __('Edit section');
    $mymetaid      = $_REQUEST['id'];
    $mymetasection = dcCore::app()->admin->mymeta->getByID($_REQUEST['id']);
    if (!($mymetasection instanceof myMetaSection)) {
        dcPage::addErrorNotice(__('Something went wrong while editing section'));
        Http::redirect(dcCore::app()->admin->getPageURL());
        exit;
    }
} else {
    dcPage::addErrorNotice(__('Something went wrong while editing section'));
    Http::redirect(dcCore::app()->admin->getPageURL());
    exit;
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
        Html::escapeHTML(dcCore::app()->blog->name) => '',
        __('My Metadata')                           => dcCore::app()->admin->getPageURL(),
        $page_title                                 => '',
    ]
) . dcPage::notices();
if (!dcCore::app()->error->flag()):
    ?>
	<form method="post" action="plugin.php">
		<div class="fieldset">
			<h3><?php echo __('MyMeta section definition'); ?></h3>
			<p>
				<label class="required"><?php echo __('Title') . ' '; ?>
				<?php echo form::field('mymeta_prompt', 20, 255, $mymetasection->prompt, '', ''); ?>
				</label>
			</p>
		</div>
		<p>
			<input type="hidden" name="p" value="mymeta" />
			<input type="hidden" name="m" value="editsection" />
			<?php
                    echo form::hidden('mymeta_id', $mymetaid) .
                        dcCore::app()->formNonce()
    ?>
			<input type="submit" name="saveconfig" value="<?php echo __('Save'); ?>" />
		</p>
	</form>

<?php
endif;
?>
</body>
</html>
