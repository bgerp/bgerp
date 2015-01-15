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
    	$this->FLD("stage", 'key(mvc=mp_Stages,select=name,allowEmpty)', 'caption=Етап,mandatory');
    	$this->FLD("description", 'richtext(rows=2)', 'caption=Описание');
    	$this->FNC("exitResource", 'varchar', 'input,caption=Изходящ ресурс->Име,placeholder=ресурс,hint=създай нов изходящ ресурс');
    	$this->FNC('resourceQuantity', 'double', 'input,column=none,caption=Изходящ ресурс->К-во');
    	$this->FNC('toStage', 'key(mvc=mp_Stages,select=name,allowEmpty)', 'input,column=none,caption=Изходящ ресурс->Етап');
    	
    	$this->setDbUnique('bomId,stage');
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     *
     * @param core_Mvc $mvc
     * @param core_Form $form
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
    	if($form->isSubmitted()){
    		
    		// Ако ще създаваме нов изходящ ресурс, той трябва да е уникален
    		if($form->rec->exitResource){
    			if(mp_Resources::fetch(array("#title = '[#1#]'", $form->rec->exitResource))){
    				$form->setError('exitResource', 'Има вече ресурс с това име');
    			}
    			
    			if(empty($form->rec->toStage)){
    				$form->setError('toStage', 'Трябва да е избрана дестинация за изходящия ресурс');
    			}
    			
    			if(empty($form->rec->resourceQuantity)){
    				$form->setError('resourceQuantity', 'Трябва да е зададено количество');
    			}
    		}
    	}
    }
    
    
    /**
     * Извиква се след успешен запис в модела
     *
     * @param core_Mvc $mvc
     * @param int $id първичния ключ на направения запис
     * @param stdClass $rec всички полета, които току-що са били записани
     */
    public static function on_AfterSave(core_Mvc $mvc, &$id, $rec)
    {
    	if($rec->exitResource){
    		if($newResourceId = mp_Resources::save((object)array("title" => $rec->exitResource, 'type' => 'material', 'bomId' => $rec->bomId))){
    			techno2_BomStageDetails::save((object)array('bomstageId' => $rec->id, 'resourceId' => $newResourceId, 'type' => 'popResource', 'propQuantity' => 0, 'toStage' => $rec->toStage, 'propQuantity' => $rec->resourceQuantity));
    		}
    	}
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
    	$form = &$data->form;
    	 
    	if(isset($form->rec->id)){
    		$form->setReadOnly('stage');
    	}
    	
    	// Задаваме възможните етапи
    	$stages = techno2_Boms::makeStagesOptions($form->rec->bomId, $form->rec->stage);
    	 
    	if(count($stages)){
    		$form->setOptions('toStage', $stages);
    	} else {
    		$form->setReadOnly('toStage');
    	}
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
    	$dQuery->EXT('stageOrder', 'mp_Stages', 'externalName=order,externalKey=stage');
    	$dQuery->orderBy('stageOrder');
    	
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
    			$row->addBtn = ht::createLink('', array('techno2_BomStageDetails', 'add', 'bomstageId' => $dRec->id, 'ret_url' => TRUE, 'type' => 'input'), FALSE, "ef_icon=img/16/add.png,title=Добавяне на ресурс");
    			$row->addBtnRem = ht::createLink('', array('techno2_BomStageDetails', 'add', 'bomstageId' => $dRec->id, 'ret_url' => TRUE, 'type' => 'popResource'), FALSE, "ef_icon=img/16/remove-icon.png,title=Добавяне на изходен ресурс");
    			$row->addBtnRemProd = ht::createLink('', array('techno2_BomStageDetails', 'add', 'bomstageId' => $dRec->id, 'ret_url' => TRUE, 'type' => 'popProduct'), FALSE, "ef_icon=img/16/A-icon.png,title=Добавяне на изходен артикул");
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
    								'measureId'	   => 'Мярка',
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
    	if($requiredRoles == 'no_one') return;
    	
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
    	
    	if($action == 'delete' && isset($rec)){
    		
    		// Ако има поне един детайл обвързан към етапа, не може да се изтрива
    		if(techno2_BomStageDetails::fetchField("#bomstageId = {$rec->id}")){
    			$requiredRoles = 'no_one';
    		}
    		
    		// Ако текущия етап е избран като изходящ в някой от детайлите на рецептата, не може да се изтрива
    		$dQuery = techno2_BomStages::getQuery();
    		$dQuery->where("#bomId = {$rec->bomId}");
    		$dQuery->show('id');
    			
    		$query2 = techno2_BomStageDetails::getQuery();
    		$query2->in("bomstageId", arr::make(array_keys($dQuery->fetchAll()), TRUE));
    		$query2->where("#toStage = {$rec->stage}");
    			
    		if($query2->fetch()){
    			$requiredRoles = 'no_one';
    		}
    	}
    }
    
    
    /**
     * Връща разбираемо за човека заглавие, отговарящо на записа
     */
    public static function getRecTitle($rec, $escaped = TRUE)
    {
    	$rec = static::fetchRec($rec);
    	
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