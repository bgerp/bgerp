<?php



/**
 * Мениджър на Счетоводни Операции
 * Една "Операция" може да се обвързва само с документи които генерират
 * счетоводни транзакции (@see acc_TransactionSourceIntf)
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
     * Заглавие на мениджъра
     */
    var $title = "Операции";
    
    
    /**
     * Неща, подлежащи на начално зареждане
     */
    var $loadList = 'plg_RowTools, acc_WrapperSettings, plg_Search, plg_Created, plg_Sorting';
    
    
    /**
     * Активен таб на менюто
     */
    var $menuPage = 'Счетоводство:Настройки';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = "tools=Пулт, name, documentSrc, debitAccount, creditAccount, systemId, createdOn, createdBy";
   
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'tools';
    
    
    /**
     * Заглавие на единичен документ
     */
    var $singleTitle = 'Операция';
    
   
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
    	$this->FLD('documentSrc', 'class(interface=acc_TransactionSourceIntf)', 'caption=Документ,mandatory');
    	$this->FLD('debitAccount', 'customKey(mvc=acc_Accounts,key=systemId, select=title)', 'caption=Дебит сметка,mandatory');
    	$this->FLD('creditAccount', 'customKey(mvc=acc_Accounts,key=systemId, select=title)', 'caption=Кредит сметка,mandatory');
    	$this->FLD('systemId', 'varchar(32)', 'caption=System ID, export, mandatory');
    	
    	// Поставяне на уникални индекси
    	$this->setDbUnique('systemId');
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
     * @return array $options - масив от позволените операции, ако няма
     * операции за този документ редиректваме към формата за добавяне
     */
    static function getPossibleOperations($name)
    {
    	// Ид-то на класа на документа
    	expect($classId = core_Classes::fetchIdByName($name), 'Няма такъв документ !!!');
    	
    	// Извличаме онея операции, които са за този документ
    	$options = array();
        $query = static::getQuery();
        $query->where("#documentSrc = {$classId}");
        while($rec = $query->fetch()) {
        	$options[$rec->id] = $rec->name;
        }
        
        if(count($options) == 0) {
        	 return Redirect(array('acc_Operations', 'add'), FALSE, tr("Моля създайте операции за този документ"));
        }
        
        return $options;
    }
    
    
    /**
     * Функция която филтрира позволените операции на даден документ
     * според подаден клас
     * @param array() $options - Масив с ключове ид-та на операции
     * @param mixed $class - Име или Ид на клас спрямо който филтрираме
     * @return array() $options - Връщаме вече филтрираните операции
     */
    static function filter($options, $class)
    {
    	expect(is_array($options), 'Не е подаден масив !!!');
    	if(count($options) == 0) {
    		
    		return;
    	}
    	
    	// В кои номенклатури може да участва класа
    	$lists = acc_Lists::getPossibleLists($class);
    	
    	foreach($options as $key => $value) {
    		
    		// за всяка операция от масива намираме сметките с които работи
    		expect($rec = static::fetch($key), 'Няма такава операция');
    		$dAcc = $rec->debitAccount;
    		$cAcc = $rec->creditAccount;
    		$occ = 0;
    		
    		// За всяка позволена номенклатура проверяваме дали сметките
    		// на операцията я поддържат
    		foreach($lists as $keyL => $valueL) {
    			
    			$listIntf = acc_Lists::fetchField($keyL, 'regInterfaceId');
    			if(acc_Lists::getPosition($dAcc, $listIntf) ||
    			   acc_Lists::getPosition($cAcc, $listIntf)
    			)  {
    				$occ++;
    				break;
    			}
    		}
    		
    		// Ако сметките на операцията не поддържа нито една
    		// позволена номенклатура ние премахваме операцията
    		if($occ == 0) {
    			unset($options[$key]);
    		}
    	}
    	
    	// Връщаме вече филтрираните опции
    	return $options;
    }
    
    
    /**
     * Излича информацията за дебитната и кредитната сметка на операцията
     * @param int $id - ID на операцията
     * @return stdClass $rec 
     */
    static function getOperationInfo($id)
    {
    	expect($rec = static::fetch($id), 'Няма такава операция !!!');
    	
    	// Извличаме записите на сметките с подадените systemId-та
    	$debitRec = acc_Accounts::getRecBySystemId($rec->debitAccount);
    	$creditRec = acc_Accounts::getRecBySystemId($rec->creditAccount);
    	
    	$rec->debitAccount = acc_Accounts::getAccountInfo($debitRec->id);
    	$rec->creditAccount = acc_Accounts::getAccountInfo($creditRec->id);
        
    	return $rec;
    }
    
    
    /**
     * Връща операцията 
     * @param int $id - ид-то на операцията
     * @return varchar $sysId - систем ид-то на операцията
     */
    static function fetchSysId($id)
    {
    	expect($sysId = static::fetchField($id, 'systemId'), 'Няма такава операция');
    	
    	return $sysId;
    }
}