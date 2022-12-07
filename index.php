<?php
if (!defined('DC_CONTEXT_ADMIN')) {
    return null;
}

dcPage::check(dcCore::app()->auth->makePermissions([
    dcAuth::PERMISSION_CONTENT_ADMIN,
    initTemplator::PERMISSION_TEMPLATOR,
]));

$part = $_REQUEST['part'] ?? '';

### grab info ###

if (in_array($part, ['files', 'delete'])) {
    $page           = !empty($_GET['page']) ? $_GET['page'] : 1;
    $nb_per_page    = 20;

    $media = new templatorMedia();
    $media->chdir(dcCore::app()->templator->template_dir_name);
    // For users with only templator permission, we use sudo.
    dcCore::app()->auth->sudo([$media,'getDir']);
    $dir   = $media->dir;
    //if files did not appear in this list, check blog->settings->media_exclusion
    $items = array_values($dir['files']);
}

if (in_array($part, ['new', 'copycat'])) {
    $has_categories = false;
    try {
        $categories = dcCore::app()->blog->getCategories(['post_type' => 'post']);

        $categories_combo = [];
        $l                = $categories->level;
        $full_name        = [$categories->cat_title];

        while ($categories->fetch()) {
            if ($categories->level < $l) {
                $full_name = [];
            } elseif ($categories->level == $l) {
                array_pop($full_name);
            }
            $full_name[] = html::escapeHTML($categories->cat_title);

            $categories_combo[implode(' &rsaquo; ', $full_name)] = $categories->cat_id;

            $l = $categories->level;
        }
        $has_categories = !$categories->isEmpty();
    } catch (Exception $e) {
    }

    $combo_source = [
        ' &mdash; ' => 'empty',
        'post.html' => 'post',
    ];

    if (dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([dcPages::PERMISSION_PAGES]), dcCore::app()->blog->id) && dcCore::app()->plugins->moduleExists('pages')) {
        $combo_source['page.html'] = 'page';
    }

    if ($has_categories) {
        $combo_source['category.html'] = 'category';
    }
}

### Action ###

/**
 * Duplicate dc template
 */
if ('new' == $part && !empty($_POST['filesource'])) {
    try {
        if ('category' == $_POST['filesource']) {
            $name = 'category-' . $_POST['filecat'] . '.html';
        } elseif (!empty($_POST['filename'])) {
            $name = files::tidyFileName($_POST['filename']) . '.html';
        } else {
            throw new Exception(__('Filename is empty.'));
        }
        dcCore::app()->templator->initializeTpl($name, $_POST['filesource']);

        if (!dcCore::app()->error->flag()) {
            dcAdminNotices::addSuccessNotice(__('The new template has been successfully created.'));
            dcCore::app()->adminurl->redirect('admin.plugin.templator');
        }
    } catch (Exception $e) {
        dcCore::app()->error->add($e->getMessage());
    }
}

/**
 * Copy tempaltor template
 */
if ('copy' == $part && !empty($_POST['filename'])) {
    try {
        dcCore::app()->templator->copypasteTpl(
            rawurldecode($_POST['filename']) . '.html', 
            rawurldecode($_POST['file'])
        );

        if (!dcCore::app()->error->flag()) {
            dcAdminNotices::addSuccessNotice(__('The template has been successfully copied.'));
            dcCore::app()->adminurl->redirect('admin.plugin.templator', ['part' => 'files']);
        }
    } catch (Exception $e) {
        dcCore::app()->error->add($e->getMessage());
    }
}

/**
 * Copy templator category template
 */
if ('copycat' == $part && !empty($_POST['filecat'])) {
    try {
        dcCore::app()->templator->copypasteTpl(
            'category-' . rawurldecode($_POST['filecat']) . '.html', 
            rawurldecode($_POST['file'])
        );

        if (!dcCore::app()->error->flag()) {
            dcAdminNotices::addSuccessNotice(__('The template has been successfully copied.'));
            dcCore::app()->adminurl->redirect('admin.plugin.templator', ['part' => 'files']);
        }
    } catch (Exception $e) {
        dcCore::app()->error->add($e->getMessage());
    }
}

/**
 * Delete tempaltor template
 */
if ('delete' == $part && !empty($_POST['file'])) {
    try {
        $file = rawurldecode($_POST['file']);
        $media->removeItem($file);
        dcCore::app()->meta->delMeta($file, 'template');

        if (!dcCore::app()->error->flag()) {
            dcAdminNotices::addSuccessNotice(__('The template has been successfully removed.'));
            dcCore::app()->adminurl->redirect('admin.plugin.templator', ['part' => 'files']);
        }
    } catch (Exception $e) {
        dcCore::app()->error->add($e->getMessage());
    }
}

### Display ###

/**
 * Check
 */

if (!dcCore::app()->templator->canUseRessources(true)) {
    dcCore::app()->error->add(__('The plugin is unusable with your configuration. You have to change file permissions.'));
    echo
    '<html><head><title>' . __('Templator') . '</title>' .
    '</head><body>' .
    dcPage::breadcrumb([
        __('Plugins') => '',
        __('Templates engine') => dcCore::app()->adminurl->get('admin.plugin.templator'),
        __('New template') => '',
    ]) .
    dcPage::notices();

/**
 * Duplicate dotclear template
 */
} elseif ('new' == $part) {
    echo
    '<html><head><title>' . __('Templator') . '</title>' .
    '</head><body>' .
    dcPage::breadcrumb([
        __('Plugins') => '',
        __('Templates engine') => dcCore::app()->adminurl->get('admin.plugin.templator'),
        __('New template') => '',
    ]) .
    dcPage::notices() .

    '<form action="' . dcCore::app()->adminurl->get('admin.plugin.templator', ['part' => 'new']) . '" method="post" id="add-template">' .
    '<h3>' . __('New template') . '</h3>' .
    '<p><label for="filesource" class="required"><abbr title="' . __('Required field') . '">*</abbr> ' . __('Template source:') . '</label> ' .
    form::combo('filesource', $combo_source) . '</p>' .
    '<p><label for="filename" class="required"><abbr title="' . __('Required field') . '">*</abbr> ' . __('Filename:') . '</label> ' .
    form::field('filename', 25, 255) . '</p>' .
    '<p class="form-note">' . __('Extension .html is automatically added to filename.') . '</p>';

    if ($has_categories) {
        echo 
        '<p><label for="filecat" class="required"><abbr title="' . __('Required field') . '">*</abbr>' . __('Category:') .'</label> ' .
        form::combo('filecat', $categories_combo, '') . '</p>' .
        '<p class="form-note">' . __('Required only for category template.') . '</p>';
    }

    echo
    '<p>' . 
    dcCore::app()->formNonce() .
    '<input type="submit" value="' . __('Create') . '" /></p>' .
    '</form>';

/**
 * Copy templator template
 */
} elseif ('copy' == $part && !empty($_REQUEST['file'])) {
    echo
    '<html><head><title>' . __('Templator') . '</title>' .
    '</head><body>' .
    dcPage::breadcrumb([
        __('Plugins') => '',
        __('Templates engine') => dcCore::app()->adminurl->get('admin.plugin.templator'),
        __('Copy available template') => '',
    ]) .
    dcPage::notices() .

    '<form action="' . dcCore::app()->adminurl->get('admin.plugin.templator', ['part' => 'copy']) . '" method="post">' .
    '<h3>' . __('Copy available template') . '</h3>' .
    '<p><label for="filename" class="required"><abbr title="' . __('Required field') . '">*</abbr> ' . __('New filename:') . '</label> ' .
    form::field('filename', 25, 255) . '<code>' . html::escapeHTML('.html') . '</code></p> ' .
    '<p class="form-note">' . sprintf(
        __('To copy the template <strong>%s</strong>, you need to fill a new filename.'),
        html::escapeHTML($_REQUEST['file'])
    ) . '</p>' .
    '<p>' .
    '<input type="submit" name="submit" value="' . __('Copy') . '" /> ' .
    '<a class="button" href="' . dcCore::app()->adminurl->get('admin.plugin.templator', ['part' => 'files']) . '">' . __('Cancel') . '</a>' .
    dcCore::app()->formNonce() .
    form::hidden('file', html::escapeHTML($_REQUEST['file'])) . '</p>' .
    '</form>';

/**
 * Copy templator category template
 */
} elseif ('copycat' == $part && !empty($_REQUEST['file'])) {
    $category_id = str_replace(['category-','.html'], '', $_REQUEST['file']);
    $cat_parents = dcCore::app()->blog->getCategoryParents($category_id);
    $full_name   = '';
    while ($cat_parents->fetch()) {
        $full_name = $cat_parents->cat_title . ' &rsaquo; ';
    };
    $name = $full_name . dcCore::app()->blog->getCategory($category_id)->cat_title;

    echo
    '<html><head><title>' . __('Templator') . '</title>' .
    '</head><body>' .
    dcPage::breadcrumb([
        __('Plugins') => '',
        __('Templates engine') => dcCore::app()->adminurl->get('admin.plugin.templator'),
        __('Copy available template') => '',
    ]) .
    dcPage::notices() .

    '<form action="' . dcCore::app()->adminurl->get('admin.plugin.templator', ['part' => 'copycat']) . '" method="post">' .
    '<h3>' . __('Copy available template') . '</h3>' .
    '<p><label for="filecat" class="required"><abbr title="' . __('Required field') . '">*</abbr> ' . __('Target category:') . '</label> ' .
    form::combo('filecat', $categories_combo, '') . '</p>' .
    '<p class="form-note">' . sprintf(
        __('To copy the template <strong>%s</strong> (%s), you need to choose a category.'),
        html::escapeHTML($_GET['file']),
        $name
    ) . '</p>' .
    '<input type="submit" name="submit" value="' . __('Copy') . '" /> ' .
    '<a class="button" href="' . dcCore::app()->adminurl->get('admin.plugin.templator', ['part' => 'files']) . '">' . __('Cancel') . '</a>' .
    dcCore::app()->formNonce() .
    form::hidden('file', html::escapeHTML($_REQUEST['file'])) . '</p>' .
    '</form>';

/**
 * Delete templator template
 */
} elseif ('delete' == $part && !empty($_REQUEST['file'])) {
    echo
    '<html><head><title>' . __('Templator') . '</title>' .
    '</head><body>' .
    dcPage::breadcrumb([
        __('Plugins') => '',
        __('Templates engine') => dcCore::app()->adminurl->get('admin.plugin.templator'),
        __('Delete available template') => '',
    ]) .
    dcPage::notices() .

    '<form action="' . dcCore::app()->adminurl->get('admin.plugin.templator', ['part' => 'delete']) . '" method="post">' .
    '<h3>' . __('Delete available template') . '</h3>' .
    '<p>' . sprintf(
        __('Are you sure you want to remove the template "%s"?'),
        html::escapeHTML($_GET['file'])
    ) . '</p>' .
    '<p><input type="submit" class="delete" value="' . __('Delete') . '" /> ' .
    '<a class="button" href="' . dcCore::app()->adminurl->get('admin.plugin.templator', ['part' => 'files']) . '">' . __('Cancel') . '</a>' .
    dcCore::app()->formNonce() .
    form::hidden('file', html::escapeHTML($_GET['file'])) . '</p>' .
    '</form>';

/**
 * List templator templates
 */
} elseif ('files' == $part) {
    echo
    '<html><head><title>' . __('Templator') . '</title>' .
    '</head><body>' .
    dcPage::breadcrumb([
        __('Plugins') => '',
        __('Templates engine') => dcCore::app()->adminurl->get('admin.plugin.templator'),
        __('Available templates') => '',
    ]) .
    dcPage::notices() .
    '<h3>' . __('Available templates') . '</h3>';

    if (count($items) == 0) {
        echo '<p><strong>' . __('No template.') . '</strong></p>';
    } else {
        $pager            = new pager($page, count($items), $nb_per_page, 10);
        $pager->html_prev = __('&#171;prev.');
        $pager->html_next = __('next&#187;');

        echo
        '<form action="media.php" method="get">' .
        '</form>' .

        '<div class="media-list">' .
        '<p>' . __('Page(s)') . ' : ' . $pager->getLinks() . '</p>';

        for ($i = $pager->index_start, $j = 0; $i <= $pager->index_end; $i++, $j++) {
            echo pagerTemplator::templatorItemLine($items[$i], $j);
        }

        echo
        '<p class="clear">' . __('Page(s)') . ' : ' . $pager->getLinks() . '</p>' .
        '</div>';
    }

/**
 * List Used templator template
 */
} elseif ('used' == $part) {
    $tags = dcCore::app()->meta->getMetadata(['meta_type' => 'template']);
    $tags = dcCore::app()->meta->computeMetaStats($tags);
    $tags->sort('meta_id_lower', 'asc');

    $last_letter = null;
    $cols        = ['',''];
    $col         = 0;

    while ($tags->fetch()) {
        $letter = mb_strtoupper(mb_substr($tags->meta_id, 0, 1));

        if ($last_letter != $letter) {
            if ($tags->index() >= round($tags->count() / 2)) {
                $col = 1;
            }
            $cols[$col] .= '<tr class="tagLetter"><td colspan="2"><span>' . $letter . '</span></td></tr>';
        }

        $img = '<img alt="%1$s" title="%1$s" src="images/%2$s" />';
        if (array_key_exists($tags->meta_id, dcCore::app()->templator->tpl)) {
            $img_status = sprintf($img, __('available template'), 'check-on.png');
        } else {
            $img_status = sprintf($img, __('missing template'), 'check-off.png');
        }

        $cols[$col] .= '<tr class="line">' .
            '<td class="maximal"><a href="' . 
            dcCore::app()->adminurl->get('admin.plugin.templator', [
                'part'  => 'posts', 
                'file'  => $tags->meta_id,
                'redir' =>  dcCore::app()->adminurl->get('admin.plugin.templator', ['part' => 'used']),
            ]) .'">' . $tags->meta_id . '</a> ' . $img_status . '</td>' .
            '<td class="nowrap"><strong>' . $tags->count . '</strong> ' .
            (($tags->count == 1) ? __('entry') : __('entries')) . '</td>' .
        '</tr>';

        $last_letter = $letter;
    }

    $table = '<div class="col"><table class="tags">%s</table></div>';

    echo
    '<html><head><title>' . __('Templator') . '</title>' .
    dcPage::cssLoad(dcPage::getPF('tags/style.css')) .
    '</head><body>' .
    dcPage::breadcrumb([
        __('Plugins') => '',
        __('Templates engine') => dcCore::app()->adminurl->get('admin.plugin.templator'),
        __('Used templates') => '',
    ]) .
    dcPage::notices() .
    '<h3>' . __('Used templates') . '</h3>';

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

/**
 * Edit emplator template
 */
} elseif ('edit' == $part && !empty($_REQUEST['file'])) {
    try {
        try {
            $name = rawurldecode($_REQUEST['file']);
            $file = dcCore::app()->templator->getSourceContent($name);
            $name = $file['f'];

            if (preg_match('/^category-(.+).html$/', $name, $cat_id)) {
                $category    = dcCore::app()->blog->getCategory($cat_id[1]);
                $full_name   = '';
                $cat_parents = dcCore::app()->blog->getCategoryParents($cat_id[1]);
                while ($cat_parents->fetch()) {
                    $full_name = $cat_parents->cat_title . ' &rsaquo; ';
                };
                $full_name = $full_name . dcCore::app()->blog->getCategory($cat_id)->cat_title;
                $name .= '</strong> (' . $full_name . $category->cat_title . ')<strong>';
            }
        } catch (Exception $e) {
            $file = $file_default;

            throw $e;
        }
        # Write file
        if (!empty($_POST['write'])) {
            $file['c'] = $_POST['file_content'];
            dcCore::app()->templator->writeTpl($file['f'], $file['c']);
        }
    } catch (Exception $e) {
        dcCore::app()->error->add($e->getMessage());
    }

    dcCore::app()->auth->user_prefs->addWorkspace('interface');

    echo
    '<html><head><title>' . __('Templator') . '</title>';

    if (dcCore::app()->auth->user_prefs->interface->colorsyntax) {
        echo
        dcPage::jsJson('dotclear_colorsyntax', ['colorsyntax' => dcCore::app()->auth->user_prefs->interface->colorsyntax]);
    }
    echo
    dcPage::jsJson('theme_editor_msg', [
        'saving_document'    => __('Saving document...'),
        'document_saved'     => __('Document saved'),
        'error_occurred'     => __('An error occurred:'),
        'confirm_reset_file' => __('Are you sure you want to reset this file?'),
    ]) .
    dcPage::jsModuleLoad('themeEditor/js/script.js') .
    dcPage::jsConfirmClose('file-form');
    if (dcCore::app()->auth->user_prefs->interface->colorsyntax) {
        echo
        dcPage::jsLoadCodeMirror(dcCore::app()->auth->user_prefs->interface->colorsyntax_theme);
    }
    echo
    dcPage::cssModuleLoad('themeEditor/style.css') .
    '</head><body>' .
    dcPage::breadcrumb([
        __('Plugins') => '',
        __('Templates engine') => dcCore::app()->adminurl->get('admin.plugin.templator'),
        __('Edit template') => '',
    ]) .
    dcPage::notices();

    if (($file['c'] !== null)) {
        echo
        '<form id="file-form" action="' . dcCore::app()->adminurl->get('admin.plugin.templator', ['part' => 'edit', 'file' => $name]) . '" method="post">' .
        '<div><h3><label for="file_content">' . sprintf(__('Editing file %s'), '<strong>' . $name) . '</strong></label></h3>' .
        '<p>' . form::textarea('file_content', 72, 25, [
            'default'  => html::escapeHTML($file['c']),
            'class'    => 'maximal',
            'disabled' => !$file['w'],
        ]) . '</p>';

        if ($file['w']) {
            echo
            '<p><input type="submit" name="write" value="' . __('Save') . '" accesskey="s" /> ' .
            '<a class="button" href="' . dcCore::app()->adminurl->get('admin.plugin.templator', ['part' => 'files']) . '">' . __('Cancel') . '</a>' .
            dcCore::app()->formNonce() .
            form::hidden(['file_id'], html::escapeHTML($file['f'])) .
            '</p>';
        } else {
            echo '<p>' . __('This file is not writable. Please check your files permissions.') . '</p>';
        }

        echo
        '</div></form>';
        if (dcCore::app()->auth->user_prefs->interface->colorsyntax) {
            echo
            dcPage::jsJson('theme_editor_mode', ['mode' => 'html']) .
            dcPage::jsModuleLoad('themeEditor/js/mode.js') .
            dcPage::jsRunCodeMirror('editor', 'file_content', 'dotclear', dcCore::app()->auth->user_prefs->interface->colorsyntax_theme);
        }
    }

/**
 * Edit posts options linked to a template
 */
} elseif ('posts' == $part && (!empty($_REQUEST['file']) || $_REQUEST['file'] == '0')) {
    $file = $_REQUEST['file'];
    $redir = $_REQUEST['redir'] ?? dcCore::app()->adminurl->get('admin.plugin.templator', ['part' => 'used']);

    # Unselect the template
    if (!empty($_POST['action']) && 'unselecttpl' == $_POST['action'] && dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
        dcAuth::PERMISSION_PUBLISH,
        dcAuth::PERMISSION_CONTENT_ADMIN,
    ]), dcCore::app()->blog->id)) {
        try {
            dcCore::app()->meta->delMeta($file, 'template');
            dcCore::app()->adminurl->redirect('admin.plugin.templator', ['part' => 'posts', 'file' => $file]);
        } catch (Exception $e) {
            dcCore::app()->error->add($e->getMessage());
        }
    }

    $filter = new adminGenericFilterV2('templator');
    $filter->add(dcAdminFilters::getPageFilter());
    $filter->add('part', 'posts');
    $filter->add('file', $file);
    $filter->add('post_type', '');

    $params = $filter->params();
    $params['no_content'] = true;
    $params['meta_id'] = $file;
    $params['meta_type'] = 'template';

    # Get posts
    try {
        $posts     = dcCore::app()->meta->getPostsByMeta($params);
        $counter   = dcCore::app()->meta->getPostsByMeta($params, true);
        $post_list = new adminPostList($posts, $counter->f(0));
    } catch (Exception $e) {
        dcCore::app()->error->add($e->getMessage());
    }

    echo
    '<html><head><title>' . __('Templator') . '</title>' .
    dcPage::jsFilterControl($filter->show()) .
    dcPage::jsLoad(dcPage::getPF('templator/js/posts.js')) .
    $filter->js(dcCore::app()->adminurl->get('admin.plugin.templator', ['part' => 'posts', 'file' => $file])) .
    '</head><body>' .
    dcPage::breadcrumb([
        __('Plugins') => '',
        __('Templates engine') => dcCore::app()->adminurl->get('admin.plugin.templator'),
        __('Unselect template') => '',
    ]) .
    dcPage::notices() .

    '<h3>' . __('Unselect template') . '</h3>' .
    '<p><a class ="back" href="' . $redir . '">' . __('Back') . '</a></p>';

    if (!dcCore::app()->error->flag()) {
        if ($posts->isEmpty() && !$filter->show()) {
            echo '<p>' . __('There is no entries') . '</p>';
        } else {
            $filter->display(
                'admin.plugin.templator',
                form::hidden('p', 'templator') . form::hidden('part', 'posts') . form::hidden('file', $file)
            );
            # Show posts
            $post_list->display(
                $filter->page,
                $filter->nb,
                '<form action="' . dcCore::app()->adminurl->get('admin.plugin.templator') . '" method="post" id="form-entries">' .

                '%s' .

                '<div class="two-cols">' .
                '<p class="col checkboxes-helpers"></p>' .

                '<p class="col right">' . 
                 '<input type="submit" value="' . __('Unselect template for selcted entries') . '" /></p>' .
                form::hidden('action', 'unselecttpl') .
                dcCore::app()->adminurl->getHiddenFormFields('admin.plugin.templator', $filter->values()) .
                form::hidden('redir', $redir) .
                dcCore::app()->formNonce() .
                '</div>' .
                '</form>',
                $filter->show(),
                dcCore::app()->adminurl->get('admin.plugin.templator', $filter->values())
            );
        }
    }

/**
 * Default page
 */
} else {
    echo
    '<html><head><title>' . __('Templator') . '</title>' .
    '</head><body>' .
    dcPage::breadcrumb([
        __('Plugins') => '',
        __('Templates engine') => '',
    ]) .
    dcPage::notices();

    $line = '<li><a href="%s">%s</a></li>';
    echo '
    <h4><i>' . __('Manage additional templates') . '</i></h4>' .
    sprintf(
        '<h3><ul class="nice">%s</ul></h3>',
        sprintf(
            $line,
            dcCore::app()->adminurl->get('admin.plugin.templator', ['part' => 'files']),
            __('Available templates')
        ) .
        sprintf(
            $line,
            dcCore::app()->adminurl->get('admin.plugin.templator', ['part' => 'used']),
            __('Used templates')
        ) .
        sprintf(
            $line,
            dcCore::app()->adminurl->get('admin.plugin.templator', ['part' => 'new']),
            __('New template')
        )
    );
}

dcPage::helpBlock('templator');

echo '</body></html>';