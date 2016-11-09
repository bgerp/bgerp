<?php



/**
 * Клас 'findeals_AdvanceDeals'
 *
 * Мениджър за служебни аванси (вид Финансова сделка)
 *
 *
 * @category  bgerp
 * @package   findeals
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class findeals_AdvanceDeals extends findeals_Deals
{
    
	
    /**
     * Заглавие
     */
    public $title = 'Служебни аванси';
    
    
    /**
     * Абревиатура
     */
    public $abbr = 'Ad';
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Служебен аванс';
    
    
    /**
     * Икона за единичния изглед
     */
    public $singleIcon = 'img/16/kwallet.png';

    
    /**
     * Групиране на документите
     */ 
    public $newBtnGroup = "4.2|Финанси";
    
    
    /**
     * Сметки с какви интерфейси да се показват за избор
     */
    protected $accountListInterfaces = 'crm_PersonAccRegIntf,deals_DealsAccRegIntf,currency_CurrenciesAccRegIntf';
    
    
    /**
     * Може ли документа да се добави в посочената папка?
     *
     * Документи-финансови сделки могат да се добавят само в папки с корица контрагент.
     *
     * @param $folderId int ид на папката
     * @return boolean
     */
    public static function canAddToFolder($folderId)
    {
    	$coverClass = doc_Folders::fetchCoverClassName($folderId);
    
    	return cls::haveInterface('crm_PersonAccRegIntf', $coverClass);
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	if($fields['-single']){
    		$row->contragentCaption = tr('Подотчетно лице');
    	}
    }
}