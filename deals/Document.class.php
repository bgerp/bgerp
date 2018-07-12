<?php


/**
 * Базов клас за наследяване документи свързани със сделките
 *
 *
 * @category  bgerp
 * @package   deals
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
abstract class deals_Document extends deals_PaymentDocument
{
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'valior, title=Документ, fromContainerId, currencyId=Валута, folderId, amount, state, createdOn, createdBy';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    public $rowToolsSingleField = 'title';
    
    
    /**
     * Полета свързани с цени
     */
    public $priceFields = 'amount,amountDeal,rate';
    
    
    /**
     * Дали в листовия изглед да се показва бутона за добавяне
     */
    public $listAddBtn = false;
    
    
    /**
     * Поле за филтриране по дата
     */
    public $filterDateField = 'createdOn, valior,modifiedOn';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'operationSysId,name,dealId,dealHandler,currencyId,description,contragentId,contragentClassId';
    
    
    /**
     * Кой може да избира ф-ра по документа?
     */
    public $canSelectinvoice = 'cash, ceo, purchase, sales, acc';
    
    
    /**
     * @param core_Mvc $mvc
     */
    protected static function addDocumentFields(core_Mvc $mvc)
    {
        $mvc->FLD('operationSysId', 'varchar', 'caption=Операция,input=hidden');
        $mvc->FLD('valior', 'date(format=d.m.Y)', 'caption=Вальор,mandatory');
        $mvc->FLD('name', 'varchar(255)', 'caption=Име,mandatory');
        $mvc->FLD('dealId', 'key(mvc=findeals_Deals,select=dealName,allowEmpty)', 'caption=Сделка,input=none');
        $mvc->FLD('amount', 'double(decimals=2)', 'caption=Платени,mandatory,summary=amount');
        $mvc->FNC('dealFolderId', 'key2(mvc=doc_Folders, restrictViewAccess=yes,coverInterface=crm_ContragentAccRegIntf,allowEmpty)', 'caption=Насрещна сделка->Папка,mandatory,input,silent,removeAndRefreshForm=dealHandler|currencyId|rate|amountDeal|dealId');
        $mvc->FNC('dealHandler', 'varchar', 'caption=Насрещна сделка->Сделка,mandatory,input,silent,removeAndRefreshForm=currencyId|rate|amountDeal|dealId');
        $mvc->FLD('amountDeal', 'double(decimals=2)', 'caption=Насрещна сделка->Заверени,mandatory,input=none');
        $mvc->FLD('currencyId', 'key(mvc=currency_Currencies, select=code)', 'caption=Валута->Код,input=none');
        $mvc->FLD('rate', 'double(decimals=5)', 'caption=Валута->Курс,input=none');
        $mvc->FLD('description', 'richtext(bucket=Notes,rows=6)', 'caption=Допълнително->Бележки');
        $mvc->FLD('creditAccount', 'customKey(mvc=acc_Accounts,key=systemId,select=systemId)', 'input=none');
        $mvc->FLD('debitAccount', 'customKey(mvc=acc_Accounts,key=systemId,select=systemId)', 'input=none');
        $mvc->FLD('contragentId', 'int', 'input=hidden,notNull');
        $mvc->FLD('contragentClassId', 'key(mvc=core_Classes,select=name)', 'input=hidden,notNull');
        $mvc->FLD('state', 'enum(draft=Чернова, active=Контиран, rejected=Оттеглен,stopped=Спряно)', 'caption=Статус, input=none');
        $mvc->FLD('isReverse', 'enum(no,yes)', 'input=none,notNull,value=no');
        
        $mvc->setDbIndex('valior');
    }
    
    
    /**
     *  Обработка на формата за редакция и добавяне
     */
    public static function on_AfterPrepareEditForm($mvc, $res, $data)
    {
        $folderId = $data->form->rec->folderId;
        $form = &$data->form;
        $rec = &$form->rec;
        
        $contragentId = doc_Folders::fetchCoverId($folderId);
        $contragentClassId = doc_Folders::fetchField($folderId, 'coverClass');
        $form->setDefault('contragentId', $contragentId);
        $form->setDefault('contragentClassId', $contragentClassId);
        
        // Поставяме стойности по подразбиране
        $form->setDefault('valior', dt::today());
        
        expect($origin = $mvc->getOrigin($form->rec));
        expect($origin->haveInterface('bgerp_DealAggregatorIntf'));
        $form->rec->originId = $origin->fetchField('containerId');
        
        $dealInfo = $origin->getAggregateDealInfo();
        expect(count($dealInfo->get('allowedPaymentOperations')));
        
        // Използваме помощната функция за намиране името на контрагента
        if (empty($form->rec->id)) {
            $form->setDefault('description', "Към документ #{$origin->getHandle()}");
            $form->setDefault('dealFolderId', $origin->fetchField('folderId'));
        } elseif (isset($rec->dealId)) {
            $form->setDefault('dealHandler', $rec->dealId);
            $form->setDefault('dealFolderId', findeals_Deals::fetchField($rec->dealId, 'folderId'));
        }
        
        if (isset($rec->dealFolderId)) {
            $form->setOptions('dealHandler', $mvc->getDealOptions($rec->dealFolderId));
        }
        
        $form->dealInfo = $dealInfo;
        $form->setDefault('operationSysId', $mvc::$operationSysId);
        $form->setField('amount', "unit=|*{$dealInfo->get('currency')} |по сделката");
        
        if (isset($rec->dealHandler)) {
            if (strpos($rec->dealHandler, 'new|') === false) {
                $doc = new core_ObjectReference('findeals_Deals', $rec->dealHandler);
                
                $form->rec->currencyId = currency_Currencies::getIdByCode($doc->fetchField('currencyId'));
                $form->setField('amountDeal', "unit=|*{$doc->fetchField('currencyId')}");
                if ($form->rec->currencyId != currency_Currencies::getIdByCode($origin->fetchField('currencyId'))) {
                    $form->setField('amountDeal', 'input');
                }
                
                $rec->dealId = findeals_Deals::fetchField($doc->that, 'id');
            } else {
                $rec->currencyId = currency_Currencies::getIdByCode($dealInfo->get('currency'));
                unset($rec->amountDeal, $rec->dealId);
            }
        } else {
            unset($rec->amountDeal, $rec->dealId);
        }
    }
    
    
    /**
     * Кои са наличните опции за сделки
     *
     * @param int $folderId
     *
     * @return array $options
     */
    protected function getDealOptions($folderId)
    {
        $options = $dealOptions = $accOptionsFiltered = array();
        
        // Има ли активни ф. сделки в избраната папка
        $fQuery = findeals_Deals::getQuery();
        $fQuery->where("#folderId = {$folderId} AND #state = 'active'");
        while ($fRec = $fQuery->fetch()) {
            $dealOptions[$fRec->id] = findeals_Deals::getTitleById($fRec, false);
        }
        
        // Ако има се добавят в техен раздел
        if (count($dealOptions)) {
            $options['deals'] = (object) array('title' => tr('Активни фин. сделки в папката'), 'group' => true);
            $options += $dealOptions;
        }
        
        // Кои са дефолтните сметки по които може да се създават ф. сделки
        $accOptions = findeals_Deals::getDefaultAccountOptions();
        foreach ($accOptions as $k => $v) {
            if (is_object($v)) {
                continue;
            }
            $accOptionsFiltered["new|{$k}"] = $v;
        }
        
        // Ако има такива, те се добавят като отделна група
        if (count($accOptionsFiltered)) {
            $options['accs'] = (object) array('title' => tr('Нова фин. сделка по сметка'), 'group' => true);
            $options += $accOptionsFiltered;
        }
        
        if (count($options)) {
            $options = array('' => '') + $options;
        }
        
        return $options;
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     *
     * @param core_Mvc  $mvc
     * @param core_Form $form
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
        if ($form->isSubmitted()) {
            $rec = &$form->rec;
            
            $origin = $mvc->getOrigin($form->rec);
            $currencyId = $origin->fetchField('currencyId');
            $code = currency_Currencies::getCodeById($rec->currencyId);
            
            if ($code == $currencyId) {
                $rec->amountDeal = $rec->amount;
            }
            
            if ($msg = currency_CurrencyRates::checkAmounts($rec->amount, $rec->amountDeal, $rec->valior, $currencyId, $code)) {
                $form->setError('amount', $msg);
            }
            
            if (strpos($rec->dealHandler, 'new|') !== false) {
                list(, $accountId) = explode('|', $rec->dealHandler);
                $accountSysId = acc_Accounts::fetchField($accountId, 'systemId');
                $Cover = doc_Folders::getCover($rec->dealFolderId);
                
                $origin = $mvc->getOrigin($rec);
                $dealInfo = $origin->getAggregateDealInfo();
                $params = array('valior' => $rec->valior, 'currencyCode' => $dealInfo->get('currency'));
                if ($rec->dealId = findeals_Deals::createDraft($Cover->getClassId(), $Cover->that, $accountSysId, $params)) {
                    findeals_Deals::conto($rec->dealId);
                }
            } else {
                $rec->dealId = $rec->dealHandler;
            }
        }
        
        $mvc->invoke('AfterInputDocumentEditForm', array($form));
    }
    
    
    /**
     * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
     */
    public function getDocumentRow($id)
    {
        $rec = $this->fetch($id);
        $row = new stdClass();
        $row->title = $this->singleTitle . " №{$id}";
        $row->authorId = $rec->createdBy;
        $row->author = $this->getVerbal($rec, 'createdBy');
        $row->state = $rec->state;
        $row->recTitle = $row->title;
        
        return $row;
    }
    
    
    /**
     * Може ли документа може да се добави в посочената папка?
     *
     * @param $folderId int ид на папката
     *
     * @return bool
     */
    public static function canAddToFolder($folderId)
    {
        return false;
    }
    
    
    /**
     * Проверка дали нов документ може да бъде добавен в
     * посочената нишка
     *
     * @param int $threadId key(mvc=doc_Threads)
     *
     * @return bool
     */
    public static function canAddToThread($threadId)
    {
        $firstDoc = doc_Threads::getFirstDocument($threadId);
        $docState = $firstDoc->fetchField('state');
        
        if (($firstDoc->haveInterface('bgerp_DealAggregatorIntf') && $docState == 'active')) {
            // Ако няма позволени операции за документа не може да се създава
            $dealInfo = $firstDoc->getAggregateDealInfo();
            
            // Ако няма позволени операции за документа не може да се създава
            $operations = $dealInfo->get('allowedPaymentOperations');
            
            return isset($operations[static::$operationSysId]) ? true : false;
        }
        
        return false;
    }
    
    
    /**
     *  Обработки по вербалното представяне на данните
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        $row->title = $mvc->getHyperlink($rec->id, true);
        
        if ($fields['-single']) {
            $row->nextHandle = findeals_Deals::getHyperlink($rec->dealId);
            $origin = $mvc->getOrigin($rec->id);
            $row->dealHandle = $origin->getHyperlink();
            $row->dealCurrencyId = $origin->fetchField('currencyId');
        }
    }
    
    
    /**
     * Имплементация на @link bgerp_DealIntf::getDealInfo()
     *
     * @param int|object $id
     *
     * @return bgerp_iface_DealAggregator
     *
     * @see bgerp_DealIntf::getDealInfo()
     */
    public function pushDealInfo($id, &$aggregator)
    {
    }
}
