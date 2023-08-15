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

use dcCore;
use Dotclear\Module\MyPlugin;

/**
 * This module definitions.
 */
class My extends MyPlugin
{
    /** @var    string  This module permission */
    public const PERMISSION_TEMPLATOR = 'templator';

    public static function checkCustomContext(int $context): ?bool
    {
        if (in_array($context, [My::BACKEND, My::MENU, My::MANAGE])) {
            return defined('DC_CONTEXT_ADMIN')
                && !is_null(dcCore::app()->blog)
                && dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
                    My::PERMISSION_TEMPLATOR,
                    dcCore::app()->auth::PERMISSION_CONTENT_ADMIN,
                ]), dcCore::app()->blog->id);
        }

        return null;
    }
}
