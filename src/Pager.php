<?php
/**
 * @brief templator, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugin
 *
 * @author Osku and contributors
 *
 * @copyright Jean-Christian Denis
 * @copyright GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
declare(strict_types=1);

namespace Dotclear\Plugin\templator;

use dcCore;
use Dotclear\Helper\File\File;
use Dotclear\Helper\File\Files;
use Exception;

class Pager
{
    public static function line(File $f, int $i): string
    {
        if (is_null(dcCore::app()->blog)) {
            return '';
        }

        $p_url       = dcCore::app()->admin->getPageURL();
        $fname       = $f->basename;
        $count       = '';
        $params      = [];
        $link        = 'media_item.php?id=' . $f->media_id;
        $link_edit   = My::manageUrl(['part' => 'edit', 'file' => $f->basename]);
        $icon        = My::fileURL('img/template.png');
        $class       = 'media-item media-col-' . ($i % 2);
        $details     = $special = '';
        $widget_icon = '<span class="widget" title="' . __('Template widget') . '">&diams;</span>';
        $part        = 'copy';

        if (preg_match('/^category-(.+).html$/', $f->basename, $cat_id)) {
            $cat_id      = (int) $cat_id[1];
            $category    = dcCore::app()->blog->getCategory($cat_id);
            $full_name   = '';
            $cat_parents = dcCore::app()->blog->getCategoryParents($cat_id);
            while ($cat_parents->fetch()) {
                $full_name = $cat_parents->f('cat_title') . ' &rsaquo; ';
            };
            $fname               = '<strong>' . __('Category') . '</strong> :&nbsp;' . $full_name . $category->f('cat_title');
            $params['cat_id']    = $cat_id;
            $params['post_type'] = '';
            $icon                = My::fileURL('img/template-alt.png');
            $part                = 'copycat';

            try {
                $counter = dcCore::app()->blog->getPosts($params, true);
                if ($counter->f(0) == 0) {
                    $count = __('No entry');
                } elseif ($counter->f(0) == 1) {
                    $count = '<strong>' . $counter->f(0) . '</strong> <a href="posts.php?cat_id=' . $cat_id . '">' . __('entry') . '</a>';
                } else {
                    $count = '<strong>' . $counter->f(0) . '</strong> <a href="posts.php?cat_id=' . $cat_id . '">' . __('entries') . '</a>';
                }
            } catch (Exception $e) {
                dcCore::app()->error->add($e->getMessage());
            }
        } elseif (preg_match('/^widget-(.+)$/', $f->basename)) {
            $count   = '&nbsp;';
            $icon    = My::fileURL('img/template-widget.png');
            $special = $widget_icon;
        } else {
            $params['meta_id']   = $f->basename;
            $params['meta_type'] = 'template';
            $params['post_type'] = '';

            try {
                $counter = dcCore::app()->meta->getPostsByMeta($params, true)?->f(0);
                $counter = is_numeric($counter) ? (int) $counter : 0;
                $url     = My::manageUrl([
                    'part'  => 'posts',
                    'file'  => $fname,
                    'redir' => My::manageUrl(['part' => 'files'], '&amp;'),
                ], '&');
                if ($counter == 0) {
                    $count = __('No entry');
                } elseif ($counter == 1) {
                    $count = '<strong>' . $counter . '</strong> <a href="' . $url . '">' . __('entry') . '</a>';
                } else {
                    $count = '<strong>' . $counter . '</strong> <a href="' . $url . '">' . __('entries') . '</a>';
                }
            } catch (Exception $e) {
                dcCore::app()->error->add($e->getMessage());
            }
        }

        $res = '<div class="' . $class . '"><a class="media-icon media-link" href="' . $link_edit . '">' .
        '<img src="' . $icon . '" alt="" /></a>' .
        '<ul>' .
        '<li><a class="media-link" href="' . $link_edit . '"><img src="images/edit-mini.png" alt="' . __('edit') . '" title="' . __('edit the template') . '" /> ' . $fname . '</a> ' . $special . '</li>';
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
            Files::size($f->size) .
            $details .
            '</li>';
        }

        $res .= '<li class="media-action">&nbsp;';

        $res .= '<a class="media-remove" href="' .
        My::manageUrl(['part' => $part, 'file' => $f->basename]) . '">' .
        '<img src="images/plus.png" alt="' . __('copy') . '" title="' . __('copy the template') . '" /></a>&nbsp;';

        if ($f->del) {
            $res .= '<a class="media-remove" href="' .
            My::manageUrl(['part' => 'delete', 'file' => $f->basename]) . '">' .
            '<img src="images/trash.png" alt="' . __('delete') . '" title="' . __('delete the template') . '" /></a>';
        }

        $res .= '</li>';

        $res .= '</ul></div>';

        return $res;
    }
}
