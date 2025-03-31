<?php

declare(strict_types=1);

namespace Dotclear\Plugin\templator;

use Dotclear\App;
use Dotclear\Core\Process;

/**
 * @brief       templator backend class.
 * @ingroup     templator
 *
 * @author      Osku (author)
 * @author      Jean-Christian Denis (latest)
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class Backend extends Process
{
    public static function init(): bool
    {
        __('Templates engine');
        __('Create and select more templates for your posts');

        return self::status(My::checkContext(My::BACKEND));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        My::addBackendMenuItem();

        App::behavior()->addBehaviors([
            'adminPostHeaders'      => BackendBehaviors::adminPostHeaders(...),
            'adminPostFormItems'    => BackendBehaviors::adminPostFormItems(...),
            'adminPageHeaders'      => BackendBehaviors::adminPostHeaders(...),
            'adminPageFormItems'    => BackendBehaviors::adminPostFormItems(...),
            'adminAfterPostCreate'  => BackendBehaviors::adminBeforePostUpdate(...),
            'adminBeforePostUpdate' => BackendBehaviors::adminBeforePostUpdate(...),
            'adminAfterPageCreate'  => BackendBehaviors::adminBeforePostUpdate(...),
            'adminBeforePageUpdate' => BackendBehaviors::adminBeforePostUpdate(...),
            'adminPostsActions'     => BackendBehaviors::adminPostsActions(...),
            'adminPagesActions'     => BackendBehaviors::adminPostsActions(...),
            'adminFiltersListsV2'   => BackendBehaviors::adminFiltersListsV2(...),
            'initWidgets'           => Widgets::initWidgets(...),
        ]);

        return true;
    }
}
