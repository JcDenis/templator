<?php

declare(strict_types=1);

namespace Dotclear\Plugin\templator;

use Dotclear\App;
use Dotclear\Module\MyPlugin;

/**
 * @brief       templator My helper.
 * @ingroup     templator
 *
 * @author      Osku (author)
 * @author      Jean-Christian Denis (latest)
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class My extends MyPlugin
{
    /**
     * The module permission.
     *
     * @var     string  PERMISSION_TEMPLATOR
     */
    public const PERMISSION_TEMPLATOR = 'templator';

    public static function checkCustomContext(int $context): ?bool
    {
        return match ($context) {
            self::BACKEND, self::MENU, self::MANAGE => App::task()->checkContext('BACKEND')
                && App::auth()->check(App::auth()->makePermissions([
                    self::PERMISSION_TEMPLATOR,
                    App::auth()::PERMISSION_CONTENT_ADMIN,
                ]), App::blog()->id()),

            default => null,
        };
    }
}
