<?php



/**
 * Мениджър на отпуски
 *
 *
 * @category  bgerp
 * @package   trz
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Отпуски
 */
class trz_Requests extends core_Master
{
    
	
	/**
     * Поддържани интерфейси
     */
    var $interfaces = 'doc_DocumentIntf';
    
    /**
     * Заглавие
     */
    var $title = 'Молби';
    
     /**
     * Заглавие в единствено число
     */
    var $singleTitle = "Молба за отпуск";
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_RowTools, trz_Wrapper, trz_LeavesWrapper, doc_DocumentPlg, doc_ActivatePlg, plg_Printing';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'id,personId, leaveFrom, leaveTo, note, useDaysFromYear, paid';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    //var $searchFields = 'description';

    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'id';
    
    
    /**
     * Поле в което да се показва иконата за единичен изглед
     */
    var $rowToolsSingleField = 'personId';
    
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'powerUser';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'powerUser';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'powerUser';
    
    
    /**
     * Кой може да го види?
     */
    var $canView = 'powerUser';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'powerUser';

    
    /**
     * Икона за единичния изглед
     */
    //var $singleIcon = 'img/16/money.png';
    
    
    /**
     * Шаблон за единичния изглед
     */
    var $singleLayoutFile = 'trz/tpl/SingleLayoutRequests.shtml';
    
    
    /**
     * Абревиатура
     */
    var $abbr = "Req";
    
    
    /**
     * Групиране на документите
     */
    var $newBtnGroup = "5.2|Човешки ресурси"; 
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
  //  var $rowToolsField = 'id';

    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
    	$this->FLD('docType', 'enum(request=Молба за отпуск, order=Заповед за отпуск)', 'caption=Документ, input=none,column=none');
    	$this->FLD('personId', 'key(mvc=crm_Persons,select=name,group=employees)', 'caption=Служител');
    	$this->FLD('leaveFrom', 'date', 'caption=Считано->От, mandatory');
    	$this->FLD('leaveTo', 'date', 'caption=Считано->До, mandatory');
    	$this->FLD('leaveDays', 'int', 'caption=Считано->Дни, input=none');
    	$this->FLD('note', 'richtext(rows=5)', 'caption=Информация->Бележки');
    	$this->FLD('useDaysFromYear', 'int', 'caption=Информация->Ползване от,unit=Година');
    	$this->FLD('paid', 'enum(paid=платен, unpaid=неплатен)', 'caption=Вид, maxRadio=2,columns=2,notNull,value=paid');
    }
    
    
    /**
     * Прилага филтъра, така че да се показват записите за определение потребител
     */
    static function on_BeforePrepareListRecs($mvc, &$res, $data)
    {
    	if($data->listFilter->rec->leaveFrom) {
    		$data->query->where("#leaveFrom = '{$data->listFilter->rec->leaveFrom}'");
    	}elseif($data->listFilter->rec->leaveTo) {
    		$data->query->where("#leaveTo = '{$data->listFilter->rec->leaveTo}'");
    	}elseif($data->listFilter->rec->leaveTo && $data->listFilter->rec->leaveFrom) {
    		$data->query->where("#leaveFrom >= '{$data->listFilter->rec->leaveFrom}'
    							 AND #leaveTo <= '{$data->listFilter->rec->leaveTo}'");
    	}
    	
        if($data->listFilter->rec->paid) {
    		$data->query->where("#paid = '{$data->listFilter->rec->paid}'");
    	}
    	
        // Филтриране по потребител/и
        if(!$data->listFilter->rec->selectedUsers) {
            $data->listFilter->rec->selectedUsers = '|' . core_Users::getCurrent() . '|';
        }

        if(($data->listFilter->rec->selectedUsers != 'all_users') && (strpos($data->listFilter->rec->selectedUsers, '|-1|') === FALSE)) {
            $data->query->where("'{$data->listFilter->rec->selectedUsers}' LIKE CONCAT('%|', #createdBy, '|%')");
            
        }


    }
    
    
    /**
     * Извиква се преди вкарване на запис в таблицата на модела
     */
    static function on_BeforeSave($mvc, &$id, $rec)
    {
        if($rec->leaveFrom &&  $rec->leaveTo){
	    	$days = static::calcLeaveDays($rec->leaveFrom, $rec->leaveTo);
	    	$rec->leaveDays = $days->workDays;
        }

    }
 
    
    /**
     * Филтър на on_AfterPrepareListFilter()
     * Малко манипулации след подготвянето на формата за филтриране
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    static function on_AfterPrepareListFilter($mvc, $data)
    {
    	$cu = core_Users::getCurrent();

        // Добавяме поле във формата за търсене
       
        $data->listFilter->FNC('selectedUsers', 'users', 'caption=Потребител,input,silent', array('attr' => array('onchange' => 'this.form.submit();')));
        $data->listFilter->setDefault('selectedUsers', 'all_users'); 
              
        $data->listFilter->view = 'horizontal';
        
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter,class=btn-filter');
        
        // Показваме само това поле. Иначе и другите полета 
        // на модела ще се появят
        $data->listFilter->showFields = 'selectedUsers, leaveFrom, leaveTo, paid';
        
        $data->listFilter->input('selectedUsers, leaveFrom, leaveTo, paid', 'silent');
    }

    
    /**
     * Подготовка на формата за добавяне/редактиране
     */
    public static function on_AfterPrepareEditForm($mvc, $data)
    {
    	//bp($data->form->fields[personId]);
    	$nowYear = dt::mysql2Verbal(dt::now(),'Y');
    	for($i = 0; $i < 5; $i++){
    		$years[] = $nowYear - $i;
    	}
    	$data->form->setSuggestions('useDaysFromYear', $years);
    	$data->form->setDefault('useDaysFromYear', $years[0]);
    	
    	$cu = core_Users::getCurrent();
        $data->form->setDefault('personId', $cu);
        
         $rec = $data->form->rec;
    }
    
    
    /**
     * Проверява и допълва въведените данни от 'edit' формата
     */
    function on_AfterInputEditForm($mvc, $form)
    {

    	$rec = $form->rec;

    }
    
    
    function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec, $userId)
    {
	    if($action == 'order'){
			if ($rec->id) {
				
					if(!haveRole('ceo') || !haveRole('trz')) {
				
						$requiredRoles = 'no_one';
				}
		    }
	    }
    }

    
    /**
     *
     */
    static function on_AfterPrepareSingleToolbar($mvc, $data)
    {
        if($mvc->haveRightFor('orders') && $data->rec->state == 'active') {
            
            $data->toolbar->addBtn('Заповед', array('trz_Orders', 'add', 'originId' => $data->rec->containerId, 'ret_url' => TRUE, ''),'class=btn-order');
        }
        
    }
    
    static public function act_Test()
    {
    	$p = 1;
    	$a = '2013-05-02 00:00:00';
    	$b = '2013-05-10 00:00:00';
    	bp(static::calcLeaveDays($a,$b));
    }
    
    
    static public function calcLeaveDays($leaveFrom, $leaveTo)
    {
    	$a = cal_calendar::getDateType($leaveFrom);
    	$leaveFromSql = "{$leaveFrom} 00:00:00";
    	$leaveToSql = "{$leaveTo} 00:00:00";
    	    	
     	$leaveFromTsm = mktime(0, 0, 0, dt::mysql2verbal($leaveFromSql, 'n'), 
    									dt::mysql2verbal($leaveFromSql, 'j'),
    									dt::mysql2verbal($leaveFromSql, 'Y') );
    	$leaveToTsm = mktime(0, 0, 0, dt::mysql2verbal($leaveToSql, 'n'), 
    									dt::mysql2verbal($leaveToSql, 'j'),
    									dt::mysql2verbal($leaveToSql, 'Y') );
    									
    	
        $allDays = (($leaveToTsm - $leaveFromTsm + (24*60*60)) / (24*60*60));
        
    	$nonWorking = $workDays = 0;
    	
    	while($leaveFromTsm <= $leaveToTsm){
    		if(((date("N", $leaveFromTsm) == '6' || date("N", $leaveFromTsm) == '7') 
    		    && (cal_calendar::getDateType(date("Y-m-d H:i:s", $leaveFromTsm)) != 'workday'))
    		    || (cal_calendar::getDateType(date("Y-m-d H:i:s", $leaveFromTsm)) == 'non-working')
    		    || (cal_calendar::getDateType(date("Y-m-d H:i:s", $leaveFromTsm))== 'holiday') ){
    			$nonWorking++;
    		} elseif((cal_calendar::getDateType(date("Y-m-d H:i:s", $leaveFromTsm)) == NULL ) ||
    		         (cal_calendar::getDateType(date("Y-m-d H:i:s", $leaveFromTsm)) == 'workday')) {
    			$workDays++;
    		}
    		$leaveFromTsm +=  24*60*60;
    		
    		
    	}
    	
    	return (object) array('nonWorking'=>$nonWorking, 'workDays'=>$workDays, 'allDays'=>$allDays);
 
    }
    
    
    /**
     * Интерфейсен метод на doc_DocumentIntf
     *
     * @param int $id
     * @return stdClass $row
     */
    function getDocumentRow($id)
    {
        $rec = $this->fetch($id);
        
        $row = new stdClass();
        
        //Заглавие
        $row->title = "Молба за отпуск  №{$rec->id}";
        
        //Създателя
        $row->author = $this->getVerbal($rec, 'createdBy');
        
        //Състояние
        $row->state = $rec->state;
        
        //id на създателя
        $row->authorId = $rec->createdBy;
        
        //$row->recTitle = $rec->title;
        
        return $row;
    }

}