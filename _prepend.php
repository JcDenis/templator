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

//if (version_compare(DC_VERSION,'2.2-beta','<')) { return; }
$__autoload['dcTemplator'] = dirname(__FILE__).'/inc/class.templator.php';
$__autoload['pagerTemplator'] = dirname(__FILE__).'/inc/admin.pager.templator.php';

$core->templator = new dcTemplator($core);

$core->addBehavior('initWidgets',array('templatorWidgets','initWidgets'));

class templatorWidgets
{
	public static function initWidgets($w)
	{
		$w->create('templatorWidget',__('Templator › Rendering'),array('widgetTemplator','getDataTpl'));
		$tpl = array('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;.html' => '');
		foreach ($GLOBALS['core']->templator->tpl as $k => $v) {
			if (preg_match('/^widget-(.+)$/',$k))
			{
				$tpl = array_merge($tpl, array($k => $k));
			}
		}
		$w->templatorWidget->setting('template',__('Template:'),'','combo',$tpl);
	}
}

class widgetTemplator
{
	public static function getDataTpl($w)
	{
		if (($GLOBALS['core']->tpl->getFilePath($w->template)))
		{
			echo $GLOBALS['core']->tpl->getData($w->template);
		}
	}
}
?>