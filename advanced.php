<?php
if (!defined('DC_CONTEXT_ADMIN')) {
    return null;
}



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
        '<td class="maximal"><a href="' . $p_url .
        '&amp;m=template_posts&amp;template=' . rawurlencode($tags->meta_id) . '">' . $tags->meta_id . '</a> ' . $img_status . '</td>' .
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
    html::escapeHTML(dcCore::app()->blog->name) => '',
    __('Additional templates') => '',
]) .
dcPage::notices() .
'<p class="top-add"><a class="button add" id="templator-control" href="' . 
dcCore::app()->adminurl->get('admin.plugin.templator', ['part' => 'new']) . 
'">' . __('New template') . '</a></p>';

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

dcPage::helpBlock('templator');

echo '</body></html>';