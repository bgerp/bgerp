<?php


/**
 * Експортиране на фактури по продажби в БН
 *
 * @category  bgerp
 * @package   bnav
 *
 * @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Експорт в БН » Експорт фактури продажби
 */
class bnav_bnavExport_SalesInvoicesExport extends frame2_driver_TableData
{
    /**
     * Кой може да избира драйвъра
     */
    public $canSelectDriver = 'ceo,admin,debug';


    /**
     * Мениджъри за зареждане
     */
    public $loadList = 'Invoices=sales_Invoices';


    /**
     * Работен кеш
     */
    public $cacheParams = array();


    /**
     * Работен кеш
     */
    public $confCache = array();


    /**
     * Ид на държавата България
     */
    public $countryId;


    /**
     * Брой записи на страница
     *
     * @var int
     */
    protected $listItemsPerPage = 30;


    /**
     * Кои полета може да се променят от потребител споделен към справката, но нямащ права за нея
     */
    protected $changeableFields;

    /**
     * Кои полета са за избор на период
     */
    protected $periodFields = 'from,to';


    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        $fieldset->FLD('from', 'date', 'caption=От,after=title,single=none,mandatory');
        $fieldset->FLD('to', 'date', 'caption=До,after=from,single=none,mandatory');

        $fieldset->FLD('currencyType', 'set(yes = )', 'caption=Експорт във валута,after=to,input,single=none');


        $fieldset->FNC('dealType', 'int', 'caption=Тип сделка,after=currencyType,input=none,single=none,mandatory');
    }


    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param frame2_driver_Proto $Driver
     *                                      $Driver
     * @param embed_Manager $Embedder
     * @param stdClass $data
     */
    protected static function on_AfterPrepareEditForm(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$data)
    {
        $form = $data->form;
        $rec = $form->rec;

        $form->setDefault('currencyType', null);
    }


    /**
     * След рендиране на единичния изглед
     *
     * @param cat_ProductDriver $Driver
     * @param embed_Manager $Embedder
     * @param core_Form $form
     * @param stdClass $data
     */
    protected static function on_AfterInputEditForm(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$form)
    {
        if ($form->isSubmitted()) {

            // Проверка на периоди
            if (isset($form->rec->from, $form->rec->to) && ($form->rec->from > $form->rec->to)) {
                $form->setError('from,to', 'Началната дата на периода не може да бъде по-голяма от крайната.');
            }
        }
    }


    /**
     * Кои записи ще се показват в таблицата
     *
     * @param stdClass $rec
     * @param stdClass $data
     *
     * @return array
     */
    protected function prepareRecs($rec, &$data = null)
    {
        $recs = array();
        $currencyType = 1;

        //Ако има регистрирана "ОСНОВНА ГРУПА", вадим групите, които са едно ниво под нея
        if (core_Packs::getConfig('bnav')->BASE_GROUP != '') {

            $baseGroupId = (trim(core_Packs::getConfig('bnav')->BASE_GROUP, '|'));
            $gQuery = cat_Groups::getQuery();
            $gQuery->where("#parentId = $baseGroupId");
            if (!$gQuery->count()) {
                cat_Products::logErr("Липсват регистрирани групи в основната група");
                followRetUrl(null, "Липсват регистрирани групи в основната група", 'error');

            }

            //    expect($gQuery->count(), 'Липсват регистрирани групи в основната група');

            //масив с групи, които са едно ниво под основната
            $flGroups = arr::extractValuesFromArray($gQuery->fetchAll(), 'id');

        }

        $sQuery = sales_Invoices::getQuery();

        //  $sQuery->where("#state != 'draft' AND #number IS NOT NULL ");
        $sQuery->where("#state = 'active' OR (#brState = 'active' AND #state = 'rejected')");

        // Ако е посочена начална дата на период
        if ($rec->from) {
            $sQuery->where(array(
                "#date >= '[#1#]'",
                $rec->from . ' 00:00:00'
            ));
        }

        //Крайна дата / 'към дата'
        if ($rec->from) {
            $sQuery->where(array(
                "#date <= '[#1#]'",
                $rec->to . ' 23:59:59'
            ));
        }

        $invoices = array();

        while ($sRec = $sQuery->fetch()) {

            //Масив с фактури от продажбите
            $id = $sRec->id;

            //Състояние
            $state = $sRec->state;
            $brState = $sRec->brState;

            //номер на фактурата
            $number = str_pad($sRec->number, 10, "0", STR_PAD_LEFT);

            //Код на контрагента, така както е експортиран в БН. В случая folderId  на контрагента
            $contragentClassName = core_Classes::getName($sRec->contragentClassId);
            $contragentCode = $contragentClassName::fetch($sRec->contragentId)->folderId;

            //Име на контрагента
            $contragentName = $sRec->contragentName;

            //VAT номер на контрагента
            $contragentVatNo = $sRec->contragentVatNo;

            //Национален номер на контрагента
            $contragentNo = $sRec->uicNo;

            //Тип на плащането
            $paymentType = $sRec->paymentType;

            //Банкова сметка
            $bankAccount = ($paymentType == 'bank') ? $sRec->accountId : null;

            $rec->dealType = self::getDealType($sRec);
            $rec->docType = self::getDocType($sRec);

            if ($rec->currencyType == 'yes') {
                $currencyType = $sRec->rate;
            } else {
                $currencyType = 1;
            }

            //Ако има авансово начисляване на суми по цялата фактура
            if ($sRec->changeAmount || $sRec->dpOperation == 'accrued') {
                $dealValue = $sRec->changeAmount ? $sRec->dealValue : $sRec->dpAmount;

                if (!array_key_exists($id, $recs)) {
                    $recs[$id] = (object)array(

                        'type' => $rec->docType,
                        'dealType' => $rec->dealType,
                        'number' => $number,
                        'date' => $sRec->date,
                        'contragentVatNo' => $contragentVatNo,
                        'contragentNo' => $contragentNo,
                        'contragentName' => $contragentName,
                        'paymentType' => $paymentType,
                        'accountId' => $bankAccount,
                        'accItem' => core_Packs::getConfig('bnav')->FSD_ADVANCE,
                        'currencyId' => $sRec->currencyId,
                        'rate' => $sRec->rate,
                        'dealValue' => $dealValue / $currencyType,
                        'detAmount' => $dealValue / $currencyType,
                        'dpOperation' => $sRec->dpOperation,
                        'dpAmount' => $sRec->dpAmount / $currencyType,
                        'changeAmount' => $sRec->changeAmount / $currencyType,
                        'state' => $state,
                        'brState' => $brState,

                    );
                }
            }

            // Запис в масива
            if (!array_key_exists($id, $invoices)) {
                $invoices[$id] = (object)array(
                    'id' => $id,
                    'type' => $rec->docType,
                    'dealType' => $rec->dealType,
                    'number' => $number,
                    'date' => $sRec->date,
                    'threadId' => $sRec->threadId,
                    'contragentVatNo' => $contragentVatNo,
                    'contragentNo' => $contragentNo,
                    'contragentName' => $contragentName,
                    'paymentType' => $paymentType,
                    'accountId' => $bankAccount,
                    'accItem' => '',
                    'currencyId' => $sRec->currencyId,
                    'rate' => $sRec->rate,
                    'dealValue' => $sRec->dealValue / $currencyType,
                    'state' => $state,
                    'brState' => $brState,
                    'dpOperation' => $sRec->dpOperation,
                    'dpAmount' => $sRec->dpAmount * $sRec->rate,
                    'changeAmount' => $sRec->changeAmount * $sRec->rate,

                );
            }
        }

        $invArr = array_keys($invoices);
        if (empty($invArr)) {
            return $recs;
        }

        $dQuery = sales_InvoiceDetails::getQuery();
        $dQuery->in('invoiceId', $invArr);

        $details = $dQuery->fetchAll();

        $detArr = array();
        //Детайлите на  КИ и ДИ се групират в отделни масиви
        foreach ($details as $dRec) {
            if (($invoices[$dRec->invoiceId]->type != 'Фактура')) {
                $detArr[$dRec->invoiceId][$dRec->id] = $dRec;
            }
        }

        //Проверка за коригирани количества в редовете на КИ и ДИ
        //На тези които имат промяна и се добавят полета changedQuantity или changedPrice
        foreach ($detArr as $k => $v) {
            $sdRec = sales_Invoices::fetch($k);
            if ($sdRec->type != 'invoice') {
                sales_InvoiceDetails::modifyDcDetails($v, $sdRec, cls::get('sales_InvoiceDetails'));
            } else {
                continue;
            }

        }

        foreach ($details as $dRec) {
            //река на артикула от детайла
            $pRec = cat_Products::fetch($dRec->productId);

            //Извличане на счетоводна сметка и код на артикула
            $accItem = null;

            $confBnav = core_Packs::getConfig('bnav');

            if ($pRec->bnavCode && $confBnav->SYCODE_PRODCODE == 'yes') {

                $bnavCodeMarker = null;

                //Проверяваме дали продулта е складируем или услуга
                $const = ($pRec->canStore == 'yes') ? 'FSD_SALES' : 'FSD_SALES_SERVICES';
                //todo
                $accItem = core_Packs::getConfig('bnav')->$const;
                $startPos = $endPos = false;
                $startPos = strpos($pRec->bnavCode, '[') + 1;
                $endPos = strpos($pRec->bnavCode, ']');

                if (strpos($pRec->bnavCode, '[') === false && $endPos === false ){

                    cat_Products::logErr("Некоректен bnavcode за артикул: $pRec->name");
                    $url = toUrl(array('cat_Products', 'single', $pRec->id));
                    $link = ht::createLink($pRec->name, $url);
                    followRetUrl($url, "Некоректен bnavcode за артикул $link", 'error');
                    $bnavCodeMarker = 1;
                }

                $subAccItem = substr($pRec->bnavCode, $startPos, $endPos - $startPos);

                $charToFind = '&';

                $position = strpos($subAccItem, $charToFind);
                if ($position !== false) {
                    $accItem = substr($subAccItem, $position + 1);
                } else {
                    $accItem .= $subAccItem;
                }

                $prodCode = substr($pRec->bnavCode, $endPos + 1);  // +1, за да пропуснем самата скоба

            }

            $id = $dRec->id;

            //Ако има авансово приспадане на суми
            if ($invoices[$dRec->invoiceId]->dpOperation == 'deducted') {

                $accItem = core_Packs::getConfig('bnav')->FSD_ADVANCE;

                $id = $invoices[$dRec->invoiceId]->number;

                if ($rec->currencyType == 'yes') {
                    $currencyType = $invoices[$dRec->invoiceId]->rate;
                } else {
                    $currencyType = 1;
                }

                $recs[$id] = (object)array(
                    'type' => $invoices[$dRec->invoiceId]->type,
                    'dealType' => $rec->dealType,
                    'number' => $invoices[$dRec->invoiceId]->number,
                    'date' => $invoices[$dRec->invoiceId]->date,
                    'contragentVatNo' => $invoices[$dRec->invoiceId]->contragentVatNo,
                    'contragentNo' => $invoices[$dRec->invoiceId]->contragentNo,
                    'contragentName' => $invoices[$dRec->invoiceId]->contragentName,
                    'paymentType' => $invoices[$dRec->invoiceId]->paymentType,
                    'accountId' => $invoices[$dRec->invoiceId]->bankAccount,
                    'accItem' => $accItem,
                    'currencyId' => $invoices[$dRec->invoiceId]->currencyId,
                    'rate' => $invoices[$dRec->invoiceId]->rate,
                    'dealValue' => $invoices[$dRec->invoiceId]->dealValue / $currencyType,
                    'state' => $invoices[$dRec->invoiceId]->state,
                    'brState' => $invoices[$dRec->invoiceId]->brState,
                    'detAmount' => $invoices[$dRec->invoiceId]->dpAmount / $currencyType,
                    'bnavCodeMarker' => $bnavCodeMarker,

                );
                $id = $dRec->id;
            }

            // Редовете на КИ и ДИ , коитпо неямат промяна се прескачат
            if ($invoices[$dRec->invoiceId]->type == $this->confCache->FSD_DOC_DEBIT_NOTE_TYPE ||
                $invoices[$dRec->invoiceId]->type == $this->confCache->FSD_DOC_CREDIT_NOTE_TYPE) {
                if (!$dRec->changedQuantity && !$dRec->changedPrice) {
                    continue;
                }
            }

            //Ако има регистрирана "ОСНОВНА ГРУПА", определяме група на артикула спрямо нея
            if (core_Packs::getConfig('bnav')->BASE_GROUP != '') {

                $gArr = explode('|', trim($pRec->groups, '|'));
                if (empty(array_intersect($gArr, $flGroups))) {

                    $group = 'n.a.';
                } else {
                    if (countR(array_intersect($gArr, $flGroups)) > 2) {
                        cat_Products::logErr("Има регистрирани повече от една група на първо ниво след  ОСНОВНАТА за артикул $pRec->name");
                        $url = toUrl(array('cat_Products', 'single', $pRec->id));
                        $link = ht::createLink($pRec->name, $url);
                        followRetUrl($url, "Артикул $link, участва в повече от една група", 'error');
                    }

                    $group = implode(',', array_intersect($gArr, $flGroups));
                }

            }

            if ($confBnav->SYCODE_PRODCODE == 'no') {

                 $erpCode = $pRec->code ? $pRec->code : 'Art' . $pRec->id;
                 $prodCode = $pRec->bnavCode ? $pRec->bnavCode : $erpCode;
            }

            $measure = cat_UoM::getShortName($dRec->packagingId);
            $detAmount = $dRec->amount;

            // Запис в масива
            if (!array_key_exists($id, $recs)) {

                $recs[$id] = (object)array(
                    'invoice' => $invoices[$dRec->invoiceId],
                    'number' => $invoices[$dRec->invoiceId]->number,
                    'accItem' => $accItem,
                    'prodCode' => $prodCode,
                    'group' => $group,
                    'quantity' => $dRec->quantity,
                    'price' => ($dRec->price * $dRec->quantityInPack) / $currencyType,
                    'detAmount' => $detAmount / $currencyType,
                    'vatAmount' => '',
                    'measure' => $measure,
                    'vat' => cat_Products::getVat($pRec->id, $invoices[$dRec->invoiceId]->date, $invoices[$dRec->invoiceId]->threadId) * 100,
                    'accText' => '',
                    'bnavCodeMarker' => $bnavCodeMarker,
                );

            }
        }

        arr::sortObjects($recs, 'number', 'ASC');

        return $recs;
    }


    /**
     * Връща фийлдсета на таблицата, която ще се рендира
     *
     * @param stdClass $rec
     *                         - записа
     * @param bool $export
     *                         - таблицата за експорт ли е
     *
     * @return core_FieldSet - полетата
     */
    protected function getTableFieldSet($rec, $export = false)
    {
        $fld = cls::get('core_FieldSet');

        $fld->FLD('type', 'varchar', 'caption=Тип на документа');
        $fld->FLD('dealType', 'varchar', 'caption=Тип на сделката');
        $fld->FLD('number', 'varchar', 'caption=Номер на документа,tdClass=centered');
        $fld->FLD('date', 'date', 'caption=Дата');
        $fld->FLD('state', 'varchar', 'caption=Статус');
        $fld->FLD('contragentName', 'varchar', 'caption=Доставчик->Име');
        $fld->FLD('contragentVatNo', 'varchar', 'caption=Доставчик->VAT Код');
        $fld->FLD('contragentNo', 'varchar', 'caption=Доставчик->Нац. Код');
        $fld->FLD('currencyId', 'varchar', 'caption=Валута,tdClass=centered');
        $fld->FLD('rate', 'double', 'caption=Курс на валутата');
        $fld->FLD('dealValue', 'double', 'caption=Обща стойност->без ДДС');
        $fld->FLD('accItem', 'int', 'caption=Сч. с-ка');
        $fld->FLD('prodCode', 'varchar', 'caption=Код на стоката');
        if (core_Packs::getConfig('bnav')->BASE_GROUP != '') {
            $fld->FLD('group', 'varchar', 'caption=Група');
        }
        $fld->FLD('quantity', 'double', 'caption=Количество');
        $fld->FLD('price', 'double', 'caption=Ед цена');
        $fld->FLD('detAmount', 'double', 'caption=Ст. на реда');
        $fld->FLD('measure', 'varchar', 'caption=Мерна единица,tdClass=centered');
        $fld->FLD('vat', 'double', 'caption=% ДДС');
        $fld->FLD('paymentType', 'varchar', 'caption=Плащане');
        $fld->FLD('bankAccount', 'varchar', 'caption=Банкова с-ка');


        return $fld;
    }


    /**
     * Вербализиране на редовете, които ще се показват на текущата страница в отчета
     *
     * @param stdClass $rec
     *                       - записа
     * @param stdClass $dRec
     *                       - чистия запис
     *
     * @return stdClass $row - вербалния запис
     */
    protected function detailRecToVerbal($rec, &$dRec)
    {
        $isPlain = Mode::is('text', 'plain');
        $Int = cls::get('type_Int');
        $Date = cls::get('type_Date');
        $Double = core_Type::getByName('double(decimals=2)');

        $row = new stdClass();
        if ($dRec->invoice) {

            //нулираме стойностите на анулираните фактури
            if ($dRec->invoice->state == 'rejected') {
                $dRec->invoice->dealValue = $dRec->quantity = $dRec->price = $dRec->detAmount = $dRec->vat = 0;
            }

            $row->type = $dRec->invoice->type;
            $row->dealType = $dRec->invoice->dealType;
            $row->number = $dRec->invoice->number;
            $row->date = $Date->toVerbal($dRec->invoice->date);
            $row->state = $dRec->invoice->state;
            $row->contragentName = $dRec->invoice->contragentName;
            $row->contragentVatNo = $dRec->invoice->contragentVatNo;
            $row->contragentNo = $dRec->invoice->contragentNo;
            $row->accItem = $dRec->accItem;
            $row->currencyId = $dRec->invoice->currencyId;
            $row->rate = core_Type::getByName('double(decimals=4)')->toVerbal($dRec->invoice->rate);
            $row->dealValue = $Double->toVerbal($dRec->invoice->dealValue);
            $row->prodCode = $dRec->prodCode;
            $row->group = cat_Groups::getTitleById($dRec->group);
            $row->quantity = core_Type::getByName('double(decimals=3)')->toVerbal($dRec->quantity);
            $row->price = core_Type::getByName('double(decimals=6)')->toVerbal($dRec->price);
            $row->detAmount = $Double->toVerbal($dRec->detAmount);
            $row->measure = $dRec->measure;
            $row->vat = $dRec->vat;
            $row->paymentType = $dRec->invoice->paymentType;
            if (isset($dRec->invoice->accountId)) {
                $row->bankAccount = bank_Accounts::getTitleById($dRec->invoice->accountId);
            }
            $row->bankAccount = bank_Accounts::getTitleById($dRec->invoice->accountId);
        } else {

            //нулираме стойностите на анулираните фактури
            if ($dRec->state == 'rejected') {
                $dRec->dealValue = $dRec->quantity = $dRec->price = $dRec->detAmount = $dRec->vat = 0;
            }

            $row->type = $dRec->type;
            $row->dealType = $dRec->dealType;
            $row->number = $dRec->number;
            $row->date = $Date->toVerbal($dRec->date);
            $row->state = $dRec->state;
            $row->contragentName = $dRec->contragentName;
            $row->contragentVatNo = $dRec->contragentVatNo;
            $row->contragentNo = $dRec->contragentNo;
            $row->accItem = $dRec->accItem;
            $row->currencyId = $dRec->currencyId;
            $row->rate = core_Type::getByName('double(decimals=4)')->toVerbal($dRec->rate);
            $row->dealValue = $Double->toVerbal($dRec->dealValue);
            $row->prodCode = $dRec->prodCode;
            $row->group = cat_Groups::getTitleById($dRec->group);
            $row->quantity = core_Type::getByName('double(decimals=3)')->toVerbal($dRec->quantity);
            $row->price = core_Type::getByName('double(decimals=6)')->toVerbal($dRec->price);
            $row->detAmount = $Double->toVerbal($dRec->detAmount);
            $row->measure = $dRec->measure;
            $row->vat = $dRec->vat;
            $row->paymentType = $dRec->paymentType;
            if (isset($dRec->invoice->accountId)) {
                $row->bankAccount = bank_Accounts::getTitleById($dRec->accountId);
            }
        }

        return $row;
    }


    /**
     * След подготовка на реда за експорт
     *
     * @param frame2_driver_Proto $Driver
     * @param stdClass $res
     * @param stdClass $rec
     * @param stdClass $dRec
     */
    protected static function on_AfterGetExportRec(frame2_driver_Proto $Driver, &$res, $rec, $dRec, $ExportClass)
    {
        $isPlain = Mode::is('text', 'plain');
        $Int = cls::get('type_Int');
        $Date = cls::get('type_Date');
        $Double = core_Type::getByName('double(decimals=2)');

        $row = new stdClass();

        if ($dRec->invoice) {

            //нулираме стойностите на анулираните фактури
            if ($dRec->invoice->state == 'rejected') {
                $dRec->invoice->dealValue = $dRec->quantity = $dRec->price = $dRec->detAmount = $dRec->vat = 0;
            }

            $res->type = $dRec->invoice->type;
            $res->dealType = $dRec->invoice->dealType;
            $res->number = $dRec->invoice->number;
            $res->date = ($dRec->invoice->date);
            $res->state = $dRec->invoice->state;
            $res->contragentName = $dRec->invoice->contragentName;
            $res->contragentVatNo = $dRec->invoice->contragentVatNo;
            $res->contragentNo = $dRec->invoice->contragentNo;
            $res->accItem = $dRec->accItem;
            $res->currencyId = $dRec->invoice->currencyId;
            $res->rate = ($dRec->invoice->rate);
            $res->dealValue = ($dRec->invoice->dealValue);
            $res->prodCode = $dRec->prodCode;
            $res->group = cat_Groups::getTitleById($dRec->group);
            $res->quantity = $dRec->quantity;
            $res->price = $dRec->price;
            $res->detAmount = $dRec->detAmount;
            $res->measure = $dRec->measure;
            $res->vat = $dRec->vat;
            $res->paymentType = $dRec->invoice->paymentType;
            if (isset($dRec->invoice->accountId)) {
                $res->bankAccount = bank_Accounts::getTitleById($dRec->invoice->accountId);
            }
        } else {
            //нулираме стойностите на анулираните фактури
            if ($dRec->state == 'rejected') {
                $dRec->dealValue = $dRec->quantity = $dRec->price = $dRec->detAmount = $dRec->vat = 0;
            }
            if (isset($dRec->invoice->accountId)) {
                $res->bankAccount = bank_Accounts::getTitleById($dRec->accountId);
            }
        }
    }


    /**
     * Определя вида сделка
     *
     * @param stdClass $rec - запис
     *
     * @return int
     */
    private function getDealType($rec)
    {
        $this->confCache = core_Packs::getConfig('bnav');
        $this->countryId = drdata_Countries::fetchField("#commonName = 'Bulgaria'", 'id');

        $number = ($rec->contragentVatNo) ? $rec->contragentVatNo : $rec->uicNo;

        if ($rec->contragentCountryId == $this->countryId || empty($rec->contragentCountryId)) {
            // Ако е фирма от БГ сделката е 21
            $vidSdelka = $this->confCache->FSD_DEAL_TYPE_BG;
        } elseif (drdata_Vats::isHaveVatPrefix($number)) {
            // Не е от БГ но е VAT - Евросъюз
            $vidSdelka = $this->confCache->FSD_DEAL_TYPE_EU; // 23
            // Обаче, ако експедиционното /packaging list/ е с адрес за доставката в страна извън ЕС
            // => $vidSdelka = $this->confCache->FSD_DEAL_TYPE_NON_EU;

            // Ако има експедиционно със същия containerId,
            // взимаме данните за доставка и проверяваме дали това ни е случая
            $shOrder = store_ShipmentOrders::fetch("#fromContainerId = {$rec->containerId}");
            if ($shOrder->country) {
                $groupsArr = drdata_CountryGroups::getGroupsArr($shOrder->country);
                foreach ($groupsArr as $group) {
                    if ('Чужбина извън ЕС' == $group->name) {
                        $vidSdelka = $this->confCache->FSD_DEAL_TYPE_NON_EU; // 22
                    }
                }
            }
        } else {
            // Извън Евросъюза

            $vidSdelka = $this->confCache->FSD_DEAL_TYPE_NON_EU; // 22
            // Но ако е начислено ДДС вида сделка става 21 - по заявка на Даниела /нерегистрирани по ДДС извън БГ/
            if ($rec->vatRate != 'no' && $rec->vatRate != 'exempt') {
                $vidSdelka = $this->confCache->FSD_DEAL_TYPE_BG;
            }
        }

        return ($vidSdelka);
    }


    /**
     * Определя типа на документа
     *
     * @param stdClass $rec - запис
     *
     * @return int
     */
    private function getDocType($rec)
    {
        $this->confCache = core_Packs::getConfig('bnav');
        $this->countryId = drdata_Countries::fetchField("#commonName = 'Bulgaria'", 'id');
        $this->kgId = cat_UoM::fetchBySinonim('kg')->id;

        if ($rec->type == 'dc_note') {
            //if ($rec->dpAmount > 0 || $rec->changeAmount) {
            if ($rec->dealValue > 0) {
                $docType = $this->confCache->FSD_DOC_DEBIT_NOTE_TYPE;
            } else {
                $docType = $this->confCache->FSD_DOC_CREDIT_NOTE_TYPE;
            }
        } else {
            $docType = $this->confCache->FSD_DOC_INVOCIE_TYPE;
        }

        return ($docType);
    }
}
