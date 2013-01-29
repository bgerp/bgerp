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
     * Икона на единичния обект
     */
    //var $singleIcon = 'img/16/money.png';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    //var $rowToolsSingleField = 'iban';

    
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
	 * Файл за единичен изглед
	 */
	//var $singleLayoutFile = 'survey/tpl/SingleAccountLayout.shtml';
	
    
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
			$row->answers = $mvc->verbalAnswers($rec->answers, $rec->id, $rec->surveyId);
			
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
	function verbalAnswers($text, $id, $surveyId)
	{
		$tpl = new ET("");
		$altTpl = new ET("<li><input name= 'quest{$id}' type='radio' [#onClick#] [#checked#]>&nbsp;&nbsp;[#answer#]</li>\n");
		
		// Ако анкетата е активна тогава радио бутоните могат да
		// изпращат гласове
		$surveyState = survey_Surveys::fetchField($surveyId, 'state');
		if($surveyState == 'active') {
			$altTpl->replace(new ET("onClick='go(&#39;[#url#]&#39;);'"), 'onClick');
		}
		
		// Разбиваме подадения текст по редове
		$txtArr = explode("\n", $text);
		$arr = array('survey_Votes', 'vote', 'id' => NULL, 'alternativeId' => $id, 'ret_url' => TRUE);
		$rowAnswered = survey_Votes::hasUserVoted($id);
		
		for($i = 1; $i <= count($txtArr); $i++) {
			if($txtArr[$i-1] != '') {
				
				// Всеки непразен ред от текста е отговор, 
				// рендираме го във вида на радио бутон
				if($mid = Request::get('m')) {
					$arr['m'] = $mid;
				}
				
				$arr['id'] = $i;
				$url = toUrl($arr);
				$copyTpl = clone($altTpl);
				$copyTpl->replace($url, 'url');
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
    	foreach($data->rows as $row) {
    		$rowTpl = clone($tplAlt);
    		$rowTpl->placeObject($row);
    		$rowTpl->removeBlocks();
    		$tpl->append($rowTpl);
    	}
    	
    	$tpl->append(new ET('[#ListToolbar#]'));
    	
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
	 * Модификация на ролите, които могат да видят избраната тема
	 */
    static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
	{  
   		if($action == 'write' && isset($rec)) {
   			
   			/* Неможем да добавяме/редактираме нови въпроси
   			 * в следните случаи: Анкетата е затворена,
   			 * Анкетата е активиранам,
   			 * потребителят не е създател на анкетата
   			 */
   			$surveyRec = survey_Surveys::fetch($rec->surveyId);
   			if(survey_Surveys::isClosed($surveyRec->id) || 
   			   $surveyRec->state != 'draft' || 
   			   $surveyRec->createdBy != core_Users::getCurrent()) {
   			   $res = 'no_one';
   			}  
   		}
   		
		if($action== 'add' && !isset($rec)) {
			
			// Предпазване от добавяне на нов постинг в act_List
			$res = 'no_one';
		}
   	}
}