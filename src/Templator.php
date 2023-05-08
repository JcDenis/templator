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
use Dotclear\Helper\File\Files;
use Dotclear\Helper\File\Path;
use Dotclear\Helper\Html\Html;
use Exception;

/**
 * Templator main class.
 */
class Templator
{
    /** @var    string  This plugin folder for templates */
    public const MY_TPL_DIR = 'other-templates';

    /** @var    string  The dotclear folder for templates */
    public const DC_TPL_DIR = 'default-templates';

    /** @var    string  The themes folder for templates */
    public const THEME_TPL_DIR = 'tpl';

    /** @var    string  The default tplset */
    public const DEFAULT_TPLSET = 'dotty';

    /** @var    string  The default post template */
    public const DEFAULT_TPL_POST = 'post.html';

    /** @var    string   The default page template */
    public const DEFAULT_TPL_PAGE = 'page.html';

    /** @var    string  The default category tempalte */
    public const DEFAULT_TPL_CATEGORY = 'category.html';

    /** @var    Templator    Self instance */
    private static $instance;

    /** @var    string  $path   This plugin templates directory path */
    private string $path = '';

    /** @var    array<string,string>    The known templates files */
    private array $tpl = [];

    private string $file_tpl_post     = '';
    private string $file_tpl_page     = '';
    private string $file_tpl_category = '';

    private string $user_path_theme   = '';
    private string $user_tpl_post     = '';
    private string $user_tpl_category = '';
    private string $user_tpl_page     = '';

    /**
     * Constructor sets properties.
     */
    public function __construct()
    {
        if (is_null(dcCore::app()->blog)) {
            throw new Exception(__('Blog is not set'));
        }

        $page_root = dcCore::app()->plugins->getDefine('pages')->get('root');

        // Initial templates
        $this->path              = implode(DIRECTORY_SEPARATOR, [dcCore::app()->blog->public_path, self::MY_TPL_DIR]);
        $this->file_tpl_post     = implode(DIRECTORY_SEPARATOR, [DC_ROOT, 'inc', 'public', self::DC_TPL_DIR, self::DEFAULT_TPLSET, self::DEFAULT_TPL_POST]);
        $this->file_tpl_category = implode(DIRECTORY_SEPARATOR, [DC_ROOT, 'inc', 'public', self::DC_TPL_DIR, self::DEFAULT_TPLSET, self::DEFAULT_TPL_CATEGORY]);
        $this->file_tpl_page     = Path::real(implode(DIRECTORY_SEPARATOR, [$page_root, self::DC_TPL_DIR, self::DEFAULT_TPLSET, self::DEFAULT_TPL_PAGE])) ?: '';

        // user templates
        $this->user_path_theme   = dcCore::app()->blog->themes_path . DIRECTORY_SEPARATOR . dcCore::app()->blog->settings->get('system')->get('theme');
        $this->user_tpl_post     = Path::real(implode(DIRECTORY_SEPARATOR, [$this->user_path_theme, self::THEME_TPL_DIR, self::DEFAULT_TPL_POST])) ?: '';
        $this->user_tpl_category = Path::real(implode(DIRECTORY_SEPARATOR, [$this->user_path_theme, self::THEME_TPL_DIR, self::DEFAULT_TPL_CATEGORY])) ?: '';
        $this->user_tpl_page     = Path::real(implode(DIRECTORY_SEPARATOR, [$this->user_path_theme, self::THEME_TPL_DIR, self::DEFAULT_TPL_PAGE])) ?: '';

        $this->findTemplates();
    }

    public static function instance(): Templator
    {
        if (!(self::$instance instanceof Templator)) {
            self::$instance = new Templator();
        }

        return self::$instance;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @return array<string,string>
     */
    public function getTpl(): array
    {
        return $this->tpl;
    }

    /**
     *
     */
    public function canUseRessources(bool $create = false): bool
    {
        if (!is_dir($this->path)) {
            if ($create) {
                Files::makeDir($this->path);
            }

            return true;
        }

        if (!is_writable($this->path)) {
            return false;
        }

        if (!is_file($this->path . '/.htaccess')) {
            try {
                file_put_contents($this->path . '/.htaccess', "Deny from all\n");
            } catch (Exception $e) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return  array{c: string, w: bool, f: string}
     */
    public function getSourceContent(string $f): array
    {
        $source = $this->tpl;

        if (!isset($source[$f])) {
            throw new Exception(__('File does not exist.'));
        }

        $F = $source[$f];
        if (!is_readable($F)) {
            throw new Exception(sprintf(__('File %s is not readable'), $f));
        }

        return [
            'c' => (string) file_get_contents($source[$f]),
            'w' => !empty($this->getDestinationFile($f)),
            'f' => $f,
        ];
    }

    /**
     *
     */
    public function filesList(string $item = '%1$s'): string
    {
        $files = $this->tpl;

        if (empty($files)) {
            return '<p>' . __('No file') . '</p>';
        }

        $list = '';
        foreach ($files as $k => $v) {
            $li = sprintf('<li>%s</li>', $item);

            $list .= sprintf($li, $k, Html::escapeHTML($k));
        }

        return sprintf('<ul>%s</ul>', $list);
    }

    /**
     *
     */
    public function initializeTpl(string $name, string $type): void
    {
        if ($type == 'category') {
            if ($this->user_tpl_category) {
                $base = $this->user_tpl_category;
            } else {
                $base = $this->file_tpl_category;
            }
        } elseif ($type == 'page') {
            if ($this->user_tpl_page) {
                $base = $this->user_tpl_page;
            } else {
                $base = $this->file_tpl_page;
            }
        } else {
            if ($this->user_tpl_post) {
                $base = $this->user_tpl_post;
            } else {
                $base = $this->file_tpl_post;
            }
        }

        $source = [
            'c' => (string) file_get_contents($base),
            'w' => !empty($this->getDestinationFile($name)),
        ];

        if (!$source['w']) {
            throw new Exception(sprintf(__('File %s is not readable'), $name));
        }

        if ($type == 'empty') {
            $source['c'] = '';
        }

        try {
            $dest = $this->getDestinationFile($name);

            if ($dest == false) {
                throw new Exception();
            }

            $content = $source['c'];

            if (!is_dir(dirname($dest))) {
                Files::makeDir(dirname($dest));
            }

            $fp = @fopen($dest, 'wb');
            if (!$fp) {
                throw new Exception('tocatch');
            }

            $content = (string) preg_replace('/(\r?\n)/m', "\n", $content);
            $content = (string) preg_replace('/\r/m', "\n", $content);

            fwrite($fp, $content);
            fclose($fp);
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     *
     */
    public function copypasteTpl(string $name, string $source): void
    {
        if ($name == $source) {
            throw new Exception(__('File already exists.'));
        }

        $file = $this->getSourceContent($source);

        $data = [
            'c' => $file['c'],
            'w' => !empty($this->getDestinationFile($name)),
        ];

        if (!$data['w']) {
            throw new Exception(sprintf(__('File %s is not readable'), $source));
        }

        try {
            $dest = $this->getDestinationFile($name);

            if ($dest == false) {
                throw new Exception();
            }

            $content = $data['c'];

            if (!is_dir(dirname($dest))) {
                Files::makeDir(dirname($dest));
            }

            $fp = @fopen($dest, 'wb');
            if (!$fp) {
                throw new Exception('tocatch');
            }

            $content = (string) preg_replace('/(\r?\n)/m', "\n", $content);
            $content = (string) preg_replace('/\r/m', "\n", $content);

            fwrite($fp, $content);
            fclose($fp);
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     *
     */
    public function writeTpl(string $name, string $content): void
    {
        try {
            $dest = $this->getDestinationFile($name);

            if ($dest == false) {
                throw new Exception();
            }

            if (!is_dir(dirname($dest))) {
                Files::makeDir(dirname($dest));
            }

            $fp = @fopen($dest, 'wb');
            if (!$fp) {
                //throw new Exception('tocatch');
                return;
            }

            $content = (string) preg_replace('/(\r?\n)/m', "\n", $content);
            $content = (string) preg_replace('/\r/m', "\n", $content);

            fwrite($fp, $content);
            fclose($fp);
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     *
     */
    public function copyTpl(string $name): void
    {
        try {
            $file = $this->getSourceContent($name);
            $dest = $this->getDestinationFile($name, true);

            if ($dest == false) {
                throw new Exception();
            }

            if (!is_dir(dirname($dest))) {
                Files::makeDir(dirname($dest));
            }

            $fp = @fopen($dest, 'wb');
            if (!$fp) {
                throw new Exception('tocatch');
            }

            $content = (string) preg_replace('/(\r?\n)/m', "\n", $file['c']);
            $content = preg_replace('/\r/m', "\n", $file['c']);

            fwrite($fp, $file['c']);
            fclose($fp);
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * @return  string  The destination or empty string on error
     */
    protected function getDestinationFile(string $f, bool $totheme = false): string
    {
        $dest = $this->path . '/' . $f;
        if ($totheme) {
            $dest = implode(DIRECTORY_SEPARATOR, [$this->user_path_theme, self::THEME_TPL_DIR, $f]);
        }

        if (file_exists($dest) && is_writable($dest)) {
            return $dest;
        }

        if (is_writable(dirname($dest))) {
            return $dest;
        }

        return '';
    }

    protected function findTemplates(): void
    {
        $this->tpl = $this->getFilesInDir($this->path);

        uksort($this->tpl, [$this,'sortFilesHelper']);
    }

    /**
     * @return array<string,string>
     */
    protected function getFilesInDir(string $dir): array
    {
        $res = [];
        $dir = Path::real($dir);
        if (!$dir || !is_dir($dir) || !is_readable($dir)) {
            return $res;
        }

        $d = dir($dir);
        if (!$d) {
            return $res;
        }
        while (($f = $d->read()) !== false) {
            if (is_file($dir . '/' . $f) && !preg_match('/^\./', $f)) {
                $res[$f] = $dir . '/' . $f;
            }
        }

        return $res;
    }

    protected function sortFilesHelper(string $a, string $b): int
    {
        if ($a == $b) {
            return 0;
        }

        $ext_a = Files::getExtension($a);
        $ext_b = Files::getExtension($b);

        return strcmp($ext_a . '.' . $a, $ext_b . '.' . $b);
    }
}
