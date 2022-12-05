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

if (!defined('DC_CONTEXT_ADMIN')) { exit; }

if (version_compare(DC_VERSION,'2.3.1','<'))
{
	$core->error->add(__('Version 2.3.1 of Dotclear at least is required for this version of Templator.'));
	$core->plugins->deactivateModule('templator');
	return false;
}

$new_version = $core->plugins->moduleInfo('templator','version');
 
$current_version = $core->getVersion('templator');
 
if (version_compare($current_version,$new_version,'>=')) {
	return;
}

$core->setVersion('templator',$new_version);
return true;
?>