<?php


/**
 * Експортиране на фактури по продажби в Ажур
 *
 * @category  bgerp
 * @package   ajur
 *
 * @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2022 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Експорт към Ажур » Експорт фактури продажби
 */
class ajur_SalesInvoicesExport extends frame2_driver_TableData
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
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        $fieldset->FLD('from', 'date', 'caption=От,after=title,single=none,mandatory');
        $fieldset->FLD('to', 'date', 'caption=До,after=from,single=none,mandatory');

        $fieldset->FNC('dealType', 'int', 'caption=Тип сделка,after=to,input=none,single=none,mandatory');
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

        $sQuery = sales_Invoices::getQuery();

        //Експортира и оттеглените фактури
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

        $bgId = drdata_Countries::fetchField("#commonName = 'Bulgaria'", 'id');
        $confCache = core_Packs::getConfig('ajur');

        while ($sRec = $sQuery->fetch()) {



            //Масив с фактури от продажбите
            $id = $sRec->id;



            //номер на фактурата
            $number = str_pad($sRec->number, 10, "0", STR_PAD_LEFT);

            //Състояние (Тип-права или анулирана (0 -за анулирана, 1 -за активна))
            $state = $sRec->state;
            $brState = $sRec->brState;
            $stateType = $sRec->state == 'rejected' ? 0 : 1;

            //Дата на данъчното събитие
            $vatDate = $sRec->date;

            //Вид валута
            $currency = $sRec->currencyId;

            //Валутен курс
            $currencyRate = $sRec->rate;

            //Експортна фактура по колона 8
            $exportInv = ($sRec->currencyId != 'BGN') ? 1:  0;

            //Код на контрагента, В случая folderId  на контрагента
            $contragentClassName = core_Classes::getName($sRec->contragentClassId);
            $contragentRec = $contragentClassName::fetch($sRec->contragentId);
            $contragentCode = $contragentRec -> folderId;

            //Адрес
            $contragentAddress = $contragentRec->contragentAddress;


            //Име на контрагента
            $contragentName = $sRec->contragentName;

            //VAT номер на контрагента
            $contragentVatNo = $sRec->contragentVatNo;

            //БУЛСТАТ на контрагента
            $bulstatNo = $sRec->uicNo;

            //Банкова сметка
            $bankAccount = $sRec->accountId;

            //Вид фактура
            $invoiceType = self::getDocType($sRec);

            //Към фактура (за КИ и ДИ)
            if($invoiceType == $confCache->AJUR_DOC_CREDIT_NOTE_TYPE ||
                $invoiceType == $confCache->AJUR_DOC_DEBIT_NOTE_TYPE ){

                $originDoc = doc_Containers::getDocument($sRec->originId);
                $originDocNumber = $originDoc->className::fetch($originDoc->that)->number;
                $originDocDate = $originDoc->className::fetch($originDoc->that)->date;
            }

            //Взема начина на плащане
            $paymentType = self::getPaymentType($sRec);

            //Дата нападеж
            $dueDate = ($sRec->dueDate) ? : $sRec->date;

            //За фактурите с ДДС от БГ разпределяме по ставки ДДС
            if ($sRec->contragentCountryId == $bgId || empty($sRec->contragentCountryId)) {

                $vatAlocation = self::getVATallocation($sRec);

            }

            // Дали е авансова фактура
            $isDpInvoice = ($rec->dpOperation == 'accrued') ? 1 : 0;

//bp($sRec);
            $dealType = self::getDealType($sRec);


            $totalValue = $sRec->dealValue - $sRec->discountAmount + $sRec->vatAmount;

            // Запис в масива
            if (!array_key_exists($id, $invoices)) {
                $invoices[$id] = (object)array(

                    1 => $number,                 // * Фалтура No
                    2 => $stateType,              // * Тип - права или обратна
                    3 => $sRec->date,             // * Дата на фактурата
                    4 => $sRec->vatDate,          // * Дата на данъчно събитие
                    5 => $sRec->currencyId,       // * Вид валута
                    6 => $sRec->rate,             // * Валутен курс към датата на дан. събитие
                    7 => $invoiceType,            // * Вид фактура
                    8 => $exportInv,              // * Фактура за експорт
                    9 => $contragentCode,         // * Шифър на контрагент
                    10 => $contragentName,        // ** Наименование
                    11 => $contragentAddress,     // ** Адрес на контрагента
                    12 => $contragentVatNo,       // * ИН по ДДС на контрагента
                    13 => $bulstatNo,             // * БУЛСТАТ на контрагента
                    14 => '',                     // * Вид доставка
                    15 => $sRec->vatReason,       // * Основание за неначисляване на ДДС
                    16 => '',                     // * ИН по ДДС в друга държава
                    17 => '',                     // * Ставка по ДДС в друга държава
                    18 => '',                     // МОЛ на контрагента
                    19 => '',                     // Шифър на дистрибутор
                    20 => '',                     // * Наименование на дистрибутора
                    21 => '',                     // * Адрес на дистрибутора
                    22 => '',                     // * Ин по ДДС на дистрибутора
                    23 => '',                     // * БУЛСТАТ на дистрибутора
                    24 => $sRec->place,           // Място на издаване
                    25 => $originDocNumber,       // * Към фактура No(при издаване на ДИ и КИ)
                    26 => $originDocDate,         // * От дата фактура (при издаване на ДИ и КИ)
                    27 => $sRec->dcReason,        // * Причина за издаване на ДИ или КИ
                    28 => $paymentType,           // * Начин на плащане
                    29 => $dueDate,               // Дата на падеж
                    30 => $sRec->vatDate,         // Дата на получаване на стоката
                    31 => 0,                      // * Дата на регистрация
                    32 => 0,                      // * Фактура към търговска верига
                    33 => '',                     // * Код на доставчика към търговска верига
                    34 => '',                     // * Поръчка номер от търговска верига
                    35 => '',                     // * Входящ стоков номер за търговската верига
                    36 => $sRec->createdBy,       // Съставил
                    37 => $sRec->dealValue,       // * Общо сума без ДДС във валутата на фактурата
                    38 => $sRec->dealValueWithoutDiscount,    // * Общо Дан. Основа във валутата на фактурата
                    39 => $sRec->vatAmount,       // * Общо ДДС във валутата на фактурата
                    40 => $totalValue,      // * Общо сума за плащане във валутата на фактурата
                    41 => $sRec->exciseTax,       // * Общо акциз във валутата на фактурата
                    42 => $sRec->productTax,      // * Общо екотакса във валутата на фактурата
                    43 => $sRec->dealValue * $sRec->rate,      // * Общо сума без ДДС в лева
                    44 => $sRec->dealValueWithoutDiscount * $sRec->rate,     // * Общо Дан. Основа в лева
                    45 => $sRec->vatAmount * $sRec->rate,      // * Общо ДДС е лева
                    46 => $totalValue * $sRec->rate,     // * Общо сума за плащане в лева
                    47 => $sRec->exciseTax * $sRec->rate,      // * Общо акциз в лева
                    48 => $sRec->productTax * $sRec->rate,     // * Общо екотакса в лева
                    49 => 1,                       // * Връзка със склад
                    50 => 1,     //ВЪПРОС          // * Дали трябва да има запис в дневника по ДДС
                    51 => 0,                       // Звено по ДДС
                    52 => $vatAlocation->taxBase20Vat,         // ДО на сделки с ДДС 20% бкл. дист. на територията на страната
                    53 => $vatAlocation->vatTax20,         // Начислен ДДС за доставки по колона 52( 20%)
                    54 => $vatAlocation->taxBase9Vat,          // ДО на сделки с ДДС 9% бкл. дист. на територията на страната
                    55 => $vatAlocation->vatTax9,          // Начислен ДДС за доставки по колона 54( 9%)
                    56 => $vatAlocation->taxBase0Vat,          // ДО на сделки с ДДС 0% бкл. дист. на територията на страната
                    57 => $vatAlocation->vatTax0,          // Начислен ДДС за доставки по колона 56( 0%)
                    58 => '',                           // ДО на доставки по чл.140, 146 и чл.173, ал.1 и 4 от ЗДДС
                    59 => '',                           // ДО на доставка на услуги по чл.21, ал 3 и чл.24-24 от ЗДДС с място на изпълнение друга държава
                    60 => '',                           // ДО на доставка на услуги по чл.69, ал 2 от ЗДДС (вкл. ДО за дост. от дист. продажби в друга държава)
                    61 => '',                           // ДО на освобобените доставки и освободените ВОП, без чл50 т2
                    62 => '',                           // Доставки по чл. 50 т.2
                    63 => '',                           // ДО на доставки като посредник в тристранни операции
                    64 => '',                           // ВОД на стоки, участващи във VIES декларацията
                    65 => '',                           // ДО на доставки като посредник в тристранна операция, участващи във VIES декларацията
                    66 => '',                           // Услуги в рамките на ЕС, участващи във VIES декларацията
                    67 => '',  // ТОДО                  // * Вид на стоката / услугата
                    68 => $isDpInvoice,                 // * Дали фактурата е за аванс


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
            $id = $dRec->id;

            //Ако има авансово приспадане на суми
            if ($invoices[$dRec->invoiceId]->dpOperation == 'deducted') {
                $id = $invoices[$dRec->invoiceId]->number;

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
                    'accItem' => '',
                    'currencyId' => $invoices[$dRec->invoiceId]->currencyId,
                    'rate' => $invoices[$dRec->invoiceId]->rate,
                    'dealValue' => $invoices[$dRec->invoiceId]->dealValue,
                    'state' => $invoices[$dRec->invoiceId]->state,
                    'brState' => $invoices[$dRec->invoiceId]->brState,
                    'detAmount' => $invoices[$dRec->invoiceId]->dpAmount,

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

            $pRec = cat_Products::fetch($dRec->productId);

//            //Ако има регистрирана "ОСНОВНА ГРУПА", определяме група на артикула спрямо нея
//            if (core_Packs::getConfig('bnav')->BASE_GROUP != '') {
//
//                $gArr = explode('|', trim($pRec->groups, '|'));
//                if (empty(array_intersect($gArr, $flGroups))) {
//
//                    $group = 'n.a.';
//                } else {
//                    expect(countR(array_intersect($gArr, $flGroups)) < 2, "Има регистрирани повече от една група на първо ниво след  ОСНОВНАТА за артикул $pRec->name");
//                    $group = implode(',', array_intersect($gArr, $flGroups));
//                }
//
//            }
            $erpCode = $pRec->code ? $pRec->code : 'Art' . $pRec->id;
            $prodCode = $pRec->bnavCode ? $pRec->bnavCode : $erpCode;
            $measure = cat_UoM::getShortName($pRec->measureId);
            $detAmount = $dRec->amount;

            // Запис в масива

            if (!array_key_exists($id, $recs)) {


                $recs[$id] = (object)array(
                    'invoice' => $invoices[$dRec->invoiceId],
                    'number' => $invoices[$dRec->invoiceId]->number,
                    'prodCode' => $prodCode,
                    'group' => '',
                    'quantity' => $dRec->quantity,
                    'price' => $dRec->price,
                    'detAmount' => $detAmount,
                    'vatAmount' => '',
                    'measure' => $measure,
                    'vat' => cat_Products::getVat($pRec->id) * 100,
                    'accText' => '',
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


        $fld->FLD('number', 'varchar', 'caption=Документ №,tdClass=centered');//1
        $fld->FLD('type', 'varchar', 'caption=Тип на документа');//2
        $fld->FLD('date', 'date', 'caption=Дата');//3
        $fld->FLD('vatDate', 'date', 'caption=Дата дан.събитие');//4
        $fld->FLD('currencyId', 'varchar', 'caption=Валута,tdClass=centered');//5
        $fld->FLD('rate', 'double', 'caption=Курс на валутата');//6
        $fld->FLD('invoiceType', 'varchar', 'caption=Тип на документа');//7
        $fld->FLD('exportInv', 'varchar', 'caption=Фактура Експорт');//8
        $fld->FLD('contragentCode', 'varchar', 'caption=Шифър на контрагента');//9
        $fld->FLD('contragentName', 'varchar', 'caption=Име на контрагента');//10
        $fld->FLD('contragentAddress', 'varchar', 'caption=Адрес на контрагента');//11
        $fld->FLD('contragentVatNo', 'varchar', 'caption=ИН по ДДС на контрагента');//12
        $fld->FLD('bulstatNo', 'varchar', 'caption=ЕИК на контрагента');//13
        $fld->FLD('deliveryType', 'varchar', 'caption=Вид доставка');//14
        $fld->FLD('vatNotReason', 'varchar', 'caption=Осн. за ненач. ДДС');//15
        $fld->FLD('vatNoOutCountry', 'varchar', 'caption=ИН по ДДС в друга държава');//16
        $fld->FLD('vatRateOutCountry', 'varchar', 'caption=Ставка по ДДС в друга държава');//17
        $fld->FLD('contragentMOL', 'varchar', 'caption=МОЛ на контрагента');//18
        $fld->FLD('distributorCode', 'varchar', 'caption=Шифър на дистрибутор');//19
        $fld->FLD('distributorName', 'varchar', 'caption=Име на дистрибутор');//20
        $fld->FLD('distributorAddress', 'varchar', 'caption=Адрес на дистрибутор');//21
        $fld->FLD('distributorVatNo', 'varchar', 'caption=ДДС № на дистрибутор');//22
        $fld->FLD('distributorBulstatNo', 'varchar', 'caption=ЕИК на дистрибутор');//23
        $fld->FLD('place', 'varchar', 'caption=Място на издаване');//24
        $fld->FLD('originDocNumber', 'varchar', 'caption=Към фактура No');//25
        $fld->FLD('originDocDate', 'date', 'caption=От дата фактура');//26
        $fld->FLD('dcReason', 'varchar', 'caption=Причина за ДИ, КИ');//27
        $fld->FLD('paymentType', 'int', 'caption=Начин на плащане');//28
        $fld->FLD('dueDate', 'date', 'caption=Дата на падеж');//29
        $fld->FLD('dateOfReceipt', 'date', 'caption=Дата получаване стока');//30
        $fld->FLD('dateOfRegistration', 'date', 'caption=Дата на регистрация');//31
        $fld->FLD('retailInv', 'int', 'caption=Фактура към ТВ');//32
        $fld->FLD('retailSupplierCode', 'varchar', 'caption=Код на доставчика ТВ');//33
        $fld->FLD('retailOrderNo', 'varchar', 'caption=Поръчка номер ТВ');//34
        $fld->FLD('retailInStockNo', 'varchar', 'caption=Входящ стоков номер ТВ');//35
        $fld->FLD('createdBy', 'varchar', 'caption=Съставил');//36
        $fld->FLD('dealValueCurrecy', 'double', 'caption=Общо сума без ДДС Валута');//37
        $fld->FLD('dealValueWithoutDiscountCurrecy', 'double', 'caption=Общо Дан. Основа Валута');//38
        $fld->FLD('vatAmountCurrecy', 'double', 'caption=Общо ДДС Валута ');//39
        $fld->FLD('totalValueCurrecy', 'double', 'caption=Общо сума за плащане Валута ');//40
        $fld->FLD('exciseTaxCurrecy', 'double', 'caption=Общо акциз Валута');//41
        $fld->FLD('productTaxCurrecy', 'double', 'caption=Общо екотакса Валута');//42
        $fld->FLD('dealValue', 'double', 'caption=Общо сума без ДДС в лева ');//43
        $fld->FLD('dealValueWithoutDiscount', 'double', 'caption=Общо Дан. Основа в лева ');//44
        $fld->FLD('vatAmount', 'double', 'caption=Общо ДДС в лева ');//45
        $fld->FLD('totalValue', 'double', 'caption=Общо сума за плащане в лева ');//46
        $fld->FLD('exciseTax', 'double', 'caption=Общо акциз в лева ');//47
        $fld->FLD('productTax', 'double', 'caption=Общо екотакса в лева ');//48
        $fld->FLD('storeConnection', 'int', 'caption=Връзка със склад ');//49
        $fld->FLD('saveInVat', 'int', 'caption=Дали трябва да има запис в дневника по ДДС ');//50
        $fld->FLD('zvenoVat', 'int', 'caption=Звено по ДДС ');//51
        $fld->FLD('taxBase20Vat', 'double', 'caption=ДО на сделки с ДДС 20% ');//52
        $fld->FLD('vatTax20', 'double', 'caption=Начислен ДДС за доставки 20% ');//53
        $fld->FLD('taxBase9Vat', 'double', 'caption=ДО на сделки с ДДС 9% ');//54
        $fld->FLD('vatTax9', 'double', 'caption=Начислен ДДС за доставки 9% ');//55














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
            $row->number = $dRec->number;
            $row->date = $Date->toVerbal($dRec->invoice->date);
            $row->state = $dRec->invoice->state;
            $row->contragentName = $dRec->invoice->contragentName;
            $row->contragentVatNo = $dRec->invoice->contragentVatNo;
            $row->contragentNo = $dRec->invoice->contragentNo;
            $row->accItem = $dRec->invoice->accItem;
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
            $row->bankAccount = bank_Accounts::getTitleById($dRec->accountId);
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
            $res->accItem = $dRec->invoice->accItem;
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
            $res->bankAccount = bank_Accounts::getTitleById($dRec->invoice->accountId);
        } else {
            //нулираме стойностите на анулираните фактури
            if ($dRec->state == 'rejected') {
                $dRec->dealValue = $dRec->quantity = $dRec->price = $dRec->detAmount = $dRec->vat = 0;
            }

            $res->bankAccount = bank_Accounts::getTitleById($dRec->accountId);
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
       // bp($rec);
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
     * Определя вида на фактурата за колона 7
     *
     * @param stdClass $rec - запис
     *
     * @return int
     */
    private function getDocType($rec)
    {
        $this->confCache = core_Packs::getConfig('ajur');

        $docType = 0;
        // Дебитно или кредитно известие
        if ($rec->type == 'dc_note') {
            if ($rec->dpAmount > 0 || $rec->changeAmount) {
                $docType = $this->confCache->AJUR_DOC_DEBIT_NOTE_TYPE; //Дебитно известие
            } else {
                $docType = $this->confCache->AJUR_DOC_CREDIT_NOTE_TYPE;  //Кредитно известие
            }
        }

        // Фактура
        if ($rec->type == 'invoice') {
            $docType = $this->confCache->AJUR_DOC_INVOCIE_TYPE; // Фактура
        }

        return ($docType);
    }

    /**
     * Връща начина на плащане за колона 28
     *
     * @param stdClass $rec - запис
     *
     * @return int
     */
    private function getPaymentType($rec)
    {
        $this->confCache = core_Packs::getConfig('ajur');

        $paymentType = ($rec->paymentType) ? : $rec->autoPaymentType;

        switch ($paymentType) {

            case null : $paymentTypeRet = $this->confCache->AJUR_DOC_PAYMENT_INDEFINITE_TYPE;break;
            case 'cash' : $paymentTypeRet = $this->confCache->AJUR_DOC_PAYMENT_CASH_TYPE;break;
            case 'bank' : $paymentTypeRet = $this->confCache->AJUR_DOC_PAYMENT_ACCOUNT_TYPE;break;
            case 'card' : $paymentTypeRet = $this->confCache->AJUR_DOC_PAYMENT_CARD_TYPE;break;
            case 'postal' : $paymentTypeRet = $this->confCache->AJUR_DOC_PAYMENT_POST_TRANSFER_TYPE;break;
            case 'factoring' : $paymentTypeRet = 'факторинг';break;
            case 'intercept' : $paymentTypeRet = 'прихващане';break;
            case 'mixed' : $paymentTypeRet = ' ';break;
            default : ' '; break;
        }

        return ($paymentTypeRet);
    }


    /**
     * Разпределя симите по размер на ДДС: 20%, 9%, 0% и др.
     *
     * @param array - запис
     *
     * @return array
     */
    private function getVATallocation($rec)
    {
        $vatAllocation = array();

            $taxBase20Vat = $taxBase9Vat = $taxBase0Vat = 0;
            $tax20 = $tax9 = $tax0 = 0;

            $detQuery = sales_InvoiceDetails::getQuery();


            $detQuery->where("#invoiceId = $rec->id");

            //Ако няма детайли връщаме празин масив
            if ($detQuery->count() == 0) {
                return $vatAllocation;
            }

            while ($detRec = $detQuery->fetch()) {
                $vatGroup = cat_Products::getVat($detRec->productId, $detRec->createdOn);

                switch ($vatGroup) {

                    case '0.2' :
                        $tax20 += $detRec->amount * $vatGroup;
                        $taxBase20Vat += $detRec->amount;
                        break;
                    case '0.09' :
                        $tax9 += $detRec->amount * $vatGroup;
                        $taxBase9Vat += $detRec->amount;
                        break;
                    case '0' :
                        $tax0 += $detRec->amount * $vatGroup;
                        $taxBase0Vat += $detRec->amount;
                        break;
                }


            }
            $vatAllocation = (object)array('taxBase20Vat' => $taxBase20Vat, 'vatTax20' => $tax20,
                'taxBase9Vat' => $taxBase9Vat, 'vatTax9' => $tax9,
                'taxBase0Vat' => $taxBase0Vat, 'vatTax0' => $tax0
            );

        return $vatAllocation;
    }
}
