<?php



/**
 * Мениджър на настройки за ешопа
 *
 *
 * @category  bgerp
 * @package   eshop
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class eshop_Settings extends core_Manager
{
    
    
    /**
     * Заглавие
     */
    public $title = "Настройки на онлайн магазина";
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Modified, plg_RowTools2, eshop_Wrapper';
    
    
    /**
     * Кои ключове да се тракват, кога за последно са използвани
     */
    public $lastUsedKeys = 'payments';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'objectId=Обект,currencyId,chargeVat,payments,terms,listId,validFrom=Продължителност->От,validTo=Продължителност->До,@info';
    
    
    /**
     * Наименование на единичния обект
     */
    public $singleTitle = "Настройка на онлайн магазина";
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'eshop,ceo,admin';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'eshop,ceo,admin';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'eshop,ceo,admin';
    
    
    /**
     * Кой има право да го изтрие?
     */
    public $canDelete = 'eshop,ceo,admin';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
    	$this->FLD('classId', 'class', 'caption=Клас,removeAndrefreshForm=objectId,silent,mandatory');
    	$this->FLD('objectId', 'int', 'caption=Обект,mandatory');
    	$this->FLD('currencyId', 'customKey(mvc=currency_Currencies,key=code,select=code)', 'caption=Плащания->Валута');
    	$this->FLD('chargeVat', 'enum(yes=Включено ДДС в цените, separate=Отделен ред за ДДС, exempt=Oсвободено от ДДС, no=Без начисляване на ДДС)', 'caption=Плащания->ДДС режим');
    	$this->FLD('payments', 'keylist(mvc=eshop_Payments,select=title)', 'caption=Общи данни->Плащане');
    	$this->FLD('terms', 'keylist(mvc=eshop_DeliveryTerms,select=title)', 'caption=Общи данни->Доставка');
    	$this->FLD('listId', 'key(mvc=price_Lists,select=title)', 'caption=Общи данни->Политика');
    	$this->FLD('info', 'richtext(rows=3)', 'caption=Общи данни->Текст');
    	$this->FLD('validFrom', 'datetime(timeSuggestions=00:00|04:00|08:00|09:00|10:00|11:00|12:00|13:00|14:00|15:00|16:00|17:00|18:00|22:00,format=smartTime)', 'caption=В сила->От,remember');
    	$this->FLD('validUntil', 'datetime(timeSuggestions=00:00|04:00|08:00|09:00|10:00|11:00|12:00|13:00|14:00|15:00|16:00|17:00|18:00|22:00,format=smartTime,defaultTime=23:59:59)', 'caption=В сила->До,remember');
    	
    	$this->setDbUnique('classId, objectId');
    }
    
    
	/**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass $data
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
    	$form = &$data->form;
    	
    	$domainClassId = cms_Domains::getClassId();
    	$classes = array($domainClassId => core_Classes::getTitleById($domainClassId));
    	$form->setOptions('classId', $classes);
    	$form->setDefault('classId', key($classes));
    	
    	if(isset($form->rec->classId)){
    		$form->setOptions('objectId', cms_Domains::getDomainOptions());
    		$form->setDefault('objectId', cms_Domains::getCurrent('id', FALSE));
    	}
    	
    	$form->setDefault('listId', price_ListRules::PRICE_LIST_CATALOG);
    	$form->setDefault('currencyId', acc_Periods::getBaseCurrencyCode());
    	
    	$ownCompany = crm_Companies::fetchOurCompany();
    	$shouldChargeVat = crm_Companies::shouldChargeVat($ownCompany->id);
    	$defaultChargeVat = ($shouldChargeVat === TRUE) ? 'yes' : 'no';
    	$form->setDefault('chargeVat', $defaultChargeVat);
    }
    
    
    /**
     *  Обработки по вербалното представяне на данните
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	if(isset($rec->classId) && isset($rec->objectId)){
    		$row->objectId = cls::get($rec->classId)->getHyperlink($rec->objectId, TRUE);
    	}
    	
    	if(isset($rec->listId)){
    		$row->listId = price_Lists::getHyperlink($rec->listId, TRUE);
    	}
    }
    
    
    /**
     * Връща настройките на пакета
     * 
     * @param int $domainId
     * @return FALSE|stdClass
     */
    public static function getSettings($classId, $objectId)
    {
    	return self::fetch(array("#classId = '[#1#]' AND #objectId = '[#2#]'", $domainId, $objectId));
    }
}