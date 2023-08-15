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
use Dotclear\Database\MetaRecord;

/**
 * Frontend prepend.
 */
class Frontend extends Process
{
    public static function init(): bool
    {
        return self::status(My::checkContext(My::FRONTEND));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        dcCore::app()->tpl->setPath(
            dcCore::app()->tpl->getPath(),
            Templator::instance()->getPath()
        );

        dcCore::app()->addBehaviors([
            'urlHandlerBeforeGetData' => function ($_): void {
                if (is_null(dcCore::app()->ctx)) {
                    return;
                }

                if ((dcCore::app()->ctx->__get('posts') instanceof MetaRecord)
                    && (array_key_exists(dcCore::app()->url->type, dcCore::app()->getPostTypes()) || dcCore::app()->url->type == 'pages')) {
                    $params              = [];
                    $params['meta_type'] = 'template';
                    $params['post_id']   = dcCore::app()->ctx->__get('posts')->f('post_id');
                    $post_meta           = dcCore::app()->meta->getMetadata($params);

                    if (!$post_meta->isEmpty() && is_string($post_meta->f('meta_id')) && dcCore::app()->tpl->getFilePath($post_meta->f('meta_id'))) {
                        dcCore::app()->ctx->__set('current_tpl', $post_meta->f('meta_id'));
                    }
                }

                if (dcCore::app()->ctx->__get('current_tpl') == 'category.html'
                    && (dcCore::app()->ctx->__get('categories') instanceof MetaRecord)
                    && is_string(dcCore::app()->ctx->__get('categories')->f('cat_id'))
                    && preg_match('/^[0-9]{1,}/', dcCore::app()->ctx->__get('categories')->f('cat_id'), $cat_id)
                ) {
                    $tpl = 'category-' . $cat_id[0] . '.html';
                    if (dcCore::app()->tpl->getFilePath($tpl)) {
                        dcCore::app()->ctx->__set('current_tpl', $tpl);
                    }
                }
            },
            'initWidgets' => [Widgets::class, 'initWidgets'],
        ]);

        return true;
    }
}
