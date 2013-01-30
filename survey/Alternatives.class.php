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
    var $loadList = 'plg_RowTools, survey_Wrapper, plg_Sorting';
    
  
    /**
	 * Мастър ключ към дъските
	 */
	var $masterKey = 'surveyId';
	
	
    /**
     * Кои полета да се показват в листовия изглед
     */
    var $listFields = 'id, tools=Пулт, surveyId, image, label';
    
    
    /**
	 *  Брой елементи на страница 
	 */
	var $listItemsPerPage = "15";
	
	
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
     * Кой може да гласува?
     */
    var $canVote = 'every_one';
    
    
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
		$this->FLD('label', 'varchar(64)', 'caption=Лейбъл, mandatory');
		$this->FLD('answers', 'text', 'caption=Отговори, mandatory');
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
     * Рендиране на въпросите
     */
    function renderDetail_($data)
    {
    	if($data->action == 'summarise') {
    		
    		// Ако трябва да показваме обобщения изглед го рендираме
    		$tpl = $this->renderSummariseDetails($data);
    	} else {
    		
    		// Ако не обобщаваме рендираме въпросите с възможност за
    		// отговор
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
			
			if(!$rec->image) {
				$imgLink = sbf('survey/img/question.png', '');
			}else {
				$attr = array('isAbsolute' => FALSE, 'qt' => '');
				$imgLink = thumbnail_Thumbnail::getLink($rec->image, array('18','18'), $attr);
			}
			
			$row->image = ht::createElement('img', array('src' => $imgLink, 'width' => '18px'));
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
		$altTpl = new ET("<li><input name= 'quest{$id}' type='radio' [#data#] [#onClick#] [#checked#]>&nbsp;&nbsp;[#answer#]</li>\n");
		
		// Ако анкетата е активна тогава радио бутоните могат да
		// изпращат гласове
		$rec = static::fetch($id);
		if($this->haveRightFor('vote', $rec)) {
			//$altTpl->replace(new ET("onClick='goUrl(&#39;[#url#]&#39;);'"), 'onClick');
		} 
		
		// Разбиваме подадения текст по редове
		$txtArr = explode("\n", $text);
		$arr = array('survey_Votes', 'vote', 'id' => NULL, 'alternativeId' => $id, 'ret_url' => TRUE);
		$rowAnswered = static::hasUserVoted($id);
		
		for($i = 1; $i <= count($txtArr); $i++) {
			if($txtArr[$i-1] != '') {
				
				// Всеки непразен ред от текста е отговор, 
				// рендираме го във вида на радио бутон
				$params = "rowId='{$i}' alternativeId='{$id}' ";
				if($mid = Request::get('m')) {
					$params .= "mid='{$mid}'";
				}
				$copyTpl = clone($altTpl);
				$copyTpl->replace($params, 'data');
				$copyTpl->replace($txtArr[$i-1], 'answer');
				if($i == $rowAnswered) {
					$copyTpl->replace('checked', 'checked');
				}
				
				$tpl->append($copyTpl);
			}
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
    	
    	$tpl->append(new ET('[#ListToolbar#]'));
    	
    	// Зареждаме JS файла за Ajax заявката
    	$clickScript = new ET(getFileContent('survey/js/scripts.js'));
    	$arr = array('survey_Votes', 'vote', 'ret_url' => TRUE);
    	$url = toUrl($arr);
    	$clickScript->replace($url, 'url');
    	$tpl->append(new ET('<script>[#JS#]</script>'));
    	$tpl->replace($clickScript, 'JS');
    	
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
    	// Преброяваме колко гласа е получил всеки ред от отговорите
    	$txtArr = explode("\n", $rec->answers);
    	$answers = array();
    	for($i = 0; $i<count($txtArr); $i++) {
    		$op = new stdClass();
    		$op->text = $txtArr[$i];
    		$op->count = survey_Votes::countVotes($rec->id, $i+1);
    		$answers[] = $op;
    	}
    	
		$res = new stdClass();
    	$res->label = $rec->label;
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
    			$text = $varcharType->toVerbal($answer->text);
    			$answersTpl->replace($text, 'OPTION');
    			$answersTpl->replace($answer->count, 'VOTES');
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
    	expect($rec = static::fetch($id));
    	
    	// Разбираме отговорите по нови редове, и връщаме търсения ред
    	$txtArr = explode("\n", $rec->answers);
    	
    	return $txtArr[$rate-1];
    }
    
    
	/**
     * Метод проверяващ дали даден потребител вече е отговорил на даден въпрос
     * @return mixed $rec->rate/FALSE - отговора който е посочен или FALSE
     * ако няма запис
     */
    static function hasUserVoted($alternativeId)
    {
    	$userUid = survey_Votes::getUserUid();
    	$query = survey_Votes::getQuery();
    	$query->where(array("#alternativeId = [#1#]", $alternativeId));
    	$query->where(array("#userUid = '[#1#]'", $userUid));
    	if($rec = $query->fetch()) {
    		
    		return $rec->rate;
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
			if($surveyRec->state == 'draft' || static::hasUserVoted($rec->id)) {
				$res = 'no_one';
			} else {
				$res = $mvc->canVote;
			}
   		}
   		
		if($action== 'add' && !isset($rec)) {
			
			// Предпазване от добавяне на нов постинг в act_List
			$res = 'no_one';
		}
   	}
}