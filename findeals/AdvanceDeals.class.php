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
     * Кой има право да чете?
     */
    public $canRead = 'ceo,findeals';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,findeals';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'ceo,findeals';
    
    
    /**
     * Кой може да пише?
     */
    public $canWrite = 'ceo,findeals';
    
    
    /**
     * Кой може да пише?
     */
    public $canEdit = 'ceo,findeals';
    
    
    /**
     * Кой има право да клонира?
     */
    public $canClonerec = 'ceo,findeals';
    
    
    /**
     * Кой има право да клонира?
     */
    public $canAdd = 'ceo,findeals';
    
    
    /**
     * Сметки с какви интерфейси да се показват за избор
     */
    protected $accountListInterfaces = 'crm_PersonAccRegIntf,deals_DealsAccRegIntf,currency_CurrenciesAccRegIntf';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, acc_plg_Registry, findeals_Wrapper, plg_Printing, doc_DocumentPlg, acc_plg_Contable,
                        acc_plg_DocumentSummary, plg_Search, bgerp_plg_Blank, doc_ActivatePlg,
                        doc_plg_Close, cond_plg_DefaultValues, plg_Clone, doc_plg_Prototype,doc_plg_SelectFolder';
    
    
    /**
     * Списък с корици и интерфейси, където може да се създава нов документ от този клас
     */
    public $coversAndInterfacesForNewDoc = 'crm_ContragentAccRegIntf';

    
    /**
     * Дали в листовия изглед да се показва бутона за добавяне
     */
    public $listAddBtn = TRUE;
    
    
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
        
        if (cls::haveInterface('crm_PersonAccRegIntf', $coverClass)) {
            return TRUE;
        }
        
        return FALSE;
    }
    
    
    /**
     * В кои корици може да се вкарва документа
     * @return array - интерфейси, които трябва да имат кориците
     */
    public static function getCoversAndInterfacesForNewDoc()
    {
        return array('crm_PersonAccRegIntf');
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