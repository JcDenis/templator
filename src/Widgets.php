<?php

declare(strict_types=1);

namespace Dotclear\Plugin\templator;

use Dotclear\App;
use Dotclear\Plugin\widgets\WidgetsStack;
use Dotclear\Plugin\widgets\WidgetsElement;

/**
 * @brief       templator widgets class.
 * @ingroup     templator
 *
 * @author      Osku (author)
 * @author      Jean-Christian Denis (latest)
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
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
                self::getDataTpl(...)
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
        return is_string($w->__get('template')) && App::frontend()->template()->getFilePath($w->__get('template')) ?
            App::frontend()->template()->getData($w->__get('template')) : '';
    }
}
