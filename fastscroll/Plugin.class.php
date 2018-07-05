<?php



/**
 * Клас 'fastscroll_Plugin' - плъгин за бързо скрoлиране на страниците
 *
 *
 * @category  vendors
 * @package   fastscroll
 * @author    Nevena Georgiva <nevena.georgieva89@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
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
    
        // Активираме скролирането
        jquery_Jquery::run($invoker, " fastScroll({$conf->FASTSCROLL_HIDE_AFTER_SEC},{$conf->FASTSCROLL_ACTIVE_RATIO});", true);
    }
}
