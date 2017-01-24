<?php



/**
 * Мениджър на детайли на счетоводните разлики
 *
 *
 * @category  bgerp
 * @package   acc
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class acc_BalanceRepairDetails extends doc_Detail
{
    
	
    /**
     * Заглавие
     */
    var $title = "Детайл на документа за корекция на закръглянията";
    
    
    /**
     * Наименование на единичния обект
     */
    var $singleTitle = 'Разлика';
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    var $masterKey = 'repairId';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_RowTools2, acc_Wrapper, plg_RowNumbering, plg_StyleNumbers, plg_AlignDecimals,plg_SaveAndNew, plg_PrevAndNext';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'accountId, blQuantity,blAmount,reason';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'tools';
    
    
    /**
     * Активен таб
     */
    var $currentTab = 'Операции->Разлики';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'ceo,acc';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'ceo,accMaster';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'ceo,acc';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'ceo,acc';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'ceo,accMaster';
    
    
    /**
     * Над каква сума да показва предупреждение
     */
    public $allowedLimit = 1;
    
    
    /**
     * Сметките от кои групи да могат да се показват за избор от сметкоплана
     * @var unknown
     */
    public $selectAccountsFromByNum = '3,4,5,6,7';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
    	$this->FLD('repairId', 'key(mvc=acc_BalanceRepairs)', 'column=none,input=hidden,silent');
        $this->FLD('accountId', 'acc_type_Account(allowEmpty)', 'caption=Сметка->От,mandatory');
        $this->FLD('reason', 'varchar', 'caption=Информация');
        $this->FLD('blQuantity', 'double', 'caption=Занули крайното салдо под->К-во');
        $this->FLD('blAmount', 'double', 'caption=Занули крайното салдо под->Сума');
        
        $this->setDbUnique('repairId,accountId');
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     *
     * @param core_Mvc $mvc
     * @param core_Form $form
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
    	if($form->isSubmitted()){
    		if($form->rec->blQuantity > $mvc->allowedLimit){
    			$form->setWarning('blQuantity', "Въведеното к-ва е над '{$mvc->allowedLimit}'");
    		}
    		
    		if($form->rec->blAmount > $mvc->allowedLimit){
    			$form->setWarning('blAmount', "Въведената сума е над '{$mvc->allowedLimit}'");
    		}
    	}
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
    	$balanceId = $mvc->Master->fetchField($rec->repairId, 'balanceId');
    	$row->accountId = acc_Balances::getAccountLink($rec->accountId, $balanceId, TRUE, TRUE);
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
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
    	$form = &$data->form;
    	
    	$Accounts = cls::get('acc_Accounts');
    	
    	// Извличаме само сметките с посочените номера
    	$nums = arr::make($mvc->selectAccountsFromByNum);
    	$options = array();
    	foreach ($nums as $num){
    		$options += $Accounts->makeArray4Select('title', array("#num LIKE '[#1#]%' AND state NOT IN ('closed')", $num));
    	}
    	
    	// Задаваме ги за опции на полето
    	$form->setOptions('accountId', $options);
    }
}