<?php


/**
 * Мениджър за производствени етапи, разширение към артикулите
 *
 * @category  bgerp
 * @package   planning
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2022 Experta OOD
 * @license   GPL 3
 *
 * @since     0.12
 */
class planning_Steps extends core_Extender
{
    /**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    public $oldClassName = 'planning_Stages';


    /**
     * Заглавие
     */
    public $title = 'Етапи в производството';
    
    
    /**
     * Единично заглавие
     */
    public $singleTitle = 'Етап в производството';
    
    
    /**
     * Плъгини и MVC класове, които се зареждат при инициализация
     */
    public $loadList = 'planning_Wrapper,plg_RowTools2,plg_GroupByField,plg_Search,plg_Rejected';


    /**
     * По-кое поле да се групират листовите данни
     */
    public $groupByField = 'centerId';


    /**
     * Кой има достъп до лист изгледа
     */
    public $canList = 'ceo,planning,name';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'name=Етап,centerId=Център,fixedAssets,employees,storeIn=Складове->Произвеждане,inputStores=Складове->Влагане,norm=Норма,modifiedOn=Модифицирано->На,modifiedBy=Модифицирано->От||By';


    /**
     * Кой може да го разглежда?
     */
    public $canSingle = 'no_one';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'centerId,name,fixedAssets,storeIn,inputStores';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    protected $extenderFields = 'centerId,name,canStore,norm,offsetAfter,inputStores,storeIn,calcWeightMode,labelTransferQuantityInPack,fixedAssets,planningParams,employees,isFinal,interruptOffset,labelPackagingId,planningActions,labelQuantityInPack,labelType,labelTemplate,showPreviousJobField,wasteProductId,wasteStart,wastePercent,mandatoryDocuments,supportSystemFolderId';


    /**
     * Какъв да е интерфейса на позволените ембедъри
     *
     * @var string
     */
    protected $extenderClassInterfaces = 'cat_ProductAccRegIntf';


    /**
     * Полета, които при клониране да не са попълнени
     *
     * @see plg_Clone
     */
    public $fieldsNotToClone = 'name';


    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('centerId', 'key(mvc=planning_Centers,select=name)', 'caption=Използване в производството->Център,mandatory,silent');
        $this->FLD('name', 'varchar', 'caption=Използване в производството->Операция,placeholder=Ако не се попълни - името на артикула,tdClass=leftCol wrapText');
        
        $this->FLD('state', 'enum(draft=Чернова, active=Активен, rejected=Оттеглен, closed=Затворен)', 'caption=Състояние');
        
        $this->FLD('fixedAssets', 'keylist(mvc=planning_AssetResources,select=name,makeLinks=hyperlink)', 'caption=Използване в производството->Оборудване');
        $this->FLD('employees', 'keylist(mvc=crm_Persons,select=id,makeLinks)', 'caption=Използване в производството->Оператори');
        $this->FLD('planningParams', 'keylist(mvc=cat_Params,select=typeExt)', 'caption=Използване в производството->Параметри');
        $this->FLD('isFinal', 'enum(no=Междинен етап,yes=Финален етап)', 'caption=Използване в производството->Вид,notNull,value=no');
        $this->FLD('showPreviousJobField', 'enum(auto=Автоматично,no=Скриване,yes=Показване)', 'caption=Използване в производството->Предходно задание,notNull,value=no');
        $this->FLD('calcWeightMode', 'enum(auto=Автоматично,no=Изключено,yes=Включено)', 'caption=Използване в производството->Въвеждане на тегло,notNull,value=auto');
        $this->FLD('supportSystemFolderId', 'key2(mvc=doc_Folders,select=title,coverClasses=support_Systems,allowEmpty)', 'caption=Използване в производството->Система за сигнали,placeholder=Автоматично');
        $this->FLD('canStore', 'enum(yes=Да,no=Не)', 'caption=Складове->Складируем,notNull,value=yes,silent');
        $this->FLD('inputStores', 'keylist(mvc=store_Stores,select=name,allowEmpty,makeLink)', 'caption=Складове->Влагане ОТ');
        $this->FLD('storeIn', 'key(mvc=store_Stores,select=name,allowEmpty)', 'caption=Складове->Произвеждане В');
        
        $this->FLD('planningActions', 'keylist(mvc=cat_Products,select=name,makeLink)', 'caption=Планиране на производството->Действия');
        $this->FLD('norm', 'planning_type_ProductionRate', 'caption=Планиране на производството->Норма');
        $this->FLD('interruptOffset', 'time', 'caption=Планиране на производството->Отместване,hint=Отместване при прекъсване в графика на оборудването');
        $this->FLD('offsetAfter', 'time', 'caption=Планиране на производството->Изчакване след,hint=Колко време да се изчака след изпълнение на операцията за етапа');
        $this->FLD('mandatoryDocuments', 'classes(select=title)', 'caption=Планиране на производството->Задължителни,hint=Задължително изискуеми документи (поне един от всеки избран тип) за да може да бъде приключена операцията');

        $this->FLD('labelPackagingId', 'key(mvc=cat_UoM,select=name,allowEmpty)', 'caption=Етикиране в производството->Опаковка,input=hidden,tdClass=small-field nowrap,placeholder=Няма,silent');
        $this->FLD('labelQuantityInPack', 'double(smartRound,Min=0)', 'caption=Етикиране в производството->В опаковката (к-во),tdClass=small-field nowrap,input=hidden');
        $this->FLD('labelTransferQuantityInPack', 'enum(yes=Прехвърляне на к-то в операцията,no=Да не се прехвърля к-то в операцията)', 'caption=Етикиране в производството->Към операцията,tdClass=small-field nowrap,input=hidden');
        $this->FLD('labelType', 'enum(print=Генериране,scan=Въвеждане,both=Комбинирано,autoPrint=Генериране и печат)', 'caption=Етикиране в производството->Производ. №,tdClass=small-field nowrap,input=hidden');
        $this->FLD('labelTemplate', 'key(mvc=label_Templates,select=title)', 'caption=Етикиране в производството->Шаблон,tdClass=small-field nowrap,input=hidden');

        $this->FLD('wasteProductId', 'key2(mvc=cat_ProductsProxy,select=name,selectSourceArr=cat_Products::getProductOptions,allowEmpty,maxSuggestions=100,forceAjax)', 'caption=Отпадък в производствена операция->Артикул,silent,class=w100');
        $this->FLD('wasteStart', 'double(min=0,smartRound)', 'caption=Отпадък в производствена операция->Начален');
        $this->FLD('wastePercent', 'percent(min=0)', 'caption=Отпадък в производствена операция->Допустим');


        $this->setDbIndex('state');
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
        $rec = &$form->rec;

        if(isset($rec->id)){
            $form->setField("{$mvc->className}_wasteProductId", "autohide");
            $form->setField("{$mvc->className}_wasteStart", "autohide");
            $form->setField("{$mvc->className}_wastePercent", "autohide");
        }

        // Добавяне на полетата от екстендъра възможност за рефреш
        $form->setField("measureId", "removeAndRefreshForm,silent");
        $form->setField("{$mvc->className}_canStore", "removeAndRefreshForm={$mvc->className}_storeIn");
        $form->setField("{$mvc->className}_centerId", "removeAndRefreshForm={$mvc->className}_fixedAssets|{$mvc->className}_employees|{$mvc->className}_norm|{$mvc->className}_planningActions|{$mvc->className}_showPreviousJobField");
        $form->setField("{$mvc->className}_labelPackagingId", "removeAndRefreshForm={$mvc->className}_labelQuantityInPack|{$mvc->className}_labelTemplate|{$mvc->className}_labelType");
        $form->setDefault("{$mvc->className}_canStore", 'yes');

        $form->setDefault("{$mvc->className}_centerId", planning_Centers::UNDEFINED_ACTIVITY_CENTER_ID);
        $form->input("{$mvc->className}_canStore,{$mvc->className}_centerId,measureId,{$mvc->className}_labelPackagingId", 'silent');
        $wasteSysId = cat_Groups::getKeylistBySysIds('waste');
        $form->setFieldTypeParams("{$mvc->className}_wasteProductId", array('hasProperties' => 'canStore,canConvert', 'groups' => $wasteSysId));

        // Добавяне на избор само на Параметрите за производствени операции
        $paramSuggestions = cat_Params::getTaskParamOptions($rec->{"{$mvc->className}_planningParams"});
        $form->setSuggestions("{$mvc->className}_planningParams", $paramSuggestions);

        $mandatoryClassOptions = static::getMandatoryClassOptions();
        $form->setSuggestions("{$mvc->className}_mandatoryDocuments", array('' => '') + $mandatoryClassOptions);

        if($form->getField('meta', false)){
            $form->setField('meta', 'input=none');
        }

        // Ако артикула е складируем и има партидна дефиниция да не може тя да се сменя
        if(isset($rec->id) && core_Packs::isInstalled('batch')){
            if(batch_Defs::getBatchDef($rec->id)){
                $form->setReadOnly("{$mvc->className}_canStore");
                $form->setField("{$mvc->className}_canStore", 'hint=Артикулът е с партида|*!');
            }
        }

        // Добавяне на достъпните ресурси от центъра
        if(isset($rec->{"{$mvc->className}_centerId"})){
            $centerRec = planning_Centers::fetch($rec->{"{$mvc->className}_centerId"}, 'folderId,showPreviousJobField');
            $actionOptions = planning_AssetResourcesNorms::getAllNormOptions($rec->{"{$mvc->className}_centerId"}, $rec->{"{$mvc->className}_planningActions"});

            $form->setSuggestions("{$mvc->className}_planningActions", $actionOptions);
            $form->setSuggestions("{$mvc->className}_employees", planning_Hr::getByFolderId($centerRec->folderId, $rec->{"{$mvc->className}_employees"}));
            $form->setSuggestions("{$mvc->className}_fixedAssets", planning_AssetResources::getByFolderId($centerRec->folderId, $rec->{"{$mvc->className}_fixedAssets"}, 'planning_Tasks',true));
            $form->setDefault("{$mvc->className}_showPreviousJobField", $centerRec->showPreviousJobField);
        }

        if(isset($rec->measureId)){
            $form->setFieldTypeParams("{$mvc->className}_norm", array('measureId' => $rec->measureId));
        }

        if($rec->{"{$mvc->className}_canStore"} != 'yes'){
            $form->setField("{$mvc->className}_storeIn", 'input=none');
        } else {

            // Ако артикула е складируем показват се полетата за етикетиране
            $form->setField("{$mvc->className}_labelPackagingId", 'input');

            // Ако артикула е съществуващ само наличните опаковки са достъпни
            $labelPacks = planning_Tasks::getAllowedLabelPackagingOptions($rec->measureId, $rec->id, $rec->{"{$mvc->className}_labelPackagingId"});
            $form->setOptions("{$mvc->className}_labelPackagingId", $labelPacks);

            // Ако има избрана опаковка за етикиране
            if(!empty($rec->{"{$mvc->className}_labelPackagingId"})){
                $templateOptions = planning_Tasks::getAllAvailableLabelTemplates($rec->{"{$mvc->className}_labelTemplate"});
                $form->setOptions("{$mvc->className}_labelTemplate", $templateOptions);

                $form->setField("{$mvc->className}_labelQuantityInPack", 'input');
                $form->setField("{$mvc->className}_labelType", 'input');
                $form->setField("{$mvc->className}_labelTemplate", 'input');
                $form->setField("{$mvc->className}_labelTransferQuantityInPack", 'input');

                // При редакция на артикул наличните опаковки за етикетиране са само тези на артикула
                if(isset($rec->id)){
                    $packRec = cat_products_Packagings::getPack($rec->id, $rec->{"{$mvc->className}_labelPackagingId"});
                    $quantityInPack = is_object($packRec) ? $packRec->quantity : 1;
                    if($data->action == 'clone'){
                        $form->setDefault("{$mvc->className}_labelQuantityInPack", $quantityInPack);
                    } else {
                        $form->setField("{$mvc->className}_labelQuantityInPack", "placeholder={$quantityInPack}");
                    }
                }
            }

            if(isset($rec->{"{$mvc->className}_wasteProductId"})){
                $wasteProductMeasureId = cat_Products::fetchField($rec->{"{$mvc->className}_wasteProductId"}, 'measureId');
                $form->setField("{$mvc->className}_wasteStart", "unit=" . cat_UoM::getShortName($wasteProductMeasureId));
            }
        }
    }


    /**
     * Опции за задължителни класове в нишката на ПО за етапа
     */
    public static function getMandatoryClassOptions()
    {
        $options = array();
        $mandatoryClasses = array('planning_DirectProductionNote', 'planning_ReturnNotes', 'planning_ConsumptionNotes');
        foreach ($mandatoryClasses as $mandatoryClass){
            $Class = cls::get($mandatoryClass);
            $options[$Class->getClassId()] = cls::getTitle($Class);
        }

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
        $rec = &$form->rec;
        
        if($form->isSubmitted()){

            // Ако артикула не е складируем се зануляват определени полета
            $metaArr = type_Set::toArray($rec->meta);
            if($rec->{"{$mvc->className}_canStore"} == 'no'){
                unset($metaArr['canStore']);
                $rec->{"{$mvc->className}_storeIn"} = null;
                $rec->{"{$mvc->className}_labelPackagingId"} = null;
            } else {
                $metaArr['canStore'] = 'canStore';
            }
            $rec->meta = implode(',', $metaArr);

            // Ако няма опаковка се зануляват полетата за етикетиране
            if(empty($rec->{"{$mvc->className}_labelPackagingId"})){
                $rec->{"{$mvc->className}_labelQuantityInPack"} = null;
                $rec->{"{$mvc->className}_labelType"} = null;
                $rec->{"{$mvc->className}_labelTemplate"} = null;
            }

            // При създаване, ако е посочена опаковка за етикет - задължително трябва да е въведено количество в нея
            if(isset($rec->{"{$mvc->className}_labelPackagingId"})){
                if(isset($rec->{"{$mvc->className}_labelQuantityInPack"})){
                    if($rec->{"{$mvc->className}_labelPackagingId"} == $rec->measureId && $rec->{"{$mvc->className}_labelQuantityInPack"} != 1){
                        $form->setError("{$mvc->className}_labelQuantityInPack", 'Ако за етикиране е избрана основната мярка, то количеството не може да е различно от 1|*!');
                    }
                } elseif(!isset($rec->id) || isset($rec->clonedFromId)){
                    $form->setError("{$mvc->className}_labelQuantityInPack", 'Трябва да е въведено количество при добавяне на нова опаковка|*!');
                }
            }
        }
    }
    
    
    /**
     * Извиква се преди запис в модела
     *
     * @param core_Mvc     $mvc     Мениджър, в който възниква събитието
     * @param int          $id      Тук се връща първичния ключ на записа, след като бъде направен
     * @param stdClass     $rec     Съдържащ стойностите, които трябва да бъдат записани
     * @param string|array $fields  Имена на полетата, които трябва да бъдат записани
     * @param string       $mode    Режим на записа: replace, ignore
     */
    protected static function on_BeforeSave(core_Mvc $mvc, &$id, $rec, &$fields = null, $mode = null)
    {
        if(empty($rec->name)){
            $prodRec = cls::get($rec->classId)->fetch($rec->objectId, 'name');
            $rec->name = $prodRec->name;
        }

        // Коя е била старата опаковка за етикетиране
        if(isset($rec->id)){
            $rec->_oldLabelPackagingId = $mvc->fetchField($rec->id, 'labelPackagingId', false);
        } else {
            $rec->_isCreated = true;
        }
    }


    /**
     * Изпълнява се след създаване на нов запис
     */
    protected static function on_AfterCreate($mvc, $rec)
    {
        $measureId = cls::get($rec->classId)->fetchField($rec->objectId, 'measureId');

        // След създаване добавя се запис за продуктовата опаковка
        if(isset($rec->labelPackagingId) && $rec->labelPackagingId != $measureId){
            $newPack = (object)array('productId' => $rec->objectId,
                                     'packagingId' => $rec->labelPackagingId,
                                     'quantity' => $rec->labelQuantityInPack,
                                     'firstClassId' => $rec->classId,
                                     'firstDocId' => $rec->objectId,
            );

            cat_products_Packagings::save($newPack);
        }
    }


    /**
     * Изпълнява се след запис на перо
     * Предизвиква обновяване на обобщената информация за перата
     */
    protected static function on_AfterSave($mvc, $id, $rec)
    {
        // Ако е сменена опаковката за етикетиране
        if(isset($rec->_oldLabelPackagingId)){
            if($rec->_oldLabelPackagingId != $rec->labelPackagingId){
                $packRec = cat_products_Packagings::getPack($rec->objectId, $rec->_oldLabelPackagingId);

                // Ако опаковката е била използвана за първи път от артикула - занулява се така, че да може да се промени при нужда
                if(is_object($packRec)){
                    if($packRec->firstClassId == $rec->classId && $packRec->firstDocId == $rec->objectId){
                        $packRec->firstClassId = $packRec->firstDocId = null;
                        cat_products_Packagings::save($packRec, 'firstClassId,firstDocId');
                    }
                }
            }
        }

        // След редакция
        if($rec->_isCreated !== true){
            if(isset($rec->labelPackagingId)){

                // Ако избраната опаковка за етикетиране не е използвана никъде маркира се като използвана
                $packRec = cat_products_Packagings::getPack($rec->objectId, $rec->labelPackagingId);
                if(is_object($packRec)){
                    if(empty($packRec->firstClassId)){
                        $packRec->firstClassId = $rec->classId;
                        $packRec->firstDocId = $rec->objectId;
                        cat_products_Packagings::save($packRec, 'firstClassId,firstDocId');
                    }
                }
            }
        }
    }


    /**
     * Синхронизиране на екстендъра с мениджъра, към който е
     */
    protected static function on_AfterSyncWithManager($mvc, $rec, $managerRec)
    {
        // Състоянието на екстендъра се синхронизира с това на мениджъра
        $rec->state = $managerRec->state;
        $mvc->save_($rec, 'state');
        plg_Search::forceUpdateKeywords($mvc, $rec);
    }


    /**
     * Какво да е дефолтното урл, за добавяне от листовия изглед
     *
     * @param int|null $userId
     * @return array $addUrl
     */
    public function getListAddUrl($userId = null)
    {
        $addUrl = array();

        // Папката в която дефолтно да се създава етапа, се взима от уеб константа
        if(cls::get('planning_interface_StepProductDriver')->canSelectDriver($userId)) {
            $driverId = planning_interface_StepProductDriver::getClassId();
            if (cat_Products::haveRightFor('add', (object)array('innerClass' => $driverId), $userId)) {
                $addUrl = array('cat_Products', 'add', 'innerClass' => $driverId, 'ret_url' => true);

                $folderId = planning_Setup::get('DEFAULT_PRODUCTION_STEP_FOLDER_ID');
                if($folderId && doc_Folders::haveRightToFolder($folderId)){
                    $addUrl['folderId'] = $folderId;
                }
            }
        }
        
        return $addUrl;
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        if(isset($fields['-list'])){
            if($Extended = $mvc->getExtended($rec)){
                $singleUrl = $Extended->getSingleUrlArray();
                $row->name = ht::createLink($row->name, $singleUrl, false, "ef_icon={$Extended->getIcon()}");
                
                $prodRec = $Extended->fetch('modifiedOn,modifiedBy');
                $prodRow = cat_Products::recToVerbal($prodRec, 'modifiedOn,modifiedBy');
                
                $row->modifiedOn = $prodRow->modifiedOn;
                $row->modifiedBy = crm_Profiles::createLink($prodRec->modifiedBy);
                $row->ROW_ATTR['class'] = "state-{$rec->state}";
            } else {
                $row->name = "<span class='red'>" . tr('Проблем с показването') . "</span>";
            }
        }

        if(isset($rec->wasteProductId)){
            $row->wasteProductId = cat_Products::getHyperlink($rec->wasteProductId, true);
            $wasteProductMeasureId = cat_Products::fetchField($rec->wasteProductId, 'measureId');
            if(!empty($rec->wasteStart)){
                $row->wasteStart .= " " . cat_UoM::getShortName($wasteProductMeasureId);
            }
        }

        if($Extended = $mvc->getExtended($rec)){
            if(empty($rec->planningActions)){
                $row->planningActions = "<i class='quiet'>n/a</i>";
            }
            if(!Mode::is('printing')){
                if($Extended->haveRightFor('editplanned')){
                    $row->planningActions .= ht::createLink('', array($Extended->getInstance(), 'editplanned', $Extended->that, 'ret_url' => true), false, 'ef_icon=img/16/edit.png');
                }
            }

            $row->norm = null;
            if(isset($rec->norm)){
                $row->norm = core_Type::getByName("planning_type_ProductionRate(measureId={$Extended->fetchField('measureId')})")->toVerbal($rec->norm);
            }

            if(isset($rec->storeIn)){
                $row->storeIn = store_Stores::getHyperlink($rec->storeIn, true);
            }

            $row->centerId = planning_Centers::getHyperlink($rec->centerId, true);

            if(!empty($rec->employees)){
                $row->employees = implode(', ', planning_Hr::getPersonsCodesArr($rec->employees, true));
            }

            if(empty($rec->labelQuantityInPack) && isset($rec->labelPackagingId)){
                $packRec = cat_products_Packagings::getPack($rec->objectId, $rec->labelPackagingId);
                $quantityInPackDefault = is_object($packRec) ? $packRec->quantity : 1;
                $quantityInPackDefault = "<span style='color:blue'>" . core_Type::getByName('double(smartRound)')->toVerbal($quantityInPackDefault) . "</span>";
                $quantityInPackDefault = ht::createHint($quantityInPackDefault, 'От опаковката/мярката на артикула');
                $row->labelQuantityInPack = $quantityInPackDefault;
            }

            if(isset($rec->labelTemplate)){
                $row->labelTemplate = label_Templates::getHyperlink($rec->labelTemplate, true);
            }

            if($rec->calcWeightMode == 'auto'){
                $row->calcWeightMode = $mvc->getFieldType('calcWeightMode')->toVerbal(planning_Setup::get('TASK_WEIGHT_MODE'));
                $row->calcWeightMode = ht::createHint($row->calcWeightMode, 'По подразбиране', 'notice', 'false');
            }

            if(empty($rec->labelPackagingId)){
                unset($row->labelTransferQuantityInPack);
            }

            $systemFolderId = $rec->supportSystemFolderId ?? planning_Centers::fetchField($rec->centerId, 'supportSystemFolderId');
            if(isset($systemFolderId)){
                $row->supportSystemFolderId = doc_Folders::recToVerbal($systemFolderId)->title;
                if(!$rec->supportSystemFolderId) {
                    $row->supportSystemFolderId = ht::createHint($row->supportSystemFolderId, 'По подразбиране от центъра на дейност', 'notice', false);
                }
            }
        }
    }
    
    
    /**
     * Подготовка на филтър формата
     */
    protected static function on_AfterPrepareListFilter($mvc, &$data)
    {
        $data->listFilter->FLD('finalType', 'enum(all=Всички,no=Междинен етап,yes=Финален етап)');
        $data->listFilter->FLD('assetId', 'key(mvc=planning_AssetResources,select=name,allowEmpty)', 'caption=Оборудване');
        $data->listFilter->setFieldType('centerId', 'key(mvc=planning_Centers,select=name,allowEmpty)');
        $data->listFilter->setOptions('assetId', planning_AssetResources::getByFolderId());
        $data->listFilter->setDefault('finalType', 'all');
        $data->listFilter->showFields = 'search,centerId,assetId,finalType';
        $data->listFilter->view = 'horizontal';
        $data->listFilter->input();

        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        $data->query->orderBy('centerId,state,id', 'asc');
        
        if($filterRec = $data->listFilter->rec){
            if(!empty($filterRec->centerId)){
                $data->query->where("#centerId = {$filterRec->centerId}");
            }
            if($filterRec->finalType != 'all'){
                $data->query->where("#isFinal = '{$filterRec->finalType}'");
            }
            if(isset($filterRec->assetId)){
                $data->query->where("LOCATE('|{$filterRec->assetId}|', #fixedAssets)");
            }
        }
    }
    
    
    /**
     * След подготовка на единичния изглед
     *
     * @param stdClass     $data
     */
    public function prepareStages_(&$data)
    {
        $data->TabCaption = 'Етапи';
        $data->Order = 2;
        
        $data->recs = $data->rows = array();
        $fields = $this->selectFields();
        $fields['-list'] = true;

        // Подготовка на записите
        $query = self::getQuery();
        $query->where("#centerId = {$data->masterId} AND #state != 'rejected'");
        $query->orderBy('state', "ASC");
        
        while($rec = $query->fetch()){
            $data->recs[$rec->id] = $rec;
            $data->rows[$rec->id] = $this->recToVerbal($rec, $fields);
        }
        
        $this->prepareListFields($data);
        unset($data->listFields['centerId']);
        unset($data->listFields['state']);
        $data->addUrl = static::getListAddUrl();
        if(countR($data->addUrl)){
            $data->addUrl["{$this->className}_centerId"] = $data->masterId;
        }
    }
    
    
    /**
     * След рендиране на единичния изглед
     *
     * @param stdClass $data
     * @return core_ET $tpl
     */
    public function renderStages_(&$data)
    {
        $tpl = getTplFromFile('crm/tpl/ContragentDetail.shtml');
        $tpl->append(tr('Етапи в производството'), 'title');
        
        // Рендиране на таблицата с резултатите
        $table = cls::get('core_TableView', array('mvc' => $this));
        $this->invoke('BeforeRenderListTable', array($tpl, &$data));
        $tableHtml = $table->get($data->rows, $data->listFields);
        $tpl->append($tableHtml, 'content');
        
        if(countR($data->addUrl)){
            $addBtn = ht::createLink('', $data->addUrl, false, "title=Добавяне на нов епат в производството в центъра на дейност,ef_icon=img/16/add.png");
            $tpl->append($addBtn, 'title');
        }
        
        return $tpl;
    }


    /**
     * Връща активните операции за предходните етапи, на етапа в рамките на подаденото задание
     *
     * @param int $stepId      - ид на артикул - етап
     * @param int $containerId - контейнер на задание, в което ще се търсят операциите
     * @return array $res
     */
    public static function getPreviousStepTaskIds($stepId, $containerId)
    {
        $res = array();

        // Кои са предходните етапи на този етап
        $cQuery = planning_StepConditions::getQuery();
        $cQuery->where("#stepId = {$stepId}");
        $cQuery->show('prevStepId');
        $prevStepIds = arr::extractValuesFromArray($cQuery->fetchAll(), 'prevStepId');
        if(!countR($prevStepIds)) return $res;

        // Всички текущи ПО към заданието за посочените етапи
        $tQuery = planning_Tasks::getQuery();
        $tQuery->where("#originId = {$containerId} AND #state IN ('active', 'stopped', 'wakeup', 'closed')");
        $tQuery->in('productId', $prevStepIds);
        $tQuery->show('id');
        $res = arr::extractValuesFromArray($tQuery->fetchAll(), 'id');

        return $res;
    }


    /**
     * Връща достъпните избираеми продуктови етапи
     */
    public static function getSelectableSteps($params, $limit = null, $q = '', $onlyIds = null, $includeHiddens = false)
    {
        $productClassId = cat_Products::getClassId();
        $driverClassId = planning_interface_StepProductDriver::getClassId();
        $pQuery = cat_Products::getQuery();
        $pQuery->where("#innerClass = {$driverClassId}");
        if (is_array($onlyIds)) {
            if (!countR($onlyIds)) {

                return array();
            }
            $ids = implode(',', $onlyIds);
            $pQuery->where("#id IN ({$ids})");
        } elseif (ctype_digit("{$onlyIds}")) {
            $pQuery->where("#id = ${onlyIds}");
        } else {
            $pQuery->where("#state != 'closed' AND #state != 'rejected'");

            if(isset($params['centerFolderId'])){
                $sQuery = planning_Steps::getQuery();
                $Cover = doc_Folders::getCover($params['centerFolderId']);
                $sQuery->where("#centerId = {$Cover->that} AND #state != 'closed' AND #state != 'rejected' AND #classId = {$productClassId}");
                $sQuery->show('objectId');
                $in = arr::extractValuesFromArray($sQuery->fetchAll(), 'objectId');

                if(countR($in)){
                    $pQuery->in('id', $in);
                } else {
                    $pQuery->where("1=2");
                }
            }
        }

        cat_Products::addSearchQueryToKey2SelectArr($pQuery, $q, $limit);
        $res = $finalSteps = $nonFinalSteps = array();
        while ($pRec = $pQuery->fetch()) {
            $isFinal = planning_Steps::fetchField("#objectId = {$pRec->id} AND #classId = {$productClassId}", 'isFinal');
            if($isFinal == 'yes'){
                $finalSteps[$pRec->id] = cat_Products::getRecTitle($pRec, false);
            } else {
                $nonFinalSteps[$pRec->id] = cat_Products::getRecTitle($pRec, false);
            }
        }

        if (countR($nonFinalSteps)) {
            asort($nonFinalSteps, SORT_NATURAL);
            if (!isset($onlyIds)) {
                $nonFinalSteps = array('nfs' => (object) array('group' => true, 'title' => tr('Междинни етапи'))) + $nonFinalSteps;
            }
            $res += $nonFinalSteps;
        }

        if (countR($finalSteps)) {
            asort($finalSteps, SORT_NATURAL);
            if (!isset($onlyIds)) {
                $finalSteps = array('fs' => (object) array('group' => true, 'title' => tr('Финални етапи'))) + $finalSteps;
            }
            $res += $finalSteps;
        }

        return $res;
    }


    /**
     * Колко са активните етапи в папката на посочения център на дейност
     *
     * @param int $folderId  - ид на папка
     * @return int           - брой намерени етапи
     */
    public static function getCountByCenterFolderId($folderId)
    {
        $Cover = doc_Folders::getCover($folderId);
        $productClassId = cat_Products::getClassId();

        return static::count("#centerId = {$Cover->that} AND #state != 'closed' AND #state != 'rejected' AND #classId = {$productClassId}");
    }


    /**
     * Връща масив с отместванията
     *
     * @param array $tasks
     * @return array $interruptionArr
     */
    public static function getInterruptionArr($tasks)
    {
        // Какви са плануваните отмествания при прекъсване
        $taskProductIds = arr::extractValuesFromArray($tasks, 'productId');
        $iQuery = planning_Steps::getQuery();
        $iQuery->where("#classId = " . cat_Products::getClassId());
        $iQuery->show('interruptOffset,objectId');
        if (!empty($taskProductIds)) {
            $iQuery->in("objectId", $taskProductIds);
        }

        $interruptionArr = array();
        while($iRec = $iQuery->fetch()){
            $interruptionArr[$iRec->objectId] = $iRec->interruptOffset;
        }

        return $interruptionArr;
    }


    /**
     * Добавя ключови думи за пълнотекстово търсене
     */
    protected static function on_AfterGetSearchKeywords($mvc, &$res, $rec)
    {
        $res = ' ' . cls::get($rec->classId)::fetchField($rec->objectId, 'searchKeywords');
    }
}
