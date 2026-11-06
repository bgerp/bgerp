<?php


/**
 * Месечната справка по Наредба Н-18 за е-магазин и одиторският XML файл по приложение № 38
 *
 * @category  bgerp
 * @package   eshopn18
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2026 Experta EOOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     НАП » Н-18 е-магазин - месечен одиторски файл
 */
class eshopn18_reports_N18Audit extends frame2_driver_TableData
{
    /**
     * Старо име на класа
     */
    public $oldClassName = 'bgfisc_reports_N18Audit';


    /**
     * Кой може да избира драйвъра
     */
    public $canSelectDriver = 'ceo,acc,eshop';


    /**
     * Кои полета от таблицата са цени/суми
     */
    protected $priceListFields = 'net,vat,total2';


    /**
     * Документите за доставка, от които се взимат редовете
     */
    protected static $deliveryDocs = array('store_ShipmentOrders' => 'store_ShipmentOrderDetails', 'sales_Services' => 'sales_ServicesDetails');


    /**
     * Кодовете на плащане по приложение № 38 - PAYM за поръчките и R_PAYM за връщанията
     */
    protected static $paymentCodes = array(
        'order' => array(1 => 'Освободено по чл. 3 плащане без ППП', 2 => 'Виртуален ПОС', 3 => 'Наложен платеж с ППП',
            4 => 'Доставчик на платежни услуги', 5 => 'Друго плащане без фискален бон', 6 => 'Плащане, отразено с фискален бон'),
        'refund' => array(1 => 'По платежна сметка', 2 => 'По карта', 3 => 'В брой', 4 => 'Друг начин'),
    );


    /**
     * Кратките надписи на балончетата - пълните наименования са в хинта
     */
    protected static $paymentShort = array(
        'order' => array(1 => 'Банка', 2 => 'Вирт. ПОС', 3 => 'ППП', 4 => 'ДПУ', 5 => 'Друго', 6 => 'Касов бон'),
        'refund' => array(1 => 'Сметка', 2 => 'Карта', 3 => 'В брой', 4 => 'Друго'),
    );


    /**
     * Цветът на балончето според кода на плащане (зелено по подразбиране)
     */
    protected static $paymentColors = array(1 => 'blue', 2 => null, 3 => 'amber', 4 => 'purple', 5 => 'gray', 6 => 'teal');


    /**
     * Кодовете на плащане за модела (на английски, без превод)
     */
    protected static $paymentCodesAi = array(
        'order' => array(1 => 'exempt, no postal transfer', 2 => 'virtual POS', 3 => 'cash on delivery by postal transfer',
            4 => 'payment service provider', 5 => 'other, no fiscal receipt', 6 => 'fiscal receipt'),
        'refund' => array(1 => 'payment account', 2 => 'card', 3 => 'cash', 4 => 'other'),
    );


    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        $fieldset->FLD('domainId', 'key(mvc=cms_Domains,select=titleExt)', 'caption=Магазин,mandatory,after=title,single=none');
        $fieldset->FLD('periodId', 'key(mvc=acc_Periods,select=title)', 'caption=Месец,mandatory,after=domainId,single=none');
    }


    /**
     * Периодът е избраният месец - по него заглавието по подразбиране получава месеца
     *
     * @param stdClass $rec
     *
     * @return array
     */
    protected function getPeriodRange($rec)
    {
        $periodRec = acc_Periods::fetch($rec->periodId ?? null);

        return is_object($periodRec) ? array('from' => $periodRec->start, 'to' => $periodRec->end) : array();
    }


    /**
     * Само магазините в режим по Н-18 и последните месеци
     *
     * @param frame2_driver_Proto $Driver
     * @param embed_Manager       $Embedder
     * @param stdClass            $data
     */
    protected static function on_AfterPrepareEditForm(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$data)
    {
        $form = $data->form;
        $domains = array();
        $sQuery = eshop_Settings::getQuery();
        $sQuery->where(array("#classId = '[#1#]' AND #n18Mode = 'yes'", cms_Domains::getClassId()));
        while ($sRec = $sQuery->fetch()) {
            $domains[$sRec->objectId] = cms_Domains::getTitleById($sRec->objectId, false);
        }
        $form->setOptions('domainId', $domains);
        if (countR($domains) == 1) {
            $form->setDefault('domainId', key($domains));
        }

        // По подразбиране - предходният месец, за който се подава файлът
        $prevPeriodId = acc_Periods::fetchField(array("#end = '[#1#]'", dt::getLastDayOfMonth(dt::addMonths(-1))), 'id');
        $form->setDefault('periodId', $prevPeriodId);
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
        $data = is_object($data) ? $data : new stdClass();
        $data->n18Errors = array();
        $recs = array();

        // Настройките на магазина към края на месеца, а не текущите
        $periodRec = acc_Periods::fetch($rec->periodId ?? null);
        $settings = (!empty($rec->domainId) && is_object($periodRec)) ? cms_Domains::getSettings($rec->domainId, $periodRec->end) : null;
        if (!is_object($periodRec) || !is_object($settings)) {

            return $recs;
        }
        $from = $periodRec->start;
        $to = $periodRec->end;

        // Онлайн поръчките на магазина
        $carts = array();
        $cQuery = eshop_Carts::getQuery();
        $cQuery->EXT('threadId', 'sales_Sales', 'externalName=threadId,externalKey=saleId');
        $cQuery->where(array("#domainId = '[#1#]' AND #saleId IS NOT NULL AND #activatedOn <= '[#2#]'", $rec->domainId, $to . ' 23:59:59'));
        $cQuery->show('id,saleId,threadId,activatedOn');
        while ($cRec = $cQuery->fetch()) {
            $carts[$cRec->threadId] = $cRec;
        }

        if (countR($carts)) {
            $this->prepareOrders($recs, $carts, $from, $to);
            $this->prepareRefunds($recs, $carts, $from, $to);
        }

        $data->n18FileHnd = $this->generateXml($rec, $recs, $settings, $periodRec, $data->n18Errors);

        return $recs;
    }


    /**
     * Поръчките с доставка през месеца - редовете идват от документите за доставка
     *
     * @param array  $recs
     * @param array  $carts - количките по нишка на продажбата
     * @param string $from
     * @param string $to
     */
    protected function prepareOrders(&$recs, $carts, $from, $to)
    {
        $threads = array_keys($carts);

        // Документите за доставка с вальор в месеца, без връщанията на стока
        $deliveries = array();
        foreach (static::$deliveryDocs as $masterClass => $detailClass) {
            $dQuery = $masterClass::getQuery();
            $dQuery->where(array("#state = 'active' AND #isReverse = 'no' AND #valior >= '[#1#]' AND #valior <= '[#2#]'", $from, $to));
            $dQuery->in('threadId', $threads);
            while ($dRec = $dQuery->fetch()) {
                $deliveries[] = array($masterClass, $detailClass, $dRec);
            }
        }

        // Продажбите, контирани с експедиция
        $saleIds = arr::extractValuesFromArray($carts, 'saleId');
        $sQuery = sales_Sales::getQuery();
        $sQuery->where(array("#state IN ('active', 'closed') AND #valior >= '[#1#]' AND #valior <= '[#2#]'", $from, $to));
        $sQuery->like('contoActions', 'ship');
        $sQuery->in('id', $saleIds);
        while ($sRec = $sQuery->fetch()) {
            $deliveries[] = array('sales_Sales', 'sales_SalesDetails', $sRec);
        }

        foreach ($deliveries as $delivery) {
            list($masterClass, $detailClass, $dRec) = $delivery;
            $cartRec = $carts[$dRec->threadId];
            $saleRec = sales_Sales::fetch($cartRec->saleId);
            $key = "o{$cartRec->saleId}";
            if (!isset($recs[$key])) {
                $recs[$key] = (object) array('kind' => 'order', 'cartId' => $cartRec->id, 'saleId' => $saleRec->id, 'saleState' => $saleRec->state, 'ownCompanyId' => $saleRec->ownCompanyId ?? null,
                    'orderDate' => dt::mysql2verbal(!empty($cartRec->activatedOn) ? $cartRec->activatedOn : $saleRec->valior, 'Y-m-d'), 'docs' => array(), 'lines' => array(), 'errors' => array(),
                    'total1' => 0, 'disc' => 0, 'vat' => 0, 'total2' => 0, 'deliveryDate' => $dRec->valior);
                $this->preparePayment($recs[$key], $saleRec, $cartRec);
            }
            $oRec = $recs[$key];
            $oRec->docs[] = array($masterClass, $dRec->id);

            // Сумата на документа с ДДС - за сверка със сметнатото от редовете
            $docAmount = ($masterClass == 'sales_Sales') ? ($dRec->amountDeal ?? 0) : ($dRec->amountDelivered ?? 0);
            $oRec->docsAmount = ($oRec->docsAmount ?? 0) + $docAmount;
            $oRec->deliveryDate = min($oRec->deliveryDate, $dRec->valior);

            $Detail = cls::get($detailClass);
            $lQuery = $Detail->getQuery();
            $lQuery->where("#{$Detail->masterKey} = {$dRec->id}");
            $lQuery->orderBy('id');
            while ($lRec = $lQuery->fetch()) {
                $line = $this->prepareLine($lRec, $dRec, $saleRec);
                if (empty($line)) continue;
                if ($line['quantity'] <= 0) {
                    $oRec->errors['quantity'] = 'Количество под 0.01 в мярката на реда - XML файлът го приема само с 2 знака';
                }
                $oRec->lines[] = $line;
                $oRec->total1 += $line['gross'];
                $oRec->disc += $line['disc'];
                $oRec->vat += $line['vat'];
                $oRec->total2 += $line['sum'];
            }
        }

        foreach ($recs as $oRec) {
            if (!countR($oRec->lines)) {
                $oRec->errors[] = 'Доставката няма редове';
            }

            // Сумите се събират от закръглените редове - разлика означава грешка в сметките
            if (abs(round($oRec->total1 - $oRec->disc + $oRec->vat, 2) - round($oRec->total2, 2)) > 0.001) {
                $oRec->errors[] = 'Сумите на редовете не дават общата сума';
            }

            // Сверка с документите за доставка - допуска се стотинка закръгляне на ред
            if (abs(round($oRec->total2 - ($oRec->docsAmount ?? 0), 2)) > 0.01 * (countR($oRec->lines) + 1)) {
                $oRec->errors[] = 'Сумата се разминава със сумата на документите за доставка';
            }
        }
    }


    /**
     * Ред от документа за доставка - суми в основната валута, закръглени така, че да се събират
     *
     * @param stdClass $lRec   - редът на документа
     * @param stdClass $dRec   - документът
     * @param stdClass $saleRec
     *
     * @return array|null
     */
    protected function prepareLine($lRec, $dRec, $saleRec)
    {
        $quantity = $lRec->quantity ?? 0;
        if (empty($quantity)) {

            return null;
        }
        $quantityInPack = !empty($lRec->quantityInPack) ? $lRec->quantityInPack : 1;
        $chargeVat = in_array($dRec->chargeVat ?? null, array('yes', 'separate'));
        $vatRate = $chargeVat ? cat_Products::getVat($lRec->productId, $dRec->valior, $saleRec->vatExceptionId ?? null) : 0;

        // Автоматичната отстъпка се слива с ръчната както в deals_Helper, докато сделката не е активна
        $discount = $lRec->discount ?? 0;
        if (!empty($lRec->autoDiscount) && in_array($dRec->state ?? null, array('draft', 'pending'))) {
            $discount = 1 - (1 - $discount) * (1 - $lRec->autoDiscount);
        }

        // Като в касовия бон (bgfisc_plg_PrintFiscReceipt) - сумата с ДДС и отстъпката с ДДС се закръглят поотделно,
        // а единичната цена с 2 знака е само за сведение и по количеството може да не дава сумата до стотинка
        $amount = $lRec->price * $quantity;
        $gross = round(round($amount, 10), 2);
        $net = round(round($amount * (1 - $discount), 10), 2);
        $sum = round(round(round($amount * (1 + $vatRate), 10), 2) - round(round($discount * $amount * (1 + $vatRate), 10), 2), 2);

        // Закръглянето на малки суми не бива да дава ДДС без ставка или отрицателно ДДС
        if (empty($vatRate) || $sum < $net) {
            $sum = $net;
        }

        // Името е текст (не HTML) и на български, както е в документа
        core_Lg::push('bg');
        $name = cat_Products::getDisplayName(cat_Products::fetch($lRec->productId, 'name,nameEn'));
        core_Lg::pop();

        return array(
            'name' => mb_substr((string) $name, 0, 200),
            'quantity' => round(round($quantity / $quantityInPack, 10), 2),
            'price' => round(round($lRec->price * $quantityInPack, 10), 2),
            'vatRate' => (int) round($vatRate * 100),
            'gross' => $gross,
            'disc' => round($gross - $net, 2),
            'vat' => round($sum - $net, 2),
            'sum' => $sum,
        );
    }


    /**
     * Начинът на плащане (PAYM) и документът към поръчката
     *
     * @param stdClass $oRec
     * @param stdClass $saleRec
     */
    protected function preparePayment($oRec, $saleRec, $cartRec = null)
    {
        $oRec->docNum = 0;
        $oRec->docDate = null;
        $oRec->paym = null;
        $oRec->posId = $oRec->transactionRef = $oRec->procId = '';
        $oRec->payDocs = array();

        // Платеното с карта има документ по чл. 52о
        $regDoc = eshopn18_SaleRegDocs::fetch("#saleId = {$saleRec->id}");
        $receipt = $this->getFiscReceipt($saleRec);
        if (is_object($regDoc)) {
            $oRec->paym = $regDoc->paymentCode ?? null;
            $oRec->docNum = $regDoc->number;
            $oRec->docDate = dt::mysql2verbal($regDoc->issuedOn, 'Y-m-d');
            $oRec->posId = $regDoc->posId ?? '';
            $oRec->transactionRef = $regDoc->transactionRef ?? '';
            $oRec->regDocId = $regDoc->id;
            if (empty($oRec->paym)) {
                $oRec->errors[] = 'Документът по чл. 52о е без код на плащането';
            } elseif ($oRec->paym == 4) {
                $oRec->errors[] = 'Липсва идентификаторът на доставчика на платежни услуги';
            } elseif ($oRec->paym == 2 && (empty($oRec->posId) || empty($oRec->transactionRef))) {
                $oRec->errors[] = 'При виртуален ПОС липсва номерът на ПОС или на трансакцията';
            }
            if (isset($receipt)) {
                $oRec->errors[] = 'Поръчката има и документ по чл. 52о, и фискален бон';
            }

            return;
        }

        if (isset($receipt)) {
            $oRec->paym = 6;
            list($oRec->docNum, $oRec->docDate) = $receipt;

            return;
        }

        // Платено с карта, а документът по чл. 52о липсва - ПБД-то от шлюза не бива да мине за банков превод
        if (!empty($cartRec->id) && $this->isPaidByCard($cartRec)) {
            $oRec->errors[] = 'Платено с карта без документ по чл. 52о - създайте го от количката';

            return;
        }

        // Банковите плащания - по сметка за пощенски паричен превод или обикновен превод
        $postalAccounts = keylist::toArray(bank_Setup::get('POSTAL_TRANSFER_ACCOUNTS'));
        $bQuery = bank_IncomeDocuments::getQuery();
        $bQuery->where("#state = 'active' AND #threadId = {$saleRec->threadId}");
        $bQuery->show('id,ownAccount');
        while ($bRec = $bQuery->fetch()) {
            $oRec->paym = in_array($bRec->ownAccount, $postalAccounts) ? 3 : ($oRec->paym ?? 1);
            $oRec->payDocs[] = array('bank_IncomeDocuments', $bRec->id);
        }

        // ПКО без бон - наложеният платеж с ППП, внесен от куриера в касата
        $cQuery = cash_Pko::getQuery();
        $cQuery->where("#state = 'active' AND #threadId = {$saleRec->threadId}");
        $cQuery->show('id');
        while ($cRec = $cQuery->fetch()) {
            $oRec->payDocs[] = array('cash_Pko', $cRec->id);
            if (empty($oRec->paym)) {
                if (($saleRec->paymentType ?? null) == 'postal') {
                    $oRec->paym = 3;
                } else {
                    $oRec->errors['cash'] = 'Плащане в брой без фискален бон';
                }
            }
        }

        if (empty($oRec->paym) && empty($oRec->errors['cash'])) {
            $oRec->errors[] = 'Плащането не е отразено - няма документ по чл. 52о, фискален бон, ПБД или ПКО';
        }
    }


    /**
     * Дали поръчката е платена с карта - по начина на плащане или по платен опит в шлюза
     *
     * @param stdClass $cartRec
     *
     * @return bool
     */
    protected function isPaidByCard($cartRec)
    {
        $paymentId = eshop_Carts::fetchField($cartRec->id, 'paymentId');
        if (!empty($paymentId) && cond_PaymentMethods::fetchField($paymentId, 'type') == 'card') {

            return true;
        }

        $cQuery = cond_CardPayments::getQuery();
        $cQuery->where(array("#initiatorClassId = '[#1#]' AND #initiatorId = '[#2#]' AND #operation = 'payment' AND #state = 'paid'", eshop_Carts::getClassId(), $cartRec->id));

        return $cQuery->count() > 0;
    }


    /**
     * Номерът и датата на отпечатания фискален бон по продажбата - от QR кода му
     *
     * @param stdClass $saleRec
     *
     * @return array|null - [номерът, както е на бона, дата]
     */
    protected function getFiscReceipt($saleRec)
    {
        // Касовите бонове са само при инсталиран bgfisc
        if (!core_Packs::isInstalled('bgfisc')) {

            return null;
        }

        $regRec = bgfisc_Register::getRec('sales_Sales', $saleRec->id);
        if (!is_object($regRec)) {

            return null;
        }

        $pQuery = bgfisc_PrintedReceipts::getQuery();
        $pQuery->where("#urnId = {$regRec->id} AND #type = 'normal' AND #state = 'active'");
        $pQuery->orderBy('id', 'ASC');
        while ($pRec = $pQuery->fetch()) {
            list(, $receiptNum, $receiptDate) = array_pad(explode('*', $pRec->string ?? ''), 3, null);
            if (ctype_digit((string) $receiptNum)) {
                $date = preg_match('/^\d{4}-\d{2}-\d{2}$/', $receiptDate ?? '') ? $receiptDate : dt::mysql2verbal($pRec->createdOn, 'Y-m-d');

                return array($receiptNum, $date);
            }
        }

        return null;
    }


    /**
     * Върнатите на клиентите суми през месеца - РБД и РКО към клиента в нишката на поръчката
     *
     * @param array  $recs
     * @param array  $carts
     * @param string $from
     * @param string $to
     */
    protected function prepareRefunds(&$recs, $carts, $from, $to)
    {
        // Всички операции, при които парите излизат от касата/банката към клиента (прихващането също е плащане към него)
        $docs = array(
            'bank_SpendingDocuments' => array(array('bank2customer', 'bank2customerRet', 'bankAdvance2customer', 'bankAdvance2customerRet'), 1),
            'cash_Rko' => array(array('case2customer', 'case2customerRet', 'caseAdvance2customer', 'caseAdvance2customerRet'), 3),
        );
        // Документите за връщане на стоката по нишки
        $returns = array();
        foreach (static::$deliveryDocs as $masterClass => $detailClass) {
            $rQuery = $masterClass::getQuery();
            $rQuery->where("#state = 'active' AND #isReverse = 'yes'");
            $rQuery->in('threadId', array_keys($carts));
            $rQuery->show('id,threadId');
            while ($rRec = $rQuery->fetch()) {
                $returns[$rRec->threadId][] = array($masterClass, $rRec->id);
            }
        }

        foreach ($docs as $class => $params) {
            list($operations, $rPaym) = $params;
            $query = $class::getQuery();
            $query->where(array("#state = 'active' AND #valior >= '[#1#]' AND #valior <= '[#2#]'", $from, $to));
            $query->in('operationSysId', $operations);
            $query->in('threadId', array_keys($carts));
            while ($dRec = $query->fetch()) {
                $cartRec = $carts[$dRec->threadId];
                $saleRec = sales_Sales::fetch($cartRec->saleId);
                $recs["r{$class}{$dRec->id}"] = (object) array('kind' => 'refund', 'cartId' => $cartRec->id, 'saleId' => $cartRec->saleId,
                    'saleState' => $saleRec->state ?? null, 'ownCompanyId' => $saleRec->ownCompanyId ?? null,
                    'orderDate' => $dRec->valior, 'docs' => $returns[$dRec->threadId] ?? array(), 'payDocs' => array(array($class, $dRec->id)), 'paym' => $rPaym, 'errors' => array(),
                    'total2' => round($dRec->amount * (!empty($dRec->rate) ? $dRec->rate : 1), 2));
            }
        }
    }


    /**
     * Сглобява XML файла, проверява го по схемата на НАП и го записва
     *
     * @param stdClass $rec
     * @param array    $recs
     * @param stdClass $settings  - настройките на магазина
     * @param stdClass $periodRec
     * @param array    $errors    - общите грешки, заради които файлът не е създаден
     *
     * @return string|null - манипулаторът на файла
     */
    protected function generateXml($rec, $recs, $settings, $periodRec, &$errors)
    {
        $orders = $refunds = $ownCompanies = array();
        foreach ($recs as $dRec) {
            if (countR($dRec->errors)) {
                $errors['rows'] = 'Има поръчки с грешки - виж "n/a" в колоната "Начин"';
            }
            $ownCompanies[!empty($dRec->ownCompanyId) ? $dRec->ownCompanyId : crm_Setup::BGERP_OWN_COMPANY_ID] = true;
            if ($dRec->kind == 'order') {
                $orders[] = $dRec;
            } else {
                $refunds[] = $dRec;
            }
        }

        if (!countR($orders)) {
            $errors[] = 'Няма поръчки с доставка през месеца, а схемата на НАП изисква поне една';
        }
        if (countR($ownCompanies) > 1) {
            $errors[] = 'Поръчките и връщанията са на различни наши фирми - магазинът трябва да е на една';
        }
        if (empty($settings->n18ShopNumber)) {
            $errors[] = 'Липсва номерът на магазина от НАП в настройките му';
        }

        $ownCompanyId = countR($ownCompanies) ? key($ownCompanies) : crm_Setup::BGERP_OWN_COMPANY_ID;
        $company = crm_Companies::fetchOwnCompany($ownCompanyId, $periodRec->end);
        $eik = preg_replace('/\D/', '', $company->uicId ?? '');
        if (strlen($eik) < 9) {
            $errors[] = 'Нашата фирма е без валиден ЕИК';
        }
        if (countR($errors)) {

            return null;
        }

        $domain = !empty($settings->n18ShopUrl) ? $settings->n18ShopUrl : cms_Domains::fetchField($rec->domainId, 'domain');
        $tpl = getTplFromFile('eshopn18/tpl/N18Audit.xml');
        $orderBlock = $tpl->getBlock('ORDER');
        $artBlock = $orderBlock->getBlock('ART');
        foreach ($orders as $oRec) {
            $orderTpl = clone $orderBlock;
            foreach ($oRec->lines as $line) {
                $artTpl = clone $artBlock;
                $artTpl->placeArray(self::escapeXml(array('art_name' => $line['name'], 'art_quant' => self::num($line['quantity']),
                    'art_price' => self::num($line['price']), 'art_vat_rate' => $line['vatRate'], 'art_vat' => self::num($line['vat']),
                    'art_sum' => self::num($line['sum']))));
                $artTpl->removeBlocksAndPlaces();
                $orderTpl->append($artTpl, 'ARTS');
            }
            $orderTpl->placeArray(self::escapeXml(array('ord_n' => $oRec->cartId, 'ord_d' => $oRec->orderDate, 'doc_n' => !empty($oRec->regDocId) ? eshopn18_SaleRegDocs::formatNumber($oRec->docNum) : $oRec->docNum,
                'doc_date' => $oRec->docDate ?? $oRec->deliveryDate, 'ord_total1' => self::num($oRec->total1), 'ord_disc' => self::num($oRec->disc),
                'ord_vat' => self::num($oRec->vat), 'ord_total2' => self::num($oRec->total2), 'paym' => $oRec->paym,
                'pos_n' => $oRec->posId, 'trans_n' => $oRec->transactionRef, 'proc_id' => $oRec->procId)));
            $orderTpl->removeBlocksAndPlaces();
            $tpl->append($orderTpl, 'ORDERS');
        }

        $rOrders = array();
        $rTotal = 0;
        $refundBlock = $tpl->getBlock('RORDER');
        foreach ($refunds as $rRec) {
            $rOrders[$rRec->cartId] = true;
            $rTotal += $rRec->total2;
            $refundTpl = clone $refundBlock;
            $refundTpl->placeArray(self::escapeXml(array('r_ord_n' => $rRec->cartId, 'r_amount' => self::num($rRec->total2),
                'r_date' => $rRec->orderDate, 'r_paym' => $rRec->paym)));
            $refundTpl->removeBlocksAndPlaces();
            $tpl->append($refundTpl, 'RORDERS');
        }

        $tpl->placeArray(self::escapeXml(array('eik' => $eik, 'e_shop_n' => $settings->n18ShopNumber, 'domain_name' => $domain,
            'e_shop_type' => $settings->n18ShopType ?? 1, 'creation_date' => dt::today(), 'mon' => dt::mysql2verbal($periodRec->end, 'm'),
            'god' => dt::mysql2verbal($periodRec->end, 'Y'), 'r_ord' => countR($rOrders), 'r_total' => self::num($rTotal))));
        $tpl->removeBlocksAndPlaces();

        // Шаблонът е в UTF-8 - DOM го прехвърля в windows-1251 като в примера на НАП и го проверява по схемата
        $doc = new DOMDocument();
        $doc->preserveWhiteSpace = false;
        $doc->formatOutput = true;
        $prevUseErrors = libxml_use_internal_errors(true);
        libxml_clear_errors();
        try {
            if (!$doc->loadXML('<?xml version="1.0" encoding="UTF-8"?>' . $tpl->getContent()) || !$doc->schemaValidate(getFullPath('eshopn18/xsd/dec_audit.xsd'))) {
                foreach (libxml_get_errors() as $xmlError) {
                    $errors[] = '|*XSD: ' . trim($xmlError->message);
                }
            }
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($prevUseErrors);
        }
        if (countR($errors)) {

            return null;
        }
        $doc->encoding = 'windows-1251';

        $fileName = "N18_{$settings->n18ShopNumber}_" . dt::mysql2verbal($periodRec->end, 'Y_m') . '.xml';

        return fileman::absorbStr($doc->saveXML(), 'exportFiles', $fileName);
    }


    /**
     * Стойностите за XML шаблона - с екранирани специални знаци
     *
     * @param array $values
     *
     * @return array
     */
    protected static function escapeXml($values)
    {
        foreach ($values as &$value) {
            $value = htmlspecialchars((string) $value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        }

        return $values;
    }


    /**
     * Сума с точка и два знака
     *
     * @param float $number
     *
     * @return string
     */
    protected static function num($number)
    {
        return number_format(round($number ?? 0, 2), 2, '.', '');
    }


    /**
     * Връща фийлдсета на таблицата, която ще се рендира
     *
     * @param stdClass $rec
     * @param bool     $export
     *
     * @return core_FieldSet
     */
    protected function getTableFieldSet($rec, $export = false)
    {
        $fld = cls::get('core_FieldSet');
        $fld->FLD('saleId', 'varchar', 'caption=Продажба');
        $fld->FLD('cartId', 'varchar', 'caption=Поръчка,tdClass=aright');
        $fld->FLD('orderDate', 'date', 'caption=Дата');
        $fld->FLD('kind', 'varchar', 'caption=Вид,smartCenter');
        // Сумите са в основната валута на месеца, както в XML файла
        $baseCurrencyId = acc_Periods::fetchField($rec->periodId ?? null, 'baseCurrencyId');
        $currency = !empty($baseCurrencyId) ? currency_Currencies::getCodeById($baseCurrencyId) : '';
        $fld->FLD('net', 'double(decimals=2)', "caption=Стойност|* {$currency}->Без ДДС,tdClass=stageCol");
        $fld->FLD('vat', 'double(decimals=2)', "caption=Стойност|* {$currency}->ДДС,tdClass=stageCol");
        $fld->FLD('total2', 'double(decimals=2)', "caption=Стойност|* {$currency}->Общо,tdClass=stageCol");
        $fld->FLD('docs', 'varchar', 'caption=Документи,tdClass=rightCol');
        $fld->FLD('paymName', 'varchar', 'caption=Плащане->Начин,smartCenter');
        $fld->FLD('docNum', 'varchar', 'caption=Плащане->Документ/Бон,tdClass=rightCol');
        if ($export) {
            $fld->FLD('transactionRef', 'varchar', 'caption=Плащане->Трансакция');
            $fld->FLD('paym', 'varchar', 'caption=Плащане->Код');
        }

        return $fld;
    }


    /**
     * Вербализиране на редовете, които ще се показват на текущата страница в отчета
     *
     * @param stdClass $rec
     * @param stdClass $dRec
     *
     * @return stdClass
     */
    protected function detailRecToVerbal($rec, &$dRec)
    {
        $Double = core_Type::getByName('double(decimals=2)');
        $row = new stdClass();

        // По-старите версии на справката нямат запазено състояние на продажбата
        $saleState = $dRec->saleState ?? (!empty($dRec->saleId) ? sales_Sales::fetchField($dRec->saleId, 'state') : null);
        if (!empty($saleState)) {
            $row->ROW_ATTR['class'] = "state-{$saleState}";
        }
        $row->kind = ($dRec->kind == 'order') ? static::renderBadge(tr('Поръчка')) : static::renderBadge(tr('Връщане'), false, 'amber');
        $canLink = static::canLink();
        $row->cartId = ($canLink && eshop_Carts::haveRightFor('single', $dRec->cartId)) ? ht::createLink($dRec->cartId, array('eshop_Carts', 'single', $dRec->cartId)) : $dRec->cartId;
        if (!empty($dRec->saleId)) {
            $row->saleId = sales_Sales::getLink($dRec->saleId, 0);
        }
        $row->orderDate = dt::mysql2verbal($dRec->orderDate, 'smartDate');
        $docs = array();
        foreach ((array) $dRec->docs as $doc) {
            list($class, $id) = $doc;
            $docs[] = $class::getLink($id, 0);
        }
        $row->docs = implode('<br>', $docs);
        // Крайната сума без ДДС - отстъпката е отделно само в XML файла
        if (isset($dRec->total1)) {
            $dRec->net = round($dRec->total1 - ($dRec->disc ?? 0), 2);
        }
        foreach (array('net', 'vat', 'total2') as $fld) {
            if (isset($dRec->{$fld})) {
                $row->{$fld} = $Double->toVerbal($dRec->{$fld});
            }
        }
        if (isset($row->total2)) {
            $row->total2 = "<b>{$row->total2}</b>";
        }
        if (!empty($dRec->docNum)) {
            $row->docNum = core_Type::escape($dRec->docNum);
            if (!empty($dRec->regDocId)) {
                $row->docNum = eshopn18_SaleRegDocs::formatNumber($dRec->docNum);
                if ($canLink) {
                    $row->docNum = ht::createLink($row->docNum, array('eshopn18_SaleRegDocs', 'single', $dRec->regDocId));
                }

                // Трансакцията е в хинта като HTML, за да остане линкът към плащането с карта
                $hint = tr('Документ по чл. 52о (плащане с карта)');
                if (!empty($dRec->transactionRef)) {
                    $transaction = core_Type::escape($dRec->transactionRef);
                    if ($canLink && cond_CardPayments::haveRightFor('list')) {
                        $transaction = ht::createLink($transaction, array('cond_CardPayments', 'list', 'selectPeriod' => 'gr0', 'search' => $dRec->transactionRef));
                    }
                    $hint .= '<br>' . tr('Трансакция') . ': ' . $transaction;
                }
                $row->docNum = ht::createHint($row->docNum, new core_ET($hint), 'notice', false,
                    array('isHtml' => true, 'aiHint' => 'Art. 52o card payment document' . (!empty($dRec->transactionRef) ? ", transaction {$dRec->transactionRef}" : '')));
            } elseif (($dRec->paym ?? null) == 6) {
                if ($canLink && bgfisc_PrintedReceipts::haveRightFor('list')) {
                    $row->docNum = ht::createLink($row->docNum, array('bgfisc_PrintedReceipts', 'list', 'selectPeriod' => 'gr0', 'search' => $dRec->docNum));
                }
                $row->docNum = ht::createHint($row->docNum, 'Касов бон', 'notice', false, array('aiHint' => 'Fiscal receipt'));
            }
        }

        // Без документ по чл. 52о и бон се показват платежните документи - само на екрана, в XML файла номерът остава 0
        if (empty($dRec->docNum) && countR($dRec->payDocs ?? null)) {
            $payDocs = array();
            foreach ($dRec->payDocs as $doc) {
                list($class, $id) = $doc;
                $payDocs[] = $class::getLink($id, 0);
            }
            $row->docNum = implode('<br>', $payDocs);
        }

        // Начинът на плащане - цветно балонче с кода от XML файла в хинта
        $kind = ($dRec->kind == 'order') ? 'order' : 'refund';
        $code = $dRec->paym ?? null;
        if (countR($dRec->errors)) {

            // Редът с грешка не влиза във файла - вместо начина излиза n/a с причината
            core_Lg::push('en');
            $aiHint = 'Error: ' . implode('; ', array_map('tr', $dRec->errors));
            core_Lg::pop();
            $row->paymName = ht::createHint(static::renderBadge('n/a', true), '|*' . implode('. ', array_map('tr', $dRec->errors)), 'noicon', false, array('aiHint' => $aiHint));
            $row->paymName = "<span class='type-badge-hint'>{$row->paymName}</span>";
        } elseif (isset(static::$paymentCodes[$kind][$code])) {
            $xmlField = ($kind == 'order') ? 'PAYM' : 'R_PAYM';
            $badge = static::renderBadge(tr(static::$paymentShort[$kind][$code]), false, static::$paymentColors[$code] ?? null);
            $row->paymName = ht::createHint($badge, '|*' . tr(static::$paymentCodes[$kind][$code]) . " ({$xmlField} = {$code})", 'noicon', false,
                array('aiHint' => "{$xmlField} {$code}: " . static::$paymentCodesAi[$kind][$code]));
            $row->paymName = "<span class='type-badge-hint'>{$row->paymName}</span>";
        }

        return $row;
    }


    /**
     * Редът за експорт - документите и кодовете като текст
     *
     * @param frame2_driver_Proto $Driver
     * @param stdClass            $res
     * @param stdClass            $rec
     * @param stdClass            $dRec
     * @param mixed               $ExportClass
     */
    protected static function on_AfterGetExportRec(frame2_driver_Proto $Driver, &$res, $rec, $dRec, $ExportClass)
    {
        // Копие - иначе се променят данните на справката
        $res = clone $res;
        $res->kind = ($dRec->kind == 'order') ? tr('Поръчка') : tr('Връщане');
        $res->saleId = !empty($dRec->saleId) ? '#' . sales_Sales::getHandle($dRec->saleId) : null;
        $docs = array();
        foreach ((array) ($dRec->docs ?? array()) as $doc) {
            list($class, $id) = $doc;
            $docs[] = '#' . $class::getHandle($id);
        }
        $res->docs = implode(', ', $docs);
        $res->net = isset($dRec->total1) ? round($dRec->total1 - ($dRec->disc ?? 0), 2) : null;
        $res->docNum = !empty($dRec->regDocId) ? eshopn18_SaleRegDocs::formatNumber($dRec->docNum) : (!empty($dRec->docNum) ? $dRec->docNum : null);
        if (empty($res->docNum) && countR($dRec->payDocs ?? null)) {
            $res->docNum = implode(', ', array_map(function ($doc) {
                return '#' . $doc[0]::getHandle($doc[1]);
            }, $dRec->payDocs));
        }
        $res->paym = countR($dRec->errors ?? array()) ? 'n/a' : ($dRec->paym ?? null);
        $kind = (($dRec->kind ?? null) == 'order') ? 'order' : 'refund';
        $res->paymName = isset(static::$paymentCodes[$kind][$dRec->paym ?? null]) ? tr(static::$paymentCodes[$kind][$dRec->paym]) : null;
        unset($res->errors, $res->lines);
    }


    /**
     * Балонче на екрана, обикновен текст при печат, PDF и текстов режим
     *
     * @param string      $text
     * @param bool        $isRed
     * @param string|null $color
     *
     * @return string
     */
    protected static function renderBadge($text, $isRed = false, $color = null)
    {
        return static::isPlainMode() ? $text : cat_Boms::renderBadge($text, $isRed, $color);
    }


    /**
     * Линкове само за влязъл потребител на екрана - не при печат, PDF и публичния изглед на документа
     *
     * @return bool
     */
    protected static function canLink()
    {
        return !static::isPlainMode() && haveRole('user');
    }


    /**
     * Печат, PDF или текстов режим - без балончета и линкове
     *
     * @return bool
     */
    protected static function isPlainMode()
    {
        return Mode::is('printing') || Mode::is('pdf') || Mode::is('text', 'xhtml') || Mode::is('text', 'plain');
    }


    /**
     * Файлът и общите грешки над таблицата
     *
     * @param frame2_driver_Proto $Driver
     * @param embed_Manager       $Embedder
     * @param core_ET             $tpl
     * @param stdClass            $data
     */
    protected static function on_AfterRenderSingle(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$tpl, $data)
    {
        $fieldTpl = new core_ET(tr("|*<fieldset class='detail-info'><legend class='groupTitle'><small><b>|Филтър|*</b></small></legend>
                                    <div class='small'>
                                        <div>|Магазин|*: [#domainId#]</div>
                                        <div>|Месец|*: [#periodId#]</div>
                                        <div>|Наша фирма|*: [#ownCompanyId#]</div>
                                        <div><b>|Включени са само онлайн поръчките от избрания домейн с доставка през месеца|*</b></div>
                                    </div>
                                </fieldset>
                                <fieldset class='detail-info'><legend class='groupTitle'><small><b>|XML файл за НАП|*</b></small></legend>
                                    <div class='small'>[#file#]</div>
                                </fieldset>"));

        $rec = $data->rec;
        $isPlain = !static::canLink();
        $domain = $isPlain ? cms_Domains::getTitleById($rec->domainId ?? null) : cms_Domains::getHyperlink($rec->domainId ?? null, true);
        $fieldTpl->replace("<b>{$domain}</b>", 'domainId');
        // Месецът води към онлайн продажбите с вальор в него
        $periodRec = acc_Periods::fetch($rec->periodId ?? null);
        $period = is_object($periodRec) ? core_Type::escape($periodRec->title) : '';
        if (is_object($periodRec) && !$isPlain && sales_Sales::haveRightFor('list')) {
            $period = ht::createLink($period, array('sales_Sales', 'list', 'type' => 'onlineSale', 'users' => 'all_users', 'filterDateField' => 'valior',
                'selectPeriod' => 'select', 'from' => $periodRec->start, 'to' => $periodRec->end));
        }
        $fieldTpl->replace("<b>{$period}</b>", 'periodId');

        // Нашата фирма от настройките на магазина към края на месеца (при holding - избраната там, иначе основната)
        if (!empty($rec->domainId)) {
            $periodEnd = acc_Periods::fetchField($rec->periodId ?? null, 'end');
            $settings = cms_Domains::getSettings($rec->domainId, $periodEnd ?: null);
            $ownCompanyId = cls::load('holding_plg_EshopCarts', true) ? holding_plg_EshopCarts::getOwnCompanyId($settings) : crm_Setup::BGERP_OWN_COMPANY_ID;
            $company = $isPlain ? crm_Companies::getTitleById($ownCompanyId) : crm_Companies::getHyperlink($ownCompanyId, true);
            $fieldTpl->replace("<b>{$company}</b>", 'ownCompanyId');
        }

        $reportData = $rec->data ?? null;
        $fh = is_object($reportData) ? ($reportData->n18FileHnd ?? null) : null;
        $errors = is_object($reportData) ? ($reportData->n18Errors ?? array()) : array();
        if (!empty($fh)) {
            $fieldTpl->replace($isPlain ? core_Type::escape(fileman_Files::fetchByFh($fh, 'name')) : fileman::getLinkToSingle($fh), 'file');
        } elseif (countR($errors)) {
            $fieldTpl->replace("<span class='red'>" . tr('Не е създаден') . ':<br>' . implode('<br>', array_map(function ($e) {
                return core_Type::escape(tr($e));
            }, $errors)) . '</span>', 'file');
        }

        $tpl->append($fieldTpl, 'DRIVER_FIELDS');
    }
}
