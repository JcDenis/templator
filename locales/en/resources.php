<?php
/**
 * @file
 * @brief       The plugin templator resources
 * @ingroup     templator
 *
 * @author      Osku (author)
 * @author      Jean-Christian Denis (latest)
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
\Dotclear\App::backend()->resources()->set('help', 'templator-home', __DIR__ . '/help/home.html');
\Dotclear\App::backend()->resources()->set('help', 'templator-files', __DIR__ . '/help/files.html');
\Dotclear\App::backend()->resources()->set('help', 'templator-used', __DIR__ . '/help/used.html');
\Dotclear\App::backend()->resources()->set('help', 'templator-new', __DIR__ . '/help/new.html');
\Dotclear\App::backend()->resources()->set('help', 'templator-edit', __DIR__ . '/help/edit.html');
