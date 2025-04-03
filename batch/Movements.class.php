<?php


/**
 * Движения на партиди
 *
 *
 * @category  bgerp
 * @package   batch
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2024 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class batch_Movements extends core_Detail
{
    /**
     * Заглавие
     */
    public $title = 'Движения на партиди';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_AlignDecimals2, batch_Wrapper, plg_RowNumbering, plg_Sorting, plg_Created, plg_SelectPeriod, bgerp_plg_Export, bgerp_plg_CsvExport';
    
    
    /**
     * Кои полета да се показват в листовия изглед
     */
    public $listFields = 'date, document=Документ,createdOn=Създаване||Created,operation,quantity';
    
    
    /**
     * Наименование на единичния обект
     */
    public $singleTitle = 'Движение на партида';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'powerUser';
    
    
    /**
     * Кой може да пише?
     */
    public $canWrite = 'no_one';


    /**
     * Кой има право да експортва?
     */
    public $canExport = 'ceo,batch';
    
    
    /**
     * Ключ към мастъра
     */
    public $masterKey = 'itemId';
    
    
    /**
     * Брой записи на страница
     */
    public $listItemsPerPage = 50;


    /**
     * Полета, които могат да бъдат експортирани
     */
    public $exportableCsvFields = 'date,operation,quantity,docId,date';


    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('itemId', 'key(mvc=batch_Items)', 'input=hidden,mandatory,caption=Партида');
        $this->FLD('operation', 'enum(in=Влиза, out=Излиза, stay=Стои)', 'tdClass=maxCell,smartCenter,mandatory,caption=Операция');
        $this->FLD('quantity', 'double', 'input=hidden,mandatory,caption=Количество');
        $this->FLD('docType', 'class(interface=doc_DocumentIntf)', 'caption=Документ вид');
        $this->FLD('docId', 'int', 'caption=Документ номер');
        $this->FLD('date', 'date', 'caption=Дата');
        
        $this->setDbIndex('itemId');
        $this->setDbIndex('operation');
        $this->setDbIndex('docType,docId');
    }
    
    
    /**
     * Подготовка на Детайлите
     */
    public function prepareDetail_($data)
    {
        $this->unloadPlugin('plg_SelectPeriod');
        parent::prepareDetail_($data);
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
        if (cls::load($rec->docType, true)) {
            $row->document = cls::get($rec->docType)->getLink($rec->docId, 0);
        } else {
            $row->document = "<span class='red'>" . tr('Проблем при показването') . '</span>';
        }
        
        if (isset($rec->productId)) {
            $row->productId = cat_Products::getHyperlink($rec->productId, true);
            
            if ($Definition = batch_Defs::getBatchDef($rec->productId)) {
                $row->batch = $Definition->toVerbal($rec->batch);
            }
        }
        
        if (isset($rec->storeId)) {
            $row->storeId = $rec->storeId == batch_Items::WORK_IN_PROGRESS_ID ? planning_WorkInProgress::getHyperlink() : store_Stores::getHyperlink($rec->storeId, true);
        }

        switch ($rec->operation) {
            case 'in':
                $row->ROW_ATTR['style'] = 'background-color:rgba(0, 255, 0, 0.1)';
                break;
            case 'out':
                $row->ROW_ATTR['style'] = 'background-color:rgba(255, 0, 0, 0.1)';
                break;
            case 'stay':
                $row->ROW_ATTR['style'] = 'background-color:rgba(0, 0, 255, 0.1)';
                break;
        }
    }
    
    
    /**
     * Подготовка на филтър формата
     */
    protected static function on_AfterPrepareListFilter($mvc, &$data)
    {
        if (isset($data->masterMvc) && $data->masterMvc instanceof batch_Items)  return;

        $data->listFilter->layout = new ET(tr('|*' . getFileContent('acc/plg/tpl/FilterForm.shtml')));
        $data->listFilter->FLD('batch', 'varchar(128)', 'caption=Партида,silent');
        $data->listFilter->FLD('searchType', 'enum(full=Точно съвпадение,notFull=Частично съвпадение)', 'caption=Търсене,silent');
        batch_Items::setStoreFilter($data);

        $data->listFilter->FLD('productId', 'key2(mvc=cat_Products,select=name,selectSourceArr=cat_Products::getProductOptions,allowEmpty,hasProperties=canStore,hasnotProperties=generic,maxSuggestions=100,forceAjax)', 'caption=Артикул');
        $data->listFilter->FLD('document', 'varchar(128)', 'silent,caption=Документ,placeholder=Хендлър');
        $data->listFilter->FNC('action', 'enum(all=Всички,in=Влиза, out=Излиза, stay=Стои)', 'caption=Операция,input');
        $data->listFilter->FLD('from', 'date', 'caption=От,silent');
        $data->listFilter->FLD('to', 'date', 'caption=До,silent');
        
        $showFields = arr::make('batch,searchType,productId,storeId,action,from,to,selectPeriod,document', true);
        $data->listFilter->showFields = $showFields;
        $data->listFilter->setDefault('searchType', 'full');
        if($oldestAvailableDate = plg_SelectPeriod::getOldestAvailableDate()){
            $data->listFilter->setDefault('from', $oldestAvailableDate);
        }

        if (haveRole('batch,ceo')) {
            $data->listFilter->showFields = $showFields;
        } else {
            if (Request::get('batch', 'varchar')) {
                $data->listFilter->setField('batch', 'input=hidden');
            }
            if (Request::get('productId', 'varchar')) {
                $data->listFilter->setField('productId', 'input=hidden');
            } else {
                unset($showFields['productId']);
            }
            
            $data->listFilter->showFields = implode(',', $showFields);
            Request::setProtected('batch');
        }
        
        $data->listFilter->toolbar->addSbBtn('Филтрирай', array($mvc, 'list'), 'id=filter', 'ef_icon = img/16/funnel.png');
        $data->listFilter->input(null, 'silent');
        $data->listFilter->input();
        
        $data->query->EXT('productId', 'batch_Items', 'externalName=productId,externalKey=itemId');
        $data->query->EXT('storeId', 'batch_Items', 'externalName=storeId,externalKey=itemId');
        $data->query->EXT('batch', 'batch_Items', 'externalName=batch,externalKey=itemId');
        $fields = array('RowNumb' => '№', 'storeId' => 'Склад', 'productId' => 'Артикул', 'batch' => 'Партида');
        $data->listFields = $fields + $data->listFields;
        
        if ($fRec = $data->listFilter->rec) {
            if (isset($fRec->productId)) {
                $data->query->where("#productId = {$fRec->productId}");
                unset($data->listFields['productId']);
            }
            
            if (!empty($fRec->storeId)) {
                $data->query->where("#storeId = {$fRec->storeId}");
                unset($data->listFields['storeId']);
            }
            
            if (!empty($fRec->batch)) {
                if($fRec->searchType == 'full'){
                    $data->query->where(array("#batch = '[#1#]'", $fRec->batch));
                } else {
                    $data->query->where(array("#batch LIKE '%[#1#]%'", $fRec->batch));
                }
            }
            
            if (isset($fRec->action) && $fRec->action != 'all') {
                $data->query->where("#operation = '{$fRec->action}'");
            }
            
            if (isset($fRec->from)) {
                $data->query->where("#date >= '{$fRec->from}'");
            }
            
            if (isset($fRec->to)) {
                $data->query->where("#date <= '{$fRec->to}'");
            }
            
            if (isset($fRec->document)) {
                $document = doc_Containers::getDocumentByHandle($fRec->document);
                if (is_object($document)) {
                    $data->query->where("#docType = {$document->getClassId()} AND #docId = {$document->that}");
                }
            }
        }
    }


    /**
     * Записва движение на партида от документ
     *
     * @param mixed $containerId - ид на контейнер
     *
     * @return bool - успех или не
     */
    public static function saveMovement($containerId)
    {
        // Кой е документа
        $doc = doc_Containers::getDocument($containerId);
        if ($doc->isInstanceOf('deals_DealMaster')) {
            
            // Ако е покупка/продажба трябва да има експедирано/доставено с нея
            $actions = type_Set::toArray($doc->fetchField('contoActions'));

            if (!isset($actions['ship'])) return;
        }
        
        // Какви партиди са въведени
        $jQuery = batch_BatchesInDocuments::getQuery();
        $jQuery->where("#containerId = {$containerId}");
        $jQuery->orderBy('id', 'ASC');
        $docRec = $doc->fetch();
        $totalMovements = array();

        // За всяка
        while ($jRec = $jQuery->fetch()) {
            if(Mode::is('recontoMovement')){
                if(isset($doc->valiorFld)){
                    $jRec->date = $docRec->{$doc->valiorFld};
                }
            }
            $batches = batch_Defs::getBatchArray($jRec->productId, $jRec->batch);
            $quantity = (countR($batches) == 1) ? $jRec->quantity : $jRec->quantity / countR($batches);
            
            // Записва се движението и
            foreach ($batches as $key => $b) {
                $result = true;

                $itemId = batch_Items::forceItem($jRec->productId, $key, $jRec->storeId);
                if (empty($jRec->date)) {
                    $jRec->date = $doc->fetchField($doc->valiorFld);
                    cls::get('batch_BatchesInDocuments')->save_($jRec, 'date');
                }

                $key = "{$itemId}|{$jRec->operation}";
                if (!array_key_exists($key, $totalMovements)) {
                    $mRec = (object)array('itemId' => $itemId,
                                          'operation' => $jRec->operation,
                                          'docType' => $doc->getClassId(),
                                          'docId' => $doc->that,
                                          'date' => $jRec->date,
                    );
                    $totalMovements[$key] = $mRec;
                }

                $totalMovements[$key]->quantity += $quantity;
            }
        }

        // Записване на сумарното по партиди
        foreach ($totalMovements as $mRec) {
            try {
                // Запис на движението
                $id = self::save($mRec);

                // Ако има проблем със записа, сетваме грешка
                if (!$id) {
                    $result = false;
                    break;
                }
            } catch (core_exception_Expect $e) {
                reportException($e);

                // Ако е изникнала грешка
                $result = false;
            }
        }

        // При грешка изтриваме всички записи до сега
        if ($result === false) {
            self::removeMovement($doc->getInstance(), $doc->that);
            core_Statuses::newStatus('Проблем със записването на партидите');
        }
        
        // Връщаме резултата
        return $result;
    }
    
    
    /**
     * Изтрива записите породени от документа
     *
     * @param mixed $class - ид на документ
     * @param mixed $rec   - ид или запис на документа
     *
     * @return void - изтрива движенията породени от документа
     */
    public static function removeMovement($class, $rec)
    {
        // Изтриване на записите, породени от документа
        $class = cls::get($class);
        $rec = $class->fetchRec($rec);
        
        static::delete("#docType = {$class->getClassId()} AND #docId = {$rec->id}");
    }
    
    
    /**
     * Изпълнява се след подготовката на листовия изглед
     */
    protected static function on_AfterPrepareListTitle($mvc, &$res, $data)
    {
        $data->title = 'Движения на партида|*';
        $titles = array();
        
        if ($fRec = $data->listFilter->rec) {
            if (isset($fRec->productId)) {
                $titles[] = "<b style='color:green'>" . cat_Products::getTitleById($fRec->productId) . '</b>';
            }
            
            if ($fRec->batch) {
                $titles[] = "<b style='color:green'>" . cls::get('type_Varchar')->toVerbal(str_replace('|', '/', $fRec->batch)) . '</b>';
            }
            
            if (isset($fRec->storeId)) {
                $storeName = $fRec->storeId == batch_Items::WORK_IN_PROGRESS_ID ? tr('Незавършено производство') : store_Stores::getTitleById($fRec->storeId);
                $titles[] = "<b style='color:green'>{$storeName}</b>";
            }
        }
        
        if (countR($titles)) {
            $data->title .= ' |*' . implode(' <b>,</b> ', $titles);
        }
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    protected static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if ($action == 'list') {
            
            // Ако потребителя няма определените роли, позволяваме достъп само през защитено урл
            if (!core_Users::haveRole('ceo,batch', $userId)) {
                
                // Само през защитено урл имаме достъп
                if (!Request::get('Protected')) {
                    $requiredRoles = 'no_one';
                }
            }
        }
    }
    
    
    /**
     * Връща масив с линкове към движенията на партидите
     *
     * @param int    $productId
     * @param string $batch
     *
     * @return array $batch
     */
    public static function getLinkArr($productId, $batch)
    {
        // Партидите стават линкове
        $batch = batch_Defs::getBatchArray($productId, $batch);
        if (!is_array($batch)) {
            
            return $batch;
        }
        
        foreach ($batch as $key => &$b) {
            if (!Mode::isReadOnly() && haveRole('powerUser')) {
                if (!haveRole('batch,ceo')) {
                    Request::setProtected('batch');
                }
                $b = ht::createLink($b, array('batch_Movements', 'list', 'batch' => $key, 'productId' => $productId));
            }
            
            $b = ($b instanceof core_ET) ? $b->getContent() : $b;
        }
        
        return $batch;
    }


    /**
     * След рендиране на лист таблицата
     */
    protected static function on_AfterRenderListTable($mvc, &$tpl, &$data)
    {
        if (!countR($data->recs)) return;

        // Сумиране по филтрираните артикули
        $total = array();
        $summaryQuery = clone $data->listSummary->query;
        $summaryQuery->show('productId,operation,quantity');
        while($sumRec = $summaryQuery->fetch()){
            $sign = ($sumRec->operation == 'in') ? 1 : (($sumRec->operation == 'out') ? -1 : 0);
            $total[$sumRec->productId] += $sign * $sumRec->quantity;
        }

        // Ако се показват повече от 1 нищо не се прави
        if(countR($total) != 1) return;

        // Ако е един се извличат данните на мярката му
        $filteredProductId = key($total);
        $measureId = cat_Products::fetchField($filteredProductId, 'measureId');
        $measureShortName = cat_UoM::getShortName($measureId);
        $round = cat_UoM::fetchField($measureId, 'round');

        // Показване на обобщаващ ред за единствения листван артикул
        $totalVerbal = core_Type::getByName("double(decimals={$round})")->toVerbal($total[$filteredProductId]);
        $totalVerbal = ht::styleIfNegative($totalVerbal, $total[$filteredProductId]);
        $lastRow = new ET("<tr style='text-align:right' class='state-closed'><td colspan='9'>[#caption#]: &nbsp;<b>[#total#]</b> &nbsp;[#measureShortName#]</td></tr>");
        $lastRow->replace(tr('Общо'), 'caption');
        $lastRow->replace($totalVerbal, 'total');
        $lastRow->replace($measureShortName, 'measureShortName');
        $tpl->append($lastRow, 'ROW_AFTER');
    }


    /**
     * След взимане на полетата за експорт в csv
     *
     * @see bgerp_plg_CsvExport
     */
    protected static function on_AfterGetCsvFieldSetForExport($mvc, &$fieldset)
    {
        $fieldset->setField('docId', 'caption=Документ');
        $fieldset->setFieldType('quantity', 'double');
        $fieldset->FLD('code', 'varchar', 'caption=Код,before=productId');
        $fieldset->FLD('productId', 'varchar', 'caption=Артикул,before=date');
        $fieldset->FLD('batch', 'varchar', 'caption=Партида,after=productId');
        $fieldset->FLD('storeId', 'key(mvc=store_Stores,select=name)', 'caption=Склад,after=batch');
        $fieldset->FLD('contragentId', 'varchar', 'caption=Контрагент');
        $fieldset->FLD('measureId', 'varchar', 'caption=Мярка,after=quantity');
    }


    /**
     * Преди експортиране като CSV
     *
     * @see bgerp_plg_CsvExport
     */
    protected static function on_BeforeExportCsv($mvc, &$recs)
    {
        if(!is_array($recs)) return;

        $showSign = batch_Setup::get('CSV_EXPORT_MOVEMENT_OUT_QUANTITY_SIGN');

        // Подготовка на данните за експорт
        foreach ($recs as &$rec){
            $pRec = cat_Products::fetch($rec->productId, 'code,name,nameEn,measureId');
            $Document = new core_ObjectReference($rec->docType, $rec->docId);
            $rec->docId = "#" . $Document->getHandle();
            $Cover = doc_Folders::getCover($Document->fetchField('folderId'));
            if($Cover->haveInterface('crm_ContragentAccRegIntf')){
                $rec->contragentId = $Cover->getTitleById();
            }
            $rec->productId = cat_Products::getVerbal($pRec->id, 'name');
            $rec->measureId = cat_UoM::getTitleById($pRec->measureId);
            $rec->code = cat_Products::getVerbal($pRec->id, 'code');
            if($Def = batch_Defs::getBatchDef($pRec->id)){
                if(!empty($rec->batch)){
                    Mode::push('text', 'plain');
                    $rec->batch = strip_tags($Def->toVerbal($rec->batch));
                    Mode::pop('text');
                }
            }

            if($rec->operation == 'out' && $showSign == 'withMinus'){
                $rec->quantity *= -1;
            }
        }
    }
}
