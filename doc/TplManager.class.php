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
    public $listFields = 'id, name, docClassId, createdOn, createdBy, modifiedOn, modifiedBy, state';

    
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
        $this->FLD('hash', 'varchar', "input=none");
        
        // Уникален индекс
        $this->setDbUnique('name');
    }
    
    
    /**
     * След потготовка на формата за добавяне / редактиране
     */
    function on_AfterPrepareEditForm($mvc, &$data)
    {
    	$form = &$data->form;
    	
    	// Ако шаблона е клонинг
    	if($originId = $form->rec->originId){
    		
    		// Копират се нужните данни от ориджина
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
    		
    		$plugins = cls::get($form->rec->docClassId)->getPlugins();
    		if(empty($plugins['doc_plg_TplManager'])){
    			$form->setError('docClassId', "Избрания клас трябва да поддържа 'doc_plg_TplManager'!");
    		}
    		
    		// Ако шаблона е клонинг
    		if($originId = $form->rec->originId){
    			
    			$origin = static::fetch($originId);
    			$new = preg_replace("/\s+/", "", $form->rec->content);
    			$old = preg_replace("/\s+/", "", $origin->content);
    			
    			// Ако клонинга е за същия документ като ориджина, и няма промяна
    			// в съдържанието се слага предупреждение
    			if($origin->docClassId == $form->rec->docClassId && $new == $old){
    				$form->setWarning('content' , 'Клонирания шаблон е със същото съдържание като оригинала!');
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
    	expect($content = static::fetchField($id, 'content'));
    	
    	return new ET(tr("|*" . $content));
    }
    
    
    /**
     * Връща всички активни шаблони за посочения мениджър
     * @param int $classId - ид на клас
     * @return array $options - опции за шаблоните на документа
     */
    public static function getTemplates($classId)
    {
    	$options = array();
    	expect(core_Classes::fetch($classId));
    	
    	// Извличане на всички активни шаблони за документа
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
     * 
     * @param mixed $object - Обект или масив
     * @param int $added - брой добавени шаблони
     * @param int $updated - брой обновени шаблони
     * @param int $skipped - брой пропуснати шаблони
     */
    public static function addOnce($object, &$added = 0, &$updated = 0, &$skipped = 0)
    {
    	$object = (object)$object;
    	
    	// Ако има вече такъв запис
    	$exRec = static::fetch("#name = '{$object->name}'");
    	if($exRec){
    		$object->id = $exRec->id;
    		$object->hash = $exRec->hash;
    		$object->modifiedBy = $exRec->modifiedBy;
    	}
    	
    	// Ако системен шаблон модифициран от потрбеителя, той не се обновява
    	if($object->id && $object->modifiedBy != -1) return;
    	
    	// Ако файла на шаблона не е променян, то записа не се обновява
    	$fileHash = md5_file(getFullPath($object->content));
    	if(isset($object->hash) && $object->hash == $fileHash){
    		$skipped++;
    		return;
    	}
    	
    	$object->hash = $fileHash;
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
    	if($mvc->haveRightFor('add')){
    		$data->toolbar->addBtn('Клониране', array('doc_TplManager', 'add', 'originId' => $data->rec->id), 'ef_icon=img/16/copy16.png,title=Клонирай шаблона');
    	}
    }
    
    
	/**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
    {
    	if($action == 'delete' && isset($rec)){
    		
    		// Ако шаблона е използван в някой документ, не може да се трие
    		if(cls::get($rec->docClassId)->fetch("#template = {$rec->id}")){
    			$res = 'no_one';
    		}
    	}
    }
}         