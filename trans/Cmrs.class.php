<?php


/**
 * Клас 'trans_Cmrs'
 *
 * Документ за ЧМР товарителници
 *
 *
 * @category  bgerp
 * @package   trans
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class trans_Cmrs extends core_Master
{
    /**
     * Заглавие
     */
    public $title = 'ЧМР-та';
    
    
    /**
     * Абревиатура
     */
    public $abbr = 'CMR';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, trans_Wrapper,plg_Clone,doc_DocumentPlg, plg_Printing, plg_Search, doc_ActivatePlg, doc_EmailCreatePlg';
    
    
    /**
     * Кой може да го клонира?
     */
    public $canClonerec = 'ceo, trans';
    
    
    /**
     * Кой може да го вижда?
     */
    public $canSingle = 'ceo, trans';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo, trans';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo, trans';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo, trans';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'cmrNumber=ЧМР №,title=ЧМР, originId=Експедиция, folderId, state,createdOn, createdBy';
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'ЧМР';
    
    
    /**
     * Файл за единичния изглед
     */
    public $singleLayoutFile = 'trans/tpl/SingleLayoutCMR.shtml';
    
    
    /**
     * Икона за единичния изглед
     */
    public $singleIcon = 'img/16/lorry_go.png';
    
    
    /**
     * Групиране на документите
     */
    public $newBtnGroup = '4.7|Логистика';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'cmrNumber,consigneeData,deliveryPlace,loadingDate,cariersData,vehicleReg,natureofGoods,successiveCarriers,documentsAttached';
    
    
    /**
     * Дали в листовия изглед да се показва бутона за добавяне
     */
    public $listAddBtn = false;
    
    
    /**
     * Кои редове да са компресирани
     */
    const NUMBER_GOODS_ROWS = 4;
    
    
    /**
     * Дефолтен брой копия при печат
     *
     * @var int
     */
    public $defaultCopiesOnPrint = 5;
    
    
    /**
     * Полета, които при клониране да не са попълнени
     *
     * @see plg_Clone
     */
    public $fieldsNotToClone = 'cmrNumber,loadingDate';
    
    
    /**
     * Може ли да се редактират активирани документи
     */
    public $canEditActivated = true;
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('cmrNumber', 'varchar(12)', 'caption=ЧМР №,mandatory');
        $this->FLD('senderData', 'text(rows=5)', 'caption=1. Изпращач,mandatory');
        $this->FLD('consigneeData', 'text(rows=5)', 'caption=2. Получател,mandatory');
        $this->FLD('deliveryPlace', 'text(rows=2)', 'caption=3. Разтоварен пункт,mandatory');
        $this->FLD('loadingPlace', 'text(rows=2)', 'caption=4. Товарен пункт,mandatory');
        $this->FLD('loadingDate', 'date', 'caption=4. Дата на товарене,mandatory');
        $this->FLD('documentsAttached', 'varchar', 'caption=5. Приложени документи');
        $this->FLD('goodsData', 'blob(1000000, serialize, compress)', 'input=none,column=none,single=none');
        
        $this->FLD('class', 'varchar(12)', 'caption=ADR->Клас,autohide');
        $this->FLD('number', 'varchar(12)', 'caption=ADR->Цифра,autohide');
        $this->FLD('letter', 'varchar(12)', 'caption=ADR->Буква,autohide');
        $this->FLD('natureofGoods', 'varchar(12)', 'caption=ADR->Вид на стоката,autohide');
        
        $this->FLD('senderInstructions', 'text(rows=2)', 'caption=Допълнително->13. Указания на изпращача');
        $this->FLD('instructionsPayment', 'text(rows=2)', 'caption=Допълнително->14. Предп. плащане навло');
        $this->FLD('carragePaid', 'varchar(12)', 'caption=Допълнително->Предплатено');
        $this->FLD('sumPaid', 'varchar(12)', 'caption=Допълнително->Дължимо');
        
        $this->FLD('cashOnDelivery', 'varchar', 'caption=Допълнително->15. Наложен платеж');
        $this->FLD('cariersData', 'text(rows=5)', 'caption=Допълнително->16. Превозвач');
        $this->FLD('vehicleReg', 'varchar', 'caption=МПС рег. №');
        $this->FLD('successiveCarriers', 'text(rows=2)', 'caption=Допълнително->17. Посл. превозвачи');
        $this->FLD('specialagreements', 'text(rows=2)', 'caption=Допълнително->19. Спец. споразумения');
        $this->FLD('establishedPlace', 'text(rows=2)', 'caption=21. Изготвена в');
        $this->FLD('establishedDate', 'date', 'caption=21. Изготвена на');
        
        $this->setDbUnique('cmrNumber');
    }
    
    
    /**
     * Изпълнява се след извличане на запис чрез ->fetch()
     */
    protected static function on_AfterRead($mvc, $rec)
    {
        // Разпъване на компресираните полета
        if (is_array($rec->goodsData)) {
            foreach ($rec->goodsData as $field => $value) {
                $rec->{$field} = $value;
            }
        }
    }
    
    
    /**
     * Преди запис в модела, компактираме полетата
     */
    public function save_(&$rec, $fields = null, $mode = null)
    {
        $saveGoodsData = false;
        $goodsData = array();
        
        $arr = (array) $rec;
        $compressFields = $this->getCompressFields();
        
        // Компресиране на нужните полета
        foreach ($arr as $fld => $value) {
            if (in_array($fld, $compressFields)) {
                $goodsData[$fld] = ($value !== '') ? $value : null;
                $saveGoodsData = true;
            }
        }
        
        if ($saveGoodsData === true) {
            $rec->goodsData = $goodsData;
            
            if (is_array($fields)) {
                $fields['goodsData'] = 'goodsData';
            }
        }
        
        $res = parent::save_($rec, $fields, $mode);
        
        if (isset($rec->originId)) {
            doc_DocumentCache::invalidateByOriginId($rec->originId);
        }
        
        return $res;
    }
    
    
    /**
     * Кои полета ще се компресират
     *
     * @return array
     */
    private function getCompressFields()
    {
        $res = array();
        foreach (range(1, self::NUMBER_GOODS_ROWS) as $i) {
            foreach (array('mark', 'numOfPacks', 'methodOfPacking', 'natureOfGoods', 'statNum', 'grossWeight', 'volume') as $fld) {
                $res[] = "{$fld}{$i}";
            }
        }
        
        return $res;
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass     $data
     */
    public function prepareEditForm_($data)
    {
        $data = parent::prepareEditForm_($data);
        $form = &$data->form;
        
        // Разпъване на компресираните полета
        foreach (range(1, self::NUMBER_GOODS_ROWS) as $i) {
            $autohide = ($i === 1) ? '' : 'autohide';
            $after = ($i === 1) ? 'documentsAttached' : ('volume' . ($i - 1));
            $mandatory = ($i === 1) ? 'mandatory' : '';
            
            $form->FLD("mark{$i}", 'varchar', "after={$after},caption={$i}. Информация за стоката->6. Знаци и Номера,{$autohide}");
            $form->FLD("numOfPacks{$i}", 'varchar', "after=mark{$i},caption={$i}. Информация за стоката->7. Брой колети,{$autohide}");
            $form->FLD("methodOfPacking{$i}", 'varchar', "after=methodOfPacking{$i},caption={$i}. Информация за стоката->8. Вид опаковка,{$autohide}");
            $form->FLD("natureOfGoods{$i}", 'varchar', "{$mandatory},after=natureOfGoods{$i},caption={$i}. Информация за стоката->9. Вид стока,{$autohide}");
            $form->FLD("statNum{$i}", 'varchar', "after=statNum{$i},caption={$i}. Информация за стоката->10. Статистически №,{$autohide}");
            $form->FLD("grossWeight{$i}", 'varchar', "after=grossWeight{$i},caption={$i}. Информация за стоката->11. Тегло Бруто,{$autohide}");
            $form->FLD("volume{$i}", 'varchar', "after=volume{$i},caption={$i}. Информация за стоката->12. Обем,{$autohide}");
        }
        
        return $data;
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = &$data->form;
        $rec = &$form->rec;
        
        // Зареждане на дефолти от ориджина
        if (isset($rec->originId) && !isset($rec->id)) {
            $mvc->setDefaultsFromShipmentOrder($rec->originId, $form);
            
            if ($senderInstructions = trans_Setup::get('CMR_SENDER_INSTRUCTIONS')) {
                $form->setDefault('senderInstructions', $senderInstructions);
            }
            
            $threadId = doc_Containers::fetchField($rec->originId, 'threadId');
            $invoicesInThread = deals_Helper::getInvoicesInThread($threadId);
            if (countR($invoicesInThread) == 1) {
                $iRec = sales_Invoices::fetch('#containerId =' . key($invoicesInThread));
                $iVerbal = sales_Invoices::recToVerbal($iRec, 'date,number');
                $documentsAttached = "INVOICE {$iVerbal->number} / {$iVerbal->date}";
                $form->setDefault('documentsAttached', $documentsAttached);
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
        if ($form->isSubmitted()) {
            
            // Подсигуряване че винаги след редакция ще е в чернова
            if ($form->cmd == 'save') {
                $form->rec->state = 'draft';
            }
        }
    }
    
    
    /**
     * Зарежда дефолтни данни от формата
     *
     * @param int       $originId - ориджин
     * @param core_Form $form     - форма
     *
     * @return void
     */
    private function setDefaultsFromShipmentOrder($originId, &$form)
    {
        expect($origin = doc_Containers::getDocument($originId));
        $sRec = $origin->fetch();
        $form->setDefault('cmrNumber', $sRec->id);
        $lData = $origin->getLogisticData();
        
        // Всичките дефолтни данни трябва да са на английски
        core_Lg::push('en');
        
        // Информация за изпращача
        $ownCompanyId = crm_Setup::get('BGERP_OWN_COMPANY_ID', true);
        $contragentData = cls::get($sRec->contragentClassId)->getContragentData($sRec->contragentId);
        $hideOurEori = false;
        if(empty($contragentData->eori) && drdata_Countries::isEu($contragentData->countryId)){
            $hideOurEori = true;
        }

        // Информация за получателя и изпращача
        $consigneeData = $this->getDefaultContragentData($sRec->contragentClassId, $sRec->contragentId, false);
        $senderData = $this->getDefaultContragentData('crm_Companies', $ownCompanyId, true, $hideOurEori);

        // Място на товарене / Разтоварване
        $loadingPlace = $lData['fromPCode'] . ' ' .  transliterate($lData['fromPlace']) . ', ' . $lData['fromCountry'];
        $deliveryPlace = $lData['toPCode'] . ' ' .  transliterate($lData['toPlace']) . ', ' . $lData['toCountry'];
        
        // Има ли общо тегло в ЕН-то
        $weight = ($sRec->weightInput) ? $sRec->weightInput : $sRec->weight;
        if (!empty($weight)) {
            Mode::push('text', 'plain');
            $weight = core_Type::getByName('cat_type_Weight')->toVerbal($weight);
            Mode::pop('text');
            $form->setDefault('grossWeight1', $weight);
        }
        
        // Има ли общ обем в ЕН-то
        $volume = ($sRec->volumeInput) ? $sRec->volumeInput : $sRec->volume;
        if (!empty($weight)) {
            Mode::push('text', 'plain');
            $volume = core_Type::getByName('cat_type_Volume')->toVerbal($volume);
            Mode::pop('text');
            $form->setDefault('volume1', $volume);
        }
        
        core_Lg::pop();
        
        // Задаване на дефолтните полета
        $form->setDefault('senderData', $senderData);
        $form->setDefault('consigneeData', $consigneeData);
        $form->setDefault('deliveryPlace', $deliveryPlace);
        $form->setDefault('loadingPlace', $loadingPlace);
        $form->setDefault('loadingDate', $lData['loadingTime']);
        
        // Информация за превозвача
        if (isset($sRec->lineId)) {
            $lineRec = trans_Lines::fetch($sRec->lineId);
            if (isset($lineRec->forwarderId)) {
                $carrierData = $this->getDefaultContragentData('crm_Companies', $lineRec->forwarderId, true, true);
                $form->setDefault('cariersData', $carrierData);
            }
            
            if (isset($lineRec->vehicle)) {
                if ($vehicleRec = trans_Vehicles::fetch(array("#name = '[#1#]'", $lineRec->vehicle))) {
                    $form->setDefault('vehicleReg', $vehicleRec->number);
                }
            }
        }
        
        // Има ли общ брой палети
        if (!empty($sRec->palletCountInput)) {
            $collets = core_Type::getByName('int')->toVerbal($sRec->palletCountInput);
            $collets .= ' PALLETS';
            $form->setDefault('numOfPacks1', $collets);
        }
    }
    
    
    /**
     * Информацията за контрагента
     *
     * @param mixed $contragentClassId - клас на контрагента
     * @param int   $contragentId      - контрагент ид
     * @param bool  $translate         - превод на името на контрагента
     * @param bool  $hideEori          - дали да се скрие ЕОРИ номера на контрагента
     *
     * @return string - информация за контрагента
     */
    private function getDefaultContragentData($contragentClassId, $contragentId, $translate = true, $hideEori = false)
    {
        $Contragent = cls::get($contragentClassId);
        $verbal = $Contragent->fetch($contragentId, 'pCode,place,address');
        $contragentAddress = ($verbal->address) ? (transliterate(tr($verbal->address)) . "\n") : '';
        $contragentAddress .= ($verbal->pCode) ? $verbal->pCode : '';
        $contragentAddress .= ($verbal->place) ? (' ' . transliterate(tr($verbal->place))) : '';
        
        $contragentCountry = $Contragent->getVerbal($contragentId, 'country');
        $contragentName = ($translate === true) ? transliterate(tr($Contragent->fetchField($contragentId, 'name'))) : $Contragent->getVerbal($contragentId, 'name');
        $cData = cls::get($contragentClassId)->getContragentData($contragentId);

        $contragentNumbers = '';
        if(!$hideEori && !empty($cData->eori)){
            $contragentNumbers .= "EORI №: {$cData->eori}";
        }

        $contragentData = trim($contragentName) . "\n" . trim($contragentAddress) . "\n" . trim($contragentCountry) . "\n" . trim($contragentNumbers);
        $contragentData = str_replace(',,', ',', $contragentData);

        return $contragentData;
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
        $row->title = $mvc->getLink($rec->id, 0);
        
        if (isset($row->originId)) {
            if (!Mode::isReadOnly()) {
                try {
                    $origin = doc_Containers::getDocument($rec->originId);
                    $row->originId = $origin->getInstance()->getLink($origin->that, 0);
                } catch (core_exception_Expect $e) {
                    $row->originId = "<span class='red'>" . tr('Проблем с показването') . '</span>';
                }
            } else {
                unset($row->originId);
            }
        }
        
        if (isset($fields['-single'])) {
            
            // Вербализиранре на компресираните полета
            if (is_array($rec->goodsData)) {
                foreach ($rec->goodsData as $field => $value) {
                    if (isset($value)) {
                        $row->{$field} = core_Type::getByName('varchar')->toVerbal($value);
                    }
                }
            }
            
            $row->basicColor = '#000';
            if (!empty($rec->establishedDate)) {
                $row->establishedDate = dt::mysql2verbal($rec->loadingDate, 'd.m.Y');
            }
            
            if (!empty($rec->loadingDate)) {
                $row->loadingDate = dt::mysql2verbal($rec->loadingDate, 'd.m.y');
            }
        }
    }
    
    
    /**
     * Изпълнява се след подготвянето на формата за филтриране
     */
    protected static function on_AfterPrepareListFilter($mvc, &$res, $data)
    {
        $data->listFilter->view = 'horizontal';
        $data->listFilter->showFields = 'search';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', array($mvc, 'list'), 'id=filter', 'ef_icon = img/16/funnel.png');
    }
    
    
    /**
     * Документа не може да бъде начало на нишка; може да се създава само в съществуващи нишки
     */
    public static function canAddToFolder($folderId)
    {
        return false;
    }
    
    
    /**
     * Проверка дали нов документ може да бъде добавен в посочената нишка
     */
    public static function canAddToThread($threadId)
    {
        $firstDoc = doc_Threads::getFirstDocument($threadId);
        if ($firstDoc && $firstDoc->isInstanceOf('deals_DealBase')) {
            $state = $firstDoc->fetchField('state');
            if (in_array($state, array('active', 'closed', 'pending'))) {
                
                return true;
            }
        }
        
        return false;
    }
    
    
    /**
     * @see doc_DocumentIntf::getDocumentRow()
     */
    public function getDocumentRow_($id)
    {
        expect($rec = $this->fetch($id));
        $title = $this->getRecTitle($rec);
        
        $row = (object) array('title' => $title,
            'authorId' => $rec->createdBy,
            'author' => $this->getVerbal($rec, 'createdBy'),
            'state' => $rec->state,
            'recTitle' => $title
        );
        
        return $row;
    }
    
    
    /**
     * Връща тялото на имейла генериран от документа
     *
     * @see email_DocumentIntf
     *
     * @param int  $id      - ид на документа
     * @param bool $forward
     *
     * @return string - тялото на имейла
     */
    public function getDefaultEmailBody($id, $forward = false)
    {
        $tpl = new ET(tr('Моля запознайте се с нашето|* |ЧМР|*') . ': #[#handle#]');
        $handle = $this->getHandle($id);
        $tpl->append($handle, 'handle');
        
        return $tpl->getContent();
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if ($action == 'add' && isset($rec->originId)) {
            $origin = doc_Containers::getDocument($rec->originId);
            if (!$origin->isInstanceOf('store_ShipmentOrders')) {
                $requiredRoles = 'no_one';
            } else {
                $state = $origin->fetchField('state');
                if (!in_array($state, array('active', 'pending'))) {
                    $requiredRoles = 'no_one';
                }
            }
        }
    }
    
    
    /**
     * Добавя ключови думи за пълнотекстово търсене
     */
    protected static function on_AfterGetSearchKeywords($mvc, &$res, $rec)
    {
        $fields = $mvc->getCompressFields();
        
        // Допълване на ключовите думите
        foreach ($fields as $fld) {
            if (strpos($fld, 'natureOfGoods') !== false || strpos($fld, 'statNum') !== false) {
                if (!empty($rec->{$fld})) {
                    $res .= ' ' . plg_Search::normalizeText($rec->{$fld});
                }
            }
        }
    }
    
    
    /**
     * Метод по подразбиране, за връщане на състоянието на документа в зависимот от класа/записа
     *
     * @param core_Master $mvc
     * @param NULL|string $res
     * @param NULL|int    $id
     * @param NULL|bool   $hStatus
     *
     * @see doc_HiddenContainers
     */
    public function getDocHiddenStatus($id, $hStatus)
    {
        $cid = $this->fetchField($id, 'containerId');
        if (doclog_Documents::fetchByCid($cid, doclog_Documents::ACTION_PRINT)) {
            
            return true;
        }
    }
    
    
    /**
     * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
     */
    public static function getHandle($id)
    {
        $self = cls::get(get_called_class());
        $cmrNumber = $self->fetchField($id, 'cmrNumber');
        $hnd = $self->abbr . $cmrNumber;
        
        return $hnd;
    }
    
    
    /**
     * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
     */
    public static function fetchByHandle($parsedHandle)
    {
        if ($cmrNumber = ltrim($parsedHandle['id'], '0')) {
            $rec = static::fetch("#cmrNumber = '{$cmrNumber}'");
        }
        
        return $rec;
    }
    
    
    /**
     * След рендиране на копия за принтиране
     *
     * @param core_Mvc $mvc     - мениджър
     * @param core_ET  $copyTpl - копие за рендиране
     * @param int      $copyNum - пореден брой на копието за принтиране
     */
    protected static function on_AfterRenderPrintCopy($mvc, &$copyTpl, $copyNum, $rec)
    {
        $head = array(1 => 'Copy for sender', 2 => 'Copy for consignee', 3 => 'Copy for carrier', 4 => 'Copy for second carrier', 5 => 'Copy for sender');
        $colorClass = array(1 => 'cmr-red', 2 => 'cmr-blue', 3 => 'cmr-green');
        $copyTpl->append($copyNum, 'copyNum');
        $copyTpl->append($head[$copyNum], 'copyTitle');
        $copyTpl->append($colorClass[$copyNum], 'colorClass');
    }


    /**
     * Рендиране на изгледа
     */
    public function renderSingleLayout_(&$data)
    {
        // Ако се печата, форсира се английски език винаги, без значение езика от сесията
        if(Mode::is('printing')){
            core_Lg::push('en');
        }

        $tpl = parent::renderSingleLayout_($data);

        if(Mode::is('printing')){
            core_Lg::pop();
        }

        return $tpl;
    }
}
