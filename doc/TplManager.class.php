<?php 


/**
 * Мениджър за шаблони, които ще се използват от документи
 *
 *
 * @category  bgerp
 * @package   doc
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class doc_TplManager extends core_Master
{
    
    
    /**
     * Заглавие
     */
    public $title = "Мениджър на шаблони";
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = "Шаблон";
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created, plg_SaveAndNew, plg_Modified, doc_Wrapper, plg_RowTools, plg_State2';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    public $rowToolsSingleField = 'name';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo,admin';
    
    
    /**
     * Кой може да пише?
     */
    public $canWrite = 'ceo,admin';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,admin';


    /**
     * Кой може да го изтрива?
     */
    public $canDelete = 'ceo,admin';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'ceo,admin';

	
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo,admin';


    /**
     * Кой има право да променя системните данни?
     */
    public $canEditsysdata = 'ceo,admin';

    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id, name, docClassId, createdBy, modifiedOn, modifiedBy, state';

    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('name', 'varchar', 'caption=Име, mandatory, width=100%');
        $this->FLD('docClassId', 'class(interface=doc_DocumentIntf,select=title,allowEmpty)', "caption=Мениджър, width=100%,mandatory");
        $this->FLD('lang', 'varchar(2)', 'caption=Език,notNull,defValue=bg,value=bg,mandatory,autoFilter,width=2em');
        $this->FLD('content', 'text', "caption=Текст,column=none, width=100%,mandatory");
        $this->FLD('originId', 'key(mvc=doc_TplManager)', "input=hidden,silent");
        
        // Уникален индекс
        $this->setDbUnique('name');
    }
    
    
    /**
     * След потготовка на формата за добавяне / редактиране.
     * 
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    function on_AfterPrepareEditForm($mvc, &$data)
    {
    	$form = &$data->form;
    	
    	if($originId = $form->rec->originId){
    		expect($origin = static::fetch($originId));
    		$form->setDefault('docClassId', $origin->docClassId);
    		$form->setDefault('lang', $origin->lang);
    		$form->setDefault('content', $origin->content);
    	}
    }
    
    
    /**
     * Проверка след изпращането на формата
     */
    function on_AfterInputEditForm($mvc, $form)
    { 
    	if ($form->isSubmitted()){
    		if($originId = $form->rec->originId){
    			$origin = static::fetch($originId);
    			$new = preg_replace("/\s+/", "", $form->rec->content);
    			$old = preg_replace("/\s+/", "", $origin->content);
    			
    			if($origin->docClassId == $form->rec->docClassId && $new == $old){
    				$form->setWarning('content' , 'Клонирания шаблон е със същото съдържание като оригинала !');
    			}
    		}
    	}
    }
    
    
    /**
     * Връща подадения шаблон
     * @param int $id - ид на шаблон
     * @return core_ET $tpl - шаблона
     */
    public static function getTemplate($id)
    {
    	expect($rec = static::fetch($id));
    	
    	return new ET(tr("|*" . $rec->content));
    }
    
    
    /**
     * Връща всички активни шаблони за посочения мениджър
     * @param int $classId - ид на клас
     * @return array $options - опции за шаблоните на документа
     */
    public static function getTemplates($classId)
    {
    	expect(core_Classes::fetch($classId));
    	
    	$options = array();
    	$query = static::getQuery();
    	$query->where("#docClassId = {$classId}");
    	$query->where("#state = 'active'");
    	
    	while($rec = $query->fetch()){
    		$options[$rec->id] = $rec->name;
    	}
    	
    	ksort($options);
    	
    	return $options;
    }
    
    
    /**
     * Добавя шаблон
     * @param mixed $object - Обект или масив
     * @param int $added - брой добавени шаблони
     * @param int $updated - брой обновени шаблони
     */
    public static function add($object, &$added = 0, &$updated = 0)
    {
    	$object = (object)$object;
    	$exRec = static::fetch("#name = '{$object->name}'");
    	$object->id = ($exRec) ? $exRec->id : NULL;
    	$object->modifiedBy = ($exRec) ? $exRec->modifiedBy : NULL;
    	
    	// Ако се обновява шаблон създаден от system но модифициран от потребител - той не се обновява
    	if($object->id && $object->modifiedBy != -1) return;
    	
    	$object->content = getFileContent($object->content);
    	$object->createdBy = -1;
    	$object->state = 'active';
    	$object->_modifiedBy = -1;
    	
    	static::save($object);
    	($object->id) ? $updated++ : $added++;
    }
    
    
    /**
     * Извиква се преди вкарване на запис в таблицата на модела
     */
    function on_BeforeSave(&$invoker, &$id, &$rec, &$fields = NULL)
    {
    	// Ако записа е вкаран от сетъпа променяме за модифициран от да е @system
    	if($rec->_modifiedBy){
    		$rec->modifiedBy = $rec->_modifiedBy;
    	}
    }
    
    
    /**
     * След подготовка на единичния изглед
     */
	function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
    	// Добавяне на бутон за клониране
    	$data->toolbar->addBtn('Клонирай', array('doc_TplManager', 'add', 'originId' => $data->rec->id), 'ef_icon=img/16/copy16.png');
    }
    
    
	/**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
    {
    	if($action == 'delete' && isset($rec)){
    		if(cls::get($rec->docClassId)->fetch("#template = {$rec->id}")){
    			$res = 'no_one';
    		}
    	}
    }
}         