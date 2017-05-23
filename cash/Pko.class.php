<?php



/**
 * Документ за Приходни касови ордери
 *
 *
 * @category  bgerp
 * @package   cash
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cash_Pko extends cash_Document
{
    
    
    /**
     * Какви интерфейси поддържа този мениджър
     */
    public $interfaces = 'doc_DocumentIntf, acc_TransactionSourceIntf=cash_transaction_Pko, bgerp_DealIntf, email_DocumentIntf';
   
    
    /**
     * Заглавие на мениджъра
     */
    public $title = "Приходни касови ордери";
    
    
    /**
     * Заглавие на единичен документ
     */
    public $singleTitle = 'Приходен касов ордер';
    
    
    /**
     * Икона на единичния изглед
     */
    public $singleIcon = 'img/16/money_add.png';
    
    
    /**
     * Абревиатура
     */
    public $abbr = "Pko";
    
    
    /**
     * Файл с шаблон за единичен изглед
     */
    public $singleLayoutFile = 'cash/tpl/Pko.shtml';
    
    
    /**
     * Файл с шаблон за единичен изглед
     */
    public $singleLayoutFileNarrow = 'cash/tpl/PkoNarrow.shtml';

    
    /**
     * Групиране на документите
     */
    public $newBtnGroup = "4.1|Финанси";
    
    
    /**
     * Кое поле отговаря на броилия парите
     */
    protected $personDocumentField = "depositor";
    
    
    /**
     * Детайла, на модела
     */
    public $details = 'cash_NonCashPaymentDetails';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
    	// Зареждаме полетата от бащата
    	parent::getFields($this);
    	$this->FLD('depositor', 'varchar(255)', 'caption=Контрагент->Броил,mandatory');
    	$this->FLD('paymentType', 'enum(cash=В брой,card=С карта)', 'caption=Допълнително->Плащане', 'notNull,value=cash');
    }

    
    /**
     * Връща платежните операции
     */
    protected static function getOperations($operations)
    {
    	$options = array();
    	
    	// Оставяме само тези операции, в които се дебитира основната сметка на документа
    	foreach ($operations as $sysId => $op){
    		if($op['debit'] == static::$baseAccountSysId){
    			$options[$sysId] = $op['title'];
    		}
    	}
    	
    	return $options;
    }
    
    
    /**
     * След подготовка на тулбара на единичен изглед
     */
    protected static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
    	$rec = $data->rec;
    	 
    	// Бутон за промяна на безналичните методи за плащане
    	if(cash_NonCashPaymentDetails::haveRightFor('modify', (object)array('documentId' => $rec->id, 'documentClassId' => $mvc->getClassId()))){
    		core_Request::setProtected('documentId,documentClassId');
    		$url = array('cash_NonCashPaymentDetails', 'modify', 'documentId' => $rec->id, 'documentClassId' => $mvc->getClassId(), 'ret_url' => TRUE);
    		$data->toolbar->addBtn('Безналично', toUrl($url), FALSE, 'ef_icon = img/16/edit.png,title=Добавяне на безналичен начин на плащане');
    
    		$data->addUrl = toUrl($url);
    		core_Request::removeProtected('documentId,documentClassId');
    	}
    }
}
