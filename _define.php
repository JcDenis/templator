<?php

if (!defined('DC_RC_PATH')) {
    return null;
}

$this->registerModule(
    'Templator',
    'Create and select more templates for your posts',
    'Osku and contributors',
    '1.4-dev',
    [
        'requires'    => [['core', '2.24']],
        'permissions' => dcCore::app()->auth->makePermissions([
            dcAuth::PERMISSION_CONTENT_ADMIN,
            initTemplator::PERMISSION_TEMPLATOR,
        ]),
        'type'       => 'plugin',
        'support'    => 'https://github.com/JcDenis/templator',
        'details'    => 'https://plugins.dotaddict.org/dc2/details/templator',
        'repository' => 'https://raw.githubusercontent.com/JcDenis/templator/master/dcstore.xml',
    ]
);
