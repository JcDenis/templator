<?php

declare(strict_types=1);

namespace Dotclear\Plugin\templator;

use ArrayObject;
use Dotclear\App;
use Dotclear\Core\Backend\Action\ActionsPosts;
use Dotclear\Core\Backend\{
    Notices,
    Page
};
use Dotclear\Database\{
    Cursor,
    MetaRecord
};
use Dotclear\Helper\Html\Html;
use Exception;

use form;

/**
 * @brief       templator backend behaviors.
 * @ingroup     templator
 *
 * @author      Osku (author)
 * @author      Jean-Christian Denis (latest)
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class BackendBehaviors
{
    public static function adminPostHeaders(): string
    {
        return My::jsLoad('admin');
    }

    /**
     * @param   ArrayObject<string, mixed>  $main_items
     * @param   ArrayObject<string, mixed>  $sidebar_items
     */
    public static function adminPostFormItems(ArrayObject $main_items, ArrayObject $sidebar_items, ?MetaRecord $post): void
    {
        $selected = '';

        if (!is_null($post)) {
            $post_meta = App::meta()->getMetadata(['meta_type' => 'template', 'post_id' => $post->f('post_id'), 'post_type' => $post->post_type]);
            $selected  = $post_meta->isEmpty() ? '' : $post_meta->f('meta_id');
        }

        $sidebar_items['options-box']['items']['templator'] = '<div id="templator">' .
            '<h5>' . __('Template') . '</h5>' .
            '<p><label for="post_tpl">' . __('Select template:') . '</label>' .
            form::combo('post_tpl', self::getTemplateCombo(), $selected) . '</p>' .
            '</div>';
    }

    public static function adminBeforePostUpdate(Cursor $cur, string|int $post_id): void
    {
        $post_id = (int) $post_id;

        if (isset($_POST['post_tpl'])) {
            App::meta()->delPostMeta($post_id, 'template');
            if (!empty($_POST['post_tpl'])) {
                App::meta()->setPostMeta($post_id, 'template', $_POST['post_tpl']);
            }
        }
    }

    public static function adminPostsActions(ActionsPosts $pa): void
    {
        $pa->addAction(
            [
                __('Appearance') => [
                    __('Select the template') => 'tpl',
                ],
            ],
            self::adminPostsActionsCallback(...)
        );
    }

    /**
     * @param   ArrayObject<string, mixed>  $post
     */
    public static function adminPostsActionsCallback(ActionsPosts $pa, ArrayObject $post): void
    {
        # No entry
        $posts_ids = $pa->getIDs();
        if (empty($posts_ids)) {
            $pa->error(new Exception(__('No entry selected')));

            return;
        }

        if (isset($post['post_tpl']) && is_string($post['post_tpl'])) {
            try {
                foreach ($posts_ids as $post_id) {
                    App::meta()->delPostMeta($post_id, 'template');
                    if (!empty($post['post_tpl'])) {
                        App::meta()->setPostMeta($post_id, 'template', $post['post_tpl']);
                    }
                }

                Notices::addSuccessNotice(__('Entries template updated.'));
                $pa->redirect(true);
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
        }

        $pa->beginPage(
            Page::breadcrumb([
                Html::escapeHTML(App::blog()->name()) => '',
                $pa->getCallerTitle()                 => $pa->getRedirection(true),
                __('Entry template')                  => '',
            ])
        );

        echo
        '<h2 class="page-title">' . __('Select template for the selection') . '</h2>' .
        '<form action="' . $pa->getURI() . '" method="post">' .
        $pa->getCheckboxes() .
        '<p><label class="classic">' . __('Select template:') . '</label> ' .
        form::combo('post_tpl', self::getTemplateCombo()) . '</p>' .

        '<p>' .
        $pa->getHiddenFields() .
        App::nonce()->getFormNonce() .
        form::hidden(['action'], 'tpl') .
        '<input type="submit" value="' . __('Save') . '" /></p>' .
        '</form>';

        $pa->endPage();
    }

    /**
     * @param   ArrayObject<string, mixed>  $sorts
     */
    public static function adminFiltersListsV2(ArrayObject $sorts): void
    {
        $sorts[My::id()] = [
            __('Templates engine'),
            [
                __('Date')     => 'post_upddt',
                __('Title')    => 'post_title',
                __('Category') => 'cat_id',
            ],
            'post_upddt',
            'desc',
            [__('Entries per page'), 30],
        ];
    }

    /**
     * @return  array<string, string>
     */
    private static function getTemplateCombo(): array
    {
        $tpl = [__('No specific template') => ''];

        $tpls = Templator::instance()->getTpl();
        foreach ($tpls as $k => $v) {
            if (!preg_match('/^category-(.+)$/', $k) && !preg_match('/^list-(.+)$/', $k)) {
                $tpl[$k] = $k;
            }
        }

        return $tpl;
    }
}
