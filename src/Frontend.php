<?php

declare(strict_types=1);

namespace Dotclear\Plugin\templator;

use Dotclear\App;
use Dotclear\Core\Process;
use Dotclear\Database\MetaRecord;

/**
 * @brief       templator frontend class.
 * @ingroup     templator
 *
 * @author      Osku (author)
 * @author      Jean-Christian Denis (latest)
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
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

        App::frontend()->template()->appendPath(Templator::instance()->getPath());

        App::behavior()->addBehaviors([
            'urlHandlerBeforeGetData' => function ($_): void {
                if ((App::frontend()->context()->__get('posts') instanceof MetaRecord)
                    && (array_key_exists(App::url()->type, App::postTypes()->getPostTypes()) || App::url()->type == 'pages')) {
                    $params              = [];
                    $params['meta_type'] = 'template';
                    $params['post_id']   = App::frontend()->context()->__get('posts')->f('post_id');
                    $post_meta           = App::meta()->getMetadata($params);

                    if (!$post_meta->isEmpty() && is_string($post_meta->f('meta_id')) && App::frontend()->template()->getFilePath($post_meta->f('meta_id'))) {
                        App::frontend()->context()->__set('current_tpl', $post_meta->f('meta_id'));
                    }
                }

                if (App::frontend()->context()->__get('current_tpl') == 'category.html'
                    && (App::frontend()->context()->__get('categories') instanceof MetaRecord)
                    && is_string(App::frontend()->context()->__get('categories')->f('cat_id'))
                    && preg_match('/^[0-9]{1,}/', App::frontend()->context()->__get('categories')->f('cat_id'), $cat_id)
                ) {
                    $tpl = 'category-' . $cat_id[0] . '.html';
                    if (App::frontend()->template()->getFilePath($tpl)) {
                        App::frontend()->context()->__set('current_tpl', $tpl);
                    }
                }
            },
            'initWidgets' => Widgets::initWidgets(...),
        ]);

        return true;
    }
}
