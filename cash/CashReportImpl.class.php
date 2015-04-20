<?php



/**
 * Имплементация на 'frame_ReportSourceIntf' за справка на движенията по каса
 *
 *
 * @category  bgerp
 * @package   acc
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cash_CashReportImpl extends acc_PeriodHistoryReportImpl
{
    
	
    /**
     * Кой може да избира драйвъра
     */
    public $canSelectSource = 'ceo, acc';
    
    
    /**
     * Заглавие
     */
    public $title = 'Финанси»Дневни обороти - каса';
    
    
    /**
     * Кои интерфейси имплементира
     */
    public $interfaces = 'frame_ReportSourceIntf';
    
    
    /**
     * Дефолт сметка
     */
    protected $defaultAccount = '501';
    
    
    /**
     * След подготовката на ембеднатата форма
     */
    public static function on_AfterAddEmbeddedFields($mvc, core_Form &$form)
    {
    	$accId = acc_Accounts::getRecBySystemId($mvc->defaultAccount)->id;
    	$form->setDefault('accountId', $accId);
    	$form->setHidden('accountId');
    }
    
    
    /**
     * След подготовката на ембеднатата форма
     */
    public static function on_AfterPrepareEmbeddedForm($mvc, core_Form &$form)
    {
    	$cItemPosition = acc_Lists::getPosition($mvc->defaultAccount, 'cash_CaseAccRegIntf');
    	$currencyPosition = acc_Lists::getPosition($mvc->defaultAccount, 'currency_CurrenciesAccRegIntf');
    	 
    	$form->setField("ent{$cItemPosition}Id", 'caption=Каса');
    	$form->setField("ent{$currencyPosition}Id", 'caption=Валута');
    	
    	// Слагаме избраната каса, ако има такава
    	if($curCase = cash_Cases::getCurrent('id', FALSE)){
    		$caseItemId = acc_Items::fetchItem('cash_Cases', $curCase)->id;
    		$form->setDefault("ent{$cItemPosition}Id", $caseItemId);
    	}
    }
    
    
    /**
     * Какви са полетата на таблицата
     */
    public static function on_AfterPrepareListFields($mvc, &$res, $data)
    {
    	$data->listFields['baseQuantity'] = 'Начално';
    	$data->listFields['blQuantity'] = 'Остатък';
    	$data->listFields['debitQuantity'] = 'Приход';
    	$data->listFields['creditQuantity'] = 'Разход';
    	
    	unset($data->listFields['baseAmount'],$data->listFields['debitAmount'],$data->listFields['creditAmount'],$data->listFields['blAmount']);
    }
}