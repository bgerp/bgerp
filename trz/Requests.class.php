<?php



/**
 * Мениджър на отпуски
 *
 *
 * @category  bgerp
 * @package   trz
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
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
    var $listFields = 'tools=Пулт,id,personId, leaveFrom, leaveTo, note, useDaysFromYear, paid,state';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    //var $searchFields = 'description';

    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'tools';
    
    
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
    	$this->FLD('personId', 'key(mvc=crm_Persons,select=name)', 'caption=Служител');
    	$this->FLD('leaveFrom', 'date', 'caption=Считано->От');
    	$this->FLD('leaveTo', 'date', 'caption=Считано->До');
    	$this->FLD('note', 'text', 'caption=Забележка');
    	$this->FLD('useDaysFromYear', 'int(nowYear, nowYear-1)', 'caption=Ползване от,unit=Година');
    	$this->FLD('paid', 'enum(paid=Платен, unpaid=Неплатен)', 'caption=Вид,export');
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

        $userId = core_Users::getCurrent();

        /*if($data->listFilter->rec->selectedUsers) {
        	$data->query->where("#personId = '{$data->listFilter->rec->selectedUsers}'");
  	
        }*/
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