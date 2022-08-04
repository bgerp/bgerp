<?php


/**
 * Клас 'cond_DeliveryTerms' - Условия на доставка
 *
 * Набор от стандартните условия на доставка (FOB, DAP, ...)
 *
 *
 * @category  bgerp
 * @package   cond
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2021 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class cond_DeliveryTerms extends core_Master
{
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created, plg_RowTools2, cond_Wrapper, plg_State2';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'codeName, term, costCalc=Транспорт->Калкулатор, calcCost=Транспорт->Скрито,properties, address, state, createdBy,createdOn';
    
    
    /**
     * Кои полета от листовия изглед да се скриват ако няма записи в тях
     */
    public $hideListFieldsIfEmpty = 'costCalc, calcCost, lastUsedOn';
    
    
    /**
     * Поле в което ще се показва тулбара
     */
    public $rowToolsSingleField = 'codeName';
    
    
    /**
     * Кой може да пише
     */
    public $canWrite = 'ceo,admin';
    
    
    /**
     * Кой може да добавя
     */
    public $canAdd = 'ceo,admin';
    
    
    /**
     * Кой може да променя
     */
    public $canEdit = 'ceo,admin';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,admin';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'ceo,admin';
    
    
    /**
     * Кой може да променя състоянието на Условията на доставка
     */
    public $canChangestate = 'ceo,admin';
    
    
    /**
     * Заглавие
     */
    public $title = 'Условия на доставка';
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Условие на доставка';
    
    
    /**
     * Икона по подразбиране за единичния обект
     */
    public $singleIcon = 'img/16/delivery.png';
    
    
    /**
     * Нов темплейт за показване
     */
    public $singleLayoutFile = 'cond/tpl/SingleDeliveryTerms.shtml';
    
    
    /**
     * Кой има право да променя системните данни?
     */
    public $canEditsysdata = 'ceo,admin';
    
    
    /**
     * Шаблон (ET) за заглавие на продукт
     *
     * @var string
     */
    public $recTitleTpl = '[#codeName#]';
    
    
    /**
     * Дали при обновяване от импорт на същестуващ запис да се запази предишното състояние или не
     *
     * @see plg_State2
     */
    public $updateExistingStateOnImport = false;
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('codeName', 'varchar', 'caption=Код');
        $this->FLD('properties', 'set(cmr=ЧМР,transport=Транспорт,insurance=Застраховка,vodeu=ВОД за Чужбина ЕС)', 'caption=Свойства');
        $this->FLD('term', 'text(rows=3)', 'caption=Обяснение');
        $this->FLD('forSeller', 'text(rows=3)', 'caption=За продавача');
        $this->FLD('forBuyer', 'text(rows=3)', 'caption=За купувача');
        $this->FLD('transport', 'text(rows=3)', 'caption=Транспорт');
        $this->FLD('costCalc', 'class(interface=cond_TransportCalc,allowEmpty,select=title)', 'caption=Изчисляване на транспортна себестойност->Калкулатор');
        $this->FLD('calcCost', 'enum(yes=Включено,no=Изключено)', 'caption=Изчисляване на транспортна себестойност->Скрито,notNull,value=no');
        $this->FLD('address', 'enum(none=Не се показва,receiver=Локацията на получателя,supplier=Локацията на доставчика)', 'caption=Показване на мястото на доставка->Избор,notNull,value=none,default=none');
        $this->FLD('lastUsedOn', 'datetime(format=smartTime)', 'caption=Последна употреба,input=none,column=none');

        $this->setDbUnique('codeName');
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
        
        if ($form->rec->createdBy == core_Users::SYSTEM_USER) {
            $form->setReadOnly('codeName');
            foreach (array('term', 'forSeller', 'forBuyer', 'transport', 'address') as $fld) {
                $form->setField($fld, 'input=none');
            }
        }
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
            if (strpos($rec->codeName, ':') !== false) {
                $form->setError('codeName', 'Кода не може да съдържа|* "<b>:</b>"');
            }
        }
    }
    
    
    /**
     * Връща имплементация на драйвера за изчисляване на транспортната себестойност
     *
     * @param mixed $id - ид, запис или NULL
     *
     * @return cond_TransportCalc|NULL
     */
    public static function getTransportCalculator($id)
    {
        if (!empty($id)) {
            $rec = self::fetchRec($id);
            if (cls::load($rec->costCalc, true)) {
                
                return cls::getInterface('cond_TransportCalc', $rec->costCalc);
            }
        }
    }
    
    
    /**
     * Дали да се изчислява скрития транспорт, за дадения артикул
     *
     * @param mixed $id        - ид или запис
     * @param int   $productId - ид на артикул
     *
     * @return bool $res  - да се начислява ли скрит транспорт или не
     */
    public static function canCalcHiddenCost($id, $productId)
    {
        if (!$id) {
            
            return false;
        }
        
        expect($rec = self::fetchRec($id));
        if ($rec->calcCost == 'yes') {
            
            // Може да се начислява скрит транспорт само за складируем артикул, ако в условието на доставка е разрешено
            if (empty($productId)) {
                
                return false;
            }
            
            if (cat_Products::fetchField($productId, 'canStore') == 'yes') {
                
                return true;
            }
        }
        
        return false;
    }
    
    
    /**
     * Извиква се след SetUp-а на таблицата за модела
     */
    protected static function on_AfterSetupMvc($mvc, &$res)
    {
        $file = 'cond/csv/DeliveryTerms.csv';
        $fields = array(
            0 => 'term',
            1 => 'codeName',
            2 => 'forSeller',
            3 => 'forBuyer',
            4 => 'transport',
            5 => 'address',
            6 => 'properties',
        );
        
        $cntObj = csv_Lib::importOnce($mvc, $file, $fields);
        $res .= $cntObj->html;
        
        return $res;
    }
    
    
    /**
     * Проверява даден стринг дали съдържа валиден код CASE SENSITIVE
     *
     * @param string $code - код
     *
     * @return int|NULL - ид на кода или NULL - ако не е открит
     */
    public static function getTermCodeId($code)
    {
        // Разделяме въведения стринг на интервали
        $params = explode(':', $code);
        
        // Кода трябва да е в първите символи
        $foundCode = trim($params[0]);
        
        // Ако няма запис с намерения код, връщаме FALSE
        $rec = static::fetch(array("#codeName = '[#1#]'", $foundCode));
        
        // Ако е намерено нещо връщаме го
        if (isset($rec)) {
            
            return $rec->id;
        }
        
        // Ако стигнем до тук, значи кода е валиден
    }
    
    
    /**
     * Помощен метод допълващ условието на доставка с адреса
     *
     * @param int      $deliveryCode      - текста на търговското условие
     * @param int      $contragentClassId - класа на контрагента
     * @param int      $contragentId      - ид на котнрагента
     * @param int      $storeId           - ид на склада
     * @param int      $locationId        - ид на локация
     * @param int      $deliveryData      - други параметри
     * @param core_Mvc $document          - за кой документ се отнася
     *
     * @return string - условието за доставка допълнено с адреса, ако може да се определи
     */
    public static function addDeliveryTermLocation($deliveryCode, $contragentClassId, $contragentId, $storeId, $locationId, $deliveryData, $document)
    {
        $adress = null;
        $isSale = ($document instanceof sales_Sales || $document instanceof sales_Quotations);
        if(empty($deliveryCode)){
            if(!empty($locationId)){
                $adress = crm_Locations::getAddress($locationId, true);
            }

            return $adress;
        }
        
        expect($rec = self::fetch(array('[#1#]', $deliveryCode)));
        
        if (($rec->address == 'supplier' && $isSale === true) || ($rec->address == 'receiver' && $isSale === false)) {
            if (isset($storeId)) {
                if ($locationId = store_Stores::fetchField($storeId, 'locationId')) {
                    $adress = crm_Locations::getAddress($locationId, true);
                }
            }
            
            if (empty($adress)) {
                $ownCompany = crm_Companies::fetchOurCompany();
                $adress = cls::get('crm_Companies')->getFullAdress($ownCompany->id, true, null, false)->getContent();
            }
        } elseif (($rec->address == 'receiver' && $isSale === true) || ($rec->address == 'supplier' && $isSale === false)) {
            if (!empty($locationId)) {
                $adress = crm_Locations::getAddress($locationId, true);
            } else {
                $adress = cls::get($contragentClassId)->getFullAdress($contragentId, true, null, false)->getContent();
            }
        }
        
        $adress = trim(strip_tags($adress));
        
        return $adress;
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     */
    public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = null, $userId = null)
    {
        if ($action == 'delete' && isset($rec->lastUsedOn)) {
            $res = 'no_one';
        }
    }
    
    
    /**
     * Модификация на изгледа на количката в е-шоп
     *
     * @param int $id
     * @param stdClass $cartRec
     * @param stdClass $cartRow
     * @param core_ET $tpl
     *
     * @return void
     */
    public static function addToCartView($id, $cartRec, $cartRow, &$tpl)
    {
        $rec = self::fetchRec($id);
        
        $Calculator = self::getTransportCalculator($rec);
        if($Calculator){
            $Calculator->addToCartView($rec, $cartRec, $cartRow, $tpl);
        }
    }
    
    
    /**
     * Подготовка на формата за инпут
     *
     * @param mixed $id
     * @param core_FieldSet $form
     * @param mixed $document
     * @param null|int $userId
     * @return void
     */
    public static function prepareDocumentForm($id, core_FieldSet &$form, $document, $userId = null)
    {
        $rec = self::fetchRec($id);
        $Document = cls::get($document);
        
        // Ако ще се показва адреса на доставчик
        if($rec->address == 'supplier'){
            if($Document instanceof sales_Sales){
                unset($form->rec->deliveryLocationId, $form->rec->deliveryAdress);
                $form->setReadOnly('deliveryLocationId');
                $form->setReadOnly('deliveryAdress');
            } elseif($Document instanceof eshop_Carts){
                unset($form->rec->deliveryCountry, $form->rec->deliveryPCode, $form->rec->deliveryPlace, $form->rec->deliveryAddress);
                
                // Имали избрана локация или склад в настройките на магазина
                $settings = cms_Domains::getSettings();
                $ownCompany = crm_Companies::fetchOurCompany();
                $countryId = $ownCompany->country;
                $locationId = isset($settings->locationId) ? $settings->locationId : (isset($settings->storeId) ? store_Stores::fetchField($settings->storeId, 'locationId') : null);
                
                // Ако има взима се нейния адрес, ако не адреса на "Моята фирма"
                if(isset($locationId)){
                    $locationRec = crm_Locations::fetch($locationId, 'countryId,place,pCode,address');
                    $countryId = (!empty($locationRec->country)) ? $locationRec->country : $countryId;
                    $pCode = $locationRec->pCode;
                    $place = $locationRec->place;
                    $address = $locationRec->address;
                } else {
                    $pCode = $ownCompany->pCode;
                    $place = $ownCompany->place;
                    $address = $ownCompany->address;
                }
               
                $form->setReadOnly('deliveryCountry', $countryId);
                $form->setReadOnly('deliveryPCode', $pCode);
                $form->setReadOnly('deliveryPlace', $place);
                $form->setReadOnly('deliveryAddress', $address);
                $form->setField('locationId', 'input=none');
            }
        }
        
        $cu = core_Users::getCurrent('id', false);
        $isColabReceiver = ($cu && $rec->address == 'receiver' && ($Document instanceof eshop_Carts) && core_Users::isContractor($cu));
        
        $settings = cms_Domains::getSettings();
        if($isColabReceiver && $settings->locationIsMandatory == 'yes'){
            $form->setField('deliveryCountry', 'input=hidden');
            $form->setField('deliveryPCode', 'input=hidden');
            $form->setField('deliveryPlace', 'input=hidden');
            $form->setField('deliveryAddress', 'input=hidden');
        }
        
        $Calculator = self::getTransportCalculator($rec);
        if($Calculator){
            $Calculator->addFields($form, $document, $userId);
        } elseif($Document instanceof eshop_Carts && $rec->address != 'supplier' && !$isColabReceiver) {
            //$form->setField('deliveryPCode', 'mandatory');
            //$form->setField('deliveryPlace', 'mandatory');
        }
        
        if($Document instanceof deals_DealMaster || $Document instanceof eshop_Carts || $Document instanceof sales_Quotations){
            $fields = self::getAdditionalFields($rec, $document);
           
            if(countR($fields)){
                foreach ($fields as $fld) {
                    $form->setDefault($fld, $form->rec->deliveryData[$fld]);
                }
            } else {
                $form->rec->deliveryData = null;
            }
        }
    }
    
    
    /**
     * Обработка на фомрата на документа след инпут
     * 
     * @param mixed $id
     * @param core_FieldSet $form
     * @param mixed $document
     * @param null|int $userId
     * @return void
     */
    public static function inputDocumentForm($id, core_FieldSet &$form, $document, $userId = null)
    {
        $Document = cls::get($document);
        $formRec = &$form->rec;
        
        if ($Document instanceof deals_DealMaster || $Document instanceof eshop_Carts || $Document instanceof sales_Quotations) {
            
            // Компресиране на данните за доставка от драйвера
            $formRec->deliveryData = array();
            $fields = self::getAdditionalFields($id, $document);
            foreach ($fields as $name) {
                $formRec->deliveryData[$name] = $formRec->{$name};
            }
        }
        
        if ($Document instanceof sales_Sales || $Document instanceof sales_Quotations) {
            $deliveryData = is_array($formRec->deliveryData) ? $formRec->deliveryData : array();
            
            $locationId = $formRec->deliveryLocationId;
            if($Document instanceof sales_Quotations){
                if(!empty($formRec->deliveryPlaceId)){
                    $locationId = crm_Locations::fetchField(array("#title = '[#1#]' AND #contragentCls = '{$formRec->contragentClassId}' AND #contragentId = '{$formRec->contragentId}'", $formRec->deliveryPlaceId), 'id');
                }
            }
            
            if ($error = sales_TransportValues::getDeliveryTermError($id, $formRec->deliveryAdress, $formRec->contragentClassId, $formRec->contragentId, $locationId, $deliveryData)) {
                $form->setError('deliveryTermId,deliveryAdress,deliveryLocationId', $error);
            }
        }
    }
    
    
    /**
     * Проверява данните на доставка преди активация
     * 
     * @param mixed $id
     * @param stdClass $documentRec
     * @param array $deliveryData
     * @param mixed $document
     * @param string|null $error
     * @return boolean
     */
    public static function checkDeliveryDataOnActivation($id, $documentRec, $deliveryData, $document, &$error = null)
    {
        $rec = self::fetchRec($id);
        $res = true;
        
        $Calculator = self::getTransportCalculator($rec);
        if($Calculator){
            $res = $Calculator->checkDeliveryDataOnActivation($rec, $documentRec, $deliveryData, $document, $error);
        }
        
        return $res;
    }
    
    
    /**
     * Кои полета са допълнени от условието
     * 
     * @param mixed $id            - ид
     * @param mixed|null $document - клас на документ
     * @return array $fields       - полета
     */
    public static function getAdditionalFields($id, $document)
    {
        $fields = array();
        
        $Calculator = self::getTransportCalculator($id);
        if($Calculator){
            $fields += $Calculator->getFields($document);
        }
        
        return $fields;
    }
    
    
    /**
     * Връща опции за избор на условия за доставка
     * 
     * @param string|null $addRessType
     * @return array $res
     */
    public static function getTermOptions($addRessType = null)
    {
        $res = array();
        $query = self::getQuery();
        $query->where("#state != 'closed'");
        if(isset($addRessType)){
            expect(in_array($addRessType, array('none', 'receiver', 'supplier')));
            $query->where("#address = '{$addRessType}'");
        }
        
        while($rec = $query->fetch()){
            $res[$rec->id] = self::getVerbal($rec, 'codeName');
        }
        
        return $res;
    }
}
