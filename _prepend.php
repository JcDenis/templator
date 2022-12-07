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
Clearbricks::lib()->autoload([
    'dcTemplator'    => __DIR__ . '/inc/class.templator.php',
    'templatorMedia' => __DIR__ . '/inc/class.templator.media.php',
    'templatorPager' => __DIR__ . '/inc/class.templator.pager.php',
]);

dcCore::app()->templator = new dcTemplator();

dcCore::app()->addBehavior('initWidgets', ['templatorWidgets', 'initWidgets']);

class templatorWidgets
{
    public static function initWidgets($w)
    {
        $w->create('templatorWidget', __('Templator › Rendering'), ['widgetTemplator', 'getDataTpl']);
        $tpl = ['&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;.html' => ''];
        foreach (dcCore::app()->templator->tpl as $k => $v) {
            if (preg_match('/^widget-(.+)$/', $k)) {
                $tpl = array_merge($tpl, [$k => $k]);
            }
        }
        $w->templatorWidget->setting('template', __('Template:'), '', 'combo', $tpl);
    }
}

class widgetTemplator
{
    public static function getDataTpl($w)
    {
        if (dcCore::app()->tpl->getFilePath($w->template)) {
            echo dcCore::app()->tpl->getData($w->template);
        }
    }
}
