<?php


/**
 * Мениджър на служебни кодове
 *
 * @category  bgerp
 * @package   crm
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     0.12
 */
class crm_ext_EmployeeCodes extends core_Manager
{
	
	
	/**
     * Заглавие
     */
    public $title = 'Кодове на служители';

    
    /**
     * Единично заглавие
     */
    public $singleTitle = 'Код на служител';
    
    
    /**
     * Плъгини и MVC класове, които се зареждат при инициализация
     */
    public $loadList = 'crm_Wrapper,plg_Rejected,plg_RowTools2';
    
    
    /**
     * Текущ таб
     */
    public $currentTab = 'Лица';
    
    
    /**
     * Кой може да редактира
     */
    public $canEdit = 'powerUser';


    /**
     * Кой може да изтрива
     */
    public $canDelete = 'no_one';
    
    
    /**  
     * Предлог в формата за добавяне/редактиране  
     */  
    public $formTitlePreposition = 'на';  
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('personId', 'key(mvc=crm_Persons)', 'input=hidden,silent,mandatory');
        $this->FLD('code', 'varchar', 'caption=Код');
        $this->FLD('state', 'enum(active=Активен,rejected=Оттеглен)', 'input=none,notNull,value=active');
        
        $this->setDbUnique('code');
    }
    
    
    /**
     * След подготовката на заглавието на формата
     */
    protected static function on_AfterPrepareEditTitle($mvc, &$res, &$data)
    {
    	$rec = $data->form->rec;
    	$data->form->title = core_Detail::getEditTitle('crm_Persons', $rec->personId, $mvc->singleTitle, $rec->id, $formTitlePreposition);
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
    	$rec = $form->rec;
    	
    	if($form->isSubmitted()){
    		if (!preg_match('/\p{L}/', $rec->code)) {
    			$form->setError('code', 'Трябва в кода да има поне една буква');
    		}
    		
    		if(!$form->gotErrors()){
    			$rec->code = strtoupper($rec->code);
    		}
    	}
    }
    
    
    /**
     * Подготвя информацията
     *
     * @param stdClass $data
     */
    public function prepareData(&$data)
    {
    	$rec = self::fetch("#personId = {$data->masterId} AND #state = 'active'");
    	
    	if(!empty($rec)){
    		$data->codeRec = $rec;
    		$row = self::recToVerbal($rec);
    		
    		$tpl = new core_ET("<span style='position:relative;top:4px'>[#1#]</span>");
    		$row->_rowTools = $tpl->append($row->_rowTools->renderHtml(), '1');
    		$data->codeRow = $row;
    	}
    }
    
    
    /**
     * Рендира информацията
     * 
     * @param stdClass $data
     * @return core_ET;
     */
    public function renderData($data)
    {
    	 $tpl = new core_ET("[#resTitle#]:<!--ET_BEGIN code--> <b>[#code#]</b><!--ET_END code-->[#btn#]");
    	 
    	 if(isset($data->codeRow)){
    	 	$tpl->append(tr('Служебен код'), 'resTitle');
    	 	$tpl->append($data->codeRow->code, 'code');
    	 	$tpl->append($data->codeRow->_rowTools, 'btn');
    	 } else {
    	 	$tpl->append(tr('Все още няма код'), 'resTitle');
    	 }
    	 
    	 if(isset($data->addResourceUrl)){
    	 	$link = ht::createLink('', $data->addResourceUrl, FALSE, "title=Добавяне на служебен код,ef_icon=img/16/add.png");
    	 	$tpl->append($link, 'btn');
    	 }
    	 $tpl->removeBlocks();
    	 
    	 return $tpl;
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите
     */
    protected static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
    {
    	if(($action == 'add' || $action == 'delete' || $action == 'edit') && isset($rec->personId)){
    		if(!crm_Persons::haveRightFor('edit', $rec->personId)){
    			$res = 'no_one';
    		}
    		
    		if($res != 'no_one'){
    			$employeeId = crm_Groups::getIdFromSysId('employees');
    			if(!keylist::isIn($employeeId, crm_Persons::fetchField($rec->personId, 'groupList'))){
    				$res = 'no_one';
    			}
    		}
    	}
    	
    	if($action == 'add' && isset($rec->personId)){
    		if($mvc->fetchField("#personId = {$rec->personId} AND #state = 'active'")){
    			$res = 'no_one';
    		}
    	}
    }
}