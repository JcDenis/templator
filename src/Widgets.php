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
use Dotclear\Plugin\widgets\WidgetsStack;
use Dotclear\Plugin\widgets\WidgetsElement;

/**
 * Widgets.
 */
class Widgets
{
    /**
     * @param  WidgetsStack $w WidgetsStack instance
     */
    public static function initWidgets(WidgetsStack $w): void
    {
        $tpl  = ['&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;.html' => ''];
        $tpls = Templator::instance()->getTpl();
        foreach ($tpls as $k => $v) {
            if (preg_match('/^widget-(.+)$/', $k)) {
                $tpl = array_merge($tpl, [$k => $k]);
            }
        }

        $w
            ->create(
                'templatorWidget',
                __('Templator › Rendering'),
                [self::class, 'getDataTpl']
            )
            ->setting(
                'template',
                __('Template:'),
                '',
                'combo',
                $tpl
            );
    }

    public static function getDataTpl(WidgetsElement $w): string
    {
        return is_string($w->__get('template')) && dcCore::app()->tpl->getFilePath($w->__get('template')) ?
            dcCore::app()->tpl->getData($w->__get('template')) : '';
    }
}
