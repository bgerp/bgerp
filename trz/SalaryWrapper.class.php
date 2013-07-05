<?php



/**
 * ТРЗ - Заплати / Премии
 *
 *
 * @category  bgerp
 * @package   trz
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class trz_SalaryWrapper extends trz_Wrapper
{
    
	function on_AfterRenderWrapping($mvc, &$tpl)
    {
        $tabs = cls::get('core_Tabs', array('htmlClass' => 'alphabet'));

        $tabs->TAB('trz_SalaryPayroll', 'Ведомост');
        $tabs->TAB('trz_SalaryIndicators', 'Показатели');
        $tabs->TAB('trz_SalaryRules', 'Правила');
      

        $tpl = $tabs->renderHtml($tpl, $mvc->className);
        $mvc->currentTab = 'Заплати';
    }
}
