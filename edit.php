<?php
if (!defined('DC_CONTEXT_ADMIN')) {
    return null;
}

try {
    try {
        if (!empty($_GET['edit'])) {
            $name = rawurldecode($_GET['edit']);
            $file = dcCore::app()->templator->getSourceContent($name);
            $name = $file['f'];
        }

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
?>
<html>
<head>
	<title><?php echo __('Templator'); ?></title>
	<link rel="stylesheet" type="text/css" href="<?php dcPage::getPF('templator/style/style.css'); ?>" />
	<script type="text/javascript">
	//<![CDATA[
	<?php echo dcPage::jsVar('dotclear.msg.saving_document', __('Saving document...')); ?>
	<?php echo dcPage::jsVar('dotclear.msg.document_saved', __('Document saved')); ?>
	<?php echo dcPage::jsVar('dotclear.msg.error_occurred', __('An error occurred:')); ?>
	//]]>
	</script>
	<?php echo dcPage::jsLoad(dcPage::getPF('templator/js/script.js'));?>
</head>

<body>
<?php
echo
'<h2>' . html::escapeHTML(dcCore::app()->blog->name) . ' &rsaquo; <a href="' . $p_url . '">' . __('Supplementary templates') . '</a> &rsaquo; <span class="page-title">' . __('Edit the template') . '</span></h2>';

if (($file['c'] !== null)) {
    echo
    '<div id="file-templator">' .
    '<form id="file-form" action="' . $p_url . '&amp;edit=' . $name . '" method="post">' .
    '<fieldset><legend>' . __('File editor') . '</legend>' .
    '<p>' . sprintf(__('Editing file %s'), '<strong>' . $name) . '</strong></p>' .
    '<p>' . form::textarea('file_content', 72, 30, html::escapeHTML($file['c']), 'maximal', '', !$file['w']) . '</p>';

    if ($file['w']) {
        echo
        '<p><input type="submit" name="write" value="' . __('Save') . '" accesskey="s" /> ' .
        dcCore::app()->formNonce() .
        form::hidden(['file_id'], html::escapeHTML($file['f'])) .
        '</p>';
    } else {
        echo '<p>' . __('This file is not writable. Please check your files permissions.') . '</p>';
    }

    echo
    '</fieldset></form></div>';
}

?>
</body>
</html>