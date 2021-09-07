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
    http::redirect($p_url);
    exit;
}

$mymetaEntry = $mymeta->getByID($_GET['id']);
if ($mymetaEntry == null) {
    dcPage::addErrorNotice(__('Something went wrong while editing mymeta value'));
    http::redirect($p_url);
    exit;
}

$value = rawurldecode($_GET['value']);

$this_url = $p_url . '&amp;m=viewposts&amp;id=' . $mymetaEntry->id . '&amp;value=' . rawurlencode($value);

$page        = !empty($_GET['page']) ? $_GET['page'] : 1;
$nb_per_page = 30;

# Rename a tag
if (!empty($_POST['rename'])) {
    $new_value = $_POST['mymeta_' . $mymetaEntry->id];

    try {
        if ($mymeta->dcmeta->updateMeta($value, $new_value, $mymetaEntry->id)) {
            dcPage::addSuccessNotice(sprintf(
        __('Mymeta value successfully updated from "%s" to "%s"'),
        html::escapeHTML($value),
        html::escapeHTML($new_value)
      ));
            http::redirect($p_url . '&m=view&id=' . $mymetaEntry->id . '&status=valchg');
        }
    } catch (Exception $e) {
        $core->error->add($e->getMessage());
    }
}

# Delete a tag
if (!empty($_POST['delete']) && $core->auth->check('publish,contentadmin', $core->blog->id)) {
    try {
        /*$mymeta->dcmeta->delMeta($tag,'tag');
        http::redirect($p_url.'&m=tags&del=1');*/
    } catch (Exception $e) {
        $core->error->add($e->getMessage());
    }
}

$params               = [];
$params['limit']      = [(($page - 1) * $nb_per_page),$nb_per_page];
$params['no_content'] = true;

$params['meta_id']   = $value;
$params['meta_type'] = $mymetaEntry->id;;
$params['post_type'] = '';

# Get posts
try {
    $posts     = $mymeta->dcmeta->getPostsByMeta($params);
    $counter   = $mymeta->dcmeta->getPostsByMeta($params, true);
    $post_list = new adminPostList($core, $posts, $counter->f(0));
} catch (Exception $e) {
    $core->error->add($e->getMessage());
}

# Actions combo box
$combo_action = [];
if ($core->auth->check('publish,contentadmin', $core->blog->id)) {
    $combo_action[__('Status')] = [
        __('Publish')         => 'publish',
        __('Unpublish')       => 'unpublish',
        __('Schedule')        => 'schedule',
        __('Mark as pending') => 'pending'
    ];
}
$combo_action[__('Mark')] = [
    __('Mark as selected')   => 'selected',
    __('Mark as unselected') => 'unselected'
];
$combo_action[__('Change')] = [__('Change category') => 'category'];
if ($core->auth->check('admin', $core->blog->id)) {
    $combo_action[__('Change')] = array_merge($combo_action[__('Change')],
    [__('Change author') => 'author']);
}
if ($core->auth->check('delete,contentadmin', $core->blog->id)) {
    $combo_action[__('Delete')] = [__('Delete') => 'delete'];
}

# --BEHAVIOR-- adminPostsActionsCombo
$core->callBehavior('adminPostsActionsCombo', [&$combo_action]);

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
  echo $mymetaEntry->postHeader(null, true);

  ?>
  </head>
<body>
<?php
echo dcPage::breadcrumb(
  [
      html::escapeHTML($core->blog->name) => '',
      __('My Metadata')                   => $p_url,
      html::escapeHTML($mymetaEntry->id)  => $p_url . '&m=view&id=' . $mymetaEntry->id,
      sprintf(__('Value "%s"'), html::escapeHTML($value)) => ''
  ]) . dcPage::notices();

  if (!empty($_GET['renamed'])) {
      echo '<p class="message">' . __('MyMeta has been successfully renamed') . '</p>';
  }

if (!$core->error->flag()) {
    echo '<h3>' . sprintf(__('Entries having meta id "%s" set to "%s"'), html::escapeHTML($mymetaEntry->id), html::escapeHTML($value)) . '</h3>';
    # Show posts
    $post_list->display($page,$nb_per_page,
  '<form action="posts_actions.php" method="post" id="form-entries">' .

  '%s' .

  '<div class="two-cols">' .
  '<p class="col checkboxes-helpers"></p>' .

  '<p class="col right">' . __('Selected entries action:') . ' ' .
  form::combo('action', $combo_action) .
  '<input type="submit" value="' . __('ok') . '" /></p>' .
  form::hidden('post_type', '') .
  form::hidden('redir', $p_url . '&amp;m=view&amp;id=' .
    $mymetaEntry->id . '&amp;page=' . $page) .
  $core->formNonce() .
  '</div>' .
  '</form>');

    # Remove tag
    if (!$posts->isEmpty() && $core->auth->check('contentadmin', $core->blog->id)) {
        echo
    '<form id="tag_delete" action="' . $this_url . '" method="post">' .
    '<p><input type="submit" name="delete" value="' . __('Delete this tag') . '" />' .
    $core->formNonce() . '</p>' .
    '</form>';
    }
    if (!$posts->isEmpty()) {
        echo
    '<div class="fieldset"><h3>' . __('Change MyMeta value') . '</h3><form action="' . $this_url . '" method="post">' .
    dcPage::message(__('This will change the meta value for all entries having this value'), false, false, false, 'info') .
    $mymetaEntry->postShowForm($mymeta, null, html::escapeHTML($value), true) .
    '<p><input type="submit" name="rename" value="' . __('save') . '" />' .
    form::hidden(['value'], html::escapeHTML($value)) .
    $core->formNonce() .
    '</p></form></div>';
    }
}
?>
</body>
</html>
