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
if (!defined('DC_CONTEXT_ADMIN')) {
    return null;
}

dcCore::app()->auth->setPermissionType(initTemplator::PERMISSION_TEMPLATOR, __('manage templates'));

dcCore::app()->menu[dcAdmin::MENU_PLUGINS]->addItem(
    __('Templates engine'),
    dcCore::app()->adminurl->get('admin.plugin.templator'),
    urldecode(dcPage::getPF('templator/icon.png')),
    preg_match('/' . preg_quote(dcCore::app()->adminurl->get('admin.plugin.templator')) . '(&.*)?$/', $_SERVER['REQUEST_URI']),
    dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
        dcAuth::PERMISSION_CONTENT_ADMIN,
        initTemplator::PERMISSION_TEMPLATOR,
    ]), dcCore::app()->blog->id)
);

if (dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
    dcAuth::PERMISSION_CONTENT_ADMIN,
    initTemplator::PERMISSION_TEMPLATOR,
]), dcCore::app()->blog->id)) {
    dcCore::app()->addBehavior('adminPostHeaders', ['templatorBehaviors','adminPostHeaders']);
    dcCore::app()->addBehavior('adminPostFormItems', ['templatorBehaviors','adminPostFormItems']);
    dcCore::app()->addBehavior('adminPageHeaders', ['templatorBehaviors','adminPostHeaders']);
    dcCore::app()->addBehavior('adminPageFormItems', ['templatorBehaviors','adminPostFormItems']);

    dcCore::app()->addBehavior('adminAfterPostCreate', ['templatorBehaviors','adminBeforePostUpdate']);
    dcCore::app()->addBehavior('adminBeforePostUpdate', ['templatorBehaviors','adminBeforePostUpdate']);
    dcCore::app()->addBehavior('adminAfterPageCreate', ['templatorBehaviors','adminBeforePostUpdate']);
    dcCore::app()->addBehavior('adminBeforePageUpdate', ['templatorBehaviors','adminBeforePostUpdate']);

    dcCore::app()->addBehavior('adminPostsActions', ['templatorBehaviors','adminPostsActions']);
    dcCore::app()->addBehavior('adminPagesActions', ['templatorBehaviors','adminPostsActions']);

    dcCore::app()->addBehavior('adminFiltersListsV2', function (ArrayObject $sorts) {
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
    });
}

class templatorBehaviors
{
    public static function adminPostHeaders()
    {
        return dcPage::jsLoad(dcPage::getPF('templator/js/admin.js'));
    }

    public static function adminPostFormItems(ArrayObject $main_items, ArrayObject $sidebar_items, $post)
    {
        $selected = '';

        if ($post) {
            $post_meta = dcCore::app()->meta->getMetadata(['meta_type' => 'template', 'post_id' => $post->post_id]);
            $selected  = $post_meta->isEmpty() ? '' : $post_meta->meta_id;
        }

        $sidebar_items['options-box']['items']['templator'] = '<div id="templator">' .
            '<h5>' . __('Template') . '</h5>' .
            '<p><label for="post_tpl">' . __('Select template:') . '</label>' .
            form::combo('post_tpl', self::getTemplateCombo(), $selected) . '</p>' .
            '</div>';
    }

    public static function adminBeforePostUpdate($cur, $post_id)
    {
        $post_id = (int) $post_id;

        if (isset($_POST['post_tpl'])) {
            dcCore::app()->meta->delPostMeta($post_id, 'template');
            if (!empty($_POST['post_tpl'])) {
                dcCore::app()->meta->setPostMeta($post_id, 'template', $_POST['post_tpl']);
            }
        }
    }

    public static function adminPostsActions(dcPostsActions $pa)
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

    public static function adminPostsActionsCallback(dcPostsActions $pa, ArrayObject $post)
    {
        # No entry
        $posts_ids = $pa->getIDs();
        if (empty($posts_ids)) {
            throw new Exception(__('No entry selected'));
        }

        if (isset($post['post_tpl'])) {
            try {
                foreach ($posts_ids as $post_id) {
                    dcCore::app()->meta->delPostMeta($post_id, 'template');
                    if (!empty($post['post_tpl'])) {
                        dcCore::app()->meta->setPostMeta($post_id, 'template', $post['post_tpl']);
                    }
                }

                dcAdminNotices::addSuccessNotice(__('Entries template updated.'));
                $pa->redirect(true);
            } catch (Exception $e) {
                dcCore::app()->error->add($e->getMessage());
            }
        }

        $pa->beginPage(
            dcPage::breadcrumb([
                html::escapeHTML(dcCore::app()->blog->name) => '',
                $pa->getCallerTitle()                       => $pa->getRedirection(true),
                __('Entry template')                        => '',
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

    private static function getTemplateCombo()
    {
        $tpl = [__('No specific template') => ''];

        foreach (dcCore::app()->templator->tpl as $k => $v) {
            if (!preg_match('/^category-(.+)$/', $k) && !preg_match('/^list-(.+)$/', $k)) {
                $tpl[$k] = $k;
            }
        }

        return $tpl;
    }
}
