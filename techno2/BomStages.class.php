<?php



/**
 * Мениджър на етапи детайл на технологична рецепта, всеки детайл също може да има детайл
 *
 *
 * @category  bgerp
 * @package   techno
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class techno2_BomStages extends core_Master
{
    
	
    /**
     * Заглавие
     */
    var $title = "Етапи технологичните рецепти";
    
    
    /**
     * Наименование на единичния обект
     */
    var $singleTitle = 'Етап';
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    var $masterKey = 'bomId';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_RowTools, techno2_Wrapper';
    
    
    /**
     * По кое поле да се групират записите
     */
    var $groupByField = 'stageId';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'tools';
    
    
    /**
     * Активен таб
     */
    var $currentTab = 'Рецепти';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'ceo,techno';
    
    
    /**
     * Кой има право да чете?
     */
    var $canSingle = 'no_one';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'ceo,techno';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'ceo,techno';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'no_one';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'ceo,techno';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
    	$this->FLD('bomId', 'key(mvc=techno2_Boms)', 'column=none,input=hidden,silent,oldFieldName=mapId');
    	$this->FLD("stage", 'varchar', 'caption=Етап');
    	//$this->FLD('departmentId', 'key(mvc=hr_Departments,select=name,allowEmpty)', 'caption=Център на дейност,mandatory');
    	
    	$this->setDbUnique('bomId,stage');
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
    	$suggestions = cls::get('mp_Stages')->makeArray4Select('name');
    	$suggestions = arr::make($suggestions, TRUE);
    	$data->form->setSuggestions('stage', array('' => '') + $suggestions);
    }
    
    
    /**
     * Подготвя показването на етапите в технологична рецепта
     * 
     * @param stdClass $data
     * @return void
     */
    public function prepareStages($data)
    {
    	$data->recs = $data->rows = $data->bomStageDetailRows = array();
    	
    	// Намираме тези записи обвързани с рецептата
    	$dQuery = $this->getQuery();
    	$dQuery->where("#bomId = '{$data->masterId}'");
    	
    	$count = 1;
    	while($dRec = $dQuery->fetch()){
    		$data->recs[$dRec->id] = $dRec;
    		$row = $this->recToVerbal($dRec);
    		$row->num = cls::get('type_Int')->toVerbal($count);
    		
    		// За всекид детайл, подготвяме неговите детайли
    		$nData = new stdClass();
    		$nData->masterMvc = $this;
    		$nData->masterId = $dRec->id;
    		$nData->masterData = $dRec;
    		
    		$detailData = cls::get('techno2_BomStageDetails')->prepareDetail($nData);
    		
    		// Добавяме бутон за добавяне на детайл към този клас
    		if(techno2_BomStageDetails::haveRightFor('add', (object)array('bomstageId' => $dRec->id))){
    			$row->addBtn = ht::createLink('', array('techno2_BomStageDetails', 'add', 'bomstageId' => $dRec->id, 'ret_url' => TRUE), FALSE, "ef_icon=img/16/add.png,title=Добавяне на ресурс");
    		}
    		
    		$data->bomStageDetailRows[$dRec->id] = $detailData->rows;
    		$data->rows[$dRec->id] = $row;
    		$count++;
    	}
    	
    	// Бутон за добавяне на нов етап към рецептата
    	if($this->haveRightFor('add', (object)array('bomId' => $data->masterId))){
    		$data->addUrl = array($this, 'add', 'bomId' => $data->masterId, 'ret_url' => TRUE);
    	}
    }
    
    
    /**
     * Рендира показването на детайлите на рецептата
     * 
     * @param stdClass $data
     * @return core_ET $tpl
     */
    public function renderStages($data)
    {
    	// Взимаме шаблона
    	$tpl = getTplFromFile("techno2/tpl/SingleBomStages.shtml");
    	
    	// Ако има записи, обхождаме ги
    	if(count($data->rows)){
    		
    		// Поставяме ги в шаблона
    		foreach ($data->rows as $id => $row){
    			$blockTpl = clone $tpl->getBlock('ROW');
    			$blockTpl->placeObject($row);
    			
    			$table = cls::get('core_TableView', array('mvc' => cls::get('techno2_BomStageDetails')));
    			$listFields = array('RowNumb'        => 'Пулт', 
    							    'resourceId'     => 'Ресурс',
    								'baseQuantity' => 'Начално к-во',
    							    'propQuantity' => 'Пропор. к-во',
    			);
    			
    			// Ако сумите на крайното салдо са отрицателни - оцветяваме ги
    			$details = $table->get($data->bomStageDetailRows[$id], $listFields);
    			$blockTpl->replace($details, 'TABLE');
    			$blockTpl->removeBlocks();
    			$blockTpl->append2Master();
    		}
    	} else {
    		$tpl->prepend("Рецептата е празна<br/>");
    	}
    	
    	// Добавяне на бутон за добавяне на нов етап
    	if(isset($data->addUrl)){
    		$btn = ht::createBtn('Нов етап', $data->addUrl, FALSE, FALSE, "title=Добавяне на нов етап,ef_icon=img/16/star_2.png");
    	 	$tpl->replace($btn, 'ADD_BTN');
    	}
    	
    	// Връщаме шаблона
    	return $tpl;
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * @param core_Mvc $mvc
     * @param string $requiredRoles
     * @param string $action
     * @param stdClass $rec
     * @param int $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
    	if(($action == 'add' || $action == 'edit' || $action == 'delete') && isset($rec)){
    		
    		// Ако няма ид на рецепта, никой няма права
    		if(empty($rec->bomId)){
    			$requiredRoles = 'no_one';
    		} else {
    			
    			// Ако има рецепта и тя не е чернова забраняваме действията
    			$masterState = techno2_Boms::fetchField($rec->bomId, 'state');
    			
    			if($masterState != 'draft'){
    				$requiredRoles = 'no_one';
    			}
    		}
    	}
    }
    
    
    /**
     * Връща разбираемо за човека заглавие, отговарящо на записа
     */
    public static function getRecTitle($rec, $escaped = TRUE)
    {
    	return techno2_Boms::getRecTitle(techno2_Boms::fetch($rec->bomId), $escaped);
    }
    
    
    /**
     * Връща URL към единичния изглед на мастера
     */
    public function getRetUrl($rec)
    {
    	$url = array('techno2_Boms', 'single', $rec->bomId);
    
    	return $url;
    }
    
    
    /**
     * След обръщане на записа във вербален вид
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	if(empty($rec->stage)){
    		$row->stage = tr("< |без етап|* >");
    	}
    }
    
    
    /**
     * Пренасочва URL за връщане след запис към сингъл изгледа
     */
    public static function on_AfterPrepareRetUrl($mvc, $res, $data)
    {
    	// Рет урл-то не сочи към мастъра само ако е натиснато 'Запис и Нов'
    	if (isset($data->form) && ($data->form->cmd === 'save' || is_null($data->form->cmd))) {
    
    		// Променяма да сочи към single-a
    		$data->retUrl = toUrl(array('techno2_Boms', 'single', $data->form->rec->bomId));
    	}
    }
}