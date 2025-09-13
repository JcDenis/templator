<?php

declare(strict_types=1);

namespace Dotclear\Plugin\templator;

use Dotclear\App;
use Dotclear\Core\Backend\Filter\Filters;
use Dotclear\Core\Backend\Filter\FiltersLibrary;
use Dotclear\Core\Backend\Listing\ListingPosts;
use Dotclear\Core\Backend\Listing\Pager as corePager;
use Dotclear\Core\Backend\Notices;
use Dotclear\Core\Backend\Page;
use Dotclear\Helper\Process\TraitProcess;
use Dotclear\Helper\File\Files;
use Dotclear\Helper\Html\Form\Div;
use Dotclear\Helper\Html\Form\Form;
use Dotclear\Helper\Html\Form\Hidden;
use Dotclear\Helper\Html\Form\Input;
use Dotclear\Helper\Html\Form\Label;
use Dotclear\Helper\Html\Form\Li;
use Dotclear\Helper\Html\Form\Link;
use Dotclear\Helper\Html\Form\Note;
use Dotclear\Helper\Html\Form\None;
use Dotclear\Helper\Html\Form\Ul;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Select;
use Dotclear\Helper\Html\Form\Set;
use Dotclear\Helper\Html\Form\Submit;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Html\Form\Textarea;
use Dotclear\Helper\Html\Html;
use Dotclear\Plugin\themeEditor\My as themeEditorMy;
use Dotclear\Plugin\tags\My as tagsMy;
use Exception;

/**
 * @brief       templator manage class.
 * @ingroup     templator
 *
 * @author      Osku (author)
 * @author      Jean-Christian Denis (latest)
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class Manage
{
    use TraitProcess;

    public static function init(): bool
    {
        return self::status(My::checkContext(My::MANAGE));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        // instances
        $t = Templator::instance();
        $v = ManageVars::instance();

        /*
         * Duplicate dc template
         */
        if ('new' == $v->part && !empty($_POST['filesource'])) {
            try {
                if ('category' == $_POST['filesource']) {
                    $name = 'category-' . $_POST['filecat'] . '.html';
                } elseif (!empty($_POST['filename'])) {
                    $name = Files::tidyFileName($_POST['filename']) . '.html';
                } else {
                    throw new Exception(__('Filename is empty.'));
                }
                $t->initializeTpl($name, $_POST['filesource']);

                if (!App::error()->flag()) {
                    Notices::addSuccessNotice(__('The new template has been successfully created.'));
                    My::redirect();
                }
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
        }

        /*
         * Copy tempaltor template
         */
        if ('copy' == $v->part && !empty($_POST['filename'])) {
            try {
                $t->copypasteTpl(
                    rawurldecode($_POST['filename']) . '.html',
                    rawurldecode($_POST['file'])
                );

                if (!App::error()->flag()) {
                    Notices::addSuccessNotice(__('The template has been successfully copied.'));
                    My::redirect(['part' => 'files']);
                }
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
        }

        /*
         * Copy templator category template
         */
        if ('copycat' == $v->part && !empty($_POST['filecat'])) {
            try {
                $t->copypasteTpl(
                    'category-' . rawurldecode($_POST['filecat']) . '.html',
                    rawurldecode($_POST['file'])
                );

                if (!App::error()->flag()) {
                    Notices::addSuccessNotice(__('The template has been successfully copied.'));
                    My::redirect(['part' => 'files']);
                }
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
        }

        /*
         * Delete tempaltor template
         */
        if ('delete' == $v->part && !empty($_POST['file'])) {
            try {
                $file = rawurldecode($_POST['file']);
                $v->media->removeItem($file);
                App::meta()->delMeta($file, 'template');

                if (!App::error()->flag()) {
                    Notices::addSuccessNotice(__('The template has been successfully removed.'));
                    My::redirect(['part' => 'files']);
                }
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
        }

        return true;
    }

    public static function render(): void
    {
        if (!self::status()
            || !App::blog()->isDefined()
        ) {
            return;
        }

        // instances
        $t = Templator::instance();
        $v = ManageVars::instance();

        /*
         * Check
         */

        if (!$t->canUseRessources(true)) {
            App::error()->add(__('The plugin is unusable with your configuration. You have to change file permissions.'));
            Page::openModule(My::name());
            echo
            Page::breadcrumb([
                __('Plugins') => '',
                My::name()    => My::manageUrl(),
            ]) .
            Notices::getNotices();

            /*
             * Duplicate dotclear template
             */
        } elseif ('new' == $v->part) {
            Page::openModule(My::name());
            echo
            Page::breadcrumb([
                __('Plugins') => '',
                My::name()    => My::manageUrl(),
                $v->name      => '',
            ]) .
            Notices::getNotices() .

            (new Form('add-template'))
                ->method('post')
                ->action(My::manageUrl(['part' => 'new']))
                ->fields([
                    (new Text('h3', $v->name)),
                    (new Note())
                        ->class('form-note')
                        ->text(sprintf(__('Fields preceded by %s are mandatory.'), (new Text('span', '*'))->class('required')->render())),
                    (new Para())
                        ->items([
                            (new Label((new Text('span', '*'))->render() . __('Title:')))
                                ->class('required')
                                ->for('filesource'),
                            (new Select('filesource'))
                                ->items($v->sources),
                        ]),
                    (new Para())->items([
                        (new Label((new Text('span', '*'))->render() . __('Filename:')))
                            ->class('required')
                            ->for('filename'),
                        (new Input('filename'))
                            ->size(30)
                            ->maxlength(255),
                    ]),
                    ($v->has_categories ? 
                        (new Para())
                            ->items([
                                (new Label((new Text('span', '*'))->render() . __('Category:')))
                                    ->class('required')
                                    ->for('filecat'),
                                (new Select('filecat'))
                                    ->items($v->categories()),
                            ])
                        : (new None())
                    ),
                    (new Para())
                        ->items([
                            ... My::hiddenFields(),
                            (new Submit(['submit'], __('Save'))),
                        ]),
                ])
                ->render();

            /*
             * Copy templator template
             */
        } elseif ('copy' == $v->part && !empty($_REQUEST['file'])) {
            Page::openModule(My::name());
            echo
            Page::breadcrumb([
                __('Plugins') => '',
                My::name()    => My::manageUrl(),
                $v->name      => '',
            ]) .
            Notices::getNotices() .

            (new Form('copy-template'))
                ->method('post')
                ->action(My::manageUrl(['part' => 'copy']))
                ->fields([
                    (new Text('h3', $v->name)),
                    (new Note())
                        ->class('form-note')
                        ->text(sprintf(__('Fields preceded by %s are mandatory.'), (new Text('span', '*'))->class('required')->render())),
                    (new Para())->items([
                        (new Label((new Text('span', '*'))->render() . __('Filename:')))
                            ->class('required')
                            ->for('filename'),
                        (new Input('filename'))
                            ->size(30)
                            ->maxlength(255),
                    ]),
                    (new Note())
                        ->class('form-note')
                        ->text(sprintf(
                            __('To copy the template <strong>%s</strong>, you need to fill a new filename.'),
                            Html::escapeHTML($_REQUEST['file'])
                        )),
                    (new Para())
                        ->separator(' ')
                        ->items([
                            ... My::hiddenFields(['file' => Html::escapeHTML($_REQUEST['file'])]),
                            (new Submit(['submit'], __('Copy'))),
                            (new Link())
                                ->class('button')
                                ->href(My::manageUrl(['part' => 'files']))
                                ->text(__('Cancel')),
                        ]),
                ])
                ->render();

            /*
             * Copy templator category template
             */
        } elseif ('copycat' == $v->part && !empty($_REQUEST['file'])) {
            $category_id = (int) str_replace(['category-','.html'], '', $_REQUEST['file']);
            $cat_parents = App::blog()->getCategoryParents($category_id);
            $full_name   = '';
            while ($cat_parents->fetch()) {
                $full_name = $cat_parents->f('cat_title') . ' &rsaquo; ';
            };
            $name = $full_name . App::blog()->getCategory($category_id)->f('cat_title');

            Page::openModule(My::name());
            echo
            Page::breadcrumb([
                __('Plugins') => '',
                My::name()    => My::manageUrl(),
                $v->name      => '',
            ]) .
            Notices::getNotices() .

            (new Form('copycat-template'))
                ->method('post')
                ->action(My::manageUrl(['part' => 'copycat']))
                ->fields([
                    (new Text('h3', $v->name)),
                    (new Note())
                        ->class('form-note')
                        ->text(sprintf(__('Fields preceded by %s are mandatory.'), (new Text('span', '*'))->class('required')->render())),
                    (new Para())
                        ->items([
                            (new Label((new Text('span', '*'))->render() . __('Target category:')))
                                ->class('required')
                                ->for('filecat'),
                            (new Select('filecat'))
                                ->items($v->categories()),
                        ]),
                    (new Note())
                        ->class('form-note')
                        ->text(sprintf(
                            __('To copy the template <strong>%s</strong> (%s), you need to choose a category.'),
                            Html::escapeHTML($_REQUEST['file']),
                            $name
                        )),
                    (new Para())
                        ->separator(' ')
                        ->items([
                            ... My::hiddenFields(['file' => Html::escapeHTML($_REQUEST['file'])]),
                            (new Submit(['submit'], __('Copy'))),
                            (new Link())
                                ->class('button')
                                ->href(My::manageUrl(['part' => 'files']))
                                ->text(__('Cancel')),
                        ]),
                ])
                ->render();

            /*
             * Delete templator template
             */
        } elseif ('delete' == $v->part && !empty($_REQUEST['file'])) {
            Page::openModule(My::name());
            echo
            Page::breadcrumb([
                __('Plugins') => '',
                My::name()    => My::manageUrl(),
                $v->name      => '',
            ]) .
            Notices::getNotices() .

            (new Form('delete-template'))
                ->method('post')
                ->action(My::manageUrl(['part' => 'delete']))
                ->fields([
                    (new Text('h3', $v->name)),
                    (new Text('p', sprintf(
                        __('Are you sure you want to remove the template "%s"?'),
                        Html::escapeHTML($_GET['file'])
                    ))),
                    (new Para())
                        ->separator(' ')
                        ->items([
                            ... My::hiddenFields(['file' => Html::escapeHTML($_REQUEST['file'])]),
                            (new Submit(['submit'], __('Delete'))),
                            (new Link())
                                ->class(['button', 'delete'])
                                ->href(My::manageUrl(['part' => 'files']))
                                ->text(__('Cancel')),
                        ]),
                ])
                ->render();

            /*
             * List templator templates
             */
        } elseif ('files' == $v->part) {
            Page::openModule(My::name());
            echo
            Page::breadcrumb([
                __('Plugins') => '',
                My::name()    => My::manageUrl(),
                $v->name      => '',
            ]) .
            Notices::getNotices();

            if (count($v->items) == 0) {
                echo (new Div())
                    ->items([
                        (new Text('h3', $v->name)),
                        (new Text('p', (new Text('strong', __('No template.')))->render())),
                    ])
                    ->render();
            } else {
                // reuse "used templatro template" filter settings
                $filter = new Filters(My::id());
                $filter->add(FiltersLibrary::getPageFilter());
                $page  = is_numeric($filter->value('page')) ? (int) $filter->value('page') : 1;
                $nb    = is_numeric($filter->value('nb')) ? (int) $filter->value('nb') : 1;
                $pager = new corePager($page, count($v->items), $nb, 10);
                $items = [];

                for ($i = $pager->index_start, $j = 0; $i <= $pager->index_end; $i++, $j++) {
                    $items[] = Pager::line($v->items[$i], $j);
                }

                echo (new Div())
                    ->class('media-lsit')
                    ->items([
                        (new Text('h3', $v->name)),
                        (new text(null, $pager->getLinks())),
                        ... $items,
                        (new text(null, $pager->getLinks())),
                    ])
                    ->render();
            }

            /*
             * List Used templator template
             */
        } elseif ('used' == $v->part) {
            $tags = App::meta()->getMetadata(['meta_type' => 'template', 'post_type' => '']);
            $tags = App::meta()->computeMetaStats($tags);
            $tags->sort('meta_id_lower', 'asc');

            $last_letter = null;
            $cols        = ['',''];
            $col         = 0;

            while ($tags->fetch()) {
                $meta_id = is_string($tags->f('meta_id')) ? $tags->f('meta_id') : '';
                $count   = is_numeric($tags->f('count')) ? (int) $tags->f('count') : 1;
                $letter  = mb_strtoupper(mb_substr($meta_id, 0, 1));

                if ($last_letter != $letter) {
                    if ($tags->index() >= round($tags->count() / 2)) {
                        $col = 1;
                    }
                    $cols[$col] .= '<tr class="tagLetter"><td colspan="2"><span>' . $letter . '</span></td></tr>';
                }

                $img = '<img alt="%1$s" title="%1$s" src="images/check-%2$s.svg" class="mark mark-check-%2$s" />';
                if (array_key_exists($meta_id, $t->getTpl())) {
                    $img_status = sprintf($img, __('available template'), 'on');
                } else {
                    $img_status = sprintf($img, __('missing template'), 'off');
                }

                $cols[$col] .= '<tr class="line">' .
                    '<td class="maximal">' . $img_status . ' <a href="' .
                    My::manageUrl([
                        'part'  => 'posts',
                        'file'  => $meta_id,
                        'redir' => My::manageUrl(['part' => 'used'], '&amp;'),
                    ], '&') . '">' . $meta_id . '</a></td>' .
                    '<td class="nowrap"><strong>' . $count . '</strong> ' .
                    (($count == 1) ? __('entry') : __('entries')) . '</td>' .
                '</tr>';

                $last_letter = $letter;
            }

            $table = '<div class="col"><table class="tags">%s</table></div>';

            Page::openModule(
                My::name(),
                tagsMy::cssLoad('/style.css')
            );
            echo
            Page::breadcrumb([
                __('Plugins') => '',
                My::name()    => My::manageUrl(),
                $v->name      => '',
            ]) .
            Notices::getNotices() .
            '<h3>' . $v->name . '</h3>';

            if ($cols[0]) {
                echo '<div class="two-cols">';
                printf($table, $cols[0]);
                if ($cols[1]) {
                    printf($table, $cols[1]);
                }
                echo '</div>';
            } else {
                echo '<p>' . __('No specific templates on this blog.') . '</p>';
            }

            /*
             * Edit emplator template
             */
        } elseif ('edit' == $v->part && !empty($_REQUEST['file'])) {
            $file = ['c' => '', 'w' => false, 'f' => ''];
            $name = '';

            try {
                try {
                    $name = rawurldecode($_REQUEST['file']);
                    $file = $t->getSourceContent($name);
                    $name = $file['f'];

                    if (preg_match('/^category-(.+).html$/', $name, $cat_id)) {
                        $category    = App::blog()->getCategory((int) $cat_id[1]);
                        $full_name   = '';
                        $cat_parents = App::blog()->getCategoryParents((int) $cat_id[1]);
                        while ($cat_parents->fetch()) {
                            $full_name = $cat_parents->f('cat_title') . ' &rsaquo; ';
                        };
                        $name .= '</strong> (' . $full_name . $category->f('cat_title') . ')<strong>';
                    }
                } catch (Exception $e) {
                    $file['c'] = null;

                    throw $e;
                }
                # Write file
                if (!empty($_POST['write'])) {
                    $file['c'] = $_POST['file_content'] ?? null;
                    $t->writeTpl($file['f'], $file['c']);
                }
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }

            $ict = App::auth()->prefs()->get('interface')->get('colorsyntax_theme');

            Page::openModule(
                My::name(),
                (
                    App::auth()->prefs()->get('interface')->get('colorsyntax') ?
                    Page::jsJson('dotclear_colorsyntax', ['colorsyntax' => App::auth()->prefs()->get('interface')->get('colorsyntax')]) : ''
                ) .
                Page::jsJson('theme_editor_msg', [
                    'saving_document'    => __('Saving document...'),
                    'document_saved'     => __('Document saved'),
                    'error_occurred'     => __('An error occurred:'),
                    'confirm_reset_file' => __('Are you sure you want to reset this file?'),
                ]) .
                themeEditorMy::jsLoad('script.js') .
                Page::jsConfirmClose('file-form') .
                (
                    App::auth()->prefs()->get('interface')->get('colorsyntax') ?
                    Page::jsLoadCodeMirror(is_string($ict) ? $ict : '') : ''
                ) .
                themeEditorMy::cssLoad('/style.css')
            );

            echo
            Page::breadcrumb([
                __('Plugins') => '',
                My::name()    => My::manageUrl(),
                $v->name      => '',
            ]) .
            Notices::getNotices();

            $more = $file['w'] ?
                (new Para())
                    ->separator(' ')
                    ->items([
                        (new Submit('write'))
                            ->value(__('Save'))
                            ->accesskey('s'),
                        (new Link())
                            ->class('button')
                            ->href(My::manageUrl(['part' => 'files']))
                            ->text(__('Cancel')),
                        ... My::hiddenFields(['file_id' => Html::escapeHTML($file['f'])]),
                    ])
                :
                new Text('p', __('This file is not writable. Please check your files permissions.'));

            if (($file['c'] !== null)) {
                echo (new Form('file-form'))
                    ->method('post')
                    ->action(My::manageUrl(['part' => 'edit', 'file' => $name]))
                    ->fields([
                        (new Div())
                            ->items([
                                (new Para())
                                    ->items([
                                        (new Textarea('file_content'))
                                            ->class('maximal')
                                            ->readonly(!$file['w'])
                                            ->cols(72)
                                            ->rows(25)
                                            ->label((new Label(sprintf(__('Editing file %s'), '<strong>' . $name), Label::OL_TF)))
                                            ->value(Html::escapeHTML($file['c'])),
                                    ]),
                                    $more,
                            ]),
                    ])
                    ->render();

                if (App::auth()->prefs()->get('interface')->get('colorsyntax')) {
                    $ict = App::auth()->prefs()->get('interface')->get('colorsyntax_theme');
                    echo
                    Page::jsJson('theme_editor_mode', ['mode' => 'html']) .
                    themeEditorMy::jsLoad('mode.js') .
                    Page::jsRunCodeMirror('editor', 'file_content', 'dotclear', is_string($ict) ? $ict : '');
                }
            }

            /*
             * Edit posts options linked to a template
             */
        } elseif ('posts' == $v->part && (!empty($_REQUEST['file']) || $_REQUEST['file'] == '0')) {
            $file  = $_REQUEST['file'];
            $redir = $_REQUEST['redir'] ?? My::manageUrl(['part' => 'used']);

            # Unselect the template
            if (!empty($_POST['action']) && 'unselecttpl' == $_POST['action'] && App::auth()->check(App::auth()->makePermissions([
                App::auth()::PERMISSION_PUBLISH,
                App::auth()::PERMISSION_CONTENT_ADMIN,
            ]), App::blog()->id())) {
                try {
                    App::meta()->delMeta($file, 'template');
                    My::redirect(['part' => 'posts', 'file' => $file]);
                } catch (Exception $e) {
                    App::error()->add($e->getMessage());
                }
            }

            $filter = new Filters('templator');
            $filter->add(FiltersLibrary::getPageFilter());
            $filter->add('part', 'posts');
            $filter->add('file', $file);
            //$filter->add('post_type', '');

            $params               = $filter->params();
            $params['no_content'] = true;
            $params['meta_id']    = $file;
            $params['meta_type']  = 'template';
            $params['post_type']  = '';

            # Get posts
            try {
                $posts     = App::meta()->getPostsByMeta($params);
                $counter   = App::meta()->getPostsByMeta($params, true)->f(0);
                $counter   = is_numeric($counter) ? (int) $counter : 0;
                $post_list = new ListingPosts($posts, $counter);
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }

            Page::openModule(
                My::name(),
                Page::jsFilterControl($filter->show()) .
                My::jsLoad('posts') .
                $filter->js(My::manageUrl(['part' => 'posts', 'file' => $file]))
            );

            echo
            Page::breadcrumb([
                __('Plugins') => '',
                My::name()    => My::manageUrl(),
                $v->name      => '',
            ]) .
            Notices::getNotices();

            echo (new Set())
                ->items([
                    (new Text('h3', sprintf(__('Unselect template "%s"'), (new Text('strong', $file))->render()))),
                    (new Para())
                        ->items([
                            (new Link())
                                ->class('back')
                                ->href($redir)
                                ->text(__('Back')),
                        ]),
                ])
                ->render();

            if (!App::error()->flag() && isset($posts) && isset($post_list)) {
                if ($posts->isEmpty() && !$filter->show()) {
                    echo (new Text('p', __('There is no entries')))->render();
                } else {
                    $page = is_numeric($filter->value('page')) ? (int) $filter->value('page') : 1;
                    $nb   = is_numeric($filter->value('nb')) ? (int) $filter->value('nb') : 0;
                    $filter->display(
                        'admin.plugin.' . My::id(),
                        (new Set())
                            ->items([
                                new Hidden('p', 'templator'),
                                new Hidden('part', 'posts'),
                                new Hidden('file', $file),
                            ])
                            ->render()
                    );
                    # Show posts
                    $post_list->display(
                        $page,
                        $nb,
                        (new Form('form-entries'))
                            ->method('post')
                            ->action(My::manageUrl())
                            ->fields([
                                new Text('', '%s'),
                                (new Div())
                                    ->class('two-cols')
                                    ->items([
                                        (new Para())
                                            ->class('col checkboxes-helpers'),
                                        (new Para())
                                            ->class('col right')
                                            ->separator('&nbsp;')
                                            ->items([
                                                (new Submit('do-action'))
                                                    ->value(__('Unselect template for selected entries')),
                                                new Hidden('action', 'unselecttpl'),
                                                new Hidden('redir', $redir),
                                                ... My::hiddenFields($filter->values()),
                                            ]),
                                    ])
                            ])
                            ->render(),
                        $filter->show()
                    );
                }
            }

            /*
             * Default page
             */
        } else {

            $links = [
                 My::manageUrl(['part' => 'files']) => __('Available templates'),
                 My::manageUrl(['part' => 'used']) => __('Used templates'),
                 My::manageUrl(['part' => 'new']) => __('New template'),
            ];

            $lines = [];
            foreach($links as $link => $title) {
                $lines[] = (new Li())
                    ->items([
                        (new Link())
                            ->href($link)
                            ->text($title)
                    ]);
            }

            Page::openModule(My::name());
            echo
            Page::breadcrumb([
                __('Plugins') => '',
                My::name()    => '',
            ]) .
            Notices::getNotices() .
            (new Div())
                ->items([
                    (new Text('h3', __('Create and select more templates for your posts'))),
                    (new Ul())->class('nice')->items($lines),
                ])
                ->render();
        }

        Page::helpBlock('templator');

        Page::closeModule();
    }
}
