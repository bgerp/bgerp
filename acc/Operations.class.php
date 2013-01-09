<?php



/**
 * Мениджър на Счетоводни Операции
 *
 *
 * @category  bgerp
 * @package   acc
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class acc_Operations extends core_Manager
{
    
    
    /**
     * Какви интерфейси поддържа този мениджър
     */
    //var $interfaces = 'acc_TransactionSourceIntf';
    
    
    /**
     * Заглавие на мениджъра
     */
    var $title = "Операции";
    
    
    /**
     * Неща, подлежащи на начално зареждане
     */
    var $loadList = 'plg_RowTools, acc_WrapperSettings,  plg_Search';
    
    
    /**
     * Активен таб на менюто
     */
    var $menuPage = 'Счетоводство:Настройки';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = "tools=Пулт, name, document, debitAccount, creditAccount";
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'tools';
    
    
    /**
     * Заглавие на единичен документ
     */
    var $singleTitle = 'Операция';
    
    
    /**
     * Абревиатура
     */
    var $abbr = "So";
   
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'acc, admin';
    
    
    /**
     * Кой може да пише?
     */
    var $canWrite = 'acc, admin';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'acc, admin';
    
    
    /**
     * Кой може да го отхвърли?
     */
    var $canReject = 'acc, admin';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
    	$this->FLD('name', 'varchar(155)', 'caption=Име,width=100%,mandatory');
    	$this->FLD('document', 'key(mvc=core_Classes, interface=doc_documentIntf, select=title)', 'caption=Документ,mandatory');
    	$this->FLD('debitAccount', 'customKey(mvc=acc_Accounts,key=systemId, select=title)', 'caption=Дебит сметка,mandatory');
    	$this->FLD('creditAccount', 'customKey(mvc=acc_Accounts,key=systemId, select=title)', 'caption=Кредит сметка,mandatory');
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
    	$debitRec = acc_Accounts::getRecBySystemId($rec->debitAccount);
    	$row->debitAccount = acc_Accounts::getRecTitle($debitRec);
    	
    	$creditRec = acc_Accounts::getRecBySystemId($rec->creditAccount);
    	$row->creditAccount = acc_Accounts::getRecTitle($creditRec);
    }
    
    
    /**
     * Кои операции са възможни на даден документ
     * @param string $name - името на класа на документа
     * @return array $options - масив от позволените операции
     */
    static function getPossibleOperations($name)
    {
    	// Ид-то на класа на документа
    	$classId = core_Classes::fetchIdByName($name);
    	
    	// Извличаме онея операции, които са за този документ
    	$options = array();
        $query = static::getQuery();
        $query->where("#document = {$classId}");
        while($rec = $query->fetch()) {
        	$options[$rec->id] = $rec->name;
        }
        
        return $options;
    }
    
    
    /**
     * Излича информацията за дебитната и кредитната сметка на операцията
     * @param int $id - ID на операцията
     * @return stdClass $rec 
     */
    static function getOperationInfo($id)
    {
    	$rec = static::fetch($id);
    	
    	// Извличаме записите на сметките с подадените systemId-та
    	$debitRec = acc_Accounts::getRecBySystemId($rec->debitAccount);
    	$creditRec = acc_Accounts::getRecBySystemId($rec->creditAccount);
    	
    	$rec->debitAccount = acc_Accounts::getAccountInfo($debitRec->id);
    	$rec->creditAccount = acc_Accounts::getAccountInfo($creditRec->id);
        
    	return $rec;
    }
    
    
    /**
     * Записи за инициализиране на таблицата
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     */
    static function on_AfterSetupMvc($mvc, &$res)
    {
    	//@TODO
    }
}