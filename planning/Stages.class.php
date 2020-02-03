<?php


/**
 * Мениджър за производствени етапи, разширение към артикулите
 *
 * @category  bgerp
 * @package   planning
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 *
 * @since     0.12
 */
class planning_Stages extends core_Extender
{
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
    public $loadList = 'planning_Wrapper,plg_RowTools2,plg_Search,plg_Rejected';
    
    
    /**
     * Кой има достъп до лист изгледа
     */
    public $canList = 'ceo,planning';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'name=Етап,folders=Центрове,canStore=Засклаждане,norm=Норма,state,modifiedOn=Модифицирано->На,modifiedBy=Модифицирано->От||By';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canSingle = 'no_one';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'folders,name';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    protected $extenderFields = 'folders,name,canStore,norm';
    
    
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
        $this->FLD('folders', 'keylist(mvc=doc_Folders, select=title, allowEmpty,makeLinks)', 'caption=Използване в производството->Центрове на дейност, remember,mandatory,silent');
        $this->FLD('name', 'varchar', 'caption=Използване в производството->Наименование,placeholder=Ако не се попълни - името на артикула,tdClass=leftCol');
        $this->FLD('canStore', 'enum(yes=Да,no=Не)', 'caption=Използване в производството->Складируем,notNull,value=yes');
        $this->FLD('norm', 'time', 'caption=Използване в производството->Норма');
        $this->FLD('state', 'enum(draft=Чернова, active=Активен, rejected=Оттеглен, closed=Затворен)', 'caption=Състояние');
        
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
        
        $resourceSuggestionsArr = doc_Folders::getSelectArr(array('titleFld' => 'title', 'restrictViewAccess' => 'yes', 'coverClasses' => 'planning_Centers'));
        $form->setSuggestions("{$mvc->className}_folders", $resourceSuggestionsArr);
        $form->setDefault("{$mvc->className}_folders", keylist::addKey('', planning_Centers::getUndefinedFolderId()));
    
        $form->setField('meta', 'input=none');
        if(isset($rec->id) && core_Packs::isInstalled('batch')){
            if(batch_Defs::getBatchDef($rec->id)){
                $form->setReadOnly("{$mvc->className}_canStore");
                $form->setField("{$mvc->className}_canStore", 'hint=Артикулът е с партида|*!');
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
        
        if($form->isSubmitted()){
            $metaArr = type_Set::toArray($rec->meta);
            if($rec->{"{$mvc->className}_canStore"} == 'no'){
                unset($metaArr['canStore']);
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
     * @return array $addUrl
     */
    protected function getListAddUrl()
    {
        $addUrl = array();
        if($driverId = planning_interface_StageDriver::getClassId()){
            if (cat_Products::haveRightFor('add', (object)array('innerClass' => $driverId)) && cls::get($driverId)->canSelectDriver()) {
                $addUrl = array('cat_Products', 'add', 'innerClass' => $driverId, 'ret_url' => true);
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
    }
    
    
    /**
     * Подготовка на филтър формата
     */
    protected static function on_AfterPrepareListFilter($mvc, &$data)
    {
        $data->listFilter->FLD('centerFolderId', 'key(mvc=doc_Folders,select=name)','placeholder=Център на дейност');
        $centerOptionsArr = doc_Folders::getSelectArr(array('titleFld' => 'title', 'restrictViewAccess' => 'yes', 'coverClasses' => 'planning_Centers'));
        $data->listFilter->setOptions('centerFolderId', array('' => '') + $centerOptionsArr);
        
        $data->listFilter->showFields = 'search,centerFolderId';
        $data->listFilter->view = 'horizontal';
        $data->listFilter->input();
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        $data->query->orderBy('state', 'asc');
        
        if($filterRec = $data->listFilter->rec){
            if(!empty($filterRec->centerFolderId)){
                $data->query->where("LOCATE('|{$filterRec->centerFolderId}|', #folders)");
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
        $blockTpl = getTplFromFile('planning/tpl/StageBlock.shtml');
        $blockTpl->placeObject($data->row);
        $blockTpl->removeBlocksAndPlaces();
        $tpl->append($blockTpl, 'ADDITIONAL_TOP_BLOCK');
    }
    
    
    /**
     * След подготовка на единичния изглед
     *
     * @param core_Manager $mvc
     * @param core_ET      $tpl
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
        $query->where("LOCATE('|{$data->masterData->rec->folderId}|', #folders)");
        $query->where("#state != 'rejected'");
        $query->orderBy('state', "ASC");
        
        while($rec = $query->fetch()){
            $data->recs[$rec->id] = $rec;
            $data->rows[$rec->id] = $this->recToVerbal($rec, $fields);
        }
        
        $this->prepareListFields($data);
        unset($data->listFields['folders']);
        $data->addUrl = self::getListAddUrl($data->masterData->rec->id);
    }
    
    
    /**
     * След рендиране на единичния изглед
     *
     * @param core_Manager $mvc
     * @param core_ET      $tpl
     * @param stdClass     $data
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
            $addBtn = ht::createLink('', $data->addUrl, false, "title=Добавяне на нов производствен етап в центъра на дейност,ef_icon=img/16/add.png");
            $tpl->append($addBtn, 'title');
        }
        
        return $tpl;
    }
}