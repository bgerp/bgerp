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
    public $loadList = 'plg_Modified, plg_RowTools2, eshop_Wrapper, plg_Created, plg_Rejected';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'objectId=Обект,currencyId,chargeVat,payments,terms=Доставка,listId=Политика,storeId=Склад,discountType=Отстъпка,validFrom=Продължителност->От,validTo=Продължителност->До,modifiedOn,modifiedBy,@info';
    
    
    /**
     * Наименование на единичния обект
     */
    public $singleTitle = "Настройка на онлайн магазина";
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'eshop,ceo,admin';
    
    
    /**
     * Кой може да оттегля
     */
    public $canReject = 'eshop,ceo,admin';
    
    
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
    public $canDelete = 'no_one';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
    	$this->FLD('classId', 'class', 'caption=Клас,removeAndrefreshForm=objectId,silent,mandatory');
    	$this->FLD('objectId', 'int', 'caption=Обект,mandatory');
    	$this->FLD('validFrom', 'datetime(timeSuggestions=00:00|04:00|08:00|09:00|10:00|11:00|12:00|13:00|14:00|15:00|16:00|17:00|18:00|22:00,format=smartTime)', 'caption=В сила->От,remember');
    	$this->FLD('validUntil', 'datetime(timeSuggestions=00:00|04:00|08:00|09:00|10:00|11:00|12:00|13:00|14:00|15:00|16:00|17:00|18:00|22:00,format=smartTime,defaultTime=23:59:59)', 'caption=В сила->До,remember');
    	$this->FLD('listId', 'key(mvc=price_Lists,select=title)', 'caption=Ценова политика->Политика,mandatory');
    	$this->FLD('discountType', 'set(percent=Процент,amount=Намалена сума)', 'caption=Показване на отстъпки спрямо "Каталог"->Като,mandatory');
    	$this->FLD('terms', 'keylist(mvc=cond_DeliveryTerms,select=codeName)', 'caption=Възможни условия на доставка->Избор,mandatory');
    	$this->FLD('payments', 'keylist(mvc=cond_PaymentMethods,select=title)', 'caption=Условия на плащане->Методи,mandatory');
    	$this->FLD('currencyId', 'customKey(mvc=currency_Currencies,key=code,select=code)', 'caption=Условия на плащане->Валута,mandatory');
    	$this->FLD('chargeVat', 'enum(yes=Включено ДДС в цените, separate=Отделно ДДС)', 'caption=Условия на плащане->ДДС режим');
    	$this->FLD('storeId', 'key(mvc=store_Stores,select=name,allowEmpty)', 'caption=Свързване със склад->Избор');
    	$this->FLD('notInStockText', 'varchar(24)', 'caption=Информация при недостатъчно количество->Текст');
    	
    	$this->FLD('enableCart', 'enum(yes=Винаги,no=Aко съдържа продукти)', 'caption=Показване на количката във външната част->Показване,notNull,value=no');
    	$this->FLD('cartName', 'varchar(16)', 'caption=Показване на количката във външната част->Надпис');
    	$this->FLD('info', 'richtext(rows=3)', 'caption=Условия на продажбата под количката->Текст');
    	$this->FLD('state', 'enum(active=Активно,rejected=Оттеглен)', 'caption=Състояние,input=none,notNull,value=active');
    	
    	$this->setDbIndex('classId, objectId');
    }
    
    
    /**
     * Изпълнява се преди запис
     */
    protected static function on_BeforeSave(core_Manager $mvc, $res, $rec)
    {
    	if(empty($rec->validFrom)){
    		$rec->validFrom = ($rec->createdOn) ? $rec->createdOn : (isset($rec->modifiedOn) ? $rec->modifiedOn : dt::now());
    	}
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
    	$form->setField('classId', 'input=hidden');
    	
    	if(isset($form->rec->classId)){
    		$form->setOptions('objectId', cms_Domains::getDomainOptions());
    		$form->setDefault('objectId', cms_Domains::getCurrent('id', FALSE));
    	}
    	
    	$form->setDefault('listId', price_ListRules::PRICE_LIST_CATALOG);
    	$form->setDefault('currencyId', acc_Periods::getBaseCurrencyCode());
    	$form->setDefault('discountType', $mvc->getFieldType('discountType')->fromVerbal('percent'));
    	
    	$ownCompany = crm_Companies::fetchOurCompany();
    	$shouldChargeVat = crm_Companies::shouldChargeVat($ownCompany->id);
    	$defaultChargeVat = ($shouldChargeVat === TRUE) ? 'yes' : 'no';
    	$form->setDefault('chargeVat', $defaultChargeVat);
    	
    	$namePlaceholder = eshop_Setup::get('CART_EXTERNAL_NAME');
    	$form->setField('cartName', "placeholder={$namePlaceholder}");
    	$notInStockPlaceholder = eshop_Setup::get('NOT_IN_STOCK_TEXT');
    	$form->setField('notInStockText', "placeholder={$notInStockPlaceholder}");
    	
    	// Ако има ред от количка в домейна да не може да се сменя валутата и ддс-то
    	$cartQuery = eshop_CartDetails::getQuery();
    	$cartQuery->EXT("domainId", 'eshop_Carts', 'externalName=domainId,externalKey=cartId');
    	if($cartQuery->count()){
    		$form->setReadOnly('currencyId');
    		$form->setReadOnly('chargeVat');
    	}
    }
    
    
    /**
     *  Обработки по вербалното представяне на данните
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	if(isset($rec->classId) && isset($rec->objectId)){
    		$row->objectId = cls::get($rec->classId)->getHyperlink($rec->objectId, TRUE);
    	}
    	
    	if(isset($rec->listId)){
    		$row->listId = price_Lists::getHyperlink($rec->listId, TRUE);
    	}
    	
    	if(isset($rec->storeId)){
    		$row->storeId = store_Stores::getHyperlink($rec->storeId, TRUE);
    	}
    	
    	$row->ROW_ATTR['class'] = "state-{$rec->state}";
    }
    
    
    /**
     * Връща настройките на класа
     * 
     * @param int $classId              - клас
     * @param int $objectId             - ид на обект
     * @param datetime|NULL $date       - дата
     * @return FALSE|stdClass $foundRec - намерения запис
     */
    public static function getSettings($classId, $objectId, $date = NULL)
    {
    	$classId = cls::get($classId)->getClassId();
    	$cacheKey = "{$classId}|{$objectId}";
    	
    	if(isset($date)) return self::get($classId, $objectId, $date);
    	
    	$settingRec = core_Cache::get('eshop_Settings', $cacheKey);
    	if($settingRec === FALSE){
    		$date = dt::now();
    		$settingRec = self::get($classId, $objectId, $date);
    		core_Cache::set('eshop_Settings', $cacheKey, $settingRec, 10080);
    	}
    	
    	return $settingRec;
    }
    
    
    /**
     * Фечва запис
     * 
     * @param int $classId              - клас
     * @param int $objectId             - ид на обект
     * @param datetime|NULL $date       - дата
     * @return FALSE|stdClass $foundRec - намерения запис
     */
    private static function get($classId, $objectId, $date)
    {
    	$query = self::getQuery();
    	$query->where(array("#classId = '[#1#]' AND #objectId = '[#2#]'", $classId, $objectId));
    	$query->where("#state != 'rejected' AND #validFrom <= '{$date}' AND (#validUntil IS NULL OR #validUntil > '{$date}')");
    	$query->orderBy('id', 'DESC');
    	$query->limit(1);
    	$foundRec = $query->fetch();
    	
    	return is_object($foundRec) ? $foundRec : NULL;
    }
    
    
    /**
     * Извиква се след успешен запис в модела
     *
     * @param core_Mvc $mvc
     * @param int $id първичния ключ на направения запис
     * @param stdClass $rec всички полета, които току-що са били записани
     */
    protected static function on_AfterSave(core_Mvc $mvc, &$id, $rec)
    {
    	core_Cache::remove('eshop_Settings', "{$rec->classId}|{$rec->objectId}");
    }
}