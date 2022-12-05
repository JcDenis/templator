<?php
# -- BEGIN LICENSE BLOCK ---------------------------------------
#
# This file is part of Dotclear 2.
#
# Copyright (c) 2003-2010 Olivier Meunier & Association Dotclear
# Licensed under the GPL version 2.0 license.
# See LICENSE file or
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK -----------------------------------------
if (!defined('DC_CONTEXT_ADMIN')) { return; }

?>
<html>
<head>
	<title><?php echo __('Templator'); ?></title>
  <link rel="stylesheet" type="text/css" href="index.php?pf=tags/style.css" />
</head>

<body>
<?php echo
'<h2>'.html::escapeHTML($core->blog->name).' &rsaquo; <a href="'.$p_url.'">'.__('Supplementary templates').'</a> &rsaquo; <span class="page-title">'.__('Database informations').'</span></h2>';

$tags = $core->meta->getMetadata(array('meta_type' => 'template'));
$tags = $core->meta->computeMetaStats($tags);
$tags->sort('meta_id_lower','asc');

$last_letter = null;
$cols = array('','');
$col = 0;
while ($tags->fetch())
{
	$letter = mb_strtoupper(mb_substr($tags->meta_id,0,1));
	
	if ($last_letter != $letter) {
		if ($tags->index() >= round($tags->count()/2)) {
			$col = 1;
		}
		$cols[$col] .= '<tr class="tagLetter"><td colspan="2"><span>'.$letter.'</span></td></tr>';
	}

	$img = '<img alt="%1$s" title="%1$s" src="images/%2$s" />';
	if (array_key_exists($tags->meta_id,$core->templator->tpl)) {
		$img_status = sprintf($img,__('available template'),'check-on.png');
	} else {
		$img_status = sprintf($img,__('missing template'),'check-off.png');
	}
	
	$cols[$col] .=
	'<tr class="line">'.
		'<td class="maximal"><a href="'.$p_url.
		'&amp;m=template_posts&amp;template='.rawurlencode($tags->meta_id).'">'.$tags->meta_id.'</a> '.$img_status.'</td>'.
		'<td class="nowrap"><strong>'.$tags->count.'</strong> '.
		(($tags->count==1) ? __('entry') : __('entries')).'</td>'.
	'</tr>';
	
	$last_letter = $letter;
}

$table = '<div class="col"><table class="tags">%s</table></div>';

if ($cols[0])
{
	echo '<div class="two-cols">';
	printf($table,$cols[0]);
	if ($cols[1]) {
		printf($table,$cols[1]);
	}
	echo '</div>';
}
else
{
	echo '<p>'.__('No specific templates on this blog.').'</p>';
}
?>

</body>
</html>