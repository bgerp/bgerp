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
    var $interfaces = 'doc_DocumentIntf, cms_ObjectSourceIntf';
    
    
    /**
     * Заглавие
     */
    var $title = 'Анкети';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_RowTools, survey_Wrapper,  plg_Printing,
     	  doc_DocumentPlg, bgerp_plg_Blank, doc_ActivatePlg, cms_ObjectPlg';
    
    
    /**
     * Наименование на единичния обект
     */
    var $singleTitle = "Анкета";
    
    
    /**
     * Икона на единичния обект
     */
    var $singleIcon = 'img/16/text_list_bullets.png';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsSingleField = 'title';

    
    /**
	 *  Брой елементи на страница 
	 */
    var $listItemsPerPage = "15";
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'survey, ceo';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	var $canList = 'survey,ceo';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	var $canSingle = 'survey,ceo';
    
    
    /**
     * Кой има право да чете?
     */
    var $canSummarise = 'user';
    
    
    /**
	 * Детайли на анкетата
	 */
	var $details = 'survey_Alternatives';
	
	
	/**
     * Абревиатура
     */
    var $abbr = "Ank";
    
    
    /**
     * Кой може да пише?
     */
    var $canWrite = 'survey, ceo';
    
    
    /**
	 * Файл за единичен изглед
	 */
	var $singleLayoutFile = 'survey/tpl/SingleSurvey.shtml';
	
	
	/**
     * Групиране на документите
     */
    var $newBtnGroup = "18.2|Други";
    
    
    /**
     * Дали може да бъде само в началото на нишка
     */
    var $onlyFirstInThread = TRUE;
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
    	$this->FLD('title', 'varchar(128)', 'caption=Заглавие, mandatory, width=400px');
		$this->FLD('description', 'text(rows=2)', 'caption=Oписание, mandatory, width=100%');
    	$this->FLD('enddate', 'date(format=d.m.Y)', 'caption=Краен срок,width=8em,mandatory');
    	$this->FLD('summary', 'enum(internal=Вътрешно,personal=Персонално,public=Публично)', 'caption=Обобщение,mandatory,width=8em');
    	$this->FLD('state', 'enum(draft=Чернова,active=Публикувана,rejected=Оттеглена)', 'caption=Състояние,input=none,width=8em');
    }
    
    
    /**
     * Модификации по формата
     */
	public static function on_AfterPrepareEditForm($mvc, &$data)
    {
    	// Премахваме бутона за активация от формата ! за да не активираме
    	// анкета без въпроси
    	$data->form->toolbar->removeBtn('activate');
    }
    
    
    /**
     * Обработки след като изпратим формата
     */
    static function on_AfterInputEditForm($mvc, &$form)
    {
    	if($form->isSubmitted()) {
    		$today = dt::now();
	    	if($form->rec->enddate <= $today) {
	    		$form->setError('enddate', 'Крайния срок на анкетата не е валиден');
	    	} 
	    	
	    	$form->rec->state = 'draft';
    	}
    }
    
    
    /**
     *  Обработки по вербалното представяне на данните
     */
    static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	$row->number = static::getHandle($rec->id);
    	
    	if($fields['-single']) {
	    	
	    	if(static::isClosed($rec->id)) {
	    		$row->closed = tr("Анкетата е затворена");
	    	}
	    }
    	
    	if($fields['-list']) {
    		if(static::isClosed($rec->id)) {
    			$row->title = $row->title . " - <span style='color:darkred'>" .tr('затворена'). "</span>";
    		}
    		
    		$txt = explode("\n", $rec->description);
    		if(count($txt) > 1) {
    			$row->description = $txt[0] . " ...";
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
    	($rec->enddate <= dt::now() ) ? $res = TRUE : $res = FALSE;
    	
    	return $res;
    }
    
    
    /**
	 * Модификация на ролите, които могат да видят избраната тема
	 */
    static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
	{  
   		//  Кой може да обобщава резултатите
		if($action == 'summarise' && isset($rec->id) ) {
   			
			//Можем да Обобщим резултатите само ако анкетата не е чернова
			if($rec->state == 'active' && !static::isClosed($rec->id)) {
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
   			} else {
   				$res = 'no_one';
   			}
   		}

   		if($action == 'activate' && isset($rec)) {
   			if(static::alternativeCount($rec->id) == 0 ||
   				$rec->enddate <= dt::now()) {
   				$res = 'no_one';
   			}
   		}
   	}
    
   	
   	/**
   	 * Обработка на SingleToolbar-a
   	 */
   	static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
    	$summary = Request::get('summary');
    	$url = getCurrentUrl();
    	if($mvc::haveRightFor('summarise', $data->rec->id) && !$summary) {
    		$url['summary'] = 'ok';
    		$data->toolbar->addBtn('Обобщение', $url, 'ef_icon=img/16/chart16.png, title=Виж резултатите от анкетата');
    	} 
    	
    	if($summary && $data->rec->state == 'active') {
    		
    		unset($url['summary']);
    		$data->toolbar->addBtn('Анкета',  $url, 'ef_icon=img/16/text_list_bullets.png, title=Обратно към анкетата');
    		$data->toolbar->buttons['btnPrint']->url['summary'] = 'ok';
    	}
    	
    	if($data->rec->state != 'draft' && survey_Votes::haveRightFor('read')){
    		$votesUrl = array('survey_Votes', 'list', 'surveyId' => $data->rec->id);
    		$data->toolbar->addBtn('Гласувания', $votesUrl, NULL, 'title=Преглед на гласовете');
    	}
    	
    }
    
    
    /**
     * Колко въпроса има дадена анкета
     * @param int $id
     * @return int - Броя въпроси които има анкетата
     */
    static function alternativeCount($id)
    {
    	expect(static::fetch($id), 'Няма такава анкета');
    	$altQuery = survey_Alternatives::getQuery();
    	$altQuery->where(array("#surveyId = [#1#]", $id));
    	
    	return $altQuery->count();
    }
    
    
    /**
     * Пушваме css и js файла
     */
    static function on_AfterRenderSingle($mvc, &$tpl, $data)
    {	
    	$tpl->push('survey/tpl/css/styles.css', 'CSS');
    	$tpl->push(('survey/js/scripts.js'), 'JS');
    	jquery_Jquery::run($tpl, "surveyActions();");
    }
    
    
	/**
     * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
     */
    function getDocumentRow($id)
    {
    	$rec = $this->fetch($id);
    	$title = $this->recToverbal($rec, 'title')->title;
    	$row = new stdClass();
        $row->title = $this->singleTitle . ' "' . $title . '"';
        $row->authorId = $rec->createdBy;
        $row->author = $this->getVerbal($rec, 'createdBy');
        $row->state = $rec->state;
		$row->recTitle = $rec->title;
		
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