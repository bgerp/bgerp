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
    var $title = 'Опции';
    
    
    /**
     * Заглавие
     */
    var $singleTitle = 'Опция';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    //var $listFields = 'tools=Пулт, locationId, salesmanId, dateFld, repeatWeeks, state, createdOn, createdBy';
    
	
	/**
	 * Брой рецепти на страница
	 */
	var $listItemsPerPage = '30';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	var $canList = 'survey,ceo';
	
	
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_RowTools,  plg_Created, survey_Wrapper, plg_SaveAndNew';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от 
     * таблицата.
     */
    var $rowToolsField = 'tools';

    
    /**
     * Кой може да чете
     */
    var $canRead = 'sales,ceo';
    
    
    /**
     * Кой може да пише
     */
    var $canWrite = 'sales,ceo';
    
    
    /**
     * Кой може да пише
     */
    var $canAdd = 'survey,ceo';
    
    
    /**
     * 
     */
    var $canDelete = 'survey,ceo';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
    	$this->FLD('alternativeId', 'key(mvc=survey_Alternatives, select=label)', 'caption=Въпрос, input=hidden, silent');
    	$this->FLD('text', 'varchar(165)', 'caption=Съдържание,mandatory');
    	$this->FLD('value', 'double(decimals=2)', 'caption=Точки');
    }
    
    
    /**
     * Модификации по формата
     */
	public static function on_AfterPrepareEditForm($mvc, &$data)
    {
    	
    }
    
    
    /**
     * Подготовка на маршрутите, показвани в Single-a на локациите
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
    private function getverbalOptions($rec)
    {
    	$row = static::recToVerbal($rec);
    	$tpl = new ET("<li><input name='quest{$rec->alternativeId}' type='radio' [#data#] [#checked#]>&nbsp;&nbsp;[#answer#] <span class='opTools'>[#tools#]</span></li>");
    	
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
    static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
	{  
   		if(($action == 'edit' || $action == 'delete' || $action == 'add') && isset($rec->alternativeId)) {
   			$surveyState = survey_Surveys::fetchField(survey_Alternatives::fetchField($rec->alternativeId, 'surveyId'), 'state');
	    	if($surveyState == 'active'){
	    		$res = 'no_one';
	    	}
   		}
   		
   		if($action == 'add' && empty($rec->alternativeId)){
   			$res = 'no_one';
   		}
	}
	
	
	/**
	 * 
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