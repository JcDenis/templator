<?php

declare(strict_types=1);

namespace Dotclear\Plugin\templator;

use Dotclear\App;
use Dotclear\Helper\File\File;
use Dotclear\Helper\File\Files;
use Exception;

/**
 * @brief       templator backend pager class.
 * @ingroup     templator
 *
 * @author      Osku (author)
 * @author      Jean-Christian Denis (latest)
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class Pager
{
    public static function line(File $f, int $i): string
    {
        if (!App::blog()->isDefined()) {
            return '';
        }

        $p_url       = App::backend()->getPageURL();
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
            $category    = App::blog()->getCategory($cat_id);
            $full_name   = '';
            $cat_parents = App::blog()->getCategoryParents($cat_id);
            while ($cat_parents->fetch()) {
                $full_name = $cat_parents->f('cat_title') . ' &rsaquo; ';
            };
            $fname               = '<strong>' . __('Category') . '</strong> :&nbsp;' . $full_name . $category->f('cat_title');
            $params['cat_id']    = $cat_id;
            $params['post_type'] = '';
            $icon                = My::fileURL('img/template-alt.png');
            $part                = 'copycat';

            try {
                $counter = App::blog()->getPosts($params, true);
                if ($counter->f(0) == 0) {
                    $count = __('No entry');
                } elseif ($counter->f(0) == 1) {
                    $count = '<strong>' . $counter->f(0) . '</strong> <a href="posts.php?cat_id=' . $cat_id . '">' . __('entry') . '</a>';
                } else {
                    $count = '<strong>' . $counter->f(0) . '</strong> <a href="posts.php?cat_id=' . $cat_id . '">' . __('entries') . '</a>';
                }
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
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
                $counter = App::meta()->getPostsByMeta($params, true)->f(0);
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
                App::error()->add($e->getMessage());
            }
        }

        $res = '<div class="' . $class . '"><a class="media-icon media-link" href="' . $link_edit . '">' .
        '<img src="' . $icon . '" alt="" /></a>' .
        '<ul>' .
        '<li><a class="media-link" href="' . $link_edit . '"><img class="mark mark-edit" src="images/edit.svg" alt="' . __('edit') . '" title="' . __('edit the template') . '" /> ' . $fname . '</a> ' . $special . '</li>';
        /*
                if (App::auth()->check(App::auth()->makePermissions([
                    App::auth()::PERMISSION_CONTENT_ADMIN,
                    My::PERMISSION_TEMPLATOR,
                ]), App::blog()->id())) {
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
        '<img src="images/plus.svg" alt="' . __('copy') . '" title="' . __('copy the template') . '" /></a>&nbsp;';

        if ($f->del) {
            $res .= '<a class="media-remove" href="' .
            My::manageUrl(['part' => 'delete', 'file' => $f->basename]) . '">' .
            '<img src="images/trash.svg" alt="' . __('delete') . '" title="' . __('delete the template') . '" /></a>';
        }

        $res .= '</li>';

        $res .= '</ul></div>';

        return $res;
    }
}
