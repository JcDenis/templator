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
if (!defined('DC_RC_PATH') || is_null(dcCore::app()->auth)) {
    return null;
}

$this->registerModule(
    'Templates engine',
    'Create and select more templates for your posts',
    'Osku and contributors',
    '1.5',
    [
        'requires'    => [['core', '2.26']],
        'permissions' => dcCore::app()->auth->makePermissions([
            dcCore::app()->auth::PERMISSION_CONTENT_ADMIN,
            initTemplator::PERMISSION_TEMPLATOR,
        ]),
        'type'       => 'plugin',
        'support'    => 'https://github.com/JcDenis/templator',
        'details'    => 'https://plugins.dotaddict.org/dc2/details/templator',
        'repository' => 'https://raw.githubusercontent.com/JcDenis/templator/master/dcstore.xml',
    ]
);
