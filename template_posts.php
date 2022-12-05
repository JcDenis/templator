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
if (!defined('DC_CONTEXT_ADMIN')) { return; }

$template = (!empty($_REQUEST['template']) || $_REQUEST['template'] == '0') ? $_REQUEST['template'] : '';

$this_url = $p_url.'&amp;m=template_posts&amp;template='.rawurlencode($template);

$page = !empty($_GET['page']) ? $_GET['page'] : 1;
$nb_per_page =  30;

# Unselect the template
if (!empty($_POST['initialise']) && $core->auth->check('publish,contentadmin',$core->blog->id))
{
	try {
		$core->meta->delMeta($template,'template');
		http::redirect($p_url.'&del='.$template);
	} catch (Exception $e) {
		$core->error->add($e->getMessage());
	}
}

$params = array();
$params['limit'] = array((($page-1)*$nb_per_page),$nb_per_page);
$params['no_content'] = true;

$params['meta_id'] = $template;
$params['meta_type'] = 'template';
$params['post_type'] = '';

# Get posts
try {
	$posts = $core->meta->getPostsByMeta($params);
	$counter = $core->meta->getPostsByMeta($params,true);
	$post_list = new adminPostList($core,$posts,$counter->f(0));
} catch (Exception $e) {
	$core->error->add($e->getMessage());
}

# Actions combo box
$combo_action = array();
if ($core->auth->check('publish,contentadmin',$core->blog->id))
{
	$combo_action[__('Status')] = array(
		__('Publish') => 'publish',
		__('Unpublish') => 'unpublish',
		__('Schedule') => 'schedule',
		__('Mark as pending') => 'pending'
	);
}
$combo_action[__('Mark')] = array(
	__('Mark as selected') => 'selected',
	__('Mark as unselected') => 'unselected'
);
$combo_action[__('Change')] = array(__('Change category') => 'category');
if ($core->auth->check('admin',$core->blog->id))
{
	$combo_action[__('Change')] = array_merge($combo_action[__('Change')],
		array(__('Change author') => 'author'));
}
if ($core->auth->check('delete,contentadmin',$core->blog->id))
{
	$combo_action[__('Delete')] = array(__('Delete') => 'delete');
}

# --BEHAVIOR-- adminPostsActionsCombo
$core->callBehavior('adminPostsActionsCombo',array(&$combo_action));

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

<h2><?php echo html::escapeHTML($core->blog->name); ?> &rsaquo;
<span class="page-title"><?php echo __('Unselect specific template'); ?></span></h2>

<?php

echo '<p><a href="'.$p_url.'">'.__('Back to templates list').'</a></p>';

if (!$core->error->flag())
{
	# Show posts
	$post_list->display($page,$nb_per_page,
	'<form action="posts_actions.php" method="post" id="form-entries">'.
	
	'%s'.
	
	'<div class="two-cols">'.
	'<p class="col checkboxes-helpers"></p>'.
	
	'<p class="col right">'.__('Selected entries action:').' '.
	form::combo('action',$combo_action).
	'<input type="submit" value="'.__('ok').'" /></p>'.
	form::hidden('post_type','').
	form::hidden('redir',$p_url.'&amp;m=template_posts&amp;tag='.
		str_replace('%','%%',rawurlencode($template)).'&amp;page='.$page).
	$core->formNonce().
	'</div>'.
	'</form>');
	
	# Remove tag
	if (!$posts->isEmpty() && $core->auth->check('contentadmin',$core->blog->id)) {
		echo
		'<form id="template_change" action="'.$this_url.'" method="post">'.
		'<p><input type="submit" name="initialise" value="'.__('Unselect the template').'" />'.
		$core->formNonce().'</p>'.
		'</form>';
	}
}
?>
</body>
</html>