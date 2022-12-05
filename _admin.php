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

$_menu['Plugins']->addItem(__('Templates'),
	'plugin.php?p=templator','index.php?pf=templator/icon.png',
	preg_match('/plugin.php\?p=templator(&.*)?$/',$_SERVER['REQUEST_URI']),
	$core->auth->check('contentadmin,templator',$core->blog->id));

$core->auth->setPermissionType('templator',__('manage templates'));

if ($core->auth->check('templator,contentadmin',$core->blog->id)) {
	$core->addBehavior('adminPostFormSidebar',array('templatorBehaviors','adminPostFormSidebar'));
	$core->addBehavior('adminPageFormSidebar',array('templatorBehaviors','adminPostFormSidebar'));

	$core->addBehavior('adminAfterPostCreate',array('templatorBehaviors','adminBeforePostUpdate'));
	$core->addBehavior('adminBeforePostUpdate',array('templatorBehaviors','adminBeforePostUpdate'));
	$core->addBehavior('adminAfterPageCreate',array('templatorBehaviors','adminBeforePostUpdate'));
	$core->addBehavior('adminBeforePageUpdate',array('templatorBehaviors','adminBeforePostUpdate'));

	$core->addBehavior('adminPostsActionsCombo',array('templatorBehaviors','adminPostsActionsCombo'));
	$core->addBehavior('adminPostsActions',array('templatorBehaviors','adminPostsActions'));
	$core->addBehavior('adminPostsActionsContent',array('templatorBehaviors','adminPostsActionsContent'));
	$core->addBehavior('adminPagesActionsCombo',array('templatorBehaviors','adminPostsActionsCombo'));
	$core->addBehavior('adminPagesActions',array('templatorBehaviors','adminPostsActions'));
	$core->addBehavior('adminPagesActionsContent',array('templatorBehaviors','adminPostsActionsContent'));
}

class templatorBehaviors
{
	public static function adminPostFormSidebar($post)
	{
		global $core;
		
		$tpl = array('&nbsp;' => '');
		$tpl_post = array();
		$selected = '';
		
		foreach ($core->templator->tpl as $k => $v) {
			if (!preg_match('/^category-(.+)$/',$k))
			{
				$tpl_post= array_merge($tpl_post, array($k => $k));
			}
		}
		
		$tpl  = array_merge($tpl,$tpl_post);
		if ($post)
		{
			$params['meta_type'] = 'template';
			$params['post_id'] = $post->post_id;
			$post_meta = $core->meta->getMetadata($params);
			$selected = $post_meta->isEmpty()? '' : $post_meta->meta_id  ;
		}
		
		echo
		'<div class="p" id="meta-edit-tpl"><label for="post_tpl">
		'.__('Entry template:').'</label>'.form::combo('post_tpl',$tpl,$selected).'</div>';
		
	}

	public static function adminBeforePostUpdate($cur,$post_id)
	{
		global $core;
		
		$post_id = (integer) $post_id;
		
		if (isset($_POST['post_tpl'])) {
			$tpl = $_POST['post_tpl'];
			
			$core->meta->delPostMeta($post_id,'template');
			if (!empty($_POST['post_tpl']))
			{
				$core->meta->setPostMeta($post_id,'template',$tpl);
			}
		}
	}

	public static function adminPostsActionsCombo($args)
	{
		$args[0][__('Appearance')] = array(__('Select the template') => 'tpl');
	}
	
	public static function adminPostsActions($core,$posts,$action,$redir)
	{
		if ($action == 'tpl' && isset($_POST['post_tpl']))
		{
			try
			{
				$tpl = $_POST['post_tpl'];
				
				while ($posts->fetch())
				{
					$core->meta->delPostMeta($posts->post_id,'template');
					if (!empty($_POST['post_tpl']))
					{
						$core->meta->setPostMeta($posts->post_id,'template',$tpl);
					}
				}
				
				http::redirect($redir);
			}
			catch (Exception $e)
			{
				$core->error->add($e->getMessage());
			}
		}
	}
	
	public static function adminPostsActionsContent($core,$action,$hidden_fields)
	{
		if ($action == 'tpl')
		{
			$tpl = array('&nbsp;' => '');
			$tpl_post = array();
			$selected = '';
		
			foreach ($core->templator->tpl as $k => $v) {
				if (!preg_match('/^category-(.+)$/',$k) && !preg_match('/^list-(.+)$/',$k))
				{
					$tpl_post= array_merge($tpl_post, array($k => $k));
				}
			}
			
			$tpl  = array_merge($tpl,$tpl_post);
			
			echo
			'<h2 class="page-title">'.__('Select template for these entries').'</h2>'.
			'<form action="posts_actions.php" method="post">'.
			'<p><label class="classic">'.__('Choose template:').' '.
			form::combo('post_tpl',$tpl).
			'</label> '.
			
			$hidden_fields.
			$core->formNonce().
			form::hidden(array('action'),'tpl').
			'<input type="submit" value="'.__('Save').'" /></p>'.
			'</form>';
			
		}
	}
}
?>