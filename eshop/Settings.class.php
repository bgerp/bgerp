<?php


/**
 * Мениджър на настройки за ешопа
 *
 *
 * @category  bgerp
 * @package   eshop
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
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
    public $listFields = 'objectId=Обект,currencyId,chargeVat,payments,terms=Доставка,listId=Политика,storeId=Склад,discountType=Отстъпка,validFrom=Продължителност->От,validUntil=Продължителност->До,modifiedOn,modifiedBy';
    
    
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
     * Кой има право да променя системните данни?
     */
    public $canEditsysdata = 'eshop,ceo,admin';
    
    
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
     * Дефолтен шаблон за текст за добавяне към количката на bg
     */
    const DEFAULT_EMAIL_INTRODUCTION_BG = 'Здравейте [#NAME#],';
    
    
    /**
     * Дефолтен шаблон за текст за добавяне към количката на bg
     */
    const DEFAULT_EMAIL_INTRODUCTION_EN = 'Hello [#NAME#],';
    
    
    /**
     * Дефолтен шаблон за текст за добавяне към количката на bg
     */
    const DEFAULT_EMAIL_FOOTER_BG = "Сърдечни поздрави,\nЕкипът на [#COMPANY_NAME#]";
    
    
    /**
     * Дефолтен шаблон за текст за добавяне към количката на bg
     */
    const DEFAULT_EMAIL_FOOTER_EN = "Kind regards,\nThe team of [#COMPANY_NAME#]";
    
    
    /**
     * Дефолтен шаблон за текст за добавяне към количката на bg
     */
    const DEFAULT_ADD_TO_CART_TEXT_BG = 'Във вашата [#cartName#] има [#packQuantity#] [#packagingId#] от [#productName#]';
    
    
    /**
     * Дефолтен шаблон за текст за добавяне към количката на en
     */
    const DEFAULT_ADD_TO_CART_TEXT_EN = 'There are [#packQuantity#] [#packagingId#] of [#productName#] in the cart';
    
    
    /**
     * Колко секунди да е живота на забравените колички от регистрирани потребители
     */
    const DEFAULT_LIFETIME_USER_CARTS = 259200;
    
    
    /**
     * Колко секунди да е живота на забравените колички от нерегистрирани потребители
     */
    const DEFAULT_LIFETIME_NO_USER_CARTS = 86400;
    
    
    /**
     * Колко секунди преди изтриване да се изпраща нотифициращ имейл
     */
    const DEFAULT_SEND_NOTIFICAION_BEFORE_DELETION = 86400;
    
    /**
     * Колко секунди да е живота на забравените празни колички
     */
    const DEFAULT_LIFETIME_EMPTY_CARTS = 3600;
    
    
    /**
     * Заглавие на бутона за добавяне в количката на бг
     */
    const DEFAULT_ADD_TO_CART_LABEL_BG = 'Купи';
    
    
    /**
     * Заглавие на бутона за добавяне в количката на ен
     */
    const DEFAULT_ADD_TO_CART_LABEL_EN = 'Buy';
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('classId', 'class', 'caption=Клас,removeAndrefreshForm=objectId,silent,mandatory');
        $this->FLD('objectId', 'int', 'caption=Обект,mandatory,silent');
        $this->FLD('validFrom', 'datetime(timeSuggestions=00:00|04:00|08:00|09:00|10:00|11:00|12:00|13:00|14:00|15:00|16:00|17:00|18:00|22:00,format=smartTime)', 'caption=В сила->От,remember');
        $this->FLD('validUntil', 'datetime(timeSuggestions=00:00|04:00|08:00|09:00|10:00|11:00|12:00|13:00|14:00|15:00|16:00|17:00|18:00|22:00,format=smartTime,defaultTime=23:59:59)', 'caption=В сила->До,remember');
        $this->FLD('listId', 'key(mvc=price_Lists,select=title)', 'caption=Ценова политика->Политика,mandatory');
        $this->FLD('discountType', 'set(percent=Процент,amount=Намалена сума)', 'caption=Показване на отстъпки спрямо "Каталог"->Като,mandatory');
        $this->FLD('terms', 'keylist(mvc=cond_DeliveryTerms,select=codeName)', 'caption=Възможни условия на доставка->Избор,mandatory');
        $this->FLD('payments', 'keylist(mvc=cond_PaymentMethods,select=title)', 'caption=Условия на плащане->Методи,mandatory');
        $this->FLD('currencyId', 'customKey(mvc=currency_Currencies,key=code,select=code)', 'caption=Условия на плащане->Валута,mandatory,removeAndRefreshForm=freeDelivery|freeDeliveryByBus,silent');
        $this->FLD('chargeVat', 'enum(yes=Включено ДДС в цените, separate=Отделно ДДС)', 'caption=Условия на плащане->ДДС режим');
        $this->FLD('countries', 'keylist(mvc=drdata_Countries,select=commonName,selectBg=commonNameBg,allowEmpty)', 'caption=Държави,silent');
        $this->FLD('storeId', 'key(mvc=store_Stores,select=name,allowEmpty)', 'caption=Склад за наличности и Адрес при избран метод на доставка до "Локация на доставчика"->Наличности от');
        $this->FLD('locationId', 'key(mvc=crm_Locations,select=title,allowEmpty)', 'caption=Склад за наличности и Адрес при избран метод на доставка до "Локация на доставчика"->Получаване от,optionsFunc=crm_Locations::getOwnLocations');
        $this->FLD('notInStockText', 'varchar(24)', 'caption=Информация при недостатъчно количество->Текст');
        $this->FLD('showParams', 'keylist(mvc=cat_Params,select=typeExt)', 'caption=Показване на е-артикулите във външната част->Общи параметри,optionsFunc=cat_Params::getPublic');
        
        $this->FLD('enableCart', 'enum(yes=Винаги,no=Ако съдържа продукти)', 'caption=Показване на количката във външната част->Показване,notNull,value=no');
        $this->FLD('cartName', 'varchar(16)', 'caption=Показване на количката във външната част->Надпис');
        $this->FLD('canUseCards', 'enum(yes=Включено,no=Изключено)', 'caption=Възможност за логване с клиентска карта->Избор,notNull,value=yes');
        $this->FLD('locationIsMandatory', 'enum(no=Опционална,yes=Задължителна)', 'caption=Настройки на партньори за онлайн магазина->Локация,notNull,value=no');
        
        $this->FLD('addProductText', 'text(rows=3)', 'caption=Добавяне на артикул към количката->Текст');
        $this->FLD('addToCartBtn', 'varchar(16)', 'caption=Добавяне на артикул към количката->Надпис');
        $this->FLD('info', 'richtext(rows=3)', 'caption=Условия на продажбата под количката->Текст');
        $this->FLD('inboxId', 'key(mvc=email_Inboxes,select=email,allowEmpty)', 'caption=Кутия от която да се изпраща имейл->Кутия');
        $this->FLD('state', 'enum(active=Активно,rejected=Оттеглен)', 'caption=Състояние,input=none,notNull,value=active');
        $this->FLD('emailBodyIntroduction', 'richtext(rows=3)', 'caption=Текст на имейл за направена поръчка->Увод,oldFieldName=emailBody');
        $this->FLD('emailBodyFooter', 'richtext(rows=3)', 'caption=Текст на имейл за направена поръчка->Футър,oldFieldName=emailRegistrationText');
        $this->FLD('lifetimeForEmptyDraftCarts', 'time', 'caption=Изтриване на неизползвани колички->Празни');
        $this->FLD('lifetimeForNoUserDraftCarts', 'time', 'caption=Изтриване на неизползвани колички->На анонимни');
        $this->FLD('lifetimeForUserDraftCarts', 'time', 'caption=Изтриване на неизползвани колички->На потребители');
        $this->FLD('timeBeforeDelete', 'time', 'caption=Нотификация за незавършена поръчка->Изпращане,unit=преди изтриване');
        
        $this->FLD('freeDelivery', 'double(min=0)', 'caption=Безплатна доставка->Сума');
        $this->FLD('freeDeliveryByBus', 'double(min=0)', 'caption=Безплатна доставка->За маршрут');
        
        $this->FLD('dealerId', 'user(roles=sales|ceo,allowEmpty,rolesForAll=eshop|ceo|admin,rolesForTeam=eshop|ceo|admin)', 'caption=Продажби създадени от онлайн магазина->Търговец');
        
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
            
            $fieldArray = array('emailBodyIntroduction' => array('[#NAME#]'), 'emailBodyFooter' => array('[#COMPANY_NAME#]'));
            foreach ($fieldArray as $name => $placeholders){
                if (!empty($rec->{$name})) {
                    $missing = array();
                    foreach ($placeholders as $placeholder) {
                        if (strpos($rec->{$name}, $placeholder) === false) {
                            $missing[] = $placeholder;
                        }
                    }
                    if (countR($missing)) {
                        $form->setWarning($name, 'Пропуснати са следните плейсхолдъри|*: <b>' . implode(', ', $missing) . '</b>');
                    }
                }
            }
            
            // Ако локацията е задължителна, проверява се имали избрано условие за доставка с адрес на получателя
            if($rec->locationIsMandatory == 'yes'){
                $selectedTerms = keylist::toArray($rec->terms);
                $receiverTerms = cond_DeliveryTerms::getTermOptions('receiver');
                $intersectedKeys = array_intersect_key($selectedTerms, $receiverTerms);
               
                if(!countR($intersectedKeys)){
                    $receiverTerms = implode(", ", $receiverTerms);
                    $form->setError('terms,locationIsMandatory', "При задължителна локация за партньор, в условията на доставка трябва да има поне едно условие с адрес на получаване локацията на получателя като|*: <b>{$receiverTerms}</b>");
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
        $rec = $form->rec;
        
        $domainClassId = cms_Domains::getClassId();
        $classes = array($domainClassId => core_Classes::getTitleById($domainClassId));
        $form->setOptions('classId', $classes);
        $form->setDefault('classId', key($classes));
        $form->setField('classId', 'input=hidden');
        
        if (isset($rec->classId)) {
            $domainArr = cms_Domains::getDomainOptions();
            $query = self::getQuery();
            $query->in('objectId', array_keys($domainArr));
            $alreadyIn = arr::extractValuesFromArray($query->fetchAll(), 'objectId');
            if($rec->id) {
                unset($alreadyIn[$rec->objectId]);
            }
            $options = array_diff_key($domainArr, $alreadyIn);
            
            if(countR($options)){
                $form->setOptions('objectId', $options);
                $form->setDefault('objectId', cms_Domains::getCurrent('id', false));
            } else {
                $form->setReadOnly('objectId');
            }
        }
        
        $form->setDefault('listId', price_ListRules::PRICE_LIST_CATALOG);
        $form->setDefault('currencyId', acc_Periods::getBaseCurrencyCode());
        $form->setDefault('discountType', $mvc->getFieldType('discountType')->fromVerbal('percent'));
        
        $ownCompany = crm_Companies::fetchOurCompany('id,country');
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
            $cartQuery->where("#domainId = '{$rec->objectId}'");
            if ($cartQuery->count()) {
                $form->setReadOnly('currencyId');
                $form->setReadOnly('chargeVat');
            }
        }
        
        // Добавяне на плейсхолдъри на някои полета
        if (isset($rec->objectId)) {
            $lang = cls::get($rec->classId)->fetchField($rec->objectId, 'lang');
            
            $placeholderValue = ($lang == 'bg') ? self::DEFAULT_EMAIL_INTRODUCTION_BG : self::DEFAULT_EMAIL_INTRODUCTION_EN;
            $form->setParams('emailBodyIntroduction', array('placeholder' => $placeholderValue));
            
            $placeholderValue = ($lang == 'bg') ? self::DEFAULT_EMAIL_FOOTER_BG : self::DEFAULT_EMAIL_FOOTER_EN;
            $form->setParams('emailBodyFooter', array('placeholder' => $placeholderValue));
        }
        
        if(isset($rec->currencyId)){
            $form->setField('freeDelivery', "unit={$rec->currencyId}");
            $form->setField('freeDeliveryByBus', "unit={$rec->currencyId}");
        }
        
        $btnPlaceholder = ($lang == 'bg') ? self::DEFAULT_ADD_TO_CART_LABEL_BG : self::DEFAULT_ADD_TO_CART_LABEL_EN;
        $form->setField('addToCartBtn', "placeholder={$btnPlaceholder}");
    
        $companyPlaceholder = $mvc->getFieldType('countries')->toVerbal(keylist::addKey('', $ownCompany->country));
        $form->setField('countries', "placeholder={$companyPlaceholder}");
        
        // При нов запис, за имейл да е корпоратичния имейл
        if(empty($rec->id)){
            if($emailRec = email_Accounts::getCorporateAcc()){
                $defaultInboxId = email_Inboxes::fetchField("#email = '{$emailRec->email}'", 'id');
                $form->setDefault('inboxId', $defaultInboxId);
            }
        }
        
        $form->setField('lifetimeForUserDraftCarts', 'placeholder=' . core_Type::getByName('time')->toVerbal(self::DEFAULT_LIFETIME_USER_CARTS));
        $form->setField('lifetimeForNoUserDraftCarts', 'placeholder=' . core_Type::getByName('time')->toVerbal(self::DEFAULT_LIFETIME_NO_USER_CARTS));
        $form->setField('lifetimeForEmptyDraftCarts', 'placeholder=' . core_Type::getByName('time')->toVerbal(self::DEFAULT_LIFETIME_EMPTY_CARTS));
        $form->setField('timeBeforeDelete', 'placeholder=' . core_Type::getByName('time')->toVerbal(self::DEFAULT_SEND_NOTIFICAION_BEFORE_DELETION));
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
     * @param int           $classId  - клас
     * @param int           $objectId - ид на обект
     * @param datetime|NULL $date     - дата
     *
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
            $settingRec->lg = $lang;
            
            if (empty($settingRec->emailBodyIntroduction)) {
                $settingRec->emailBodyIntroduction = ($lang == 'bg') ? self::DEFAULT_EMAIL_INTRODUCTION_BG : self::DEFAULT_EMAIL_INTRODUCTION_EN;
            }
            
            if (empty($settingRec->emailBodyFooter)) {
                $settingRec->emailBodyFooter = ($lang == 'bg') ? self::DEFAULT_EMAIL_FOOTER_BG : self::DEFAULT_EMAIL_FOOTER_EN;
            }
            
            if (empty($settingRec->addProductText)) {
                $settingRec->addProductText = ($lang == 'bg') ? self::DEFAULT_ADD_TO_CART_TEXT_BG : self::DEFAULT_ADD_TO_CART_TEXT_EN;
            }
            
            // Какъв е живота на количките на регистрираните потребители
            if (empty($settingRec->lifetimeForUserDraftCarts)) {
                $settingRec->lifetimeForUserDraftCarts = self::DEFAULT_LIFETIME_USER_CARTS;
            }
            
            // Какъв е живота на количките на нерегистрираните потребители
            if (empty($settingRec->lifetimeForNoUserDraftCarts)) {
                $settingRec->lifetimeForNoUserDraftCarts = self::DEFAULT_LIFETIME_NO_USER_CARTS;
            }
            
            if (empty($settingRec->lifetimeForEmptyDraftCarts)) {
                $settingRec->lifetimeForEmptyDraftCarts = self::DEFAULT_LIFETIME_EMPTY_CARTS;
            }
            
            if (empty($settingRec->timeBeforeDelete)) {
                $settingRec->timeBeforeDelete = self::DEFAULT_SEND_NOTIFICAION_BEFORE_DELETION;
            }
            
            if (empty($settingRec->addToCartBtn)) {
                $settingRec->addToCartBtn = ($lang == 'bg') ? self::DEFAULT_ADD_TO_CART_LABEL_BG : self::DEFAULT_ADD_TO_CART_LABEL_EN;
            }
            
            if (empty($settingRec->countries)) {
                $settingRec->countries = keylist::addKey('', crm_Companies::fetchOurCompany('country')->country);
            }
            
            if (empty($settingRec->partnerTerms)) {
                $settingRec->partnerTerms = $settingRec->terms;
            }
        }
        
        return $settingRec;
    }
    
    
    /**
     * Фечва запис
     *
     * @param int           $classId  - клас
     * @param int           $objectId - ид на обект
     * @param datetime|NULL $date     - дата
     *
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
     * @param mixed    $class
     * @param int|NULL $domainId
     *
     * @return array $options
     */
    public static function getDeliveryTermOptions($class, $domainId = null)
    {
        $settings = self::getSettings($class, $domainId);
        $terms = keylist::toArray($settings->terms);
        $cu = core_Users::getCurrent('id', false);
        
        $options = array();
        array_walk($terms, function ($termId) use (&$options, $cu) {
            $options[$termId] = cond_DeliveryTerms::getVerbal($termId, 'codeName');
            if($Calc = cond_DeliveryTerms::getTransportCalculator($termId)){
               
                if(!$Calc->canSelectInEshop($termId, $cu)){
                    unset($options[$termId]);
                }
            }
        });
        
        return $options;
    }
    
    
    /**
     * Връща методите на плащане за домейна
     *
     * @param mixed    $class
     * @param int|NULL $domainId
     *
     * @return array $options
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
    
    
    /**
     * Извиква се след SetUp-а на таблицата за модела
     */
    public function loadSetupData()
    {
        $res = '';
        $domainClassId = cms_Domains::getClassId();
        
        $dQuery = cms_Domains::getQuery();
        while($dRec = $dQuery->fetch()){
            if(!eshop_Settings::fetch("#classId = {$domainClassId} AND #objectId = {$dRec->id}")){
                $settingRec = (object)array('classId' => $domainClassId, 'objectId' => $dRec->id, 'listId' => price_ListRules::PRICE_LIST_CATALOG);
                $settingRec->discountType = 'percent';
                $settingRec->enableCart = 'no';
                $settingRec->currencyId = acc_Periods::getBaseCurrencyCode();
                $settingRec->chargeVat = 'yes';
                
                eshop_Settings::save($settingRec);
            }
        }
        
        return $res;
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = null, $userId = null)
    {
        // Не може да се клонира ако потребителя няма достъп до папката
        if (in_array($action, array('edit', 'reject', 'restore')) && isset($rec)) {
            if(!cls::get($rec->classId)->haveRightFor('select', $rec->objectId)){
                $res = 'no_one';
            }
        }
    }
}
