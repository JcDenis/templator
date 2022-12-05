<?php
if (!defined('DC_CONTEXT_ADMIN')) {
    return null;
}

dcPage::check(dcCore::app()->auth->makePermissions([
    dcAuth::PERMISSION_CONTENT_ADMIN,
    initTemplator::PERMISSION_TEMPLATOR,
]));

$p_url = dcCore::app()->admin->getPageURL();

if ((!empty($_REQUEST['m'])) && ($_REQUEST['m'] = 'template_posts')) {
    require dirname(__FILE__) . '/' . $_REQUEST['m'] . '.php';

    return;
}
if (!empty($_REQUEST['edit'])) {
    require dirname(__FILE__) . '/edit.php';

    return;
}
if (!empty($_REQUEST['database']) && $_REQUEST['database'] = 'on') {
    require dirname(__FILE__) . '/advanced.php';

    return;
}

$file_default   = $file = ['c' => null, 'w' => false, 'type' => null, 'f' => null, 'default_file' => false];
$page           = !empty($_GET['page']) ? $_GET['page'] : 1;
$nb_per_page    = 20;
$msg            = '';
$remove_confirm = false;

// Load infos.
$ressources = dcCore::app()->templator->canUseRessources(true);
$files      = dcCore::app()->templator->tpl;

// Media
$media = new dcMedia();
$media->chdir(dcCore::app()->templator->template_dir_name);
// For users with only templator permission, we use sudo.
dcCore::app()->auth->sudo([$media,'getDir']);
$dir   = & $media->dir;
//if files did not appear in this list, check blog->settings->media_exclusion
$items = array_values($dir['files']);

// Categories
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
} catch (Exception $e) {
}

$hasCategories = ($categories->isEmpty()) ? false : true;

$combo_source = [
    'post.html' => 'post',
];

if (dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([dcPages::PERMISSION_PAGES]), dcCore::app()->blog->id) && dcCore::app()->plugins->moduleExists('pages')) {
    $combo_source['page.html'] = 'page';
}

if (!$categories->isEmpty()) {
    $combo_source['category.html'] = 'category';
}

$combo_source[' &mdash; '] = 'empty';

$add_template = $copy_confirm = $copycat_confirm = false;

if (!$ressources) {
    dcCore::app()->error->add(__('The plugin is unusable with your configuration. You have to change file permissions.'));
}

if (!empty($_POST['filesource'])) {
    try {
        $source = $_POST['filesource'];
        if (empty($_POST['filename']) && $source != 'category') {
            throw new Exception(__('Filename is empty.'));
        }
        $name = files::tidyFileName($_POST['filename']) . '.html';
        if ($source == 'category') {
            $name = 'category-' . $_POST['filecat'] . '.html';
        }
        dcCore::app()->templator->initializeTpl($name, $source);

        if (!dcCore::app()->error->flag()) {
            dcAdminNotices::addSuccessNotice(__('The new template has been successfully created.'));
            dcCore::app()->adminurl->redirect('admin.plugin.templator');
        }
    } catch (Exception $e) {
        dcCore::app()->error->add($e->getMessage());
    }
}

if (!empty($_POST['rmyes']) && !empty($_POST['remove'])) {
    try {
        $file = rawurldecode($_POST['remove']);
        $media->removeItem($file);
        dcCore::app()->meta->delMeta($file, 'template');

        if (!dcCore::app()->error->flag()) {
            dcAdminNotices::addSuccessNotice(__('The template has been successfully removed.'));
            dcCore::app()->adminurl->redirect('admin.plugin.templator');
        }
    } catch (Exception $e) {
        dcCore::app()->error->add($e->getMessage());
    }
}

if (!empty($_POST['cpyes']) && !empty($_POST['copy']) && !empty($_POST['newfile'])) {
    try {
        $file    = rawurldecode($_POST['copy']);
        $newfile = rawurldecode($_POST['newfile']) . '.html';
        dcCore::app()->templator->copypasteTpl($newfile, $file);

        if (!dcCore::app()->error->flag()) {
            dcAdminNotices::addSuccessNotice(__('The template has been successfully copied.'));
            dcCore::app()->adminurl->redirect('admin.plugin.templator');
        }
    } catch (Exception $e) {
        dcCore::app()->error->add($e->getMessage());
    }
}

if (!empty($_POST['cpyes']) && !empty($_POST['copycat']) && !empty($_POST['copcat'])) {
    try {
        $file    = rawurldecode($_POST['copycat']);
        $newfile = 'category-' . rawurldecode($_POST['copcat']) . '.html';
        dcCore::app()->templator->copypasteTpl($newfile, $file);

        if (!dcCore::app()->error->flag()) {
            dcAdminNotices::addSuccessNotice(__('The template has been successfully copied.'));
            dcCore::app()->adminurl->redirect('admin.plugin.templator');
        }
    } catch (Exception $e) {
        dcCore::app()->error->add($e->getMessage());
    }
}

if (!empty($_GET['remove'])) {
    $remove_confirm = true;
}
if (!empty($_GET['copy'])) {
    $copy_confirm = true;
}
if (!empty($_GET['copycat'])) {
    $copycat_confirm = true;
}

echo
'<html><head><title>' . __('Templator') . '</title>' .
dcPage::cssLoad(dcPage::getPF('templator/style/style.css')) .
(!$add_template ? dcPage::jsLoad(dcPage::getPF('templator/js/form.js')) : '') .
dcPage::jsLoad(dcPage::getPF('templator/js/script.js')) .
'</head><body>' .
dcPage::breadcrumb([
    html::escapeHTML(dcCore::app()->blog->name) => '',
    __('Additional templates') => '',
]) .
dcPage::notices();

if ($remove_confirm) {
    echo
    '<form action="' . $p_url . '" method="post">' .
    '<p>' . sprintf(
        __('Are you sure you want to remove the template "%s"?'),
        html::escapeHTML($_GET['remove'])
    ) . '</p>' .
    '<p><input type="submit" class="delete" value="' . __('Cancel') . '" /> ' .
    ' &nbsp; <input type="submit" name="rmyes" value="' . __('Yes') . '" />' .
    dcCore::app()->formNonce() .
    form::hidden('remove', html::escapeHTML($_GET['remove'])) . '</p>' .
    '</form>';
}

if ($copy_confirm) {
    echo
    '<form action="' . $p_url . '" method="post">' .
    '<p>' . sprintf(
        __('To copy the template <strong>%s</strong>, you need to fill a new filename.'),
        html::escapeHTML($_GET['copy'])
    ) . '</p>' .
    '<p><label for="filename" class="classic required"><abbr title="' . __('Required field') . '">*</abbr> ' . __('New filename:') . ' ' .
form::field('newfile', 25, 255) . '</label><code>' . html::escapeHTML('.html') . '</code>&nbsp; ' .
    '<input type="submit" name="cpyes" value="' . __('Copy') . '" /> &nbsp;<input class="delete" type="submit" value="' . __('Cancel') . '" />' .
    dcCore::app()->formNonce() .
    form::hidden('copy', html::escapeHTML($_GET['copy'])) . '</p>' .
    '</form>';
}

if ($copycat_confirm) {
    $category_id = str_replace('category-', '', $_GET['copycat']);
    $category_id = str_replace('.html', '', $category_id);
    $cat_parents = dcCore::app()->blog->getCategoryParents($category_id);
    $full_name   = '';
    while ($cat_parents->fetch()) {
        $full_name = $cat_parents->cat_title . ' &rsaquo; ';
    };
    $name = $full_name . dcCore::app()->blog->getCategory($category_id)->cat_title;
    echo
    '<form action="' . $p_url . '" method="post">' .
    '<p>' . sprintf(
        __('To copy the template <strong>%s</strong> (%s), you need to choose a category.'),
        html::escapeHTML($_GET['copycat']),
        $name
    ) . '</p>' .
    '<p class="field"><label for="copcat" class="classic required"><abbr title="' . __('Required field') . '">*</abbr> ' . __('Target category:') .
    form::combo('copcat', $categories_combo, '') . '</label></p>' .
    '<input type="submit" name="cpyes" value="' . __('Copy') . '" /> &nbsp;<input class="delete" type="submit" value="' . __('Cancel') . '" />' .
    $dcCore::app()->formNonce() .
    form::hidden('copycat', html::escapeHTML($_GET['copycat'])) .
    '</form>';
}

if (!$add_template) {
    echo '<p class="top-add"><a class="button add" id="templator-control" href="#">' .
    __('New template') . '</a></p>';
}

echo
'<form action="' . $p_url . '" method="post" id="add-template">' .
'<h3>' . __('New template') . '</h3>' .
'<p class="field"><label for="filesource" class="required"><abbr title="' . __('Required field') . '">*</abbr> ' . __('Template source:') . ' ' .
form::combo('filesource', $combo_source) . '</label></p>' .
'<p class="field two-cols"><label for="filename" class="classic required"><abbr title="' . __('Required field') . '">*</abbr> ' . __('Filename:') . ' ' .
form::field('filename', 25, 255) . '</label></p></br />
<p class="form-note warn">' . __('Extension <strong><code>.html</code></strong> is automatically added to filename') . '</p>
';

if ($hasCategories) {
    echo '<p class="field"><label for="filecat" class="required"><abbr title="' . __('Required field') . '">*</abbr>' . __('Category:') .
    form::combo('filecat', $categories_combo, '') . '</label></p>';
}

echo
'<p>' . form::hidden(['p'], 'templator') .
dcCore::app()->formNonce() .
'<input type="submit" name="add_message" value="' . __('Create') . '" /></p>' .
'</form>';

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

echo
    '<p class="clear">
		<a class="button" 
			href="' . $p_url . '&amp;database=on" 
			title="' . __('Display templates used for entries in base') . '"
		>' .
        __('Display templates used for entries in base') . '
		</a>
	</p>';
?>
</body>
</html>