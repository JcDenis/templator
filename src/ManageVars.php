<?php

declare(strict_types=1);

namespace Dotclear\Plugin\templator;

use Dotclear\App;
use Dotclear\Helper\File\File;
use Dotclear\Helper\Html\Html;
use Dotclear\Interface\Core\MediaInterface;
use Dotclear\Plugin\pages\Pages;
use Exception;

/**
 * @brief       templator vars class.
 * @ingroup     templator
 *
 * @author      Osku (author)
 * @author      Jean-Christian Denis (latest)
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class ManageVars
{
    /**
     * Self instance.
     *
     * @var     ManageVars  $container
     */
    private static $container;

    /**
     * The requested manage part name.
     *
     * @var     string  $name
     */
    public readonly string $name;

    /**
     * The requested manage part.
     *
     * @var     string  $part
     */
    public readonly string $part;

    /**
     * The limited media instance.
     *
     * @var     MediaInterface  $media
     */
    public readonly MediaInterface $media;

    /**
     * The media items.
     *
     * @var     array<int, File>    $items
     */
    public readonly array $items;

    /**
     * The blog categories list.
     *
     * @var     array<string, int>  $categories
     */
    public readonly array $categories;

    /**
     * Blog has categories.
     *
     * @var     bool    $has_categories
     */
    public readonly bool $has_categories;

    /**
     * The templates list.
     *
     * @var     array<string, string>   $sources
     */
    public readonly array $sources;

    /**
     * Constructor sets properties.
     */
    public function __construct()
    {
        // manage page
        $name       = $this->getPartName(($_REQUEST['part'] ?? ''));
        $this->name = empty($name) ? __('Home') : $name;
        $this->part = empty($name) ? '' : $_REQUEST['part'];

        // Extend dcMedia to change settings to allow .html vs media_exclusion
        $this->media = clone App::media();
        $this->media->setExcludePattern('/^(.html)$/i');
        $this->media->chdir(Templator::MY_TPL_DIR);
        // For users with only templator permission, we use sudo.
        App::auth()->sudo($this->media->getDir(...));
        $this->items = array_values($this->media->getFiles());

        // categories
        $categories_combo = [];
        $has_categories   = false;

        try {
            $categories = App::blog()->getCategories(['post_type' => 'post']);

            $l         = is_numeric($categories->f('level')) ? (int) $categories->f('level') : 1;
            $full_name = [is_string($categories->f('cat_title')) ? $categories->f('cat_title') : ''];

            while ($categories->fetch()) {
                $id    = is_numeric($categories->f('cat_id')) ? (int) $categories->f('cat_id') : 1;
                $level = is_numeric($categories->f('level')) ? (int) $categories->f('level') : 1;
                $title = is_string($categories->f('cat_title')) ? $categories->f('cat_title') : '';

                if ($level < $l) {
                    $full_name = [];
                } elseif ($level == $l) {
                    array_pop($full_name);
                }
                $full_name[] = Html::escapeHTML($title);

                $categories_combo[implode(' &rsaquo; ', $full_name)] = $id;

                $l = $level;
            }
            $has_categories = !$categories->isEmpty();
        } catch (Exception $e) {
        }
        $this->categories     = $categories_combo;
        $this->has_categories = $has_categories;

        // sources
        $sources_combo = [
            __('Empty template') => 'empty',
            'post.html'          => 'post',
        ];

        if (App::plugins()->moduleExists('pages')
            && App::auth()->check(App::auth()->makePermissions([Pages::PERMISSION_PAGES]), App::blog()->id())
        ) {
            $sources_combo['page.html'] = 'page';
        }

        if ($has_categories) {
            $sources_combo['category.html'] = 'category';
        }
        $this->sources = $sources_combo;
    }

    /**
     * Get self instance.
     *
     * @return  ManageVars  Self instance
     */
    public static function instance(): ManageVars
    {
        if (!(self::$container instanceof self)) {
            self::$container = new self();
        }

        return self::$container;
    }

    private function getPartName(string $part): string
    {
        $parts = [
            'new'     => __('New template'),
            'copy'    => __('Copy available template'),
            'copycat' => __('Copy available category template'),
            'delete'  => __('Delete available template'),
            'files'   => __('Available templates'),
            'used'    => __('Used templates'),
            'edit'    => __('Edit template'),
            'posts'   => __('Unselect template'),
        ];

        return array_key_exists($part, $parts) ? $parts[$part] : '';
    }
}
