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

if (empty($_GET['id']) || empty($_GET['value'])) {
    dcPage::addErrorNotice(__('Something went wrong while editing mymeta value'));
    http::redirect(dcCore::app()->admin->getPageURL());
    exit;
}

dcCore::app()->admin->mymetaEntry = dcCore::app()->admin->mymeta->getByID($_GET['id']);
if (dcCore::app()->admin->mymetaEntry == null) {
    dcPage::addErrorNotice(__('Something went wrong while editing mymeta value'));
    http::redirect(dcCore::app()->admin->getPageURL());
    exit;
}

$value = rawurldecode($_GET['value']);

$this_url = dcCore::app()->admin->getPageURL() . '&amp;m=viewposts&amp;id=' . dcCore::app()->admin->mymetaEntry->id . '&amp;value=' . rawurlencode($value);

$page        = !empty($_GET['page']) ? $_GET['page'] : 1;
$nb_per_page = 30;

# Rename a tag
if (!empty($_POST['rename'])) {
    $new_value = $_POST['mymeta_' . dcCore::app()->admin->mymetaEntry->id];

    try {
        if (dcCore::app()->admin->mymeta->dcmeta->updateMeta($value, $new_value, dcCore::app()->admin->mymetaEntry->id)) {
            dcPage::addSuccessNotice(sprintf(
                __('Mymeta value successfully updated from "%s" to "%s"'),
                html::escapeHTML($value),
                html::escapeHTML($new_value)
            ));
            http::redirect(dcCore::app()->admin->getPageURL() . '&m=view&id=' . dcCore::app()->admin->mymetaEntry->id . '&status=valchg');
        }
    } catch (Exception $e) {
        dcCore::app()->error->add($e->getMessage());
    }
}

# Delete a tag
if (!empty($_POST['delete']) && dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
    dcAuth::PERMISSION_PUBLISH,
    dcAuth::PERMISSION_CONTENT_ADMIN,
]), dcCore::app()->blog->id)) {
    try {
        dcCore::app()->admin->mymeta->dcmeta->delMeta($value, dcCore::app()->admin->mymetaEntry->id);
        http::redirect(dcCore::app()->admin->getPageURL() . '&m=view&del=1');
    } catch (Exception $e) {
        dcCore::app()->error->add($e->getMessage());
    }
}

$params               = [];
$params['limit']      = [(($page - 1) * $nb_per_page),$nb_per_page];
$params['no_content'] = true;

$params['meta_id']   = $value;
$params['meta_type'] = dcCore::app()->admin->mymetaEntry->id;

$params['post_type'] = '';

# Get posts
$post_list = null;
$posts     = null;

try {
    $posts     = dcCore::app()->admin->mymeta->dcmeta->getPostsByMeta($params);
    $counter   = dcCore::app()->admin->mymeta->dcmeta->getPostsByMeta($params, true);
    $post_list = new adminPostList($posts, $counter->f(0));
} catch (Exception $e) {
    dcCore::app()->error->add($e->getMessage());
}

# Actions combo box
$combo_action = [];
if (dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
    dcAuth::PERMISSION_PUBLISH,
    dcAuth::PERMISSION_CONTENT_ADMIN,
]), dcCore::app()->blog->id)) {
    $combo_action[__('Status')] = [
        __('Publish')         => 'publish',
        __('Unpublish')       => 'unpublish',
        __('Schedule')        => 'schedule',
        __('Mark as pending') => 'pending',
    ];
}
$combo_action[__('Mark')] = [
    __('Mark as selected')   => 'selected',
    __('Mark as unselected') => 'unselected',
];
$combo_action[__('Change')] = [__('Change category') => 'category'];
if (dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
    dcAuth::PERMISSION_ADMIN,
]), dcCore::app()->blog->id)) {
    $combo_action[__('Change')] = array_merge(
        $combo_action[__('Change')],
        [__('Change author') => 'author']
    );
}
if (dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
    dcAuth::PERMISSION_DELETE,
    dcAuth::PERMISSION_CONTENT_ADMIN,
]), dcCore::app()->blog->id)) {
    $combo_action[__('Delete')] = [__('Delete') => 'delete'];
}

# --BEHAVIOR-- adminPostsActionsCombo
dcCore::app()->callBehavior('adminPostsActionsCombo', [&$combo_action]);

?>
<html>
<head>
  <title>MyMeta</title>
  <link rel="stylesheet" type="text/css" href="index.php?pf=tags/style.css" />
  <script type="text/javascript" src="js/_posts_list.js"></script>
  <script type="text/javascript">
  //<![CDATA[
  dotclear.msg.confirm_tag_delete = '<?php echo html::escapeJS(__('Are you sure you want to remove this metadata?')); ?>';
  $(function() {
    $('#tag_delete').submit(function() {
      return window.confirm(dotclear.msg.confirm_tag_delete);
    });
  });
  //]]>
  </script>
  <?php
  echo dcPage::jsPageTabs('mymeta');
echo dcCore::app()->admin->mymetaEntry->postHeader(null, true);

?>
  </head>
<body>
<?php
echo dcPage::breadcrumb(
    [
        html::escapeHTML(dcCore::app()->blog->name)             => '',
        __('My Metadata')                                       => dcCore::app()->admin->getPageURL(),
        html::escapeHTML(dcCore::app()->admin->mymetaEntry->id) => dcCore::app()->admin->getPageURL() . '&m=view&id=' . dcCore::app()->admin->mymetaEntry->id,
        sprintf(__('Value "%s"'), html::escapeHTML($value))     => '',
    ]
) . dcPage::notices();

if (!empty($_GET['renamed'])) {
    echo '<p class="message">' . __('MyMeta has been successfully renamed') . '</p>';
}

if (!dcCore::app()->error->flag()) {
    echo '<h3>' . sprintf(__('Entries having meta id "%s" set to "%s"'), html::escapeHTML(dcCore::app()->admin->mymetaEntry->id), html::escapeHTML($value)) . '</h3>';
    # Show posts
    $post_list->display(
        $page,
        $nb_per_page,
        '<form action="posts_actions.php" method="post" id="form-entries">' .

  '%s' .

  '<div class="two-cols">' .
  '<p class="col checkboxes-helpers"></p>' .

  '<p class="col right">' . __('Selected entries action:') . ' ' .
  form::combo('action', $combo_action) .
  '<input type="submit" value="' . __('ok') . '" /></p>' .
  form::hidden('post_type', '') .
  form::hidden('redir', dcCore::app()->admin->getPageURL() . '&amp;m=view&amp;id=' .
    dcCore::app()->admin->mymetaEntry->id . '&amp;page=' . $page) .
  dcCore::app()->formNonce() .
  '</div>' .
  '</form>'
    );

    # Remove tag
    if (!$posts->isEmpty() && dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
        dcAuth::PERMISSION_CONTENT_ADMIN,
    ]), dcCore::app()->blog->id)) {
        echo
    '<form id="tag_delete" action="' . $this_url . '" method="post">' .
    '<p><input type="submit" name="delete" value="' . __('Delete this tag') . '" />' .
    dcCore::app()->formNonce() . '</p>' .
    '</form>';
    }
    if (!$posts->isEmpty()) {
        echo
    '<div class="fieldset"><h3>' . __('Change MyMeta value') . '</h3><form action="' . $this_url . '" method="post">' .
    dcPage::message(__('This will change the meta value for all entries having this value'), false, false, false) .
    dcCore::app()->admin->mymetaEntry->postShowForm(dcCore::app()->admin->mymeta->dcmeta, null, html::escapeHTML($value), true) .
    '<p><input type="submit" name="rename" value="' . __('save') . '" />' .
    form::hidden(['value'], html::escapeHTML($value)) .
    dcCore::app()->formNonce() .
    '</p></form></div>';
    }
}
?>
</body>
</html>
