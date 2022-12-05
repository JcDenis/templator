<?php

if (!defined('DC_CONTEXT_ADMIN')) {
    return null;
}

try {
    # Grab info
    $mod_id      = basename(__DIR__);
    $dc_min      = dcCore::app()->plugins->moduleInfo($mod_id, 'requires')[0][1];
    $new_version = dcCore::app()->plugins->moduleInfo($mod_id, 'version');

    if (version_compare(dcCore::app()->getVersion($mod_id), $new_version, '>=')) {
        return null;
    }

    # Check Dotclear version
    if (!method_exists('dcUtils', 'versionsCompare')
     || dcUtils::versionsCompare(DC_VERSION, $dc_min, '<', false)) {
        throw new Exception(sprintf(
            '%s requires Dotclear %s',
            $mod_id,
            $dc_min
        ));
    }

    # Set version
    dcCore::app()->setVersion($mod_id, $new_version);

    return true;
} catch (Exception $e) {
    dcCore::app()->error->add($e->getMessage());
}

return false;
