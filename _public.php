<?php

if (!defined('DC_RC_PATH')) {
    return null;
}

dcCore::app()->tpl->setPath(dcCore::app()->tpl->getPath(), dcCore::app()->templator->path);
dcCore::app()->addBehavior('urlHandlerBeforeGetData', ['publicTemplatorBehaviors','BeforeGetData']);

class publicTemplatorBehaviors
{
    public static function BeforeGetData($_)
    {
        if (array_key_exists(dcCore::app()->url->type, dcCore::app()->getPostTypes()) || dcCore::app()->url->type == 'pages') {
            $params              = [];
            $params['meta_type'] = 'template';
            $params['post_id']   = dcCore::app()->ctx->posts->post_id;
            $post_meta           = dcCore::app()->meta->getMetadata($params);

            if (!$post_meta->isEmpty() && dcCore::app()->tpl->getFilePath($post_meta->meta_id)) {
                dcCore::app()->ctx->current_tpl = $post_meta->meta_id;
            }
        }

        if (dcCore::app()->ctx->current_tpl == 'category.html' && preg_match('/^[0-9]{1,}/', dcCore::app()->ctx->categories->cat_id, $cat_id)) {
            $tpl = 'category-' . $cat_id[0] . '.html';
            if (dcCore::app()->tpl->getFilePath($tpl)) {
                dcCore::app()->ctx->current_tpl = $tpl;
            }
        }
    }
}
