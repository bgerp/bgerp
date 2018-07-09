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
    public $title = 'Настройки на онлайн магазина';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Modified, plg_RowTools2, eshop_Wrapper, plg_Created, plg_Rejected';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'objectId=Обект,currencyId,chargeVat,payments,terms=Доставка,listId=Политика,storeId=Склад,discountType=Отстъпка,validFrom=Продължителност->От,validUntil=Продължителност->До,modifiedOn,modifiedBy,@info';
    
    
    /**
     * Наименование на единичния обект
     */
    public $singleTitle = 'Настройка на онлайн магазина';
    
    
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
     * Дефолтен шаблон за имейл на български за онлайн поръчка
     */
    const DEFAULT_EMAIL_BODY_WITH_REGISTRATION_BG = "Уважаеми [#NAME#],\n\nБлагодарим за вашата покупка [#SALE_HANDLER#],\n
       Ако желаете в бъдеще да спестите време при покупки от нашия е-Магазин, моля регистрирайте се от този [#link#], който изтича след 7 дни";
      
    
    /**
     * Дефолтен шаблон за имейл на български за онлайн поръчка
     */
    const DEFAULT_EMAIL_BODY_WITHOUT_REGISTRATION_BG = "Уважаеми [#NAME#],\n\nБлагодарим за вашата покупка [#SALE_HANDLER#]";
    
    
    /**
     * Дефолтен шаблон за имейл на английски за онлайн поръчка
     */
    const DEFAULT_EMAIL_BODY_WITH_REGISTRATION_EN = "Dear [#NAME#],\n\nThank you for your purchase [#SALE_HANDLER#],
    \nIf you want to save time in the future purchases of our online shop, please register from this [#link#], which expires in 7 days";
     
    
    /**
     * Дефолтен шаблон за имейл на английски за онлайн поръчка
     */
    const DEFAULT_EMAIL_BODY_WITHOUT_REGISTRATION_EN = "Dear [#NAME#],\n\nThank you for your purchase [#SALE_HANDLER#]";
    
    
    /**
     * Колко секунди да е живота на забравените колички от регистрирани потребители
     */
    const DEFAULT_LIFETIME_USER_CARTS = 259200;
    
    
    /**
     * Колко секунди да е живота на забравените колички от нерегистрирани потребители
     */
    const DEFAULT_LIFETIME_NO_USER_CARTS = 86400;
    
    
    /**
     * Описание на модела
     */
    public function description()
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
        $this->FLD('showParams', 'keylist(mvc=cat_Params,select=typeExt)', 'caption=Показване на е-артикулите във външната част->Общи параметри,optionsFunc=cat_Params::getPublic');
        
        $this->FLD('enableCart', 'enum(yes=Винаги,no=Ако съдържа продукти)', 'caption=Показване на количката във външната част->Показване,notNull,value=no');
        $this->FLD('cartName', 'varchar(16)', 'caption=Показване на количката във външната част->Надпис');
        $this->FLD('info', 'richtext(rows=3)', 'caption=Условия на продажбата под количката->Текст');
        $this->FLD('inboxId', 'key(mvc=email_Inboxes,select=email,allowEmpty)', 'caption=Кутия от която да се изпраща имейл->Кутия');
        $this->FLD('state', 'enum(active=Активно,rejected=Оттеглен)', 'caption=Състояние,input=none,notNull,value=active');
        $this->FLD('emailBodyWithReg', 'richtext(rows=3)', 'caption=Текст на имейл за направена поръчка->С регистрация');
        $this->FLD('emailBodyWithoutReg', 'richtext(rows=3)', 'caption=Текст на имейл за направена поръчка->Без регистрация');
        $this->FLD('lifetimeForUserDraftCarts', 'time', 'caption=Изтриване на неизползвани колички->На потребители');
        $this->FLD('lifetimeForNoUserDraftCarts', 'time', 'caption=Изтриване на неизползвани колички->На анонимни');
        
        $this->setDbIndex('classId, objectId');
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     *
     * @param core_Mvc  $mvc
     * @param core_Form $form
     */
    protected static function on_AfterInputEditForm($mvc, &$form)
    {
        $rec = &$form->rec;
        if ($form->isSubmitted()) {
            if (!empty($rec->emailBodyWithReg)) {
                $missing = array();
                foreach (array('[#SALE_HANDLER#]', '[#NAME#]', '[#link#]') as $placeholder) {
                    if (strpos($rec->emailBodyWithReg, $placeholder) === false) {
                        $missing[] = $placeholder;
                    }
                }
                
                if (count($missing)) {
                    $form->setWarning('emailBodyWithReg', 'Пропуснати са следните плейсхолдъри|*: <b>' . implode(', ', $missing) . '</b>');
                }
            }
        }
    }
    
    
    /**
     * Изпълнява се преди запис
     */
    protected static function on_BeforeSave(core_Manager $mvc, $res, $rec)
    {
        if (empty($rec->validFrom)) {
            $rec->validFrom = isset($rec->modifiedOn) ? $rec->modifiedOn : dt::now();
        }
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass     $data
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = &$data->form;
        
        $domainClassId = cms_Domains::getClassId();
        $classes = array($domainClassId => core_Classes::getTitleById($domainClassId));
        $form->setOptions('classId', $classes);
        $form->setDefault('classId', key($classes));
        $form->setField('classId', 'input=hidden');
        
        if (isset($form->rec->classId)) {
            $form->setOptions('objectId', cms_Domains::getDomainOptions());
            $form->setDefault('objectId', cms_Domains::getCurrent('id', false));
        }
        
        $form->setDefault('listId', price_ListRules::PRICE_LIST_CATALOG);
        $form->setDefault('currencyId', acc_Periods::getBaseCurrencyCode());
        $form->setDefault('discountType', $mvc->getFieldType('discountType')->fromVerbal('percent'));
        
        $ownCompany = crm_Companies::fetchOurCompany();
        $shouldChargeVat = crm_Companies::shouldChargeVat($ownCompany->id);
        $defaultChargeVat = ($shouldChargeVat === true) ? 'yes' : 'no';
        $form->setDefault('chargeVat', $defaultChargeVat);
        
        $namePlaceholder = eshop_Setup::get('CART_EXTERNAL_NAME');
        $form->setField('cartName', "placeholder={$namePlaceholder}");
        $notInStockPlaceholder = eshop_Setup::get('NOT_IN_STOCK_TEXT');
        $form->setField('notInStockText', "placeholder={$notInStockPlaceholder}");
        
        // Ако има ред от количка в домейна да не може да се сменя валутата и ддс-то
        if ($rec->classId == cms_Domains::getClassId()) {
            $cartQuery = eshop_CartDetails::getQuery();
            $cartQuery->EXT('domainId', 'eshop_Carts', 'externalName=domainId,externalKey=cartId');
            $cartQuery->where("#domainId = {$rec->objectId}");
            if ($cartQuery->count()) {
                $form->setReadOnly('currencyId');
                $form->setReadOnly('chargeVat');
            }
        }
        
        // Добавяне на плейсхолдъри на някои полета
        $lang = cls::get($form->rec->classId)->fetchField($form->rec->objectId, 'lang');
        $placeholderValue = ($lang == 'bg') ? self::DEFAULT_EMAIL_BODY_WITH_REGISTRATION_BG : self::DEFAULT_EMAIL_BODY_WITH_REGISTRATION_EN;
        $form->setParams('emailBodyWithReg', array('placeholder' => $placeholderValue));
        
        $placeholderValue = ($lang == 'bg') ? self::DEFAULT_EMAIL_BODY_WITHOUT_REGISTRATION_BG : self::DEFAULT_EMAIL_BODY_WITHOUT_REGISTRATION_EN;
        $form->setParams('emailBodyWithoutReg', array('placeholder' => $placeholderValue));
        
        $form->setField('lifetimeForUserDraftCarts', 'placeholder=' . core_Type::getByName('time')->toVerbal(self::DEFAULT_LIFETIME_USER_CARTS));
        $form->setField('lifetimeForNoUserDraftCarts', 'placeholder=' . core_Type::getByName('time')->toVerbal(self::DEFAULT_LIFETIME_NO_USER_CARTS));
    }
    
    
    /**
     *  Обработки по вербалното представяне на данните
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        if (isset($rec->classId, $rec->objectId)) {
            $row->objectId = cls::get($rec->classId)->getHyperlink($rec->objectId, true);
        }
        
        if (isset($rec->listId)) {
            $row->listId = price_Lists::getHyperlink($rec->listId, true);
        }
        
        if (isset($rec->storeId)) {
            $row->storeId = store_Stores::getHyperlink($rec->storeId, true);
        }
        
        $row->ROW_ATTR['class'] = "state-{$rec->state}";
    }
    
    
    /**
     * Връща настройките на класа
     *
     * @param  int            $classId  - клас
     * @param  int            $objectId - ид на обект
     * @param  datetime|NULL  $date     - дата
     * @return FALSE|stdClass $foundRec - намерения запис
     */
    public static function getSettings($classId, $objectId, $date = null)
    {
        $classId = cls::get($classId)->getClassId();
        $cacheKey = "{$classId}|{$objectId}";
        
        if (isset($date)) {
            
            return self::get($classId, $objectId, $date);
        }
        
        $settingRec = core_Cache::get('eshop_Settings', $cacheKey);
        if (!is_object($settingRec)) {
            $date = dt::now();
            $settingRec = self::get($classId, $objectId, $date);
            if (is_object($settingRec)) {
                core_Cache::set('eshop_Settings', $cacheKey, $settingRec, 10080);
            }
        }
        
        // Ако няма тяло на имейла да се вземат дефолтните
        if (is_object($settingRec)) {
            $lang = cls::get($settingRec->classId)->fetchField($settingRec->objectId, 'lang');
            
            if (empty($settingRec->emailBodyWithReg)) {
                $settingRec->emailBodyWithReg = ($lang == 'bg') ? self::DEFAULT_EMAIL_BODY_WITH_REGISTRATION_BG : self::DEFAULT_EMAIL_BODY_WITH_REGISTRATION_EN;
            }
        
            if (empty($settingRec->emailBodyWithoutReg)) {
                $settingRec->emailBodyWithoutReg = ($lang == 'bg') ? self::DEFAULT_EMAIL_BODY_WITHOUT_REGISTRATION_BG : self::DEFAULT_EMAIL_BODY_WITHOUT_REGISTRATION_EN;
            }
            
            // Какъв е живота на количките на регистрираните потребители
            if (empty($settingRec->lifetimeForUserDraftCarts)) {
                $settingRec->lifetimeForUserDraftCarts = self::DEFAULT_LIFETIME_USER_CARTS;
            }
            
            // Какъв е живота на количките на нерегистрираните потребители
            if (empty($settingRec->lifetimeForNoUserDraftCarts)) {
                $settingRec->lifetimeForNoUserDraftCarts = self::DEFAULT_LIFETIME_NO_USER_CARTS;
            }
        }
        
        return $settingRec;
    }
    
    
    /**
     * Фечва запис
     *
     * @param  int            $classId  - клас
     * @param  int            $objectId - ид на обект
     * @param  datetime|NULL  $date     - дата
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
        
        return is_object($foundRec) ? $foundRec : null;
    }
    
    
    /**
     * Извиква се след успешен запис в модела
     *
     * @param core_Mvc $mvc
     * @param int      $id  първичния ключ на направения запис
     * @param stdClass $rec всички полета, които току-що са били записани
     */
    protected static function on_AfterSave(core_Mvc $mvc, &$id, $rec)
    {
        core_Cache::remove('eshop_Settings', "{$rec->classId}|{$rec->objectId}");
    }
    
    
    /**
     * Връща начините за доставка на домейна
     *
     * @param  mixed    $class
     * @param  int|NULL $domainId
     * @return array    $options
     */
    public static function getDeliveryTermOptions($class, $domainId = null)
    {
        $settings = self::getSettings($class, $domainId);
        $terms = keylist::toArray($settings->terms);
        
        $options = array();
        array_walk($terms, function ($termId) use (&$options) {
            $options[$termId] = cond_DeliveryTerms::getVerbal($termId, 'codeName');
        });
        
        return $options;
    }
    
    
    /**
     * Връща методите на плащане за домейна
     *
     * @param  mixed    $class
     * @param  int|NULL $domainId
     * @return array    $options
     */
    public static function getPaymentMethodOptions($class, $domainId = null)
    {
        $settings = self::getSettings($class, $domainId);
        $payments = keylist::toArray($settings->payments);
        
        $options = array();
        array_walk($payments, function ($paymentId) use (&$options) {
            $options[$paymentId] = tr(cond_PaymentMethods::getVerbal($paymentId, 'name'));
        });
         
        return $options;
    }
}
