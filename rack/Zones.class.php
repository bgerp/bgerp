<?php


/**
 * Модел за "Зони"
 *
 *
 * @category  bgerp
 * @package   rack
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class rack_Zones extends core_Master
{
    
    
    /**
     * Заглавие
     */
    public $title = 'Зони';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'rack_Wrapper,plg_Sorting,plg_SaveAndNew,plg_Created,plg_State2,plg_RowTools2';
    
    
    /**
     * Кой може да добавя?
     */
    public $canAdd = 'admin,ceo,rack';
    
    
    /**
     * Кой може да редактира?
     */
    public $canEdit = 'admin,ceo,rack,store';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'admin,ceo,rack';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'admin,ceo,rack';
    
    
    /**
     * Полета в листовия изглед
     */
    public $listFields = 'name,storeId,containerId,readiness,state,createdOn,createdBy,pendingHtml=@';
    
    
    /**
     * Кои полета от листовия изглед да се скриват ако няма записи в тях
     *
     *  @var string
     */
    public $hideListFieldsIfEmpty = 'pendingHtml';
    
    
    /**
     * Детайла, на модела
     */
    public $details = 'rack_Journals';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    public $rowToolsSingleField = 'name';
    
    
    /**
     * Кой може да селектира документа
     */
    public $canSelectdocument = 'admin,ceo,rack';
    
    
    /**
     * Файл с шаблон за единичен изглед на статия
     */
    public $singleLayoutFile = 'rack/tpl/SingleLayoutZone.shtml';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('name', 'varchar(16)', 'caption=Зона,mandatory');
        $this->FLD('storeId', 'key(mvc=store_Stores,select=name,allowEmpty)', 'caption=Склад,mandatory,remember');
        $this->FLD('containerId', 'key(mvc=doc_Containers)', 'caption=Документ,input=none');
        $this->FLD('summaryData', 'blob(serialize, compress)', 'input=none');
        $this->FLD('readiness', 'percent', 'caption=Готовност,mandatory,input=none');
        
        $this->setDbUnique('name');
        $this->setDbIndex('storeId');
        $this->setDbIndex('containerId');
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        $row->storeId = store_Stores::getHyperlink($rec->storeId, true);
        if (isset($rec->containerId)){
            $row->containerId = doc_Containers::getDocument($rec->containerId)->getLink(0);
        }
        
        if ($fields['-list']){
            $pendingHtml = rack_Journals::getPendingTableHtml($rec->id);
            if (!empty($pendingHtml)){ 
                $row->pendingHtml = "<span class='small'>{$pendingHtml}</span>";
            }
        }
    }
    
    
    /**
     * Рекалкулира статистиката на зоната
     * 
     * @param mixed $id - ид или запис на зона
     * 
     * @return void
     */
    protected function recalcSummaryData($id)
    {
        $rec = $this->fetchRec($id);
       
        $summary = array();
        $journalRecs = rack_Journals::getSummaryRecs($rec->id);
        $documentRecs = isset($rec->containerId) ? doc_Containers::getDocument($rec->containerId)->getProductsSummary() : array();
        $summaryProducts = arr::make(array_keys($journalRecs), true) + arr::make(array_keys($documentRecs), true);
        
        $ready = null;
        foreach ($summaryProducts as $productId){
            $summaryRec = (object)array('productId' => $productId, 'measureId' => cat_Products::fetchField($productId, 'measureId'));
            $summaryRec->jQuantity = (array_key_exists($productId, $journalRecs)) ? $journalRecs[$productId]->quantity : null;
            $summaryRec->dQuantity = (array_key_exists($productId, $documentRecs)) ? $documentRecs[$productId]->quantity : null;
            $summary[$productId] = $summaryRec;
            
            if(isset($summaryRec->dQuantity) && round($summaryRec->jQuantity, 4) == round($summaryRec->dQuantity, 4)){
                $ready++;
            }
        }
        
        $rec->summaryData = $summary;
        $rec->readiness = isset($ready) ? round(($ready / count($summary)), 2) : null;
        
        $this->save($rec, 'summaryData,readiness');
    }
    
    
    /**
     * След подготовка на сингъла
     */
    protected static function on_AfterPrepareSingle($mvc, &$res, $data)
    {
        if(rack_Journals::haveRightFor('list')){
            $data->toolbar->addBtn('Архив', array('rack_Journals', 'list', 'zoneId' => $data->rec->id), 'ef_icon=img/16/bug.png,title=Към архива на движенията в зоната');
        }
        
        $rec = &$data->rec;
        $data->summary = (object)array('rows' => array());
        $data->summary->recs = is_array($rec->summaryData) ? $rec->summaryData : array();
        
        // Вербализиране на статистиката
        foreach ($data->summary->recs as $productId => $summaryRec){
            $summaryRow = (object)array('productId' => cat_Products::getHyperlink($summaryRec->productId, true), 'measureId' => cat_UoM::getShortName($summaryRec->measureId));
            if(isset($summaryRec->jQuantity)){
                $summaryRow->jQuantity = core_Type::getByName('double(smartRound)')->toVerbal($summaryRec->jQuantity);
                $summaryRow->jQuantity = ht::styleIfNegative($summaryRow->jQuantity, $summaryRec->jQuantity);
            }
            if(isset($summaryRec->dQuantity)){
                $summaryRow->dQuantity = core_Type::getByName('double(smartRound)')->toVerbal($summaryRec->dQuantity);
                $summaryRow->dQuantity = ht::styleIfNegative($summaryRow->dQuantity, $summaryRec->dQuantity);
            }
            
            $summaryRow->orderName = cat_Products::getTitleById($productId);
            $summaryRow->ROW_ATTR['class'] = 'row-added';
            $data->summary->rows[$productId] = $summaryRow;
        }
        
        arr::sortObjects($data->summary->rows, 'orderName', 'asc', 'natural');
        
        // Добавяне на бутон за нагласяне
        if (rack_Journals::haveRightFor('add', (object)array('zoneId' => $rec->id, 'operation' => 'take'))) {
            $data->summary->takeUrl = array('rack_Journals', 'add', 'zoneId' => $rec->id, 'operation' => 'take', 'ret_url' => true);
        }
        
        // Добавяне на бутон за връщане
        if (rack_Journals::haveRightFor('add', (object)array('zoneId' => $rec->id, 'operation' => 'put'))) {
            $data->summary->putUrl = array('rack_Journals', 'add', 'zoneId' => $rec->id, 'operation' => 'put', 'ret_url' => true);
        }
    }
    
    
    /**
     * Поддържа точна информацията за записите в детайла
     */
    protected static function on_AfterUpdateDetail(core_Master $mvc, $id, core_Manager $detailMvc)
    {
        // Обновяване на статистиката на зоната
        $rec = $mvc->fetchRec($id);
        $mvc->recalcSummaryData($rec);
    }
    
    
    /**
     * След рендиране на единичния изглед
     */
    protected static function on_AfterRenderSingle($mvc, &$tpl, $data)
    {
        if(isset($data->summary)){
            $fld = new core_FieldSet();
            $fld->FLD('productId', 'varchar', 'tdClass=productCell leftCol wrap');
            $fld->FLD('measureId', 'varchar', 'smartCenter');
            $fld->FLD('jQuantity', 'double', 'smartCenter');
            $fld->FLD('dQuantity', 'double', 'smartCenter');
            
            // Рендиране на таблицата на статистиката
            $table = cls::get('core_TableView', array('mvc' => $fld));
            $details = $table->get($data->summary->rows, "productId=Артикул,measureId=Мярка,jQuantity=Нагласено,dQuantity=Очаквано");
            $tpl->append($details, "SUMMARY_TABLE");
            
            if(isset($data->summary->takeUrl)){
                $takeBtn = ht::createBtn('Нагласяне', $data->summary->takeUrl, false, false, 'title=Нагласяне на палет в зоната,ef_icon=img/16/bug.png');
                $tpl->replace($takeBtn, 'takeBtn');
            }
            
            if(isset($data->summary->putUrl)){
                $putBtn = ht::createBtn('Връщане', $data->summary->putUrl, false, false, 'title=Връщане на палет от зоната,ef_icon=img/16/bug.png');
                $tpl->replace($putBtn, 'putBtn');
            }
        }
    }
    
    
    
    /**
     * Връща зоните към подадения склад
     * 
     * @param int|NULL $storeId
     * 
     * @return array $options
     */
    public static function getFreeZones($storeId = NULL)
    {
        $query = self::getQuery();
        $query->where("#state != 'closed' AND #containerId IS NULL");
        if(isset($storeId)){
            $query->where("#storeId = {$storeId}");
        }
        
        $options = array();
        while($rec = $query->fetch()){
            $options[$rec->id] = self::getVerbal($rec, 'name'); 
        }
       
        return $options;
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
        $form->setDefault('storeId', store_Stores::getCurrent('id', FALSE));
        
        // Ако има работен запис към зоната не може да се сменя склада
        if (isset($form->rec->containerId)){
            $form->setReadOnly('storeId');
        }
    }
    
    
    /**
     * Добавя филтър към перата
     *
     * @param acc_Items $mvc
     * @param stdClass  $data
     */
    protected static function on_AfterPrepareListFilter($mvc, $data)
    {
        $storeId = store_Stores::getCurrent();
        $data->query->where("#storeId = {$storeId}");
        $data->title = 'Зони в склад|* <b style="color:green">' . store_Stores::getTitleById($storeId) . '</b>';
    }
    
    
    /**
     * Избор на зона в документ
     * 
     * @return void|core_ET
     */
    function act_Selectdocument()
    {
        // Проверка на права
        $this->requireRightFor('selectdocument');
        expect($containerId = Request::get('containerId', 'int'));
        expect($document = doc_Containers::getDocument($containerId));
        $this->requireRightFor('selectdocument', (object)array('containerId' => $containerId));
        $documentRec = $document->fetch();
        
        // Подготовка на формата
        $form = cls::get('core_Form');
        $form->title = 'Събиране на редовете на|* ' . $document->getFormTitleLink();
        $form->FLD('zoneId', 'key(mvc=rack_Zones,select=name)', 'caption=Зона');
        $zoneOptions = rack_Zones::getFreeZones($documentRec->{$document->storeFieldName});
        $zoneId = rack_Zones::fetchField("#containerId = {$containerId}", 'id');
        if(!empty($zoneId) && !array_key_exists($zoneId, $zoneOptions)){
            $zoneOptions[$zoneId] = $this->getVerbal($zoneId, 'name');
        }
        $form->setOptions('zoneId', array('' => '') + $zoneOptions);
        $form->setDefault('zoneId', $zoneId);
        $form->input();
        
        // Изпращане на формата
        if($form->isSubmitted()){
            $fRec = $form->rec;
            
            // Присвояване на новата зона
            if(isset($fRec->zoneId)){
                $zoneRec = $this->fetch($fRec->zoneId);
                $zoneRec->containerId = $containerId;
                $this->save($zoneRec);
                $this->updateMaster($zoneRec);
            }
            
            // Старата зона се отчуждава от документа
            if($zoneId != $fRec->zoneId && isset($zoneId)){
                $zoneRec1 = $this->fetch($zoneId);
                $zoneRec1->containerId = NULL;
                $this->save($zoneRec1);
                $this->updateMaster($zoneRec1);
            }
            
            // Ако е избрана зона редирект към нея, иначе се остава в документа
            if(isset($fRec->zoneId)){
                redirect(array('rack_Zones', 'single', $fRec->zoneId));
            }
            
            followRetUrl();
        }
        
        // Добавяне на бутони
        $form->toolbar->addSbBtn('Запис', 'save', 'ef_icon = img/16/move.png, title = Запис на действието');
        $form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close-red.png, title=Прекратяване на действията');
        
        // Записваме, че потребителя е разглеждал този списък
        $document->logInfo('Избор на зона');
        $tpl = $document->getInstance()->renderWrapping($form->renderHtml());
        core_Form::preventDoubleSubmission($tpl, $form);
        
        return $tpl;
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if ($action == 'selectdocument' && isset($rec)){
            if(empty($rec->containerId)){
                $requiredRoles = 'no_one';
            } else {
                $document = doc_Containers::getDocument($rec->containerId);
                $selectedStoreId = store_Stores::getCurrent('id', false);
                $documentRec = $document->fetch("state,{$document->storeFieldName}");
                
                if(!$document->haveRightFor('single') || !in_array($documentRec->state, array('draft', 'pending')) || $documentRec->{$document->storeFieldName} != $selectedStoreId){
                    $requiredRoles = 'no_one';
                }
            }
        }
        
        if (($action == 'delete' || $action == 'changestate') && isset($rec)){
            if(isset($rec->containerId) || rack_Journals::fetchField("#zoneId = {$rec->id}")){
                $requiredRoles = 'no_one';
            }
        }
    }
    
    
    /**
     * Изчистване на зоната към която е закачен документа
     * 
     * @param int $containerId
     */
    public static function clearZone($containerId)
    {
        // Към коя зона е в момента закачен документа
        $zoneRec = self::fetch("#containerId = {$containerId}");
        if (empty($zoneRec)) return;
        
        // Затваряне на текущите записи към зоната
        rack_Journals::closeRecs($zoneRec->id, $containerId);
        $zoneRec->containerId = NULL;
        self::save($zoneRec);
    }
    
    
    /**
     * Обновява данни в мастъра
     *
     * @param int $id първичен ключ на статия
     * @return int $id ид-то на обновения запис
     */
    public function updateMaster_($id)
    {
        $rec = $this->fetchRec($id);
        $this->recalcSummaryData($rec);
    }
    
    
    /**
     * Изпълнява се след подготвянето на тулбара в листовия изглед
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     *
     * @return bool
     */
    protected static function on_AfterPrepareListToolbar($mvc, &$res, $data)
    {
        $storeId = store_Stores::getCurrent();
        if (rack_Journals::haveRightFor('orderpickup', (object)array('storeId' => $storeId))) {
            $data->toolbar->addBtn('Нагласяне', array('rack_Journals', 'orderpickup', 'storeId' => $storeId,'ret_url' => TRUE), 'ef_icon=img/16/arrow_refresh.png,title=Бързо нагласяне');
        }
    }
}