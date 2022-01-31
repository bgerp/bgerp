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
    public $canList = 'ceo,planning';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'name=Етап,centerId=Център,fixedAssets,employees,norm,storeIn,storeInput,state,modifiedOn=Модифицирано->На,modifiedBy=Модифицирано->От||By';


    /**
     * Кой може да го разглежда?
     */
    public $canSingle = 'no_one';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'centerId,name';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    protected $extenderFields = 'centerId,name,canStore,norm,storeInput,storeIn,fixedAssets,employees';
    
    
    /**
     * Какъв да е интерфейса на позволените ембедъри
     *
     * @var string
     */
    protected $extenderClassInterfaces = 'cat_ProductAccRegIntf';
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('centerId', 'key(mvc=planning_Centers,select=name)', 'caption=Използване в производството->Център,mandatory,silent');
        $this->FLD('name', 'varchar', 'caption=Използване в производството->Наименование,placeholder=Ако не се попълни - името на артикула,tdClass=leftCol');
        $this->FLD('canStore', 'enum(yes=Да,no=Не)', 'caption=Използване в производството->Складируем,notNull,value=yes,silent');

        $this->FLD('state', 'enum(draft=Чернова, active=Активен, rejected=Оттеглен, closed=Затворен)', 'caption=Състояние');
        $this->FLD('storeInput', 'key(mvc=store_Stores,select=name,allowEmpty)', 'caption=Използване в производството->Склад влагане');
        $this->FLD('storeIn', 'key(mvc=store_Stores,select=name,allowEmpty)', 'caption=Използване в производството->Склад приемане');
        $this->FLD('fixedAssets', 'keylist(mvc=planning_AssetResources,select=name,makeLinks=hyperlink)', 'caption=Използване в производството->Оборудване');
        $this->FLD('employees', 'keylist(mvc=crm_Persons,select=id,makeLinks)', 'caption=Използване в производството->Оператори');
        $this->FLD('norm', 'planning_type_ProductionRate', 'caption=Използване в производството->Норма');

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

        $form->setField("measureId", "removeAndRefreshForm,silent");
        $form->setField("{$mvc->className}_canStore", "removeAndRefreshForm={$mvc->className}_storeInput|{$mvc->className}_storeIn");
        $form->setField("{$mvc->className}_centerId", "removeAndRefreshForm={$mvc->className}_fixedAssets|{$mvc->className}_employees|{$mvc->className}_norm");
        $form->setDefault("{$mvc->className}_canStore", 'yes');

        $form->setDefault("{$mvc->className}_centerId", planning_Centers::UNDEFINED_ACTIVITY_CENTER_ID);
        $form->input("{$mvc->className}_canStore,{$mvc->className}_centerId,measureId", 'silent');

        if($form->getField('meta', false)){
            $form->setField('meta', 'input=none');
        }
        
        if(isset($rec->id) && core_Packs::isInstalled('batch')){
            if(batch_Defs::getBatchDef($rec->id)){
                $form->setReadOnly("{$mvc->className}_canStore");
                $form->setField("{$mvc->className}_canStore", 'hint=Артикулът е с партида|*!');
            }
        }

        if(isset($rec->{"{$mvc->className}_centerId"})){
            $folderId = planning_Centers::fetchField($rec->{"{$mvc->className}_centerId"}, 'folderId');
            $form->setSuggestions("{$mvc->className}_employees", planning_Hr::getByFolderId($folderId, $rec->{"{$mvc->className}_employees"}));
            $form->setSuggestions("{$mvc->className}_fixedAssets", planning_AssetResources::getByFolderId($folderId, $rec->{"{$mvc->className}_fixedAssets"}));
        }

        if(isset($rec->measureId)){
            $form->setFieldTypeParams("{$mvc->className}_norm", array('measureId' => $rec->measureId));
        }

        if($rec->{"{$mvc->className}_canStore"} != 'yes'){
            $form->setField("{$mvc->className}_storeInput", 'input=none');
            $form->setField("{$mvc->className}_storeIn", 'input=none');
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
        
        if($form->isSubmitted()){
            $metaArr = type_Set::toArray($rec->meta);
            if($rec->{"{$mvc->className}_canStore"} == 'no'){
                unset($metaArr['canStore']);
                $rec->{"{$mvc->className}_storeInput"} = null;
                $rec->{"{$mvc->className}_storeIn"} = null;
            } else {
                $metaArr['canStore'] = 'canStore';
            }
            $rec->meta = implode(',', $metaArr);
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
    }
    
    
    /**
     * Синхронизиране на екстендъра с мениджъра, към който е
     */
    protected static function on_AfterSyncWithManager($mvc, $rec, $managerRec)
    {
        // Състоянието на екстендъра се синхронизира с това на мениджъра
        $rec->state = $managerRec->state;
        $mvc->save_($rec, 'state');
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

        if($Extended = $mvc->getExtended($rec)){
            if(isset($rec->norm)){
                $row->norm = core_Type::getByName("planning_type_ProductionRate(measureId={$Extended->fetchField('measureId')})")->toVerbal($rec->norm);
            } else {
                $row->norm = null;
            }

            if(isset($rec->storeInput)){
                $row->storeInput = store_Stores::getHyperlink($rec->storeInput, true);
            }

            if(isset($rec->storeIn)){
                $row->storeIn = store_Stores::getHyperlink($rec->storeIn, true);
            }

            $row->centerId = planning_Centers::getHyperlink($rec->centerId, true);

            if(!empty($rec->employees)){
                $row->employees = implode(', ', planning_Hr::getPersonsCodesArr($rec->employees, true));
            }
        }
    }
    
    
    /**
     * Подготовка на филтър формата
     */
    protected static function on_AfterPrepareListFilter($mvc, &$data)
    {
        $data->listFilter->showFields = 'search,centerId';
        $data->listFilter->view = 'horizontal';
        $data->listFilter->input();
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        $data->query->orderBy('centerId,state', 'asc');
        
        if($filterRec = $data->listFilter->rec){
            if(!empty($filterRec->centerId)){
                $data->query->where("#centerId = {$filterRec->centerId}");
            }
        }
    }
    
    
    /**
     * След рендиране на единичния изглед
     *
     * @param core_Manager $mvc
     * @param core_ET      $tpl
     * @param stdClass     $data
     */
    protected static function on_AfterRenderSingle($mvc, &$tpl, $data)
    {
        // Показване на данните от екстендъра в шаблона
        $blockTpl = getTplFromFile('planning/tpl/StepBlock.shtml');
        $blockTpl->placeObject($data->row);
        $blockTpl->removeBlocksAndPlaces();
        $tpl->append($blockTpl, 'ADDITIONAL_TOP_BLOCK');
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
}