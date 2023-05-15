<?php


/**
 * Детайли на Транспортните линии
 *
 *
 * @category  bgerp
 * @package   trans
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2021 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class trans_LineDetails extends doc_Detail
{
    /**
     * Заглавие
     */
    public $title = 'Детайли на транспортните линии';
    
    
    /**
     * Наименование на единичния обект
     */
    public $singleTitle = 'Логистичен документ';
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'lineId';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, trans_Wrapper, plg_GroupByField';
    
    
    /**
     * Поле за групиране
     */
    public $groupByField = 'classId';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'containerId=Документ,amount=Инкасиране,zoneId=Зона,logistic=Лог. информация,documentHtml=@,address=@,notes=@,classId=Клас';
    
    
    /**
     * Кои полета от листовия изглед да се скриват ако няма записи в тях
     *
     *  @var string
     */
    public $hideListFieldsIfEmpty = 'notes,address,documentHtml,amount,zoneId,classId';
    
    
    /**
     * Кой може да изтрива?
     */
    public $canDelete = 'ceo,trans';
    
    
    /**
     * Кой може да премахва документа?
     */
    public $canRemove = 'ceo,trans';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'no_one';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'no_one';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'no_one';
    
    
    /**
     * Работен кеш
     */
    private static $cache = array();
    
    
    /**
     * Кои полета да се извличат при изтриване
     */
    public $fetchFieldsBeforeDelete = 'id,lineId,containerId';
    
    
    /**
     * Вербалните имена на класовете
     */
    private static $classGroups = array('store_ShipmentOrders' => 'Експедиции',
                                        'store_Receipts' => 'Доставки',
                                        'store_ConsignmentProtocols' => 'Отговорно пазене',
                                        'store_Transfers' => 'Трансфери',
                                        'cash_Pko' => 'Приходни касови ордери',
                                        'cash_Rko' => 'Разходни касови ордери',
                                        'removed' => 'Премахнати документи');
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('lineId', 'key(mvc=trans_Lines)', 'column=none,notNull,silent,hidden,mandatory');
        $this->FLD('containerId', 'key(mvc=doc_Containers)', 'column=none,notNull,silent,hidden,mandatory');
        $this->FLD('classId', 'class', 'input=none');
        $this->FLD('status', 'enum(ready=Готово,removed=Изключено)', 'input=none,notNull,value=ready,caption=Статус,smartCenter,tdClass=status-cell');
        $this->EXT('containerState', 'doc_Containers', 'externalName=state,externalKey=containerId');
        $this->EXT('containerThreadId', 'doc_Containers', 'externalName=threadId,externalKey=containerId');

        $this->FLD('createdOn', 'datetime(format=smartTime)', 'input=none');
        $this->FLD('createdBy', 'key(mvc=core_Users,select=nick)', 'input=none');
        $this->FLD('modifiedOn', 'datetime(format=smartTime)', 'input=none');
        $this->FLD('modifiedBy', 'key(mvc=core_Users,select=nick)', 'input=none');

        $this->setDbIndex('containerId,status');
        $this->setDbIndex('containerId');
        $this->setDbIndex('classId');
        $this->setDbIndex('status');
    }
    
    
    /**
     * Синхронизиране детайла на линията с документа
     *
     * @param int $lineId      - линия
     * @param int $containerId - контейнер на документ
     *
     * @return int - синхронизирания запис
     */
    public static function sync($lineId, $containerId)
    {
        $Document = doc_Containers::getDocument($containerId);
        $cu = core_Users::getCurrent();
        $now = dt::now();

        // Има ли запис за тази линия
        $rec = self::fetch("#lineId = {$lineId} AND #containerId = {$containerId}");
        
        // Ако е бил добавян към други сделки, в тях се отбелязва като премахнат
        $exQuery = self::getQuery();
        $exQuery->where("#lineId != {$lineId} AND #containerId = {$containerId} AND #status != 'removed'");
        while ($exRec = $exQuery->fetch()) {
            $exRec->status = 'removed';
            $exRec->modifiedOn = $now;
            $exRec->modifiedBy = $cu;
            self::save($exRec, 'status,modifiedOn,modifiedBy');
        }
        
        // Ако няма се създава нов запис
        if (empty($rec)) {
            $rec = (object) array('lineId' => $lineId, 'containerId' => $containerId, 'classId' => $Document->getClassId(), 'createdOn' => $now, 'createdBy' => $cu);
        }
        $rec->modifiedOn = $now;
        $rec->modifiedBy = $cu;
        $rec->status = 'ready';

        self::save($rec);
        cls::get('trans_Lines')->updateMaster($rec->lineId);
        
        return $rec->id;
    }


    /**
     * Преди подготовката на полетата за листовия изглед
     */
    protected static function on_AfterPrepareListFields($mvc, &$res, &$data)
    {
        if(Request::get('lineTab') == 'detailed'){
            $data->listFields['renderDocumentInline'] = true;
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
        // Транспортната информация за транспортната линия
        $Document = doc_Containers::getDocument($rec->containerId);
        $transportInfo = $Document->getTransportLineInfo($rec->lineId);
        core_RowToolbar::createIfNotExists($row->_rowTools);
        $lineRec = trans_Lines::fetch($rec->lineId);

        // Линк към документа
        $handle = $Document->getHandle();
        $row->containerId = "#{$handle}";
        if (!core_Mode::isReadOnly()) {
            $row->containerId = $Document->getLink(0);
            $createdBy = core_Users::getNick($Document->fetchField('createdBy'));
            $displayContainerId = $row->containerId;
            $displayContainerId .= " / {$createdBy}";

            if(!empty($lineRec->activatedOn) && $rec->createdOn >= $lineRec->activatedOn){
                $createdVerbal = dt::mysql2verbal($rec->createdOn);
                $displayContainerId .= " / <b style='color:red;'>" . tr('Добавен') . ": {$createdVerbal}</b>";
            }

            $row->containerId = "<span class='state-{$rec->containerState} document-handler' id='$handle'>{$displayContainerId}</span>";
        }

        $tags = tags_Logs::getTagsFor($Document->getClassId(), $Document->that);
        if(count($tags)){
            $tagsStr = '';
            array_walk($tags, function($a) use (&$tagsStr){$tagsStr  .= $a['span'];});
            $row->containerId .= "<span class='documentTags'>{$tagsStr}</span>";
        }

        if (isset($fields['renderDocumentInline']) && isset($Document->layoutFileInLine)) {
            if($rec->containerState != 'rejected' && $rec->status != 'removed'){
                Mode::push('noBlank', true);
                Mode::push('renderHtmlInLine', true);
                $row->documentHtml = $Document->getInlineDocumentBody('xhtml');
                Mode::pop('renderHtmlInLine');
                Mode::pop('noBlank');
            }
        }

        if (!empty($transportInfo['notes'])) {
            $row->notes = core_Type::getByName('richtext')->toVerbal($transportInfo['notes']);
            $row->notes = "<div class='notes{$rec->id}'>{$row->notes}</div>";
        }

        if($Document->isInstanceOf('store_ShipmentOrders')){
            $invoicesInShipment = deals_InvoicesToDocuments::getInvoiceArr($rec->containerId);
            if(countR($invoicesInShipment)){
                $invoiceArr = array();
                foreach ($invoicesInShipment as $iRec){
                    $invoiceArr[] = doc_Containers::getDocument($iRec->containerId)->getLink(0)->getContent();
                }
                $row->notes .= implode('|', $invoiceArr);
            }
        }


        if (!empty($transportInfo['address'])) {
            $row->address = core_Type::getByName('varchar')->toVerbal($transportInfo['address']);
        }

        // Визуализиране на движението на складовете
        if (!empty($transportInfo['stores'])) {
            if (countR($transportInfo['stores']) == 1) {
                $row->storeId = store_Stores::getHyperlink($transportInfo['stores'][0]);
                if($transportInfo['storeMovement'] == 'both'){
                    $row->storeId .= " &#8660; ";
                } else {
                    $symbol = ($transportInfo['storeMovement'] == 'in') ? '&#8656;' : '&#8658;';
                    $row->storeId .= " {$symbol}";
                }
            } else {
                $row->storeId = store_Stores::getHyperlink($transportInfo['stores'][0]) . ' &#8658; ' . store_Stores::getHyperlink($transportInfo['stores'][1]);
            }

            $row->address = "{$row->storeId} {$transportInfo['contragentName']}" . (!empty($row->address) ? ", {$row->address}" : '');
        }
        $row->address = rtrim($row->address, ' ,');
        $row->address = rtrim($row->address, ', ');

        if(isset($transportInfo['locationId']) && !core_Mode::isReadOnly()){
            if(crm_Locations::haveRightFor('single', $transportInfo['locationId'])){
                $row->address = ht::createLinkRef($row->address, crm_Locations::getSingleUrlArray($transportInfo['locationId']), false, 'title=Преглед на локацията');
            }
        }

        if(!empty($transportInfo['addressInfo'])){
            $row->address .= ", " . core_Type::getByName('richtext')->toVerbal($transportInfo['addressInfo']);
        }
        $row->address = str_replace(', <div', '<div', $row->address);

        // Ако е складов документ
        if($Document->haveInterface('store_iface_DocumentIntf')){

            // Ако документа в момента е в зона
            if(isset($transportInfo['zoneId']) && $rec->status != 'removed'){
                $readiness = core_Type::getByName('percent(decimals=0)')->toVerbal($transportInfo['readiness']);
                if(!Mode::isReadOnly()){
                    $readiness = "<div class='block-readiness lineShow'>{$readiness}</div>";
                }
                $row->zoneId = "{$readiness} " . rack_Zones::getDisplayZone($transportInfo['zoneId']);
            }

            // Подготовка на логистичната информация за документа
            $logisticArr = array();
            if(!empty($transportInfo['transportUnits'])){
                $transUnits = trans_helper::displayTransUnits($transportInfo['transportUnits'], false, '<br>');
                $logisticArr[] = $transUnits;
            } elseif(isset($transportInfo['volume'])){
                $logisticArr[] = core_Type::getByName('cat_type_Volume')->toVerbal($transportInfo['volume']);
            }

            if(isset($transportInfo['weight'])){
                $logisticArr[] = core_Type::getByName('cat_type_Weight')->toVerbal($transportInfo['weight']);
            } else {
                $logisticArr[] = "<span class='quiet'>N/A</span>";
            }
            $row->logistic = implode(', ', $logisticArr);
        } else {
            if(!empty($transportInfo['contragentName'])){
                $row->address = "<span style='margin:2px'>" . $transportInfo['contragentName'] . "</span>";
            }

            if($rec->status != 'removed'){
                $amountTpl = new core_ET("");
                $amountTpl->append('<div class="payment-line-amount">');
                $amountTpl->append($transportInfo['amountVerbal']);
                $amountTpl->append('</div>');
                $row->amount = $amountTpl;
            }
        }

        if(!empty($row->address)){
            $row->address = "<div style='margin:2px;font-size:0.9em'>{$row->address}</div>";
        }

        // Бутон за създаване на коментар
        $masterRec = trans_Lines::fetch($rec->lineId);
        if (doc_Comments::haveRightFor('add', (object) array('originId' => $masterRec->containerId)) && $masterRec->state != 'rejected') {
            $commentUrl = array('doc_Comments', 'add', 'originId' => $masterRec->containerId, 'detId' => $rec->id, 'ret_url' => true);
            $row->_rowTools->addLink('Известяване', $commentUrl, array('ef_icon' => 'img/16/comment_add.png', 'alwaysShow' => true, 'title' => 'Известяване на отговорниците на документа'));
        }
        
        // Бутон за изключване
        if ($mvc->haveRightFor('remove', $rec)) {
            $row->_rowTools->addLink('Премахване', array($mvc, 'remove', $rec->id, 'ret_url' => true), array('ef_icon' => 'img/16/gray-close.png', 'title' => 'Премахване на документа от транспортната линия'));
        }

        if(!Mode::isReadOnly() && !empty($row->notes)){
            $row->logistic .= "&nbsp; <a id= 'btn{$rec->id}' href=\"javascript:toggleDisplayByClass('btn{$rec->id}','notes{$rec->id}', 'true')\"  style=\"background-image:url(" . sbf('img/16/toggle1.png', "'") . ');" class=" plus-icon more-btn show-btn", title="' . tr('Допълнителна информация за транспорта') . "\"</a>";
        }

        if ($Document->haveRightFor('changeline') && (!Mode::is('printing') && !Mode::is('xhtml')) && $rec->status != 'removed') {
            $lineThreadId = trans_Lines::fetchField($rec->lineId, 'threadId');
            $retUrl = (trans_Lines::haveRightFor('single', $rec->lineId)) ? array('doc_Containers', 'list', 'threadId' => $lineThreadId, 'docId' => trans_Lines::getHandle($rec->lineId), "#" => $handle) : true;
            if (!Mode::is('screenMode', 'narrow')){
                $row->logistic .= "&nbsp; " . ht::createLink('', array($Document->getInstance(), 'changeline', $Document->that, 'ret_url' => $retUrl), false, 'ef_icon=img/16/lorry_go.png, title = Промяна на транспортната информация');
            } else {
                $row->_rowTools->addLink('Транспорт', array($Document->getInstance(), 'changeline', $Document->that, 'ret_url' => true), array('ef_icon' => 'img/16/lorry_go.png', 'title' => 'Промяна на транспортната информация'));
            }
        }

        if(!empty($transportInfo['features'])){
            $featuresString = '';
            foreach ($transportInfo['features'] as $transFeatureId){
                $featuresString .= "<span class='lineFeature'>" . trans_Features::getVerbal($transFeatureId, 'name') . "</span>";
            }
            $row->containerId .= " {$featuresString}";
        }

        // Ако има платежни документи към складовия
        if(is_array($rec->paymentsArr) && $rec->status != 'removed'){
            $rec->_allPaymentActive = (bool)countR($rec->paymentsArr);
            $amountTpl = new core_ET("");
            foreach ($rec->paymentsArr as $p){

                // Каква е сумата на платежния документ
                $PayDoc = doc_Containers::getDocument($p->containerId);
                $paymentInfo = $PayDoc->getTransportLineInfo($rec->lineId);
                if($paymentInfo['state'] != 'active') {
                    $rec->_allPaymentActive = false;
                }
                if($p->containerState == 'rejected'){
                    $paymentInfo['amountVerbal'] = "<span class='state-{$p->containerState} document-handler'>{$paymentInfo['amountVerbal']}</span>";
                }

                Mode::push('text', 'plain');
                $paymentCaption = "#" . $PayDoc->getHandle() . " (" . core_Type::getByName('double(decimals=2)')->toVerbal($paymentInfo['amount']) . ")";
                Mode::pop('text');
                $row->_rowTools->addLink($paymentCaption, $PayDoc->getSingleUrlArray(), array('ef_icon' => $PayDoc->singleIcon, 'title' => 'Преглед на документа'));

                $amountTpl->append('<div class="payment-line-amount">');
                $amountTpl->append($paymentInfo['amountVerbal']);
                $amountTpl->append('</div>');
            }

            if(countR($rec->paymentsArr)){
                $row->amount = $amountTpl;
            }
        }

        // В какъв цвят да се оцвети реда на линията
        if($Document->haveInterface('store_iface_DocumentIntf')){
            $class = (in_array($transportInfo['state'], array('active', 'rejected '))) ? $transportInfo['state'] : 'waiting';
            if($rec->_allPaymentActive && $class == 'active'){
                $class = 'closed';
            }
        } else {
            $class = (in_array($transportInfo['state'], array('active', 'rejected '))) ? 'closed' : 'waiting';
        }

        $row->ROW_ATTR['class'] = ($rec->status == 'removed') ? 'state-removed' : "state-{$class}";
        $row->ROW_ATTR['class'] .= " group{$rec->classId}";
        if($fields['renderDocumentInline']){
            $row->ROW_ATTR['class'] .= " detailedView";
        }
    }
    
    
    /**
     * Екшън за премахване на документ
     */
    public function act_Remove()
    {
        $this->requireRightFor('remove');
        expect($id = Request::get('id', 'int'));
        expect($rec = self::fetch($id));
        $this->requireRightFor('remove', $rec);
        
        $rec->status = 'removed';
        $this->save($rec, 'status');

        $Document = doc_Containers::getDocument($rec->containerId);
        $docRec = $Document->fetch();
        $docRec->lineId = null;
        $Document->getInstance()->save($docRec, 'lineId');

        return followRetUrl();
    }
    
    
    /**
     * Преди рендиране на таблицата
     */
    protected static function on_BeforeRenderListTable($mvc, &$tpl, $data)
    {
        unset($data->listFields['renderDocumentInline']);

        $data->listTableMvc->setField('containerId', 'tdClass=documentCol');
        $data->listTableMvc->FNC('logistic', 'varchar', 'smartCenter,tdClass=small-field logisticCol');
        $data->listTableMvc->FNC('notes', 'varchar', 'tdClass=row-notes');
        $data->listTableMvc->FNC('zoneId', 'varchar', 'smartCenter,tdClass=small-field');
        $data->listTableMvc->FNC('documentHtml', 'varchar', 'tdClass=documentHtml');

        if($data->masterData->rec->state == 'rejected'){
            unset($data->listFields['_rowTools']);
        }
    }


    /**
     * Добавя след таблицата
     */
    protected static function on_AfterRenderListTable($mvc, &$tpl, $data)
    {
        if(!Mode::is('printing') && !Mode::is('xhtml')){
            $tabs = cls::get('core_Tabs', array('htmlClass' => 'deal-history-tab', 'urlParam' => 'lineTab'));

            // Подготовка на табовете
            $url = getCurrentUrl();
            unset($url['lineTab']);
            $tabs->TAB('List', 'Списък', $url);
            $url['lineTab'] = 'detailed';
            $tabs->TAB('Detailed', 'Подробно', $url);
            $selected = (Request::get('lineTab') == 'detailed') ? 'Detailed' : 'List';
            $tabHtml = $tabs->renderHtml('', $selected);

            $tpl->prepend($tabHtml);
        }
    }


    /**
     * Подготвя формата за добавяне на ЛЕ
     *
     * @param core_Form $form  - форма
     * @param mixed     $value - дефолтна стойност
     */
    public static function setTransUnitField(&$form, $value)
    {
        $form->setDefault('transUnitsInput', $value);
        $units = trans_TransportUnits::getAll();
        $form->FLD('transUnitsInput', 'table(columns=unitId|quantity,captions=Вид|Брой,validate=trans_LineDetails::validateTransTable)', 'caption=Логистична информация->Лог. ед.,after=lineNotes');
        $form->setFieldTypeParams('transUnitsInput', array('unitId_opt' => array('' => '') + $units));
    }
    
    
    /**
     * Валидиране на таблица с транспортни линии
     *
     * @param array     $tableData
     * @param core_Type $Type
     *
     * @return array
     */
    public static function validateTransTable($tableData, $Type)
    {
        $res = array();
        $units = $tableData['unitId'];
        $quantities = $tableData['quantity'];
        $error = $errorFields = array();
        
        if (countR($units) != countR(array_unique($units))) {
            $error[] = 'Логистичните единици трябва да са уникални|*';
        }
        
        $unitKeys = array_keys($units);
        foreach ($unitKeys as $k) {
            if (!isset($quantities[$k])) {
                $error[] = 'Попълнена ЛЕ без да има количество|*';
                $errorFields['quantity'][$k] = 'Попълнена ЛЕ без да има количество|*';
                $errorFields['unitId'][$k] = 'Попълнена ЛЕ без да има количество|*';
            }
        }
        
        foreach ($quantities as $k1 => $q1) {
            if (empty($units[$k1])) {
                $error[] = 'Попълнено количество без да има ЛЕ|*';
                $errorFields['quantity'][$k1] = 'Попълнено количество без да има ЛЕ|*';
                $errorFields['unitId'][$k1] = 'Попълнено количество без да има ЛЕ|*';
            }
            
            if (empty($errorFields['quantity'][$k1])) {
                if (!type_Int::isInt($q1) || $q1 < 0) {
                    $error[] = 'Не е въведено цяло положително число|*';
                    $errorFields['quantity'][$k1] = 'Не е въведено цяло положително число|*';
                    $errorFields['unitId'][$k1] = 'Не е въведено цяло положително число|*';
                }
            }
        }
        
        if (countR($error)) {
            $error = implode('<li>', $error);
            $res['error'] = $error;
        }
        
        if (countR($errorFields)) {
            $res['errorFields'] = $errorFields;
        }
        
        return $res;
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if ($action == 'remove' && isset($rec)) {
            $state = trans_Lines::fetchField($rec->lineId, 'state');
            if (in_array($state, array('rejected', 'closed', 'draft', 'pending')) || $rec->status == 'removed') {
                $requiredRoles = 'no_one';
            }
        }
        
        if ($action == 'delete' && isset($rec)) {
            $state = trans_Lines::fetchField($rec->lineId, 'state');
            if (in_array($state, array('rejected', 'closed', 'active')) || $rec->status == 'removed') {
                $requiredRoles = 'no_one';
            }
        }
    }


    /**
     * С какво име ще се показва групата
     */
    public function renderGroupName($data, $groupId, $groupVerbal)
    {
        // Към коя група спада документа
        if (!array_key_exists($groupId, self::$cache)) {
            $className = ($groupId == 'removed') ? 'removed' : cls::getClassName($groupId);
            $className = tr(self::$classGroups[$className]);

            if(!Mode::isReadOnly() && $groupId == 'removed'){
                $className .= " <a id= 'groupBtn{$groupId}' href=\"javascript:toggleDisplayByClass('groupBtn{$groupId}','group{$groupId}')\"  style=\"background-image:url(" . sbf('img/16/toggle1.png', "'") . ');" class=" plus-icon more-btn"> </a>';
            }
            self::$cache[$groupId] = $className;
        }

        return self::$cache[$groupId];
    }
    
    
    /**
     * Преди извличане на записите от БД
     */
    protected static function on_BeforePrepareListRecs($mvc, &$res, $data)
    {
        $shipClassId = store_ShipmentOrders::getClassId();
        $receiptClassId = store_Receipts::getClassId();
        $transferClassId = store_Transfers::getClassId();
        $consClassId = store_ConsignmentProtocols::getClassId();
        $pkoClassId = cash_Pko::getClassId();
        $rkoClassId = cash_Rko::getClassId();
        
        $data->query->XPR('orderByClassId', 'int', "(CASE #classId WHEN {$shipClassId} THEN 1 WHEN {$receiptClassId} THEN 2 WHEN {$transferClassId} THEN 3 WHEN {$consClassId} THEN 4 WHEN {$pkoClassId} THEN 5 WHEN {$rkoClassId} THEN 6 ELSE 7 END)");
        $data->query->orderBy('#orderByClassId=ASC,#containerId=ASC');

        if(Mode::is('printing')){
            $data->query->where("#status != 'removed'");
        }
    }


    /**
     * След извличане на записите от базата данни
     */
    protected static function on_AfterPrepareListRecs(core_Mvc $mvc, $data)
    {
        $recs = $data->recs;
        if(!countR($recs)) return;

        // Кои документи са платежни и кои документи могат да имат такива към тях
        $paymentDocsClassIds = array(cash_Pko::getClassId(), cash_Rko::getClassId());
        $documentsWithPayments = array(store_ShipmentOrders::getClassId(), store_Receipts::getClassId());
        $paymentDocuments = array_filter($recs, function ($a) use ($paymentDocsClassIds) {return in_array($a->classId, $paymentDocsClassIds) && ($a->status != 'removed');});

        $removedRecs = array();
        foreach ($data->recs as $rec){
            if($rec->status == 'removed'){
                $rec->classId = 'removed';
                $removedRecs[$rec->id] = $rec;
                unset($data->recs[$rec->id]);
                continue;
            }

            if(!in_array($rec->classId, $documentsWithPayments)) continue;

            // Към всеки документ който може да има платежен се добавят на неговия ред тези създадени към него
            $shipmentPayments = array_filter($paymentDocuments, function($a) use (&$rec){
                $PaymentDoc = doc_Containers::getDocument($a->containerId);
                $paymentRec = $PaymentDoc->fetch('originId,threadId');

                return ($paymentRec->originId == $rec->containerId);
            });

            // Премахване на платежните документи, закачени към Складов документ от последващо показване в линията
            $rec->paymentsArr = array();
            foreach ($shipmentPayments as $i => $shipPayment){
                $rec->paymentsArr[$i] = $shipPayment;
                unset($data->recs[$i]);
                unset($paymentDocuments[$i]);
            }
        }

        foreach ($data->recs as $rec1){
            if(!in_array($rec1->classId, $documentsWithPayments) || $rec1->status == 'removed') continue;

            // При второто обикаляне, гледа се от останалите платежни, които не са към конкретен документ
            // има ли такива към някоя от нишките, ако има се добавя към първия документ от нея
            $shipmentPayments = array_filter($paymentDocuments, function($a) use (&$rec1){
                $PaymentDoc = doc_Containers::getDocument($a->containerId);
                $paymentRec = $PaymentDoc->fetch('originId,threadId');

                return ($paymentRec->threadId == $rec1->containerThreadId);
            });

            if(!is_array($rec1->paymentsArr)){
                $rec1->paymentsArr = array();
            }

            // Премахване на платежните документи, закачени към Складов документ от последващо показване в линията
            foreach ($shipmentPayments as $i => $shipPayment) {
                $rec1->paymentsArr[$i] = $shipPayment;
                unset($paymentDocuments[$i]);
                unset($data->recs[$i]);
            }
        }

        if(countR($removedRecs)){
            $data->recs += $removedRecs;
        }
    }
    
    
    /**
     * След изтриване на запис
     */
    public static function on_AfterDelete($mvc, &$numDelRows, $query, $cond)
    {
        foreach ($query->getDeletedRecs() as $rec) {
            $Document = doc_Containers::getDocument($rec->containerId);
            
            // Изтриване от документа че е към тази линия
            $rec = $Document->fetch();
            $rec->lineId = null;
            $Document->getInstance()->save_($rec, 'lineId,modifiedOn,modifiedBy');
            doc_DocumentCache::invalidateByOriginId($rec->containerId);
        }
    }
}
