<?php



/**
 * Модел "Анкетни отговори"
 *
 *
 * @category  bgerp
 * @package   survey
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class survey_Alternatives extends core_Detail {
    
    
    /**
     * Заглавие
     */
    var $title = 'Въпроси';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_RowTools, survey_Wrapper, plg_SaveAndNew,options=survey_Options,plg_Clone';
    
  
    /**
	 * Мастър ключ към дъските
	 */
	var $masterKey = 'surveyId';
	
	
    /**
     * Кои полета да се показват в листовия изглед
     */
    var $listFields = 'tools=Пулт, surveyId, label, image';
    
    
    /**
	 *  Брой елементи на страница 
	 */
	var $listItemsPerPage = "70";
	
	
    /**
     * Наименование на единичния обект
     */
    var $singleTitle = "Въпрос";
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'tools';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'survey, ceo, admin';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	var $canList = 'survey, ceo, admin';
    
    
    /**
     * Кой може да пише?
     */
    var $canWrite = 'survey, ceo, admin';
    
    
    /**
     * Кой таб да бъде отворен
     */
    var $currentTab = 'Въпроси';
	
    
     /**
     * Описание на модела (таблицата)
     */
    function description()
    {
    	$this->FLD('surveyId', 'key(mvc=survey_Surveys, select=title)', 'caption=Тема, input=hidden, silent');
		$this->FLD('label', 'varchar(64)', 'caption=Въпрос, mandatory');
		$this->FLD('image', 'fileman_FileType(bucket=survey_Images)', 'caption=Картинка');
    }
    
    
    /**
     * Подготовка на Детайлите
     */
    function prepareDetail_($data)
    {
    	/*
    	 * Рендираме резултатите вместо въпросите в следните случаи:
    	 * В режим за "обобщение" сме и имаме права да обобщаваме,
    	 * или Анкетата е изтекла
    	 */
    	if((Request::get('summary') && 
    		survey_Surveys::haveRightFor('summarise', $data->masterId))
    		|| survey_Surveys::isClosed($data->masterId)) {
	    	$data->rec = survey_Surveys::fetch($data->masterId);
	    	$this->prepareSummariseDetails($data);	
    	}
    	
    	parent::prepareDetail_($data);
    	
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    static function on_AfterPrepareListRows($mvc, &$res)
    {
        $rows = &$res->rows;
        $recs = &$res->recs;
        if($res->masterData){
	        $masterRec = $res->masterData->rec;
	       	if(count($recs) && !survey_Surveys::isClosed($masterRec->id)) {
	            foreach ($recs as $id => $rec) {
	            	$rows[$id]->answers = $mvc->options->prepareOptions($id, $rec->surveyId);
	            	if(!count($rows[$id]->answers) && $masterRec->state == 'active'){
	            		unset($rows[$id]);
	            	}
	            }
	        }
        }
    }
    
    
    /**
     * Рендиране на въпросите
     */
    function renderDetail_($data)
    {
    	if($data->action == 'summarise') {
    		
    		// Ако трябва да показваме обобщения изглед го рендираме
    		$tpl = $this->renderSummariseDetails($data);
    	} else {
    		
    		// Ако не обобщаваме рендираме въпросите с възможност за отговор
    		$tpl = $this->renderAlternatives($data);
    	}
    	
    	$tpl->append($this->renderListToolbar($data), 'ListToolbar');
    	
    	return $tpl;
    }
    
    
    /**
	 * Обработка на вербалното представяне на въпросите
	 */
	function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
	{
		if($fields['-list']) {
			
			if(!Mode::is('printing') && $mvc->haveRightFor('edit', $rec)){
				$addUrl = array('survey_Options', 'add', 'alternativeId' => $rec->id, 'ret_url' => TRUE);
				$row->addOption = ht::createLink('', $addUrl, NULL, array('ef_icon' => 'img/16/add.png', 'class' => 'addParams', 'title'=>'Добавяне на опция'));
			}
			
			$imgLink = sbf('survey/img/question.png', '');
			$row->icon = ht::createElement('img', array('src' => $imgLink, 'style' =>"vertical-align:middle", 'width' => 16));
				
			if($rec->image) {
				$Fancybox = cls::get('fancybox_Fancybox');
				$row->image = $Fancybox->getImage($rec->image, array(400, 140), array(700, 500), null, array('class'=>'question-image'));
			}
		}
	}
	
	
    /**
     *  Рендираме въпросите от анкетата
     *  @return core_ET $tpl
     */
    function renderAlternatives($data)
    {
    	$tpl = getTplFromFile('survey/tpl/SingleAlternative.shtml');
    	$tplAlt = $tpl->getBlock('ROW');
    	if($data->rows) {
	    	foreach ($data->rows as $row) {
	    		$rowTpl = clone($tplAlt);
	    		$rowTpl->placeObject($row);
	    		$rowTpl->removeBlocks();
	    		$rowTpl->append2master();
	    	}
    	}
    	
    	$url = toUrl(array('survey_Votes', 'vote'));
    	$tpl->appendOnce("voteUrl = '{$url}';", 'SCRIPTS');
    	
    	// Рендиране на пейджъра
    	if($data->pager){
    	    $tpl->append($data->pager->getHtml(), 'PAGER');
    	}
    	
    	return $tpl;
    }
    
    
 	/**
     *  Подготовка на Обобщението на анкетата, Подготвяме резултатите във вида
     *  на масив от обекти, като всеки въпрос съдържа  информацията за неговите
     *  възможни отговори и техния брой гласове
     */
    function prepareSummariseDetails(&$data)
    {
    	$rec = &$data->rec;
    	$data->action = 'summarise';
    	$recs = array();
    	
    	$queryAlt = survey_Alternatives::getQuery();
    	$queryAlt->where("#surveyId = {$rec->id}");
    	while($altRec = $queryAlt->fetch()) {
    		$row = $this->prepareResults($altRec);
    		if(!count($row->answers)) continue;
    		$recs[$altRec->id] = $row;
    	}
    	
    	$data->summary = $recs;
    }
    
    
    /**
     * Метод преброяващ колко гласа е получила всяка от опциите на въпроса
     * @param stdClass $rec - запис на въпрос
     * @return stdClass $res - Обект показващ колко гласа е получил 
     * Всеки възможен отговор
     */
    function prepareResults($rec)
    {
    	$int = cls::get('type_Int');
    	$double = cls::get('type_Double');
    	$double->params['decimals'] = 2;
    	
    	// Всички гласове, които е получил въпроса
    	$totalVotes = survey_Votes::countVotes($rec->id);
    	
    	// Преброяваме колко гласа е получил всеки ред от отговорите
    	$answers = array();
    	$query = $this->options->getQuery();
    	$query->where("#alternativeId = {$rec->id}");
    	while($option = $query->fetch()) {
    		$op = new stdClass();
    		$op->text = $option->text;
    		if($totalVotes != 0) {
	    		$op->votes = $int->toVerbal(survey_Votes::countVotes($rec->id, $option->id));
	    		$op->percent = $double->toVerbal(round($op->votes / $totalVotes * 100, 2));
	    	} else {
    			$op->votes = 0;
    			$op->percent = 0;
    		}
    		$answers[] = $op;
    	}
    	
    	$res = new stdClass();
    	
    	$res->label = $rec->label;
    	$res->points = $this->options->countPoints($rec->id);
    	$res->points = $double->toVerbal($res->points);
    	
    	arr::order($answers, 'votes');
    	$answers = array_reverse($answers, true);
    	$res->answers = $answers;
    	
    	return $res;
    }
    
    
 	/**
     * Рендиране на Обобщените резултати
     */
	function renderSummariseDetails($data)
    {
    	$tpl = getTplFromFile('survey/tpl/Summarise.shtml');
    	$blockTpl = $tpl->getBlock('ROW');
    	$type = cls::get('type_Varchar');
    	$tpl->replace($type->toVerbal($data->rec->title), 'TOPIC');
    	
    	// За всеки въпрос от анкетата го рендираме заедно с отговорите
    	foreach($data->summary as $rec) {
    		$questionTpl = clone($blockTpl);
    		$subRow = $questionTpl->getBlock('subRow');
    		$label = $type->toVerbal($rec->label);
    		$questionTpl->replace($label, 'QUESTION');
    		$questionTpl->replace($rec->points, 'points');
    		
    		// Рендираме всеки отговор от въпроса с неговите гласове
    		foreach($rec->answers as $answer) {
    			$answersTpl = clone($subRow);
    			$answer->text = $type->toVerbal($answer->text);
    			$answersTpl->placeObject($answer);
    			$answersTpl->removeBlocks();
    			$answersTpl->append2master();
    		}
    		$questionTpl->removeBlocks();
    		$questionTpl->append2master();
    	}
    	
    	return $tpl;
    }
    
    
	/**
     * Метод проверяващ дали даден потребител вече е отговорил на
     * даден въпрос
     * @return boolean TRUE/FALSE дали е гласувал
     */
    static function hasUserVoted($alternativeId)
    {
    	if(survey_Votes::lastUserVote($alternativeId)) {
    		return TRUE;
    	}
    	
    	return FALSE;
    }
    
    
	/**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    static function on_AfterPrepareListToolbar($mvc, &$data)
    {
    	 if(empty($data->masterMvc)){
    	 	$data->toolbar->removeBtn('btnAdd');
    	 }
    }
    
    
 	/**
	 * Модификация на ролите, които могат да видят избраната тема
	 */
    public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
	{
   		if($action == 'write' && isset($rec->surveyId)) {
   			
   			/* Не можем да добавяме/редактираме нови въпроси
   			 * в следните случаи: Анкетата е затворена,
   			 * Анкетата е активирана,
   			 * потребителят не е създател на анкетата
   			 */
   			$surveyRec = survey_Surveys::fetch($rec->surveyId);
   			if(survey_Surveys::isClosed($surveyRec->id) || 
   			   $surveyRec->state != 'draft' || 
   			   $surveyRec->createdBy != core_Users::getCurrent()) {
   			   
   			   $res = 'no_one';
   			} 
   		}
   		
   		if($action == 'vote' && isset($rec->id)) {
   			$altRec = survey_Alternatives::fetch($rec->id);
			$surveyRec = survey_Surveys::fetch($altRec->surveyId);
			if($surveyRec->state != 'active' || survey_Surveys::isClosed($altRec->surveyId)) {
				$res = 'no_one';
			} else {
				$res = 'every_one';
			}
   		}
   	}
}