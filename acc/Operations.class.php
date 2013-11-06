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
    var $loadList = 'plg_RowTools, acc_WrapperSettings, plg_Search, 
    				 plg_Created, plg_Sorting, plg_ExportCsv,
    				 plg_AutoFilter';
    
    
    /**
     * Активен таб на менюто
     */
    var $menuPage = 'Счетоводство:Настройки';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'ceo,acc';
    
    
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
    var $canRead = 'acc, ceo';
    
    
    /**
     * Кой може да пише?
     */
    var $canWrite = 'accMaster, ceo';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'accMaster, ceo';
    
    
    /**
     * Кой може да го отхвърли?
     */
    var $canReject = 'accMaster, ceo';

    
    /**  
     * Кой има право да променя системните данни?  
     */  
    var $canEditsysdata = 'accMaster, admin';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
    	$this->FLD('name', 'varchar(155)', 'caption=Име,width=100%,mandatory,export=Csv');
    	$this->FLD('documentSrc', 'class(interface=acc_TransactionSourceIntf,select=title)', 'caption=Документ,mandatory,export=Csv');
    	$this->FLD('debitAccount', 'customKey(mvc=acc_Accounts,key=systemId, select=title)', 'caption=Дебит сметка,mandatory,export=Csv, autoFilter');
    	$this->FLD('creditAccount', 'customKey(mvc=acc_Accounts,key=systemId, select=title)', 'caption=Кредит сметка,mandatory,export=Csv');
    	$this->FLD('systemId', 'varchar(32)', 'caption=System ID, mandatory,export=Csv');
    	
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
     * Изпълнява се след подготовката на формата за филтриране
     */
    function on_AfterPrepareListFilter($mvc, $data)
    {
        $form = $data->listFilter;
        
        // В хоризонтален вид
        $form->view = 'horizontal';
        
        // Добавяме бутон
        $form->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        
        // Показваме само това поле. Иначе и другите полета 
        // на модела ще се появят
        $form->showFields = 'debitAccount';
        
        $form->input('debitAccount', 'silent');

        if($form->rec->debitAccount){
        	$data->query->where(array("#debitAccount = '{$form->rec->debitAccount}' OR #creditAccount = '{$form->rec->debitAccount}'"));
        }
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
        	$options[$rec->systemId] = $rec->name;
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
    		expect($rec = static::fetchBySysId($key), 'Няма такава операция');
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
     * Връща операцията по дадено systemId
     * @param string $sysId - системно ид на операцията
     * @return stdClass $rec - запис на операцията
     */
    static function fetchBySysId($sysId)
    {
    	expect($rec = static::fetch(array("#systemId ='[#1#]'", $sysId)), 'Няма операция с това systemId');
    	
    	return $rec;
    }
    
    
    /**
     * Излича информацията за дебитната и кредитната сметка на операцията
     * @param string $sysId - systemId на операцията
     * @return stdClass $rec 
     */
    static function getOperationInfo($sysId)
    {
    	expect($rec = static::fetchBySysId($sysId), 'Няма такава операция !!!');
    	
    	// Извличаме записите на сметките с подадените systemId-та
    	$debitRec = acc_Accounts::getRecBySystemId($rec->debitAccount);
    	$creditRec = acc_Accounts::getRecBySystemId($rec->creditAccount);
    	
    	$rec->debitAccount = acc_Accounts::getAccountInfo($debitRec->id);
    	$rec->creditAccount = acc_Accounts::getAccountInfo($creditRec->id);
        
    	return $rec;
    }
    
    
	/**
     * За NULL-ява празните systemId
     */
    function on_BeforeSetupMvc($mvc, &$res) 
    {
        if($mvc->db->tableExists($mvc->dbTableName)) {
            $query = $mvc->getQuery();
            while($rec = $query->fetch()) {
                $saveFlag = FALSE;
                if($rec->systemId === '') {
                    $rec->systemId = NULL;
                    $saveFlag = TRUE;
                }

                if($saveFlag) {
                    $mvc->save($rec);
                }
            }
        }
    }
    
    
	/**
     * Изпълнява се преди запис
     */
    public static function on_BeforeSave(core_Manager $mvc, $res, $rec)
    {
    	if(isset($rec->csv_documentSrc) && strlen($rec->csv_documentSrc) != 0){
    		expect($rec->documentSrc = core_Classes::fetchIdByName($rec->csv_documentSrc));
    	}
    }
    
    
	/**
     * Извиква се след SetUp-а на таблицата за модела
     */
    static function on_AfterSetupMvc($mvc, &$res)
    {
    	$file = "acc/setup/csv/Operations.csv";
    	$fields = array( 
	    	0 => "name", 
	    	1 => "csv_documentSrc", 
	    	2 => "debitAccount", 
	    	3 => "creditAccount",
	    	4 => "systemId");
    	
    	$cntObj = csv_Lib::importOnce($mvc, $file, $fields);
    	$res .= $cntObj->html;
    	
    	return $res;
    }
}
