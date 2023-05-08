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
if (!defined('DC_RC_PATH')) {
    return null;
}

class dcTemplator
{
    protected $tpls_default_name     = 'dotty';
    protected $post_default_name     = 'post.html';
    protected $page_default_name     = 'page.html';
    protected $category_default_name = 'category.html';

    public $template_dir_name = 'other-templates';
    public $path;

    public $tpl       = [];
    public $theme_tpl = [];

    /**
     *
     */
    public function __construct()
    {
        $this->path = dcCore::app()->blog->public_path . '/' . $this->template_dir_name;

        // Initial templates
        $this->post_tpl     = DC_ROOT . '/inc/public/default-templates/' . $this->tpls_default_name . '/' . $this->post_default_name;
        $this->category_tpl = DC_ROOT . '/inc/public/default-templates/' . $this->tpls_default_name . '/' . $this->category_default_name;

        if (dcCore::app()->plugins->moduleExists('pages')) {
            $plugin_page    = dcCore::app()->plugins->getModules('pages');
            $this->page_tpl = path::real($plugin_page['root'] . '/default-templates/' . $this->tpls_default_name . '/' . $this->page_default_name);
        }

        $this->user_theme        = dcCore::app()->blog->themes_path . '/' . dcCore::app()->blog->settings->system->theme;
        $this->user_post_tpl     = path::real($this->user_theme . '/tpl/' . $this->post_default_name);
        $this->user_category_tpl = path::real($this->user_theme . '/tpl/' . $this->category_default_name);
        $this->user_page_tpl     = path::real($this->user_theme . '/tpl/' . $this->page_default_name);

        $this->findTemplates();
    }

    /**
     *
     */
    public function canUseRessources($create = false)
    {
        if (!is_dir($this->path)) {
            if ($create) {
                files::makeDir($this->path);
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
     *
     */
    public function getSourceContent($f)
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
            'c' => file_get_contents($source[$f]),
            'w' => $this->getDestinationFile($f) !== false,
            'f' => $f,
        ];
    }

    /**
     *
     */
    public function filesList($item = '%1$s')
    {
        $files = $this->tpl;

        if (empty($files)) {
            return '<p>' . __('No file') . '</p>';
        }

        $list = '';
        foreach ($files as $k => $v) {
            $li = sprintf('<li>%s</li>', $item);

            $list .= sprintf($li, $k, html::escapeHTML($k));
        }

        return sprintf('<ul>%s</ul>', $list);
    }

    /**
     *
     */
    public function initializeTpl($name, $type)
    {
        if ($type == 'category') {
            if ($this->user_category_tpl) {
                $base = $this->user_category_tpl;
            } else {
                $base = $this->category_tpl;
            }
        } elseif ($type == 'page') {
            if ($this->user_page_tpl) {
                $base = $this->user_page_tpl;
            } else {
                $base = $this->page_tpl;
            }
        } else {
            if ($this->user_post_tpl) {
                $base = $this->user_post_tpl;
            } else {
                $base = $this->post_tpl;
            }
        }

        $source = [
            'c' => file_get_contents($base),
            'w' => $this->getDestinationFile($name) !== false,
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
                files::makeDir(dirname($dest));
            }

            $fp = @fopen($dest, 'wb');
            if (!$fp) {
                throw new Exception('tocatch');
            }

            $content = preg_replace('/(\r?\n)/m', "\n", $content);
            $content = preg_replace('/\r/m', "\n", $content);

            fwrite($fp, $content);
            fclose($fp);
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     *
     */
    public function copypasteTpl($name, $source)
    {
        if ($name == $source) {
            throw new Exception(__('File already exists.'));
        }

        $file = $this->getSourceContent($source);

        $data = [
            'c' => $file['c'],
            'w' => $this->getDestinationFile($name) !== false,
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
                files::makeDir(dirname($dest));
            }

            $fp = @fopen($dest, 'wb');
            if (!$fp) {
                throw new Exception('tocatch');
            }

            $content = preg_replace('/(\r?\n)/m', "\n", $content);
            $content = preg_replace('/\r/m', "\n", $content);

            fwrite($fp, $content);
            fclose($fp);
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     *
     */
    public function writeTpl($name, $content)
    {
        try {
            $dest = $this->getDestinationFile($name);

            if ($dest == false) {
                throw new Exception();
            }

            if (!is_dir(dirname($dest))) {
                files::makeDir(dirname($dest));
            }

            $fp = @fopen($dest, 'wb');
            if (!$fp) {
                //throw new Exception('tocatch');
            }

            $content = preg_replace('/(\r?\n)/m', "\n", $content);
            $content = preg_replace('/\r/m', "\n", $content);

            fwrite($fp, $content);
            fclose($fp);
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     *
     */
    public function copyTpl($name)
    {
        try {
            $file = $this->getSourceContent($name);
            $dest = $this->getDestinationFile($name, true);

            if ($dest == false) {
                throw new Exception();
            }

            if (!is_dir(dirname($dest))) {
                files::makeDir(dirname($dest));
            }

            $fp = @fopen($dest, 'wb');
            if (!$fp) {
                throw new Exception('tocatch');
            }

            $content = preg_replace('/(\r?\n)/m', "\n", $file['c']);
            $content = preg_replace('/\r/m', "\n", $file['c']);

            fwrite($fp, $file['c']);
            fclose($fp);
        } catch (Exception $e) {
            throw $e;
        }
    }

    protected function getDestinationFile($f, $totheme = false)
    {
        $dest = $this->path . '/' . $f;
        if ($totheme) {
            $dest = $this->user_theme . '/tpl/' . $f;
        }

        if (file_exists($dest) && is_writable($dest)) {
            return $dest;
        }

        if (is_writable(dirname($dest))) {
            return $dest;
        }

        return false;
    }

    protected function findTemplates()
    {
        $this->tpl = $this->getFilesInDir($this->path);
        //$this->theme_tpl = $this->getFilesInDir(path::real($this->user_theme).'/tpl');

        uksort($this->tpl, [$this,'sortFilesHelper']);
        //uksort($this->theme_tpl,array($this,'sortFilesHelper'));
    }

    protected function getFilesInDir($dir)
    {
        $dir = path::real($dir);
        if (!$dir || !is_dir($dir) || !is_readable($dir)) {
            return [];
        }

        $d   = dir($dir);
        $res = [];
        while (($f = $d->read()) !== false) {
            if (is_file($dir . '/' . $f) && !preg_match('/^\./', $f)) {
                $res[$f] = $dir . '/' . $f;
            }
        }

        return $res;
    }

    protected function sortFilesHelper($a, $b)
    {
        if ($a == $b) {
            return 0;
        }

        $ext_a = files::getExtension($a);
        $ext_b = files::getExtension($b);

        return strcmp($ext_a . '.' . $a, $ext_b . '.' . $b);
    }
}
