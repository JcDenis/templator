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
if (!defined('DC_RC_PATH')) { return; }

class pagerTemplator
{
	public static function templatorItemLine($f,$i)
	{
		global $core, $p_url;
		
		$fname = $f->basename;
		$count = '';
		$params = array();
		$link = 'media_item.php?id='.$f->media_id;
		$link_edit = $p_url.'&amp;edit='.$fname;
		$icon = 'index.php?pf=templator/img/template.png';
		$class = 'media-item media-col-'.($i%2);
		$details = $special = '';
		$widget_icon = '<span class="widget" title="'.__('Template widget').'">&diams;</span>';
		$copy_url = '&amp;copy=';
		
		if (preg_match('/^category-(.+)$/',$f->basename)) {
			// That is ugly.
			$cat_id = str_replace('category-', '', $f->basename);
			$cat_id = str_replace('.html', '', $cat_id);
			$cat_parents = $core->blog->getCategoryParents($cat_id);
			$full_name = '';
			while ($cat_parents->fetch()) {$full_name = $cat_parents->cat_title.' &rsaquo; ';};
			$fname = '<strong>'.__('Category').'</strong> :&nbsp;'.$full_name.$core->blog->getCategory($cat_id)->cat_title;
			$params['cat_id'] = $cat_id;
			$params['post_type'] = '';
			$icon = 'index.php?pf=templator/img/template-alt.png';
			$copy_url = '&amp;copycat=';
			try {
				$counter = $core->blog->getPosts($params,true);
			} catch (Exception $e) {
				$core->error->add($e->getMessage());
			}
			
			if ($counter->f(0) == 0) {
				$count =  __('No entry');
			} elseif ($counter->f(0) == 1) {
				$count = '<strong>'.$counter->f(0).'</strong> <a href="posts.php?cat_id='.$cat_id.'">'.__('entry').'</a>';
			} else {
				$count = '<strong>'.$counter->f(0).'</strong> <a href="posts.php?cat_id='.$cat_id.'">'.__('entries').'</a>';
			}
		}
		elseif (preg_match('/^widget-(.+)$/',$f->basename)) {
			$count = '&nbsp;';
			$icon = 'index.php?pf=templator/img/template-widget.png';
			$special = $widget_icon;
		}
		else {
			$params['meta_id'] = $f->basename;
			$params['meta_type'] = 'template';
			$params['post_type'] = '';
			try {
				$counter = $core->meta->getPostsByMeta($params,true);
			} catch (Exception $e) {
				$core->error->add($e->getMessage());
			}
			if ($counter->f(0) == 0) {
				$count =  '&nbsp;';
			} elseif ($counter->f(0) == 1) {
				$count = '<strong>'.$counter->f(0).'</strong> <a href="'.$p_url.'&amp;m=template_posts&amp;template='.$fname.'">'.__('entry').'</a>';
			} else {
				$count = '<strong>'.$counter->f(0).'</strong> <a href="'.$p_url.'&amp;m=template_posts&amp;template='.$fname.'">'.__('entries').'</a>';
			}
		}
		
		$res =
		'<div class="'.$class.'"><a class="media-icon media-link" href="'.$link_edit.'">'.
		'<img src="'.$icon.'" alt="" /></a>'.
		'<ul>'.
		'<li><a class="media-link" href="'.$link_edit.'">'.$fname.'</a> '.$special.'</li>';
		
		if($core->auth->check('contentadmin,media',$core->blog->id)) {
			$details = ' - <a href="'.$link.'">'.__('details').'</a>';
		}
		
		if (!$f->d) {
			$res .=
			'<li>'.$count.'</li>'.
			'<li>'.
			$f->media_dtstr.' - '.
			files::size($f->size).
			$details.
			'</li>';
		}
		
		$res .= '<li class="media-action">&nbsp;';

		$res .= '<a class="media-remove" '.
		'href="'.$p_url.$copy_url.rawurlencode($f->basename).'">'.
		'<img src="index.php?pf=templator/img/copy.png" alt="'.__('copy').'" title="'.__('copy the template').'" /></a>&nbsp;';

		
		if ($f->del) {
			$res .= '<a class="media-remove" '.
			'href="'.$p_url.'&amp;remove='.rawurlencode($f->basename).'">'.
			'<img src="index.php?pf=templator/img/delete.png" alt="'.__('delete').'" title="'.__('delete the template').'" /></a>';
		}
		
		$res .= '</li>';
		
		$res .= '</ul></div>';
		
		return $res;
	}
}
?>