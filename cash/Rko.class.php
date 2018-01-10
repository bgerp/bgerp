<?php



/**
 * Документ за Разходни касови ордери
 *
 *
 * @category  bgerp
 * @package   cash
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cash_Rko extends cash_Document
{
    
    
    /**
     * Какви интерфейси поддържа този мениджър
     */
    public $interfaces = 'doc_DocumentIntf, acc_TransactionSourceIntf=cash_transaction_Rko, bgerp_DealIntf, email_DocumentIntf';
    
    
    /**
     * Заглавие на мениджъра
     */
    public $title = "Разходни касови ордери";
    
	
    /**
     * Заглавие на единичен документ
     */
    public $singleTitle = 'Разходен касов ордер';
    
    
    /**
     * Икона на единичния изглед
     */
    public $singleIcon = 'img/16/money_delete.png';
    
    
    /**
     * Абревиатура
     */
    public $abbr = "Rko";
    
    
    /**
     * Файл с шаблон за единичен изглед
     */
    public $singleLayoutFile = 'cash/tpl/Rko.shtml';
    
    
    /**
     * Файл с шаблон за единичен изглед
     */
    public $singleLayoutFileNarrow = 'cash/tpl/RkoNarrow.shtml';
    
    
    /**
     * Групиране на документите
     */
    public $newBtnGroup = "4.2|Финанси";
    
    
    /**
     * Кое поле отговаря на броилия парите
     */
    protected $personDocumentField = "beneficiary";
    
    
    /**
     * Полета, които при клониране да не са попълнени
     *
     * @see plg_Clone
     */
    public $fieldsNotToClone = 'termDate';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
    	// Зареждаме полетата от бащата
    	parent::getFields($this);
    	$this->FLD('beneficiary', 'varchar(255)', 'caption=Контрагент->Получил,mandatory');
    	$this->setField("contragentName", "caption=Контрагент->Получател");
    	$this->setField("termDate", "caption=Срок");
    }
    
    
    /**
     * Връща платежните операции
     */
    protected static function getOperations($operations)
    {
    	$options = array(); 
    	
    	// Оставяме само тези операции, в които се дебитира основната сметка на документа
    	foreach ($operations as $sysId => $op){
    		if($op['credit'] == static::$baseAccountSysId){
    			$options[$sysId] = $op['title'];
    		}
    	}
    	 
    	return $options;
    }
}
