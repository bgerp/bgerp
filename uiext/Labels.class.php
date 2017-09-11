<?php



/**
 * Клас 'uiext_Labels'
 *
 * Мениджър за тагове на документите
 *
 * @category  bgerp
 * @package   uiext
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class uiext_Labels extends core_Manager
{
	
	
    /**
     * Заглавие
     */
    public $title = 'Тагове';


    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Таг';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, plg_Created, uiext_Wrapper, plg_State2, plg_SaveAndNew';
    
    
    /**
     * Кой има право да гледа списъка?
     */
    public $canList = 'uiext, admin, ceo';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'uiext, admin, ceo';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'uiext, admin, ceo';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'uiext, admin, ceo';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'docClassId,title,color,state';
    
    
    /**
     * Работен кеш
     */
    protected static $cache = array();
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
    	$this->FLD('docClassId', 'class(interfaces=d,select=title,allowEmpty)', 'caption=Клас, mandatory,remember');
    	$this->FLD('title', 'varchar', 'caption=Заглавие, mandatory');
    	$this->FLD('color', 'color_Type()', 'caption=Фон, mandatory,tdClass=rightCol');
    	
    	$this->setDbUnique('docClassId,title');
    }
    
    
    /**
     * Подготвя формата за въвеждане на ценови правила за клиент
     */
    protected static function on_AfterPrepareEditForm($mvc, $res, $data)
    {
    	$form = &$data->form;
    	
    	// Определяне на кои класове са допустими за избор
    	$options = core_Classes::getOptionsByInterface('doc_DocumentIntf', 'title');
    	$options += core_Classes::getOptionsByInterface('frame2_ReportIntf', 'title');
    	$options += core_Classes::getOptionsByInterface('frame_ReportSourceIntf', 'title');
    	$form->setOptions('docClassId', $options);
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
    	if(isset($form->rec->title)){
    		$form->rec->title = str::mbUcfirst($form->rec->title);
    	}
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
    	if($action == 'delete' && isset($rec)){
    		if(uiext_DocumentLabels::fetch("#labels LIKE '%|{$rec->id}|%'")){
    			$requiredRoles = 'no_one';
    		}
    	}
    }
    
    
    /**
     * Връща наличните опции за избор
     * 
     * @param int $classId
     * @return array 
     */
    private static function getLabelOptions($classId)
    {
    	if(!self::$cache[$classId]){
    		$options = array();
			$opt = new stdClass();
			$opt->title = "";
			$opt->attr = array('style' => "background-color:#fff; color:#000");
			$options[""] = $opt;

    		$lQuery = self::getQuery();
    		$lQuery->where("#docClassId = {$classId}");
    		$lQuery->where('#state != "closed"');
    		$lQuery->show('title,color');
    		
    		while ($lRec = $lQuery->fetch()){
    			$textColor = (phpcolor_Adapter::checkColor($lRec->color, 'dark')) ? "#fff" : "#000";
    			$opt = new stdClass();
    			$opt->title = $lRec->title;
    			
    			$opt->attr = array('style' => "background-color:{$lRec->color}; color:{$textColor}", 'data-color' => "{$lRec->color}", 'data-text' => "{$textColor}");
    			$options[$lRec->id] = $opt;
    		}
    		
    		self::$cache[$classId] = $options;
    	}
    	
    	return self::$cache[$classId];
    }
    
    
    /**
     * Помощен метод за показване на таговете
     * 
     * @param int $class                - за кой клас
     * @param int $containerId          - ид на контейнера
     * @param array $recs               - масив със записите
     * @param array $rows               - масив със вербалните записи
     * @param array $listFields         - колонките
     * @param array $hashFields         - кои полета ще служат за хеш
     * @param varchar $colName          - Как ще се казва колонката за избора на тагове
     * @param core_ET $tpl              - шаблон за рендиране
     * @param core_FieldSet &$fieldset  - шаблон за рендиране
     * @param void
     */
    public static function showLabels($class, $containerId, $recs, &$rows, &$listFields, $hashFields, $colName, &$tpl, core_FieldSet &$fieldset)
    {
    	if(!is_array($rows)) return;
    	if(Mode::isReadOnly() || Mode::is('blank')) return;
    	$fieldset->FLD('_tagField', 'varchar', 'tdClass=tagColumn small-field');
    	
    	$listFields = arr::make($listFields, TRUE);
    	$listFields['_tagField'] = $colName;
    	$classId = cls::get($class)->getClassId();
    	
    	// Генериране на таговете на документа
    	foreach ($rows as $key => $row){
    		$rec = $recs[$key];
    		$hash = self::getHash($rec, $hashFields);
    		$row->_tagField = self::renderLabel($containerId, $classId, $hash);
    	}
    }
    
    
    /**
     * Активира нужните файлове за таговете
     * 
     * @param core_ET $tpl
     * @return void
     */
    public static function enable(&$tpl)
    {
    	// Зареждане на нужните файлове
    	if(core_Packs::isInstalled('uiext')){
    		$tpl->push('uiext/js/Label.js', 'JS');
    		jquery_Jquery::run($tpl, "labelActions();");
    	}
    }
    
    
    /**
     * Хеша на документа, който трябва да е уникален за записа
     * 
     * @param stdClass $rec     - запис
     * @param mixed $hashFields - полета за хеш
     * @return string $hash     - хеш
     */
    public static function getHash($rec, $hashFields)
    {
    	$hash = array();
    	$hashFields = arr::make($hashFields, TRUE);
    	foreach ($hashFields as $name){
    		$hash[] = $rec->{$name};
    	}
    	
    	$hash = md5(implode('|', $hash));
    	
    	return $hash;
    }
    
    
    /**
     * Рендиране на таговете на документа
     * 
     * @param int $containerId - ид на контейнера
     * @param int $classId     - ид на класа, от който ще се избират таговете
     * @param varchar $hash    - хеш на реда
     * @return text            - инпута за избор на тагове
     */
    public static function renderLabel($containerId, $classId, $hash)
    {
    	$labels = self::getLabelOptions($classId);
    	if(count($labels) <= 1) return NULL;
    	
    	// Връщане
    	$value = NULL;
    	$selRec = uiext_DocumentLabels::fetchByDoc($containerId, $hash);
    	if($selRec){
    		$value = keylist::toArray($selRec->labels);
    		$value = key($value);
    	}
    	
    	$input = '';
    	if(uiext_DocumentLabels::haveRightFor('selectlabel', (object)array('containerId' => $containerId))){
    		$attr = array();
    		$attr['class'] = "transparentSelect selectLabel";
    			
    		//core_Request::setProtected('containerId,hash');
    		$attr['data-url'] = toUrl(array('uiext_DocumentLabels', 'saveLabels', 'containerId' => $containerId, 'hash' => $hash, 'classId' => $classId), 'local');
    		//core_Request::removeProtected('containerId,hash');
    		$attr['title'] = "Избор на таг";
    	
    		$input = ht::createSelect('selTag', $labels, $value, $attr);
    		$input->removePlaces();
    		$input = $input->getContent();
    	} else {
    		if(!empty($value)){
    			$input = cls::get('uiext_DocumentLabels')->getFieldType('labels')->toVerbal($value);
    		}
    	}
    		
    	$k = "{$containerId}|{$classId}|{$hash}";
    	
    	return "<span id='charge{$k}'>{$input}</span>";
    }
}