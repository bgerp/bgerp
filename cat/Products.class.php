<?php


/**
 * Регистър на артикулите в каталога
 *
 *
 * @category  bgerp
 * @package   cat
 *
 * @author    Milen Georgiev <milen@download.bg> и Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.11
 */
class cat_Products extends embed_Manager
{
    /**
     * Свойство, което указва интерфейса на вътрешните обекти
     */
    public $driverInterface = 'cat_ProductDriverIntf';
    
    
    /**
     * Как се казва полето за избор на вътрешния клас
     */
    public $driverClassField = 'innerClass';
    
    
    /**
     * Флаг, който указва, че документа е партньорски
     */
    public $visibleForPartners = true;
    
    
    /**
     * Интерфейси, поддържани от този мениджър
     */
    public $interfaces = 'acc_RegisterIntf,cat_ProductAccRegIntf,acc_RegistryDefaultCostIntf,export_DetailExportCsvIntf';
    
    
    /**
     * Заглавие
     */
    public $title = 'Артикули в каталога';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, plg_SaveAndNew, plg_Clone,doc_plg_Prototype, doc_DocumentPlg, plg_PrevAndNext, acc_plg_Registry, plg_State, cat_plg_Grouping, bgerp_plg_Blank,
                     cat_Wrapper, plg_Sorting, doc_ActivatePlg, doc_plg_Close, doc_plg_BusinessDoc, cond_plg_DefaultValues, plg_Printing, plg_Select, plg_Search, bgerp_plg_Import, bgerp_plg_Groups, bgerp_plg_Export,plg_ExpandInput, core_UserTranslatePlg';
    
    
    /**
     * Полето, което ще се разширява
     *
     * @see plg_ExpandInput
     */
    public $expandFieldName = 'groups';
    
    
    /**
     * Детайла, на модела
     */
    public $details = 'Packagings=cat_products_Packagings,Prices=cat_products_PriceDetails,AccReports=acc_ReportDetails,
    Resources=planning_GenericMapper,Usage=cat_products_Usage,Boms=cat_Boms,Shared=cat_products_SharedInFolders,store_Products';
    
    
    /**
     * Записите от кои детайли на мениджъра да се клонират, при клониране на записа
     *
     * @see plg_Clone
     */
    public $cloneDetails = 'cat_products_Packagings';
    
    
    /**
     * По кои сметки ще се правят справки
     */
    public $balanceRefAccounts = '321,323,61101,60201';
    
    
    /**
     * Да се показват ли в репортите нулевите редове
     */
    public $balanceRefShowZeroRows = true;
    
    
    /**
     * Кой може да вижда частния сингъл
     */
    public $canViewpsingle = 'user';
    
    
    /**
     * По кой итнерфейс ще се групират сметките
     */
    public $balanceRefGroupBy = 'cat_ProductAccRegIntf';
    
    
    /**
     * Кой  може да вижда счетоводните справки?
     */
    public $canReports = 'ceo,sales,purchase,store,acc,cat';
    
    
    /**
     * Кой  може да вижда счетоводните справки?
     */
    public $canAddacclimits = 'ceo,storeMaster,accMaster,accLimits';
    
    
    /**
     * Кой  може да клонира системни записи
     */
    public $canClonesysdata = 'cat,ceo,sales,purchase';
    
    
    /**
     * Кой  може да клонира запис
     */
    public $canClonerec = 'cat,ceo,sales,purchase';
    
    
    /**
     * Наименование на единичния обект
     */
    public $singleTitle = 'Артикул';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'code,name,measureId,quantity,price,folderId';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    public $rowToolsSingleField = 'name';
    
    
    /**
     * Кой може да променя?
     */
    public $canEdit = 'cat,ceo,sales,purchase,catEdit';
    
    
    /**
     * Кой може да пише?
     */
    public $canWrite = 'cat,ceo,sales,purchase,catEdit';
    
    
    /**
     * Кой може да добавя?
     */
    public $canAdd = 'cat,ceo,sales,purchase';
    
    
    /**
     * Кой може да затваря?
     */
    public $canClose = 'cat,ceo,sales,purchase';
    
    
    /**
     * Дали в листовия изглед да се показва бутона за добавяне
     */
    public $listAddBtn = false;
    
    
    /**
     * Може ли да се редактират активирани документи
     */
    public $canEditActivated = true;
    
    
    /**
     * Кой може да го разгледа?
     */
    public $canList = 'powerUser';
    
    
    /**
     * Кой има право да променя системните данни?
     */
    public $canEditsysdata = 'cat,ceo,sales,purchase,catEdit';
    
    
    /**
     * Кой  може да групира "С избраните"?
     */
    public $canGrouping = 'cat,ceo';
    
    
    /**
     * Нов темплейт за показване
     */
    public $singleLayoutFile = 'cat/tpl/products/SingleProduct.shtml';
    
    
    /**
     * Икона за еденичен изглед
     */
    public $singleIcon = 'img/16/wooden-box.png';
    
    
    /**
     * Кой има достъп до единичния изглед
     */
    public $canSingle = 'powerUser';
    
    
    /**
     *  Полета по които ще се търси
     */
    public $searchFields = 'name, code, info, innerClass, nameEn';
    
    
    /**
     * Да се забрани ли кеширането на документа
     */
    public $preventCache = true;
    
    
    /**
     * Шаблон (ET) за заглавие на продукт
     *
     * @var string
     */
    public $recTitleTpl = '[[#code#]] [#name#]';
    
    
    /**
     * Шаблон (ET) за заглавие на продукт
     *
     * @var string
     */
    public $recTitleNonPublicTpl = '[#name#] [[#code#]]';
    
    
    /**
     * Групиране на документите
     */
    public $newBtnGroup = '9.8|Производство';
    
    
    /**
     * На кой ред в тулбара да се показва бутона всички
     */
    public $allBtnToolbarRow = 1;
    
    
    /**
     * В коя номенклатура да се добави при активиране
     */
    public $addToListOnActivation = 'catProducts';
    
    
    /**
     * Абревиатура
     */
    public $abbr = 'Art';
    
    
    /**
     * Стратегии за дефолт стойностти
     */
    public static $defaultStrategies = array('groups' => 'lastDocUser');
    
    
    /**
     * Групи в които са добавени продукти
     */
    public $addToGroups = array();
    
    
    /**
     * Групи от които са изключени продукти
     */
    public $removeFromGroups = array();
    
    
    /**
     * Кеширана информация за артикулите
     */
    protected static $productInfos = array();
    
    
    /**
     * Масив със създадените артикули
     */
    protected $createdProducts = array();
    
    
    /**
     * Полета, които могат да бъдат експортирани
     */
    public $exportableCsvFields = 'code, name, nameEn, measureId, groups, meta';
    
    
    /**
     * Полета, които при клониране да не са попълнени
     *
     * @see plg_Clone
     */
    public $fieldsNotToClone = 'originId, code, name, nameEn, isPublic';
    
    
    /**
     * Кои полета от листовия изглед да се скриват ако няма записи в тях
     */
    public $hideListFieldsIfEmpty = 'price';
    
    
    /**
     * Кое поле съдържа от кой прототип е артикула
     */
    public $protoFieldName = 'proto';
    
    
    /**
     * Кой може да импортира записи?
     */
    public $canImport = 'catImpEx, admin';
    
    
    /**
     * Кой може да експортира записи?
     */
    public $canExport = 'catImpEx, admin';
    
    /**
     * На участъци от по колко записа да се бекъпва?
     */
    public $backupMaxRows = 20000;
    
    
    /**
     * Кои полета да определят рзличността при backup
     */
    public $backupDiffFields = 'modifiedOn,state';
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('proto', 'key(mvc=cat_Products,allowEmpty,select=name)', 'caption=Шаблон,input=hidden,silent,refreshForm,placeholder=Популярни продукти,groupByDiv=»');
        
        $this->FLD('code', 'varchar(32, ci)', 'caption=Код,remember=info,width=15em');
        $this->FLD('name', 'varchar', 'caption=Наименование,remember=info,width=100%, translate=field|transliterate');
        $this->FLD('nameEn', 'varchar', 'caption=Международно,width=100%,after=name, oldFieldName=nameInt');
        $this->FLD('info', 'richtext(rows=4, bucket=Notes)', 'caption=Описание');
        $this->FLD('measureId', 'key(mvc=cat_UoM, select=name,allowEmpty)', 'caption=Мярка,mandatory,remember,notSorting,smartCenter');
        $this->FLD('photo', 'fileman_FileType(bucket=pictures)', 'caption=Илюстрация,input=none');
        $this->FLD('groups', 'keylist(mvc=cat_Groups, select=name, makeLinks)', 'caption=Групи,maxColumns=2,remember');
        $this->FLD('isPublic', 'enum(no=Частен,yes=Публичен)', 'input=none');
        $this->FNC('quantity', 'double(decimals=2)', 'input=none,caption=Наличност,smartCenter');
        $this->FNC('price', 'double(minDecimals=2,maxDecimals=6)', 'input=none,caption=Цена,smartCenter');
        
        $this->FLD('canSell', 'enum(yes=Да,no=Не)', 'input=none');
        $this->FLD('canBuy', 'enum(yes=Да,no=Не)', 'input=none');
        $this->FLD('canStore', 'enum(yes=Да,no=Не)', 'input=none');
        $this->FLD('canConvert', 'enum(yes=Да,no=Не)', 'input=none');
        $this->FLD('fixedAsset', 'enum(yes=Да,no=Не)', 'input=none');
        $this->FLD('canManifacture', 'enum(yes=Да,no=Не)', 'input=none');
        $this->FLD('generic', 'enum(yes=Да,no=Не)', 'input=none,notNull,value=no');
        $this->FLD('meta', 'set(canSell=Продаваем,canBuy=Купуваем,canStore=Складируем,canConvert=Вложим,fixedAsset=Дълготраен актив,canManifacture=Производим,generic=Генеричен)', 'caption=Свойства->Списък,columns=2,mandatory');
        
        $this->setDbIndex('isPublic');
        $this->setDbIndex('canSell');
        $this->setDbIndex('canBuy');
        $this->setDbIndex('canStore');
        $this->setDbIndex('canConvert');
        $this->setDbIndex('fixedAsset');
        $this->setDbIndex('canManifacture');
        
        $this->setDbUnique('code');
    }
    
    
    /**
     * Извиква се преди изпълняването на екшън
     *
     * @param core_Mvc $mvc
     * @param mixed    $res
     * @param string   $action
     */
    public static function on_BeforeAction($mvc, &$res, $action)
    {
        if ($action == 'add') {
            
            // При добавяне, ако има папка и не е избран драйвер
            $innerClass = Request::get('innerClass', 'int');
            $folderId = Request::get('folderId', 'int');
            if (empty($innerClass) && isset($folderId)) {
                
                // Намира се последния избиран драйвер в папката
                $lastDriver = cond_plg_DefaultValues::getFromLastDocument($mvc, $folderId, 'innerClass');
                if (!$lastDriver) {
                    $lastDriver = cat_GeneralProductDriver::getClassId();
                }
                
                // Ако може да бъде избран редирект към формата с него да е избран
                if (!empty($lastDriver)) {
                    if (cls::load($lastDriver, true)) {
                        if (cls::get($lastDriver)->canSelectDriver()) {
                            
                            return redirect(array($mvc, 'add', 'folderId' => $folderId, 'innerClass' => $lastDriver));
                        }
                    }
                }
            }
        }
    }
    
    
    /**
     * Изпълнява се след подготовка на Едит Формата
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = &$data->form;
        $rec = $form->rec;
        
        // Всички позволени мерки
        $measureOptions = cat_UoM::getUomOptions();
        $form->setField($mvc->driverClassField, 'remember,removeAndRefreshForm=proto|measureId|meta|groups');
        
        // Ако е избран драйвер слагаме задъжителните мета данни според корицата и драйвера
        if (isset($rec->folderId)) {
            $cover = doc_Folders::getCover($rec->folderId);
            $isTemplate = ($cover->getProductType() == 'template');
            
            $defMetas = array();
            if (isset($rec->proto)) {
                $defMetas = $mvc->fetchField($rec->proto, 'meta');
                $defMetas = type_Set::toArray($defMetas);
            } else {
                if ($Driver = $mvc->getDriver($rec)) {
                    $defMetas = $Driver->getDefaultMetas($rec->meta);
                    if (countR($defMetas)) {
                        $form->setField('meta', 'autohide=any');
                    }
                }
                
                if (!$defMetas || !countR($defMetas)) {
                    $defMetas = $cover->getDefaultMeta();
                }
            }
            
            if (countR($defMetas)) {
                // Задаваме дефолтните свойства
                $form->setDefault('meta', $form->getFieldType('meta')->fromVerbal($defMetas));
            }
            
            // Ако корицата не е на контрагент
            if (!$cover->haveInterface('crm_ContragentAccRegIntf')) {
                
                // Правим кода на артикула задължителен, ако не е шаблон
                if ($isTemplate === false) {
                    $form->setField('code', 'mandatory');
                }
                
                if ($cover->isInstanceOf('cat_Categories')) {
                    
                    // Ако корицата е категория слагаме дефолтен код и мерки
                    $CategoryRec = $cover->rec();
                    if ($code = $cover->getDefaultProductCode()) {
                        $form->setDefault('code', $code);
                    }
                    
                    $form->setDefault('groups', $CategoryRec->markers);
                    
                    // Ако има избрани мерки, оставяме от всички само тези които са посочени в корицата +
                    // вече избраната мярка ако има + дефолтната за драйвера
                    $categoryMeasures = keylist::toArray($CategoryRec->measures);
                    if (countR($categoryMeasures)) {
                        if (isset($rec->measureId)) {
                            $categoryMeasures[$rec->measureId] = $rec->measureId;
                        }
                        
                        $measureOptions = array_intersect_key($measureOptions, $categoryMeasures);
                    }
                }
                
                // Запомняме последно добавения код
                if ($code = Mode::get('cat_LastProductCode')) {
                    if ($newCode = str::increment($code)) {
                        
                        // Проверяваме дали има такъв запис в системата
                        if (!$mvc->fetch("#code = '${newCode}'")) {
                            $form->setDefault('code', $newCode);
                        }
                    }
                }
            }
        }
        
        // Ако артикула е създаден от източник
        if (isset($rec->originId) && $form->cmd != 'refresh') {
            $document = doc_Containers::getDocument($rec->originId);
            
            // Задаваме за дефолти полетата от източника
            $Driver = $document->getDriver();
            $fields = $document->getInstance()->getDriverFields($Driver);
            $sourceRec = $document->rec();
            
            $form->setDefault('name', $sourceRec->title);
            if (empty($rec->id)) {
                foreach ($fields as $name => $fld) {
                    $form->rec->{$name} = $sourceRec->driverRec[$name];
                }
            }
        }
        
        // Ако има дефолтна мярка, избираме я
        if (is_object($Driver) && $Driver->getDefaultUomId()) {
            $defaultUomId = $Driver->getDefaultUomId();
            $form->setDefault('measureId', $defaultUomId);
            $form->setField('measureId', 'input=hidden');
        } else {
            if ($defMeasure = core_Packs::getConfigValue('cat', 'CAT_DEFAULT_MEASURE_ID')) {
                $measureOptions[$defMeasure] = cat_UoM::getTitleById($defMeasure, false);
                $form->setDefault('measureId', $defMeasure);
            }
            
            // Задаваме позволените мерки като опция
            $form->setOptions('measureId', array('' => '') + $measureOptions);
            
            // При редакция ако артикула е използван с тази мярка, тя не може да се променя
            if (isset($rec->id) && $data->action != 'clone') {
                $isUsed = false;
                if (cat_products_Packagings::fetch("#productId = {$rec->id}")) {
                    $isUsed = true;
                } else {
                    $isUsed = cat_products_Packagings::isUsed($rec->id, $rec->measureId, true);
                }
                
                // Ако артикулът е използван, мярката му не може да бъде сменена
                if ($isUsed === true) {
                    $form->setReadOnly('measureId');
                }
            }
        }
    }
    
    
    /**
     * Изпълнява се след въвеждане на данните от Request
     */
    protected static function on_AfterInputEditForm($mvc, &$form)
    {
        if (!isset($form->rec->innerClass)) {
            $form->setField('groupsInput', 'input=hidden');
            $form->setField('meta', 'input=hidden');
            $form->setField('measureId', 'input=hidden');
            $form->setField('code', 'input=hidden');
            $form->setField('name', 'input=hidden');
            $form->setField('nameEn', 'input=hidden');
            $form->setField('measureId', 'input=hidden');
            $form->setField('info', 'input=hidden');
        }
        
        // Проверяваме за недопустими символи
        if ($form->isSubmitted()) {
            $rec = &$form->rec;
            
            if (empty($rec->name)) {
                if ($Driver = $mvc->getDriver($rec)) {
                    $rec->name = $Driver->getProductTitle($rec);
                }
            }
            
            if (empty($rec->name)) {
                $form->setError('name', 'Моля задайте наименование на артикула');
            }
            
            if (!empty($rec->code)) {
                if (preg_match('/[^0-9a-zа-я\- _]/iu', $rec->code)) {
                    $form->setError('code', 'Полето може да съдържа само букви, цифри, тирета, интервали и долна черта!');
                }
            }
            
            // Ако артикулът е в папка на контрагент, и има вече артикул, със същото се сетва предупреждение
            if (isset($rec->folderId)) {
                $Cover = doc_Folders::getCover($rec->folderId);
                if ($Cover->haveInterface('crm_ContragentAccRegIntf')) {
                    $cond = ($form->_cloneForm !== true) ? "AND #id != '{$rec->id}'" : '';
                    while (cat_Products::fetchField(array("#folderId = {$rec->folderId} AND #name = '[#1#]' {$cond}", $rec->name), 'id')) {
                        $rec->name = str::addIncrementSuffix($rec->name, 'v', 2);
                    }
                } elseif ($Cover->getProductType() == 'template') {
                    if (cat_Products::fetchField(array("#name = '[#1#]' AND #id != '{$rec->id}' AND #state = 'template'", $rec->name), 'id') || doc_Prototypes::fetchField(array("#title = '[#1#]' AND #classId = '{$mvc->getClassId}' AND #docId != '{$rec->id}'", $rec->name))) {
                        $form->setError('name', 'Има вече шаблон с това име');
                    }
                }
            }
            
            if(isset($rec->id) && $form->_cloneForm !== true){
                $rec->_isEditedFromForm = true;
               
                // Предупреждение ако артикула е на чернова
                $sQuery = sales_SalesDetails::getQuery();
                $sQuery->EXT('state', 'sales_Sales', 'externalName=state,externalKey=saleId');
                $sQuery->where("#productId = {$rec->id} AND #state = 'draft'");
                $sQuery->show('id');
                if($sQuery->fetch()){
                    $form->setWarning('name', '|Артикулът участва в продажба на чернова|*. |За да се преизчисли цената в нея, трябва да се редактира артикула, да се изтрие цената и да се презапише|*. |Наистина ли желаете да редактирате артикула|*?');
                }
            }
            
            $metaError = null;
            if(!cat_Categories::checkMetas($rec->meta, $rec->id, $metaError)){
                $form->setError('meta', $metaError);
            }
        }
    }
    
    
    /**
     * Преди запис на продукт
     */
    protected static function on_BeforeSave($mvc, &$id, $rec, $fields = null, $mode = null)
    {
        // Обновяване на групите
        if ($rec->id) {
            $exRec = self::fetch($rec->id);
            if ($exRec->groups) {
                $mvc->removeFromGroups[$exRec->id] = $exRec->groups;
            }
        }
        
        // Разпределяме свойствата в отделни полета за полесно търсене
        if ($rec->meta) {
            $metas = type_Set::toArray($rec->meta);
            foreach (array('canSell', 'canBuy', 'canStore', 'canConvert', 'fixedAsset', 'canManifacture', 'generic') as $fld) {
                $rec->{$fld} = (isset($metas[$fld])) ? 'yes' : 'no';
            }
        }
        
        // Според папката се определя дали артикула е публичен/частен или е шаблон
        if (isset($rec->folderId)) {
            $Cover = doc_Folders::getCover($rec->folderId);
            $type = $Cover->getProductType($id);
            
            if (!isset($rec->id)) {
                $rec->isPublic = ($type != 'private') ? 'yes' : 'no';
            }
            
            if ($rec->state != 'rejected' && $rec->state != 'closed') {
                $rec->state = ($type == 'template') ? 'template' : 'draft';
            }
        }
        
        if ($rec->state == 'draft') {
            $rec->state = 'active';
        }
        
        $rec->code = ($rec->code == '') ? null : $rec->code;
    }
    
    
    /**
     * Рутира публичен артикул в папка на категория
     */
    private function routePublicProduct($categorySysId, &$rec)
    {
        $categoryId = (is_numeric($categorySysId)) ? $categorySysId : null;
        if (!isset($categoryId)) {
            $categoryId = cat_Categories::fetchField("#sysId = '{$categorySysId}'", 'id');
            if (!$categoryId) {
                $categoryId = cat_Categories::fetchField("#sysId = 'goods'", 'id');
            }
        }
        
        // Ако няма такъв артикул създаваме документа
        if (!$this->fetch("#code = '{$rec->code}'")) {
            $rec->folderId = cat_Categories::forceCoverAndFolder($categoryId);
            $this->route($rec);
        }
        
        $defMetas = array();
        if ($Driver = $this->getDriver($rec)) {
            $defMetas = $Driver->getDefaultMetas();
        }
        
        if (!countR($defMetas)) {
            $defMetas = cls::get('cat_Categories')->getDefaultMeta($categoryId);
        }
        
        $rec->meta = ($rec->meta) ? $rec->meta : $this->getFieldType('meta')->fromVerbal($defMetas);
    }
    
    
    /**
     * След подготовка на полетата за импортиране
     *
     * @param crm_Companies $mvc
     * @param array         $fields
     */
    protected static function on_AfterPrepareImportFields($mvc, &$fields)
    {
        $fields = array();
        
        $fields['code'] = array('caption' => 'Код', 'mandatory' => 'mandatory');
        $fields['name'] = array('caption' => 'Наименование');
        $fields['nameEn'] = array('caption' => 'Международно');
        $fields['measureId'] = array('caption' => 'Мярка', 'mandatory' => 'mandatory');
        $fields['groups'] = array('caption' => 'Групи');
        $fields['meta'] = array('caption' => 'Свойства');
        
        $categoryType = 'key(mvc=cat_Categories,select=name,allowEmpty)';
        $groupType = 'keylist(mvc=cat_Groups, select=name, makeLinks)';
        $metaType = 'set(canSell=Продаваем,canBuy=Купуваем,canStore=Складируем,canConvert=Вложим,fixedAsset=Дълготраен актив,canManifacture=Производим,generic=Генеричен)';
        
        $fields['Category'] = array('caption' => 'Допълнителен избор->Категория', 'mandatory' => 'mandatory', 'notColumn' => true, 'type' => $categoryType);
        $fields['Groups'] = array('caption' => 'Допълнителен избор->Групи', 'notColumn' => true, 'type' => $groupType);
        $fields['Meta'] = array('caption' => 'Допълнителен избор->Свойства', 'notColumn' => true, 'type' => $metaType);
        
        if (!$mvc->fields['Category']) {
            $mvc->FNC('Category', $categoryType);
        }
        
        if (!$mvc->fields['Groups']) {
            $mvc->FNC('Groups', $groupType);
        }
        
        if (!$mvc->fields['Meta']) {
            $mvc->FNC('Meta', $metaType);
        }
    }
    
    
    /**
     *
     * Обработка, преди импортиране на запис при начално зареждане
     *
     * @param cat_Products $mvc
     * @param stdClass     $rec
     */
    protected static function on_BeforeImportRec($mvc, $rec)
    {
        // Полетата csv_ се попълват в loadSetupData
        // При 'Импорт' не се използват
        if (empty($rec->innerClass)) {
            $rec->innerClass = cls::get('cat_GeneralProductDriver')->getClassId();
        }
        
        if (isset($rec->csv_name)) {
            $rec->name = $rec->csv_name;
        }
        
        // При дублиран запис, правим опит да намерим нов код
        $onExist = Mode::get('onExist');
        if ($onExist == 'duplicate') {
            $loopCnt = 0;
            while (self::fetch(array("#code = '[#1#]'", $rec->code))) {
                if ($loopCnt > 100) {
                    $rec->code = str::getRand();
                    continue;
                }
                if (is_int($rec->code)) {
                    $rec->code++;
                } else {
                    $nCode = str::increment($rec->code);
                    
                    if ($nCode !== false) {
                        $rec->code = $nCode;
                    } else {
                        $rec->code .= '_d';
                    }
                }
                $loopCnt++;
            }
        }
        
        if ($rec->csv_measureId) {
            $rec->measureId = cat_UoM::fetchBySinonim($rec->csv_measureId)->id;
        } else {
            if (isset($rec->measureId) && !is_numeric($rec->measureId)) {
                $measureName = $rec->measureId;
                $rec->measureId = cat_UoM::fetchBySinonim($rec->measureId)->id;
                
                if (!$rec->measureId) {
                    self::logNotice('Липсваща мярка при импортиране: ' . "{$measureName}");
                    
                    return false;
                }
            }
        }
        
        if ($rec->csv_groups) {
            $rec->groupsInput = cat_Groups::getKeylistBySysIds($rec->csv_groups);
        } else {
            
            // От вербална стойност се опитваме да вземем невербалната
            if (isset($rec->groups)) {
                $delimiter = csv_Lib::getDevider($rec->groups);
                
                $groupArr = explode($delimiter, $rec->groups);
                
                $groupIdArr = array();
                
                foreach ($groupArr as $groupName) {
                    $groupName = trim($groupName);
                    
                    if (!$groupName) {
                        continue;
                    }
                    
                    $force = false;
                    if (haveRole('debug')) {
                        $force = true;
                    }
                    $groupId = cat_Groups::forceGroup($groupName, null, $force);
                    
                    if (!isset($groupId)) {
                        self::logNotice('Липсваща група при импортиране: ' . "{$groupName}");
                        
                        return false;
                    }
                    
                    $groupIdArr[$groupId] = $groupId;
                }
                
                $rec->groupsInput = type_Keylist::fromArray($groupIdArr);
            }
        }
        
        // Обединяваме групите с избраните от потребителя
        if ($rec->Groups) {
            $rec->groupsInput = type_Keylist::merge($rec->groupsInput, $rec->Groups);
        }
        
        $nMetaArr = array();
        if (isset($rec->meta)) {
            $metaArr = type_Set::toArray($rec->meta);
            if (!empty($metaArr)) {
                $mType = $mvc->getFieldType('meta');
                $suggArr = $mType->suggestions;
                
                foreach ($suggArr as &$s) {
                    $s = mb_strtolower($s);
                }
                
                foreach ($metaArr as $m) {
                    $m = trim($m);
                    $metaErr = true;
                    if (isset($suggArr[$m])) {
                        $nMetaArr[$m] = $m;
                        $metaErr = false;
                    } else {
                        $m = mb_strtolower($m);
                        $searchVal = array_search($m, $suggArr);
                        if ($searchVal !== false) {
                            $nMetaArr[$searchVal] = $searchVal;
                            $metaErr = false;
                        }
                    }
                    
                    if ($metaErr) {
                        self::logNotice('Липсваща стойност за мета при импортиране: ' . "{$m}");
                        
                        return false;
                    }
                }
            }
        }
        
        // Обединяваме свойствата с избраните от потребителя
        if ($rec->Meta) {
            $fMetaArr = type_Set::toArray($rec->Meta);
            $rec->meta .= $rec->meta ? ',' : '';
            $rec->meta .= $rec->Meta;
            
            $nMetaArr = array_merge($nMetaArr, $fMetaArr);
        }
        $rec->meta = implode(',', $nMetaArr);
        
        $rec->state = ($rec->state) ? $rec->state : 'active';
        
        $category = ($rec->csv_category) ? $rec->csv_category : $rec->Category;
        
        $mvc->routePublicProduct($category, $rec);
    }
    
    
    /**
     * Добавяне на полета към филтър форма
     *
     * @param core_Form $listFilter
     *
     * @return void
     */
    public static function expandFilter(&$listFilter)
    {
        $orderOptions = arr::make('all=Всички,standard=Стандартни,private=Нестандартни,last=Последно добавени,eproduct=Артикул в Е-маг,prototypes=Шаблони,closed=Закрити,vat09=ДДС 9%,vat0=ДДС 0%');
        if(!core_Packs::isInstalled('eshop')){
            unset($orderOptions['eproduct']);
        }
        
        $orderOptions = arr::fromArray($orderOptions);
        $listFilter->FNC('order', "enum({$orderOptions})", 'caption=Подредба,input,silent,remember,autoFilter');
        $listFilter->FNC('groupId', 'key2(mvc=cat_Groups,select=name,allowEmpty)', 'placeholder=Групи,input,silent,remember,autoFilter');
        
        $listFilter->view = 'horizontal';
        $listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
    }
    
    
    /**
     * Подготовка на филтър формата
     */
    protected static function on_AfterPrepareListFilter($mvc, $data)
    {
        static::expandFilter($data->listFilter);
        
        $data->listFilter->input(null, 'silent');
        $defOrder = 'standard';
        if ($data->listFilter->rec->groupId) {
            $defOrder = 'all';
        }
        $data->listFilter->setDefault('order', $defOrder);
        
        $data->listFilter->FNC('type', 'class', 'caption=Вид');
        $classes = core_Classes::getOptionsByInterface('cat_ProductDriverIntf', 'title');
        $data->listFilter->setOptions('type', array('' => '') + $classes);
        
        $data->listFilter->FNC('meta1', 'enum(all=Свойства,
       							canSell=Продаваеми,
                                canBuy=Купуваеми,
                                canStore=Складируеми,
    							services=Услуги,
                                canConvert=Вложими,
    							canConvertServices=Вложими услуги,
                                fixedAsset=Дълготрайни активи,
    							fixedAssetStorable=Дълготрайни материални активи,
    							fixedAssetNotStorable=Дълготрайни НЕматериални активи,
        					    canManifacture=Производими,generic=Генерични)', 'input,autoFilter');
        $data->listFilter->showFields = 'search,order,type,meta1,groupId';
        $data->listFilter->input('order,groupId,search,meta1,type', 'silent');
        
        // Сортираме по име
        $order = 'name';
        
        // Ако е избран маркер и той е указано да се подрежда по код, сортираме по код
        if (!empty($data->listFilter->rec->groupId)) {
            $gRec = cat_Groups::fetch($data->listFilter->rec->groupId);
            if ($gRec->orderProductBy == 'code') {
                $order = 'code';
            }
        }
        
        if ($data->listFilter->rec->type) {
            $data->query->where("#innerClass = {$data->listFilter->rec->type}");
        }
        
        switch ($data->listFilter->rec->order) {
            case 'all':
                $data->query->orderBy("#state,#{$order}");
                break;
            case 'private':
                $data->query->where("#isPublic = 'no'");
                $data->query->orderBy("#state,#{$order}");
                break;
            case 'last':
                $data->query->orderBy('#createdOn=DESC');
                break;
            case 'closed':
                $data->query->where("#state = 'closed'");
                $data->query->orderBy("#{$order}");
                break;
            case 'prototypes':
                $data->query->where("#state = 'template'");
                break;
            case 'eproduct':
                $eProductArr = eshop_Products::getProductsInEshop();
                if(countR($eProductArr)){
                    $data->query->in("id", $eProductArr);
                } else {
                    $data->query->where("1=2");
                }
                break;
            case 'vat09':
            case 'vat0':
                $v = ($data->listFilter->rec->order == 'vat09') ? 0.09 : 0;
                $products = cat_products_VatGroups::getByVatPercent($v);
                if (countR($products)) {
                    $data->query->in('id', $products);
                } else {
                    $data->query->where('1=2');
                }
                break;
            default:
                $data->query->where("#isPublic = 'yes' AND #state != 'template' AND #state != 'closed'");
                $data->query->orderBy("#state,#{$order}");
                break;
        }
        
        // Филтър по свойства
        if ($data->listFilter->rec->meta1) {
            switch ($data->listFilter->rec->meta1) {
                case 'services':
                    $data->query->where("#canStore = 'no'");
                    break;
                case 'fixedAssetStorable':
                    $data->query->where("#canStore = 'yes' and #fixedAsset = 'yes'");
                    break;
                case 'fixedAssetNotStorable':
                    $data->query->where("#canStore = 'no' and #fixedAsset = 'yes'");
                    break;
                case 'canConvertServices':
                    $data->query->where("#canConvert = 'yes' and #canStore = 'no'");
                    break;
                case 'all':
                    break;
                default:
                    $data->query->like('meta', $data->listFilter->rec->meta1);
                    break;
            }
        }
        
        if ($data->listFilter->rec->groupId) {
            $data->query->where("LOCATE('|{$data->listFilter->rec->groupId}|', #groups)");
        }
    }
    
    
    /**
     * Перо в номенклатурите, съответстващо на този продукт
     *
     * @see acc_RegisterIntf
     */
    public static function getItemRec($objectId)
    {
        $result = null;
        
        if ($rec = self::fetch($objectId)) {
            $Driver = cat_Products::getDriver($rec->id);
            if (!is_object($Driver)) {
                
                return;
            }
            
            static::setCodeIfEmpty($rec);
            
            $result = (object) array(
                'num' => $rec->code . ' a',
                'title' => self::getDisplayName($rec),
                'uomId' => $rec->measureId,
                'features' => array()
            );
            
            // Добавяме свойствата от групите, ако има такива
            $groupFeatures = cat_Groups::getFeaturesArray($rec->groups);
            if (countR($groupFeatures)) {
                $result->features += $groupFeatures;
            }
            
            // Добавяме и свойствата от драйвера, ако има такива
            $result->features = array_merge($Driver->getFeatures($objectId), $result->features);
        }
        
        return $result;
    }
    
    
    /**
     * Задава код на артикула ако няма
     *
     * @param stdClass $rec - запис
     *
     * @return void
     */
    public static function setCodeIfEmpty(&$rec)
    {
        if ($rec->isPublic == 'no' && empty($rec->code)) {
            $rec->code = "Art{$rec->id}";
        } else {
            if (empty($rec->code)) {
                $code = ($rec->id) ? static::fetchField($rec->id, 'code') : null;
                $rec->code = ($code) ? $code : "Art{$rec->id}";
            }
        }
    }
    
    
    /**
     * @see acc_RegisterIntf::itemInUse()
     *
     * @param int $objectId
     */
    public static function itemInUse($objectId)
    {
    }
    
    
    /**
     * Връща масив от продукти отговарящи на зададени мета данни:
     * canSell, canBuy, canManifacture, canConvert, fixedAsset, canStore
     *
     * @param mixed $properties       - комбинация на горе посочените мета
     *                                данни, на които трябва да отговарят
     * @param mixed $hasnotProperties - комбинация на горе посочените мета
     *                                които не трябва да имат
     * @param int   $limit            - лимит
     * @param mixed   $groups            - групи
     *
     * @return array - намерените артикули
     */
    public static function getByProperty($properties, $hasnotProperties = null, $limit = null, $groups = null)
    {
        return static::getProducts(null, null, null, $properties, $hasnotProperties, $limit, false, $groups);
    }
    
    
    /**
     * Метод връщаш информация за продукта и неговите опаковки
     *
     * @param int $productId - ид на продукта
     *
     * @return stdClass $res
     *                  -> productRec - записа на продукта
     *                  o name      - име
     *                  о measureId - ид на мярка
     *                  o code      - код
     *                  -> meta - мета данни за продукта ако има
     *                  meta['canSell'] 		- дали може да се продава
     *                  meta['canBuy']         - дали може да се купува
     *                  meta['canConvert']     - дали може да се влага
     *                  meta['canStore']       - дали може да се съхранява
     *                  meta['canManifacture'] - дали може да се прозивежда
     *                  meta['fixedAsset']     - дали е ДА
     *                  -> packagings - всички опаковки на продукта, ако не е зададена
     */
    public static function getProductInfo($productId)
    {
        if (isset(self::$productInfos[$productId])) {
            
            return self::$productInfos[$productId];
        }
        
        // Ако няма такъв продукт връщаме NULL
        if (!$productRec = static::fetchRec($productId)) {
            
            return;
        }
        
        $res = new stdClass();
        $res->packagings = array();
        $res->productRec = (object) array('name' => $productRec->name,
            'measureId' => $productRec->measureId,
            'code' => $productRec->code,);
        
        $res->isPublic = ($productRec->isPublic == 'yes') ? true : false;
        
        if ($grRec = cat_products_VatGroups::getCurrentGroup($productId)) {
            $res->productRec->vatGroup = $grRec->title;
        }
        
        if ($productRec->meta) {
            if ($meta = explode(',', $productRec->meta)) {
                foreach ($meta as $value) {
                    $res->meta[$value] = true;
                }
            }
        } else {
            $res->meta = false;
        }
        
        // Ако не е зададена опаковка намираме всички опаковки
        $packQuery = cat_products_Packagings::getQuery();
        $packQuery->where("#productId = '{$productId}'");
        while ($packRec = $packQuery->fetch()) {
            $res->packagings[$packRec->packagingId] = $packRec;
        }
        
        // Връщаме информацията за продукта
        self::$productInfos[$productId] = $res;
        
        return $res;
    }
    
    
    /**
     * Връща ид на продукта и неговата опаковка по зададен Код/Баркод
     *
     * @param mixed $code - Код/Баркод на търсения продукт
     *
     * @return mixed $res - Информация за намерения продукт
     *               и неговата опаковка
     */
    public static function getByCode($code)
    {
        $code = trim($code);
        expect($code, 'Не е зададен код', $code);
        $res = new stdClass();
        
        // Проверяваме имали продукт с такъв код
        if ($rec = self::fetch(array("#code = '[#1#]'", $code), 'id')) {
            $res->productId = $rec->id;
            $res->packagingId = null;
        }
        
        if (!$res->productId) {
            
            // Проверява се имали опаковка с този код: вътрешен или баркод
            if ($catPack = cat_products_Packagings::fetch(array("#eanCode = '[#1#]'", $code), 'productId,packagingId')) {
               
                    // Ако има запис намираме ид-та на продукта и опаковката
                $res->productId = $catPack->productId;
                $res->packagingId = $catPack->packagingId;
            }
        }
        
        // Ако не е намерен артикул с този баркод или код, търсим дали е ArtXXX, търси артикул с това ид
        if (!$res->productId) {
            if(stripos($code, 'art') === 0){
                $extractId = str_ireplace('art', '', $code);
                if(type_Int::isInt($extractId)){
                    if($productId = cat_Products::fetchField("#id = '{$extractId}'")){
                        $res->productId = $productId;
                        $res->packagingId = null;
                    }
                }
            }
        }
        
        if (!$res->productId) {
            
            return false;
        }
        
        return $res;
    }
    
    
    /**
     * Връща ДДС на даден продукт
     *
     * @param int      $productId - Ид на продукт
     * @param DateTime $date      - Дата към която начисляваме ДДС-то
     *
     * @return float $vat - ДДС-то на продукта:
     *               Ако има параметър ДДС за продукта го връщаме, впротивен случай
     *               връщаме ДДС-то от периода
     *
     */
    public static function getVat($productId, $date = null)
    {
        expect(static::fetch($productId), 'Няма такъв артикул');
        if (!$date) {
            $date = dt::now();
        }
        
        if ($groupRec = cat_products_VatGroups::getCurrentGroup($productId, $date)) {
            
            return $groupRec->vat;
        }
        
        // Връщаме ДДС-то от периода
        $period = acc_Periods::fetchByDate($date);
        
        return $period->vatRate;
    }
    
    
    /**
     * След всеки запис
     */
    protected static function on_AfterSave(core_Mvc $mvc, &$id, $rec, $fields = null, $mode = null)
    {
        if ($rec->groups) {
            $mvc->addToGroups[$rec->id] = $rec->groups;
            if ($rec->isPublic = 'yes') {
                price_Cache::invalidateProduct($rec->id);
            }
        }
        
        Mode::setPermanent('cat_LastProductCode', $rec->code);
        
        if (isset($rec->originId)) {
            doc_DocumentCache::cacheInvalidation($rec->originId);
        }
        
        if (isset($rec->folderId)) {
            $Cover = doc_Folders::getCover($rec->folderId);
            $type = $Cover->getProductType($rec->id);
            $isPublic = isset($rec->isPublic) ? $rec->isPublic : $mvc->fetchField($rec->id, 'isPublic');
            
            if ($type == 'public' && $isPublic == 'no') {
                $rec->isPublic = 'yes';
                $mvc->save_($rec, 'isPublic');
            }
        }
        
        // Ако артикула е редактиран, преизчислява се транспорта
        if($rec->_isEditedFromForm === true){
            sales_TransportValues::recalcTransportByProductId($rec->id);
        }
    }
    
    
    /**
     * При активиране да се добавили обекта като перо
     */
    public function canAddToListOnActivation($rec)
    {
        $rec = $this->fetchRec($rec);
        
        // Ако артикула е генеричен не става перо по дефолт
        $generic = ($rec->generic) ? $rec->generic : $this->fetchField($rec->id, 'generic');
        if($generic == 'yes') return false;
        
        $isPublic = ($rec->isPublic) ? $rec->isPublic : $this->fetchField($rec->id, 'isPublic');
        
        return ($isPublic == 'yes') ? true : false;
    }
    
    
    /**
     * Рутинни действия, които трябва да се изпълнят в момента преди терминиране на скрипта
     */
    public static function on_Shutdown($mvc)
    {
        // Обновяваме дефиринциално групите
        $deltaGroups = array();
        if (countR($mvc->removeFromGroups)) {
            foreach ($mvc->removeFromGroups as $k) {
                $kArr = keylist::toArray($k);
                foreach ($kArr as $groupId) {
                    --$deltaGroups[$groupId];
                }
            }
        }
        
        if (countR($mvc->addToGroups)) {
            foreach ($mvc->addToGroups as $k) {
                $kArr = keylist::toArray($k);
                foreach ($kArr as $i => $groupId) {
                    ++$deltaGroups[$groupId];
                    if ($deltaGroups[$groupId] == 0) {
                        unset($deltaGroups[$i]);
                    }
                }
            }
        }
        
        // Записваме промяната в групите
        if (countR($deltaGroups) < 10) {
            foreach ($deltaGroups as $groupId => $delta) {
                $gRec = cat_Groups::fetch($groupId);
                $gRec->productCnt += $delta;
                cat_Groups::save($gRec, 'productCnt');
            }
        } else {
            self::updateGroupsCnt();
        }
        
        // За всеки от създадените артикули, създаваме му дефолтната рецепта ако можем
        if (countR($mvc->createdProducts)) {
            foreach ($mvc->createdProducts as $rec) {
                if ($rec->canManifacture == 'yes') {
                    try{
                        if($bomId = self::createDefaultBom($rec)){
                            core_Statuses::newStatus("Успешно е създадена нова базова рецепта|* #" . cat_Boms::getHandle($bomId));
                        }
                    } catch(core_exception_Expect $e){
                        $dump = $e->getDump();
                        core_Statuses::newStatus($dump[0], 'error');
                        static::logErr($dump[0], $rec->id);
                        reportException($e);
                    }
                }
                
                // Ако е създаден артикул, базиран на прототип клонират се споделените му папки, само ако той е частен
                if (isset($rec->proto) && $rec->isPublic == 'no') {
                    cat_products_SharedInFolders::cloneFolders($rec->proto, $rec->id);
                }
            }
        }
    }
    
    
    /**
     * Обновява броячите на групите по cron
     */
    public function cron_UpdateGroupsCnt()
    {
        self::updateGroupsCnt();
    }
    
    
    /**
     * Ъпдейтване на броя продукти на всички групи
     */
    private static function updateGroupsCnt()
    {
        $query = self::getQuery();
        $gCntArr = $query->countKeylist('groups');
        
        $queryGroups = cat_Groups::getQuery();
        
        while ($rec = $queryGroups->fetch()) {
            if ($gCntArr[$rec->id] != $rec->productCnt) {
                $rec->productCnt = $gCntArr[$rec->id];
                cat_Groups::save($rec, 'productCnt');
            }
        }
    }
    
    
    /**
     * Извиква се след SetUp-а на таблицата за модела
     */
    public function loadSetupData()
    {
        $file = 'cat/csv/Products.csv';
        $fields = array(
            0 => 'csv_name',
            1 => 'code',
            2 => 'csv_measureId',
            3 => 'csv_groups',
            4 => 'csv_category',
            5 => 'meta',
        );
        
        core_Users::forceSystemUser();
        $cntObj = csv_Lib::importOnce($this, $file, $fields);
        core_Users::cancelSystemUser();
        
        $res = $cntObj->html;
        
        return $res;
    }
    
    
    /**
     * Връща достъпните продаваеми артикули
     */
    public static function getProductOptions($params, $limit = null, $q = '', $onlyIds = null, $includeHiddens = false)
    {
        $private = $products = array();
        $query = cat_Products::getQuery();
        
        if (is_array($onlyIds)) {
            if (!countR($onlyIds)) {
                
                return array();
            }
            
            $ids = implode(',', $onlyIds);
            
            $query->where("#id IN (${ids})");
        } elseif (ctype_digit("{$onlyIds}")) {
            $query->where("#id = ${onlyIds}");
        } else {
            $query->where("#state = 'active'");
            $reverseOrder = false;
            
            // Ако е зададен контрагент, оставяме само публичните + частните за него
            if (isset($params['customerClass']) && isset($params['customerId'])) {
                $reverseOrder = true;
                $folderId = cls::get($params['customerClass'])->forceCoverAndFolder($params['customerId']);
                cat_products_SharedInFolders::limitQuery($query, $folderId);
            }
            
            if ($limit) {
                $query->limit($limit);
            }
            
            self::filterQueryByMeta($query, $params['hasProperties'], $params['hasnotProperties'], $params['orHasProperties']);
            
            if(isset($params['groups'])){
                $groups = (keylist::isKeylist($params['groups'])) ? $params['groups'] : keylist::fromArray(arr::make($params['groups'], true));
                $query->likeKeylist('groups', $groups);
            }
            
            if(isset($params['notInGroups'])){
                $query->notLikeKeylist('groups', $params['notInGroups']);
            }
            
            // Филтър само на артикули с рецепта, ако е зададен
            if(isset($params['onlyWithBoms'])){
                $bQuery = cat_Boms::getQuery();
                $bQuery->where("#state = 'active'");
                $bQuery->groupBy('productId');
                $in = arr::extractValuesFromArray($bQuery->fetchAll(), 'productId');
                if(countR($in)){
                    $query->in('id', $in);
                } else {
                    $query->where('1=2');
                }
            }
            
            if(isset($params['isPublic'])){
                $query->where("#isPublic = '{$params['isPublic']}'");
            }
            
            // Филтър по драйвер, ако има
            if(isset($params['driverId'])){
                $query->where("#innerClass = {$params['driverId']}");
            }
            
            // Ако има ограничение по ид-та
            if(isset($params['onlyIn'])){
                $query->in("id", $params['onlyIn']);
            }
            
            if(isset($params['notIn'])){
                $query->notIn("id", $params['notIn']);
            }
        }
        
        $query->XPR('searchFieldXprLower', 'text', "LOWER(CONCAT(' ', COALESCE(#name, ''), ' ', COALESCE(#code, ''), ' ', COALESCE(#nameEn, ''), ' ', 'Art', #id, ' ', #id))");
        $direction = ($reverseOrder === true) ? 'ASC' : 'DESC';
        $query->orderBy('isPublic', $direction);
        if (!trim($q)) {
            $query->orderBy('createdOn', 'DESC');
        }
        
        if ($q) {
            if ($q{0} == '"') {
                $strict = true;
            }
            $q = trim(preg_replace("/[^a-z0-9\p{L}]+/ui", ' ', $q));
            $q = mb_strtolower($q);
            $qArr = ($strict) ? array(str_replace(' ', '.*', $q)) : explode(' ', $q);
            
            $pBegin = type_Key2::getRegexPatterForSQLBegin();
            foreach ($qArr as $w) {
                $query->where(array("#searchFieldXprLower REGEXP '(" . $pBegin . "){1}[#1#]'", $w));
            }
        }
        
        $qRegexp = $qRegexpCode = '';
        if ($q) {
            $qRegexp = $qArr[0] ? trim($qArr[0]) : trim($q);
            $qRegexp = preg_quote($qRegexp, '/');
            $qRegexpCode = "/\({$qRegexp}\)$/ui";
            $qRegexp = "/(^|[^0-9a-zа-я]){$qRegexp}([^0-9a-zа-я]|$)/ui";
        
        }
        $mArr = array();
        
        // Подготвяне на опциите
        $query->show('isPublic,folderId,meta,id,code,name,nameEn');
        
        while ($rec = $query->fetch()) {
            $title = static::getRecTitle($rec, false);
            if ($rec->isPublic == 'yes') {
                $products[$rec->id] = $title;
            } else {
                $private[$rec->id] = $title;
            }
            
            if ($qRegexp && preg_match($qRegexp, $title)) {
                $mArr[$rec->id] = $title;
            }
        }
        
        // Подреждане по код
        if (!empty($mArr) && $qRegexpCode) {
            uasort($mArr, function ($a, $b) use ($qRegexpCode) {
                if (preg_match($qRegexpCode, $a)) {
                    
                    return 1;
                }
                
                return 0;
            });
        }
        
        // Подредба по азбучен ред
        if ($q) {
            if (!empty($products)) {
                asort($products);
            }
            if (!empty($private)) {
                asort($private);
            }
        }
        
        $mustReverse = null;
        
        // Ако има пълно съвпадение с някоя дума - добавяме в началото
        foreach ($mArr as $mId => $mTitle) {
            if (isset($products[$mId])) {
                unset($products[$mId]);
                $products = array($mId => $mTitle) + $products;
                if (!isset($mustReverse)) {
                    $mustReverse = false;
                } elseif ($mustReverse === true) {
                    $mustReverse = -1;
                }
            }
            
            if (isset($private[$mId])) {
                unset($private[$mId]);
                $private = array($mId => $mTitle) + $private;
                if (!isset($mustReverse)) {
                    $mustReverse = true;
                } elseif ($mustReverse === false) {
                    $mustReverse = -1;
                }
            }
        }
        if (isset($mustReverse) && $mustReverse !== -1) {
            $reverseOrder = $mustReverse;
        }
        
        if (countR($products) && !isset($onlyIds)) {
            $products = array('pu' => (object) array('group' => true, 'title' => tr('Стандартни'))) + $products;
        }
        
        // Частните артикули излизат преди публичните
        if (countR($private)) {
            krsort($private);
            if(!isset($onlyIds)){
                $private = array('pr' => (object) array('group' => true, 'title' => tr('Нестандартни'))) + $private;
            }
            
            if ($reverseOrder === true) {
                $products = $private + $products;
            } else {
                $products = $products + $private;
            }
        }
        
        return $products;
    }
    
    
    /**
     * Връща масив с артикули за избор, според подадения контрагент.
     * Намира всички стандартни + нестандартни артикули (тези само за клиента или споделени към него).
     * Или ако не е подаден контрагент от всички налични артикули
     *
     * @param mixed    $customerClass    - клас на контрагента
     * @param int|NULL $customerId       - ид на контрагента
     * @param string   $datetime         - към коя дата
     * @param mixed    $hasProperties    - свойства, които да имат артикулите
     * @param mixed    $hasnotProperties - свойства, които да нямат артикулите
     * @param int|NULL $limit            - лимит
     * @param bool     $orHasProperties  - Дали трябва да имат всички свойства от зададените или поне едно
     * @param mixed    $groups           - групи в които да участват
     * @param mixed    $notInGroups      - групи в които да не участват
     * @param null|boolean $isPublic     - null за всички артикули, true за стандартните, false за нестандартните
     * @param null|boolean $driverId     - null за всички артикули, true за тези с избрания драйвер
     *
     * @return array $products         - артикулите групирани по вида им стандартни/нестандартни
     */
    public static function getProducts($customerClass, $customerId, $datetime = null, $hasProperties = null, $hasnotProperties = null, $limit = null, $orHasProperties = false, $groups = null, $notInGroups = null, $isPublic = null, $driverId = null)
    {
        $Type = core_Type::getByName('key2(mvc=cat_Products,select=name,selectSourceArr=cat_Products::getProductOptions,allowEmpty)');
        foreach (array('customerClass', 'customerId', 'orHasProperties', 'isPublic', 'driverId') as $val){
            if(isset(${"{$val}"})){
                $Type->params[$val] = ${"{$val}"};
            }
        }
        
        foreach (array('hasProperties', 'hasnotProperties', 'groups', 'notInGroups') as $val){
            if(!empty(${"{$val}"})){
                $Type->params[$val] = implode('|', arr::make(${"{$val}"}, true));
            }
        }
        
        foreach (array('groups', 'notInGroups') as $val){
            if(!empty(${"{$val}"})){
                $Type->params[$val] = (keylist::isKeylist(${"{$val}"})) ? ${"{$val}"} : keylist::fromArray(arr::make(${"{$val}"}, true));
            }
        }
        
        $products = $Type->getOptions($limit);
        
        return $products;
    }
    
    
    /**
     * Добавя филтър по свойства към артикулите
     *
     * @param core_Query $query            - заявка към модела
     * @param mixed      $hasProperties    - свойства, които да имат артикулите
     * @param mixed      $hasnotProperties - свойства, които да нямат артикулите
     * @param bool       $orHasProperties  - Дали трябва да имат всички свойства от зададените или поне едно
     */
    private static function filterQueryByMeta(&$query, $hasProperties = null, $hasnotProperties = null, $orHasProperties = false)
    {
        $metaArr = (strpos($hasProperties, '|') !== false)  ? explode('|', $hasProperties) : arr::make($hasProperties);
        $hasnotProperties = (strpos($hasnotProperties, '|') !== false)  ? explode('|', $hasnotProperties) : arr::make($hasnotProperties);
        
        // Търси се всяко свойство
        if (countR($metaArr)) {
            $count = 0;
            foreach ($metaArr as $meta) {
                if ($orHasProperties === true) {
                    $or = ($count == 0) ? false : true;
                } else {
                    $or = false;
                }
                
                $query->where("#{$meta} = 'yes'", $or);
                $count++;
            }
        }
        
        if (countR($hasnotProperties)) {
            foreach ($hasnotProperties as $meta1) {
                $query->where("#{$meta1} != 'yes' OR #{$meta1} IS NULL");
            }
        }
    }
    
    
    /**
     * Връща себестойноста на артикула
     *
     * @param int      $productId       - ид на артикул
     * @param int      $packagingId     - ид на опаковка
     * @param float    $quantity        - количество
     * @param datetime $date            - към коя дата
     * @param int|null $primeCostlistId - по коя ценова политика да се смята себестойноста
     *
     * @return float|NULL $primeCost   - себестойност
     */
    public static function getPrimeCost($productId, $packagingId = null, $quantity = 1, $date = null, $primeCostlistId = null)
    {
        // Опитваме се да намерим запис в в себестойностти за артикула
        
        $primeCostlistId = (isset($primeCostlistId)) ? $primeCostlistId : price_ListRules::PRICE_LIST_COST;
        
        // Ако няма цена се опитва да намери от драйвера
        if ($Driver = cat_Products::getDriver($productId)) {
            $primeCost = $Driver->getPrice($productId, $quantity, 0, 0, $date, 1, 'no', $primeCostlistId);
        }
        
        // Ако няма цена от драйвера, се гледа политика 'Себестойност';
        $date = price_ListToCustomers::canonizeTime($date);
        if ((is_object($primeCost) && !isset($primeCost->price)) || !isset($primeCost)) {
            $primeCost = price_ListRules::getPrice($primeCostlistId, $productId, $packagingId, $date);
        }
        
        // Ако няма себестойност, но има прототип, гледа се неговата себестойност
        if ((is_object($primeCost) && !isset($primeCost->price)) || !isset($primeCost)) {
            if ($proto = cat_Products::fetchField($productId, 'proto')) {
                $primeCost = price_ListRules::getPrice($primeCostlistId, $proto, $packagingId, $date);
            }
        }
        
        $primeCost = is_object($primeCost) ? $primeCost->price : $primeCost;
        
        return $primeCost;
    }
    
    
    /**
     * Връща масив със всички опаковки, в които може да участва един продукт + основната му мярка
     * Първия елемент на масива е основната опаковка (ако няма основната мярка)
     *
     * @param int $productId - ид на артикул
     * @param boolean $onlyMeasures - дали да се връщат само мерките на артикула
     *
     * @return array $options - опаковките
     */
    public static function getPacks($productId, $onlyMeasures = false)
    {
        $options = array();
        expect($productRec = cat_Products::fetch($productId, 'measureId,canStore'));
        
        // Определяме основната мярка
        $baseId = $productRec->measureId;
        if ($productRec->canStore == 'yes') {
            $packQuery = cat_products_Packagings::getQuery();
            $packQuery->EXT('type', 'cat_UoM', 'externalName=type,externalKey=packagingId');
            $packQuery->where("#productId = {$productRec->id}");
            $packQuery->show('packagingId,isBase');
            if($onlyMeasures === true){
                $packQuery->where("#type = 'uom'");
            }
            
            while ($packRec = $packQuery->fetch()) {
                $options[$packRec->packagingId] = cat_UoM::getTitleById($packRec->packagingId, false);
                if ($packRec->isBase == 'yes') {
                    $baseId = $packRec->packagingId;
                }
            }
        }
        
        // Подготвяме опциите
        $options = array($productRec->measureId => cat_UoM::getTitleById($productRec->measureId, false)) + $options;
        $firstVal = $options[$baseId];
        
        // Подсигуряваме се че основната опаковка/мярка е първа в списъка
        unset($options[$baseId]);
        $options = array($baseId => $firstVal) + $options;
        
        // Връщане на опциите
        return $options;
    }
    
    
    /**
     * Връща стойността на параметъра с това име, или
     * всички параметри с техните стойностти
     *
     * @param string $id     - ид на записа
     * @param string $name   - име на параметъра, или NULL ако искаме всички
     * @param bool   $verbal - дали да са вербални стойностите
     *
     * @return mixed - стойност или празен масив ако няма параметри
     */
    public static function getParams($id, $name = null, $verbal = false)
    {
        $res = (isset($name)) ? null : array();
        // Ако има драйвър, питаме него за стойността
        if ($Driver = static::getDriver($id)) {

            $res =  $Driver->getParams(cat_Products::getClassId(), $id, $name, $verbal);
        }
        if ($name == 'preview' && !$res) {
            $rec = self::fetch($id);
            $res = $rec->photo;
        }
        // Ако няма връщаме празен масив
        return $res;
    }
    
    
    /**
     * ХТМЛ представяне на артикула (img)
     *
     * @param int   $id      - запис на артикул
     * @param array $size    - размер на картинката
     * @param array $maxSize - макс размер на картинката
     *
     * @return string|NULL $preview - хтмл представянето
     */
    public static function getPreview($id, $size = array('280', '150'), $maxSize = array('550', '550'))
    {
        // Ако има драйвър, питаме него за стойността
        if ($Driver = static::getDriver($id)) {
            $rec = self::fetchRec($id);
            
            return $Driver->getPreview($rec, static::getSingleton(), $size, $maxSize);
        }
        
        // Ако няма връщаме FALSE
    }
    
    
    /**
     * Връща транспортното тегло за подаденото количество и опаковка
     *
     * @param int $productId - ид на продукт
     * @param int $quantity  - общо количество
     *
     * @return float|NULL - транспортното тегло за к-то на артикула
     */
    public static function getTransportWeight($productId, $quantity)
    {
        // За нескладируемите не се изчислява транспортно тегло
        if (cat_Products::fetchField($productId, 'canStore') != 'yes') {
            
            return;
        }
        
        // Ако драйвера връща транспортно тегло, то е с приоритет
        if ($Driver = static::getDriver($productId)) {
            $rec = self::fetchRec($productId);
            $weight = $Driver->getTransportWeight($rec, $quantity);
            if (!empty($weight) && !is_nan($weight)) {
                
                return $weight;
            }
        }
        
        // Колко е нетото за 1-ца от артикула в килограми
        $netto = self::convertToUom($productId, 'kg');
        if (empty($netto)) {
            
            return;
        }
        
        // Колко е нетото за търсеното количество
        $weight = $netto * $quantity;
        
        $foundTare = false;
        $packQuery = cat_products_Packagings::getQuery();
        $packQuery->EXT('type', 'cat_UoM', 'externalName=type,externalKey=packagingId');
        $packQuery->where("#productId = '{$productId}' AND #type = 'packaging' AND #tareWeight IS NOT NULL");
        $packQuery->show('quantity,tareWeight');
        
        // Проверява се първо има ли най-голяма първична опаковка с тара
        $packQueryBase = clone $packQuery;
        $packQueryBase->EXT('isBasic', 'cat_UoM', 'externalName=isBasic,externalKey=packagingId');
        $packQueryBase->where("#isBasic = 'yes'");
        $packQueryBase->orderBy('quantity', 'DESC');
        $basicPackRec = $packQueryBase->fetch();
        
        // Ако има взима се само нейната тара
        if (is_object($basicPackRec)) {
            $foundTare = true;
            $coeficient = $quantity / $basicPackRec->quantity;
            $weight += $basicPackRec->tareWeight * $coeficient;
        } else {
            
            // Ако няма първична и всичките са други, тогава се приема че са вложени
            while ($packRec = $packQuery->fetch()) {
                
                // Какво е отношението на търсеното к-во към това в опаковката
                $coeficient = $quantity / $packRec->quantity;
                
                // Ако е много малко, тарата на опаковката се пропуска
                if (round($coeficient, 2) < 0.5) {
                    continue;
                }
                
                // Ако е достатъчно, тарата се добавя към нетното тегло, умножена по коефицента
                $coeficient = ceil($coeficient);
                $tare = $packRec->tareWeight * $coeficient;
                $foundTare = true;
                
                $weight += $tare;
            }
        }
        
        // Ако има намерена поне една тара, транспортното тегло се връща
        if ($foundTare === true) {
            
            return round($weight, 2);
        }
    }
    
    
    /**
     * Връща транспортния обем за подаденото количество и опаковка
     *
     * @param int $productId - ид на продукт
     * @param int $quantity  - общо количество
     *
     * @return float - теглото на единица от продукта
     */
    public static function getTransportVolume($productId, $quantity)
    {
        // За нескладируемите не се изчислява транспортно тегло
        if (cat_Products::fetchField($productId, 'canStore') != 'yes') {
            
            return;
        }
        
        // Ако драйвера връща транспортно тегло, то е с приоритет
        if ($Driver = static::getDriver($productId)) {
            $rec = self::fetchRec($productId);
            $volume = $Driver->getTransportVolume($rec, $quantity);
            if (!empty($volume) && !is_nan($volume)) {
                
                return $volume;
            }
        }
        
        // Първо се гледа най-голямата опаковка за която има габаритни размери
        $packQuery = cat_products_Packagings::getQuery();
        $packQuery->where("#productId = '{$productId}'");
        $packQuery->where('#sizeWidth IS NOT NULL AND #sizeHeight IS NOT NULL AND #sizeDepth IS NOT NULL');
        $packQuery->orderBy('quantity', 'DESC');
        $packQuery->limit(1);
        $packQuery->show('sizeWidth,sizeHeight,sizeDepth,quantity');
        $packRec = $packQuery->fetch();
        
        if (is_object($packRec)) {
            
            // Ако има такава количеството се преизчислява в нея
            $brutoVolume = $packRec->sizeWidth * $packRec->sizeHeight * $packRec->sizeDepth;
            $quantity /= $packRec->quantity;
            
            // Връща се намереното тегло
            $volume = $brutoVolume * $quantity;
            
            return round($volume, 3);
        }
    }
    
    
    /**
     * След подготовка на записите в счетоводните справки
     */
    protected static function on_AfterPrepareAccReportRecs($mvc, &$data)
    {
        $recs = &$data->recs;
        if (empty($recs) || !countR($recs)) {
            
            return;
        }
        
        $basePackId = key($mvc->getPacks($data->masterId));
        $data->packName = cat_UoM::getTitleById($basePackId);
        
        $quantity = 1;
        if ($pRec = cat_products_Packagings::getPack($data->masterId, $basePackId)) {
            $quantity = $pRec->quantity;
        }
        
        foreach ($recs as &$dRec) {
            $dRec->blQuantity /= $quantity;
        }
    }
    
    
    /**
     * След подготовка на вербалнтие записи на счетоводните справки
     */
    protected static function on_AfterPrepareAccReportRows($mvc, &$data)
    {
        $rows = &$data->balanceRows;
        arr::placeInAssocArray($data->listFields, 'packId=Мярка', 'blQuantity');
        $data->reportTableMvc->FLD('packId', 'varchar', 'tdClass=small-field');
        
        foreach ($rows as &$arrs) {
            if (countR($arrs['rows'])) {
                foreach ($arrs['rows'] as &$row) {
                    $row->packId = $data->packName;
                }
            }
        }
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        if ($fields['-single']) {
            $row->title = $mvc->getRecTitle($rec);
            
            if (isset($rec->originId)) {
                $row->originId = doc_Containers::getDocument($rec->originId)->getLink(0);
            }

            if (isset($rec->proto)) {
                $row->proto = $mvc->getHyperlink($rec->proto);
            }
            
            if ($mvc->haveRightFor('edit', $rec)) {
                if (!Mode::isReadOnly()) {
                    $row->editGroupBtn = ht::createLink('', array($mvc, 'EditGroups', $rec->id, 'ret_url' => true), false, 'ef_icon=img/16/edit-icon.png,title=Промяна на групите на артикула');
                    $row->editMetaBtn = ht::createLink('', array($mvc, 'changemeta', 'Selected' => $rec->id, 'ret_url' => true), false, 'ef_icon=img/16/edit-icon.png,title=Промяна на мета-свойствата на артикула');
                }
            }
            
            $groupLinks = cat_Groups::getLinks($rec->groupsInput);
            $row->groupsInput = (countR($groupLinks)) ? implode(' ', $groupLinks) : (haveRole('partner') ? null : '<i>' . tr('Няма') . '</i>');
        }
        
        if ($fields['-list']) {
            $meta = arr::make($rec->meta, true);
            
            if ($meta['canStore']) {
                $rec->quantity = store_Products::getQuantity($rec->id);
            }
            
            if ($rec->quantity) {
                $row->quantity = $mvc->getVerbal($rec, 'quantity');
                if ($rec->quantity < 0) {
                    $row->quantity = "<span style='color:red;'>" . $row->quantity . '</span>';
                }
            }
            
            if ($meta['canSell']) {
                if ($rec->price = price_ListRules::getPrice(cat_Setup::get('DEFAULT_PRICELIST'), $rec->id)) {
                    $vat = self::getVat($rec->id);
                    $rec->price *= (1 + $vat);
                    $row->price = $mvc->getVerbal($rec, 'price');
                }
            }
        }
    }
    
    
    /**
     * Връща името с което ще показваме артикула според езика в сесията
     * Ако езика не е български поакзваме интернационалното име иначе зададеното
     *
     * @param stdClass $rec
     *
     * @return string
     */
    private static function getDisplayName($rec)
    {
        // Ако в името имаме '||' го превеждаме
        $name = $rec->name;
        
        $lg = core_Lg::getCurrent();
        if ($lg == 'en' && !empty($rec->nameEn)) {
            $name = $rec->nameEn;
        }
        
        // Иначе го връщаме такова, каквото е
        return $name;
    }
    
    
    /**
     * Извиква се преди извличането на вербална стойност за поле от запис
     */
    protected static function on_BeforeGetVerbal($mvc, &$part, &$rec, $field)
    {
        if ($field == 'name') {
            if (!is_object($rec) && type_Int::isInt($rec)) {
                $rec = $mvc->fetchRec($rec);
            }
            
            $part = self::getDisplayName($rec);
            
            return false;
        } elseif ($field == 'code') {
            if (!is_object($rec) && type_Int::isInt($rec)) {
                $rec = $mvc->fetchRec($rec);
            }
            
            if (is_object($rec)) {
                $cRec = clone($rec);
                self::setCodeIfEmpty($cRec);
                $part = $cRec->code;
                
                return false;
            }
        }
    }
    
    
    /**
     * Връща разбираемо за човека заглавие, отговарящо на ключа
     */
    public static function getTitleById($id, $escaped = true)
    {
        // Предефиниране на метода, за да е подсигурено само фечването на нужните полета
        // За да се намали натоварването, при многократни извиквания
        $rec = self::fetch($id, 'name,code,isPublic,nameEn,state');
        
        return parent::getTitleById($rec, $escaped);
    }
    
    
    /**
     * Връща шаблона на заглавието
     *
     * @param stdClass $rec
     * @return mixed
     */
    public function getRecTitleTpl($rec)
    {
        $tpl = ($rec->isPublic != 'yes' || $rec->state == 'template') ? $this->recTitleNonPublicTpl : $this->recTitleTpl;
        
        return new core_ET($tpl);
    }
    
    
    /**
     * Връща разбираемо за човека заглавие, отговарящо на записа
     */
    public static function getRecTitle($rec, $escaped = true)
    {
        $rec->name = self::getDisplayName($rec);
        
        static::setCodeIfEmpty($rec);
        
        return parent::getRecTitle($rec, $escaped);
    }
    
    
    /**
     * Връща информацията за артикула според зададения режим:
     * 		- автоматично : ако артикула е частен се връща детайлното описание, иначе краткото
     * 		- детайлно    : винаги връщаме детайлното описание
     * 		- кратко      : връщаме краткото описание
     *
     * @param mixed    $id                - ид или запис на артикул
     * @param datetime $time              - време
     * @param string   $mode              - режим на показване
     * @param string   $lang              - език
     * @param int      $componentQuantity - к-во на компонентите
     * @param bool     $showCode          - да се показва ли кода до името или не
     *
     * @return mixed $res
     *               ако $mode e 'auto'     - ако артикула е частен се връща детайлното описание, иначе краткото
     *               ако $mode e 'detailed' - подробно описание
     *               ако $mode e 'short'	   - кратко описание
     */
    public static function getAutoProductDesc($id, $time = null, $mode = 'auto', $documentType = 'public', $lang = 'bg', $componentQuantity = null, $showCode = true)
    {
        if ($documentType == 'public') {
            $componentQuantity = 1;
        }
        
        $rec = static::fetchRec($id);
        
        $title = cat_ProductTplCache::getCache($rec->id, $time, 'title', $documentType, $lang);
        if (!$title) {
            $title = cat_ProductTplCache::cacheTitle($rec, $time, $documentType, $lang);
        }
        
        $fullTitle = $title;
        $title = (is_array($fullTitle)) ? $fullTitle['title'] : $fullTitle;
        $subTitle = (is_array($fullTitle)) ? $fullTitle['subTitle'] : null;
        
        if ($showCode === true) {
            if($rec->isPublic == 'yes'){
                $titleTpl = new core_ET('<!--ET_BEGIN code-->[[#code#]] <!--ET_END code-->[#name#]');
            } else {
                $titleTpl = new core_ET('[#name#]<!--ET_BEGIN code--> [[#code#]]<!--ET_END code-->');
            }
            
            
            $titleTpl->replace($title, 'name');
            
            if (!empty($rec->code)) {
                $code = core_Type::getByName('varchar')->toVerbal($rec->code);
                if (!mb_strpos($title, "[{$code}]")) {
                    $titleTpl->replace($code, 'code');
                }
            }
            
            $title = $titleTpl->getContent();
            
            if ($rec->isPublic == 'no' && empty($rec->code)) {
                $count = cat_ProductTplCache::count("#productId = {$rec->id} AND #type = 'description' AND #documentType = '{$documentType}'", 2);
                $title = "{$title} [Art{$rec->id}]";
                
                if ($count > 1) {
                    $vNumber = "/<small class='versionNumber'>v{$count}</small>";
                    $title = str::replaceLastOccurence($title, ']', $vNumber . ']');
                }
            }
        }
        
        $showDescription = false;
        
        switch ($mode) {
            case 'detailed':
                $showDescription = true;
                break;
            case 'short':
                $showDescription = false;
                break;
            default:
                $showDescription = ($rec->isPublic == 'no') ? true : false;
                break;
        }
        
        // Ако ще показваме описание подготвяме го
        if ($showDescription === true) {
            $data = cat_ProductTplCache::getCache($rec->id, $time, 'description', $documentType, $lang);
            if (!$data) {
                $data = cat_ProductTplCache::cacheDescription($rec, $time, $documentType, $lang, $componentQuantity);
            }
            $data->documentType = $documentType;
            $descriptionTpl = cat_Products::renderDescription($data);
            
            // Удебеляваме името само ако има допълнително описание
            if (strlen($descriptionTpl->getContent())) {
                $title = "<b class='productName'>{$title}</b>";
            }
        }
        
        if (!Mode::is('text', 'xhtml') && !Mode::is('printing')) {
            $singleUrl = static::getSingleUrlArray($rec->id);
            $title = ht::createLinkRef($title, $singleUrl);
        }
        
        // Връщаме шаблона с подготвените данни
        $tpl = new ET("[#name#]<!--ET_BEGIN additionalTitle--><br>[#additionalTitle#]<!--ET_END additionalTitle--><!--ET_BEGIN desc--><br><div style='font-size:0.85em'>[#desc#]</div><!--ET_END desc-->");
        $tpl->replace($title, 'name');
        $tpl->replace($descriptionTpl, 'desc');
        
        if (!empty($subTitle)) {
            $tpl->replace($subTitle, 'additionalTitle');
        }
        
        $tpl->removeBlocks();
        $tpl->removePlaces();
        
        return $tpl;
    }
    
    
    /**
     * Връща последната активна рецепта на артикула
     *
     * @param mixed  $id   - ид или запис
     * @param string|array $inOrder - В какъв приоритет да се търсят рецептите
     *
     * @return mixed $res - записа на рецептата или FALSE ако няма
     */
    public static function getLastActiveBom($id, $inOrder = null)
    {
        $rec = self::fetchRec($id, 'canManifacture');
        
        // Ако артикула не е производим не търсим рецепта
        if ($rec->canManifacture == 'no') {
            
            return false;
        }
        
        // Прави опит да намери рецептата по зададения ред
        $inOrderArr = arr::make($inOrder, 'true');
        if(countR($inOrderArr)){
            foreach ($inOrderArr as $type){
                $bRec = cat_Boms::fetch(array("#productId = '{$rec->id}' AND #state = 'active' AND #type = '[#1#]'", $type));
                
                if(is_object($bRec)){
                    
                    return $bRec;
                }
            }
            
            return false;
        }
        
        // Ако не е указан тип, се взима последната рецепта
        $query = cat_Boms::getQuery();
        $query->where("#productId = '{$rec->id}' AND #state = 'active'");
        $query->orderBy('id', 'DESC');
        
        return $query->fetch();
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    protected static function on_AfterPrepareListToolbar($mvc, &$data)
    {
        // Бутона 'Нов запис' в листовия изглед, добавя винаги универсален артикул
        if ($mvc->haveRightFor('add')) {
            $data->toolbar->addBtn('Нов запис', array($mvc, 'add'), 'order=1,id=btnAdd', 'ef_icon = img/16/shopping.png,title=Създаване на нова стока');
        }
    }
    
    
    /**
     * Интерфейсен метод на doc_DocumentInterface
     */
    public function getDocumentRow($id)
    {
        $rec = $this->fetchRec($id);
        $row = new stdClass();
        
        $row->title = $this->getTitleById($rec->id);
        $row->authorId = $rec->createdBy;
        $row->author = $this->getVerbal($rec, 'createdBy');
        $row->recTitle = $row->title;
        $row->state = $rec->state;
        
        return $row;
    }
    
    
    /**
     * В кои корици може да се вкарва документа
     *
     * @return array - интерфейси, които трябва да имат кориците
     */
    public static function getCoversAndInterfacesForNewDoc()
    {
        return array('folderClass' => 'cat_Categories');
    }
    
    
    /**
     * Може ли документа да се добави в посочената папка?
     *
     * @param $folderId int ид на папката
     *
     * @return bool
     */
    public static function canAddToFolder($folderId)
    {
        $coverClass = doc_Folders::fetchCoverClassName($folderId);
        
        return cls::haveInterface('cat_ProductFolderCoverIntf', $coverClass);
    }
    
    
    /**
     * Проверка дали нов документ може да бъде добавен в посочената нишка
     *
     * @param int $threadId key(mvc=doc_Threads)
     *
     * @return bool
     */
    public static function canAddToThread($threadId)
    {
        $threadRec = doc_Threads::fetch($threadId);
        
        return static::canAddToFolder($threadRec->folderId);
    }
    
    
    /**
     * Коя е дефолт папката за нови записи
     */
    public function getDefaultFolder()
    {
        return cat_Categories::forceCoverAndFolder(cat_Categories::fetchField("#sysId = 'goods'", 'id'));
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = null, $userId = null)
    {
        if ($action == 'add') {
            if (isset($rec)) {
                if (isset($rec->originId)) {
                    $document = doc_Containers::getDocument($rec->originId);
                    if (!$document->haveInterface('marketing_InquiryEmbedderIntf')) {
                        $res = 'no_one';
                    } elseif (isset($rec->threadId)) {
                        $originThreadId = $document->fetchField('threadId');
                        if ($originThreadId != $rec->threadId) {
                            $res = 'no_one';
                        }
                    }
                }
            }
        }
        
        // Ако потребителя няма определени роли не може да добавя или променя записи в папка на категория
        if (($action == 'add' || $action == 'edit' || $action == 'write' || $action == 'clonerec' || $action == 'close') && isset($rec)) {
            if ($rec->isPublic == 'yes') {
                if (!haveRole('ceo,cat')) {
                    $res = 'no_one';
                }
            }
        }
        
        if($action == 'add' && isset($rec->innerClass)){
            if(!cls::load($rec->innerClass, true)){
                $res = 'no_one';
            }
        }
    }
    
    
    /**
     * След подготовка на тулбара на единичен изглед.
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    protected static function on_AfterPrepareSingleToolbar($mvc, &$res, $data)
    {
        // Ако има чернова оферта към нея, бутон за редакция
        if ($qRec = sales_Quotations::fetch("#originId = {$data->rec->containerId} AND #state = 'draft'", 'id')) {
            if (sales_Quotations::haveRightFor('edit', $qRec)) {
                $data->toolbar->addBtn('Оферта', array('sales_Quotations', 'edit', $qRec->id, 'ret_url' => true), 'ef_icon = img/16/edit.png,title=Редактиране на оферта');
            }
        } elseif ($data->rec->state != 'rejected') {
            if (sales_Quotations::haveRightFor('add', (object) array('threadId' => $data->rec->threadId, 'originId' => $data->rec->containerId))) {
                $data->toolbar->addBtn('Оферта', array('sales_Quotations', 'add', 'originId' => $data->rec->containerId, 'ret_url' => true), 'ef_icon = img/16/document_quote.png,title=Нова оферта за артикула');
            }
        }
        
        if (core_Packs::isInstalled('batch')) {
            if (batch_Defs::haveRightFor('add', (object) array('productId' => $data->rec->id))) {
                $data->toolbar->addBtn('Партидност', array('batch_Defs', 'add', 'productId' => $data->rec->id, 'ret_url' => true), 'ef_icon = img/16/wooden-box.png,title=Добавяне на партидност,row=2');
            }
        }
        
        if (sales_Sales::haveRightFor('createsaleforproduct', (object) array('folderId' => $data->rec->folderId, 'productId' => $data->rec->id))) {
            $data->toolbar->addBtn('Продажба', array('sales_Sales', 'createsaleforproduct', 'folderId' => $data->rec->folderId, 'productId' => $data->rec->id, 'ret_url' => true), 'ef_icon = img/16/cart_go.png,title=Създаване на нова продажба');
        }
    }
    
    
    /**
     * Променяме шаблона в зависимост от мода
     */
    protected static function on_BeforeRenderSingleLayout($mvc, &$tpl, $data)
    {
        // Ако потребителя е контрактор не показваме детайлите
        if (core_Users::haveRole('partner')) {
            $data->noDetails = true;
            unset($data->row->meta);
        }
    }
    
    
    /**
     * Връща хендлъра на изображението представящо артикула, ако има такова
     *
     * @param mixed $id - ид или запис
     *
     * @return string - файлов хендлър на изображението
     */
    public function getIcon($id)
    {
        if ($Driver = $this->getDriver($id)) {
            
            return $Driver->getIcon();
        }
        
        return 'img/16/error-red.png';
    }
    
    
    /**
     * Иконка за еденичен изглед
     *
     * @param int $id
     */
    public function getSingleIcon($id)
    {
        return $this->getIcon($id);
    }
    
    
    /**
     * Затваряне на перата на частните артикули, по които няма движения
     * в продължение на няколко затворени периода
     */
    public function cron_closePrivateProducts()
    {
        $now = dt::now();
        $stProductsToClose = array();
        $before = dt::addMonths(-3);
        $iStQuery = acc_Items::getQuery();
        $iStQuery->EXT('isPublic', 'cat_Products', 'externalName=isPublic,externalKey=objectId');
        $iStQuery->EXT('pState', 'cat_Products', 'externalName=state,externalKey=objectId');
        $iStQuery->where("#state = 'active' AND #lastUseOn IS NULL AND #isPublic = 'yes' AND #classId = " . cat_Products::getClassId());
        $iStQuery->where("#createdOn <= '{$before}' AND #pState = 'active'");
        $iStQuery->show('objectId');
        while ($iStRec = $iStQuery->fetch()) {
            $pRec1 = cat_Products::fetch($iStRec->objectId, 'id,state,brState');
            $pRec1->brState = $pRec1->state;
            $pRec1->state = 'closed';
            $pRec1->modifiedOn = $now;
            $pRec1->modifiedBy = core_Users::SYSTEM_USER;
            $stProductsToClose[$iStRec->objectId] = $pRec1;
        }
        
        $this->closeItems = $stProductsToClose;
        $this->saveArray($stProductsToClose, 'id,state,brState,modifiedBy,modifiedOn');
        
        foreach ($stProductsToClose as $sd1) {
            $this->logWrite('Автоматично затваряне', $sd1);
        }
        log_System::add('cat_Products', 'ST close items:' . countR($stProductsToClose), null, 'info', 17);
        
        // Намираме всички нестандартни артикули
        $before1 = dt::addMonths(-5);
        $productQuery1 = cat_Products::getQuery();
        $productQuery1->where("#isPublic != 'yes'");
        $productQuery1->where("#createdOn <= '{$before1}'");
        $productQuery1->where("#state != 'closed' AND #state != 'rejected'");
        $productQuery1->show('id,state');
        $products1 = array_keys($productQuery1->fetchAll());
        
        $iQuery2 = acc_Items::getQuery();
        $iQuery2->where("#classId = {$this->getClassId()}");
        $iQuery2->show('objectId');
        $iTems = arr::extractValuesFromArray($iQuery2->fetchAll(), 'objectId');
        
        $diff = array_diff($products1, $iTems);
        $saveDiff = array();
        foreach ($diff as $p1) {
            $pr1 = cat_Products::fetch($p1, 'id,state,brState');
            $pr1->brState = $pr1->state;
            $pr1->state = 'closed';
            $pr1->modifiedOn = $now;
            $pr1->modifiedBy = core_Users::SYSTEM_USER;
            $saveDiff[$p1] = $pr1;
        }
        
        $this->saveArray($saveDiff, 'id,state,brState,modifiedOn,modifiedBy');
        foreach ($saveDiff as $sd) {
            $this->logWrite('Автоматично затваряне', $sd);
        }
        
        log_System::add('cat_Products', 'Products Without Items Closed:' . countR($diff), null, 'info', 17);
        
        $productQuery = cat_Products::getQuery();
        $productQuery->where("#isPublic != 'yes'");
        $productQuery->where("#state != 'closed' AND #state != 'rejected'");
        $productQuery->show('id');
        $products = array_keys($productQuery->fetchAll());
        if (!countR($products)) {
            
            return;
        }
        
        // Последните изчислени периода
        $periods = acc_Periods::getCalcedPeriods(true, 3);
        if (!countR($periods)) {
            
            return;
        }
        
        $oldestPeriod = acc_Periods::fetch(min(array_keys($periods)));
        
        // Намират се отворените пера, създадени преди посочената дата, които са на нестандартни артикули
        $iQuery = acc_Items::getQuery();
        $iQuery->where("#createdOn < '{$oldestPeriod->start}'");
        $iQuery->where("#state = 'active'");
        $iQuery->where("#classId = {$this->getClassId()}");
        $iQuery->in('objectId', $products);
        $iQuery->show('id,objectId');
        $productItems = $objectIds = array();
        while ($iRec = $iQuery->fetch()) {
            $productItems[$iRec->id] = $iRec->id;
            $objectIds[$iRec->id] = $iRec->objectId;
        }
        
        // Ако няма отворени пера, отговарящи на условията не се прави нищо
        if (!countR($productItems)) {
            
            return;
        }
        log_System::add('cat_Products', 'Item products count:' . countR($productItems), null, 'info', 17);
        
        // Оставяме само записите където участват перата на частните артикули на произволно място
        $bQuery = acc_BalanceDetails::getQuery();
        acc_BalanceDetails::filterQuery($bQuery, null, '321,323,60020,60201,61101,701,703');
        
        $balances = array();
        foreach ($periods as $pId => $name) {
            $balances[] = acc_Balances::fetchField("#periodId = {$pId}");
        }
        
        $bQuery->in('balanceId', $balances);
        $bQuery->where('#ent1Id IS NOT NULL || #ent2Id IS NOT NULL || #ent3Id IS NOT NULL');
        $bQuery->show('ent1Id,ent2Id,ent3Id');
        $bQuery->groupBy('ent1Id,ent2Id,ent3Id');
        
        log_System::add('cat_Products', 'Details in:' . implode(',', $balances), null, 'info', 17);
        log_System::add('cat_Products', 'Balance Recs:' . $bQuery->count(), null, 'info', 17);
        
        $itemsInBalanceBefore = array();
        while ($bRec = $bQuery->fetch()) {
            foreach (range(1, 3) as $i) {
                if (!empty($bRec->{"ent{$i}Id"})) {
                    $itemsInBalanceBefore[$bRec->{"ent{$i}Id"}] = $bRec->{"ent{$i}Id"};
                }
            }
        }
        
        if (!is_array($itemsInBalanceBefore)) {
            
            return;
        }
        
        foreach ($productItems as $key => $itemId) {
            if (array_key_exists($itemId, $itemsInBalanceBefore)) {
                unset($productItems[$key]);
            }
        }
        
        log_System::add('cat_Products', 'Items to Close count:' . countR($productItems), null, 'info', 17);
        
        // Ако не са останали пера за затваряне
        if (!countR($productItems)) {
            
            return;
        }
        
        $toSave = array();
        foreach ($productItems as $itemId) {
            $pRec = cat_Products::fetch($objectIds[$itemId], 'id,state,brState');
            $pRec->brState = $pRec->state;
            $pRec->state = 'closed';
            $pRec->modifiedOn = $now;
            $pRec->modifiedBy = core_Users::SYSTEM_USER;
            $toSave[] = $pRec;
        }
        
        $this->saveArray($toSave, 'id,state,brState,modifiedOn,modifiedBy');
        foreach ($toSave as $sd2) {
            $this->logWrite('Автоматично затваряне', $sd2);
        }
        
        $this->closeItems = (is_array($this->closeItems)) ? $this->closeItems : array();
        $this->closeItems += $toSave;
        
        log_System::add('cat_Products', 'END close items:' . countR($toSave), null, 'info', 17);
    }
    
    
    /**
     * Връща дефолтната цена
     *
     * @param mixed $id - ид/запис на обекта
     */
    public function getDefaultCost($id, $quantity)
    {
        // Намира се цената на последния дебит в складовата сметка където участва артикула, с най-голямо количество
        if ($itemId = acc_Items::fetchField("#classId = '{$this->getClassId()}' AND #objectId = '{$id}'")) {
            $jQuery = acc_JournalDetails::getQuery();
            $sysId = acc_Accounts::getRecBySystemId('321')->id;
            $jQuery->where("#debitAccId = {$sysId} AND #debitItem2 = {$itemId} AND #debitPrice > 0");
            $jQuery->orderBy('debitQuantity', 'DESC');
            $jQuery->show('debitPrice');
            $jQuery->limit(1);
            
            // Ако има таква цена, то това ще е дефолтната цена
            if ($biggestDebitPrice = $jQuery->fetch()->debitPrice) {
                
                return $biggestDebitPrice;
            }
        }
        
        // Ако няма се взима количеството от последното задание за артикула (ако има)
        if ($quantityFromJob = planning_Jobs::getLastQuantity($id)) {
            $quantity = $quantityFromJob;
        }
        
        // За артикула, това е цената по себестойност за исканото количество
        return self::getPrimeCost($id, null, $quantity);
    }
    
    
    /**
     * Подготовка на бутоните на формата за добавяне/редактиране.
     *
     * @param core_Manager $mvc
     * @param stdClass     $res
     * @param stdClass     $data
     */
    protected static function on_AfterPrepareEditToolbar($mvc, &$res, $data)
    {
        $data->form->toolbar->renameBtn('save', 'Запис');
        $data->form->toolbar->removeBtn('activate');
    }
    
    
    /**
     * Прави стандартна 'обвивка' на изгледа
     *
     * @todo: да се отдели като плъгин
     */
    public function renderWrapping_($tpl, $data = null)
    {
        if (core_Packs::isInstalled('colab')) {
            if (core_Users::haveRole('partner')) {
                $this->load('cms_ExternalWrapper');
                $this->currentTab = 'Нишка';
            }
        }
        
        return parent::renderWrapping_($tpl, $data);
    }
    
    
    /**
     * Връща складовата (средно притеглената цена) на артикула в подадения склад за количеството
     *
     * @param float    $quantity  - к-во
     * @param int      $productId - ид на артикула
     * @param datetime $date      - към коя дата
     * @param string   $storeId   - склада
     *
     * @return mixed $amount   - сумата или NULL ако няма
     */
    public static function getWacAmountInStore($quantity, $productId, $date, $storeId = null)
    {
        $item2 = acc_Items::fetchItem('cat_Products', $productId)->id;
        if (!$item2) {
            
            return;
        }
        
        $item1 = '*';
        if (!empty($storeId)) {
            $item1 = acc_Items::fetchItem('store_Stores', $storeId)->id;
        }
        
        // Намираме сумата която струва к-то от артикула в склада
        $maxTry = core_Packs::getConfigValue('cat', 'CAT_WAC_PRICE_PERIOD_LIMIT');
        $amount = acc_strategy_WAC::getAmount($quantity, $date, '321', $item1, $item2, null, $maxTry);
        
        if (isset($amount)) {
            
            return round($amount, 4);
        }
        
        // Връщаме сумата
        return $amount;
    }
    
    
    /**
     * Какви материали са нужни за производството на 'n' бройки от подадения артикул
     *
     * @param int   $id       - ид
     * @param float $quantity - количество
     *                        o productId - ид на продукта
     *                        o quantity - к-то на продукта
     */
    public static function getMaterialsForProduction($id, $quantity = 1, $date = null, $recursive = false)
    {
        if (!$date) {
            $date = dt::now();
        }
        
        $res = array();
        
        // Намираме рецептата за артикула (ако има)
        $bomId = static::getLastActiveBom($id, 'production,sales')->id;
        
        if (isset($bomId)) {
            
            // Извличаме какво к-во
            $info = cat_Boms::getResourceInfo($bomId, $quantity, $date);
            
            foreach ($info['resources'] as $rRec) {
                if ($rRec->type != 'input') {
                    continue;
                }
                
                // Добавяме материала в масива
                $quantity1 = $rRec->baseQuantity + $rRec->propQuantity;
                if (!array_key_exists($rRec->productId, $res)) {
                    $res[$rRec->productId] = array('productId' => $rRec->productId, 'quantity' => $quantity1);
                } else {
                    $res[$rRec->productId]['quantity'] += $quantity1;
                }
                
                // Ако искаме рекурсивно, проверяваме дали артикула има материали
                if ($recursive === true) {
                    $newMaterials = self::getMaterialsForProduction($rRec->productId, $quantity1, $date, $recursive);
                    
                    // Ако има артикула се маха и се викат материалите му
                    if (countR($newMaterials)) {
                        unset($res[$rRec->productId]);
                        
                        foreach ($newMaterials as $pId => $arr) {
                            if (array_key_exists($pId, $res)) {
                                $res[$pId]['quantity'] += $arr['quantity'];
                            } else {
                                $res[$pId] = $arr;
                            }
                        }
                    }
                }
            }
        } else {
            $Driver = static::getDriver($id);
            if($Driver !== false){
                $res = $Driver->getMaterialsForProduction($id, $quantity);
            }
        }
        
        return $res;
    }
    
    
    /**
     * Връща готовото описание на артикула
     *
     * @param mixed  $id
     * @param string $documentType
     *
     * @return core_ET
     */
    public static function getDescription($id, $documentType = 'public')
    {
        $data = static::prepareDescription($id, $documentType);
        
        return self::renderDescription($data);
    }
    
    
    /**
     * Подготвя описанието на артикула
     *
     * @param int                   $id
     * @param enum(public,internal) $documentType
     *
     * @return stdClass - подготвеното описание
     */
    public static function prepareDescription($id, $documentType = 'public')
    {
        $Driver = static::getDriver($id);
        $data = new stdClass();
        
        if ($Driver) {
            $data->rec = static::fetchRec($id);
            $data->row = cat_Products::recToVerbal($data->rec);
            $data->documentType = $documentType;
            $data->Embedder = cat_Products::getClassId();
            $data->isSingle = false;
            $data->noChange = true;
            $Driver->prepareProductDescription($data);
        }
        
        return $data;
    }
    
    
    /**
     * Рендира описанието на артикула
     *
     * @param stdClass $data
     *
     * @return core_ET
     */
    private static function renderDescription($data)
    {
        if ($data->rec) {
            $Driver = static::getDriver($data->rec);
        }
        
        if ($Driver) {
            $tpl = $Driver->renderProductDescription($data);
            $showLinks = ($data->documentType == 'public' || $data->documentType == 'invoice') ? false : true;
            
            $componentTpl = cat_Products::renderComponents($data->components, $showLinks);
            $tpl->append($componentTpl, 'COMPONENTS');
        } else {
            $tpl = new ET(tr("|*<span class='red'>|Проблем с показването|*</span>"));
        }
        
        return $tpl;
    }
    
    
    /**
     * Рендира компонентите на един артикул
     *
     * @param array $components - компонентите на артикула
     *
     * @return core_ET - шаблона на компонентите
     */
    public static function renderComponents($components, $makeLinks = true)
    {
        if (!countR($components)) {
            
            return;
        }
        
        $compTpl = getTplFromFile('cat/tpl/Components.shtml');
        $block = $compTpl->getBlock('COMP');
        foreach ($components as $obj) {
            $bTpl = clone $block;
            if ($obj->quantity == cat_BomDetails::CALC_ERROR) {
                $obj->quantity = "<span class='red'>???</span>";
            } else {
                $obj->divideBy = ($obj->divideBy) ? $obj->divideBy : 1;
                $quantity = $obj->quantity / $obj->divideBy;
                
                $Double = cls::get('type_Double', array('params' => array('smartRound' => 'smartRound')));
                $obj->quantity = $Double->toVerbal($quantity);
            }
            
            // Ако ще показваме компонента като линк, го правим такъв
            if ($makeLinks === true && !Mode::is('text', 'xhtml') && !Mode::is('printing')) {
                $singleUrl = cat_Products::getSingleUrlArray($obj->componentId);
                $obj->title = ht::createLinkRef($obj->title, $singleUrl);
            }
            
            $obj->divideBy = ($obj->divideBy) ? $obj->divideBy : 1;
            
            $arr = array('componentTitle' => $obj->title,
                'componentDescription' => $obj->description,
                'titleClass' => $obj->titleClass,
                'componentCode' => $obj->code,
                'componentStage' => $obj->stageName,
                'componentQuantity' => $obj->quantity,
                'level' => $obj->level,
                'leveld' => $obj->leveld,
                'componentMeasureId' => $obj->measureId);
            
            $bTpl->placeArray($arr);
            $bTpl->removeBlocks();
            $bTpl->append2Master();
        }
        $compTpl->removeBlocks();
        
        return $compTpl;
    }
    
    
    /**
     * След подготовка на сингъла
     */
    protected static function on_AfterPrepareSingle($mvc, &$res, $data)
    {
        $data->components = array();
        cat_Products::prepareComponents($data->rec->id, $data->components, 'internal', 1);
        
        if (haveRole('partner')) {
            unset($data->row->originId);
        }
    }
    
    
    /**
     * След рендиране на единичния изглед
     */
    protected static function on_AfterRenderSingle($mvc, &$tpl, $data)
    {
        if (countR($data->components)) {
            $componentTpl = cat_Products::renderComponents($data->components);
            $tpl->append($componentTpl, 'COMPONENTS');
        }
    }
    
    
    /**
     * Подготвя обект от компонентите на даден артикул
     *
     * @param int    $productId
     * @param array  $res
     * @param string $documentType
     * @param number $componentQuantity
     * @param string $typeBom
     *
     * @return array
     */
    public static function prepareComponents($productId, &$res = array(), $documentType = 'internal', $componentQuantity, $typeBom = null)
    {
        if (empty($componentQuantity)) {
            
            return $res;
        }
        $typeBom = (!empty($typeBom)) ? $typeBom : 'sales';
        $rec = cat_Products::getLastActiveBom($productId, $typeBom);
        
        // Ако няма последна активна рецепта, и сме на 0-во ниво ще показваме от черновите ако има
        if (empty($rec)) {
            $bQuery = cat_Boms::getQuery();
            $bQuery->where("#productId = {$productId} AND #state = 'draft' AND #type = 'sales'");
            $bQuery->orderBy('id', 'DESC');
            $rec = $bQuery->fetch();
        }
        
        if ($documentType == 'job') {
            if ($pRec = cat_Products::getLastActiveBom($productId, 'production')) {
                $rec = $pRec;
            }
        }
        
        $checkMvc = ($documentType == 'job') ? 'planning_Jobs' : 'cat_Products';
        if (!$rec || cat_Boms::showIn($rec, $checkMvc) === false) {
            
            return $res;
        }
        
        // Кои детайли от нея ще показваме като компоненти
        $details = cat_BomDetails::getOrderedBomDetails($rec->id);
        $qQuantity = $componentQuantity;
        
        if (is_array($details)) {
            $fields = cls::get('cat_BomDetails')->selectFields();
            $fields['-components'] = true;
            
            foreach ($details as $dRec) {
                if (!isset($dRec->parentId)) {
                    $dRec->params['$T'] = $qQuantity;
                }
                
                $obj = new stdClass();
                $obj->componentId = $dRec->resourceId;
                $row = cat_BomDetails::recToVerbal($dRec, $fields);
                $obj->code = $row->position;
                
                $codeCount = strlen($obj->code);
                $length = $codeCount - strlen(".{$dRec->position}");
                $length = ($length < 0) ? 0 : $length;
                $obj->parent = substr($obj->code, 0, $length);
                
                $obj->title = cat_Products::getTitleById($dRec->resourceId);
                $obj->measureId = $row->packagingId;
                $obj->quantity = ($dRec->rowQuantity == cat_BomDetails::CALC_ERROR) ? $dRec->rowQuantity : $dRec->rowQuantity;
                
                $obj->level = substr_count($obj->code, '.');
                $obj->titleClass = 'product-component-title';
                if ($dRec->type == 'stage') {
                    $specTpl = cat_Products::getParams($dRec->resourceId, 'specTpl');
                    if ($specTpl && countR($dRec->params)) {
                        $specTpl = strtr($specTpl, $dRec->params);
                        $specTpl = new core_ET($specTpl);
                        $obj->title .= ' ' . $specTpl->getContent();
                    }
                }
                
                if ($obj->parent) {
                    if ($res[$obj->parent]->quantity != cat_BomDetails::CALC_ERROR) {
                        $obj->quantity *= $res[$obj->parent]->quantity;
                    }
                } else {
                    $obj->quantity *= $qQuantity;
                }
                
                if ($dRec->description) {
                    $obj->description = $row->description;
                    $obj->leveld = $obj->level;
                }
                $res[$obj->code] = $obj;
                $obj->divideBy = $rec->quantity;
            }
        }
        
        return $res;
    }
    
    
    /**
     * Създава дефолтната рецепта за артикула.
     * Ако е по прототип клонира и разпъва неговата,
     * ако не проверява дали от драйвера може да се генерира
     *
     * @param int $id - ид на артикул
     *
     * @return int|null;
     */
    private static function createDefaultBom($id)
    {
        $rec = static::fetchRec($id);
        
        // Ако има прототипен артикул, клонираме му рецептата и я разпъваме
        if (isset($rec->proto)) {
            return cat_Boms::cloneBom($rec->proto, $rec);
        } else {
            
            // Ако не е прототипен, питаме драйвера може ли да се генерира рецепта
            //return cat_Boms::createDefault($rec);
        }
    }
    
    
    /**
     * Изпълнява се след създаване на нов запис
     */
    protected static function on_AfterCreate($mvc, $rec)
    {
        $mvc->createdProducts[] = $rec;
    }
    
    
    /**
     * Връща информация за какви дефолт задачи за производство могат да се създават по артикула
     *
     * @param mixed $jobRec   - ид или запис на задание
     * @param float $quantity - к-во за произвеждане
     *
     * @return array $drivers - масив с информация за драйверите, с ключ името на масива
     *               o title                          - дефолт име на задачата, най добре да е името на крайния артикул / името заготовката
     *               o plannedQuantity                - планирано к-во в основна опаковка
     *               o productId                      - ид на артикул
     *               o packagingId                    - ид на опаковка
     *               o quantityInPack                 - к-во в 1 опаковка
     *               o products                       - масив от масиви с продуктите за влагане/произвеждане/отпадане
     *               o timeStart                      - начало
     *               o timeDuration                   - продължителност
     *               o timeEnd                        - край
     *               o fixedAssets                    - списък (кейлист) от оборудвания
     *               o employees                      - списък (кейлист) от служители
     *               o storeId                        - склад
     *               o indTime                        - норма
     *               o indPackagingId                 - опаковка/мярка за норма
     *               o indTimeAllocation              - начин на отчитане на нормата
     *               o showadditionalUom              - какъв е режима за изчисляване на теглото
     *               o weightDeviationNotice          - какво да е отклонението на теглото за внимание
     *               o weightDeviationWarning         - какво да е отклонението на теглото за предупреждение
     *               o weightDeviationAverageWarning  - какво да е отклонението спрямо средното
     *               
     *               - array input        - масив отматериали за влагане
     *                  o productId      - ид на материал
     *                  o packagingId    - ид на опаковка
     *                  o quantityInPack - к-во в 1 опаковка
     *                  o packQuantity   - общо количество от опаковката
     *               - array production   - масив от производими артикули
     *                  o productId      - ид на заготовка
     *                  o packagingId    - ид на опаковка
     *                  o quantityInPack - к-во в 1 опаковка
     *                  o packQuantity   - общо количество от опаковката
     *               - array waste        - масив от отпадъци
     *                  o productId      - ид на отпадък
     *                  o packagingId    - ид на опаковка
     *                  o quantityInPack - к-во в 1 опаковка
     *                  o packQuantity   - общо количество от опаковката
     */
    public static function getDefaultProductionTasks($jobRec, $quantity = 1)
    {
        $defaultTasks = array();
        expect($jobRec = planning_Jobs::fetchRec($jobRec));
        $rec = self::fetch($jobRec->productId);
        
        if ($rec->canManifacture != 'yes') {
            
            return $defaultTasks;
        }
        
        // Питаме драйвера какви дефолтни задачи да се генерират
        $ProductDriver = cat_Products::getDriver($rec);
        if (!empty($ProductDriver)) {
            $defaultTasks = $ProductDriver->getDefaultProductionTasks($jobRec, $quantity);
        }
        
        // Ако няма дефолтни задачи
        if (!countR($defaultTasks)) {
            
            // Намираме последната активна рецепта
            $bomRec = self::getLastActiveBom($rec, 'production,sales');
            
            // Ако има опитваме се да намерим задачите за производството по нейните етапи
            if ($bomRec) {
                $defaultTasks = cat_Boms::getTasksFromBom($bomRec, $quantity);
            }
        }
        
        // Връщаме намерените задачи
        return $defaultTasks;
    }
    
    
    /**
     * Кои полета от драйвера да се добавят към форма за автоматично създаване на артикул
     *
     * @param core_Form - $form
     * @param int         $id   - ид на артикул
     *
     * @return void
     */
    public static function setAutoCloneFormFields(&$form, $id, $driverId = null)
    {
        $form->FLD('name', 'varchar', 'caption=Наименование,remember=info,width=100%');
        $form->FLD('nameEn', 'varchar', 'caption=Международно,width=100%,after=name');
        $form->FLD('info', 'richtext(rows=4, bucket=Notes)', 'caption=Описание');
        $form->FLD('measureId', 'key(mvc=cat_UoM, select=name,allowEmpty)', 'caption=Мярка,mandatory,remember,notSorting,smartCenter');
        $form->FLD('groups', 'keylist(mvc=cat_Groups, select=name, makeLinks)', 'caption=Групи,maxColumns=2,remember');
        $form->FLD('meta', 'set(canSell=Продаваем,canBuy=Купуваем,canStore=Складируем,canConvert=Вложим,fixedAsset=Дълготраен актив,canManifacture=Производим)', 'caption=Свойства->Списък,columns=2,mandatory');
        
        if (isset($id)) {
            if ($driverId) {
                $Driver = cls::get($driverId);
            } else {
                $Driver = self::getDriver($id);
            }
            
            // Добавяне на стойностите от записа в $rec-a на формата
            $rec = self::fetch($id);
            if ($rec) {
                $fields = self::getDriverFields($Driver);
                if (is_array($fields)) {
                    foreach ($fields as $name => $caption) {
                        if (isset($rec->{$name})) {
                            $form->rec->{$name} = $rec->{$name};
                        }
                    }
                }
            }
        } else {
            $Driver = cls::get($driverId);
        }
        
        $Driver->addFields($form);
    }
    
    
    /**
     * Екшън за редактиране на групите на артикула
     */
    public function act_EditGroups()
    {
        $this->requireRightFor('edit');
        expect($id = Request::get('id', 'int'));
        expect($rec = $this->fetch($id));
        $this->requireRightFor('edit', $rec);
        
        $form = cls::get('core_Form');
        $form->title = 'Промяна на групите на|* <b>' . cat_Products::getHyperlink($id, true) . '</b>';
        
        $this->setExpandInputField($form, $this->expandInputFieldName, $this->expandFieldName);
        
        // TODO - временно решение, трябва да се премахне след #C28560
        unset($form->fields[$this->expandInputFieldName]->type->params['pathDivider']);
        
        $form->setDefault('groupsInput', $rec->groupsInput);
        $form->input();
        if ($form->isSubmitted()) {
            $fRec = $form->rec;
            
            if ($fRec->groupsInput != $rec->groupsInput) {
                $this->save((object) array('id' => $id, 'groupsInput' => $fRec->groupsInput), 'groups');
                $this->logInAct('Редактиране', $rec);
            }
            
            return followRetUrl();
        }
        
        $form->toolbar->addSbBtn('Запис', 'save', 'ef_icon = img/16/disk.png, title = Запис на документа');
        $form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close-red.png, title=Прекратяване на действията');
        
        return $this->renderWrapping($form->renderHtml());
    }
    
    
    /**
     * Метод позволяващ на артикула да добавя бутони към rowtools-а на документ
     *
     * @param int             $id          - ид на артикул
     * @param core_RowToolbar $toolbar     - тулбара
     * @param mixed           $detailClass - класа на детайла на документа
     * @param int             $detailId    - ид на реда от детайла на документа
     *
     * @return void
     */
    public static function addButtonsToDocToolbar($id, core_RowToolbar &$toolbar, $detailClass, $detailId)
    {
        if ($Driver = self::getDriver($id)) {
            $Driver->addButtonsToDocToolbar($id, $toolbar, $detailClass, $detailId);
        }
    }
    
    
    /**
     * Връща сметките, върху които може да се задават лимити на перото
     *
     * @param stdClass $rec
     *
     * @return array
     */
    public function getLimitAccounts($rec)
    {
        $rec = $this->fetchRec($rec, 'canStore,canConvert');
        
        $accounts = '';
        if ($rec->canStore == 'yes') {
            $accounts .= ($rec->canConvert == 'yes') ? '321,323,61101' : '321,323';
        } else {
            $accounts .= ($rec->canConvert == 'yes') ? '61101,60201' : '60201';
        }
        
        $accounts = arr::make($accounts, true);
        
        return $accounts;
    }
    
    
    /**
     * Намира цена на артикул по неговия код към текущата дата, в следния ред
     *
     * 1. Мениджърска себестойност
     * 2. Ако е вложим и има заместващи, себестойността на този с най-голямо к-во във всички складове
     * 3. Ако е производим и има търговска рецепта, цената по нея
     * 4. Ако е складируем - средната му цена във всички складове
     * 5. Ако не открие връща NULL
     *
     * @param string $code
     * @param bool   $onlyManager
     *
     * @return NULL|float $primeCost
     */
    public static function getPrimeCostByCode($code, $onlyManager = false)
    {
        // Имали такъв артикул?
        $product = self::getByCode($code);
        if (!$product) {
            
            return;
        }
        
        $productId = $product->productId;
        
        // Мениджърската му себестойност, ако има
        $primeCost = price_ListRules::getPrice(price_ListRules::PRICE_LIST_COST, $productId);
        if (!empty($primeCost)) {
            
            return $primeCost;
        }
        
        if ($onlyManager === true) {
            
            return;
        }
        
        $pRec = cat_Products::fetch($productId, 'canConvert,canManifacture,canStore');
        
        // Ако е вложим
        if ($pRec->canConvert == 'yes') {
            
            // Кои са му еквивалентните
            $similar = planning_GenericMapper::getEquivalentProducts($productId);
            
            // Подреждане на еквивалентните му, по к-то им във всички складове
            if (countR($similar)) {
                $orderArr = array();
                foreach ($similar as $k => $pId) {
                    if ($k == $productId) {
                        continue;
                    }
                    $query = store_Products::getQuery();
                    $query->where("#productId = {$k}");
                    $query->XPR('sum', 'double', 'SUM(#quantity)');
                    $sum = $query->fetch()->sum;
                    $orderArr["{$sum}"] = $k;
                }
                
                krsort($orderArr);
                $topKey = $orderArr[key($orderArr)];
                
                // Връщане на себестойността на този с най-голямо количество
                if (!empty($topKey)) {
                    $primeCost = price_ListRules::getPrice(price_ListRules::PRICE_LIST_COST, $topKey);
                    if (!empty($primeCost)) {
                        
                        return $primeCost;
                    }
                }
            }
        }
        
        // Ако е производим, и има търговска рецепта, цената по нея
        if ($pRec->canManifacture == 'yes') {
            $bomId = cat_Products::getLastActiveBom($productId, 'sales');
            if (!empty($bomId)) {
                $primeCost = cat_Boms::getBomPrice($bomId, 1, 0, 0, null, price_ListRules::PRICE_LIST_COST);
                if (!empty($primeCost)) {
                    
                    return $primeCost;
                }
            }
        }
        
        // Ако е складируем, средната му цена му във всички складове
        if ($pRec->canStore == 'yes') {
            $primeCost = cat_Products::getWacAmountInStore(1, $productId, null);
            if (!empty($primeCost)) {
                
                return $primeCost;
            }
        }
        
        // Ако нищо не намери
    }
    
    
    /**
     * Колко е толеранса
     *
     * @param int   $id       - ид на артикул
     * @param float $quantity - к-во
     *
     * @return float|NULL - толеранс или NULL, ако няма
     */
    public static function getTolerance($id, $quantity)
    {
        // Ако има драйвър, питаме него за стойността
        if ($Driver = static::getDriver($id)) {
            $tolerance = $Driver->getTolerance($id, $quantity);
            
            return (!empty($tolerance)) ? $tolerance : null;
        }
    }
    
    
    /**
     * Колко е срока на доставка
     *
     * @param int   $id       - ид на артикул
     * @param float $quantity - к-во
     *
     * @return float|NULL - срока на доставка в секунди или NULL, ако няма
     */
    public static function getDeliveryTime($id, $quantity)
    {
        // Ако има драйвър, питаме него за стойността
        if ($Driver = static::getDriver($id)) {
            $term = $Driver->getDeliveryTime($id, $quantity);
            
            return (!empty($term)) ? $term : null;
        }
    }
    
    
    /**
     * Връща минималното количество за поръчка
     *
     * @param int|NULL $id   - ид на артикул
     * @param string $action - дали да е за продажба или покупка
     *
     * @return float|NULL - минималното количество в основна мярка, или NULL ако няма
     */
    public static function getMoq($id = null, $action = 'sell')
    {
        // Ако има драйвър, питаме го за МКП-то
        if (!isset($id)) {
            
            return;
        }
        
        expect(in_array($action, array('sell', 'buy')));
        
        if ($Driver = static::getDriver($id)) {
            $moq = $Driver->getMoq($id, $action);
            
            return (!empty($moq)) ? $moq : null;
        }
    }
    
    
    /**
     * Допълнителните условия за дадения продукт,
     * които автоматично се добавят към условията на договора
     *
     * @param stdClass    $rec     - ид/запис на артикул
     * @param string      $docType - тип на документа sale/purchase/quotation
     * @param string|NULL $lg      - език
     */
    public static function getConditions($rec, $docType, $lg = null)
    {
        $conditions = array();
        $rec = self::fetchRec($rec);
        
        if ($Driver = static::getDriver($rec)) {
            $conditions = $Driver->getConditions($rec, $docType, $lg);
        }
        
        // Ако има параметър за дефолтни условия. Показва се
        $defParamName = ($docType == 'purchase') ? 'commonConditionPur' : 'commonConditionSale';
        if ($cValue = cat_Products::getParams($rec->id, $defParamName)) {
            $dConditionArr = str::text2Array($cValue);
            foreach ($dConditionArr as $cText) {
                $cText = str::replaceUrlsWithLinks($cText);
                $conditions[] = $cText;
            }
            
            $conditions += $dConditionArr;
        }
        
        return $conditions;
    }
    
    
    /**
     * Връща хеша на артикула (стойност която показва дали е уникален)
     *
     * @param mixed $rec - ид или запис на артикул
     *
     * @return NULL|string - Допълнителните условия за дадения продукт
     */
    public static function getHash($rec)
    {
        // Ако има драйвър, питаме него за стойността
        if ($Driver = static::getDriver($rec)) {
            $rec = self::fetchRec($rec);
            
            return $Driver->getHash(self::getSingleton(), $rec);
        }
    }
    
    
    /**
     *
     *
     * @return string
     */
    public function getExportMasterFieldName()
    {
        return 'productId';
    }
    
    
    /**
     *
     *
     * @return array
     */
    public function getExportFieldsNameFromMaster()
    {
        
        return array('productId' => 'code', 'packQuantity', 'packagingId', 'packPrice', 'batch');
    }
    
    
    /**
     *
     *
     * @param core_Mvc      $masterMvc
     * @param int           $id
     * @param core_FieldSet $csvFields
     *
     * @return array
     */
    public function getRecsForExportInDetails($masterMvc, $mRec, &$csvFields, $activatedBy)
    {
        expect($mRec);
        
        $canSeePrice = haveRole('seePrice,ceo', $activatedBy);
        $pStrName = 'price';
        
        $Detail = null;
        if(isset($masterMvc->mainDetail)){
            $Detail = cls::get($masterMvc->mainDetail);
        }
        
        $detArr = arr::make($masterMvc->details);
        
        expect(!empty($detArr));
        
        $recs = array();
        
        $exportFStr = $this->getExportMasterFieldName();
        $exportFCls = cls::get(get_called_class());
        $fFieldsArr = array();
        
        foreach ($detArr as $dName) {
            if (!cls::load($dName, true)) {
                continue;
            }
            
            $dInst = cls::get($dName);
            
            $detClsId = $dInst->getClassId();
            
            if (!$dInst->fields[$exportFStr]) {
                continue;
            }
            
            if (!($exportFCls instanceof $dInst->fields[$exportFStr]->type->params['mvc'])) {
                continue;
            }
            
            if (!$dInst->masterKey) {
                continue;
            }
            
            $tFieldsArr = array();
            if ($mRec->template) {
                $toggleFields = doc_TplManager::fetchField($mRec->template, 'toggleFields');
                if ($toggleFields && $toggleFields[$dInst->className] !== null) {
                    $tFieldsArr = arr::make($toggleFields[$dInst->className], true);
                }
            }
            
            // Подготвяме полетата, които ще се експортират
            $exportArr = arr::make($this->getExportFieldsNameFromMaster(), true);
            
            // За бачовете - ако не е инсталиран пакета - премахваме полето
            if ($exportArr['batch'] && !core_Packs::isInstalled('batch')) {
                unset($exportArr['batch']);
            }
            
            foreach ($exportArr as $eName => $eFields) {
                if ($eName == $eFields) {
                    $fFieldsArr[$eName] = $eName;
                } else {
                    $fFieldsArr[$eName] = explode('|', $eFields);
                }
            }
            
            $dQuery = $dInst->getQuery();
            $dQuery->where(array("#{$dInst->masterKey} = {$mRec->id}"));
            
            $dQuery->orderBy('id', 'ASC');
           
            while ($dRec = $dQuery->fetch()) {
                if (!$recs[$dRec->id]) {
                    $recs[$dRec->id] = new stdClass();
                }
                
                $recs[$dRec->id]->productId = $dRec->productId;
                $recs[$dRec->id]->packPrice = $dRec->packPrice;
                $recs[$dRec->id]->discount = $dRec->discount;
                
                $allFFieldsArr = $fFieldsArr;
                
                if ($dInst->exportToMaster) {
                    $exportToMasterArr = arr::make($dInst->exportToMaster, true);
                    
                    foreach ($exportToMasterArr as $eName => $eFields) {
                        if ($eName == $eFields) {
                            $exportToMasterArr[$eName] = $eName;
                        } else {
                            $exportToMasterArr[$eName] = explode('|', $eFields);
                        }
                    }
                    
                    $allFFieldsArr = array_merge($allFFieldsArr, $exportToMasterArr);
                }
               
                foreach ($allFFieldsArr as $k => $vArr) {
                    if (!$dInst->fields[$k]) {
                        continue;
                    }
                    
                    if (is_array($vArr) && $dInst->fields[$k]->type->params['mvc']) {
                        
                        // Ако полето е ключ и от него трябва да се вземе стойността на друго поле
                        
                        $vInst = cls::get($dInst->fields[$k]->type->params['mvc']);
                        
                        if (!$dRec->{$k}) {
                            continue;
                        }
                        
                        $vRec = $vInst->fetch($dRec->{$k});
                        
                        foreach ($vArr as $v) {
                            // Ако няма права за виждане на цена, на потребителя, който е активирал
                            if (stripos($v, $pStrName)) {
                                if (!$canSeePrice) {
                                    continue;
                                } elseif (!empty($tFieldsArr)) {
                                    if (!$tFieldsArr[$v]) {
                                        continue;
                                    }
                                }
                            }
                            
                            // Попълване на кода
                            if (($vInst instanceof cat_Products) && ($v == 'code')) {
                                cat_Products::setCodeIfEmpty($vRec);
                            }
                            
                            $recs[$dRec->id]->{$v} = $vRec->{$v};
                            
                            if (!$csvFields->fields[$v]) {
                                if ($vInst->fields[$v]->type instanceof type_Double) {
                                    $csvFields->FLD($v, 'varchar', "caption={$vInst->fields[$v]->caption}");
                                } else {
                                    $csvFields->fields[$v] = $vInst->fields[$v];
                                }
                            }
                        }
                    } else {
                        // Ако няма права за виждане на цена, на потребителя, който е активирал
                        if (stripos($k, $pStrName)) {
                            if (!$canSeePrice) {
                                continue;
                            } elseif (!empty($tFieldsArr)) {
                                if (!$tFieldsArr[$k]) {
                                    continue;
                                }
                            }
                        }
                        
                        $recs[$dRec->id]->{$k} = $dRec->{$k};
                        
                        if (!$csvFields->fields[$k]) {
                            if ($dInst->fields[$k]->type instanceof type_Double) {
                                $csvFields->FLD($k, 'varchar', "caption={$dInst->fields[$k]->caption}");
                            } else {
                                $csvFields->fields[$k] = $dInst->fields[$k];
                            }
                        }
                    }
                }
                
                // Добавяме отстъпката към цената
                if ($allFFieldsArr['packPrice']) {
                    if ($recs[$dRec->id]->packPrice && $dRec->discount && !($masterMvc instanceof deals_InvoiceMaster && $mRec->type == 'dc_note')) {
                        $recs[$dRec->id]->packPrice -= ($recs[$dRec->id]->packPrice * $dRec->discount);
                    }
                }
                
                // За добавяне на бачовете
                if ($allFFieldsArr['batch'] && $masterMvc->storeFieldName && $mRec->{$masterMvc->storeFieldName}) {
                    $Def = batch_Defs::getBatchDef($dRec->{$dInst->productFld});
                    if ($recs[$dRec->id] && isset($recs[$dRec->id]->packQuantity) && $Def) {
                        if (!$csvFields->fields['batch']) {
                            $csvFields->FLD('batch', 'text', 'caption=Партида');
                        }
                        
                        $bQuery = batch_BatchesInDocuments::getQuery();
                        
                        if (isset($dRec->packagingId)) {
                            $bQuery->where(array("#packagingId = '[#1#]'", $dRec->packagingId));
                        }
                        
                        if (isset($dRec->productId)) {
                            $bQuery->where(array("#productId = '[#1#]'", $dRec->productId));
                        }
                        
                        $bQuery->where(array("#detailRecId = '[#1#]'", $dRec->id));
                        $bQuery->where(array("#detailClassId = '[#1#]'", $detClsId));
                        
                        $bQuery->orderBy('id', 'ASC');
                        
                        $haveBatch = false;
                        
                        while ($bRec = $bQuery->fetch()) {
                            $oRec = clone $recs[$dRec->id];
                            
                            $bQuantity = $bRec->quantity;
                            if ($bRec->quantityInPack) {
                                $bQuantity /= $bRec->quantityInPack;
                            }
                            
                            $bName = $dRec->id . '_' . $bRec->id;
                            $recs[$bName] = $oRec;
                            $recs[$bName]->packQuantity = $bQuantity;
                            $recs[$bName]->batch = $bRec->batch;
                            $recs[$dRec->id]->packQuantity -= $recs[$bName]->packQuantity;
                            
                            $haveBatch = true;
                        }
                        
                        if ($haveBatch) {
                            if ($recs[$dRec->id]->packQuantity > 0) {
                                // За да се подреди под другите записи от същия продукт
                                $noBRec = $recs[$dRec->id];
                                unset($recs[$dRec->id]);
                                $recs[$dRec->id] = $noBRec;
                            } else {
                                unset($recs[$dRec->id]);
                            }
                        }
                    }
                }
            }
            
            
            /**
             * Ако артикула е ред във КИ или ДИ със промяна, да се покаже промененото количество
             */
            if($masterMvc instanceof deals_InvoiceMaster){
                if(isset($allFFieldsArr['quantity']) && $mRec->type == 'dc_note'){
                    $Detail::modifyDcDetails($recs, $mRec, $Detail);
                    
                    foreach ($recs as $id => &$mdRec){
                        if ($allFFieldsArr['packPrice']) {
                            if ($mdRec->packPrice && $mdRec->discount) {
                                $mdRec->packPrice -= ($mdRec->packPrice * $mdRec->discount);
                            }
                        }
                        
                        if(!$mdRec->changedQuantity && !$mdRec->changedPrice){
                            unset($recs[$id]);
                        }
                    }
                }
            }
            
            if (!empty($recs)) {
                break;
            }
        }
        
        return $recs;
    }
    
    
    /**
     * Дали артикула се среща в детайла на активни договори (Покупка и продажба)
     *
     * @param int $productId
     *
     * @return bool
     */
    private function isUsedInActiveDeal($productId)
    {
        $productId = (is_object($productId)) ? $productId->id : $productId;
        
        foreach (array('sales_SalesDetails', 'purchase_PurchasesDetails') as $Det) {
            $Detail = cls::get($Det);
            $dQuery = $Detail->getQuery();
            $dQuery->EXT('state', $Detail->Master, "externalName=state,externalKey={$Detail->masterKey}");
            $dQuery->where("#productId = {$productId} AND #state = 'active'");
            $dQuery->show('id');
            $dQuery->limit(1);
            
            if ($dQuery->fetch()) {
                
                return true;
            }
        }
        
        $jQuery = planning_Jobs::getQuery();
        $jQuery->where("#productId = {$productId} AND #state IN ('active', 'wakeup', 'stopped')");
        $jQuery->show('id');
        $jQuery->limit(1);
        if ($jQuery->fetch()) {
            
            return true;
        }
        
        return false;
    }
    
    
    /**
     * Преди затваряне/отваряне на записа
     *
     * @param core_Mvc $mvc      - мениджър
     * @param stdClass $rec      - запис
     * @param string   $newState - ново състояние
     *
     * @return mixed
     */
    protected static function on_BeforeChangeState(core_Mvc $mvc, &$rec, &$newState)
    {
        if ($newState == 'closed' && $mvc->isUsedInActiveDeal($rec)) {
            core_Statuses::newStatus('Артикулът не може да бъде затворен, докато се използва в активни договори и/или задания', 'error');
            
            return false;
        }
    }
    
    
    /**
     * Изпълнява се преди оттеглянето на документа
     */
    protected static function on_BeforeReject(core_Mvc $mvc, &$res, $id)
    {
        if ($mvc->isUsedInActiveDeal($id)) {
            core_Statuses::newStatus('Артикулът не може да бъде оттеглен, докато се използва в активни договори и/или задания', 'error');
            
            return false;
        }
    }
    
    
    /**
     * Колко е 1-ца от артикула в посочената мярка
     *
     * @param int   $productId - ид на артикула
     * @param mixed $uom       - мярка
     *
     * @return NULL|float - конвертираната стойност или NULL ако не може
     */
    public static function convertToUom($productId, $uom)
    {
        // В коя мярка ще се преобразува 1-ца от артикила
        expect($measureId = self::fetchField($productId, 'measureId'));
        expect($toUomId = cat_UoM::fetchBySinonim($uom)->id);
        
        // Ако основната мярка е подадената, то стойноста е 1
        if ($toUomId == $measureId) {
            
            return 1;
        }
        
        // Извличане на мерките от същата група, като на $toUomId
        $sameTypeMeasures = cat_UoM::getSameTypeMeasures($toUomId);
        unset($sameTypeMeasures['']);
        
        // Ако основната мярка е от същата група, конвертира се към $toUomId
        if (array_key_exists($measureId, $sameTypeMeasures)) {
            $res = cat_UoM::convertValue(1, $measureId, $toUomId);
            
            return $res;
        }
        
        // Ако артикула, има доп. мярка, която е от същата група като на $toUomId
        $pQuery = cat_products_Packagings::getQuery();
        $pQuery->where("#productId = {$productId}");
        $pQuery->in('packagingId', array_keys($sameTypeMeasures));
        $pQuery->orderBy('id', 'ASC');
        $pQuery->show('quantity,packagingId');
        while ($pRec = $pQuery->fetch()) {
            
            // Връща се отношението и за 1-ца към $toUomId
            if ($res = cat_UoM::convertValue(1, $pRec->packagingId, $toUomId)) {
                $res = $res / $pRec->quantity;
                
                return $res;
            }
        }
        
        // Ако търсената мярка е от групата на килограмите
        $kgUom = cat_UoM::fetchBySysId('kg')->id;
        $kgUoms = cat_UoM::getSameTypeMeasures($kgUom);
        
        // Взима се стойност от параметрите на артикула
        if (array_key_exists($toUomId, $kgUoms)) {
            if ($paramValue = self::getParams($productId, 'weight')) {
                $res = cat_UoM::convertValue($paramValue, 'gr', $toUomId);
                
                return $res;
            } elseif ($paramValue = self::getParams($productId, 'weightKg')) {
                
                return $paramValue;
            }
        }
    }
    
    
    /**
     * Показване на хинтове към името на артикула
     *
     * @param mixed $name
     * @param int   $id
     * @param mixed $meta
     */
    public static function styleDisplayName(&$name, $id, $meta = null)
    {
        if (Mode::isReadOnly()) {
            
            return;
        }
        
        $hint = '';
        $meta = arr::make($meta, true);
        $metaString = implode(',', $meta);
        $pRec = cat_Products::fetchRec($id, "state,{$metaString}");
        $pRec->canSell = 'no';
        if ($pRec->state != 'active') {
            $hint .= tr('Артикулът не е активен|*!');
        }
        
        foreach ($meta as $m) {
            if ($pRec->{$m} != 'yes') {
                $hint = (empty($hint) ? '' : ' ') . tr('Артикулът има премахнати свойства|*!');
                break;
            }
        }
        
        if (!empty($hint)) {
            $name = ht::createHint($name, $hint);
        }
    }
    
    
    /**
     * Обновява modified стойностите
     *
     * @param core_Master $mvc
     * @param bool|NULL   $res
     * @param int         $id
     */
    protected static function on_AfterTouchRec($mvc, &$res, $id)
    {
        if($rec = $mvc->fetchRec($id)){
            $keywords = $mvc->getSearchKeywords($rec);
            if($rec->searchKeywords != $keywords){
                $keywords = plg_Search::purifyKeywods($keywords);
                $rec->searchKeywords = $keywords;
                $mvc->save_($rec, 'searchKeywords');
                $cRec = (object)array('id' => $rec->containerId, 'searchKeywords' => $rec->searchKeywords);
                
                $containersInst = cls::get('doc_Containers');
                $containersInst->save_($cRec, 'searchKeywords');
            }
        }
    }
}
