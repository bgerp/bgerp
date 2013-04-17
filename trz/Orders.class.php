<?php



/**
 * Мениджър на заповеди за отпуски
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
class trz_Orders extends core_Master
{
    
	
	/**
     * Поддържани интерфейси
     */
    var $interfaces = 'doc_DocumentIntf';
    
    /**
     * Заглавие
     */
    var $title = 'Заповеди';
    
     /**
     * Заглавие в единствено число
     */
    var $singleTitle = "Заповед за отпуск";
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_RowTools, trz_Wrapper, trz_LeavesWrapper, doc_DocumentPlg, doc_ActivatePlg, plg_Printing';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'id,personId, leaveFrom, leaveTo, note, useDaysFromYear, isPaid, amount';
    
    
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
    var $canRead = 'ceo, trz';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'ceo, trz';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'ceo, trz';
    
    
    /**
     * Кой може да го види?
     */
    var $canView = 'ceo, trz';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'ceo, trz';
  
    
    var $canOrders = 'ceo, trz';
    /**
     * Икона за единичния изглед
     */
    //var $singleIcon = 'img/16/money.png';
    
    
    /**
     * Шаблон за единичния изглед
     */
    var $singleLayoutFile = 'trz/tpl/SingleLayoutOrders.shtml';
    
    
    /**
     * Абревиатура
     */
    var $abbr = "Ord";
    
    
    /**
     * Групиране на документите
     */
    var $newBtnGroup = "5.3|Човешки ресурси"; 

    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
    	$this->FLD('personId', 'key(mvc=crm_Persons,select=name,group=employees)', 'caption=Служител');
    	$this->FLD('leaveFrom', 'date', 'caption=Считано->От, mandatory');
    	$this->FLD('leaveTo', 'date', 'caption=Считано->До, mandatory');
    	$this->FLD('leaveDays', 'int', 'caption=Считано->Дни, input=none');
    	$this->FLD('note', 'richtext(rows=5)', 'caption=Информация->Бележки');
    	$this->FLD('useDaysFromYear', 'int(nowYest, nowYear-1)', 'caption=Информация->Ползване от,unit=Година');
    	$this->FLD('isPaid', 'enum(paid=платен, unpaid=неплатен)', 'caption=Вид,maxRadio=2,columns=2,notNull,value=paid');
    	$this->FLD('amount', 'double', 'caption=Начисления');
    }
    
    /**
     * Извиква се преди вкарване на запис в таблицата на модела
     */
    static function on_BeforeSave($mvc, &$id, $rec)
    {
        if($rec->leaveFrom &&  $rec->leaveTo){
	    	$days = trz_Requests::calcLeaveDays($rec->leaveFrom, $rec->leaveTo);
	    	$rec->leaveDays = $days->workDays;
        }

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
    	
        if($data->listFilter->rec->isPaid) {
    		$data->query->where("#isPaid = '{$data->listFilter->rec->isPaid}'");
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
        $data->listFilter->showFields = 'selectedUsers, leaveFrom, leaveTo, isPaid';
        
        $data->listFilter->input('selectedUsers, leaveFrom, leaveTo, isPaid', 'silent');
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
        
        if($data->form->rec->originId){
			// Ако напомнянето е по  документ задача намираме кой е той
    		$doc = doc_Containers::getDocument($data->form->rec->originId);
    		$class = $doc->className;
    		$dId = $doc->that;
    		$rec = $class::fetch($dId);
    		
    		// Извличаме каквато информация можем от оригиналния документ
    		
    		$data->form->setDefault('personId', $rec->personId);
    		$data->form->setDefault('leaveFrom', $rec->leaveFrom);
    		$data->form->setDefault('leaveTo', $rec->leaveTo);
    		$data->form->setDefault('leaveDays', $rec->leaveDays);
    		$data->form->setDefault('note', $rec->note);
    		$data->form->setDefault('useDaysFromYear', $rec->useDaysFromYear);
    		$data->form->setDefault('isPaid', $rec->paid);
    

		}
        
         $rec = $data->form->rec;
    }
      
    
    /**
     * Проверява и допълва въведените данни от 'edit' формата
     */
    function on_AfterInputEditForm($mvc, $form)
    {
    	$rec = $form->rec;

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
        $row->title = "Заповед за отпуск  №{$rec->id}";
        
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