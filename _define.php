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
    '1.8',
    [
        'requires'    => [['core', '2.33']],
        'permissions' => 'My',
        'type'        => 'plugin',
        'support'     => 'https://forge.dotclear.watch/JcDenis/' . $this->id . '/issues',
        'details'     => 'https://forge.dotclear.watch/JcDenis/' . $this->id . '/src/branch/master/README.md',
        'repository'  => 'https://forge.dotclear.watch/JcDenis/' . $this->id . '/raw/branch/master/dcstore.xml',
        'date'        => '2025-02-21T10:10:10+00:00',
    ]
);
