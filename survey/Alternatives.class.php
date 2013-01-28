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
     * Рендиране на въпросите
     */
    function renderDetail_($data)
    {
    	$tpl = $this->renderAlternatives($data);
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
	 */
	function verbalAnswers($text, $id)
	{
		$tpl = new ET("");
		$altTpl = new ET("<li><input type='radio' onClick='go(&#39;[#url#]&#39;);' [#checked#]>&nbsp;&nbsp;[#answer#]</li>\n");
		
		// Разбиваме подадения текст по редове
		$txtArr = explode("\n", $text);
		$arr = array('survey_Votes', 'vote', 'id' => NULL, 'alternativeId' => $id, 'ret_url' => TRUE);
		$rowAnswered = survey_Votes::hasUserVoted($id);
		for($i = 1; $i <= count($txtArr); $i++) {
			
			// Всеки ред от текста е отговор, рендираме го във вида на радио
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
	 * Модификация на ролите, които могат да видят избраната тема
	 */
    static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
	{  
   		if($action == 'write' && isset($rec)) {
   			
   			/* Неможем да добавяме/редактираме нови въпроси в следните случаи:
   			 * Анкетата е затворена, Анкетата е активиранам, потребителят не е
   			 * създател на анкетата
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