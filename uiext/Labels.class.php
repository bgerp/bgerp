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
    	
    	$this->setDbUnique('docClassId,title,color');
    }
    
    
    /**
     * Подготвя формата за въвеждане на ценови правила за клиент
     */
    protected static function on_AfterPrepareEditForm($mvc, $res, $data)
    {
    	$form = &$data->form;
    	
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
    		$form->rec->title = mb_strtolower($form->rec->title);
    	}
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
    	if($action == 'delete' && isset($rec)){
    		//bp();
    	}
    }
    
    
    private static function getLabelOptions($classId)
    {
    	if(!self::$cache[$classId]){
    		$options = array();
    		$lQuery = self::getQuery();
    		$lQuery->where("#docClassId = {$classId}");
    		$lQuery->show('title,color');
    		
    		while ($lRec = $lQuery->fetch()){
    			$textColor = (phpcolor_Adapter::checkColor($lRec->color, 'dark')) ? "#fff" : "#000";
    			$opt = new stdClass();
    			$opt->title = $lRec->title;
    			
    			
    			$opt->attr = array('style' => "background-color:{$lRec->color}; color:{$textColor}");
    			
    			$options[$lRec->id] = $opt;
    		}
    		
    		self::$cache[$classId] = $options;
    	}
    	
    	return self::$cache[$classId];
    }
    
    public static function showLabels($class, $containerId, $recs, &$rows, &$listFields, $hashFields, $colName, &$tpl)
    {
    	if(!is_array($rows)) return;
    	
    	$listFields = arr::make($listFields, TRUE);
    	$listFields['_tagField'] = $colName;
    	$classId = cls::get($class)->getClassId();
    	
    	foreach ($rows as $key => $row){
    		$rec = $recs[$key];
    		$hash = self::getHash($rec, $hashFields);
    		$row->_tagField = self::renderLabel($containerId, $classId, $hash);
    	}
    	
    	$tpl->push('uiext/js/Label.js', 'JS');
    	jquery_Jquery::run($tpl, "labelActions();");
    }
    
    public static function getHash($rec, $hashFields)
    {
    	$hash = array();
    	$hashFields = arr::make($hashFields, TRUE);
    	foreach ($hashFields as $name){
    		$hash[] = $rec->{$name};
    	}
    	
    	$hash = serialize(implode('|', $hash));
    	
    	return $hash;
    }
    
    
    public static function renderLabel($containerId, $classId, $hash)
    {
    	$labels = self::getLabelOptions($classId);
    	
    	$value = NULL;
    	$selRec = uiext_DocumentLabels::fetchByDoc($containerId, $hash);
    	if($selRec){
    		$value = keylist::toArray($selRec->labels);
    		$value = key($value);
    	}
    	
    	
    	if(!Mode::isReadOnly() && !Mode::is('blank')){
    		$input = '';
    		if(uiext_DocumentLabels::haveRightFor('selectlabel', (object)array('containerId' => $containerId))){
    			$attr = array();
    			$attr['class'] = "transparentSelect selectLabel";
    			
    			//core_Request::setProtected('containerId,hash');
    			$attr['data-url']    = toUrl(array('uiext_DocumentLabels', 'saveLabels', 'containerId' => $containerId, 'hash' => $hash, 'classId' => $classId), 'local');
    			//core_Request::removeProtected('containerId,hash');
    			
    			$attr['title']       = "Избор на таг";
    	
    			$input = ht::createSelect('selTag', $labels, $value, $attr);
    			$input->removePlaces();
    			$input = $input->getContent();
    		}
    		
    		$k = "{$containerId}|{$classId}|{$hash}";
    		return "<span id='charge{$k}' style='background-color:red'>{$input}</span>";
    	}
    	
    	//bp($labels);
    }
}