<?php 


/**
 * Мениджър за шаблони, които ще се използват от документи.
 * Добавя възможността спрямо шаблона да се скриват/показват полета от мастъра
 * За целта в класа и неговите детайли трябва да се дефинира '$toggleFields',
 * където са изброени незадължителните полета, които могат да се скриват/показват.
 * Задават се във вида: "field1=caption1,field2=caption2"
 * 
 * Ако избраният мениджър има тези полета, то отдолу на формата се появява възможност за
 * избор на кои от тези незадължителни полета да се показват във въпросния шаблон. Ако никое
 * не е избрано. То се показват всичките
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
    public $title = "Изгледи на документи";
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = "Изглед";
    
    
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
     * Файл с шаблон за единичен изглед
     */
    public $singleLayoutFile = 'doc/tpl/SingleTemplateLayout.shtml';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id, name, docClassId, createdOn, createdBy, state';

    
    /**
     * Кеш на скриптовете
     * @var array
     */
    protected static $cacheScripts = array();
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('name', 'varchar', 'caption=Име, mandatory, width=100%');
        $this->FLD('docClassId', 'class(interface=doc_DocumentIntf,select=title,allowEmpty)', "caption=Документ, width=100%,mandatory,silent");
        $this->FLD('lang', 'varchar(2)', 'caption=Език,notNull,defValue=bg,value=bg,mandatory,width=2em');
        $this->FLD('content', 'text', "caption=Текст->Широк,column=none, width=100%,mandatory");
        $this->FLD('narrowContent', 'text', "caption=Текст->Мобилен,column=none, width=100%");
        $this->FLD('path', 'varchar', "caption=Файл,column=none, width=100%");
        $this->FLD('originId', 'key(mvc=doc_TplManager)', "input=hidden,silent");
        $this->FLD('hash', 'varchar', "input=none");
        $this->FLD('hashNarrow', 'varchar', "input=none");
        
        // Полета които ще се показват в съответния мениджър и неговите детайли
        $this->FLD('toggleFields', 'blob(serialize,compress)', 'caption=Полета за скриване,input=none');
        
        // Уникален индекс
        $this->setDbUnique('name');
    }
    
    
    /**
     * Подготовка на филтър формата
     */
    static function on_AfterPrepareListFilter($mvc, &$data)
    {
        $data->query->orderBy('name');
    }
    
    
    /**
     * След потготовка на формата за добавяне / редактиране
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
    	$form = &$data->form;
    	
    	// Ако шаблона е клонинг
    	if($originId = $form->rec->originId){
    		
    		// Копират се нужните данни от ориджина
    		expect($origin = static::fetch($originId));
    		$form->setDefault('docClassId', $origin->docClassId);
    		$form->setDefault('lang', $origin->lang);
    		$form->setDefault('content', $origin->content);
    		$form->setDefault('narrowContent', $origin->narrowContent);
    		$form->setDefault('toggleFields', $origin->toggleFields);
    		$form->setReadOnly('path', $origin->path);
    	} else {
    		$form->setField('path', 'input=none');
    	}
    	
    	// При смяна на документа се рефрешва формата
    	if(empty($form->rec->id)){
        	$form->setField('docClassId' , array('removeAndRefreshForm' => "lang|content|toggleFields|path"));
    	}
    	
    	// Ако има избран документ, се подготвят допълнителните полета
    	if($form->rec->docClassId){
    		$DocClass = cls::get($form->rec->docClassId); 
    		$mvc->prepareToggleFields($DocClass, $form);
    	}
    }
    
    
    /**
     * За мастър документа и всеки негов детайл се генерира поле за избор кои от
     * незадължителните му полета да се показват
     * 
     * @param core_Mvc $DocClass - класа на който е прикачен плъгина
     * @param core_Form $form - формата
     */
	private function prepareToggleFields(core_Mvc $DocClass, core_Form &$form)
    {
    	// Слагане на поле за избор на полета от мастъра
    	$this->setTempField($DocClass, $form);
    	
    	// За вски детайл (ако има) се създава поле
    	$details = arr::make($DocClass->details);
        if($details){
        	foreach ($details as $d){
        		$Dclass = cls::get($d);
        		$this->setTempField($Dclass, $form);
        	}
        }
    }
    
    
    /**
     * Ф-я създаваща FNC поле към формата за избор на кои от незадължителните му полета
     * да се показват. Използва 'toggleFields' от документа, за генериране на полетата
     * 
     * @param core_Mvc $DocClass - класа за който се създава полето
     * @param core_Form $form - формата
     */
    private function setTempField(core_Mvc $DocClass, core_Form &$form)
    {
    	// Ако са посочени незадължителни полета
    	if($DocClass->toggleFields){
    		
    		// Създаване на FNC поле със стойности идващи от 'toggleFields'
    		$fldName = ($DocClass instanceof core_Master) ? 'masterFld' : $DocClass->className;
    		$fields = array_keys(arr::make($DocClass->toggleFields));
    		$form->FNC($fldName, "set({$DocClass->toggleFields})", "caption=Полета за показване->{$DocClass->title},input,columns=3,tempFld,silent");
    		
    		// Стойност по подразбиране
    		if(isset($form->rec->$fldName)){
    			$default = $form->rec->$fldName;
    		} elseif(isset($form->rec->toggleFields) && array_key_exists($fldName, $form->rec->toggleFields)){
    			$default = $form->rec->toggleFields[$fldName];
    		} else {
    			$default = implode(',', $fields);
    		}
    		
    		$form->setDefault($fldName, $default);
    	}
    }
    
    
    /**
     * Проверка след изпращането на формата
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    { 
    	if ($form->isSubmitted()){
    		
    		// Проверка дали избрания клас поддържа 'doc_plg_TplManager'
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
    			if(empty($form->rec->id) && $origin->docClassId == $form->rec->docClassId && $new == $old){
    				$form->setWarning('content' , 'Клонирания шаблон е със същото съдържание като оригинала!');
    			}
    		}
    		
    		// Ако има временни полета, то данните се обработват
    		$tempFlds = $form->selectFields("#tempFld");
    		if(count($tempFlds)){
    			$mvc->prepareDataFld($form, $tempFlds);
    		}
    	}
    }
    
    
    /**
     * Всяко едно допълнително поле се обработва и информацията
     * от него се записва в блоб полето
     * 
     * @param core_Form $form - формата
     * @param array $fields - FNC полетата
     */
    private function prepareDataFld(core_Form &$form, $fields)
    {
    	$rec = &$form->rec;
    	
    	// За всяко едно от опционалните полета
    	$toggleFields = array();
    	foreach ($fields as $name => $fld){
    		$toggleFields[$name] = $rec->$name;
    	}
    	
    	// Подготвяне на масива за сериализиране
    	$rec->toggleFields = $toggleFields;
    }
    
    
    /**
     * Връща подадения шаблон
     * @param int $id - ид на шаблон
     * @return core_ET $tpl - шаблона
     */
    public static function getTemplate($id)
    {
    	$rec = static::fetch($id, 'content,narrowContent');
    	
    	// Ако сме в режим тесен
    	if(Mode::is('screenMode', 'narrow')){
    		
    		// И има шаблон за мобилен изглед вземаме него
    		if(!empty($rec->narrowContent)){
    			$content = $rec->narrowContent;
    		}
    	} 
    	
    	// Взимаме обикновения шаблон ако няма мобилен шаблон
    	if(empty($content)){
    		$content = $rec->content;
    	}
    	
    	$content = core_ET::loadFilesRecursivelyFromString($content);
    	
    	return new ET(tr("|*" . $content));
    }
    
    
    /**
     * Връща първия шаблон за документа на езика на ориджинина му, ако има
     * 
     * @param mixed $class  - класа
     * @param int $originId - ориджина на записа
     * @return FALSE|int    - намерения шаблон
     */
    public static function getTplByOriginLang($class, $originId)
    {
    	if(isset($originId)){
    		$origin = doc_Containers::getDocument($originId);
    		if($origin->getInstance()->hasPlugin('doc_plg_TplManager')){
    			$templateLang = doc_TplManager::fetchField($origin->fetchField('template'), 'lang');
    			$templates = doc_TplManager::getTemplates($class, $templateLang);
    			
    			return key($templates);
    		}
    	}
    	
    	return FALSE;
    }
    
    
    /**
     * Връща всички активни шаблони за посочения мениджър
     * @param int $classId - ид на клас
     * @return array $options - опции за шаблоните на документа
     */
    public static function getTemplates($classId, $lang = NULL)
    {
    	$options = array();
    	$classId = cls::get($classId)->getClassId();
    	expect(core_Classes::fetch($classId));
    	
    	// Извличане на всички активни шаблони за документа
    	$query = static::getQuery();
    	$query->where("#docClassId = {$classId}");
    	$query->where("#state = 'active'");
    	if(isset($lang)){
    		$query->where("#lang = '{$lang}'");
    	}
    	
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
    public static function addOnce($mvc, $tplArr, &$added = 0, &$updated = 0, &$skipped = 0)
    {
        $skipped = $added = $updated = 0;
    	
        foreach ($tplArr as $object){
    		
            $object['docClassId'] = $mvc->getClassId();
    		
            $object = (object)$object;
            
            // Ако има старо име на шаблона
            if($object->oldName){
                // Извличане на записа на стария шаблон
                $exRec = static::fetch("#name = '{$object->oldName}'");
            } else {
                $exRec = NULL;
            }
            
            // Ако няма старо име проверка имали шаблон с текущото име
            if(!$exRec){
                $exRec = static::fetch("#name = '{$object->name}'");
            }
            
            if($exRec){
                $object->id = $exRec->id;
            }

            // Ако файла на шаблона не е променян, то записа не се обновява
            expect($object->hash = md5_file(getFullPath($object->content)));
            
            if($object->narrowContent){
            	expect($object->hashNarrow = md5_file(getFullPath($object->narrowContent)));
            }
            
            if($exRec && ($exRec->name == $object->name) && ($exRec->hashNarrow == $object->hashNarrow)  && ($exRec->hash == $object->hash) && ($exRec->lang == $object->lang) && ($exRec->toggleFields == $object->toggleFields) && ($exRec->path == $object->content)){
                $skipped++;
                continue;
            }
			
            $object->path = $object->content;
            $object->content = getFileContent($object->content);
            if($object->narrowContent){
            	$object->narrowContent = getFileContent($object->narrowContent);
            }
            $object->createdBy = -1;
            $object->state = 'active';
            
            static::save($object);

            ($object->id) ? $updated++ : $added++;
        }
        
        $class = ($added > 0 || $updated > 0) ? ' class="green"' : '';

    	$res = "<li{$class}>Добавени са {$added} шаблона за " . mb_strtolower($mvc->title) . ", обновени са {$updated}, пропуснати са {$skipped}</li>";

        return $res;
    }
    
    
    /**
     * Извиква се преди вкарване на запис в таблицата на модела
     */
    public static function on_BeforeSave(&$invoker, &$id, &$rec, &$fields = NULL)
    {
    	// Ако записа е вкаран от сетъпа променяме за модифициран от да е @system
    	if($rec->_modifiedBy){
    		$rec->modifiedBy = $rec->_modifiedBy;
    	}
    }
    
    
    /**
     * След подготовка на единичния изглед
     */
	public static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
    	$data->toolbar->addBtn('Всички', array('doc_TplManager', 'list'), 'caption=Всички шаблони,ef_icon=img/16/view.png');
    	
    	// Добавяне на бутон за клониране
    	if($mvc->haveRightFor('add')){
    		$data->toolbar->addBtn('Клониране', array('doc_TplManager', 'add', 'originId' => $data->rec->id), 'ef_icon=img/16/copy16.png,title=Клониране на шаблона');
    	}
    }
    
    
	/**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
    {
    	if($res == 'no_one') return;
    	
    	if($action == 'delete' && isset($rec)){
    		// Ако шаблона е използван в някой документ, не може да се трие
    		if(cls::get($rec->docClassId)->fetch("#template = {$rec->id}")){
    			$res = 'no_one';
    		}
    	}
    	
    	if(($action == 'edit') && isset($rec)){
    		if($rec->createdBy == -1){
    			$res = 'no_one';
    		}
    	}
    }
    
    
    /**
     * Връща скриптовия клас на шаблона (ако има)
     * 
     * @param int $templateId - ид на шаблона
     * @return mixed $Script/False - заредения клас, или FALSE ако не може да се зареди
     */
    public static function getTplScriptClass($templateId)
    {
    	// Ако няма шаблон
    	if(!$templateId) return FALSE;
    	
    	// Ако има кеширан в хита резултат за скрипта, връща се той
    	if(isset(static::$cacheScripts[$templateId])) return static::$cacheScripts[$templateId];
    	
    	// Намираме пътя на файла генерирал шаблона
    	$filePath = doc_TplManager::fetchField($templateId, 'path');
    	
    	if(!$filePath) return FALSE;
    	
    	$filePath = str_replace(".shtml", '.class.php', $filePath);
    	
    	// Ако физически съществува този файл
    	if(getFullPath($filePath)){
    		$supposedClassname = str_replace("/", '_', $filePath);
    		$supposedClassname = str_replace(".class.php", '', $supposedClassname);
    		
    		// Опитваме се да го заредим.
    		// Трябва и да е наследник на 'doc_TplScript'
    		if(cls::load($supposedClassname, TRUE) && is_subclass_of($supposedClassname, 'doc_TplScript')){
    			
    			// Зареждаме го
    			$Script = cls::get($supposedClassname);
    			
    			// Връщаме заредения клас
    			return $Script;
    		}
    	}
    	
    	// Ако не е открит такъв файл
    	return FALSE;
    }
}  