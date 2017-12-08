<?php



/**
 * Документ за "Прехвърляне на задължение"
 * Могат да се добавят към нишки на покупки, продажби и финансови сделки
 *
 *
 * @category  bgerp
 * @package   findeals
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class findeals_CreditDocuments extends deals_Document
{
	
	
	/**
     * Какви интерфейси поддържа този мениджър
     */
    public  $interfaces = 'doc_DocumentIntf, acc_TransactionSourceIntf=findeals_transaction_CreditDocument, bgerp_DealIntf';
   
    
    /**
     * Заглавие на мениджъра
     */
    public $title = "Прехвърляне на задължения";
    
    
    /**
     * Неща, подлежащи на начално зареждане
     */
    public $loadList = 'plg_RowTools2, findeals_Wrapper, plg_Sorting, acc_plg_Contable,
                     doc_DocumentPlg, plg_Printing, acc_plg_DocumentSummary,doc_plg_HidePrices,
                     plg_Search, bgerp_plg_Blank,bgerp_DealIntf, doc_EmailCreatePlg';


	
    
    
    /**
     * Заглавие на единичен документ
     */
    public $singleTitle = 'Прехвърляне на задължение';
    
    
    /**
     * Абревиатура
     */
    public $abbr = "Cdc";
    
    
    /**
     * Файл с шаблон за единичен изглед
     */
    public $singleLayoutFile = 'findeals/tpl/SingleLayoutCreditDocument.shtml';

    
    /**
     * Групиране на документите
     */
    public $newBtnGroup = "4.6|Финанси";
    
    
    /**
     * Основна операция
     */
    protected static $operationSysId = 'creditDeals';
    
    
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
    		
    		$operations = $form->dealInfo->get('allowedPaymentOperations');
    		$operation = $operations[$rec->operationSysId];
    		
    		// Да се изпълнява след другото
    		$creditAcc = findeals_Deals::fetchField($rec->dealId, 'accountId');
    		
    		$debitAccount = empty($operation['reverse']) ? $operation['debit'] : acc_Accounts::fetchRec($creditAcc)->systemId;
    		$creditAccount = empty($operation['reverse']) ? acc_Accounts::fetchRec($creditAcc)->systemId : $operation['debit'];
    		
    		// Коя е дебитната и кредитната сметка
    		$rec->debitAccount = $debitAccount;
    		$rec->creditAccount = $creditAccount;
    		$rec->isReverse = empty($operation['reverse']) ? 'no' : 'yes';
    	}
    }
}
