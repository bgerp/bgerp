<?php



/**
 * Модел "Анкети"
 *
 *
 * @category  bgerp
 * @package   survey
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class survey_Surveys extends core_Master {
    
    
	/**
     * Какви интерфейси поддържа този мениджър
     */
    var $interfaces = 'doc_DocumentIntf';
    
    
    /**
     * Заглавие
     */
    var $title = 'Анкети';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_RowTools, survey_Wrapper,  plg_Printing,
     	plg_Sorting,  doc_DocumentPlg, bgerp_plg_Blank, doc_ActivatePlg';
    
  
    /**
     * Кои полета да се показват в листовия изглед
     */
    //var $listFields = 'id, iban, contragent=Контрагент, currencyId, type';
    
    
    /**
     * Наименование на единичния обект
     */
    var $singleTitle = "Анкета";
    
    
    /**
     * Икона на единичния обект
     */
    var $singleIcon = 'img/16/survey.png';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsSingleField = 'title';

    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'survey, ceo, admin';
    
    
    /**
     * Кой има право да чете?
     */
    var $canSummarise = 'user';
    
    
    /**
	 * Коментари на статията
	 */
	var $details = 'survey_Alternatives';
	
	
	/**
     * Абревиатура
     */
    var $abbr = "Ank";
    
    
    /**
     * Кой може да пише?
     */
    var $canWrite = 'survey, ceo, admin';
    
    
    /**
	 * Файл за единичен изглед
	 */
	var $singleLayoutFile = 'survey/tpl/SingleSurvey.shtml';
	
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
    	$this->FLD('title', 'varchar(50)', 'caption=Заглавие, mandatory, width=400px');
		$this->FLD('description', 'text(rows=2)', 'caption=Oписание, mandatory, width=100%');
    	$this->FLD('deadline', 'date(format=d.m.Y)', 'caption=Краен срок,width=8em,mandatory');
    	$this->FLD('summary', 'enum(internal=Вътрешно,personal=Персонално,public=Публично)', 'caption=Обобщение,mandatory,width=8em');
    	$this->FLD('state', 'enum(draft=Чернова,active=Публикувана,rejected=Оттеглена)', 'caption=Състояние,mandatory,width=8em');
    }
    
    
    /**
     * Обработки след като изпратим формата
     */
    static function on_AfterInputEditForm($mvc, &$form)
    {
    	if($form->isSubmitted()) {
    		$today = dt::now();
	    	if($form->rec->deadline <= $today) {
	    		$form->setError('deadline', 'Крайния срок на анкетата не е валиден');
	    	} 
    	}
    }
    
    
    /**
     *  Обработки по вербалното представяне на данните
     */
    static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	$row->number = static::getHandle($rec->id);
    	
    	if($fields['-single']) {
    		
    		// Показваме заглавието само ако не сме в режим принтиране
	    	if(!Mode::is('printing')){
	    		$row->header = $mvc->singleTitle . "&nbsp;&nbsp;<b>{$row->ident}</b>" . " ({$row->state})" ;
	    	}
    	}
    }
    
    
    /**
     * Метод проверяващ дали дадена анкета е отворена
     * @param int id - id на анкетата
     * @return boolean $res - затворена ли е анкетата или не
     */
    static function isClosed($id)
    {
    	expect($rec = static::fetch($id), 'Няма такъв запис');
    	$now = dt::now();
    	($rec->deadline <= $now) ? $res = TRUE : $res = FALSE;
    	
    	return $res;
    }
    
    
   /**
	 * Модификация на ролите, които могат да видят избраната тема
	 */
    static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
	{  
   		//  Кой може да обобщава резултатите
		if($action == 'summarise' && isset($rec->id)) {
   			switch($rec->summary) {
   				case 'internal':
   					$res = $mvc->canSummarise;
   					break;
   				case 'personal':
   					if($rec->createdBy != core_Users::getCurrent()) {
   						$res = 'no_one';
   					}
   					break;
   				case 'public':
   					$res = 'every_one';
   					break;
   			}
   		} 
   	}
    
   	
   	/**
   	 * Обработка на SingleToolbar-a
   	 */
   	static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
    	if($mvc::haveRightFor('summarise', $data->rec->id)) {
    		$data->toolbar->addBtn('Обобщение', array($mvc, 'summarise', $data->rec->id, 'ret_url' => TRUE, ''));
    	}
    }
   	
    
    /**
     *  Екшън обобщаващ резултатите на анкетата
     */
    function act_Summarise()
    {
    	$this->requireRightFor('summarise', $data->rec->id);
    	expect($id = Request::get('id'));
    	expect($rec = $this->fetch($id));
    	
    	$data = new stdClass();
    	$data->rec = $rec;
    	$data->action = 'summarise';
    	
    	// Подготвяме резултатите от анкетата
    	$this->prepareSummarise($data);
    	
    	// Рендираме резултатите
    	$layout = $this->renderSummarise($data);  	
    	
    	return $layout;
     }
    
    
    /**
     *  Подготовка на Обобщението на анкетата, Подготвяме резултатите във вида
     *  на масив от обекти, като всеки въпрос съдържа  информацията за неговите
     *  възможни отговори и техния брой гласове
     */
    function prepareSummarise($data)
    {
    	$rec = &$data->rec;
    	$recs = array();
    	$queryAlt = survey_Alternatives::getQuery();
    	$queryAlt->where("#surveyId = {$rec->id}");
    	while($altRec = $queryAlt->fetch()) {
    		$txtArr = explode("\n", $altRec->answers);
    		$answers = array();
    		for($i = 0; $i<count($txtArr); $i++) {
    			$op = new stdClass();
    			$op->text = $txtArr[$i];
    			$op->count = survey_Votes::countVotes($altRec->id, $i+1);
    			$answers[] = $op;
    		}
			
    		$rec = new stdClass();
    		$rec->label = $altRec->label;
    		$rec->answers = $answers;
    		$recs[$altRec->id] = $rec;
    	}
    	
    	$data->recs = $recs;
    }
    
    
    /**
     * Рендиране на Обобщените резултати
     */
	function renderSummarise($data)
    {
    	$tpl = new ET(getFileContent('survey/tpl/Summarise.shtml'));
    	$blockTpl = $tpl->getBlock('ROW');
    	$varcharType = cls::get('type_Varchar');
    	
    	// За всеки въпрос от анкетата го рендираме заедно с отговорите
    	foreach($data->recs as $rec) {
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
    	
    	$tpl = $this->renderWrapping($tpl);
    	$tpl->push('survey/tpl/css/styles.css', 'CSS');
    	
    	return $tpl;
    }
    
    
   	/**
     * Пушваме css файла
     */
    static function on_AfterRenderSingle($mvc, &$tpl, $data)
    {	
    	$tpl->push('survey/tpl/css/styles.css', 'CSS');
    	$tpl->push('survey/js/scripts.js', 'JS');
    }
    
    
	/**
     * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
     */
    function getDocumentRow($id)
    {
    	$rec = $this->fetch($id);
        $row = new stdClass();
        $row->title = $rec->reason;
        $row->authorId = $rec->createdBy;
        $row->author = $this->getVerbal($rec, 'createdBy');
        
        return $row;
    }
    
    
    /**
     * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
     */
    static function getHandle($id)
    {
    	$rec = static::fetch($id);
    	$self = cls::get(get_called_class());
    	
    	return $self->abbr . $rec->id;
    }
}