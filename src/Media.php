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
declare(strict_types=1);

namespace Dotclear\Plugin\templator;

use dcMedia;

class Media extends dcMedia
{
    // limit to html files
    protected function isFileExclude(string $file): bool
    {
        return !preg_match('/\.html$/i', $file);
    }
}
