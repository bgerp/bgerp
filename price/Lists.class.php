<?php


/**
 * Ценови политики
 *
 *
 * @category  bgerp
 * @package   price
 *
 * @author    Milen Georgiev <milen@experta.bg> и Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2024 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Ценови политики
 */
class price_Lists extends core_Master
{
    /**
     * Заглавие
     */
    public $title = 'Ценови политики';
    
    
    /**
     * Наименование на единичния обект
     */
    public $singleTitle = 'Ценова политика';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, price_Wrapper, plg_Search, doc_DocumentPlg, doc_plg_SelectFolder';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'title,parent,folderId';
    
    
    /**
     * Абревиатура
     */
    public $abbr = 'Pl';
    
    
    /**
     * Дали може да бъде само в началото на нишка
     */
    public $onlyFirstInThread = true;
    
    
    /**
     * Детайла, на модела
     */
    public $details = 'price_ListRules,price_ListVariations,price_ListBasicDiscounts';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'title, parent, folderId, createdOn, createdBy';
    
    
    /**
     * Кой може да го промени?
     */
    public $canEdit = 'price,sales,ceo';
    
    
    /**
     * Кой може да редактира системните данни
     */
    public $canEditsysdata = 'price,sales,ceo';
    
    
    /**
     * Кой може да променя типа на политиката?
     */
    public $canChangepublic = 'priceMaster,ceo';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'price,sales,ceo';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'price,sales,ceo';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'powerUser';
    
    
    /**
     * Може ли да се редактират активирани документи
     */
    public $canEditActivated = true;
    
    
    /**
     * Поле за връзка към единичния изглед
     */
    public $rowToolsSingleField = 'title';
    
    
    /**
     * Шаблон за единичния изглед
     */
    public $singleLayoutFile = 'price/tpl/SingleLayoutLists.shtml';


    /**
     * Кой може да вижда частния сингъл
     */
    public $canViewpsingle = 'powerUser';


    /**
     * Работен кеш
     */
    protected static $cache = array();
    
    
    /**
     * Групиране на документите
     */
    public $newBtnGroup = '3.91|Търговия';
    
    
    /**
     * Списък с корици и интерфейси, където може да се създава нов документ от този клас
     */
    public $coversAndInterfacesForNewDoc = 'crm_ContragentAccRegIntf,doc_UnsortedFolders';
    
    
    /**
     * Да се забрани ли кеширането на документа
     */
    public $preventCache = true;


    /**
     * Дали се очаква в документа да има файлове
     */
    public $expectFiles = false;


    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('title', 'varchar(128,ci)', 'mandatory,caption=Наименование,hint=Наименование на ценовата политика');
        $this->FLD('parent', 'key(mvc=price_Lists,select=title,allowEmpty)', 'caption=Наследява');
        $this->FLD('public', 'enum(no=Не,yes=Да)', 'caption=Публичен,input=none');
        $this->FLD('currency', 'customKey(mvc=currency_Currencies,key=code,select=code)', 'notNull,caption=Валута');
        $this->FLD('vat', 'enum(yes=Включено,no=Без ДДС)', 'caption=ДДС');
        $this->FLD('vatExceptionId', 'key(mvc=cond_VatExceptions,select=title,allowEmpty)', 'caption=ДДС изключение');

        $this->FLD('cId', 'int', 'caption=Клиент->Id,input=hidden,silent');
        $this->FLD('cClass', 'class(select=title,interface=crm_ContragentAccRegIntf)', 'caption=Клиент->Клас,input=hidden,silent');
        $this->FLD('discountCompared', 'key(mvc=price_Lists,select=title,where=#state !\\= \\\'rejected\\\',allowEmpty)', 'caption=Показване на отстъпка в документите спрямо->Ценоразпис');
        $this->FLD('discountComparedShowAbove', 'percent(min=0)', 'caption=Показване на отстъпка в документите->Ако е над,placeholder=1 %');
        $this->FLD('visiblePricesByAnyone', 'enum(no=Само потребители с права,yes=За всички)', 'caption=Видимост на цените->Избор,notNull,value=no');
        $this->FLD('minDecimals', 'double(smartRound)', 'caption=Закръгляне за избрания вид (с/без ДДС) цени (стойности 2 и 1 за цена Х.хх)->Десетични знаци', "unit= (|желан брой цифри след десетичната запетая|*)");
        $this->FLD('significantDigits', 'double(smartRound)', 'caption=Закръгляне за избрания вид (с/без ДДС) цени (стойности 2 и 1 за цена Х.хх)->Значещи цифри', "unit= (|но минимален брой цифри различни от|* 0)");
        $this->FLD('defaultSurcharge', 'percent(min=-1,max=1)', 'caption=Надценка / Отстъпка по подразбиране->Процент', "unit= |(със знак минус за Отстъпка)");
        $this->FLD('minSurcharge', 'percent', 'caption=Надценки за нестандартни продукти->Минимална');
        $this->FLD('maxSurcharge', 'percent', 'caption=Надценки за нестандартни продукти->Максимална');
        $this->FLD('discountClassPeriod', 'enum(default=За продажба,daily=За ден,monthly=За текущ месец,hourly=В рамките на 1 час)', 'caption=Автоматични отстъпки->Сума за отстъпки,autohide,notNull,value=default');
        $this->FLD('haveBasicDiscounts', 'enum(no=Няма,yes=Има)', 'caption=Автоматични отстъпки->Има ли,notNull,value=no,input=none');
        $this->FLD('orderGroupRules', 'enum(validFrom=Валидност (низходящ),name=Наименование (възходящ))', 'caption=Подредба на груповите правила->Избор,autohide,notNull,value=validFrom');

        $this->setDbUnique('title');
        $this->setDbIndex('cId,cClass');
    }
    
    
    /**
     * Интерфейсен метод на doc_DocumentInterface
     */
    public function getDocumentRow_($id)
    {
        $rec = $this->fetch($id);
        $row = new stdClass();
        $title = $this->getVerbal($rec, 'title');
        
        $row->title = tr($this->singleTitle) . " \"{$title}\"";
        $row->authorId = $rec->createdBy;
        $row->author = $this->getVerbal($rec, 'createdBy');
        $row->recTitle = $row->title;
        $row->state = $rec->state;
        
        return $row;
    }
    
    
    /**
     * Изпълнява се преди запис
     */
    public static function on_BeforeSave(core_Manager $mvc, $res, $rec)
    {
        if (isset($rec->folderId)) {
            $Cover = doc_Folders::getCover($rec->folderId);
            if ($Cover->haveInterface('crm_ContragentAccRegIntf')) {
                $rec->public = 'no';
                $rec->cClass = $Cover->getClassId();
                $rec->cId = $Cover->that;
            } else {
                $rec->public = 'yes';
            }
        }

        if(isset($rec->id)){
            $exRec = $mvc->fetch($rec->id, '*', false);
            $checkExFields = md5("{$exRec->parent}|{$exRec->currency}|{$exRec->vat}|{$exRec->discountCompared}|{$exRec->discountComparedShowAbove}|{$exRec->defaultSurcharge}|{$exRec->defaultSurcharge}|{$exRec->minSurcharge}|{$exRec->maxSurcharge}");
            $checkCurrentFields = md5("{$rec->parent}|{$rec->currency}|{$rec->vat}|{$rec->discountCompared}|{$rec->discountComparedShowAbove}|{$rec->defaultSurcharge}|{$rec->defaultSurcharge}|{$rec->minSurcharge}|{$rec->maxSurcharge}");
            if($checkExFields != $checkCurrentFields){
                $rec->_invalidateCache = true;
            }
        }
    }
    
    
    /**
     * Коя е дефолт папката за нови записи
     */
    public function getDefaultFolder()
    {
        $folderRec = (object) array('name' => $this->title);
        
        return doc_UnsortedFolders::forceCoverAndFolder($folderRec);
    }
    
    
    /**
     * Метод за форсиране на ценова политика.
     * Ако няма политика с това име я създава. Ако има я модифицира.
     *
     * @param string $title                  - заглавие
     * @param mixed  $cClass                 - клас на контрагента
     * @param int    $cId                    - ид на контрагента
     * @param string $parentTitle            - заглавие на политиката-баща
     * @param string $currencyCode           - код на валута по подразбиране на политиката
     * @param bool   $vat                    - дали политиката е с включен ДДС или не
     * @param float  $defaultSurcharge       - дефолтна надценка между 0 и 1
     * @param string $discountComparedToList - име на политиката спрямо който ще се показва отстъпка
     * @param float  $roundingPrecision      - закръгляне до десетичен знак
     * @param float  $roundingOffset         - отместване на закръглянето
     *
     * @return int $id                        - ид на създадения каталог
     */
    public static function forceList($title, $cClass = null, $cId = null, $public = true, $parentTitle = null, $currencyCode = null, $vat = true, $defaultSurcharge = null, $discountComparedToList = null, $roundingPrecision = null, $roundingOffset = null)
    {
        // Заглавие на политиката
        $self = cls::get(get_called_class());
        $title = str::mbUcfirst($title);
        $parentId = null;
        
        // Ако искаме да наследява друга политика, то трябва да има такава
        if (isset($parentTitle)) {
            $parentTitle = str::mbUcfirst($parentTitle);
            expect($parentId = self::fetchField(array("#title = '[#1#]'", $parentTitle)), 'Няма политика с това име');
        }
        
        // Трябва да е зададен контрагент или да не е зададен
        expect((!isset($cClass) && !isset($cId)) || (isset($cClass, $cId)));
        
        // Ако е зададен контрагент, той трябва да съществува
        if (isset($cClass, $cId)) {
            expect(is_numeric($cId));
            expect($ContragentClass = cls::get($cClass), 'Невалиден клас');
            expect($ContragentClass->fetch($cId), 'Няма такъв контрагент');
            $folderId = $ContragentClass->forceCoverAndFolder($cId);
            $cClass = $ContragentClass->getClassId();
        } else {
            $folderId = $self->getDefaultFolder();
        }
        
        // Валута на каталога
        if (isset($currencyCode)) {
            $currencyCode = mb_strtoupper($currencyCode);
            expect(currency_Currencies::getIdByCode($currencyCode));
        } else {
            $currencyCode = acc_Periods::getBaseCurrencyCode();
        }
        
        expect(is_bool($vat));
        
        if (isset($defaultSurcharge)) {
            expect(is_numeric($defaultSurcharge));
            expect($defaultSurcharge >= 0 && $defaultSurcharge <= 1);
        }
        
        // Ако искаме да се показват отстъпките към друг каталог то трябва да има такъв
        $discountCompareToId = null;
        if (isset($discountComparedToList)) {
            $discountComparedToList = str::mbUcfirst($discountComparedToList);
            expect($discountCompareToId = self::fetchField(array("#title = '[#1#]'", $discountComparedToList)), 'Няма политика с това име');
        }
        
        if (isset($roundingPrecision)) {
            expect(is_numeric($roundingPrecision));
        }
        
        if (isset($roundingOffset)) {
            expect(is_numeric($roundingOffset));
        }
        
        // Записа, който ще записваме
        $rec = (object) array('title' => $title,
            'parent' => $parentId,
            'cClass' => $cClass,
            'cId' => $cId,
            'currency' => $currencyCode,
            'vat' => ($vat === true) ? 'yes' : 'no',
            'defaultSurcharge' => $defaultSurcharge,
            'discountCompared' => $discountCompareToId,
            'roundingPrecision' => $roundingPrecision,
            'roundingOffset' => $roundingOffset,
            'state' => 'active',
            'folderId' => $folderId,
        );
        
        // Ако има политика с такова име, обновяваме я
        if ($exRec = self::fetch(array("#title = '[#1#]'", $title))) {
            $rec->id = $exRec->id;
            $rec->threadId = $exRec->threadId;
            $rec->containerId = $exRec->containerId;
        } else {
            $self->route($rec);
        }
        
        // Запис
        $id = static::save($rec);
        
        // Връщаме ид-то на запазения запис
        return $id;
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass     $data
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = $data->form;
        $rec = $form->rec;

        $form->FNC('variationOf', 'key(mvc=price_Lists,select=title,allowEmpty)', 'silent,input=hidden');
        $form->input('variationOf', 'silent');
        if(isset($rec->variationOf)){
            $form->info = "<div class='richtext-info-no-image'>" . tr('Вариация на|*: ') . price_Lists::getHyperlink($rec->variationOf, true) . "</div>";
        }

        $folderId = $rec->folderId;
        if (isset($rec->cClass, $rec->cId)) {
            $Cover = new core_ObjectReference($rec->cClass, $rec->cId);
        } else {
            $Cover = doc_Folders::getCover($folderId);
        }
        
        $form->rec->folderId = $Cover->forceCoverAndFolder();

        // Кои са достъпните политики
        $parentOptions = self::getAccessibleOptions();
        if (empty($rec->id)) {

            // По дефолт слагаме за частните политики да наследяват дефолт политиката за контрагента, иначе 'Каталог'
            $rec->parent = ($rec->cId && $rec->cClass) ? price_ListToCustomers::getListForCustomer($rec->cClass, $rec->cId) : cat_Setup::get('DEFAULT_PRICELIST');
        } else {
            // Ако наследената политика, не присъства в опциите, задаваме я за да не се затрие
            if($rec->parent && !array_key_exists($rec->parent, $parentOptions)){
                $parentOptions[$rec->parent] = static::getVerbal($rec->parent, 'title');
            }

            // От наличните политики за наследяване, се махат тези, в които текущата вече е наследена, да не става зацикляне
            foreach ($parentOptions as $k => $v){
                $parents = $mvc->getParents($k);
                if(array_key_exists($rec->id, $parents)){
                    unset($parentOptions[$k]);
                }
            }

            // Ако има правило за МАРЖ политиката, трябва винаги да е базирана на друга политика
            if(price_ListRules::fetchField("#type != 'value' AND #listId = {$rec->id}")){
                $form->setField('parent', 'mandatory');
            }
        }

        $form->setOptions('parent', $parentOptions);
        $form->setDefault('currency', acc_Periods::getBaseCurrencyCode());
        
        // За политиката себестойност, скриваме определени полета
        if ($rec->id == price_ListRules::PRICE_LIST_COST) {
            foreach (array('parent', 'public', 'discountCompared', 'defaultSurcharge', 'minSurcharge', 'maxSurcharge') as $fld) {
                $form->setField($fld, 'input=hidden');
            }
        } else {
            $digits = price_Setup::get('SIGNIFICANT_DIGITS');
            $minDecimals = price_Setup::get('MIN_DECIMALS');
            $form->setField('significantDigits', "placeholder={$digits}");
            $form->setField('minDecimals', "placeholder={$minDecimals}");
        }
    }


    /**
     * Връща всички политики, които са наследени
     *
     * @param mixed $id
     * @return array $parents
     */
    private function getParents($id)
    {
        $rec = $this->fetchRec($id);
        $parents = array($rec->id => $rec->id);
        if(isset($rec->parent)){
            $parents[$rec->parent] = $rec->parent;
        }
        $parent = $rec->parent;

        while ($parent && ($lRec = $this->fetch($parent, 'parent'))) {
            if(!empty($lRec->parent)){
                $parents[$lRec->parent] = $lRec->parent;
            }
            $parent = $lRec->parent;
        }

        return $parents;
    }


    /**
     * След подготовката на заглавието на формата
     */
    protected static function on_AfterPrepareEditTitle($mvc, &$res, &$data)
    {
        $rec = $data->form->rec;
        if ($rec->cId && $rec->cClass) {
            $data->form->title = core_Detail::getEditTitle($rec->cClass, $rec->cId, 'ценова политика', $rec->id, 'на');
        }
    }
    
    
    /**
     * Подготовка на филтър формата
     */
    protected static function on_AfterPrepareListFilter($mvc, &$data)
    {
        $data->listFilter->showFields = 'search';
        $data->listFilter->view = 'horizontal';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', array($mvc, 'list'), 'id=filter', 'ef_icon = img/16/funnel.png');
    }
    
    
    /**
     * Намиране na ценовите политики, които може да избира потребителя
     * Ако ги няма може да избира само публичните + частните, до чийто контрагент има достъп
     *
     * @param mixed $cClass           - клас на контрагента
     * @param int|null $cId           - ид на контрагента
     * @param boolean $filterByPublic - да се филтрира ли по публичните политики
     *
     * @return array $options - опции за избор
     */
    public static function getAccessibleOptions($cClass = null, $cId = null, $filterByPublic = true)
    {
        $query = static::getQuery();
        $query->show('title,visiblePricesByAnyone');
        $query->where("#state != 'rejected'");
        if($filterByPublic === true){
            $query->where("#public = 'yes'");
        }
        
        // Ако има данни за контрагент и тези, които са към него
        if (isset($cClass, $cId)) {
            $Class = cls::get($cClass);
            $query->orWhere("#public = 'no' AND #cClass = {$Class->getClassId()} AND #cId = {$cId}");
        }
       
        // От тях остават, само тези достъпни до потребителя
        $options = array();
        while ($rec = $query->fetch()) {
            if (static::haveRightFor('single', $rec->id) || $rec->visiblePricesByAnyone == 'yes') {
                $options[$rec->id] = static::getVerbal($rec, 'title');
            }
        }
        
        // Връщаме намерените политики
        return $options;
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     *
     * @param core_Mvc  $mvc
     * @param core_Form $form
     */
    protected static function on_AfterInputEditForm($mvc, &$form)
    {
        if ($form->isSubmitted()) {
            $rec = &$form->rec;
            
            if (($rec->id) && isset($rec->discountCompared) && $rec->discountCompared == $rec->id) {
                $form->setError('discountCompared', 'Не може да изберете същата политика');
            }
            
            if ($rec->state == 'draft' || is_null($rec->state)) {
                $rec->state = 'active';
            }
        }
    }
    
    
    /**
     * Изпълнява се след създаване на нов набор от ценови правила
     */
    protected static function on_AfterCreate($mvc, $rec)
    {
        if (isset($rec->cId, $rec->cClass) && !Mode::is('syncing')) {
            price_ListToCustomers::setPolicyToCustomer($rec->id, $rec->cClass, $rec->cId);
        }
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид
     */
    protected static function on_AfterRecToVerbal($mvc, $row, $rec, $fields = array())
    {
        if (isset($rec->parent)) {
            $row->parent = price_Lists::getHyperlink($rec->parent, true);
        }

        if(isset($rec->cClass) && isset($rec->cId)){
            $row->cId = cls::get($rec->cClass)->getHyperlink($rec->cId, true);
        }

        if (isset($fields['-single'])) {
            if (isset($rec->discountCompared)) {
                $row->discountCompared = price_Lists::getHyperlink($rec->discountCompared, true);
            }
            
            if ($rec->public == 'yes' && $rec->id != cat_Setup::get('DEFAULT_PRICELIST')) {
                $customerCount = price_ListToCustomers::count("#listId = {$rec->id} AND #state = 'active'");
                $row->connectedClients = cls::get('type_Int')->toVerbal($customerCount);
                if ($customerCount != 0) {
                    if (price_ListToCustomers::haveRightFor('list')) {
                        $row->connectedClients = ht::createLinkRef($row->connectedClients, array('price_ListToCustomers', 'list', 'listId' => $rec->id));
                    }
                }
            }
            
            if ($rec->defaultSurcharge < 0) {
                $row->discountType = 'Отстъпка';
                $rec->defaultSurcharge = abs($rec->defaultSurcharge);
                $row->defaultSurcharge = $mvc->getFieldType('defaultSurcharge')->toVerbal($rec->defaultSurcharge);
            } else {
                $row->discountType = 'Надценка';
            }
            
            if (!isset($rec->defaultSurcharge)) {
                $row->defaultSurcharge = ht::createHint(tr('Няма'), 'Тази ценова политика няма надценка/отстъпка по подразбиране и затова само изрично посочените артикули и групи от артикули ще имат цени', 'warning');
            }
            
            $row->currency = "<span class='cCode'>{$row->currency}</span>";
            
            if (empty($rec->significantDigits)) {
                $significantDigits = price_Setup::get('SIGNIFICANT_DIGITS');
                $row->significantDigits = $mvc->getFieldType('significantDigits')->toVerbal($significantDigits);
                $row->significantDigits = ht::createHint($row->significantDigits, 'Стойност по подразбиране');
            }
            
            if (!isset($rec->minDecimals)) {
                $minDecimals = price_Setup::get('MIN_DECIMALS');
                $row->minDecimals = $mvc->getFieldType('minDecimals')->toVerbal($minDecimals);
                $row->minDecimals = ht::createHint($row->minDecimals, 'Стойност по подразбиране');
            }
            
            if(empty($rec->discountComparedShowAbove)){
                $row->discountComparedShowAbove = ht::createHint($mvc->getFieldType('discountComparedShowAbove')->toVerbal(0.01), 'Стойност по подразбиране');
            }

            $variationOfArr = array();
            $varQuery = price_ListVariations::getQuery();
            $varQuery->where("#variationId = {$rec->id}");
            while($vRec = $varQuery->fetch()){
                $vRow = price_ListVariations::recToVerbal($vRec);
                $hint = strip_tags("{$vRow->validFrom} - {$vRow->validUntil} ({$vRow->repeatInterval})");
                $variationOfArr[] = ht::createHint($vRow->listId, $hint, 'notice', false)->getContent();
            }
            $row->variationsOf = implode(',', $variationOfArr);

            $activeVariationId = price_ListVariations::getActiveVariationId($rec->id);
            if(isset($activeVariationId)){
                $row->activeVariationId = price_Lists::getHyperlink($activeVariationId, true);
            }
        }
    }
    
    
    /**
     * След подготовка на урл-то за връщане
     */
    protected static function on_AfterPrepareRetUrl($mvc, $res, $data)
    {
        // Ако създаваме копие, редиректваме до създаденото копие
        if (is_object($data->form) && $data->form->isSubmitted()) {
            $rec = $data->form->rec;

            $redirectToSingle = true;
            if(isset($rec->variationOf)){
                if(price_ListVariations::haveRightFor('add', (object)array('listId' => $rec->variationOf, 'variationId' => $rec->id))){
                    $data->retUrl = array('price_ListVariations', 'add', "listId" => $rec->variationOf, 'variationId' => $rec->id);
                    $redirectToSingle = false;
                }
            }

            if($redirectToSingle){
                $data->retUrl = array($mvc, 'single', $rec->id);
            }
        }
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if ($requiredRoles == 'no_one') {
            
            return;
        }
        
        if (($action == 'add' || $action == 'edit') && isset($rec->folderId)) {
            
            // Ако корицата не е контрагент само price & ceo могат да променят
            $Cover = doc_Folders::getCover($rec->folderId);
            if (!$Cover->haveInterface('crm_ContragentAccRegIntf')) {
                if (!core_Users::haveRole('ceo,price', $userId)) {
                    $requiredRoles = 'no_one';
                }
            }
        }
        
        if ($requiredRoles == 'no_one') {
            
            return;
        }

        if($action == 'viewpsingle' && isset($rec)){
            if($rec->visiblePricesByAnyone != 'yes'){
                $requiredRoles = 'no_one';
            }
        }

        if ($action == 'add' && isset($rec->cClass, $rec->cId)) {
            if (!cls::get($rec->cClass)->haveRightFor('single', $rec->id)) {
                $requiredRoles = 'no_one';
            }
        }

        if ($action == 'edit' && isset($rec->threadId)) {
            if(!doc_Threads::haveRightFor('single', $rec->threadId)){
                $requiredRoles = 'no_one';
            }
        }

        if($action == 'changepublic' && isset($rec)){
            if($rec->state == 'rejected'){
                $requiredRoles = 'no_one';
            } elseif($rec->public == 'yes'){
                $customers = price_ListToCustomers::getCustomers($rec->id);
                
                // Ако ценовата политика е публична и е закачена на повече от 1 контрагент, не може да стане частна
                if(countR($customers) > 1){
                    $requiredRoles = 'no_one';
                }
            }
        }
    }
    
    
    /**
     * След инсталирането на модела, създава двете базови групи с правила за ценообразуване
     * Себестойност - тук се задават цените на придобиване на стоките, продуктите и услугите
     * Каталог - това са цените които се публикуват
     */
    public function loadSetupData()
    {
        if (!$this->fetchField(price_ListRules::PRICE_LIST_COST, 'id')) {
            $rec = new stdClass();
            $rec->id = price_ListRules::PRICE_LIST_COST;
            $rec->parent = null;
            $rec->title = 'Себестойност';
            $rec->currency = acc_Periods::getBaseCurrencyCode();
            $rec->state = 'active';
            $rec->vat = 'no';
            $rec->folderId = $this->getDefaultFolder();
            $rec->createdBy = core_Users::SYSTEM_USER;
            $rec->createdOn = dt::now();
            
            $this->route($rec);
            $this->save($rec, null, 'REPLACE');
        }
        
        if (!$this->fetchField(price_ListRules::PRICE_LIST_CATALOG, 'id')) {
            $rec = new stdClass();
            $rec->id = price_ListRules::PRICE_LIST_CATALOG;
            $rec->parent = price_ListRules::PRICE_LIST_COST;
            $rec->title = 'Каталог';
            $rec->currency = acc_Periods::getBaseCurrencyCode();
            $rec->state = 'active';
            $rec->vat = 'yes';
            $rec->defaultSurcharge = null;
            $rec->roundingPrecision = 3;
            $rec->visiblePricesByAnyone = 'yes';
            $rec->folderId = $this->getDefaultFolder();
            $rec->createdBy = core_Users::SYSTEM_USER;
            $rec->createdOn = dt::now();
            
            $this->route($rec);
            $this->save($rec, null, 'REPLACE');
        }
    }
    
    
    /**
     * Премахва кеша за интервалите от време
     */
    protected static function on_AfterSave($mvc, &$id, &$rec, $fields = null)
    {
        if (isset($rec->cClass, $rec->cId)) {
            price_ListToCustomers::updateStates($rec->cClass, $rec->cId);
        }

        if($rec->_invalidateCache){
            price_Cache::callback_InvalidatePriceList($rec->id);
        }
    }
    
    
    /**
     * Закръгля сумата според указаното в ценовата политика.
     * Ако в нея не е указано нищо според указаното в настройките на пакета 'price'
     *
     * @param mixed $listId - ид или запис на ценова политика
     * @param float $price  - цената за закръгляне
     *
     * @return float $price - закръглената цена
     */
    public static function roundPrice($listId, $price, $verbal = false)
    {
        $listRec = self::fetchRec($listId);
        
        // Кеш в текущия хит за извлечената информация
        if (!array_key_exists($listRec->id, static::$cache)) {
            $rInfo = new stdClass();
            $rInfo->significantDigits = (isset($listRec->significantDigits)) ? $listRec->significantDigits : price_Setup::get('SIGNIFICANT_DIGITS');
            $rInfo->minDecimals = (isset($listRec->minDecimals)) ? $listRec->minDecimals : price_Setup::get('MIN_DECIMALS');
            static::$cache[$listRec->id] = $rInfo;
        }
        
        $rInfo = static::$cache[$listRec->id];
        
        $p = 0;
        if ($price) {
            $p = log10(abs($price));
        }
        
        // Колко да е точността на закръгляне
        $precision = max($rInfo->minDecimals, round($rInfo->significantDigits - $p));
        $precision = (is_infinite($precision) || is_nan($precision)) ? 0 : $precision;
        if ($verbal === true) {
            $Double = cls::get('type_Double', array('params' => array('decimals' => $precision)));
            $price = $Double->toVerbal($price);
        } else {
            $price = round($price, $precision);
        }
        
        // Връщаме закръглената цена
        return $price;
    }
    
    
    /**
    * След подготовка на тулбара на единичен изглед.
    *
    * @param core_Mvc $mvc
    * @param stdClass $data
    *
    * @return bool|null
    */
    protected static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
        $rec = $data->rec;
        
        if ($mvc->haveRightFor('changepublic', $rec)) {
            $btnTitle = ($rec->public == 'yes') ? 'Частна' : 'Публична';
            $btnWarning = ($rec->public == 'yes') ? 'Наистина ли желаете да направите политиката частна|*?' : 'Наистина ли желаете да направите политиката публична|*?';
            
            $data->toolbar->addBtn($btnTitle, array($mvc, 'changepublic', $rec->id, 'ret_url' => true), "ef_icon=img/16/arrow_refresh.png,title=Промяна на типа на политиката,warning={$btnWarning}");
        }
    }
    
    
    /**
     * Екшън за промяна на състоянието на ценовата политика
     */
    public function act_Changepublic()
    {
        $this->requireRightFor('changepublic');
        expect($id = Request::get('id', 'int'));
        expect($rec = $this->fetch($id));
        $this->requireRightFor('changepublic', $rec);
        
        if($rec->public == 'no'){
            $rec->public = 'yes';
            $rec->cClass = null;
            $rec->cId = null;
        } else {
            $rec->public = 'no';
            $clQuery = price_ListToCustomers::getQuery();
            $clQuery->where("#listId = {$rec->id}");
            $foundRec = $clQuery->fetch();
            $rec->cClass = $foundRec->cClass;
            $rec->cId = $foundRec->cId;
        }
        
        $this->save_($rec, 'public,cClass,cId');
        $currentState = ($rec->public == 'yes') ? 'публична' : 'частна';
        
        followRetUrl(null, "Политиката е променена на {$currentState}");
    }


    /**
     * Има ли промяна в ценовите правила
     *
     * @param datetime|null $datetime
     * @return bool
     */
    public static function areListUpdated($datetime = null)
    {
        $datetime = $datetime ?? dt::now();

        $keys = array_values(price_ListVariations::getActiveVariations(null, $datetime));

        $primeCostListId = price_ListRules::PRICE_LIST_COST;
        $ruleQuery = price_ListRules::getQuery();
        $ruleQuery->XPR('maxCreatedOn', 'datetime', 'MAX(#createdOn)');
        $ruleQuery->where("#listId != {$primeCostListId}");
        $ruleQuery->show('maxCreatedOn');
        $keys[] = $ruleQuery->fetch()->maxCreatedOn;

        $ruleQuery1 = price_ListRules::getQuery();
        $ruleQuery1->XPR('maxValidFrom', 'datetime', 'MAX(#validFrom)');
        $ruleQuery1->where("#listId != {$primeCostListId}");
        $ruleQuery1->where("#validFrom < '{$datetime}'");
        $keys[] = $ruleQuery1->fetch()->maxValidFrom;

        $ruleQuery2 = price_ListRules::getQuery();
        $ruleQuery2->XPR('maxValidUntil', 'datetime', 'MAX(#validUntil)');
        $ruleQuery2->where("#listId != {$primeCostListId}");
        $ruleQuery2->where("#validUntil IS NULL OR #validUntil < '{$datetime}'");
        $keys[] = $ruleQuery2->fetch()->maxValidUntil;

        $query = price_Lists::getQuery();
        $query->XPR('maxModifiedOn', 'datetime', 'MAX(#modifiedOn)');
        $query->where("#id != {$primeCostListId}");
        $query->show('maxModifiedOn');
        $keys[] = $query->fetch()->maxModifiedOn;

        $query = cond_VatExceptions::getQuery();
        $query->XPR('maxModifiedOn', 'datetime', 'MAX(#modifiedOn)');
        $query->show('maxModifiedOn');
        $keys[] = $query->fetch()->maxModifiedOn;

        $query = cat_products_VatGroups::getQuery();
        $query->XPR('maxCreatedOn', 'datetime', 'MAX(#createdOn)');
        $query->show('maxCreatedOn');
        $keys[] = $query->fetch()->maxCreatedOn;

        $hash = md5(implode('|', $keys));
        $pricelistHash = core_Permanent::get("priceListHash");

        if($hash != $pricelistHash){
            core_Permanent::set('priceListHash', $hash, 60);

            return true;
        }

        return false;
    }


    /**
     * Обновява данни в мастъра
     *
     * @param int $id първичен ключ на статия
     *
     * @return int $id ид-то на обновения запис
     */
    public function updateMaster_($id)
    {
        $rec = $this->fetchRec($id);
        $discountCount = price_ListBasicDiscounts::count("#listId = {$rec->id}");
        $rec->haveBasicDiscounts = $discountCount ? 'yes' : 'no';
        $this->save($rec, 'haveBasicDiscounts');
    }


    /**
     * Кой е първия лист с автоматични отстъпки
     *
     * @param mixed $Master
     * @param stdClass $masterRec
     * @return null|stdClass
     */
    public static function getListWithBasicDiscounts($Master, $masterRec)
    {
        $Master = cls::get($Master);
        $clone = clone $masterRec;
        if($Master instanceof sales_Sales){
            $listId = $clone->priceListId ?? price_ListToCustomers::getListForCustomer($clone->contragentClassId, $clone->contragentId, $clone->valior);
        } elseif($Master instanceof eshop_Carts) {
            $listId = eshop_Carts::getCartListId($masterRec);
        }  else {
            $listId = $masterRec->policyId ? $masterRec->policyId : (pos_Receipts::isForDefaultContragent($clone) ? pos_Points::getSettings($clone->pointId)->policyId : price_ListToCustomers::getListForCustomer($clone->contragentClass, $clone->contragentObjectId));
        }

        // Обикаля се тази политика+бащите ѝ дали има поне една с общи отстъпки
        $listIds = array($listId => $listId);
        $count = 1;
        $parent = $listId;
        $where = "CASE #id";
        while ($parent && ($pRec = price_Lists::fetch("#id = {$parent}", "id,parent"))) {
            $listIds[$pRec->id] = $pRec->id;
            $parent = $pRec->parent;
            $where .= " WHEN {$pRec->id} THEN {$count}";
            $count++;
        }
        $where .= " ELSE {$count} END";

        $lQuery = price_Lists::getQuery();
        $lQuery->XPR('order', 'int', "({$where})");
        $lQuery->where("#haveBasicDiscounts = 'yes'");
        $lQuery->orderBy('order', 'ASC');
        $lQuery->in('id', $listIds);

        $foundRec = $lQuery->fetch();

        return is_object($foundRec) ? $foundRec : null;
    }
}
