<?php
/**
 * @file
 * @brief       The plugin templator definition
 * @ingroup     templator
 *
 * @defgroup    templator Plugin templator.
 *
 * Create and select more templates for your posts.
 *
 * @author      Osku (author)
 * @author      Jean-Christian Denis (latest)
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
declare(strict_types=1);

$this->registerModule(
    'Templates engine',
    'Create and select more templates for your posts',
    'Osku and contributors',
    '1.8.3',
    [
        'requires'    => [
            ['core', '2.33'],
            ['tags', '2.0'],
            ['themeEditor', '2.0'],
        ],
        'permissions' => 'My',
        'type'        => 'plugin',
        'support'     => 'https://github.com/JcDenis/' . $this->id . '/issues',
        'details'     => 'https://github.com/JcDenis/' . $this->id . '/',
        'repository'  => 'https://raw.githubusercontent.com/JcDenis/' . $this->id . '/master/dcstore.xml',
        'date'        => '2025-03-02T19:47:58+00:00',
    ]
);
