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
use Dotclear\Helper\File\File;
use Dotclear\Helper\Html\Html;
use Exception;
use initPages;

class ManageVars
{
    /** @var    ManageVars  $container  Self instance  */
    private static $container;

    /** @var    string  $name   The requested manage part name*/
    public readonly string $name;

    /** @var    string  $part   The requested manage part */
    public readonly string $part;

    /** @var    Media   $media  The limited media instance */
    public readonly Media $media;

    /** @var    array<int,File>   $items  The media items */
    public readonly array $items;

    /** @var    array<string,int>   $categories     The blog categories list */
    public readonly array $categories;

    /** @var    bool    $has_categories     Blog has categories */
    public readonly bool $has_categories;

    /** @var    array<string,string>   $sources    The templates list */
    public readonly array $sources;

    /**
     * Constructo sets properties.
     */
    public function __construct()
    {
        // manage page
        $name       = $this->getPartName(($_REQUEST['part'] ?? ''));
        $this->name = empty($name) ? __('Home') : $name;
        $this->part = empty($name) ? '' : $_REQUEST['part'];

        // Extend dcMedia to change settings to allow .html vs media_exclusion
        $this->media = new Media();
        $this->media->chdir(Templator::MY_TPL_DIR);
        // For users with only templator permission, we use sudo.
        dcCore::app()->auth?->sudo([$this->media,'getDir']);
        $dir         = $this->media->dir;
        $this->items = array_values($dir['files']);

        // categories
        $categories_combo = [];
        $has_categories   = false;

        try {
            $categories = dcCore::app()->blog?->getCategories(['post_type' => 'post']);
            if (!is_null($categories)) {
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
            }
        } catch (Exception $e) {
        }
        $this->categories     = $categories_combo;
        $this->has_categories = $has_categories;

        // sources
        $sources_combo = [
            __('Empty template') => 'empty',
            'post.html'          => 'post',
        ];

        if (dcCore::app()->plugins->moduleExists('pages')
            && dcCore::app()->auth?->check(dcCore::app()->auth->makePermissions([initPages::PERMISSION_PAGES]), dcCore::app()->blog?->id)
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
