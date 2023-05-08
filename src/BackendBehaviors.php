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

use ArrayObject;
use dcCore;
use dcPage;
use dcPostsActions;
use Dotclear\Database\Cursor;
use Dotclear\Database\MetaRecord;
use Dotclear\Helper\Html\Html;
use Exception;

use form;

class BackendBehaviors
{
    public static function adminPostHeaders(): string
    {
        return dcPage::jsModuleLoad(My::id() . '/js/admin.js');
    }

    public static function adminPostFormItems(ArrayObject $main_items, ArrayObject $sidebar_items, ?MetaRecord $post): void
    {
        $selected = '';

        if (!is_null($post)) {
            $post_meta = dcCore::app()->meta->getMetadata(['meta_type' => 'template', 'post_id' => $post->f('post_id')]);
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
            dcCore::app()->meta->delPostMeta($post_id, 'template');
            if (!empty($_POST['post_tpl'])) {
                dcCore::app()->meta->setPostMeta($post_id, 'template', $_POST['post_tpl']);
            }
        }
    }

    public static function adminPostsActions(dcPostsActions $pa): void
    {
        $pa->addAction(
            [
                __('Appearance') => [
                    __('Select the template') => 'tpl',
                ],
            ],
            ['templatorBehaviors', 'adminPostsActionsCallback']
        );
    }

    public static function adminPostsActionsCallback(dcPostsActions $pa, ArrayObject $post): void
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
                    dcCore::app()->meta->delPostMeta($post_id, 'template');
                    if (!empty($post['post_tpl'])) {
                        dcCore::app()->meta->setPostMeta($post_id, 'template', $post['post_tpl']);
                    }
                }

                dcPage::addSuccessNotice(__('Entries template updated.'));
                $pa->redirect(true);
            } catch (Exception $e) {
                dcCore::app()->error->add($e->getMessage());
            }
        }

        $pa->beginPage(
            dcPage::breadcrumb([
                Html::escapeHTML((string) dcCore::app()->blog?->name) => '',
                $pa->getCallerTitle()                                 => $pa->getRedirection(true),
                __('Entry template')                                  => '',
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
        dcCore::app()->formNonce() .
        form::hidden(['action'], 'tpl') .
        '<input type="submit" value="' . __('Save') . '" /></p>' .
        '</form>';

        $pa->endPage();
    }

    public static function adminFiltersListsV2(ArrayObject $sorts): void
    {
        $sorts['templator'] = [
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
     * @return  array<string,string>
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
