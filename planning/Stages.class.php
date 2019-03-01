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
    public $title = 'Производствени етапи';
    
    
    /**
     * Единично заглавие
     */
    public $singleTitle = 'Производствен етап';
    
    
    /**
     * Плъгини и MVC класове, които се зареждат при инициализация
     */
    public $loadList = 'planning_Wrapper,plg_RowTools2,plg_Search';
    
    
    /**
     * Кой има достъп до лист изгледа
     */
    public $canList = 'ceo,planning';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'name=Етап,folders=Центрове,objectId=Артикул,canStore=Засклаждане,norm=Норма,modifiedOn=Модифицирано->На,modifiedBy=Модифицирано->От||By';
    
    
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
        $this->FLD('name', 'varchar', 'caption=Използване в производството->Наименование,placeholder=Ако не се попълни - името на артикула');
        $this->FLD('canStore', 'enum(yes=Да,no=Не)', 'caption=Използване в производството->Засклаждане,notNull,value=yes');
        $this->FLD('norm', 'time', 'caption=Използване в производството->Норма');
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
       
        $resourceSuggestionsArr = doc_Folders::getSelectArr(array('titleFld' => 'title', 'restrictViewAccess' => 'yes', 'coverClasses' => 'planning_Centers'));
        $form->setSuggestions("{$mvc->className}_folders", $resourceSuggestionsArr);
    
        $form->setDefault("{$mvc->className}_canStore", 'yes');
        $form->setDefault("{$mvc->className}_folders", keylist::addKey('', planning_Centers::getUndefinedFolderId()));
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
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    protected static function on_AfterPrepareListToolbar($mvc, &$data)
    {
        // Документа не може да се създава  в нова нишка, ако е възоснова на друг
        $driverId = planning_interface_StageDriver::getClassId();
        if (cat_Products::haveRightFor('add', (object)array('innerClass' => $driverId)) && cls::get($driverId)->canSelectDriver()) {
            $data->toolbar->addBtn('Нов запис', array('cat_Products', 'add', 'innerClass' => $driverId, 'ret_url' => true), false, 'ef_icon = img/16/star_2.png,title=Добавяне на произведен артикул');
        }
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
        $row->objectId = cls::get($rec->classId)->getHyperlink($rec->objectId, true);
        
        if(isset($fields['-list'])){
            $prodRec = cls::get($rec->classId)->fetch($rec->objectId, 'modifiedOn,modifiedBy,state');
            $prodRow = cat_products::recToVerbal($prodRec, 'modifiedOn,modifiedBy');
            
            $row->modifiedOn = $prodRow->modifiedOn;
            $row->modifiedBy = crm_Profiles::createLink($prodRec->modifiedBy);
            $row->ROW_ATTR['class'] = "state-{$prodRec->state}";
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
        $blockTpl = getTplFromFile('planning/tpl/StageBlock.shtml');
        $blockTpl->placeObject($data->row);
        $blockTpl->removeBlocksAndPlaces();
        $tpl->append($blockTpl, 'ADDITIONAL_BLOCK');
    }
}