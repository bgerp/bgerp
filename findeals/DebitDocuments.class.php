<?php



/**
 * Документ за "Прехвърляне на вземания"
 * Могат да се добавят към нишки на покупки, продажби и финансови сделки
 *
 *
 * @category  bgerp
 * @package   findeals
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class findeals_DebitDocuments extends deals_Document
{
	
	
	/**
     * Какви интерфейси поддържа този мениджър
     */
    public  $interfaces = 'doc_DocumentIntf, acc_TransactionSourceIntf=findeals_transaction_DebitDocument, bgerp_DealIntf';
   
    
    /**
     * Заглавие на мениджъра
     */
    public $title = "Прехвърляне на вземания";
    
    
    /**
     * Неща, подлежащи на начално зареждане
     */
    public $loadList = 'plg_RowTools2, findeals_Wrapper, plg_Sorting, acc_plg_Contable,
                     doc_DocumentPlg, plg_Printing, acc_plg_DocumentSummary,doc_plg_HidePrices,
                     plg_Search, bgerp_plg_Blank,bgerp_DealIntf, doc_EmailCreatePlg';
    
    
    /**
     * Заглавие на единичен документ
     */
    public $singleTitle = 'Прехвърляне на взeмане';
    
    
    /**
     * Абревиатура
     */
    public $abbr = "Cdd";
    
    
    /**
     * Файл с шаблон за единичен изглед
     */
    public $singleLayoutFile = 'findeals/tpl/SingleLayoutDebitDocument.shtml';

    
    /**
     * Групиране на документите
     */
    public $newBtnGroup = "4.5|Финанси";
    
    
    /**
     * Основна операция
     */
    protected static $operationSysId = 'debitDeals';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo, findeals, acc';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'ceo, findeals, acc';
    
    
    /**
     * Кой може да пише?
     */
    public $canWrite = 'findeals, ceo';
    
    
    /**
     * Кой може да го контира?
     */
    public $canConto = 'findeals, ceo';
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
    	parent::addDocumentFields($this);
    }
    
    
    /**
     * Проверка и валидиране на формата
     */
    public static function on_AfterInputDocumentEditForm($mvc, &$form)
    {
    	$rec = &$form->rec;
    	
    	if ($form->isSubmitted()){
    		
    		expect($rec->dealId);
    		
    		$oprtations = $form->dealInfo->get('allowedPaymentOperations');
    		$operation = $oprtations[$rec->operationSysId];
    		$debitAcc = findeals_Deals::fetchField($rec->dealId, 'accountId');
    		
    		$debitAccount = empty($operation['reverse']) ? acc_Accounts::fetchRec($debitAcc)->systemId : $operation['credit'];
    		$creditAccount = empty($operation['reverse']) ? $operation['credit'] : acc_Accounts::fetchRec($debitAcc)->systemId;
    		
    		// Коя е дебитната и кредитната сметка
    		$rec->debitAccount = $debitAccount;
    		$rec->creditAccount = $creditAccount;
    		$rec->isReverse = empty($operation['reverse']) ? 'no' : 'yes';
    	}
    }
}
