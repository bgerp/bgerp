<?php

/**
 * Клас 'common_Wrapper'
 *
 * Поддържа системното меню и табовете на пакета 'Core'
 *
 * @category   Experta Framework
 * @package    core
 * @author     Milen Georgiev <milen@download.bg>
 * @copyright  2006-2010 Experta OOD
 * @license    GPL 2
 * @version    CVS: $Id: Guess.php,v 1.29 2009/04/09 22:24:12 dufuz Exp $
 * @link
 * @since
 */

class common_Wrapper extends core_Plugin
{
    /**
     *  Извиква се след рендирането на 'опаковката' на мениджъра
     */
    function on_AfterRenderWrapping($invoker, &$tpl)
    {
        Mode::set('pageMenu', 'Общи');
        
        $tree = cls::get('core_Tree', array('name'=>'commonWrp'));
        
        $tree->addNode('Общи регистри->Банкови сметки->Списък', array('common_BankAccounts'));
        $tree->addNode('Общи регистри->Банкови сметки->Групи', array('common_BankAccountGroups'));
        $tree->addNode('Общи регистри->Банкови сметки->Типове', array('common_BankAccountTypes'));
        
        $tree->addNode('Общи регистри->Валути->Списък', array('common_Currencies'));
        $tree->addNode('Общи регистри->Валути->Групи', array('common_CurrencyGroups'));
        $tree->addNode('Общи регистри->Валути->Курсове', array('common_CurrencyRates'));
        
        $tree->addNode('Общи регистри->Мерки->Списък', array('common_Units'));
        $tree->addNode('Общи регистри->Мерки->Групи', array('common_UnitsGroups'));
        
        $tree->addNode('Общи регистри->Локации->Списък', array('common_Locations'));
        $tree->addNode('Общи регистри->Локации->Групи', array('common_LocationGroups'));
        $tree->addNode('Общи регистри->Локации->Типове', array('common_LocationTypes'));
        
        $tree->addNode('Общи регистри->VATs', array('common_Vats'));
        
        $tree->addNode('Общи регистри->Плащания->Начини', array('common_PaymentMethods'));
        
        $tree->addNode('Общи регистри->Условия на доставка', array('common_DeliveryTerms'));
        
        $tree->addNode('Общи регистри->Институции->МВР', array('common_Mvr'));
        $tree->addNode('Общи регистри->Институции->Окръжни съдилища', array('common_DistrictCourts'));
        
       
        
        /*

        $tabs->TAB('common_Units', 'Мерки');
        $tabs->TAB('', 'VATs');
        $tabs->TAB('common_Locations', 'Локации');
        $tabs->TAB('common_LocationTypes', 'Тип. локации');
        $tabs->TAB('common_DocumentTypes', 'Тип. документи'); */
        
        $tpl = $tree->renderHtml($tpl);
        
        $tpl->append(tr($invoker->title) . " » ", 'PAGE_TITLE');
    }
}