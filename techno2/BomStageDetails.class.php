<?php



/**
 * Мениджър на детайли на детайлите етапи на технологичните рецепти
 *
 *
 * @category  bgerp
 * @package   techno
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class techno2_BomStageDetails extends core_Detail
{
    
	
    /**
     * Заглавие
     */
    var $title = "Ресурси на технологичните рецепти";
    
    
    /**
     * Наименование на единичния обект
     */
    var $singleTitle = 'Ресурс';
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    var $masterKey = 'bomstageId';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_RowTools, techno2_Wrapper, plg_LastUsedKeys, plg_RowNumbering, plg_AlignDecimals';
    
    
    /**
     * По кое поле да се групират записите
     */
    var $groupByField = 'stageId';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'RowNumb';
    
    
    /**
     * Активен таб
     */
    var $currentTab = 'Рецепти';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'ceo,techno';
    
    
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
    	$this->FLD('bomstageId', 'key(mvc=techno2_BomStages)', 'column=none,input=hidden,silent');
    	
    	$this->FLD("resourceId", 'key(mvc=mp_Resources,select=title,allowEmpty)', 'caption=Ресурс,mandatory,silent', array('attr' => array('onchange' => 'addCmdRefresh(this.form);this.form.submit();')));
    	$this->FLD("baseQuantity", 'double', 'caption=Количество->Начално,hint=Начално количество');
    	$this->FLD("propQuantity", 'double', 'caption=Количество->Пропорционално,hint=Пропорционално количество');
    	
    	$this->setDbUnique('bomstageId,resourceId');
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
    	// Ако детайла е добавен към етап, показваме го в инфото
    	$stage = $mvc->Master->getVerbal($data->form->rec->bomstageId, 'stage');
    	if($stage != ''){
    		$data->form->info = "<b>" . tr('Етап') . "</b>: {$stage}";
    	}
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     *
     * @param core_Mvc $mvc
     * @param core_Form $form
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
    	$rec = &$form->rec;
    	 
    	// Ако има избран ресурс, добавяме му мярката до полетата за количества
    	if(isset($rec->resourceId)){
    		$uomId = mp_Resources::fetchField($rec->resourceId, 'measureId');
    		$uomName = cat_UoM::getShortName($uomId);
    		 
    		$form->setField('baseQuantity', "unit={$uomName}");
    		$form->setField('propQuantity', "unit={$uomName}");
    	}
    	 
    	// Проверяваме дали е въведено поне едно количество
    	if($form->isSubmitted()){
    		if(empty($rec->baseQuantity) && empty($rec->propQuantity)){
    			$form->setError('baseQuantity,propQuantity', 'Трябва да е въведено поне едно количество');
    		}
    	}
    }
    
    
    /**
     * След обръщане на записа във вербален вид
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	$uomId = mp_Resources::fetchField($rec->resourceId, 'measureId');
    	$row->measureId = cat_UoM::getTitleById($uomId);
    	 
    	if(!Mode::is('printing') && !Mode::is('text', 'xhtml')){
    		$row->resourceId = ht::createLinkRef($row->resourceId, array('mp_Resources', 'single', $rec->resourceId));
    	}
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
    	if(($action == 'add' || $action == 'edit' || $action == 'delete') && isset($rec)){
    		if(empty($rec->bomstageId)){
    			$requiredRoles = 'no_one';
    		} else {
    			$masterBomId = $mvc->Master->fetchField($rec->bomstageId, 'bomId');
    			$masterState = techno2_Boms::fetchField($masterBomId, 'state');
    			
    			if($masterState != 'draft'){
    				$requiredRoles = 'no_one';
    			}
    		}
    	}
    }
    
    
    /**
     * Връща URL към единичния изглед на мастера
     */
    public function getRetUrl($rec)
    {
    	$bomId = $this->Master->fetchField($rec->bomstageId, 'bomId');
    	$url = array('techno2_Boms', 'single', $bomId);
    
    	return $url;
    }
    
    
    /**
     * Пренасочва URL за връщане след запис към сингъл изгледа
     */
    public static function on_AfterPrepareRetUrl($mvc, $res, $data)
    {
    	// Рет урл-то не сочи към мастъра само ако е натиснато 'Запис и Нов'
    	if (isset($data->form) && ($data->form->cmd === 'save' || is_null($data->form->cmd))) {
    
    		// Променяма да сочи към single-a
    		$bomId = techno2_BomStages::fetchField($data->form->rec->bomstageId, 'bomId');
    		$data->retUrl = toUrl(array('techno2_Boms', 'single', $bomId));
    	}
    }
}