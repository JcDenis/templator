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
if (!defined('DC_RC_PATH')) {
    return;
}

class pagerTemplator
{
    public static function templatorItemLine($f, $i)
    {
        $p_url       = dcCore::app()->admin->getPageURL();
        $fname       = $f->basename;
        $count       = '';
        $params      = [];
        $link        = 'media_item.php?id=' . $f->media_id;
        $link_edit   = dcCore::app()->adminurl->get('admin.plugin.templator', ['part' => 'edit', 'file' => $f->basename]);
        $icon        = dcPage::getPF('templator/img/template.png');
        $class       = 'media-item media-col-' . ($i % 2);
        $details     = $special = '';
        $widget_icon = '<span class="widget" title="' . __('Template widget') . '">&diams;</span>';
        $part = 'copy';

        if (preg_match('/^category-(.+)$/', $f->basename)) {
            // That is ugly.
            $cat_id      = str_replace('category-', '', $f->basename);
            $cat_id      = str_replace('.html', '', $cat_id);
            $cat_parents = dcCore::app()->blog->getCategoryParents($cat_id);
            $full_name   = '';
            while ($cat_parents->fetch()) {
                $full_name = $cat_parents->cat_title . ' &rsaquo; ';
            };
            $fname               = '<strong>' . __('Category') . '</strong> :&nbsp;' . $full_name . dcCore::app()->blog->getCategory($cat_id)->cat_title;
            $params['cat_id']    = $cat_id;
            $params['post_type'] = '';
            $icon                = dcPage::getPF('templator/img/template-alt.png');
            $part            = 'copycat';;

            try {
                $counter = dcCore::app()->blog->getPosts($params, true);
            } catch (Exception $e) {
                dcCore::app()->error->add($e->getMessage());
            }

            if ($counter->f(0) == 0) {
                $count = __('No entry');
            } elseif ($counter->f(0) == 1) {
                $count = '<strong>' . $counter->f(0) . '</strong> <a href="posts.php?cat_id=' . $cat_id . '">' . __('entry') . '</a>';
            } else {
                $count = '<strong>' . $counter->f(0) . '</strong> <a href="posts.php?cat_id=' . $cat_id . '">' . __('entries') . '</a>';
            }
        } elseif (preg_match('/^widget-(.+)$/', $f->basename)) {
            $count   = '&nbsp;';
            $icon    = dcPage::getPF('templator/img/template-widget.png');
            $special = $widget_icon;
        } else {
            $params['meta_id']   = $f->basename;
            $params['meta_type'] = 'template';
            $params['post_type'] = '';

            try {
                $counter = dcCore::app()->meta->getPostsByMeta($params, true);
            } catch (Exception $e) {
                dcCore::app()->error->add($e->getMessage());
            }
            $url = dcCore::app()->adminurl->get('admin.plugin.templator', [
                'part'  => 'posts', 
                'file'  => $fname,
                'redir' => dcCore::app()->adminurl->get('admin.plugin.templator', ['part' => 'files']),
            ]);
            if ($counter->f(0) == 0) {
                $count = __('No entry');
            } elseif ($counter->f(0) == 1) {
                $count = '<strong>' . $counter->f(0) . '</strong> <a href="' . $url . '">' . __('entry') . '</a>';
            } else {
                $count = '<strong>' . $counter->f(0) . '</strong> <a href="' . $url . '">' . __('entries') . '</a>';
            }
        }

        $res = '<div class="' . $class . '"><a class="media-icon media-link" href="' . $link_edit . '">' .
        '<img src="' . $icon . '" alt="" /></a>' .
        '<ul>' .
        '<li><a class="media-link" href="' . $link_edit . '">' . $fname . '</a> ' . $special . '</li>';
/*
        if (dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
            dcAuth::PERMISSION_CONTENT_ADMIN,
            initTemplator::PERMISSION_TEMPLATOR,
        ]), dcCore::app()->blog->id)) {
            $details = ' - <a href="' . $link . '">' . __('details') . '</a>';
        }
*/
        if (!$f->d) {
            $res .= '<li>' . $count . '</li>' .
            '<li>' .
            $f->media_dtstr . ' - ' .
            files::size($f->size) .
            $details .
            '</li>';
        }

        $res .= '<li class="media-action">&nbsp;';

        $res .= '<a class="media-remove" href="' . 
        dcCore::app()->adminurl->get('admin.plugin.templator', ['part' => $part, 'file' => $f->basename]) . '">' .
        '<img src="' . dcPage::getPF('templator/img/copy.png') . '" alt="' . __('copy') . '" title="' . __('copy the template') . '" /></a>&nbsp;';

        if ($f->del) {
            $res .= '<a class="media-remove" href="' . 
            dcCore::app()->adminurl->get('admin.plugin.templator', ['part' => 'delete', 'file' => $f->basename]) . '">' .
            '<img src="' . dcPage::getPF('templator/img/delete.png') . '" alt="' . __('delete') . '" title="' . __('delete the template') . '" /></a>';
        }

        $res .= '</li>';

        $res .= '</ul></div>';

        return $res;
    }
}
