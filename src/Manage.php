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

use adminGenericFilterV2;
use adminPostList;
use dcAdminFilters;
use dcCore;
use dcNsProcess;
use dcPage;
use dcPager;
use Dotclear\Helper\File\Files;
use Dotclear\Helper\Html\Html;
use Exception;

use form;

class Manage extends dcNsProcess
{
    public static function init(): bool
    {
        static::$init == defined('DC_CONTEXT_ADMIN')
            && My::phpCompliant()
            && !is_null(dcCore::app()->auth)
            && !is_null(dcCore::app()->blog)
            && dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
                dcCore::app()->auth::PERMISSION_CONTENT_ADMIN,
                My::PERMISSION_TEMPLATOR,
            ]), dcCore::app()->blog->id);

        return static::$init;
    }

    public static function process(): bool
    {
        if (!static::$init) {
            return false;
        }

        // nullsafe
        if (is_null(dcCore::app()->blog) || is_null(dcCore::app()->adminurl)) {
            return false;
        }

        // instances
        $t = Templator::instance();
        $v = ManageVars::instance();

        /*
         * Duplicate dc template
         */
        if ('new' == $v->part && !empty($_POST['filesource'])) {
            try {
                if ('category' == $_POST['filesource']) {
                    $name = 'category-' . $_POST['filecat'] . '.html';
                } elseif (!empty($_POST['filename'])) {
                    $name = Files::tidyFileName($_POST['filename']) . '.html';
                } else {
                    throw new Exception(__('Filename is empty.'));
                }
                $t->initializeTpl($name, $_POST['filesource']);

                if (!dcCore::app()->error->flag()) {
                    dcPage::addSuccessNotice(__('The new template has been successfully created.'));
                    dcCore::app()->adminurl->redirect('admin.plugin.' . My::id());
                }
            } catch (Exception $e) {
                dcCore::app()->error->add($e->getMessage());
            }
        }

        /*
         * Copy tempaltor template
         */
        if ('copy' == $v->part && !empty($_POST['filename'])) {
            try {
                $t->copypasteTpl(
                    rawurldecode($_POST['filename']) . '.html',
                    rawurldecode($_POST['file'])
                );

                if (!dcCore::app()->error->flag()) {
                    dcPage::addSuccessNotice(__('The template has been successfully copied.'));
                    dcCore::app()->adminurl->redirect('admin.plugin.' . My::id(), ['part' => 'files']);
                }
            } catch (Exception $e) {
                dcCore::app()->error->add($e->getMessage());
            }
        }

        /*
         * Copy templator category template
         */
        if ('copycat' == $v->part && !empty($_POST['filecat'])) {
            try {
                $t->copypasteTpl(
                    'category-' . rawurldecode($_POST['filecat']) . '.html',
                    rawurldecode($_POST['file'])
                );

                if (!dcCore::app()->error->flag()) {
                    dcPage::addSuccessNotice(__('The template has been successfully copied.'));
                    dcCore::app()->adminurl->redirect('admin.plugin.' . My::id(), ['part' => 'files']);
                }
            } catch (Exception $e) {
                dcCore::app()->error->add($e->getMessage());
            }
        }

        /*
         * Delete tempaltor template
         */
        if ('delete' == $v->part && !empty($_POST['file'])) {
            try {
                $file = rawurldecode($_POST['file']);
                $v->media->removeItem($file);
                dcCore::app()->meta->delMeta($file, 'template');

                if (!dcCore::app()->error->flag()) {
                    dcPage::addSuccessNotice(__('The template has been successfully removed.'));
                    dcCore::app()->adminurl->redirect('admin.plugin.' . My::id(), ['part' => 'files']);
                }
            } catch (Exception $e) {
                dcCore::app()->error->add($e->getMessage());
            }
        }

        return true;
    }

    public static function render(): void
    {
        if (!static::$init) {
            return;
        }

        // nullsafe
        if (is_null(dcCore::app()->auth) || is_null(dcCore::app()->blog) || is_null(dcCore::app()->adminurl) || is_null(dcCore::app()->auth->user_prefs)) {
            return;
        }

        // instances
        $t = Templator::instance();
        $v = ManageVars::instance();

        /*
         * Check
         */

        if (!$t->canUseRessources(true)) {
            dcCore::app()->error->add(__('The plugin is unusable with your configuration. You have to change file permissions.'));
            dcPage::openModule(My::name());
            echo
            dcPage::breadcrumb([
                __('Plugins')          => '',
                My::name() => dcCore::app()->adminurl->get('admin.plugin.' . My::id())
            ]) .
            dcPage::notices();

            /*
             * Duplicate dotclear template
             */
        } elseif ('new' == $v->part) {
            dcPage::openModule(My::name());
            echo
            dcPage::breadcrumb([
                __('Plugins')          => '',
                My::name() => dcCore::app()->adminurl->get('admin.plugin.' . My::id()),
                $v->name     => '',
            ]) .
            dcPage::notices() .

            '<form action="' . dcCore::app()->adminurl->get('admin.plugin.' . My::id(), ['part' => 'new']) . '" method="post" id="add-template">' .
            '<h3>' . $v->name . '</h3>' .
            '<p><label for="filesource" class="required"><abbr title="' . __('Required field') . '">*</abbr> ' . __('Template source:') . '</label> ' .
            form::combo('filesource', $v->sources) . '</p>' .
            '<p><label for="filename" class="required"><abbr title="' . __('Required field') . '">*</abbr> ' . __('Filename:') . '</label> ' .
            form::field('filename', 25, 255) . '</p>' .
            '<p class="form-note">' . __('Extension .html is automatically added to filename.') . '</p>';

            if ($v->has_categories) {
                echo
                '<p><label for="filecat" class="required"><abbr title="' . __('Required field') . '">*</abbr>' . __('Category:') . '</label> ' .
                form::combo('filecat', $v->categories, '') . '</p>' .
                '<p class="form-note">' . __('Required only for category template.') . '</p>';
            }

            echo
            '<p>' .
            dcCore::app()->formNonce() .
            '<input type="submit" value="' . __('Create') . '" /></p>' .
            '</form>';

            /*
             * Copy templator template
             */
        } elseif ('copy' == $v->part && !empty($_REQUEST['file'])) {
            dcPage::openModule(My::name());
            echo
            dcPage::breadcrumb([
                __('Plugins')                 => '',
                My::name()        => dcCore::app()->adminurl->get('admin.plugin.' . My::id()),
                $v->name => '',
            ]) .
            dcPage::notices() .

            '<form action="' . dcCore::app()->adminurl->get('admin.plugin.' . My::id(), ['part' => 'copy']) . '" method="post">' .
            '<h3>' . $v->name . '</h3>' .
            '<p><label for="filename" class="required"><abbr title="' . __('Required field') . '">*</abbr> ' . __('New filename:') . '</label> ' .
            form::field('filename', 25, 255) . '<code>' . Html::escapeHTML('.html') . '</code></p> ' .
            '<p class="form-note">' . sprintf(
                __('To copy the template <strong>%s</strong>, you need to fill a new filename.'),
                Html::escapeHTML($_REQUEST['file'])
            ) . '</p>' .
            '<p>' .
            '<input type="submit" name="submit" value="' . __('Copy') . '" /> ' .
            '<a class="button" href="' . dcCore::app()->adminurl->get('admin.plugin.' . My::id(), ['part' => 'files']) . '">' . __('Cancel') . '</a>' .
            dcCore::app()->formNonce() .
            form::hidden('file', Html::escapeHTML($_REQUEST['file'])) . '</p>' .
            '</form>';

            /*
             * Copy templator category template
             */
        } elseif ('copycat' == $v->part && !empty($_REQUEST['file'])) {
            $category_id = (int) str_replace(['category-','.html'], '', $_REQUEST['file']);
            $cat_parents = dcCore::app()->blog->getCategoryParents($category_id);
            $full_name   = '';
            while ($cat_parents->fetch()) {
                $full_name = $cat_parents->f('cat_title') . ' &rsaquo; ';
            };
            $name = $full_name . dcCore::app()->blog->getCategory($category_id)->f('cat_title');

            dcPage::openModule(My::name());
            echo
            dcPage::breadcrumb([
                __('Plugins')                 => '',
                My::name()        => dcCore::app()->adminurl->get('admin.plugin.' . My::id()),
                $v->name => '',
            ]) .
            dcPage::notices() .

            '<form action="' . dcCore::app()->adminurl->get('admin.plugin.' . My::id(), ['part' => 'copycat']) . '" method="post">' .
            '<h3>' . $v->name . '</h3>' .
            '<p><label for="filecat" class="required"><abbr title="' . __('Required field') . '">*</abbr> ' . __('Target category:') . '</label> ' .
            form::combo('filecat', $v->categories, '') . '</p>' .
            '<p class="form-note">' . sprintf(
                __('To copy the template <strong>%s</strong> (%s), you need to choose a category.'),
                Html::escapeHTML($_GET['file']),
                $name
            ) . '</p>' .
            '<input type="submit" name="submit" value="' . __('Copy') . '" /> ' .
            '<a class="button" href="' . dcCore::app()->adminurl->get('admin.plugin.' . My::id(), ['part' => 'files']) . '">' . __('Cancel') . '</a>' .
            dcCore::app()->formNonce() .
            form::hidden('file', Html::escapeHTML($_REQUEST['file'])) . '</p>' .
            '</form>';

            /*
             * Delete templator template
             */
        } elseif ('delete' == $v->part && !empty($_REQUEST['file'])) {
            dcPage::openModule(My::name());
            echo
            dcPage::breadcrumb([
                __('Plugins')                   => '',
                My::name()          => dcCore::app()->adminurl->get('admin.plugin.' . My::id()),
                $v->name => '',
            ]) .
            dcPage::notices() .

            '<form action="' . dcCore::app()->adminurl->get('admin.plugin.' . My::id(), ['part' => 'delete']) . '" method="post">' .
            '<h3>' . $v->name . '</h3>' .
            '<p>' . sprintf(
                __('Are you sure you want to remove the template "%s"?'),
                Html::escapeHTML($_GET['file'])
            ) . '</p>' .
            '<p><input type="submit" class="delete" value="' . __('Delete') . '" /> ' .
            '<a class="button" href="' . dcCore::app()->adminurl->get('admin.plugin.' . My::id(), ['part' => 'files']) . '">' . __('Cancel') . '</a>' .
            dcCore::app()->formNonce() .
            form::hidden('file', Html::escapeHTML($_GET['file'])) . '</p>' .
            '</form>';

            /*
             * List templator templates
             */
        } elseif ('files' == $v->part) {
            dcPage::openModule(My::name());
            echo
            dcPage::breadcrumb([
                __('Plugins')             => '',
                My::name()    => dcCore::app()->adminurl->get('admin.plugin.' . My::id()),
                $v->name => '',
            ]) .
            dcPage::notices() .
            '<h3>' . $v->name . '</h3>';

            if (count($v->items) == 0) {
                echo '<p><strong>' . __('No template.') . '</strong></p>';
            } else {
                // reuse "used templatro template" filter settings
                $filter = new adminGenericFilterV2(My::id());
                $filter->add(dcAdminFilters::getPageFilter());
                $page = is_numeric($filter->value('page')) ? (int) $filter->value('page') : 1;
                $nb   = is_numeric($filter->value('nb')) ? (int) $filter->value('nb') : 1;

                $pager = new dcPager($page, count($v->items), $nb, 10);

                echo
                '<div class="media-list">' .
                $pager->getLinks();

                for ($i = $pager->index_start, $j = 0; $i <= $pager->index_end; $i++, $j++) {
                    echo Pager::line($v->items[$i], $j);
                }

                echo
                $pager->getLinks() .
                '</div>';
            }

            /*
             * List Used templator template
             */
        } elseif ('used' == $v->part) {
            $tags = dcCore::app()->meta->getMetadata(['meta_type' => 'template']);
            $tags = dcCore::app()->meta->computeMetaStats($tags);
            $tags->sort('meta_id_lower', 'asc');

            $last_letter = null;
            $cols        = ['',''];
            $col         = 0;

            while ($tags->fetch()) {
                $meta_id = is_string($tags->f('meta_id')) ? $tags->f('meta_id') : '';
                $count   = is_numeric($tags->f('count')) ? (int) $tags->f('count') : 1;
                $letter  = mb_strtoupper(mb_substr($meta_id, 0, 1));

                if ($last_letter != $letter) {
                    if ($tags->index() >= round($tags->count() / 2)) {
                        $col = 1;
                    }
                    $cols[$col] .= '<tr class="tagLetter"><td colspan="2"><span>' . $letter . '</span></td></tr>';
                }

                $img = '<img alt="%1$s" title="%1$s" src="images/%2$s" />';
                if (array_key_exists($meta_id, $t->getTpl())) {
                    $img_status = sprintf($img, __('available template'), 'check-on.png');
                } else {
                    $img_status = sprintf($img, __('missing template'), 'check-off.png');
                }

                $cols[$col] .= '<tr class="line">' .
                    '<td class="maximal"><a href="' .
                    dcCore::app()->adminurl->get('admin.plugin.' . My::id(), [
                        'part'  => 'posts',
                        'file'  => $meta_id,
                        'redir' => dcCore::app()->adminurl->get('admin.plugin.' . My::id(), ['part' => 'used']),
                    ]) . '">' . $meta_id . '</a> ' . $img_status . '</td>' .
                    '<td class="nowrap"><strong>' . $count . '</strong> ' .
                    (($count == 1) ? __('entry') : __('entries')) . '</td>' .
                '</tr>';

                $last_letter = $letter;
            }

            $table = '<div class="col"><table class="tags">%s</table></div>';

            dcPage::openModule(
                My::name(),
                dcPage::cssModuleLoad('tags/style.css')
            );
            echo
            dcPage::breadcrumb([
                __('Plugins')          => '',
                My::name() => dcCore::app()->adminurl->get('admin.plugin.' . My::id()),
                $v->name   => '',
            ]) .
            dcPage::notices() .
            '<h3>' . $v->name . '</h3>';

            if ($cols[0]) {
                echo '<div class="two-cols">';
                printf($table, $cols[0]);
                if ($cols[1]) {
                    printf($table, $cols[1]);
                }
                echo '</div>';
            } else {
                echo '<p>' . __('No specific templates on this blog.') . '</p>';
            }

            /*
             * Edit emplator template
             */
        } elseif ('edit' == $v->part && !empty($_REQUEST['file'])) {
            $file = ['c' => '', 'w' => false, 'f' => ''];
            $name = '';

            try {
                try {
                    $name = rawurldecode($_REQUEST['file']);
                    $file = $t->getSourceContent($name);
                    $name = $file['f'];

                    if (preg_match('/^category-(.+).html$/', $name, $cat_id)) {
                        $category    = dcCore::app()->blog->getCategory((int) $cat_id[1]);
                        $full_name   = '';
                        $cat_parents = dcCore::app()->blog->getCategoryParents((int) $cat_id[1]);
                        while ($cat_parents->fetch()) {
                            $full_name = $cat_parents->f('cat_title') . ' &rsaquo; ';
                        };
                        $name .= '</strong> (' . $full_name . $category->f('cat_title') . ')<strong>';
                    }
                } catch (Exception $e) {
                    $file['c'] = null;

                    throw $e;
                }
                # Write file
                if (!empty($_POST['write'])) {
                    $file['c'] = $_POST['file_content'] ?? null;
                    $t->writeTpl($file['f'], $file['c']);
                }
            } catch (Exception $e) {
                dcCore::app()->error->add($e->getMessage());
            }
            
            $ict = dcCore::app()->auth->user_prefs->get('interface')->get('colorsyntax_theme');

            dcPage::openModule(
                My::name(),
                (dcCore::app()->auth->user_prefs->get('interface')->get('colorsyntax') ?
                    dcPage::jsJson('dotclear_colorsyntax', ['colorsyntax' => dcCore::app()->auth->user_prefs->get('interface')->get('colorsyntax')]) : ''
                ) .
                dcPage::jsJson('theme_editor_msg', [
                    'saving_document'    => __('Saving document...'),
                    'document_saved'     => __('Document saved'),
                    'error_occurred'     => __('An error occurred:'),
                    'confirm_reset_file' => __('Are you sure you want to reset this file?'),
                ]) .
                dcPage::jsModuleLoad('themeEditor/js/script.js') .
                dcPage::jsConfirmClose('file-form') .
                (dcCore::app()->auth->user_prefs->get('interface')->get('colorsyntax') ?
                    dcPage::jsLoadCodeMirror(is_string($ict) ? $ict : '') : ''
                ) .
                dcPage::cssModuleLoad('themeEditor/style.css')
            );

            echo
            dcPage::breadcrumb([
                __('Plugins')          => '',
                My::name() => dcCore::app()->adminurl->get('admin.plugin.' . My::id()),
                $v->name    => '',
            ]) .
            dcPage::notices();

            if (($file['c'] !== null)) {
                echo
                '<form id="file-form" action="' . dcCore::app()->adminurl->get('admin.plugin.' . My::id(), ['part' => 'edit', 'file' => $name]) . '" method="post">' .
                '<div><h3><label for="file_content">' . sprintf(__('Editing file %s'), '<strong>' . $name) . '</strong></label></h3>' .
                '<p>' . form::textarea('file_content', 72, 25, [
                    'default'  => Html::escapeHTML($file['c']),
                    'class'    => 'maximal',
                    'disabled' => !$file['w'],
                ]) . '</p>';

                if ($file['w']) {
                    echo
                    '<p><input type="submit" name="write" value="' . __('Save') . '" accesskey="s" /> ' .
                    '<a class="button" href="' . dcCore::app()->adminurl->get('admin.plugin.' . My::id(), ['part' => 'files']) . '">' . __('Cancel') . '</a>' .
                    dcCore::app()->formNonce() .
                    form::hidden(['file_id'], Html::escapeHTML($file['f'])) .
                    '</p>';
                } else {
                    echo '<p>' . __('This file is not writable. Please check your files permissions.') . '</p>';
                }

                echo
                '</div></form>';
                if (dcCore::app()->auth->user_prefs->get('interface')->get('colorsyntax')) {
                    $ict = dcCore::app()->auth->user_prefs->get('interface')->get('colorsyntax_theme');
                    echo
                    dcPage::jsJson('theme_editor_mode', ['mode' => 'html']) .
                    dcPage::jsModuleLoad('themeEditor/js/mode.js') .
                    dcPage::jsRunCodeMirror('editor', 'file_content', 'dotclear', is_string($ict) ? $ict : '');
                }
            }

            /*
             * Edit posts options linked to a template
             */
        } elseif ('posts' == $v->part && (!empty($_REQUEST['file']) || $_REQUEST['file'] == '0')) {
            $file  = $_REQUEST['file'];
            $redir = $_REQUEST['redir'] ?? dcCore::app()->adminurl->get('admin.plugin.' . My::id(), ['part' => 'used']);

            # Unselect the template
            if (!empty($_POST['action']) && 'unselecttpl' == $_POST['action'] && dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
                dcCore::app()->auth::PERMISSION_PUBLISH,
                dcCore::app()->auth::PERMISSION_CONTENT_ADMIN,
            ]), dcCore::app()->blog->id)) {
                try {
                    dcCore::app()->meta->delMeta($file, 'template');
                    dcCore::app()->adminurl->redirect('admin.plugin.' . My::id(), ['part' => 'posts', 'file' => $file]);
                } catch (Exception $e) {
                    dcCore::app()->error->add($e->getMessage());
                }
            }

            $filter = new adminGenericFilterV2('templator');
            $filter->add(dcAdminFilters::getPageFilter());
            $filter->add('part', 'posts');
            $filter->add('file', $file);
            $filter->add('post_type', '');

            $params               = $filter->params();
            $params['no_content'] = true;
            $params['meta_id']    = $file;
            $params['meta_type']  = 'template';

            # Get posts
            try {
                $posts = dcCore::app()->meta->getPostsByMeta($params);
                if (is_null($posts)) {
                    throw new Exception(__('Failed to get posts meta'));
                }
                $counter   = dcCore::app()->meta->getPostsByMeta($params, true)?->f(0);
                $counter   = is_numeric($counter) ? (int) $counter : 0;
                $post_list = new adminPostList($posts, $counter);
            } catch (Exception $e) {
                dcCore::app()->error->add($e->getMessage());
            }

            dcPage::openModule(
                My::name(),
                dcPage::jsFilterControl($filter->show()) .
                dcPage::jsModuleLoad(My::id() . '/js/posts.js') .
                $filter->js(dcCore::app()->adminurl->get('admin.plugin.' . My::id(), ['part' => 'posts', 'file' => $file]))
            );

            echo
            dcPage::breadcrumb([
                __('Plugins')           => '',
                My::name()  => dcCore::app()->adminurl->get('admin.plugin.' . My::id()),
                $v->name => '',
            ]) .
            dcPage::notices() .

            '<h3>' . sprintf(__('Unselect template "%s"'), '<strong>' . $file . '</strong>') . '</h3>' .
            '<p><a class ="back" href="' . $redir . '">' . __('Back') . '</a></p>';

            if (!dcCore::app()->error->flag() && isset($posts)) {
                if ($posts->isEmpty() && !$filter->show()) {
                    echo '<p>' . __('There is no entries') . '</p>';
                } else {
                    $page = is_numeric($filter->value('page')) ? (int) $filter->value('page') : 1;
                    $nb   = is_numeric($filter->value('nb')) ? (int) $filter->value('nb') : 0;
                    $filter->display(
                        'admin.plugin.' . My::id(),
                        form::hidden('p', 'templator') . form::hidden('part', 'posts') . form::hidden('file', $file)
                    );
                    # Show posts
                    $post_list->display(
                        $page,
                        $nb,
                        '<form action="' . dcCore::app()->adminurl->get('admin.plugin.' . My::id()) . '" method="post" id="form-entries">' .

                        '%s' .

                        '<div class="two-cols">' .
                        '<p class="col checkboxes-helpers"></p>' .

                        '<p class="col right">' .
                         '<input type="submit" value="' . __('Unselect template for selected entries') . '" /></p>' .
                        form::hidden('action', 'unselecttpl') .
                        dcCore::app()->adminurl->getHiddenFormFields('admin.plugin.' . My::id(), $filter->values()) .
                        form::hidden('redir', $redir) .
                        dcCore::app()->formNonce() .
                        '</div>' .
                        '</form>',
                        $filter->show()
                    );
                }
            }

            /*
             * Default page
             */
        } else {
            dcPage::openModule(My::name());
            echo
            dcPage::breadcrumb([
                __('Plugins')          => '',
                My::id() => '',
            ]) .
            dcPage::notices();

            $line = '<li><a href="%s">%s</a></li>';
            echo '
            <h4><i>' . __('Manage additional templates') . '</i></h4>' .
            sprintf(
                '<h3><ul class="nice">%s</ul></h3>',
                sprintf(
                    $line,
                    dcCore::app()->adminurl->get('admin.plugin.' . My::id(), ['part' => 'files']),
                    __('Available templates')
                ) .
                sprintf(
                    $line,
                    dcCore::app()->adminurl->get('admin.plugin.' . My::id(), ['part' => 'used']),
                    __('Used templates')
                ) .
                sprintf(
                    $line,
                    dcCore::app()->adminurl->get('admin.plugin.' . My::id(), ['part' => 'new']),
                    __('New template')
                )
            );
        }

        dcPage::helpBlock('templator');

        dcPage::closeModule();
    }
}
