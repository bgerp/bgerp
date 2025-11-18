<?php


/**
 * Абстрактен клас за наследяване от класове сделки
 *
 *
 * @category  bgerp
 * @package   deals
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2022 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
abstract class deals_DealBase extends core_Master
{
    /**
     * Работен кеш
     */
    protected $historyCache = array();
    
    
    /**
     * Колко записи от журнала да се показват от историята
     */
    protected $historyItemsPerPage = 6;
    
    
    /**
     * Колко записи от репорта да се показват от отчета
     */
    protected $reportItemsPerPage = 10;
    
    
    /**
     * Колко записи от репорта да се показват от отчета
     * в csv-то
     */
    protected $csvReportItemsPerPage = 1000;
    
    
    /**
     * Документа продажба може да бъде само начало на нишка
     */
    public $onlyFirstInThread = true;
    
    
    /**
     * В коя номенклатура да се вкара след активиране
     */
    public $addToListOnActivation = 'deals';
    
    
    /**
     * Кой има права да експортира
     */
    public $canExport = 'powerUser';
    
    
    /**
     * Кой може да обединява сделките
     */
    public $canClosewith = 'ceo,dealJoin';
    
    
    /**
     * Дали в листовия изглед да се показва бутона за добавяне
     */
    public $listAddBtn = false;


    /**
     * Извиква се след описанието на модела
     *
     * @param core_Mvc $mvc
     */
    public static function on_AfterDescription(core_Master &$mvc)
    {
        if (empty($mvc->fields['closedDocuments'])) {
            $mvc->FLD('closedDocuments', "keylist(mvc={$mvc->className})", 'input=none,notNull');
        }
        $mvc->FLD('closedOn', 'datetime', 'input=none');
        $mvc->FLD('closeWith', "key(mvc={$mvc->className},allowEmpty)", 'caption=Приключена със,input=none');
    }
    
    
    /**
     * Функция, която се извиква след активирането на документа
     */
    public static function on_AfterActivation($mvc, &$rec)
    {
        $rec = $mvc->fetchRec($rec);
        
        if ($rec->state == 'active') {
            $Cover = doc_Folders::getCover($rec->folderId);
            
            if ($Cover->haveInterface('crm_ContragentAccRegIntf')) {
                
                // Добавяме контрагента като перо, ако не е
                $listId = acc_Lists::fetchBySystemId('contractors')->id;
                acc_Items::force($Cover->getClassId(), $Cover->that, $listId);
            }
        }
    }
    
    
    /**
     * Имплементация на @link bgerp_DealAggregatorIntf::getAggregateDealInfo()
     * Генерира агрегираната бизнес информация за тази сделка
     *
     * Обикаля всички документи, имащи отношение към бизнес информацията и извлича от всеки един
     * неговата "порция" бизнес информация. Всяка порция се натрупва към общия резултат до
     * момента.
     *
     * Списъка с въпросните документи, имащи отношение към бизнес информацията за продажбата е
     * сечението на следните множества:
     *
     *  * Документите, върнати от @link doc_DocumentIntf::getDescendants()
     *  * Документите, реализиращи интерфейса @link bgerp_DealIntf
     *  * Документите, в състояние различно от `draft` и `rejected`
     *
     * @return bgerp_iface_DealAggregator
     */
    public function getAggregateDealInfo($id)
    {
        $dealRec = $this->fetchRec($id);

        // Извличаме dealInfo от самата сделка
        $aggregateInfo = new bgerp_iface_DealAggregator;
        $this->pushDealInfo($dealRec->id, $aggregateInfo);

        if(Mode::is('onlySimpleDealInfo')) return $aggregateInfo;

        $dealDocuments = $this->getDescendants($dealRec->id);
        if (!empty($dealRec->closedDocuments)) {
            $combinedThreads = deals_Helper::getCombinedThreads($dealRec->threadId);
            unset($combinedThreads[$dealRec->threadId]);

            $iQuery = doc_Containers::getQuery();
            $iQuery->in('threadId', $combinedThreads);
            $iQuery->in('docClass', array(sales_Invoices::getClassId(), purchase_Invoices::getClassId()));
            $iQuery->where("#state = 'active'");
            while($iRec = $iQuery->fetch()){
                if(!array_key_exists($iRec->id, $dealDocuments)){
                    $dealDocuments[$iRec->id] = doc_Containers::getDocument($iRec->id);
                }
            }
        }

        foreach ($dealDocuments as $d) {
            $dState = $d->rec('state');
            if ($dState == 'draft' || $dState == 'rejected') continue;

            if ($d->haveInterface('bgerp_DealIntf')) {
                try {
                    $d->getInstance()->pushDealInfo($d->that, $aggregateInfo);
                } catch (core_exception_Expect $e) {
                    reportException($e);
                }
            }
        }

        return $aggregateInfo;
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     */
    public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = null, $userId = null)
    {
        if ($res == 'no_one') {
            
            return;
        }
        
        // Ако няма документи с които може да се затвори или е чернова не може да се приключи с друга сделка
        if ($action == 'closewith' && isset($rec)) {
            if (($rec->state != 'draft' && $rec->state != 'pending' && $rec->state != 'active') || (empty($rec->closedDocuments) && $rec->state == 'active')) {
                $res = 'no_one';
            } else {
                $options = $mvc->getDealsToCloseWith($rec);
                if(!countR($options)){
                    $res = 'no_one';
                }
            }
        }
        
        // Ако документа е активен, може да се експортва
        if ($action == 'export' && isset($rec)) {
            $state = (!isset($rec->state)) ? $mvc->fetchField($rec->id, 'state') : $rec->state;
            if ($state != 'active') {
                $res = 'no_one';
            }
        }
        
        // Ако някой от документите в нишката има контировка, сделката не мжое да се затваря
        if ($action == 'close' && isset($rec)) {
            $cQuery = doc_Containers::getQuery();
            $cQuery->where("#threadId = {$rec->threadId} AND #state = 'active'");
            $cQuery->show('docClass,docId');
            
            $where = '';
            while($cRec = $cQuery->fetch()){
                $where .= (empty($where) ? '' : ' OR ') . "(#docType = {$cRec->docClass} AND #docId = {$cRec->docId})";
            }

            // Ако има активен приключващ документ, да не може да се затваря/отваря от бутона
            if(isset($mvc->closeDealDoc)){
                if(cls::get($mvc->closeDealDoc)->fetch("#threadId = {$rec->threadId} AND #state = 'active'")){
                    $res = 'no_one';
                }
            }

            if(!empty($where) && acc_Journal::fetch($where, 'id')){
                $res = 'no_one';
            }
        }
        
        if ($action == 'changerate' && isset($rec)) {
            if ($rec->currencyId == 'BGN') {
                $res = 'no_one';
            } elseif ($rec->state == 'closed' || $rec->state == 'rejected') {
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
    public static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
        $rec = &$data->rec;
        
        if ($mvc->haveRightFor('closeWith', $rec)) {
            $attr = arr::make("id=btnCloseWith{$rec->containerId},ef_icon = img/16/tick-circle-frame.png,title=Обединяване на сделката с други сделки");
            if($rec->state == 'active'){
                $attr['row'] = 2;
            }
            $data->toolbar->addBtn('Обединяване', array($mvc, 'closeWith', $rec->id), $attr);
        }
        
        if ($mvc->haveRightFor('changerate', $rec)) {
            $data->toolbar->addBtn('Промяна на курса', array($mvc, 'changeRate', $rec->id, 'ret_url' => true), 'id=changeRateBtn,row=2', 'ef_icon = img/16/bug.png,title=Преизчисляване на курса на документите в нишката');
        }
    }
    
    
    /**
     * Кои сделки ще могатд а се приключат с документа
     *
     * @param object $rec
     *
     * @return array $options - опции
     */
    public function getDealsToCloseWith_($rec)
    {
        // Избираме всички други активни сделки от същия тип и валута, като началния документ в същата папка
        $docs = array();
        $dealQuery = $this->getQuery();
        $dealQuery->where("#id != {$rec->id}");
        $dealQuery->where("#folderId = {$rec->folderId}");
        if($rec->currencyId == 'EUR'){
            $dealQuery->in("currencyId", array('BGN', 'EUR'));
        } else {
            $dealQuery->where("#currencyId = '{$rec->currencyId}'");
        }

        if($this->getField('deliveryTermId', false)){
            if(isset($rec->deliveryTermId)){
                $dealQuery->where("#deliveryTermId = '{$rec->deliveryTermId}'");
            } else {
                $dealQuery->where("#deliveryTermId IS NULL");
            }
        }
        $dealQuery->where("#state = 'active'");
        $dealQuery->where("#closedDocuments = ''");
        $dealQuery->orderBy('id', 'ASC');

        while ($dealRec = $dealQuery->fetch()) {
            $title = $this->getRecTitle($dealRec, false) . ' / ' . (($this->valiorFld) ? $this->getVerbal($dealRec, $this->valiorFld) : '');
            $docs[$dealRec->id] = $title;
        }
        
        return $docs;
    }
    
    
    /**
     * Преди да се проверят имали приключени пера в транзакцията
     *
     * Обхождат се всички документи в треда и ако един има приключено перо, документа начало на нишка
     * не може да се оттегля/възстановява/контира
     */
    public static function on_BeforeGetClosedItemsInTransaction($mvc, &$res, $id)
    {
        $closedItems = array();
        $rec = $mvc->fetchRec($id);
        $dealItem = acc_Items::fetchItem($mvc->getClassId(), $rec->id);
        
        // Записите от журнала засягащи това перо
        $entries = acc_Journal::getEntries(array($mvc, $rec->id));

        // Към тях добавяме и самия документ
        $entries[] = (object) array('docType' => $mvc->getClassId(), 'docId' => $rec->id);
        
        $entries1 = array();
        foreach ($entries as $ent) {
            $index = $ent->docType . '|' . $ent->docId;
            if (!isset($entries1[$index])) {
                $entries1[$index] = $ent;
            }
        }
        
        // За всеки запис
        foreach ($entries1 as $ent) {
            
            // Ако има метод 'getValidatedTransaction'
            $Doc = cls::get($ent->docType);
            
            // Ако транзакцията е направена от друг тред запомняме от кой документ е направена
            $threadId = $Doc->fetchField($ent->docId, 'threadId');
            if ($threadId != $rec->threadId) {
                $mvc->usedIn[$dealItem->id][] = $Doc->getHandle($ent->docId);
            }
            
            if (cls::existsMethod($Doc, 'getValidatedTransaction')) {
                
                // Ако има валидна транзакция, проверяваме дали има затворени пера
                $transaction = $Doc->getValidatedTransaction($ent->docId);
                
                if ($transaction) {
                    // Добавяме всички приключени пера
                    $closedItems += $transaction->getClosedItems();
                }
            }
        }
        
        if ($rec->state != 'closed') {
            unset($closedItems[$dealItem->id]);
        }
        
        // Връщаме намерените пера
        $res = $closedItems;
    }
    
    
    /**
     * Екшън за приключване на сделка с друга сделка
     */
    public function act_Closewith()
    {
        $this->requireRightFor('closewith');
        $id = Request::get('id', 'int');
        expect($rec = $this->fetch($id));

        // Трябва потребителя да може да контира
        $this->requireRightFor('closewith', $rec);
        $originalState = $rec->state;
        $options = $this->getDealsToCloseWith($rec);

        // Подготовка на формата за избор на опция
        $title = ($rec->state == 'active') ? tr('Обединяване към') : tr('Активиране на');
        $form = cls::get('core_Form');
        $form->title = "|*{$title} <b>" . $this->getFormTitleLink($id). '</b>' . ' ?';
        $form->info = 'Посочете кои сделки желаете да обедините с тази сделка';
        $form->FLD('closeWith', "keylist(mvc={$this->className})", 'caption=Приключи и,column=1,mandatory');
        $form->FLD('rate', "double(decimals=5)", 'caption=Общ курс,input=hidden');
        $form->setDefault('rate', currency_CurrencyRates::getRate($rec->valior, $rec->currencyId, null));
        $form->setSuggestions('closeWith', $options);
        $form->input();
        
        // След като формата се изпрати
        if ($form->isSubmitted()) {
            if($this instanceof deals_DealMaster){
                if($this->setErrorIfDeliveryTimeIsNotSet($rec)){
                    $form->setError('closeWith', 'Преди активирането, трябва задължително да е посочено време/дата на доставка');
                }
            }

            $err = $closedDeals = $threads = $warning = array();
            $warning[$rec->currencyRate] = $rec->currencyRate;
            $deals1 = keylist::toArray($form->rec->closeWith);
            $CloseDoc = cls::get($this->closeDealDoc);

            $dealCountries = array();
            foreach ($deals1 as $d1) {
                $dealRec = $this->fetch($d1, 'threadId,currencyRate');
                $dealItemRec = acc_Items::fetchItem($this, $dealRec->id);
                $exClosedDoc = $CloseDoc->fetch("#threadId = {$dealRec->threadId} AND #state = 'active'");

                $logisticData = $this->getLogisticData($d1);
                if(isset($logisticData['toCountry'])){
                    $toCountryId = drdata_Countries::getIdByName($logisticData['toCountry']);
                    $dealCountries[$toCountryId][] = $d1;
                }
                if (acc_plg_Contable::haveDocumentInThreadWithStates($dealRec->threadId, 'pending,draft')) {
                    $err[] = $this->getLink($d1, 0);
                }

                if($dealItemRec->state == 'closed' || $exClosedDoc){
                    $closedDeals[] = $this->getLink($dealRec->id, 0);
                }
                $warning[$dealRec->currencyRate] = $dealRec->currencyRate;
                $threads[$dealRec->threadId] = $dealRec->threadId;
            }

            if (countR($err)) {
                $msg = '|В следните ' . mb_strtolower($this->title) . ' има документи в заявка и/или чернова|*: ' . implode(',', $err);
                $form->setError('closeWith', $msg);
            }

            if (countR($closedDeals)) {
                $msg = '|Следните ' . mb_strtolower($this->title) . ' са вече затворени|*: ' . implode(',', $closedDeals);
                $form->setError('closeWith', $msg);
            }

            $countryWarningMsg = array();
            $logisticData = $this->getLogisticData($rec->id);
            if(countR($dealCountries)){
                $toCountryId = drdata_Countries::getIdByName($logisticData['toCountry']);
                if(isset($toCountryId)){
                    $dealList = array();
                    $diffCountries = array_diff_key($dealCountries, array($toCountryId => $toCountryId));

                    if(countR($diffCountries)){
                        foreach ($diffCountries as $dArr){
                            foreach ($dArr as $d1){
                                $dealList[] = "#" . $this->getHandle($d1);
                            }
                        }
                        $countryWarningMsg = "Държавата на доставка в обединяващия договор е различна от държавата на доставка в|*: " . implode(', ', $dealList);
                    }
                } else {
                    $countryWarningMsg = "Обединяват се договори с избрана държава на доставка в договор без посочена такава|*!";
                }
            }

            if(!empty($countryWarningMsg)){
                $form->setWarning('closeWith', $countryWarningMsg);
            }

            if (countR($warning) != 1) {
                $form->rec->_recalRate = true;
                $form->setWarning('closeWith,rate', 'Всички избрани договори ще бъдат преизчислени по курса на новия договор (при необходимост - въведете ръчно друг курс)');
                $form->setField('rate', 'input');
                if($rec->state == 'active'){
                    $form->setReadOnly('rate');
                }
            }

            if (!$form->gotErrors()) {
                $formRec = $form->rec;
                setIfNot($rec->valior, dt::today());

                // Ако ще има преизчисляване на курс
                $errorArr = array();
                $deals = keylist::toArray($formRec->closeWith);
                core_App::setTimeLimit(2000);

                if(countR($threads)){

                    // Намират се документите за КР в сделките, които ще се оттеглят
                    $notifiedItems = array();
                    $cRateQuery = acc_RatesDifferences::getQuery();
                    $cRateQuery->where("#state = 'active'");
                    $cRateQuery->in("threadId", $threads);
                    $cRateQuery->show('id,dealOriginId');
                    while($cRateRec = $cRateQuery->fetch()){
                        $closedDeal = doc_Containers::getDocument($cRateRec->dealOriginId);

                        // Оттеглят се активните КР
                        core_Users::forceSystemUser();
                        acc_RatesDifferences::reject($cRateRec->id);
                        acc_RatesDifferences::logWrite('Оттегляне преди обединение с друга сделка', $cRateRec->id);
                        core_Users::cancelSystemUser();
                        $itemRec = acc_Items::fetchItem($closedDeal->getClassId(), $closedDeal->that);
                        if($itemRec){
                            $notifiedItems[] = $itemRec;
                        }
                    }
                    foreach ($notifiedItems as $itemRecToNotify){
                        acc_Items::notifyObject($itemRecToNotify);
                    }
                    cls::get('acc_Items')->flushTouched();
                }

                if($formRec->_recalRate){
                    $notifiedItems2 = array();
                    $recalcRates = $deals + array($rec->id => $rec);
                    foreach ($recalcRates as $recalcDealId){
                        $recalcDealRec = $this->fetchRec($recalcDealId);
                        if(round($recalcDealRec->currencyRate, 5) == round($formRec->rate, 5)) continue;

                        try{
                            // Рекалкулиране на документите с новия курс
                            Mode::push('dontUpdateThread', true);
                            core_Users::forceSystemUser();
                            $this->recalcDocumentsWithNewRate($recalcDealRec, $formRec->rate);
                            core_Users::cancelSystemUser();
                            Mode::pop('dontUpdateThread');
                            $itemRec = acc_Items::fetchItem($this, $recalcDealRec->id);
                            if($itemRec){
                                $notifiedItems2[] = $itemRec;
                            }
                        } catch(acc_journal_Exception $e){
                            $errorArr[] = "Курса не може да бъде преизчислен|*: " . $this->getHandle($recalcDealRec->id);
                        }
                    }

                    foreach ($notifiedItems2 as $itemRecToNotify){
                        acc_Items::notifyObject($itemRecToNotify);
                    }
                    cls::get('acc_Items')->flushTouched();
                }

                // Обединения договор ще е активен
                $allocatedExpenses = array();
                $dealRec = $this->fetch($rec->id, '*', false);
                $dealRec->contoActions = 'activate';
                $dealRec->state = 'active';
                $dealRec->closedDocuments = keylist::merge($dealRec->closedDocuments, $formRec->closeWith);
                $this->save($dealRec);

                // Проверка дали някоя от сделките, които ще се обединяват е РО
                $haveDealExpenseItem = false;
                $listId = acc_Lists::fetchBySystemId('costObjects')->id;
                foreach ($deals as $dealId1) {
                    if (acc_Items::isItemInList($this, $dealId1, 'costObjects')) {
                        $haveDealExpenseItem = true;
                        break;
                    }
                }

                // Ако поне една от сделките е РО, то и обединяващата сделка ще е ПО
                $combinedDealItemId = null;
                if($haveDealExpenseItem){
                    if (!acc_Items::isItemInList($this, $rec->id, 'costObjects')) {
                        acc_Items::force($this->getClassId(), $rec->id, $listId);
                    }
                    $combinedDealItemId = acc_Items::fetchItem($this, $rec->id)->id;
                }

                core_App::setTimeLimit(2000);
                foreach ($deals as $dealId) {

                    // Ако има разпределени разходи към сделката, запомнят се и се изтриват
                    $tmpCache = array();
                    if(acc_Items::isItemInList($this, $dealId, 'costObjects')){
                        $dItemId = acc_Items::fetchItem($this, $dealId)->id;
                        $cQuery = acc_CostAllocations::getQuery();
                        $cQuery->where("#expenseItemId = {$dItemId}");
                        while($cRec = $cQuery->fetch()){
                            $exAccId = $cRec->id;
                            acc_CostAllocations::delete($cRec->id);
                            unset($cRec->id, $cRec->createdOn, $cRec->createdBy);
                            $tmpCache[$exAccId] = clone $cRec;
                            $cRec->_oldExpenceItemId = $cRec->expenseItemId;
                            $cRec->expenseItemId = $combinedDealItemId;
                            $allocatedExpenses[$exAccId] = $cRec;
                        }
                    }

                    // Създаване на приключващ документ-чернова
                    $dRec = $this->fetch($dealId);
                    $clId = $CloseDoc->create($this->className, $dRec, $id);
                    $exClosedDoc = null;

                    try {

                        // Ако няма друг активен приключващ документ за обединяване към тази сделка - да не се дублира
                        $exClosedDoc = $CloseDoc->fetch("#threadId = {$dRec->threadId} AND #id != '{$clId}' AND #state = 'active'");
                        if(!$exClosedDoc){
                            Mode::push('isBeingClosedWithDeal', true);
                            $CloseDoc->conto($clId);
                            Mode::pop('isBeingClosedWithDeal');
                        } else {
                            wp($exClosedDoc, $CloseDoc->fetch($clId), 'Дублиране на обединяване');
                        }
                    } catch (acc_journal_RejectRedirect $e) {
                        // Ако е имало грешка и има изтрити разходи възстановяват се
                        foreach ($tmpCache as $exAccCostId => $deletedCostRec){
                            acc_CostAllocations::save($deletedCostRec);
                            unset($allocatedExpenses[$exAccCostId]);
                        }
                        $errorArr[] = "Не може да се контира|*: #{$CloseDoc->getHandle($clId)}";
                    }

                    if(!$exClosedDoc){
                        $this->logWrite('Приключено с друга сделка', $dealId);
                    }
                }

                $this->invoke('AfterActivation', array($dealRec));

                // Разпределените разходи към приключените сделки се насочват към новата сделка
                foreach ($allocatedExpenses as $newExpense){
                    acc_CostAllocations::save($newExpense);
                }

                // Форсиране на съмърито, ако сделката е форсирана като РО
                if($haveDealExpenseItem && $combinedDealItemId){
                    doc_ExpensesSummary::updateSummary($rec->containerId, $combinedDealItemId, true);
                }

                // Записваме, че потребителя е разглеждал този списък
                $this->logWrite('Приключване на сделка с друга сделка', $id);
                if(countR($errorArr)){
                    $errorArrStr = implode('. ', $errorArr);
                    return new Redirect(array($this, 'single', $id), $errorArrStr, 'error');
                }

                return new Redirect(array($this, 'single', $id));
            }
        }

        $arr = arr::make("ef_icon = img/16/tick-circle-frame.png");
        if($originalState == 'active'){
            $arr['warning'] = 'Договорът е вече активен. Наистина ли желаете да обедините друг(и) договори към него?';
        }
        $form->toolbar->addSbBtn('Обединяване', 'save', $arr);
        $form->toolbar->addBtn('Отказ', array($this, 'single', $id), 'ef_icon = img/16/close-red.png,order=9999');
        
        $tpl = $this->renderWrapping($form->renderHtml());
        core_Form::preventDoubleSubmission($tpl, $form);
        
        // Рендиране на формата
        return $tpl;
    }
    
    
    /**
     * Добавяме полетата от драйвера, ако са указани
     */
    public static function recToVerbal_($rec, &$fields = array())
    {
        $row = parent::recToVerbal_($rec, $fields);
        
        if ($rec->closedDocuments) {
            $docs = keylist::toArray($rec->closedDocuments);
            $row->closedDocuments = '';
            foreach ($docs as $docId) {
                $row->closedDocuments .= ht::createLink(static::getHandle($docId), array(get_called_class(), 'single', $docId)) . ', ';
            }
            $row->closedDocuments = trim($row->closedDocuments, ', ');
        }
        
        if ($fields['-list']) {
            $row->title = static::getLink($rec->id);
        }

        if ($fields['-single']) {
            if(isset($rec->clonedFromId)){
                $row->clonedFromId = static::getLink($rec->clonedFromId, 0);
            }
        }

        return $row;
    }
    
    
    /**
     * Подготвя обединено представяне на всички записи от журнала където участва сделката
     *
     * @param core_ET  $tpl
     * @param stdClass $data
     */
    public function renderDealHistory(&$tpl, $data)
    {
        $tableMvc = new core_Mvc;
        $tableMvc->FLD('debitAcc', 'varchar', 'tdClass=articleCell');
        $tableMvc->FLD('creditAcc', 'varchar', 'tdClass=articleCell');
        
        $table = cls::get('core_TableView', array('mvc' => $tableMvc));
        $fields = 'valior=Вальор,debitAcc=Дебит->Сметка,debitQuantity=Дебит->К-во,debitPrice=Дебит->Цена,creditAcc=Кредит->Сметка,creditQuantity=Кредит->К-во,creditPrice=Кредит->Цена,amount=Сума';
        
        $tpl->append($table->get($data->DealHistory, $fields), 'DEAL_HISTORY');
        $tpl->append($data->historyPager->getHtml(), 'DEAL_HISTORY');
        $tpl->removeBlock('STATISTIC_BAR');
    }
    
    
    /**
     * Рендира информацията за доставеното/полученото по сделката
     *
     * @param core_ET  $tpl
     * @param stdClass $data
     */
    public function renderDealReport(&$tpl, $data)
    {
        $table = cls::get('core_TableView', array('mvc' => $data->reportTableMvc));
        $tpl->append($table->get($data->DealReport, $data->reportFields), 'DEAL_REPORT');
        $tpl->append($data->reportPager->getHtml(), 'DEAL_REPORT');
        
        if ($this->haveRightFor('export', $data->rec) && countR($data->DealReport)) {
            $expUrl = getCurrentUrl();
            $expUrl['export'] = true;
            $btn = cls::get('core_Toolbar');
            $btn->addBtn('Експорт в CSV', $expUrl, null, 'ef_icon=img/16/file_extension_xls.png, title=Сваляне на записите в CSV формат');
            $btnCSV = 'export';
            $btnCSVHtml = $btn->renderHtml('', $btnCSV);
            
            $tpl->replace($btnCSVHtml, 'TABEXP');
        }
        $tpl->removeBlock('STATISTIC_BAR');
    }
    
    
    /**
     * Извиква се преди рендирането на 'опаковката'
     */
    public static function on_AfterRenderSingleLayout($mvc, &$tpl, &$data)
    {
        if (Mode::is('printing') || Mode::is('text', 'xhtml')) {
            $tpl->removeBlock('header');
            $tpl->removeBlock('STATISTIC_BAR');
        }
    }


    /**
     * След подготовка на табовете на документа
     * @see doc_plg_Tabs
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     * @return void
     */
    protected static function on_AfterPrepareDocumentTabs($mvc, &$res, $data)
    {
        // Таб за показване на счетоводните обороти
        if (haveRole('ceo,acc')) {
            if ($data->rec->state != 'draft') {
                $url = getCurrentUrl();
                $url["docTab{$data->rec->containerId}"] = 'DealHistory';
                $data->tabs->TAB('DealHistory', 'Обороти', $url, null, 2);
            }
        }
    }
    
    
    /**
     * След подготовка на тулбара на единичен изглед.
     */
    public static function on_AfterPrepareSingle($mvc, &$res, &$data)
    {
        if (Mode::is('printing') || Mode::is('text', 'xhtml')) {
            
            return;
        }

        if(!empty($data->rec->closeWith)){
            $data->row->closeWith = $mvc->getLink($data->rec->closeWith, 0);
        }
    }
    
    
    /**
     * Екшън който експортира данните
     */
    protected function exportReport(&$data)
    {
        expect(Request::get('export', 'int'));
        expect($rec = $data->rec);
        
        // Проверка за права
        $this->requireRightFor('export', $rec);
        $csv = csv_Lib::createCsv($data->DealReportCsv, $data->reportTableMvc, $data->reportFields);
        $csv .= "\n";
        
        $csv = mb_convert_encoding($csv, 'UTF-8', 'UTF-8');
        $csv = iconv('UTF-8', 'UTF-8//IGNORE', $csv);
        
        // Записване във файловата система
        $fh = fileman::absorbStr($csv, 'exportCsv', "{$this->abbr}{$rec->id}_OrderedAndShipped.csv");
        
        // Редирект към експортиртния файл
        redirect(array('fileman_Files', 'single', $fh), '|Справката е експортирана успешно');
    }
    
    
    /**
     * Ще се експортирват полетата, които се
     * показват в табличния изглед
     *
     * @return array
     *
     * @todo да се замести в кода по-горе
     */
    protected function getFields_()
    {
        // Кои полета ще се показват
        $f = new core_FieldSet;
        $f->FLD('code', 'varchar');
        $f->FLD('productId', 'richtext(bucket=Notes)');
        $f->FLD('measure', 'varchar');
        $f->FLD('quantity', 'double');
        $f->FLD('shipQuantity', 'double');
        $f->FLD('bQuantity', 'double');
        
        return $f;
    }
    
    
    /**
     * Подготвя обединено представяне на всички записи от журнала където участва сделката
     */
    public function prepareDealReport(&$data)
    {
        $rec = $data->rec;
        if ($rec->state == 'draft') return;
        
        // обобщената информация за цялата нищка
        $dealInfo = self::getAggregateDealInfo($rec->id);
        $Double = cls::get('type_Double', array('params' => array('decimals' => '2')));
        $report = $dealReportCSV = array();
        $productIds = arr::extractValuesFromArray($dealInfo->products, 'productId') + arr::extractValuesFromArray($dealInfo->shippedProducts, 'productId');
        
        if (countR($productIds)) {
            foreach ($productIds as $productId) {
                $pRec = cat_Products::fetch($productId, 'measureId,isPublic,nameEn,code,name,canStore');
                $expRec = (object) array('code' => ($pRec->code) ? $pRec->code : "Art{$productId}",
                    'productId' => $productId,
                    'measureId' => $pRec->measureId,
                    'blQuantity' => $dealInfo->products[$productId]->quantity - $dealInfo->shippedProducts[$productId]->quantity,
                    'quantity' => ($dealInfo->products[$productId]->quantity) ? $dealInfo->products[$productId]->quantity : 0,
                    'shipQuantity' => ($dealInfo->shippedProducts[$productId]->quantity) ? $dealInfo->shippedProducts[$productId]->quantity : 0,
                );
                
                $row = (object) array('code' => core_Type::getByName('varchar')->toVerbal($expRec->code),
                    'measureId' => cat_UoM::getShortName($expRec->measureId),
                    'productId' => cat_Products::getShortHyperLink($productId),
                );

                if ($pRec->canStore == 'yes') {
                    $expRec->free = store_Products::getQuantities($productId)->free;
                    $expRec->inStock = store_Products::getQuantities($productId)->quantity;
                }
                
                foreach (array('quantity', 'shipQuantity', 'blQuantity', 'inStock', 'free') as $q) {
                    if (!isset($expRec->{$q})) {
                        continue;
                    }
                    $row->{$q} = $Double->toVerbal($expRec->{$q});
                    $row->{$q} = ht::styleNumber($row->{$q}, $expRec->{$q});
                }
                
                $report[$productId] = $row;
                $dealReportCSV[$productId] = $expRec;
            }
        }
        
        // правим странициране
        $pager = cls::get('core_Pager', array('pageVar' => 'P_' .  $this->className,'itemsPerPage' => $this->reportItemsPerPage));
        
        $cnt = countR($report);
        $pager->itemsCount = $cnt;
        $data->reportPager = $pager;
        
        $pager->calc();
        
        $start = $data->reportPager->rangeStart;
        $end = $data->reportPager->rangeEnd - 1;
        
        // проверяваме дали може да се сложи на страницата
        $data->DealReport = array_slice($report, $start, $end - $start + 1);
        $data->DealReportCsv = $dealReportCSV;
        $data->reportFields = arr::make('code=Код,productId=Артикул,measureId=Мярка,quantity=Количество->Поръчано,shipQuantity=Количество->Доставено,blQuantity=Количество->Остатък,inStock=Количество->Налично,free=Количество->Разполагаемо', true);
        
        $data->reportTableMvc = new core_Mvc;
        $data->reportTableMvc->FLD('code', 'varchar');
        $data->reportTableMvc->FLD('productId', 'key(mvc=cat_Products,select=name)');
        $data->reportTableMvc->FLD('measureId', 'key(mvc=cat_UoM,select=name)', 'tdClass=accToolsCell nowrap');
        $data->reportTableMvc->FLD('quantity', 'double', 'tdClass=aright');
        $data->reportTableMvc->FLD('shipQuantity', 'double', 'tdClass=aright');
        $data->reportTableMvc->FLD('blQuantity', 'double', 'tdClass=aright');
        $data->reportTableMvc->FLD('inStock', 'double', 'tdClass=aright');
        $data->reportTableMvc->FLD('free', 'double', 'tdClass=aright');

        if (Request::get('export', 'int') && $this->haveRightFor('export', $data->rec)) {
            $this->exportReport($data);
        }
    }
    
    
    /**
     * Подготвя обединено представяне на всички записи от журнала където участва сделката
     */
    public function prepareDealHistory(&$data)
    {
        $rec = $data->rec;
        if (!haveRole('ceo,acc')) {
            
            return;
        }
        if ($rec->state == 'draft') {
            
            return;
        }
        
        // Извличаме всички записи от журнала където сделката е в дебита или в кредита
        $entries = acc_Journal::getEntries(array($this->className, $rec->id));
        
        $history = array();
        $Date = cls::get('type_Date');
        $Double = cls::get('type_Double', array('params' => array('decimals' => '2')));
        
        $Pager = cls::get('core_Pager', array('itemsPerPage' => $this->historyItemsPerPage));
        $Pager->setPageVar($this->className, $rec->id);
        $Pager->itemsCount = countR($entries);
        $Pager->calc();
        $data->historyPager = $Pager;
        
        $start = $data->historyPager->rangeStart;
        $end = $data->historyPager->rangeEnd - 1;

        // Ако има записи където участва перото подготвяме ги за показване
        if (countR($entries)) {
            foreach ($entries as $e){
                $e->documentCreatedOn = cls::get($e->docType)->fetchField($e->docId, 'createdOn');
            }

            // Подредба по вальор
            usort($entries, function ($a, $b) {
                if ($a->valior == $b->valior) {
                    return ($a->documentCreatedOn < $b->documentCreatedOn) ? -1 : 1;
                }
                return ($a->valior < $b->valior) ? -1 : 1;
            });

            $count = 0;
            foreach ($entries as $ent) {
                if ($count >= $start && $count <= $end) {
                    $obj = new stdClass();
                    $obj->valior = $Date->toVerbal($ent->valior);
                    $docHandle = cls::get($ent->docType)->getLink($ent->docId, 0);
                    
                    $obj->valior .= "<br>{$docHandle}";
                    $obj->valior = "<span style='font-size:0.8em;'>{$obj->valior}</span>";
                    if (empty($this->historyCache[$ent->debitAccId])) {
                        $this->historyCache[$ent->debitAccId] = acc_Balances::getAccountLink($ent->debitAccId);
                    }
                    
                    if (empty($this->historyCache[$ent->creditAccId])) {
                        $this->historyCache[$ent->creditAccId] = acc_Balances::getAccountLink($ent->creditAccId);
                    }
                    $obj->debitAcc = $this->historyCache[$ent->debitAccId];
                    $obj->creditAcc = $this->historyCache[$ent->creditAccId];
                    
                    foreach (range(1, 3) as $i) {
                        if (!empty($ent->{"debitItem{$i}"})) {
                            $obj->debitAcc .= "<div style='font-size:0.8em;margin-top:1px'>{$i}. " . acc_Items::getVerbal($ent->{"debitItem{$i}"}, 'titleLink') . '</div>';
                        }
                        
                        if (!empty($ent->{"creditItem{$i}"})) {
                            $obj->creditAcc .= "<div style='font-size:0.8em;margin-top:1px'>{$i}. " . acc_Items::getVerbal($ent->{"creditItem{$i}"}, 'titleLink') . '</div>';
                        }
                    }

                    foreach (array('debitQuantity', 'debitPrice', 'creditQuantity', 'creditPrice', 'amount') as $fld) {
                        $entVerbal = $Double->toVerbal($ent->{$fld});
                        if(in_array($fld, array('amount', 'debitPrice', 'creditPrice'))){
                            if(!empty($ent->{$fld})){
                                $entVerbal = currency_Currencies::decorate($entVerbal, acc_Periods::getBaseCurrencyCode($ent->valior), true);
                            }
                        }

                        $obj->{$fld} = "<span style='float:right'>{$entVerbal}</span>";
                    }
                    
                    $history[] = $obj;
                }
                
                $count++;
            }
        }

        $data->DealHistory = $history;
    }
    
    
    /**
     * Рекалкулиране на курса на документите в сделката
     */
    public function act_Changerate()
    {
        $this->requireRightFor('changerate');
        expect($id = Request::get('id', 'int'));
        expect($rec = $this->fetchRec($id));
        $this->requireRightFor('changerate', $rec);
        
        $form = cls::get('core_Form');
        $form->title = '|Преизчисляване на курса на|* ' . $this->getFormTitleLink($rec);
        $form->info = "<div style='margin-left:7px'>" . tr("Стар курс|*: <i style='color:green'>{$rec->currencyRate}</i>") . "</div>";
        $form->FLD('newRate', 'double', 'caption=Нов курс,mandatory');

        $today = dt::today();
        $newCurrencyRate = currency_CurrencyRates::getRate($today, $rec->currencyId, null);
        $todayVerbal = dt::mysql2verbal($today);
        $form->info .= "<div style='margin-left:7px'>" . tr("Курс към|* {$todayVerbal}: <i style='color:green'>{$newCurrencyRate}</i>") . "</div>";
        $form->setDefault('newRate', $newCurrencyRate);

        $form->input();

        if ($form->isSubmitted()) {
            $fRec = $form->rec;
            try{
                $this->recalcDocumentsWithNewRate($rec, $fRec->newRate);
            } catch(acc_journal_Exception $e){
                $url = $this->getSingleUrlArray($rec->id);
                redirect($url, false, '|Курса не може да бъде преизчислен|! ' . $e->getMessage(), 'error');
            }

            // Нотифициране на обекта за да се преизчисли статистиката за всеки случай
            $itemRec = acc_Items::fetchItem($this, $rec);
            if($itemRec){
                acc_Items::notifyObject($itemRec);
            }

            acc_RatesDifferences::force($rec->threadId, $rec->currencyId, $fRec->newRate, 'Автоматична корекция на курсови разлики');
            if($itemRec){
                acc_Items::notifyObject($itemRec);
            }

            followRetUrl(null, '|Документите са преизчислени успешно|*!');
        }

        $form->toolbar->addSbBtn('Преизчисли', 'save', 'ef_icon = img/16/tick-circle-frame.png,warning=Ще преизчислите всички документи в нишката по новия курс,order=9');
        $form->toolbar->addBtn('Отказ', array($this, 'single', $id), 'ef_icon = img/16/close-red.png,order=911');
        
        // Рендиране на формата
        return $this->renderWrapping($form->renderHtml());
    }


    /**
     * Реконтира документите в нишката на сделката с посочения нов курс
     *
     * @param stdClass $rec            - запис на сделка
     * @param double $newRate          - нов курс
     * @param boolean $recontoDealAlso - да реконтира ли сделката с новия курс
     * @return void
     */
    public function recalcDocumentsWithNewRate($rec, $newRate, $recontoDealAlso = true)
    {
        // Рекалкулиране на сделката
        $valior = $rec->{$this->valiorFld};
        $periodState = acc_Periods::fetchByDate($valior)->state;

        // Рекалкулиране на курса на сделката, само ако не е в затворен период
        if($periodState != 'closed' && $recontoDealAlso) {
            if ($this instanceof findeals_Deals) {
                $rec->currencyRate = $newRate;
                $this->save($rec);
                if ($rec->state == 'active') {
                    $deletedRec = null;
                    acc_Journal::deleteTransaction($this->getClassId(), $rec->id, $deletedRec);

                    $popReconto = $popRecontoDate = false;
                    try{
                        if(is_object($deletedRec)){
                            Mode::push('recontoWithCreatedOnDate', $deletedRec->createdOn);
                            $popRecontoDate = true;
                        }
                        Mode::push('recontoTransaction', true);
                        $popReconto = true;
                        acc_Journal::saveTransaction($this->getClassId(), $rec->id, false);
                        Mode::pop('recontoTransaction');
                        $popReconto = false;
                        if($popRecontoDate){
                            Mode::pop('recontoWithCreatedOnDate');
                            $popRecontoDate = false;
                        }
                    } catch(acc_journal_RejectRedirect  $e){
                        if(is_object($deletedRec)) {
                            acc_Journal::restoreDeleted($this->getClassId(), $rec->id, $deletedRec, $deletedRec->_details);
                        }
                        if($popReconto){
                            Mode::pop('recontoTransaction');
                        }
                        if($popRecontoDate){
                            Mode::pop('recontoWithCreatedOnDate');
                        }
                    }
                }
            } else {
                deals_Helper::recalcRate($this, $rec->id, $newRate);
            }
        }

        // Рекалкулиране на определени документи в нишката и
        $dealDocuments = $this->getDescendants($rec->id);
        $arr = array('store_ShipmentOrders', 'store_Receipts', 'sales_Services', 'purchase_Services', 'sales_Invoices', 'purchase_Invoices', 'acc_ValueCorrections');
        foreach ($dealDocuments as $d) {
            if (!in_array($d->className, $arr)) continue;

            // Ако вальора е в затворен период - пропуска се
            $valior = $d->fetchField($d->valiorFld);
            $periodState = acc_Periods::fetchByDate($valior)->state;
            if ($periodState == 'closed') continue;

            deals_Helper::recalcRate($d->getInstance(), $d->fetch(), $newRate);
        }
    }


    /**
     * Рекалкулиране на документите с курса на сделките, в сделки, които не са в посочените валути
     *
     * @param array $skipCurrencyCodes
     * @return void
     */
    public function recalcDocumentsWithDealCurrencyRate($skipCurrencyCodes = array('BGN', 'EUR'))
    {
        $iQuery = acc_Items::getQuery();
        $iQuery->where("#classId = {$this->getClassId()} AND #state = 'active'");
        $iQuery->EXT('currencyId', $this->className, 'externalName=currencyId,externalKey=objectId');
        $iQuery->notIn("currencyId", $skipCurrencyCodes);

        $dealIds = arr::extractValuesFromArray($iQuery->fetchAll(), 'objectId');
        $count = countR($dealIds);

        if(!$count) return;
        core_App::setTimeLimit(0.9 * $count, false, 200);

        // Ако има намерени сделки
        $Items = cls::get('acc_Items');
        $query = $this->getQuery();
        $query->in('id', $dealIds);
        $query->where("#state = 'active'");

        $recalcedItems = $saved = array();
        while($rec = $query->fetch()){
            $itemRec = acc_Items::fetchItem($this, $rec->id);

            try{
                // Рекалкулиране на документите с новия курс
                Mode::push('dontUpdateThread', true);
                $this->recalcDocumentsWithNewRate($rec, $rec->currencyRate, false);
                Mode::pop('dontUpdateThread');
            } catch(acc_journal_Exception $e){
                $errorMsg = "Курса не може да бъде авт. преизчислен. {$e->getMessage()}";
                $this->logErr($errorMsg, $rec->id);
                continue;
            }
            $recalcedItems[$rec->id] = $itemRec;
        }

        // Нотифициране на перата на сделките
        foreach ($recalcedItems as $itemRec){
            acc_Items::notifyObject($itemRec);
        }
        $Items->flushTouched();
    }
}
