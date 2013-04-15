<?php



/**
 * Мениджър на болнични
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
class trz_Sickdays extends core_Master
{
    
	
	/**
     * Поддържани интерфейси
     */
    var $interfaces = 'doc_DocumentIntf';
    
    /**
     * Заглавие
     */
    var $title = 'Болнични листи';
    
     /**
     * Заглавие в единствено число
     */
    var $singleTitle = "Болничен лист";
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_RowTools, trz_Wrapper, doc_DocumentPlg, doc_ActivatePlg, plg_Printing';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'tools=Пулт,id,personId, fitNoteNum, fitNoteFile, startDate, toDate, reason, note, icdCode, accruals=Начисления';
    
    
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
    var $canRead = 'ceo,trz';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'ceo,trz';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'ceo,trz';
    
    
    /**
     * Кой може да го види?
     */
    var $canView = 'ceo,trz';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'ceo,trz';
    
    /**
     * Кой има право да прави начисления
     */
    var $canAccruals = 'ceo,trz,manager';
  
    
    /**
     * Икона за единичния изглед
     */
    //var $singleIcon = 'img/16/money.png';
    
    
    /**
     * Шаблон за единичния изглед
     */
    var $singleLayoutFile = 'trz/tpl/SingleLayoutSickdays.shtml';
    
    
    /**
     * Абревиатура
     */
    var $abbr = "Sick";
    
    
    /**
     * Групиране на документите
     */
    var $newBtnGroup = "5.3|Човешки ресурси"; 
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
  //  var $rowToolsField = 'id';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
    	$this->FLD('personId', 'key(mvc=crm_Persons,select=name,where=#groupList LIKE \\\'%|1|%\\\')', 'caption=Служител');
    	$this->FLD('startDate', 'date', 'caption=Отсъствие->От, mandatory');
    	$this->FLD('toDate', 'date', 'caption=Отсъствие->До, mandatory');
    	$this->FLD('fitNoteNum', 'varchar', 'caption=Болничен лист->Номер, hint=Номер/Серия/Година');
    	$this->FLD('fitNoteFile', 'fileman_FileType(bucket=trzSickdays)', 'caption=Болничен лист->Файл');
    	$this->FLD('reason', 'enum(1=Майчинство до 15 дни,
								   2=Майчинство до 410 дни,
								   3=Заболяване,
								   4=Трудова злополука,
								   5=Битова злополука,
								   6=Гледане на болен член от семейството,
								   7=Професионално заболяване,
								   8=Бащинство до 15 дни,
								   9=Бащинство до 410 дни,
								   10=Гледа дете до 18 години)', 'caption=Информация->Причина');
    	$this->FLD('note', 'richtext(rows=5)', 'caption=Информация->Бележки');
    	$this->FLD('icdCode', 'varchar(5)', 'caption=Информация->MKB код, hint=Международна класификация на болестите');
    	$this->FLD('paidByEmployer', 'double(Min=0)', 'caption=Заплащане->Работодател, input=none');
    	$this->FLD('paidByHI', 'double(Min=0)', 'caption=Заплащане->НЗК, input=none');
    }
    
    
    /**
     * Прилага филтъра, така че да се показват записите за определение потребител
     */
    static function on_BeforePrepareListRecs($mvc, &$res, $data)
    {
       
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
                
        $data->listFilter->view = 'horizontal';
        
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter,class=btn-filter');
        
        // Показваме само това поле. Иначе и другите полета 
        // на модела ще се появят
        $data->listFilter->showFields = 'selectedUsers';
        
        $data->listFilter->input('selectedUsers', 'silent');
    }

    
    /**
     * Подготовка на формата за добавяне/редактиране
     */
    public static function on_AfterPrepareEditForm($mvc, $data)
    {
    	//bp($data->form->fields[personId]);
        $data->form->setDefault('reason', 3);
        if(Request::get('accruals')){
        	$data->form->setField('paidByEmployer', 'input, mandatory');
        	$data->form->setField('paidByHI', 'input, mandatory');
        	
        }

        $rec = $data->form->rec;
    }
    
    
    /**
     * Проверява и допълва въведените данни от 'edit' формата
     */
    function on_AfterInputEditForm($mvc, $form)
    { 
    	$now = dt::now(FALSE);
        // Ако формата е изпратена успешно
        if ($form->isSubmitted()) {
        	if($form->rec->startDate > $now){
        		// Добавяме съобщение за грешка
                $form->setError('startDate', "Началната дата трябва да е преди ". $now);
        	}
        	if($form->rec->toDate < $form->rec->startDate){
        		$form->setError('toDate', "Крайната дата трябва да е след ". $form->rec->startDate);
        	}
        }
        
    	$rec = $form->rec;

    }
    
	function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec, $userId)
    {
	    if($action == 'accruals'){
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
        if($mvc->haveRightFor('accruals') && $data->rec->state == 'active') {
            
            $data->toolbar->addBtn('Начисления', array('trz_Sickdays', 'add', 'originId' => $data->rec->containerId, 'ret_url' => TRUE, ''), '');
        }
        
    }
    
    /**
     * Изпълнява се след начално установяване
     */
    static function on_AfterSetupMvc($mvc, &$res)
    {
        //Създаваме, кофа, където ще държим всички прикачени файлове на болничните листи
        $Bucket = cls::get('fileman_Buckets');
        $res .= $Bucket->createBucket('trzSickdays', 'Прикачени файлове в болнични листи', NULL, '104857600', 'user', 'user');
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
        $row->title = "Болничен лист №{$rec->fitNoteNum}";
        
        //Създателя
        $row->author = $this->getVerbal($rec, 'createdBy');
        
        //Състояние
        $row->state = $rec->state;
        
        //id на създателя
        $row->authorId = $rec->createdBy;
        
        //$row->recTitle = $rec->title;
        
        return $row;
    }
    
    function act_Accruals()
    {
    	self::requireRightFor('аccruals');
    }

}