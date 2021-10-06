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
    public $listFields = 'containerId=Документ,amount=Инкасиране,zoneId=Зона,logistic=Логистична информаци,notes=@,address=@,documentHtml=@,classId=Клас';
    
    
    /**
     * Кои полета от листовия изглед да се скриват ако няма записи в тях
     *
     *  @var string
     */
    public $hideListFieldsIfEmpty = 'notes,address,documentHtml,zoneId,classId';
    
    
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
        );
    
    
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
        
        // Има ли запис за тази линия
        $rec = self::fetch("#lineId = {$lineId} AND #containerId = {$containerId}");
        
        // Ако е бил добавян към други сделки, в тях се отбелязва като премахнат
        $exQuery = self::getQuery();
        $exQuery->where("#lineId != {$lineId} AND #containerId = {$containerId} AND #status != 'removed'");
        while ($exRec = $exQuery->fetch()) {
            $exRec->status = 'removed';
            $exRec->_forceStatus = true;
            self::save($exRec, 'status');
        }
        
        // Ако няма се създава нов запис
        if (empty($rec)) {
            $rec = (object) array('lineId' => $lineId, 'containerId' => $containerId, 'classId' => $Document->getClassId());
        }
        
        self::save($rec);
        cls::get('trans_Lines')->updateMaster($rec->lineId);
        
        return $rec->id;
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

        // Линк към документа
        $row->containerId = '#' . $Document->getHandle();
        if (!core_Mode::isReadOnly()) {
            $row->containerId = $Document->getLink(0);
        }
        
        if (Mode::is('renderHtmlInLine') && isset($Document->layoutFileInLine)) {
            $row->documentHtml = $Document->getInlineDocumentBody();
        }
        
        $row->ROW_ATTR['class'] = ($rec->status == 'removed') ? 'state-removed' : "state-{$transportInfo['state']}";
        if (!empty($transportInfo['notes'])) {
            $row->notes = core_Type::getByName('richtext')->toVerbal($transportInfo['notes']);
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

        // Ако е складов документ
        if($Document->haveInterface('store_iface_DocumentIntf')){

            // Ако документа в момента е в зона
            if(isset($transportInfo['zoneId'])){
                Mode::push('shortZoneName', true);
                $zoneTitle = rack_Zones::getRecTitle($transportInfo['zoneId']);
                Mode::pop('shortZoneName');
                $zoneTitle .= " " . core_Type::getByName('percent')->toVerbal($transportInfo['readiness']);
                $row->zoneId = rack_Zones::styleZone($transportInfo['zoneId'], $zoneTitle, 'zoneMovement');
            }

            // Бутон към зоната
            if(core_Packs::isInstalled('rack') && store_Stores::getCurrent('id', false)){
                Mode::push('shortZoneName', true);
                $zoneBtn = rack_Zones::getBtnToZone($rec->containerId);
                Mode::pop();
                if (countR($zoneBtn->url)) {
                    core_RowToolbar::createIfNotExists($row->_rowTools);
                    $row->_rowTools->addLink($zoneBtn->caption, $zoneBtn->url, $zoneBtn->attr);
                }
            }

            // Подготовка на логистичната информация за документа
            $logisticArr = array();
            if(!empty($transportInfo['transportUnits'])){
                $transUnits = trans_helper::displayTransUnits($transportInfo['transportUnits']);
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
            $amountTpl = new core_ET("");
            $amountTpl->append('<div class="payment-line-amount">');
            $amountTpl->append($transportInfo['amountVerbal']);
            $amountTpl->append('</div>');
            $row->amount = $amountTpl;
        }

        if(!empty($row->address)){
            $row->address = "<div style='margin:2px;font-size:0.9em'>{$row->address}</div>";
        }

        // Бутон за създаване на коментар
        $masterRec = trans_Lines::fetch($rec->lineId);
        if ($mvc->haveRightFor('doc_Comments', (object) array('originId' => $masterRec->containerId)) && $masterRec->state != 'rejected') {
            $commentUrl = array('doc_Comments', 'add', 'originId' => $masterRec->containerId, 'detId' => $rec->id, 'ret_url' => true);
            $row->_rowTools->addLink('Известяване', $commentUrl, array('ef_icon' => 'img/16/comment_add.png', 'alwaysShow' => true, 'title' => 'Известяване на отговорниците на документа'));
        }
        
        // Бутон за изключване
        if ($mvc->haveRightFor('remove', $rec)) {
            $row->_rowTools->addLink('Изключване', array($mvc, 'remove', $rec->id, 'ret_url' => true), array('ef_icon' => 'img/16/delete.png', 'title' => 'Изключване от транспортната линия'));
        }

        // Ако има платежни документи към складовия
        if(is_array($rec->paymentsArr) ){
            $amountTpl = new core_ET("");
            foreach ($rec->paymentsArr as $p){

                // Каква е сумата на платежния документ
                $PayDoc = doc_Containers::getDocument($p->containerId);
                $paymentInfo = $PayDoc->getTransportLineInfo($rec->lineId);
                if($p->containerState == 'rejected'){
                    $paymentInfo['amountVerbal'] = "<span class='state-{$p->containerState} document-handler'>{$paymentInfo['amountVerbal']}</span>";
                }

                $paymentInfo['amountVerbal'] = ht::createLinkRef($paymentInfo['amountVerbal'], $PayDoc->getSingleUrlArray(), false, 'title=Преглед на документа');
                $amountTpl->append('<div class="payment-line-amount">');
                $amountTpl->append($paymentInfo['amountVerbal']);
                $amountTpl->append('</div>');
            }

            $row->amount = $amountTpl;
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
        $rec->_forceStatus = true;
        $this->save($rec, 'status');
        
        $Document = doc_Containers::getDocument($rec->containerId);
        $docRec = $Document->fetch();
        $docRec->lineId = null;
        $Document->getInstance()->save($docRec);
        
        return followRetUrl();
    }
    
    
    /**
     * Преди рендиране на таблицата
     */
    protected static function on_BeforeRenderListTable($mvc, &$tpl, $data)
    {
        $data->listTableMvc->FNC('payment', 'varchar', 'smartCenter');
        $data->listTableMvc->FNC('logistic', 'varchar', 'smartCenter');
        $data->listTableMvc->FNC('notes', 'varchar', 'tdClass=row-notes');
        $data->listTableMvc->FNC('zoneId', 'varchar', 'smartCenter');
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
        $form->FLD('transUnitsInput', 'table(columns=unitId|quantity,captions=ЛЕ|Брой,validate=trans_LineDetails::validateTransTable)', 'caption=Лог. ед.,after=lineNotes');
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
            $className = cls::getClassName($groupId);
            $className = tr(self::$classGroups[$className]);
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
        $paymentDocuments = array_filter($recs, function ($a) use ($paymentDocsClassIds) {return in_array($a->classId, $paymentDocsClassIds);});

        foreach ($data->recs as $rec){
            if(!in_array($rec->classId, $documentsWithPayments)) continue;

            // Към всеки документ който може да има платежен се добавят на неговия ред тези създадени към него
            $shipmentPayments = array_filter($paymentDocuments, function($a) use (&$rec){
                $PaymentDoc = doc_Containers::getDocument($a->containerId);
                $paymentRec = $PaymentDoc->fetch('originId,threadId');

                return ($paymentRec->originId == $rec->containerId);
            });

            $rec->paymentsArr = array();
            foreach ($shipmentPayments as $i => $shipPayment){
                $rec->paymentsArr[$i] = $shipPayment;
                unset($data->recs[$i]);
                unset($paymentDocuments[$i]);
            }
        }

        foreach ($data->recs as $rec1){
            if(!in_array($rec1->classId, $documentsWithPayments)) continue;

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

            foreach ($shipmentPayments as $i => $shipPayment) {
                $rec1->paymentsArr[$i] = $shipPayment;
                unset($paymentDocuments[$i]);
                unset($data->recs[$i]);
            }
        }
    }


    /**
     * Подготовка на детайла
     */
    public function prepareDetail_($data)
    {
        // Ако ще се печата разширено се пушва в определен мод
        if (Mode::is('printing') && Request::get('Width')) {
            Mode::push('renderHtmlInLine', true);
            $data->renderDocumentInLine = true;
        }
        
        parent::prepareDetail_($data);
    }
    
    
    /**
     * Рендиране на детайла
     */
    public function renderDetail_($data)
    {
        $tpl = parent::renderDetail_($data);
        if ($data->renderDocumentInLine === true) {
            Mode::pop('renderHtmlInLine');
        }
        
        return $tpl;
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
            $Document->getInstance()->save_($rec);
            doc_DocumentCache::invalidateByOriginId($rec->containerId);
        }
    }
}
