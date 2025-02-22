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
    '1.8.1',
    [
        'requires'    => [
            ['core', '2.33'],
            ['themeEditor', '2.0'],
        ],
        'permissions' => 'My',
        'type'        => 'plugin',
        'support'     => 'https://github.com/JcDenis/' . $this->id . '/issues',
        'details'     => 'https://github.com/JcDenis/' . $this->id . '/src/branch/master/README.md',
        'repository'  => 'https://github.com/JcDenis/' . $this->id . '/raw/branch/master/dcstore.xml',
        'date'        => '2025-02-22T10:50:18+00:00',
    ]
);
