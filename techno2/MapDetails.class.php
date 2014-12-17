<?php



/**
 * Мениджър на детайли на технологични карти
 *
 *
 * @category  bgerp
 * @package   techno
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class techno2_MapDetails extends doc_Detail
{
    
	
    /**
     * Заглавие
     */
    var $title = "Детайл на технологичните карти";
    
    
    /**
     * Наименование на единичния обект
     */
    var $singleTitle = 'Ресурс';
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    var $masterKey = 'mapId';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_RowTools, techno2_Wrapper, plg_RowNumbering, plg_StyleNumbers, plg_AlignDecimals';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'rowNumb=Пулт, stageId, resourceId, hardQuantity,propQuantity,minQuantity,maxQuantity';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'rowNumb';
    
    
    /**
     * Активен таб
     */
    //var $currentTab = 'Операции->Разлики';
    
    
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
    var $canList = 'ceo,techno';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'ceo,techno';
    
    
    
    /**
     * Описание на модела
     */
    function description()
    {
    	$this->FLD('mapId', 'key(mvc=techno2_Maps)', 'column=none,input=hidden,silent');
    	
    	$this->FLD("stageId", 'key(mvc=mp_Stages,select=name,allowEmpty)', 'caption=Етап');
    	
    	$this->FLD("resourceId", 'key(mvc=mp_Resources,select=title,allowEmpty)', 'caption=Ресурс,mandatory,silent', array('attr' => array('onchange' => 'addCmdRefresh(this.form);this.form.submit();')));
    	$this->FLD("hardQuantity", 'double', 'caption=Количество->Твърдо,mandatory');
    	$this->FLD("propQuantity", 'double', 'caption=Количество->Пропорционално');
    	$this->FLD("minQuantity", 'double', 'caption=Количество->Минимално');
    	$this->FLD("maxQuantity", 'double', 'caption=Количество->Максимално');
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
    	
    	if($form->cmd == 'refresh'){
    		if(isset($rec->resourceId)){
    			$uomId = mp_Resources::fetchField($rec->resourceId, 'measureId');
    			$uomName = cat_UoM::getShortName($uomId);
    			
    			$form->setField('minQuantity', "unit={$uomName}");
    			$form->setField('maxQuantity', "unit={$uomName}");
    			$form->setField('hardQuantity', "unit={$uomName}");
    			$form->setField('propQuantity', "unit={$uomName}");
    		}
    	}
    	
    	if($form->isSubmitted()){
    		if(!empty($rec->minQuantity) && !empty($rec->maxQuantity) && !empty($rec->hardQuantity)){
    			
    			if($rec->hardQuantity < $rec->minQuantity){
    				$form->setError('hardQuantity,minQuantity', 'Твърдото к-во е под минималното');
    			}
    			
    			if($rec->hardQuantity > $rec->maxQuantity){
    				$form->setError('hardQuantity,maxQuantity', 'Твърдото к-во е над максималното');
    			}
    		}
    	}
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
    	if(($action == 'edit' || $action == 'delete' || $action == 'add') && isset($rec)){
    		if($mvc->Master->fetchField($rec->{$mvc->masterKey}, 'state') != 'draft'){
    			$requiredRoles = 'no_one';
    		}
    	}
    }
}