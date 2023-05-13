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

use dcAdmin;
use dcCore;
use dcMenu;
use dcNsProcess;
use dcPage;

class Backend extends dcNsProcess
{
    public static function init(): bool
    {
        static::$init == defined('DC_CONTEXT_ADMIN')
            && !is_null(dcCore::app()->blog) && !is_null(dcCore::app()->auth)
            && dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
                My::PERMISSION_TEMPLATOR,
                dcCore::app()->auth::PERMISSION_CONTENT_ADMIN,
            ]), dcCore::app()->blog->id);

        return static::$init;
    }

    public static function process(): bool
    {
        if (!static::$init) {
            return false;
        }

        // nullsafe
        if (is_null(dcCore::app()->auth) || is_null(dcCore::app()->blog) || is_null(dcCore::app()->adminurl)) {
            return false;
        }

        //backend sidebar menu icon
        if ((dcCore::app()->menu[dcAdmin::MENU_PLUGINS] instanceof dcMenu)) {
            dcCore::app()->menu[dcAdmin::MENU_PLUGINS]->addItem(
                My::name(),
                dcCore::app()->adminurl->get('admin.plugin.' . My::id()),
                urldecode(dcPage::getPF(My::id() . '/icon.svg')),
                preg_match('/' . preg_quote(dcCore::app()->adminurl->get('admin.plugin.' . My::id())) . '(&.*)?$/', $_SERVER['REQUEST_URI']),
                dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
                    dcCore::app()->auth::PERMISSION_CONTENT_ADMIN,
                    My::PERMISSION_TEMPLATOR,
                ]), dcCore::app()->blog->id)
            );
        }

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
