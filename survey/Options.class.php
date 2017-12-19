<?php



/**
 * Модел  Търговски маршрути
 *
 *
 * @category  bgerp
 * @package   survey
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class survey_Options extends core_Manager {
    
    
    /**
     * Заглавие
     */
    public $title = 'Опции';
    
    
    /**
     * Заглавие
     */
    public $singleTitle = 'Опция';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'debug';
	
	
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, plg_Created, survey_Wrapper, plg_SaveAndNew';
    
    
    /**
     * Кой може да пише
     */
    public $canWrite = 'sales,ceo';
    
    
    /**
     * Кой може да пише
     */
    public $canAdd = 'survey,ceo';
    
    
    /**
     * Кой може да изтрива
     */
    public $canDelete = 'survey,ceo';
    
    
    /**
     * Дали в листовия изглед да се показва бутона за добавяне
     */
    public $listAddBtn = FALSE;
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
    	$this->FLD('alternativeId', 'key(mvc=survey_Alternatives, select=label)', 'caption=Въпрос, input=hidden, silent');
    	$this->FLD('text', 'varchar(165)', 'caption=Съдържание,mandatory');
    	$this->FLD('value', 'double(decimals=2)', 'caption=Точки');
    	
    	$this->setDbIndex('alternativeId');
    }
    
    
    /**
     * Пренасочва URL за връщане след запис към сингъл изгледа
     */
    protected function on_AfterPrepareRetUrl($mvc, $res, $data)
    {
        if ($data->form->rec && $data->form->cmd == 'save') {
			
        	// retUrl-то е single-a на анкетата
        	$surveyId = survey_Alternatives::fetchField($data->form->rec->alternativeId, 'surveyId');
            $data->retUrl = toUrl(array('survey_Surveys', 'single', $surveyId));
        }
    }
    
    
    /**
     * Подготовка на опциите в въпросите
     */
    public function prepareOptions($id)
    {
    	$query = static::getQuery();
	    $query->where("#alternativeId = {$id}");
	    
	    while($rec = $query->fetch()){
	    	$options .= static::getVerbalOptions($rec);
	    }
	    return $options;
    }
    
    
    /**
     * 
     * Enter description here ...
     * @param unknown_type $rec
     */
    private function getVerbalOptions($rec)
    {
    	$row = static::recToVerbal($rec);
    	$tpl = new ET("<li><input  id='o{$rec->id}' name='quest{$rec->alternativeId}' type='radio' [#data#] [#checked#]><label for='o{$rec->id}'>[#answer#]</label> <span class='opTools'>[#tools#]</span></li>");
    	
		// Кой е послед посочения отговор от потребителя
		$lastVote = survey_Votes::lastUserVote($rec->alternativeId);
		$altRec = survey_Alternatives::fetch($rec->alternativeId);
		if((survey_Alternatives::haveRightFor('vote', $altRec))){
			$params = "data-rowId='{$rec->id}' data-alternativeId='{$rec->alternativeId}' ";
			if($mid = Request::get('m')) {
				$params .= " data-m='{$mid}'";
			}
		}
		
		$tpl->replace($row->text, 'answer');
		$tpl->replace($params, 'data');
		$tpl->replace($row->tools, 'tools');
		
    	// Ако потребителя вече е гласувал, чекваме радио бутона
		if($rec->id == $lastVote->rate) {
			$tpl->replace('checked', 'checked');
		}
		
		return $tpl;
    }
    
    
    /**
	 * Модификация на ролите, които могат да видят избраната тема
	 */
    public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
	{  
	    if (($action == 'edit' || $action == 'delete' || $action == 'add') && isset($rec->alternativeId)) {
   			$surveyState = survey_Surveys::fetchField(survey_Alternatives::fetchField($rec->alternativeId, 'surveyId'), 'state');
	    	if ($surveyState == 'active') {
	    		$res = 'no_one';
	    	}
   		}
   		
   		if ($action == 'add' && !empty((array)$rec)) {
   			if(empty($rec->alternativeId)) {
   				$res = 'no_one';
   			}
   		}
	}
	
	
	/**
	 * Преброява точките
	 */
	public function countPoints($alternativeId)
	{
		$query = static::getQuery();
		$query->where("#alternativeId = {$alternativeId}");
		$query->XPR('totalRate', 'double', 'sum(#value)');
		$query->show('totalRate');
		return $query->fetch()->totalRate;
	}
}