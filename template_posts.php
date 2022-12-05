<?php
# -- BEGIN LICENSE BLOCK ----------------------------------
#
# This file is part of templator a plugin for Dotclear 2.
#
# Copyright (c) 2010 Osku and contributors
# Licensed under the GPL version 2.0 license.
# A copy of this license is available in LICENSE file or at
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK ------------------------------------
if (!defined('DC_CONTEXT_ADMIN')) {
    return;
}

$template = (!empty($_REQUEST['template']) || $_REQUEST['template'] == '0') ? $_REQUEST['template'] : '';

$this_url = $p_url . '&amp;m=template_posts&amp;template=' . rawurlencode($template);

$page        = !empty($_GET['page']) ? $_GET['page'] : 1;
$nb_per_page = 30;

# Unselect the template
if (!empty($_POST['initialise']) && dcCore::app()->auth->check('publish,contentadmin', dcCore::app()->blog->id)) {
    try {
        dcCore::app()->meta->delMeta($template, 'template');
        http::redirect($p_url . '&del=' . $template);
    } catch (Exception $e) {
        dcCore::app()->error->add($e->getMessage());
    }
}

$params               = [];
$params['limit']      = [(($page - 1) * $nb_per_page),$nb_per_page];
$params['no_content'] = true;

$params['meta_id']   = $template;
$params['meta_type'] = 'template';
$params['post_type'] = '';

# Get posts
try {
    $posts     = dcCore::app()->meta->getPostsByMeta($params);
    $counter   = dcCore::app()->meta->getPostsByMeta($params, true);
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
  <title><?php echo __('Templator'); ?></title>
  <script type="text/javascript" src="js/_posts_list.js"></script>
  <script type="text/javascript">
  //<![CDATA[
  dotclear.msg.confirm_template_unselect = '<?php echo html::escapeJS(__('Are you sure you want to unselect the template?')) ?>';
  $(function() {
    $('#template_change').submit(function() {
      return window.confirm(dotclear.msg.confirm_template_unselect);
    });
  });
  //]]>
  </script>
</head>
<body>

<h2><?php echo html::escapeHTML(dcCore::app()->blog->name); ?> &rsaquo;
<span class="page-title"><?php echo __('Unselect specific template'); ?></span></h2>

<?php

echo '<p><a href="' . $p_url . '">' . __('Back to templates list') . '</a></p>';

if (!dcCore::app()->error->flag()) {
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
        form::hidden('redir', $p_url . '&amp;m=template_posts&amp;tag=' .
            str_replace('%', '%%', rawurlencode($template)) . '&amp;page=' . $page) .
        dcCore::app()->formNonce() .
        '</div>' .
        '</form>'
    );

    # Remove tag
    if (!$posts->isEmpty() && dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
        dcAuth::PERMISSION_CONTENT_ADMIN,
    ]), dcCore::app()->blog->id)) {
        echo
        '<form id="template_change" action="' . $this_url . '" method="post">' .
        '<p><input type="submit" name="initialise" value="' . __('Unselect the template') . '" />' .
        dcCore::app()->formNonce() . '</p>' .
        '</form>';
    }
}
?>
</body>
</html>