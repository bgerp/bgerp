<?php
/**
 * Клас 'deals_ClosedDeals'
 * Абстрактен клас за създаване на приключващи документи. Неговите наследници
 * могат да се създават само в тред, началото на който е документ с интерфейс
 *'bgerp_DealAggregatorIntf'. След контирането на този документ, не може в треда
 * да се добавят документи, променящи стойностите на сделката
 *
 *
 *
 * @category  bgerp
 * @package   deals
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2021 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
abstract class deals_ClosedDeals extends core_Master
{
    /**
     * Икона за фактура
     */
    public $singleIcon = 'img/16/closeDeal.png';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    protected $listFields = 'tools=Пулт, title=Документ, valior=Вальор, docId=Сделка, createdOn, createdBy';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    public $rowToolsSingleField = 'title';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'tools';


    /**
     * Дали може да се използват затворени пера
     */
    public $canUseClosedItems = true;


    /**
     * Дали се очаква в документа да има файлове
     */
    public $expectFiles = false;


    /**
     * Файл за единичен изглед
     */
    protected $singleLayoutFile = 'deals/tpl/ClosedDealsSingleLayout.shtml';
    
    
    /**
     * Работен кеш
     */
    protected static $cache = array();
    
    
    /**
     * Още един работен кеш
     */
    protected static $incomeAmount;
    
    
    /**
     * Още един работен кеш
     */
    protected $year;
    
    
    /**
     * Кратък баланс на записите от журнала засегнали сделката
     */
    protected $shortBalance;
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('valiorStrategy', 'enum(auto=Най-голям вальор към сделката,createdOn=Дата на създаване,manual=Конкретен вальор)', 'caption=Вальор,mandatory,silent,removeAndRefreshForm=valior,notNull,value=auto');
        $this->FLD('valior', 'date', 'input=hidden,caption=Вальор,after=valiorStrategy');
        $this->FLD('notes', 'richtext(rows=2,bucket=Notes)', 'caption=Забележка');
        $this->FLD('docClassId', 'class(interface=doc_DocumentIntf)', 'input=none');
        $this->FLD('docId', 'class(interface=doc_DocumentIntf)', 'input=none');
        $this->FLD('amount', 'double(decimals=2)', 'input=none,caption=Сума');
        $this->FLD('currencyId', 'customKey(mvc=currency_Currencies,key=code,select=code)', 'caption=Плащане->Валута,input=none');
        $this->FLD('rate', 'double(decimals=5)', 'caption=Плащане->Курс,input=none');

        // От кой клас наследник на deals_ClosedDeals идва записа
        $this->FLD('classId', 'key(mvc=core_Classes)', 'input=none');
        
        $this->setDbIndex('valior');
    }
    
    
    /**
     * Подготвя записите за приключване на дадена сделка с друга сделка
     *
     * 1. Занулява салдата на първата сделка, прави обратни транзакции на всички записи от журнала свързани с тази сделка
     * 2. Прави същите операции но подменя перото на първата сделка с това на второто, така всички салда са
     * прихвърлени по втората сделка, а първата е приключена
     */
    public function getTransferEntries($dealItem, &$total, $closeDeal, $rec)
    {
        $newEntries = array();
        $docs = array();

        // Намираме записите в които участва перото
        $entries = acc_Journal::getEntries($dealItem);

        // Намираме документите, които имат транзакции към перото
        if (countR($entries)) {
            foreach ($entries as $ent) {
                if ($ent->docType != $rec->classId || ($ent->docType == $rec->classId && $ent->docId != $rec->id)) {
                    $docs[$ent->docType . '|' . $ent->docId] = (object) array('docType' => $ent->docType, 'docId' => $ent->docId);
                }
            }
        }
        
        $dealItem->docClassName = cls::get($dealItem->classId)->className;
        $dealClassId = cls::get($dealItem->classId)->getClassId();

        if (countR($docs)) {

            // За всеки транзакционен клас
            foreach ($docs as $doc) {

                // Взимаме му редовете на транзакцията
                $transactionSource = cls::getInterface('acc_TransactionSourceIntf', $doc->docType);
                $entries = $transactionSource->getTransaction($doc->docId)->entries;
                $copyEntries = $entries;

                // За всеки ред, генерираме запис с обратни стойностти (сумите и к-та са с обратен знак)
                // Така зануляване салдата по следката
                if (countR($entries)) {
                    foreach ($copyEntries as &$entry) {
                        
                        // Ако има сума добавяме я към общата сума на транзакцията
                        if (isset($entry['amount'])) {
                            $entry['amount'] *= -1;
                            $total += $entry['amount'];
                        }
                        
                        if (isset($entry['debit']['quantity'])) {
                            $entry['debit']['quantity'] *= -1;
                        }
                        
                        if (isset($entry['credit']['quantity'])) {
                            $entry['credit']['quantity'] *= -1;
                        }
                        
                        $newEntries[] = $entry;
                    }

                    // Втори път обхождаме записите
                    foreach ($entries as &$entry2) {
                        if (isset($entry2['amount'])) {
                            $total += $entry2['amount'];
                        }

                        // Генерираме запис, който прави същите действия но с перо новата сделка
                        foreach (array('debit', 'credit') as $type) {
                            foreach ($entry2[$type] as $index => &$item) {

                                // Намираме кое перо отговаря на перото на текущата сделка и го заменяме с това на новата сделка
                                if ($index != 0) {
                                    if (is_array($item) && (is_numeric($item[0]) && $item[0] == $dealClassId || $item[0] == $dealItem->docClassName)  && $item[1] == $dealItem->objectId) {
                                        $item = $closeDeal;
                                    } elseif(is_numeric($item) && $item == $dealItem->id){

                                        // Ако участва директно перото на сделката, също се променя
                                        $item = $closeDeal;
                                    }
                                }
                            }
                        }
                        
                        $newEntries[] = $entry2;
                    }
                }
            }
        }

        // Връщаме генерираните записи
        return $newEntries;
    }
    
    
    /**
     * Връща информацията 'bgerp_DealAggregatorIntf' от първия документ
     * в нишката ако го поддържа
     *
     * @param mixed $threadId - ид на нишката или core_ObjectReference
     *                        към първия документ в нишката
     *
     * @return bgerp_iface_DealAggregator - бизнес информацията от документа
     */
    public static function getDealInfo($threadId)
    {
        $firstDoc = (is_numeric($threadId)) ? doc_Threads::getFirstDocument($threadId) : $threadId;
        
        expect($firstDoc instanceof core_ObjectReference, $firstDoc);
        $threadId = $firstDoc->fetchField('threadId');
        
        if ($firstDoc->haveInterface('bgerp_DealAggregatorIntf')) {
            if (empty(static::$cache[$threadId])) {
                
                // Запис във временния кеш
                expect($dealInfo = $firstDoc->getAggregateDealInfo());
                static::$cache[$threadId] = $dealInfo;
            }
            
            return static::$cache[$threadId];
        }
        
        return false;
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass     $data
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = &$data->form;
        $rec = &$form->rec;

        $strategyOptions = arr::make('auto=Най-голям вальор към сделката,createdOn=Дата на създаване,manual=Конкретен вальор', true);
        if(!haveRole('accMaster,ceo') && empty($rec->valior)){
            unset($strategyOptions['manual']);
        }
        $form->setFieldType('valiorStrategy', "enum(" . arr::fromArray($strategyOptions). ")");

        if($rec->valiorStrategy == 'manual'){
            $form->setField('valior', 'input');
        }
    }


    /**
     * Извиква се след подготовката на toolbar-а на формата за редактиране/добавяне
     */
    protected static function on_AfterPrepareEditToolbar($mvc, $data)
    {
        if ($mvc->haveRightFor('conto', $data->form->rec)) {
            $error = $mvc->getContoBtnErrStr($data->form->rec);
            $contoBtnUrl = array('warning' => 'Наистина ли желаете да контирате приключването|*?', 'ef_icon' => 'img/16/tick-circle-frame.png', 'order' => '9.99985', 'title' => 'Контиране на документа');
            if(!empty($error)){
                $contoBtnUrl['error'] = $error;
            }
            $data->form->toolbar->addSbBtn('Контиране', 'autoConto', $contoBtnUrl);
        }
    }


    /**
     * Преди показване на форма за добавяне/промяна
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
        $rec = &$form->rec;

        if($rec->valiorStrategy == 'manual'){
            $form->setField('valior', 'input,caption=Дата');
        }

        $firstDoc = doc_Threads::getFirstDocument($rec->threadId);
        $rec->docId = $firstDoc->that;
        $rec->docClassId = $firstDoc->getInstance()->getClassId();
        $rec->classId = $mvc->getClassId();

        $liveAmount = $mvc->getLiveAmount($rec);
        $Double = core_Type::getByName('double(decimals=2)');

        // При редакция се показва очаквания, приход разход
        if (round($liveAmount, 2) > 0) {
            $incomeAmount = $liveAmount;
            $form->info = tr('Извънреден приход|*: <b style="color:blue">') . $Double->toVerbal($incomeAmount) . "</b> " . acc_Periods::getBaseCurrencyCode();
        } elseif (round($liveAmount, 2) < 0) {
            $costAmount = abs($liveAmount);
            $form->info = tr('Извънреден разход|*: <b style="color:blue">') . $Double->toVerbal($costAmount) . "</b> " . acc_Periods::getBaseCurrencyCode();
        }

        if($form->isSubmitted()){
            if($rec->valiorStrategy == 'manual'){
                if(empty($rec->valior)){
                    $form->setError('valior', 'Трябва да е посочена конкретна дата');
                } else {
                    $skipClasses = array(acc_RatesDifferences::getClassId());
                    $biggestValior = $mvc->getBiggestValiorInDeal($rec, $skipClasses);
                    if(!empty($biggestValior) && $rec->valior < $biggestValior){
                        $biggestValiorVerbal = core_Type::getByName('date')->toVerbal($biggestValior);
                        $form->setError('valior', "Датата e преди най-големия вальор към сделката:|* <b>{$biggestValiorVerbal}</b>");
                    }
                }
            }
        }
    }
    
    
    /**
     * Може ли документа да се добави в посочената папка?
     */
    public static function canAddToFolder($folderId)
    {
        return false;
    }
    
    
    /**
     * Проверка дали нов документ може да бъде добавен в
     * посочената нишка
     */
    public static function canAddToThread($threadId)
    {
        // Първия документ в треда трябва да е активиран
        $firstDoc = doc_Threads::getFirstDocument($threadId);
        
        if (!$firstDoc) {
            
            return false;
        }
        
        // Може да се добавя само към ниша с първи документ имащ "bgerp_DealAggregatorIntf'
        if (!$firstDoc->haveInterface('bgerp_DealAggregatorIntf')) {
            
            return false;
        }
        
        // Може да се добавя само към активирани документи
        if ($firstDoc->fetchField('state') != 'active') {
            
            return false;
        }
        
        // Дали вече има такъв документ в нишката
        $closedDoc = static::fetch("#threadId = {$threadId} AND #state != 'rejected'");
        
        if ($closedDoc !== false) {
            
            return false;
        }
        
        return true;
    }
    
    
    /**
     * След подготовка на лист тулбара
     */
    public static function on_AfterPrepareListToolbar($mvc, $data)
    {
        if (!empty($data->toolbar->buttons['btnAdd'])) {
            unset($data->toolbar->buttons['btnAdd']);
        }
    }


    /**
     * Пренасочва URL за връщане след запис към сингъл изгледа
     */
    protected static function on_AfterPrepareRetUrl($mvc, $res, $data)
    {
        // Ако се иска директно контиране редирект към екшъна за контиране
        if ($data->form && $data->form->isSubmitted() && $data->form->cmd == 'autoConto') {
            if ($mvc->haveRightFor('conto', $data->form->rec->id)){
                $contoUrl = $mvc->getContoUrl($data->form->rec->id);
                $contoUrl['ret_url'] = array($mvc, 'single', $data->form->rec->id);
                $data->retUrl = toUrl($contoUrl);
            }
        }
    }


    /**
     * Изпълнява се след запис
     */
    public static function on_AfterSave($mvc, &$id, $rec, $saveFields = null)
    {
        // При активация на документа
        $rec = $mvc->fetch($id);

        if ($rec->state == 'active') {

            // Пораждащия документ става closed
            $DocClass = cls::get($rec->docClassId);
            $firstRec = $DocClass->fetch($rec->docId);
            $firstRec->state = 'closed';
            $firstRec->closedOn = $mvc->getValiorDate($rec);
            $DocClass->save($firstRec, 'modifiedOn,modifiedBy,state,closedOn');

            if (empty($saveFields)) {
                $rec->amount = $mvc->getClosedDealAmount($rec->threadId);
                $mvc->save($rec, 'amount');
            }

            if($DocClass->hasPlugin('store_plg_StockPlanning')){
                store_StockPlanning::updateByDocument($DocClass, $rec->docId);
            }
        }
        
        doc_DocumentCache::threadCacheInvalidation($rec->threadId);
    }
    
    
    /**
     * След оттегляне на документа, възстановява предишното
     * състояние на първия документ в нишката
     */
    public static function on_AfterReject($mvc, &$res, $id)
    {
        $rec = $mvc->fetch((is_object($id)) ? $id->id : $id);
        
        if ($rec->brState == 'active') {
            $DocClass = cls::get($rec->docClassId);
            $firstRec = $DocClass->fetch($rec->docId);
            
            // Обновяваме състоянието на сделката, само ако не е оттеглена
            if ($firstRec->state != 'rejected') {
                $firstRec->state = 'active';
                $firstRec->closeWith = null;
                $DocClass->save($firstRec, 'modifiedOn,modifiedBy,state,closeWith');
            }
        }
        
        $mvc->notifyDealUsedForClosure($id);
    }
    

    /**
     * Конвертира един запис в разбираем за човека вид
     * Входният параметър $rec е оригиналният запис от модела
     * резултата е вербалният еквивалент, получен до тук
     */
    public static function recToVerbal_($rec, &$fields = '*')
    {
        $me = cls::get(get_called_class());
        $row = parent::recToVerbal_($rec, $fields);
        
        $Double = cls::get('type_Double');
        $Double->params['decimals'] = 2;
        $costAmount = $incomeAmount = 0;

        if($rec->state == 'draft'){
            $rec->amount = $me->getLiveAmount($rec);
        }

        if (round($rec->amount, 2) > 0) {
            $incomeAmount = $rec->amount;
            $costAmount = 0;
        } elseif (round($rec->amount, 2) < 0) {
            $costAmount = $rec->amount;
            $incomeAmount = 0;
        }

        $Double = core_Type::getByName('double(decimals=2)');
        $row->costAmount = $Double->toVerbal(abs($costAmount));
        $row->incomeAmount = $Double->toVerbal(abs($incomeAmount));

        if($rec->state == 'draft'){
            $row->costAmount = ht::styleNumber($row->costAmount, abs($costAmount), 'blue');
            $row->costAmount = ht::createHint($row->costAmount, 'Сумата ще бъде записана при контиране');
            $row->incomeAmount = ht::styleNumber($row->incomeAmount, abs($incomeAmount), 'blue');
            $row->incomeAmount = ht::createHint($row->incomeAmount, 'Сумата ще бъде записана при контиране');
        }

        $row->currencyId = acc_Periods::getBaseCurrencyCode($rec->createdOn);
        $row->title = static::getLink($rec->id, 0);
        $row->docId = cls::get($rec->docClassId)->getLink($rec->docId, 0);

        if (!isset($rec->valior)) {
            $rec->valior = $me->getValiorDate($rec);
            $row->valior = $me->getFieldType('valior')->toVerbal($rec->valior);
            $row->valior = "<span style='color:blue'>{$row->valior}</span>";
        }
        
        return $row;
    }
    
    
    /**
     * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
     */
    public function getDocumentRow_($id)
    {
        $rec = $this->fetch($id);
        $row = new stdClass();
        
        $row->authorId = $rec->createdBy;
        $row->author = $this->getVerbal($rec, 'createdBy');
        $row->state = $rec->state;
        $row->saleId = cls::get($rec->docClassId)->getHandle($rec->docId);
        
        return $row;
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     */
    public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = null, $userId = null)
    {
        // Документа не може да се контира, ако ориджина му е в състояние 'closed'
        if (($action == 'add' || $action == 'conto' || $action == 'restore') && isset($rec)) {
            $origin = $mvc->getOrigin($rec);
            
            if ($origin && $origin->haveInterface('bgerp_DealAggregatorIntf')) {
                $item = acc_Items::fetchItem($origin->getInstance(), $origin->that);
                if (is_null($item->lastUseOn)) {
                    
                    // Ако перото на сделката не е използвано, не може да се приключи
                    $res = 'no_one';
                }
            }
        }
        
        // не може да се възстанови оттеглен документ, ако има друг неоттеглен в треда, или ако самия тред е оттеглен
        if ($action == 'restore' && isset($rec)) {
            if ($mvc->fetch("#threadId = {$rec->threadId} AND #state != 'rejected' AND #id != '{$rec->id}'") || doc_Threads::fetchField($rec->threadId, 'state') == 'rejected') {
                $res = 'no_one';
            }
        }
    }
    
    
    /**
     * Преди извличане на записите от БД
     */
    public static function on_AfterPrepareListFilter($mvc, &$data)
    {
        $plugins = $mvc->getPlugins();
        $docClassId = null;

        $data->listFilter->showFields = 'search';
        $data->listFilter->view = 'horizontal';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');

        if (isset($plugins['sales_Wrapper'])) {
            $docClassId = sales_Sales::getClassId();
        } elseif (isset($plugins['purchase_Wrapper'])) {
            $docClassId = purchase_Purchases::getClassId();
        } elseif (isset($plugins['findeals_Wrapper'])) {
            $docClassId = findeals_Deals::getClassId();
        }
        
        if ($docClassId) {
            $data->query->where("#docClassId = {$docClassId}");
            
            if (!$data->rejQuery) {
                $data->rejQuery = clone $data->query;
                $data->rejQuery->where("#state = 'rejected'");
            }
            
            $data->rejQuery->where("#docClassId = {$docClassId}");
        }
    }
    
    
    /**
     * Нов приключващ документ в същия тред на даден документ, и
     * приключващ продажбата/покупката
     *
     * @param mixed    $Class     - покупка или продажба
     * @param stdClass $docRec    - запис на покупка или продажба
     * @param int      $closeWith - с коя сделка ще се приключи продажбата
     */
    public function create($Class, $docRec, $closeWith = false)
    {
        $Class = cls::get($Class);
        
        // Създаване на приключващ документ, само ако има остатък/излишък
        $newRec = new stdClass();
        $notes = ($closeWith) ? 'Приключено със сделка' : 'Автоматично приключване';
        
        $newRec->notes = $notes;
        $newRec->docClassId = $Class->getClassId();
        $newRec->docId = $docRec->id;
        $newRec->folderId = $docRec->folderId;
        $newRec->threadId = $docRec->threadId;
        $newRec->state = 'draft';
        $newRec->classId = $this->getClassId();
        
        if ($closeWith) {
            $newRec->closeWith = $closeWith;
        }
        
        // Създаване на документа
        $id = static::save($newRec);
        $this->logWrite('Автоматично създаване', $id);

        return $id;
    }
    
    
    /**
     * Дали документа има приключени пера в транзакцията му
     */
    public function getClosedItemsInTransaction_($id)
    {
        $rec = $this->fetchRec($id);
        
        // Ако приключващия документ, приключва към друга сделка, то позволяваме
        // да може да се контира дори ако има затворени пера
        if (!empty($rec->closeWith)) {
            
            // Само ако приключващата сделка не е също приключена
            $closeWithState = acc_Items::fetchItem($rec->docClassId, $rec->closeWith)->state;
            if($closeWithState != 'closed'){
                return array();
            }
        }
        
        $closedItems = null;
        
        // Намираме приключените пера от транзакцията
        $transaction = $this->getValidatedTransaction($id);
        
        if ($transaction) {
            $closedItems = $transaction->getClosedItems();
        }
        
        // От списъка с приключените пера, премахваме това на приключения документ, така че да може
        // приключването да се оттегля/възстановява въпреки, че има в нея приключено перо
        $dealItemId = acc_Items::fetchItem($rec->docClassId, $rec->docId)->id;
        unset($closedItems[$dealItemId]);
        
        return $closedItems;
    }
    
    
    /**
     * Връща всички документи, които са приключили сделки с подадената сделка
     */
    public static function getClosedWithDeal($dealId)
    {
        $closedDealQuery = self::getQuery();
        $closeClassId = self::getClassId();
        $closedDealQuery->where("#closeWith = {$dealId}");
        $closedDealQuery->where("#classId = {$closeClassId}");
        $closedDealQuery->where("#state = 'active'");
        
        return $closedDealQuery->fetchAll();
    }
    
    
    /**
     * След успешно контиране на документа
     */
    public static function on_AfterRestore($mvc, &$res, $id)
    {
        $mvc->notifyDealUsedForClosure($id);
    }
    
    
    /**
     * След успешно контиране на документа
     */
    public static function on_AfterConto($mvc, &$res, $id)
    {
        $mvc->notifyDealUsedForClosure($id);
    }
    
    
    /**
     * Нотифицира продажбата която е използвана да се приключи продажбата на документа
     */
    private function notifyDealUsedForClosure($id)
    {
        $rec = $this->fetchRec($id);
        
        // Ако ще се приключва с друга продажба
        if (!empty($rec->closeWith) && $rec->state != 'draft') {
            
            // Прехвърляме ги към детайлите на продажбата с която сме я приключили
            $Doc = cls::get($rec->docClassId);
            $Doc->invoke('AfterClosureWithDeal', array($rec->closeWith));
            
            // Записва се в документа, коя сделка с коя е приключена
            if($rec->state == 'active'){
                $updateRec = (object)array('id' => $rec->docId, 'closeWith' => $rec->closeWith);
                $Doc->save_($updateRec);
            }
        }
    }


    /**
     * Намиране на най-големия вальор в треда на приключващия документ
     *
     * @param stdClass $rec
     * @return date $dates
     */
    protected function getBiggestValiorInDeal($rec, $skipClasses = array())
    {
        // Намира се най-големия вальор от документите свързани към сделката
        $firstDoc =  doc_Threads::getFirstDocument($rec->threadId);
        $jRecs = acc_Journal::getEntries(array($firstDoc->className, $firstDoc->that));
        if(countR($skipClasses)){
            $jRecs = array_filter($jRecs, function($a) use ($skipClasses) {return !in_array($a->docType, $skipClasses);});
        }
        $valiors = arr::extractValuesFromArray($jRecs, 'valior');
        if($firstDocValior = $firstDoc->fetchField($firstDoc->valiorFld)){
            $valiors[$firstDocValior] = $firstDocValior;
        }

        if(countR($valiors)) return max($valiors);

        return null;
    }

    
    /**
     * Какъв да е вальора на контировката.
     *    Ако е избрано "конкретен вальор" и има такъв - взима се той
     *    Ако е избрано "дата на създаване" - взима се тя
     *    Ако не е избрано някое от горните - взима се най-големия вальор в сделката
     *
     * Ако няма такъв - датата на създаване
     * Ако намерената дата е в затворен период подменя се с датата на първия незатворен период след нея
     *
     * @param stdClass $rec
     * @return date $date
     */
    public function getValiorDate($rec)
    {
        // При ръчен вальор е с приоритет
        if($rec->valiorStrategy == 'manual' && !empty($rec->valior)) {
            $date = $rec->valior;
        } elseif($rec->valiorStrategy == 'createdOn'){
            $date = $rec->createdOn;
        } else {
            $date = $this->getBiggestValiorInDeal($rec);
        }

        if(empty($date)){
            $date = $rec->createdOn;
        }

        // Ако датата не е свободна, взима се първата свободна
        $date =  acc_Periods::getNextAvailableDateIfNeeded($date);

        // и връщаме намерената дата
        return $date;
    }


    /**
     * Връща разликата с която ще се приключи сделката
     *
     * @param mixed $threadId - ид на нишката или core_ObjectReference
     *                        към първия документ в нишката
     *
     * @return float $amount - разликата на платеното и експедираното
     */
    protected function getClosedDealAmount($threadId)
    {
        $firstDoc = doc_Threads::getFirstDocument($threadId);
        $jRecs = acc_Journal::getEntries(array($firstDoc->getInstance(), $firstDoc->that));

        $cost = acc_Balances::getBlAmounts($jRecs, $this->incomeAndCostAccounts['debit'], 'debit')->amount;
        $inc = acc_Balances::getBlAmounts($jRecs, $this->incomeAndCostAccounts['credit'], 'credit')->amount;

        // Разликата между платеното и доставеното
        return $inc - $cost;
    }


    /**
     * Колко ще бъде разликата между прихода и разхода
     *
     * @param $rec
     * @return int|mixed
     */
    protected function getLiveAmount($rec)
    {
        $transactionSource = cls::getInterface('acc_TransactionSourceIntf', $this);
        $transaction = $transactionSource->getTransaction($rec);
        $cost = $inc = 0;

        if(is_array($transaction->entries)){
            foreach ($transaction->entries as $entry){
                if($entry['debit'][0] == $this->incomeAndCostAccounts['debit']){
                    $cost += $entry['amount'];
                }

                if($entry['credit'][0] == $this->incomeAndCostAccounts['credit']){
                    $inc += $entry['amount'];
                }
            }
        }

        return $inc - $cost;
    }


    /**
     * Взимане на грешка в бутона за контиране
     */
    protected static function on_AfterGetContoBtnErrStr($mvc, &$res, $rec)
    {
        if(empty($res)){
            $firstDoc = doc_Threads::getFirstDocument($rec->threadId);
            if($firstDoc->isInstanceOf('deals_DealMaster')){

                // Ако се приключва сделка с валута различна от BGN и EUR
                $firstDocCurrencyCode = $firstDoc->fetchField('currencyId');
                if($firstDocCurrencyCode != 'BGN'){

                    // Ако се приключва продажба проверката ще се прави САМО ако няма обратни платежни документи
                    if($firstDoc->isInstanceOf('sales_Sales')){
                        $countRko = cash_Rko::count("#threadId = {$rec->threadId} AND #state = 'active' AND #isReverse = 'yes'");
                        $countSbds = bank_SpendingDocuments::count("#threadId = {$rec->threadId} AND #state = 'active' AND #isReverse = 'yes'");
                        if(!$countRko && !$countSbds) return;
                    }

                    $skipClasses = array(acc_RatesDifferences::getClassId());
                    $biggestValior = $mvc->getBiggestValiorInDeal($rec, $skipClasses);

                    $setupClass = $firstDoc->isInstanceOf('sales_Sales') ? 'sales_Setup' : 'purchase_Setup';
                    $accDay = acc_Setup::get('DATE_FOR_INVOICE_DATE') + $setupClass::get('CURRENCY_CLOSE_AFTER_ACC_DATE');
                    $firstDayOfMonth = date('Y-m-01') . " 23:59:59";


                    $today = dt::today();
                    $accDayPadded = str_pad($accDay, 2, '0', STR_PAD_LEFT);
                    $nextMonthAfterBiggestValior = dt::addMonths(1, $biggestValior, false);
                    $nextAccDateValior = dt::mysql2verbal($nextMonthAfterBiggestValior, "Y-m-{$accDayPadded}");

                    // Ако най-големия вальор не е в миналия месец или деня е преди нужния за осчетоводяване сетва се грешка
                    if($biggestValior >= $firstDayOfMonth || $today < $nextAccDateValior){
                        $biggestValior = dt::mysql2verbal($biggestValior, 'd.m.Y');
                        $res = "Не може да се приключи валутна сделка, преди|* {$accDay} |число на месеца следващ най-големия вальор на сделката|*: {$biggestValior}!";
                    }
                }
            }
        }
    }
}
