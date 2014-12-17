<?php


/**
 * Мениджър за технологични карти
 *
 *
 * @category  bgerp
 * @package   techno
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class techno2_Maps extends core_Master
{
   
	
   /**
     * Какви интерфейси поддържа този мениджър
     */
    //var $interfaces = '';
    
    
    /**
     * Заглавие на мениджъра
     */
    var $title = "Технологични карти";
    
    
    /**
     * Неща, подлежащи на начално зареждане
     */
    var $loadList = 'plg_RowTools, plg_Printing, techno2_Wrapper, plg_Sorting, doc_DocumentPlg, acc_plg_DocumentSummary, doc_ActivatePlg';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = "tools=Пулт,originId=Спецификация,createdOn,createdBy,modifiedOn,modifiedBy";
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'tools';
    
    
    /**
     * Детайла, на модела
     */
    var $details = 'techno2_MapDetails';
    
    
    /**
     * Заглавие на единичен документ
     */
    var $singleTitle = 'Технологична карта';
    
    
    /**
     * Икона на единичния изглед
     */
    var $singleIcon = 'img/16/legend.png';
    
    
    /**
     * Абревиатура
     */
    var $abbr = "Tmp";
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'techno,ceo';
    
    
    /**
     * Кой може да пише?
     */
    var $canWrite = 'techno,ceo';
    
    
    /**
     * Кой може да го контира?
     */
    var $canConto = 'techno,ceo';
    
    
    /**
     * Кой може да го отхвърли?
     */
    var $canReject = 'techno,ceo';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'ceo,techno';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    var $canSingle = 'ceo,techno';
    
    
    /**
     * Файл с шаблон за единичен изглед на статия
     */
    var $singleLayoutFile = 'techno2/tpl/SingleLayoutMap.shtml';
    
    
    /**
     * Поле за филтриране по дата
     */
    public $filterDateField = 'createdOn';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
    	$this->FLD('notes', 'richtext(rows=4)', 'caption=Забележки');
    	$this->FLD('state','enum(draft=Чернова, active=Активиран, rejected=Оттеглен)', 'caption=Статус, input=none');
    }
    
    
    /**
     * Подготовка на бутоните на формата за добавяне/редактиране
     */
    public static function on_AfterPrepareEditToolbar($mvc, &$res, $data)
    {
    	// Документа не може да се създава  в нова нишка, ако е възоснова на друг
    	if(!empty($data->form->toolbar->buttons['btnNewThread'])){
    		$data->form->toolbar->removeBtn('btnNewThread');
    	}
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
    {
    	if($action == 'add' && isset($rec)){
    		
    		// Може да се добавя само ако има ориджин
    		if(empty($rec->originId)){
    			$res = 'no_one';
    		}
    	}
    }
    
    
    /**
     * Проверка дали нов документ може да бъде добавен в посочената нишка
     *
     * @param int $threadId key(mvc=doc_Threads)
     * @return boolean
     */
    public static function canAddToThread($threadId)
    {
    	if($originId = Request::get('originId', 'int')){
    		$origin = doc_Containers::getDocument($originId);
    		expect($origin->getInstance() instanceof techno2_SpecificationDoc);
    		
    		return TRUE;
    	}
    		
    	return FALSE;
    }
    
    
    /**
     * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
     */
    function getDocumentRow($id)
    {
    	$rec = $this->fetch($id);
    	
    	$row = new stdClass();
    	$row->title = $this->getRecTitle($rec);
    	$row->authorId = $rec->createdBy;
    	$row->author = $this->getVerbal($rec, 'createdBy');
    	$row->state = $rec->state;
    	$row->recTitle = $rec->title;
    	
    	return $row;
    }
    
    
    /**
     * Връща разбираемо за човека заглавие, отговарящо на записа
     */
    public static function getRecTitle($rec, $escaped = TRUE)
    {
    	$self = cls::get(__CLASS__);
    
    	return "{$self->singleTitle} №{$rec->id}";
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	$row->header = $mvc->singleTitle . " №<b>{$row->id}</b> ({$row->state})" ;
    	 
    	$origin = doc_Containers::getDocument($rec->originId);
    	$row->originId = $origin->getHyperLink(TRUE);
    }
}