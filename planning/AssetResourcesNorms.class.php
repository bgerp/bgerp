<?php


/**
 * Мениджър на нормите за производство
 *
 *
 * @category  bgerp
 * @package   planning
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class planning_AssetResourcesNorms extends core_Manager
{
    /**
     * Заглавие
     */
    public $title = 'Норми за дейности';
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Норма за дейности';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, plg_Created, planning_Wrapper, plg_State2, plg_AlignDecimals2';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo, planningMaster';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo, planningMaster';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'no_one';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo, planning';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'objectId,productId=Действие,packagingId=Мярка/Опаковка,indTime,limit,state';
    
    
    /**
     * Кои полета от листовия изглед да се скриват ако няма записи в тях
     */
    public $hideListFieldsIfEmpty = 'state';
    
    
    /**
     * Дали в листовия изглед да се показва бутона за добавяне
     */
    public $listAddBtn = false;
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('objectId', 'int', 'caption=Оборудване/Група,mandatory,silent,input=hidden,tdClass=leftCol');
        $this->FLD('classId', 'class', 'caption=Клас,mandatory,silent,input=hidden');
        $this->FLD('productId', 'key2(mvc=cat_Products,select=name,selectSourceArr=cat_Products::getProductOptions,allowEmpty,maxSuggestions=100,forceAjax,titleFld=name)', 'silent,mandatory,caption=Артикул,removeAndRefreshForm=indTime,class=w100');
        $this->FLD('indTime', 'planning_type_ProductionRate', 'caption=Норма,smartCenter,mandatory');
        $this->FLD('packagingId', 'key(mvc=cat_UoM,select=shortName)', 'caption=Опаковка,smartCenter,input=hidden');
        $this->FLD('quantityInPack', 'double', 'input=hidden');
        $this->FLD('limit', 'double(min=0)', 'caption=Лимит,smartCenter');
        
        $this->setDbUnique('classId,objectId,productId');
        $this->setDbIndex('classId,objectId');
    }
    
    
    /**
     * Подготовка на детайла
     *
     * @param stdClass $data
     *
     * @return void
     */
    public function prepareDetail_(&$data)
    {
        $data->recs = $data->rows = array();
        $masterClassId = $data->masterMvc->getClassId();
        $query = self::getQuery();
        $query->where("#classId = {$masterClassId} AND #objectId = {$data->masterId}");
        
        // Извличане на записите
        while ($rec = $query->fetch()) {
            $data->recs[$rec->productId] = $rec;
            $data->rows[$rec->productId] = $this->recToVerbal($rec);
        }
        
        // Бутон за добавяне на нова норма
        if ($this->haveRightFor('add', (object) array('classId' => $masterClassId, 'objectId' => $data->masterId))) {
            $addUrl = array($this, 'add', 'classId' => $masterClassId, 'objectId' => $data->masterId, 'ret_url' => true);
            $data->addUrl = $addUrl;
        }
        
        // Ако се показва в Оборудването
        if ($data->masterMvc instanceof  planning_AssetResources) {
            
            // Взимат се всички норми от групата му
            $gQuery = self::getQuery();
            $gQuery->where("#classId = {$data->masterMvc->Master->getClassId()} AND #objectId = {$data->masterData->rec->groupId} AND #state != 'closed'");
            $gQuery->notIn('productId', arr::extractValuesFromArray($data->recs, 'productId'));
            
            // Те ще се показват под неговите норми
            while ($rec = $gQuery->fetch()) {
                $data->recs[$rec->productId] = $rec;
                $row = $this->recToVerbal($rec);
                $row->ROW_ATTR['class'] = 'zebra1';
                core_RowToolbar::createIfNotExists($row->_rowTools);
                $row->_rowTools->removeBtn('*');

                $row->indTime = "<span style='color:blue'>{$row->indTime}</span>";
                $row->indTime = ht::createHint($row->indTime, 'Нормата е зададена във вида на оборудването', 'notice', false);
                
                unset($row->state);
                if (isset($addUrl)) {
                    $addUrl['productId'] = $rec->productId;
                    $row->_rowTools->addLink('', $addUrl, array('ef_icon' => 'img/16/add.png', 'title' => 'Задаване на норма само за това оборудване'));
                }
                
                $data->rows[$rec->productId] = $row;
            }
        }
        
        // Подготовка на полетата на таблицата
        $this->prepareListFields($data);
    }
    
    
    /**
     * Рендиране на детайла
     *
     * @param stdClass $data
     *
     * @return core_ET $tpl
     */
    public function renderDetail_($data)
    {
        $tpl = new core_ET('');
        $tpl = $this->renderList($data);
        
        if (isset($data->addUrl)) {
            $addBtn = ht::createBtn('Нова норма', $data->addUrl, false, false, 'ef_icon=img/16/star_2.png,title=Добавяне на нова норма');
            $tpl->replace($addBtn, 'ListToolbar');
        }
        
        return $tpl;
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = &$data->form;
        $rec = $form->rec;

        $form->setFieldTypeParams('productId', array('hasProperties' => 'canConvert', 'hasnotProperties' => 'canStore', 'driverId' => cat_GeneralProductDriver::getClassId()));
        $form->setSuggestions('limit', array('' => '', '1' => '1'));

        if(isset($rec->productId)){
            $measureId = cat_Products::fetchField($rec->productId, 'measureId');
            $form->setFieldTypeParams('indTime', array('measureId' => $measureId));
        }
    }


    /**
     * След подготовката на заглавието на формата
     */
    protected static function on_AfterPrepareEditTitle($mvc, &$res, &$data)
    {
        $data->form->title = core_Detail::getEditTitle($data->form->rec->classId, $data->form->rec->objectId, $mvc->singleTitle, $data->form->rec->id);
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     */
    protected static function on_AfterInputEditForm($mvc, &$form)
    {
        $rec = $form->rec;
        
        if ($form->isSubmitted()) {
            $rec->packagingId = cat_Products::fetchField($rec->productId, 'measureId');
            $rec->quantityInPack = 1;
        }
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
        $row->productId = cat_Products::getHyperlink($rec->productId, true);
        $row->objectId = cls::get($rec->classId)->getHyperlink($rec->objectId, true);
        if (!isset($rec->limit)) {
            $row->limit = "<i class='quiet'>" . tr('Няма||No') . '</i>';
        }

        $measureId = cat_Products::fetchField($rec->productId, 'measureId');
        $row->indTime = core_Type::getByName("planning_type_ProductionRate(measureId={$measureId})")->toVerbal($rec->indTime);
    }
    
    
    /**
     * Преди подготовката на полетата за листовия изглед
     */
    protected static function on_AfterPrepareListFields($mvc, &$res, &$data)
    {
        if (isset($data->masterMvc)) {
            unset($data->listFields['objectId']);
        }
    }
    
    
    /**
     * Намира норма за артикула
     *
     * @param mixed      $class     - клас към който е нормата
     * @param int        $objectId  - ид на обект
     * @param int|NULL   $productId - ид на точен артикул
     * @param array|NULL $notIn     - ид на артикули да се изключат
     *
     * @return array $res	        - запис на нормата
     */
    public static function fetchNormRec($class, $objectId, $productId = null, $notIn = null)
    {
        $res = array();
        $classId = cls::get($class)->getClassId();
        
        $query = self::getQuery();
        $query->where("#classId = {$classId} AND #objectId = {$objectId} AND #state != 'closed'");
        $query->show('productId,indTime,packagingId,quantityInPack,limit');
        $query->notIn('productId', $notIn);
        if (isset($productId)) {
            $query->where("#productId = {$productId}");
        }
        
        while ($rec = $query->fetch()) {
            $res[$rec->productId] = $rec;
        }
        
        return $res;
    }
    
    
    /**
     * Връща опциите за избор на действия за оборудването
     *
     * @param int      $assetId  - списък с оборудвания
     * @param array    $notIn    - ид-та на артикули, които да се игнорират
     * @param boolean  $onlyIds  - дали да са само ид-та
     *
     * @return array $options   - имена на действия, групирани по оборудвания
     */
    public static function getNormOptions($assetId, $notIn = array(), $onlyIds = false)
    {
        $options = array();

        $groupId = planning_AssetResources::fetchField($assetId, 'groupId');
        $groupAssets = self::fetchNormRec('planning_AssetGroups', $groupId, null, $notIn);
        $notIn += arr::make(array_keys($groupAssets), true);
        
        $arr = array();
        if (countR($groupAssets)) {
            $group = planning_AssetGroups::getVerbal($groupId, 'name');
            if(!$onlyIds){
                $options = array('g' => (object) array('group' => true, 'title' => $group));
            }
            foreach ($groupAssets as $productId => $rec) {
                $title = ($onlyIds) ? $productId : cat_Products::getTitleById($productId, false);
                $arr[$rec->productId] = $title;
            }
            $options += $arr;
        }

        $assetArr = array();
        $assetNorms = self::fetchNormRec('planning_AssetResources', $assetId, null, $notIn);
        foreach ($assetNorms as $productId => $rec1) {
            $title = ($onlyIds) ? $productId : cat_Products::getTitleById($productId, false);
            $assetArr[$rec1->productId] = $title;
        }
        if (countR($assetArr)) {
            if(!$onlyIds){
                $assetName = planning_AssetResources::getTitleById($assetId, false);
                $options += array("a{$assetId}" => (object) array('group' => true, 'title' => $assetName));
            }
            $options += $assetArr;
        }
        
        // Връщане на готовите опции
        return $options;
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if ($action == 'changestate' && isset($rec)) {
            $groupState = cls::get($rec->classId)->fetchField($rec->objectId, 'state');
            if ($groupState == 'closed') {
                $requiredRoles = 'no_one';
            }
        }
        
        if ($action == 'add' && isset($rec)) {
            if (empty($rec->classId) || empty($rec->objectId)) {
                $requiredRoles = 'no_one';
            } elseif ($rec->classId != planning_AssetResources::getClassId() && $rec->classId != planning_AssetGroups::getClassId()) {
                $requiredRoles = 'no_one';
            }
        }
    }


    /**
     * Връща зададените норми в центъра на дейност
     *
     * @param int $centerId
     * @param null|string $exIds
     * @return array $options
     */
    public static function getAllNormOptions($centerId, $exIds = null)
    {
        $options = array();
        $folderId = planning_Centers::fetchField($centerId, 'folderId');
        $assetOptions = planning_AssetResources::getByFolderId($folderId);
        $assetIds = array_keys($assetOptions);
        if(!countR($assetIds)) return $options;

        // Всички нормиз ададени към конкретни оборудвания
        $assetClassId = planning_AssetResources::getClassId();
        $query = static::getQuery();
        $query->where("#classId = {$assetClassId} AND #objectId IN (" . implode(',', $assetIds). ")");
        $query->show('productId');

        if(countR($assetIds)){

            // Добавят се и всички норми зададени към групите на оборудванията
            $groupClassId = planning_AssetGroups::getClassId();
            $gQuery = planning_AssetResources::getQuery();
            $gQuery->in('id', $assetIds);
            $gQuery->show('groupId');
            $groupIds = arr::extractValuesFromArray($gQuery->fetchAll(), 'groupId');
            $query->orWhere("#classId = {$groupClassId} AND #objectId IN (" . implode(',', $groupIds). ")");
        }
        $productIds = arr::extractValuesFromArray($query->fetchAll(), 'productId');
        if(isset($exIds)){
            $productIds += keylist::toArray($exIds);
        }

        foreach ($productIds as $productId){
            $options[$productId] = cat_Products::getTitleById($productId, false);
        }

        return $options;

    }
}
