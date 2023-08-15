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
use Dotclear\Core\Process;

class Backend extends Process
{
    public static function init(): bool
    {
        return self::status(My::checkContext(My::BACKEND));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        My::addBackendMenuItem();

        dcCore::app()->addBehaviors([
            'adminPostHeaders'      => [BackendBehaviors::class,'adminPostHeaders'],
            'adminPostFormItems'    => [BackendBehaviors::class,'adminPostFormItems'],
            'adminPageHeaders'      => [BackendBehaviors::class,'adminPostHeaders'],
            'adminPageFormItems'    => [BackendBehaviors::class,'adminPostFormItems'],
            'adminAfterPostCreate'  => [BackendBehaviors::class,'adminBeforePostUpdate'],
            'adminBeforePostUpdate' => [BackendBehaviors::class,'adminBeforePostUpdate'],
            'adminAfterPageCreate'  => [BackendBehaviors::class,'adminBeforePostUpdate'],
            'adminBeforePageUpdate' => [BackendBehaviors::class,'adminBeforePostUpdate'],
            'adminPostsActions'     => [BackendBehaviors::class,'adminPostsActions'],
            'adminPagesActions'     => [BackendBehaviors::class,'adminPostsActions'],
            'adminFiltersListsV2'   => [BackendBehaviors::class, 'adminFiltersListsV2'],
            'initWidgets'           => [Widgets::class, 'initWidgets'],
        ]);

        return true;
    }
}
