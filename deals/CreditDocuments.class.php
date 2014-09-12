<?php



/**
 * Документ за "Прехвърляне на задължение"
 * Могат да се добавят към нишки на покупки, продажби и финансови сделки
 *
 *
 * @category  bgerp
 * @package   deals
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class deals_CreditDocuments extends deals_Document
{
	
	/**
	 * За конвертиране на съществуващи MySQL таблици от предишни версии
	 */
	public $oldClassName = 'deals_CreditDocument';
	
	
    /**
     * Какви интерфейси поддържа този мениджър
     */
    public  $interfaces = 'doc_DocumentIntf, acc_TransactionSourceIntf=deals_transaction_CreditDocument, sales_PaymentIntf, bgerp_DealIntf, email_DocumentIntf, doc_ContragentDataIntf';
   
    
    /**
     * Заглавие на мениджъра
     */
    public $title = "Прехвърляне на задължения";
    
    
    /**
     * Неща, подлежащи на начално зареждане
     */
    public $loadList = 'plg_RowTools, deals_Wrapper, plg_Sorting, acc_plg_Contable,
                     doc_DocumentPlg, plg_Printing, acc_plg_DocumentSummary,
                     plg_Search, bgerp_plg_Blank,bgerp_DealIntf, doc_EmailCreatePlg';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo, dealsMaster';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	public $canSingle = 'ceo, deals';
    
    
    /**
     * Заглавие на единичен документ
     */
    public $singleTitle = 'Прехвърляне на задължение';
    
    
    /**
     * Абревиатура
     */
    public $abbr = "Cdc";
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'deals, ceo';
    
    
    /**
     * Кой може да пише?
     */
    public $canWrite = 'deals, ceo';
    
    
    /**
     * Кой може да го контира?
     */
    public $canConto = 'deals, ceo';
    
    
    /**
     * Кой може да го оттегля
     */
    public $canRevert = 'deals, ceo';
    
    
    /**
     * Файл с шаблон за единичен изглед на статия
     */
    public $singleLayoutFile = 'deals/tpl/SingleLayoutCreditDocument.shtml';

    
    /**
     * Групиране на документите
     */
    public $newBtnGroup = "4.6|Финанси";
    
    
    /**
     * Основна операция
     */
    protected static $operationSysId = 'creditDeals';
    
    
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
    function on_AfterInputEditForm($mvc, $form)
    {
    	$rec = &$form->rec;
    	
    	if ($form->isSubmitted()){
    		$operations = $form->dealInfo->get('allowedPaymentOperations');
    		$operation = $operations[$rec->operationSysId];
    		
    		$creditAcc = deals_Deals::fetchField($rec->dealId, 'accountId');
    		
    		$debitAccount = empty($operation['reverse']) ? $operation['debit'] : acc_Accounts::fetchRec($creditAcc)->systemId;
    		$creditAccount = empty($operation['reverse']) ? acc_Accounts::fetchRec($creditAcc)->systemId : $operation['debit'];
    		
    		// Коя е дебитната и кредитната сметка
    		$rec->debitAccount = $debitAccount;
    		$rec->creditAccount = $creditAccount;
    		$rec->isReverse = empty($operation['reverse']) ? 'no' : 'yes';
    		acc_Periods::checkDocumentDate($form, 'valior');
    		
    		$currencyCode = currency_Currencies::getCodeById($rec->currencyId);
    		if(!$rec->rate){
    			$rec->rate = round(currency_CurrencyRates::getRate($rec->valior, $currencyCode, NULL), 4);
    		} else {
    			if($msg = currency_CurrencyRates::hasDeviation($rec->rate, $rec->valior, $currencyCode, NULL)){
    				$form->setWarning('rate', $msg);
    			}
    		}
    	}
    }
}
