<?php


/**
 * Клас 'fastscroll_Plugin' - плъгин за бързо скролиране на страниците
 *
 *
 * @category  vendors
 * @package   fastscroll
 *
 * @author    Nevena Georgiva <nevena.georgieva89@gmail.com>
 * @copyright 2006 - 2026 Experta EOOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class fastscroll_Plugin extends core_Plugin
{
    /**
     * Изпълнява се преди добавянето на бутона за показване на втория ред бутони
     */
    public function on_Output(&$invoker)
    {
        $conf = core_Packs::getConfig('fastscroll');
        
        $invoker->push('fastscroll/lib/fastscroll.css', 'CSS');
        $invoker->push('fastscroll/lib/fastscroll.js', 'JS');
        
        $hideAfterSec = (float) ($conf->FASTSCROLL_HIDE_AFTER_SEC ?? 3);
        $activateRatio = (float) ($conf->FASTSCROLL_ACTIVE_RATIO ?? 2);
        $topLabel = json_encode(tr('Към началото||Scroll to top'));
        $bottomLabel = json_encode(tr('Към края||Scroll to bottom'));

        // Активираме скролирането
        jquery_Jquery::run($invoker, "if (typeof window.fastScroll === 'function') { window.fastScroll({$hideAfterSec}, {$activateRatio}, {$topLabel}, {$bottomLabel}); }", true);
    }
}
