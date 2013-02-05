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
    var $loadList = 'plg_RowTools, survey_Wrapper, plg_Sorting, plg_SaveAndNew';
    
  
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
	var $listItemsPerPage = "20";
	
	
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
		$this->FLD('label', 'varchar(64)', 'caption=Въпрос, mandatory, width=100%');
		$this->FLD('answers', 'text', 'caption=Отговори, mandatory');
		$this->FLD('image', 'fileman_FileType(bucket=survey_Images)', 'caption=Картинка');
    }
    
    
    /**
     * Функция извиквана след като изпратим формата
     */
    static function on_AfterInputEditForm($mvc, &$form)
    {
    	if($form->isSubmitted()) {
    		$txtArr = explode("\n", $form->rec->answers);
    		
    		// Изчистваме подадените отговори от празни редове, и правим
    		// проверка за тяхната дължина
    		$txtArr = array_filter($txtArr, 'trim');
			if(count($txtArr) == 1) {
				$form->setWarning('answers', 
								  'Въпросът има само един въжможен отговор. 
				                   Сигурни ли сте че искате да го запишете ? ');
			} elseif(count($txtArr) == 0) {
				$form->setError('answers', 'Не сте подали възможни отговори !!!');
			}
    		
			// преобразуваме изчистения масив във вида на текст, записвайки го
			$form->rec->answers = implode("\n", $txtArr);
    	}
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
	 * Обработка на вербалното представяне на статиите
	 */
	function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
	{
		if($fields['-list']) {
			$row->answers = $mvc->verbalAnswers($rec->answers, $rec->id);
			
			$imgLink = sbf('survey/img/question.png', '');
			$row->icon = ht::createElement('img', array('src' => $imgLink, 'width' => '16px', 'valign' =>"middle"));
			
			if($rec->image) {
				$Fancybox = cls::get('fancybox_Fancybox');
				$row->image = $Fancybox->getImage($rec->image, array(140, 140), array(500, 500), null, array('class'=>'question-image'));
			}
		}
	}
	
	
	/**
	 * Подготвя отговорите на въпроса, от текст във вида на радио бутони
	 * с връзки към екшъна Vote на survey_Votes
	 * @param text $text - Отговорите на въпроса във вида на текст
	 * @param int $id - ид на въпроса
	 * @param int $surveyId - id на анкетата
	 * @return core_ET $tpl
	 */
	function verbalAnswers($text, $id)
	{
		$tpl = new ET("");
		$altTpl = new ET("<li><input name='quest{$id}' type='radio' [#data#] [#checked#]>&nbsp;&nbsp;[#answer#]</li>");
		
		// Ако анкетата е активна тогава радио бутоните могат да
		// изпращат гласове
		$rec = static::fetch($id);
		($this->haveRightFor('vote', $rec)) ? $can = TRUE : $can = FALSE;
		
		// Разбиваме подадения текст по редове, и махаме празните такива
		$txtArr = explode("\n", $text);
		
		// Кой е послед посочения отговор от потребителя
		$lastVote = survey_Votes::lastUserVote($id);
		
		// Всеки непразен ред от текста е отговор, 
		// рендираме го във вида на радио бутон
		for($i = 1; $i <= count($txtArr); $i++) {
			$copyTpl = clone($altTpl);
				
			// Ако гласуването е позволено, слагаме в инпута
			// атрибутите нужни за Ajax заявката
			if($can) { 
				$params = "data-rowId='{$i}' data-alternativeId='{$id}' ";
				if($mid = Request::get('m')) {
					$params .= " data-m='{$mid}'";
				}
					
				$copyTpl->replace($params, 'data');
			}
				
			$copyTpl->replace($txtArr[$i-1], 'answer');
				
			// Ако потребителя вече е гласувал, чекваме радио бутона
			if($i == $lastVote->rate) {
				$copyTpl->replace('checked', 'checked');
			}
				
			$tpl->append($copyTpl);
		}
		
		return $tpl;
	}
	
	
    /**
     *  Рендираме въпросите от анкетата
     *  @return core_ET $tpl
     */
    function renderAlternatives($data)
    {
    	$tpl = new ET(getFileContent('survey/tpl/SingleAlternative.shtml'));
    	$tplAlt = $tpl->getBlock('ROW');
    	if($data->rows) {
	    	foreach($data->rows as $row) {
	    		$rowTpl = clone($tplAlt);
	    		$rowTpl->placeObject($row);
	    		$rowTpl->removeBlocks();
	    		$tpl->append($rowTpl);
	    	}
    	}
    	
    	$url = toUrl(array('survey_Votes', 'vote'));
    	$tpl->appendOnce("voteUrl = '{$url}';", 'SCRIPTS');
    	
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
    		$recs[$altRec->id] = $this->prepareResults($altRec);
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
    	// Всички гласове, които е получил въпроса
    	$totalVotes = survey_Votes::countVotes($rec->id);
    	
    	// Преброяваме колко гласа е получил всеки ред от отговорите
    	$txtArr = explode("\n", $rec->answers);
    	$answers = array();
    	for($i = 0; $i < count($txtArr); $i++) {
    		$op = new stdClass();
    		$op->text = $txtArr[$i];
    		if($totalVotes != 0) {
	    		$op->votes = survey_Votes::countVotes($rec->id, $i+1);
	    		$op->percent = round($op->votes / $totalVotes * 100, 2);
	    	} else {
    			$op->votes = 0;
    			$op->percent = 0;
    		}
    		
    		$answers[] = $op;
    	}
    	
    	$res = new stdClass();
    	$res->label = $rec->label;
    	
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
    	$tpl = new ET(getFileContent('survey/tpl/Summarise.shtml'));
    	$blockTpl = $tpl->getBlock('ROW');
    	$varcharType = cls::get('type_Varchar');
    	$tpl->replace($varcharType->toVerbal($data->rec->title), 'TOPIC');
    	
    	// За всеки въпрос от анкетата го рендираме заедно с отговорите
    	foreach($data->summary as $rec) {
    		$questionTpl = clone($blockTpl);
    		$subRow = $questionTpl->getBlock('subRow');
    		$label = $varcharType->toVerbal($rec->label);
    		$questionTpl->replace($label, 'QUESTION');
    		
    		// Рендираме всеки отговор от въпроса с неговите гласове
    		foreach($rec->answers as $answer) {
    			$answersTpl = clone($subRow);
    			$answer->text = $varcharType->toVerbal($answer->text);
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
     * Метод връщащ подаден ред от отговорите на даден въпрос
     * @param alternativeId $id - ид ана въпроса
     * @param int $rate - номер на реда
     * @return varchar $res - текста, който се намира на този ред
     */
    static function getAnswerRow($id, $rate)
    {
    	// Разбираме отговорите по нови редове, и връщаме търсения ред
    	expect($rec = static::fetch($id));
    	$txtArr = explode("\n", $rec->answers);
    	
    	return $txtArr[$rate-1];
    }
    
    
	/**
     * Метод проверяващ дали даден потребител вече е отговорил на
     * даден въпрос
     * @return boolean TRUE/FALSE дали е гласувал
     */
    static function hasUserVoted($alternativeId)
    {
    	if($rec = survey_Votes::lastUserVote($alternativeId)) {
    		
    			return TRUE;
    	}
    	
    	return FALSE;
    }
    
    
 	/**
	 * Модификация на ролите, които могат да видят избраната тема
	 */
    static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
	{  
   		if($action == 'write' && isset($rec->id)) {
   			
   			/* Неможем да добавяме/редактираме нови въпроси
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
   		
		if($action == 'add' && !isset($rec)) {
			
			// Предпазване от добавяне на нов постинг в act_List
			$res = 'no_one';
		}
   	}
}